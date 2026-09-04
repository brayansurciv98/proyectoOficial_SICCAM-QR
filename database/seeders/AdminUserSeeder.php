<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@siccam.com'], // Evita duplicados si ya existe
            [
                'name' => 'Administrador SICCAM',
                'password' => Hash::make('admin12345'), // Contraseña encriptada
            ]
        );
    }
}