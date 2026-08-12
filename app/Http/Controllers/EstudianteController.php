<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class EstudianteController extends Controller
{
    // Listar estudiantes (READ)
    public function index()
    {
        $estudiantes = Estudiante::with('tutores')
            ->orderBy('id', 'desc')
            ->get();

        // Corregido: apunta a la vista en resources/views/estudiantes/index.blade.php
        return view('estudiantes.index', compact('estudiantes'));
    }

    // Mostrar formulario de registro (CREATE)
    public function create()
    {
        return view('estudiantes.create');
    }

    // Guardar estudiante + tutor + QR (STORE)
    public function store(Request $request)
    {
        $request->validate([
            // Datos del tutor
            'tutor_nombres'   => 'required|string|max:100',
            'tutor_apellidos' => 'required|string|max:100',
            'tutor_ci'        => 'nullable|string|max:20',
            'tutor_telefono'  => 'nullable|string|max:20',
            'tutor_email'     => 'nullable|email|max:150',

            // Datos del estudiante
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'ci'               => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'genero'           => 'nullable|in:M,F,Otro',
            'curso'            => 'required|string|max:50',
            'paralelo'         => 'nullable|string|max:10',
            'parentesco'       => 'nullable|string|max:50',
        ]);

        // 1. Crear tutor
        $tutor = Tutor::create([
            'nombres'   => $request->tutor_nombres,
            'apellidos' => $request->tutor_apellidos,
            'ci'        => $request->tutor_ci,
            'telefono'  => $request->tutor_telefono,
            'email'     => $request->tutor_email,
            'estado'    => 'activo',
        ]);

        // 2. Generar código único del estudiante
        $ultimoId = Estudiante::max('id') ?? 0;
        $codigo = 'EST-' . date('Y') . '-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);

        // 3. Datos que irán en el QR
        $qrData = $codigo . '|' . $request->nombres . ' ' . $request->apellidos . '|' . $request->curso;

        // 4. Crear estudiante
        $estudiante = Estudiante::create([
            'codigo_estudiante' => $codigo,
            'nombres'           => $request->nombres,
            'apellidos'         => $request->apellidos,
            'ci'                => $request->ci,
            'fecha_nacimiento'  => $request->fecha_nacimiento,
            'genero'            => $request->genero,
            'curso'             => $request->curso,
            'paralelo'          => $request->paralelo,
            'qr_data'           => $qrData,
            'estado'            => 'activo',
        ]);

        // 5. Relacionar estudiante con tutor
        $estudiante->tutores()->attach($tutor->id, [
            'parentesco'   => $request->parentesco ?? 'Tutor',
            'es_principal' => true,
        ]);

        // 6. Generar imagen del QR (PNG)
        $nombreArchivo = 'qr/' . $codigo . '.png';

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($nombreArchivo, $result->getString());

        // 7. Actualizar la ruta de la imagen en el estudiante
        $estudiante->update([
            'qr_imagen' => $nombreArchivo,
        ]);

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante registrado correctamente. Código: ' . $codigo);
    }

    // Ver detalle (SHOW)
    public function show(Estudiante $estudiante)
    {
        $estudiante->load('tutores');
        return view('estudiantes.show', compact('estudiante'));
    }

    // Formulario de edición (EDIT)
    public function edit(Estudiante $estudiante)
    {
        $estudiante->load('tutores');
        return view('estudiantes.edit', compact('estudiante'));
    }

    // Actualizar datos del estudiante (UPDATE)
    public function update(Request $request, Estudiante $estudiante)
    {
        $request->validate([
            'nombres'          => 'required|string|max:100',
            'apellidos'        => 'required|string|max:100',
            'ci'               => 'nullable|string|max:20',
            'curso'            => 'required|string|max:50',
            'paralelo'         => 'nullable|string|max:10',
            'genero'           => 'nullable|in:M,F,Otro',
            'fecha_nacimiento' => 'nullable|date',
        ]);

        // Recalcular qrData
        $qrData = $estudiante->codigo_estudiante . '|' . $request->nombres . ' ' . $request->apellidos . '|' . $request->curso;

        // Regenerar imagen QR si cambiaron datos clave
        $nombreArchivo = 'qr/' . $estudiante->codigo_estudiante . '.png';

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($nombreArchivo, $result->getString());

        // Actualizar datos del estudiante
        $estudiante->update([
            'nombres'          => $request->nombres,
            'apellidos'        => $request->apellidos,
            'ci'               => $request->ci,
            'curso'            => $request->curso,
            'paralelo'         => $request->paralelo,
            'genero'           => $request->genero,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'qr_data'          => $qrData,
            'qr_imagen'        => $nombreArchivo,
        ]);

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante actualizado correctamente.');
    }

    // Eliminar registro (DESTROY)
    public function destroy(Estudiante $estudiante)
    {
        // Borrar imagen QR del disco
        if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
            Storage::disk('public')->delete($estudiante->qr_imagen);
        }

        $estudiante->delete();

        // Corregido: redirige a 'estudiantes.index'
        return redirect()->route('estudiantes.index')
            ->with('success', 'Estudiante eliminado correctamente.');
    }

    // Activar / Inhabilitar
    public function toggleEstado(Estudiante $estudiante)
    {
        $estudiante->estado = $estudiante->estado === 'activo' ? 'inactivo' : 'activo';
        $estudiante->save();

        return redirect()->route('estudiantes.index')
            ->with('success', 'Estado del estudiante actualizado correctamente.');
    }
}