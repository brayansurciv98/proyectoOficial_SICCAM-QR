<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MateriasSeeder extends Seeder
{
    public function run(): void
    {
        $materias = [
            ['nombre' => 'Matemáticas',       'codigo' => 'MAT'],
            ['nombre' => 'Lenguaje',          'codigo' => 'LEN'],
            ['nombre' => 'Ciencias Sociales', 'codigo' => 'CS'],
            ['nombre' => 'Física',            'codigo' => 'FIS'],
            ['nombre' => 'Química',           'codigo' => 'QUI'],
            ['nombre' => 'Artes Plásticas',   'codigo' => 'ART'],
        ];

        foreach ($materias as $m) {
            DB::table('materias')->updateOrInsert(
                ['nombre' => $m['nombre']],
                [
                    'codigo' => $m['codigo'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}