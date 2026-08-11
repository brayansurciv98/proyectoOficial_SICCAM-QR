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
    public function index()
    {
        $estudiantes = Estudiante::with('tutores')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.estudiantes', compact('estudiantes'));
    }

    // Mostrar formulario de registro
    public function create()
    {
        return view('estudiantes.create');
    }

    // Guardar estudiante + tutor + QR
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
            'nombres'            => 'required|string|max:100',
            'apellidos'          => 'required|string|max:100',
            'ci'                 => 'nullable|string|max:20',
            'fecha_nacimiento'   => 'nullable|date',
            'genero'             => 'nullable|in:M,F,Otro',
            'curso'              => 'required|string|max:50',
            'paralelo'           => 'nullable|string|max:10',
            'parentesco'         => 'nullable|string|max:50',
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
        $codigo = 'EST-' . date('Y') . '-' . str_pad(Estudiante::count() + 1, 4, '0', STR_PAD_LEFT);

        // 3. Datos que irán en el QR (fijo)
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

        // 6. Generar imagen del QR (PNG con endroid)
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

        return redirect()->back()->with('success', 'Estudiante registrado correctamente. Código: ' . $codigo);
    }

    // Ver detalle
public function show(Estudiante $estudiante)
{
    $estudiante->load('tutores');
    return view('estudiantes.show', compact('estudiante'));
}

// Formulario de edición
public function edit(Estudiante $estudiante)
{
    $estudiante->load('tutores');
    return view('estudiantes.edit', compact('estudiante'));
}

// Actualizar
public function update(Request $request, Estudiante $estudiante)
{
    $request->validate([
        'nombres'   => 'required|string|max:100',
        'apellidos' => 'required|string|max:100',
        'ci'        => 'nullable|string|max:20',
        'curso'     => 'required|string|max:50',
        'paralelo'  => 'nullable|string|max:10',
        'genero'    => 'nullable|in:M,F,Otro',
        'fecha_nacimiento' => 'nullable|date',
    ]);

    $estudiante->update($request->only([
        'nombres', 'apellidos', 'ci', 'curso', 'paralelo', 'genero', 'fecha_nacimiento'
    ]));

    return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado correctamente.');
}

// Eliminar
public function destroy(Estudiante $estudiante)
{
    // Borrar imagen QR si existe
    if ($estudiante->qr_imagen && Storage::disk('public')->exists($estudiante->qr_imagen)) {
        Storage::disk('public')->delete($estudiante->qr_imagen);
    }

    $estudiante->delete();

    return redirect()->route('estudiantes')->with('success', 'Estudiante eliminado correctamente.');
}

// Inhabilitar / Activar
public function toggleEstado(Estudiante $estudiante)
{
    $estudiante->estado = $estudiante->estado === 'activo' ? 'inactivo' : 'activo';
    $estudiante->save();

    return redirect()->route('estudiantes.index')->with('success', 'Estado actualizado correctamente.');
}
}