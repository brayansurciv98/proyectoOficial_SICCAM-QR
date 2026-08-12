<?php

namespace App\Http\Controllers;

use App\Models\Comunicado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComunicadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Comunicado::query();

        // Filtro por estado (Enviado / Pendiente-Borrador)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $comunicados = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('comunicados.index', compact('comunicados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'destinatario' => 'required|string',
            'mensaje' => 'required|string',
            'accion' => 'required|in:enviar,borrador',
        ]);

        // Determinar tipo de destinatario y curso destino
        $destinatarioTipo = 'todos';
        $cursoDestino = null;

        if ($request->destinatario !== 'todos') {
            $destinatarioTipo = 'curso';
            $cursoDestino = $request->destinatario;
        }

        $estado = $request->accion === 'enviar' ? 'enviado' : 'pendiente';

        Comunicado::create([
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'destinatario' => $destinatarioTipo,
            'curso_destino' => $cursoDestino,
            'canal' => 'sistema',
            'estado' => $estado,
            'user_id' => Auth::id(),
        ]);

        $mensajeExito = $estado === 'enviado' 
            ? 'Comunicado enviado correctamente.' 
            : 'Comunicado guardado como borrador.';

        return redirect()->route('comunicados.index')->with('success', $mensajeExito);
    }

    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();

        return redirect()->route('comunicados.index')->with('success', 'Comunicado eliminado correctamente.');
    }
}