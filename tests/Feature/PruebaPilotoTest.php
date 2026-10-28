<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Notificacion;
use App\Models\Tutor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruebaPilotoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secreto123']);
    }

    private function estudiante(string $codigo, int $curso = 4, string $paralelo = 'A'): Estudiante
    {
        return Estudiante::create([
            'codigo_estudiante' => $codigo, 'nombres' => 'Ana', 'apellidos' => 'Quispe',
            'curso' => $curso, 'paralelo' => $paralelo, 'qr_data' => $codigo . '|Ana Quispe|' . $curso, 'estado' => 'activo',
        ]);
    }

    public function test_credenciales_qr_por_curso_y_de_un_estudiante(): void
    {
        $uno = $this->estudiante('EST-2026-0001');
        $this->estudiante('EST-2026-0002');
        $admin = $this->admin();

        $this->actingAs($admin, 'web')
            ->get(route('estudiantes.credenciales.qr', ['curso' => 4, 'paralelo' => 'A']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin, 'web')
            ->get(route('estudiantes.credenciales.qr', ['estudiante' => $uno->id]))
            ->assertOk();

        // Curso sin estudiantes: vuelve con un aviso en lugar de un PDF vacío
        $this->actingAs($admin, 'web')
            ->from(route('estudiantes.index'))
            ->get(route('estudiantes.credenciales.qr', ['curso' => 6]))
            ->assertRedirect(route('estudiantes.index'))
            ->assertSessionHasErrors('credenciales');
    }

    public function test_resultados_cuentan_lecturas_tiempos_y_avisos(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 15:00:00'));
        $a = $this->estudiante('EST-2026-0001');
        $b = $this->estudiante('EST-2026-0002');
        $c = $this->estudiante('EST-2026-0003');
        $d = $this->estudiante('EST-2026-0004');

        // Lectura al instante: leída 13:35:00, guardada 2 s después
        $this->travelTo(Carbon::parse('2026-10-28 13:35:02'));
        Asistencia::create(['estudiante_id' => $a->id, 'fecha' => '2026-10-28', 'hora_ingreso' => '13:35:00',
            'estado' => Asistencia::PRESENTE, 'registrado_por' => 'QR_Raspberry']);

        // Lectura sin conexión: leída 13:40, enviada 14:20
        $this->travelTo(Carbon::parse('2026-10-28 14:20:00'));
        Asistencia::create(['estudiante_id' => $b->id, 'fecha' => '2026-10-28', 'hora_ingreso' => '13:40:00',
            'estado' => Asistencia::PRESENTE, 'registrado_por' => 'QR_Raspberry']);

        Asistencia::create(['estudiante_id' => $c->id, 'fecha' => '2026-10-28', 'hora_ingreso' => '14:00:00',
            'estado' => Asistencia::TARDANZA, 'registrado_por' => 'Administrador']);
        Asistencia::create(['estudiante_id' => $d->id, 'fecha' => '2026-10-28', 'hora_ingreso' => null,
            'estado' => Asistencia::AUSENTE, 'registrado_por' => 'Cierre automatico']);

        $tutor = Tutor::create(['nombres' => 'Rosa', 'apellidos' => 'Mamani', 'ci' => '4455667', 'estado' => 'activo', 'fcm_token' => 'abc']);
        Notificacion::create(['tutor_id' => $tutor->id, 'estudiante_id' => $d->id, 'tipo' => 'ausencia', 'titulo' => 'Inasistencia',
            'mensaje' => 'x', 'fecha' => '2026-10-28', 'enviada_push' => true, 'leida_en' => now()]);
        Notificacion::create(['tutor_id' => $tutor->id, 'estudiante_id' => $c->id, 'tipo' => 'tardanza', 'titulo' => 'Retraso',
            'mensaje' => 'x', 'fecha' => '2026-10-28', 'enviada_push' => false]);

        $admin = $this->admin();
        $respuesta = $this->actingAs($admin, 'web')
            ->get(route('reportes.resultados', ['desde' => '2026-10-28', 'hasta' => '2026-10-28']))
            ->assertOk();

        $respuesta->assertViewHas('totalRegistros', 4);
        $respuesta->assertViewHas('lecturasQr', 2);
        $respuesta->assertViewHas('lecturasEnLinea', 1);
        $respuesta->assertViewHas('lecturasSincronizadas', 1);
        $respuesta->assertViewHas('segundosPromedio', 2.0);
        $respuesta->assertViewHas('avisos', 2);
        $respuesta->assertViewHas('avisosPush', 1);
        $respuesta->assertViewHas('avisosLeidos', 1);
        $respuesta->assertViewHas('tutoresConApp', 1);
        $respuesta->assertViewHas('coberturaDiaria', 75.0); // 3 de 4 con ingreso
        $respuesta->assertViewHas('origen', fn ($o) => $o['Lector QR (Raspberry Pi)'] === 2
            && $o['Registro manual del administrador'] === 1 && $o['Cierre automático (ausentes)'] === 1);

        $this->actingAs($admin, 'web')
            ->get(route('reportes.resultados.pdf', ['desde' => '2026-10-28', 'hasta' => '2026-10-28']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
