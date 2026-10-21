<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusquedaYExamenAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'secreto123',
        ]);
    }

    private function estudiante(string $codigo, string $nombres, string $apellidos, string $curso, string $estado = 'activo'): Estudiante
    {
        return Estudiante::create([
            'codigo_estudiante' => $codigo,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'ci' => substr(preg_replace('/\D/', '', $codigo), -7),
            'curso' => $curso,
            'paralelo' => 'A',
            'qr_data' => $codigo,
            'estado' => $estado,
        ]);
    }

    public function test_asistencia_manual_busca_por_nombre_sin_elegir_curso(): void
    {
        $this->estudiante('EST-2026-0001', 'Ana', 'Quispe', '1');
        $this->estudiante('EST-2026-0002', 'Luis', 'Alvarez', '4');
        $this->estudiante('EST-2026-0003', 'Ana', 'Mamani', '2', 'inactivo');

        $this->actingAs($this->admin(), 'web')
            ->get(route('asistencias.manual', ['buscar' => 'quispe ana']))
            ->assertOk()
            ->assertViewHas('estudiantes', fn ($lista) => $lista->pluck('codigo_estudiante')->all() === ['EST-2026-0001']);
    }

    public function test_asistencia_manual_combina_busqueda_y_curso(): void
    {
        $this->estudiante('EST-2026-0001', 'Ana', 'Quispe', '1');
        $this->estudiante('EST-2026-0002', 'Ana', 'Rojas', '4');

        $this->actingAs($this->admin(), 'web')
            ->get(route('asistencias.manual', ['buscar' => 'Ana', 'curso' => '4']))
            ->assertOk()
            ->assertViewHas('estudiantes', fn ($lista) => $lista->pluck('codigo_estudiante')->all() === ['EST-2026-0002']);
    }

    public function test_examen_admin_rechaza_materia_que_no_es_del_docente(): void
    {
        $matematica = Materia::create(['nombre' => 'Matemáticas', 'codigo' => 'MAT']);
        $fisica = Materia::create(['nombre' => 'Física', 'codigo' => 'FIS']);
        $docente = Docente::create([
            'nombres' => 'Luis',
            'apellidos' => 'Rojas',
            'ci' => '7654321',
            'materia_id' => $matematica->id,
            'password' => bcrypt('clave123'),
            'estado' => 'activo',
        ]);

        $this->actingAs($this->admin(), 'web')
            ->from(route('examenes.create'))
            ->post(route('examenes.store'), [
                'docente_id' => $docente->id,
                'materia_id' => $fisica->id,
                'curso' => 1,
                'paralelo' => 'A',
                'titulo' => 'Primer parcial',
                'fecha' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('examenes.create'))
            ->assertSessionHasErrors('materia_id');

        $this->assertSame(0, Examen::count());
    }
}
