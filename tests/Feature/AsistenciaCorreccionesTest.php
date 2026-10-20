<?php

namespace Tests\Feature;

use App\Events\AsistenciaRegistrada;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AsistenciaCorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/asistencias/marcar';

    private function headers(): array
    {
        return [
            'X-Raspberry-Token' => config('services.raspberry.token'),
            'Accept' => 'application/json',
        ];
    }

    private function estudiante(string $codigo = 'EST-2026-0001', string $curso = '1'): Estudiante
    {
        return Estudiante::create([
            'codigo_estudiante' => $codigo,
            'nombres' => 'Ana',
            'apellidos' => 'Quispe',
            'ci' => '1234567',
            'curso' => $curso,
            'paralelo' => 'A',
            'qr_data' => $codigo . '|Ana Quispe|' . $curso,
            'estado' => 'activo',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'secreto123',
        ]);
    }

    public function test_token_invalido_devuelve_401(): void
    {
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], ['X-Raspberry-Token' => 'otro'])
            ->assertStatus(401);
    }

    public function test_hasta_13_45_es_presente_y_despues_tardanza(): void
    {
        $this->estudiante('EST-2026-0001');
        $this->estudiante('EST-2026-0002');

        $this->travelTo(Carbon::parse('2026-10-19 13:45:00'));
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('estudiante.estado', 'Presente');

        $this->travelTo(Carbon::parse('2026-10-19 13:46:00'));
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0002'], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('estudiante.estado', 'Tardanza');
    }

    public function test_la_hora_limite_sale_de_configuracion(): void
    {
        $this->estudiante();
        Configuracion::create(['clave' => 'hora_ingreso', 'valor' => '14:00', 'grupo' => 'horarios']);
        Configuracion::create(['clave' => 'tolerancia_minutos', 'valor' => '5', 'grupo' => 'parametros']);

        $this->travelTo(Carbon::parse('2026-10-19 14:04:00'));
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())
            ->assertJsonPath('estudiante.estado', 'Presente');
    }

    public function test_segunda_lectura_del_dia_es_duplicado(): void
    {
        $this->estudiante();
        $this->travelTo(Carbon::parse('2026-10-19 13:35:00'));

        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())->assertStatus(201);
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())
            ->assertStatus(200)
            ->assertJsonPath('duplicado', true);

        $this->assertSame(1, Asistencia::count());
    }

    public function test_acepta_el_contenido_completo_del_qr(): void
    {
        $this->estudiante();
        $this->travelTo(Carbon::parse('2026-10-19 13:35:00'));

        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001|Ana Quispe|1'], $this->headers())
            ->assertStatus(201);
    }

    public function test_sincronizacion_offline_usa_la_hora_real_de_lectura(): void
    {
        $this->estudiante();
        $this->travelTo(Carbon::parse('2026-10-19 15:20:00'));

        $this->postJson(self::URL, [
            'codigo_estudiante' => 'EST-2026-0001',
            'fecha_hora' => '2026-10-19T13:35:10',
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('estudiante.estado', 'Presente');

        $asistencia = Asistencia::first();
        $this->assertSame('13:35:10', Carbon::parse($asistencia->hora_ingreso)->format('H:i:s'));
        $this->assertSame('2026-10-19', Carbon::parse($asistencia->fecha)->toDateString());
    }

    public function test_fecha_hora_en_el_futuro_se_rechaza(): void
    {
        $this->estudiante();
        $this->travelTo(Carbon::parse('2026-10-19 13:35:00'));

        $this->postJson(self::URL, [
            'codigo_estudiante' => 'EST-2026-0001',
            'fecha_hora' => '2026-10-19T14:00:00',
        ], $this->headers())->assertStatus(422);
    }

    public function test_ausente_que_luego_escanea_pasa_a_tardanza(): void
    {
        $estudiante = $this->estudiante();
        Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'fecha' => '2026-10-19',
            'hora_ingreso' => null,
            'estado' => Asistencia::AUSENTE,
            'registrado_por' => 'sistema',
        ]);

        $this->travelTo(Carbon::parse('2026-10-19 14:10:00'));
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())
            ->assertStatus(200)
            ->assertJsonPath('actualizado', true)
            ->assertJsonPath('estudiante.estado', 'Tardanza');

        $this->assertSame(1, Asistencia::count());
        $this->assertSame(Asistencia::TARDANZA, Asistencia::first()->estado);
    }

    public function test_si_falla_el_broadcast_la_asistencia_igual_se_guarda(): void
    {
        $this->estudiante();
        Event::listen(AsistenciaRegistrada::class, function () {
            throw new \RuntimeException('Reverb apagado');
        });

        $this->travelTo(Carbon::parse('2026-10-19 13:35:00'));
        $this->postJson(self::URL, ['codigo_estudiante' => 'EST-2026-0001'], $this->headers())
            ->assertStatus(201);

        $this->assertSame(1, Asistencia::count());
    }

    public function test_el_evento_no_expone_ci_del_estudiante(): void
    {
        $estudiante = $this->estudiante();
        $asistencia = Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'fecha' => '2026-10-19',
            'hora_ingreso' => '13:35:00',
            'estado' => Asistencia::PRESENTE,
        ]);

        $datos = (new AsistenciaRegistrada($asistencia))->broadcastWith();

        $this->assertSame('Ana', $datos['asistencia']['estudiante']['nombres']);
        $this->assertArrayNotHasKey('ci', $datos['asistencia']['estudiante']);
    }

    public function test_indice_unico_impide_dos_asistencias_el_mismo_dia(): void
    {
        $estudiante = $this->estudiante();
        $datos = ['estudiante_id' => $estudiante->id, 'fecha' => '2026-10-19', 'estado' => Asistencia::PRESENTE];
        Asistencia::create($datos);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Asistencia::create($datos);
    }

    public function test_asistencia_manual_admin_registra_falta(): void
    {
        $estudiante = $this->estudiante();

        $this->actingAs($this->admin(), 'web')
            ->post(route('asistencias.manual.store'), [
                'fecha' => '2026-10-19',
                'estados' => [$estudiante->id => 'ausente'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $asistencia = Asistencia::first();
        $this->assertSame(Asistencia::AUSENTE, $asistencia->estado);
        $this->assertNull($asistencia->hora_ingreso);
    }

    public function test_reporte_por_curso_cuenta_las_tardanzas(): void
    {
        $estudiante = $this->estudiante('EST-2026-0001', '1');
        Asistencia::create([
            'estudiante_id' => $estudiante->id,
            'fecha' => '2026-10-19',
            'hora_ingreso' => '13:50:00',
            'estado' => Asistencia::TARDANZA,
        ]);

        $response = $this->actingAs($this->admin(), 'web')
            ->get(route('reportes.index', ['fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-31', 'curso' => '1']))
            ->assertOk();

        $response->assertViewHas('totalTardanzas', 1);
        $response->assertViewHas('reporteCursos', fn ($filas) => $filas->count() === 1
            && $filas->first()['curso'] === '1ro Secundaria'
            && $filas->first()['tardanzas'] === 1
            && $filas->first()['total_estudiantes'] === 1);
    }

    public function test_admin_puede_eliminar_un_examen(): void
    {
        $materia = Materia::create(['nombre' => 'Matemáticas', 'codigo' => 'MAT']);
        $docente = Docente::create([
            'nombres' => 'Luis',
            'apellidos' => 'Rojas',
            'ci' => '7654321',
            'materia_id' => $materia->id,
            'password' => bcrypt('clave123'),
            'estado' => 'activo',
        ]);
        $examen = Examen::create([
            'docente_id' => $docente->id,
            'materia_id' => $materia->id,
            'curso' => 1,
            'paralelo' => 'A',
            'titulo' => 'Primer parcial',
            'fecha' => '2026-10-25',
        ]);

        $this->actingAs($this->admin(), 'web')
            ->delete(route('examenes.destroy', $examen))
            ->assertRedirect(route('examenes.index'));

        $this->assertDatabaseMissing('examenes', ['id' => $examen->id]);
    }
}
