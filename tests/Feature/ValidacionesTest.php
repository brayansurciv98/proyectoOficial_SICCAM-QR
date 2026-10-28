<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Docente;
use App\Models\DocenteCurso;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Notificacion;
use App\Models\Tutor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ValidacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // los push de Firebase no salen en las pruebas
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secreto123']);
    }

    private function materia(string $nombre = 'Química'): Materia
    {
        return Materia::create(['nombre' => $nombre, 'codigo' => strtoupper(substr($nombre, 0, 3))]);
    }

    private function docente(Materia $materia, array $cursos = [[4, 'A']], string $ci = '7654321'): Docente
    {
        $docente = Docente::create([
            'nombres' => 'Mario', 'apellidos' => 'Aguirre', 'ci' => $ci,
            'materia_id' => $materia->id, 'password' => bcrypt('clave123'), 'estado' => 'activo',
        ]);
        foreach ($cursos as [$curso, $paralelo]) {
            DocenteCurso::create(['docente_id' => $docente->id, 'curso' => $curso, 'paralelo' => $paralelo]);
        }
        return $docente;
    }

    private function horario(Docente $docente, int $dia, int $bloque, int $curso, string $paralelo): Horario
    {
        [$inicio, $fin] = Horario::horasBloque($bloque);
        return Horario::create([
            'dia' => $dia, 'bloque' => $bloque, 'hora_inicio' => $inicio, 'hora_fin' => $fin,
            'curso' => $curso, 'paralelo' => $paralelo, 'materia_id' => $docente->materia_id,
            'docente_id' => $docente->id, 'estado' => 'activo',
        ]);
    }

    private function estudiante(string $codigo = 'EST-2026-0001', ?string $ci = '1234567'): Estudiante
    {
        return Estudiante::create([
            'codigo_estudiante' => $codigo, 'nombres' => 'Ana', 'apellidos' => 'Quispe', 'ci' => $ci,
            'curso' => 4, 'paralelo' => 'A', 'qr_data' => $codigo, 'estado' => 'activo',
        ]);
    }

    private function datosEstudiante(array $cambios = []): array
    {
        return array_merge([
            'tutor_nombres' => 'Rosa', 'tutor_apellidos' => 'Mamani', 'tutor_ci' => '4455667',
            'nombres' => 'Luis', 'apellidos' => 'Rojas', 'curso' => 4, 'paralelo' => 'A',
        ], $cambios);
    }

    // ---------- Docentes ----------

    public function test_docente_con_curso_repetido_da_error_y_no_falla(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->post(route('docentes.store'), [
                'nombres' => 'Mario', 'apellidos' => 'Aguirre', 'ci' => '7654321',
                'materia_id' => $this->materia()->id,
                'cursos' => [['curso' => 4, 'paralelo' => 'A'], ['curso' => 4, 'paralelo' => 'A']],
            ])
            ->assertSessionHasErrors('cursos');

        $this->assertSame(0, Docente::count());
    }

    public function test_docente_guarda_su_genero_y_se_muestra_su_icono(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->post(route('docentes.store'), [
                'nombres' => 'Rosa', 'apellidos' => 'Choque', 'genero' => 'F', 'ci' => '7654321',
                'materia_id' => $this->materia()->id,
                'cursos' => [['curso' => 4, 'paralelo' => 'A']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('F', Docente::first()->genero);

        // Ícono de mujer: el pelo largo detrás de la cabeza
        $this->actingAs($admin, 'web')->get(route('docentes.index'))
            ->assertSee('M18.5 30 C17 15', false);

        $this->actingAs($admin, 'web')
            ->post(route('docentes.store'), [
                'nombres' => 'Luis', 'apellidos' => 'Rojas', 'genero' => 'X', 'ci' => '7654322',
                'materia_id' => Docente::first()->materia_id,
                'cursos' => [['curso' => 5, 'paralelo' => 'B']],
            ])
            ->assertSessionHasErrors('genero');
    }

    public function test_docente_sin_ci_ni_correo_no_se_registra(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->post(route('docentes.store'), [
                'nombres' => 'Mario', 'apellidos' => 'Aguirre',
                'materia_id' => $this->materia()->id,
                'cursos' => [['curso' => 4, 'paralelo' => 'A']],
            ])
            ->assertSessionHasErrors(['ci', 'email']);
    }

    public function test_no_se_quita_un_curso_ni_la_materia_si_tiene_horarios(): void
    {
        $quimica = $this->materia();
        $fisica = $this->materia('Física');
        $docente = $this->docente($quimica, [[4, 'A'], [5, 'B']]);
        $this->horario($docente, 1, 1, 4, 'A');

        $base = ['nombres' => 'Mario', 'apellidos' => 'Aguirre', 'ci' => '7654321', 'estado' => 'activo'];
        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->put(route('docentes.update', $docente), $base + ['materia_id' => $quimica->id, 'cursos' => [['curso' => 5, 'paralelo' => 'B']]])
            ->assertSessionHasErrors('cursos');

        $this->actingAs($admin, 'web')
            ->put(route('docentes.update', $docente), $base + ['materia_id' => $fisica->id, 'cursos' => [['curso' => 4, 'paralelo' => 'A']]])
            ->assertSessionHasErrors('materia_id');

        // Quitar un curso sin clases sí se puede
        $this->actingAs($admin, 'web')
            ->put(route('docentes.update', $docente), $base + ['materia_id' => $quimica->id, 'cursos' => [['curso' => 4, 'paralelo' => 'A']]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $docente->cursos()->count());
    }

    // ---------- Horarios ----------

    public function test_horario_rechaza_docente_ocupado_y_curso_no_asignado(): void
    {
        $docente = $this->docente($this->materia(), [[4, 'A'], [5, 'B']]);
        $this->horario($docente, 1, 2, 4, 'A');
        $admin = $this->admin();
        $datos = ['materia_id' => $docente->materia_id, 'docente_id' => $docente->id];

        // Mismo día y bloque con otro curso: el docente ya está en clase
        $this->actingAs($admin, 'web')
            ->post(route('horarios.store'), $datos + ['dia' => 1, 'bloque' => 2, 'curso' => 5, 'paralelo' => 'B'])
            ->assertSessionHasErrors('bloque');

        // Curso que no tiene asignado
        $this->actingAs($admin, 'web')
            ->post(route('horarios.store'), $datos + ['dia' => 2, 'bloque' => 1, 'curso' => 6, 'paralelo' => 'C'])
            ->assertSessionHasErrors('curso');

        // Caso correcto
        $this->actingAs($admin, 'web')
            ->post(route('horarios.store'), $datos + ['dia' => 2, 'bloque' => 1, 'curso' => 5, 'paralelo' => 'B'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Horario::count());
    }

    // ---------- Estudiantes y tutores ----------

    public function test_estudiante_valida_ci_repetido_paralelo_y_fecha_de_nacimiento(): void
    {
        $this->estudiante('EST-2026-0001', '1234567');
        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->post(route('estudiantes.store'), $this->datosEstudiante(['ci' => '1234567']))
            ->assertSessionHasErrors('ci');

        $this->actingAs($admin, 'web')
            ->post(route('estudiantes.store'), $this->datosEstudiante(['paralelo' => '']))
            ->assertSessionHasErrors('paralelo');

        $this->actingAs($admin, 'web')
            ->post(route('estudiantes.store'), $this->datosEstudiante(['fecha_nacimiento' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('fecha_nacimiento');

        $this->assertSame(1, Estudiante::count());
    }

    public function test_tutor_sin_ci_ni_correo_no_se_registra(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->post(route('estudiantes.store'), $this->datosEstudiante(['tutor_ci' => '']))
            ->assertSessionHasErrors(['tutor_ci', 'tutor_email']);

        $this->assertSame(0, Tutor::count());
    }

    public function test_editar_estudiante_con_correo_de_otro_tutor_da_error_y_no_falla(): void
    {
        Tutor::create(['nombres' => 'Otro', 'apellidos' => 'Tutor', 'ci' => '9988776', 'email' => 'otro@correo.com', 'estado' => 'activo']);
        $propio = Tutor::create(['nombres' => 'Rosa', 'apellidos' => 'Mamani', 'ci' => '4455667', 'estado' => 'activo']);
        $estudiante = $this->estudiante();
        $estudiante->tutores()->attach($propio->id, ['parentesco' => 'Madre', 'es_principal' => true]);

        $this->actingAs($this->admin(), 'web')
            ->put(route('estudiantes.update', $estudiante), [
                'nombres' => 'Ana', 'apellidos' => 'Quispe', 'curso' => 4, 'paralelo' => 'A', 'estado' => 'activo',
                'tutor_id' => $propio->id, 'tutor_nombres' => 'Rosa', 'tutor_apellidos' => 'Mamani',
                'tutor_ci' => '4455667', 'tutor_email' => 'otro@correo.com',
            ])
            ->assertSessionHasErrors('tutor_email');

        $this->assertNull($propio->fresh()->email);
    }

    // ---------- Configuración y cierre de ausencias ----------

    public function test_configuracion_valida_horas_y_solo_guarda_campos_conocidos(): void
    {
        $admin = $this->admin();

        // Ausencias antes de "presente hasta" (13:30 + 15 = 13:45)
        $this->actingAs($admin, 'web')
            ->post(route('configuracion.store'), ['tab_active' => 'horarios', 'hora_ingreso' => '13:30', 'hora_marcar_ausencias' => '13:40'])
            ->assertSessionHasErrors('hora_marcar_ausencias');

        $this->actingAs($admin, 'web')
            ->post(route('configuracion.store'), ['tab_active' => 'parametros', 'tolerancia_minutos' => '-5', 'notificar_ausencia' => 'si'])
            ->assertSessionHasErrors('tolerancia_minutos');

        $this->actingAs($admin, 'web')
            ->post(route('configuracion.store'), [
                'tab_active' => 'horarios', 'hora_ingreso' => '14:00', 'hora_marcar_ausencias' => '14:30', 'campo_inventado' => 'x',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('14:30', Configuracion::valor('hora_marcar_ausencias'));
        $this->assertNull(Configuracion::valor('campo_inventado'));
    }

    public function test_ausencias_se_marcan_a_la_hora_de_configuracion_una_vez_al_dia(): void
    {
        Configuracion::create(['clave' => 'hora_ingreso', 'valor' => '14:00', 'grupo' => 'horarios']);
        Configuracion::create(['clave' => 'hora_marcar_ausencias', 'valor' => '14:20', 'grupo' => 'horarios']);
        $this->estudiante();

        $this->travelTo(Carbon::parse('2026-10-28 14:10:00')); // miércoles, antes de la hora
        $this->artisan('asistencias:cerrar-ingreso')->assertSuccessful();
        $this->assertSame(0, Asistencia::count());

        $this->travelTo(Carbon::parse('2026-10-28 14:25:00'));
        $this->artisan('asistencias:cerrar-ingreso')->assertSuccessful();
        $this->assertSame(Asistencia::AUSENTE, Asistencia::first()->estado);

        // Un estudiante nuevo después del cierre no se marca: ya se ejecutó hoy
        $this->estudiante('EST-2026-0002', null);
        $this->artisan('asistencias:cerrar-ingreso')->assertSuccessful();
        $this->assertSame(1, Asistencia::count());
    }

    public function test_si_se_desactiva_el_aviso_las_ausencias_no_se_notifican(): void
    {
        Configuracion::create(['clave' => 'notificar_ausencia', 'valor' => 'no', 'grupo' => 'parametros']);
        $tutor = Tutor::create(['nombres' => 'Rosa', 'apellidos' => 'Mamani', 'ci' => '4455667', 'estado' => 'activo']);
        $this->estudiante()->tutores()->attach($tutor->id, ['parentesco' => 'Madre', 'es_principal' => true]);

        $this->travelTo(Carbon::parse('2026-10-28 15:00:00'));
        $this->artisan('asistencias:cerrar-ingreso')->assertSuccessful();

        $this->assertSame(1, Asistencia::where('estado', Asistencia::AUSENTE)->count());
        $this->assertSame(0, Notificacion::count());
    }

    // ---------- Asistencia manual, comunicados y reportes ----------

    public function test_asistencia_manual_no_acepta_fechas_futuras(): void
    {
        $estudiante = $this->estudiante();

        $this->actingAs($this->admin(), 'web')
            ->post(route('asistencias.manual.store'), [
                'fecha' => now()->addDay()->toDateString(),
                'estados' => [$estudiante->id => 'presente'],
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_comunicado_con_mensaje_demasiado_largo_se_rechaza(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->post(route('comunicados.store'), [
                'titulo' => 'Reunión', 'destinatario' => 'todos', 'accion' => 'borrador',
                'mensaje' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('mensaje');
    }

    public function test_reportes_con_fechas_mal_escritas_no_fallan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->get(route('reportes.index', ['fecha_desde' => 'abc', 'fecha_hasta' => '2026-13-45']))
            ->assertOk();

        $this->actingAs($admin, 'web')
            ->get(route('reportes.pdf', ['fecha_desde' => 'abc', 'fecha_hasta' => 'xyz']))
            ->assertOk();
    }

    // ---------- Pantallas modificadas ----------

    public function test_las_pantallas_modificadas_se_muestran_sin_errores(): void
    {
        $docente = $this->docente($this->materia(), [[4, 'A']]);
        $tutor = Tutor::create(['nombres' => 'Rosa', 'apellidos' => 'Mamani', 'ci' => '4455667', 'estado' => 'activo']);
        $estudiante = $this->estudiante();
        $estudiante->tutores()->attach($tutor->id, ['parentesco' => 'Madre', 'es_principal' => true]);
        Configuracion::create(['clave' => 'colegio_nombre', 'valor' => 'Unidad Educativa Prueba', 'grupo' => 'institucion']);

        $this->actingAs($this->admin(), 'web');

        foreach ([
            route('dashboard'),
            route('configuracion.index', ['tab' => 'horarios']),
            route('estudiantes.index'),
            route('estudiantes.show', $estudiante),
            route('estudiantes.create'),
            route('estudiantes.edit', $estudiante),
            route('reportes.resultados'),
            route('asistencias.index'),
            route('docentes.index'),
            route('docentes.create'),
            route('docentes.edit', $docente),
            route('horarios.create'),
            route('horarios.index', ['curso' => 4, 'paralelo' => 'A']),
            route('comunicados.index'),
            route('asistencias.manual', ['curso' => 4]),
            route('reportes.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('U.E. Prueba');
        }

        $this->get(route('configuracion.index'))->assertSee('Marcar ausencias a las')->assertDontSee('WhatsApp');

        // Íconos de persona según el rol (estudiante y su tutor, docente)
        $this->get(route('estudiantes.index'))
            ->assertSee('aria-label="Estudiante"', false)
            ->assertSee('aria-label="Tutor"', false);
        $this->get(route('docentes.index'))->assertSee('aria-label="Docente"', false);
    }

    // ---------- Inicio de sesión ----------

    public function test_login_web_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/', ['email' => 'admin@test.local', 'password' => 'incorrecta']);
        }

        $this->post('/', ['email' => 'admin@test.local', 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest('web');
    }

    public function test_login_de_la_app_usa_el_mismo_mensaje_para_usuario_inexistente_y_clave_incorrecta(): void
    {
        Tutor::create(['nombres' => 'Rosa', 'apellidos' => 'Mamani', 'ci' => '4455667', 'password' => bcrypt('clave123'), 'estado' => 'activo']);

        $inexistente = $this->postJson('/api/tutor/login', ['login' => '0000000', 'password' => 'x'])->assertStatus(401);
        $claveMala = $this->postJson('/api/tutor/login', ['login' => '4455667', 'password' => 'x'])->assertStatus(401);

        $this->assertSame($inexistente->json('message'), $claveMala->json('message'));
        $this->postJson('/api/tutor/login', ['login' => '4455667', 'password' => 'clave123'])->assertOk();
    }
}
