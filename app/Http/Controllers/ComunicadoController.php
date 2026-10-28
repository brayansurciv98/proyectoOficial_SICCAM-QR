<?php

namespace App\Http\Controllers;

use App\Models\Comunicado;
use App\Services\AvisoTutores;
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
        // Llega como notificación push: título y mensaje con largo limitado
        $request->validate([
            'titulo' => 'required|string|max:100',
            'destinatario' => 'required|in:todos,1,2,3,4,5,6',
            'mensaje' => 'required|string|max:1000',
            'accion' => 'required|in:enviar,borrador',
        ], [
            'titulo.max' => 'El título puede tener como máximo 100 caracteres.',
            'mensaje.max' => 'El mensaje puede tener como máximo 1000 caracteres.',
        ]);

        // Determinar tipo de destinatario y curso destino
        $destinatarioTipo = 'todos';
        $cursoDestino = null;

        if ($request->destinatario !== 'todos') {
            $destinatarioTipo = 'curso';
            $cursoDestino = $request->destinatario;
        }

        $estado = $request->accion === 'enviar' ? 'enviado' : 'pendiente';

        $comunicado = Comunicado::create([
            'titulo' => $request->titulo,
            'mensaje' => $request->mensaje,
            'destinatario' => $destinatarioTipo,
            'curso_destino' => $cursoDestino,
            'estado' => $estado,
            'user_id' => Auth::id(),
        ]);

        if ($estado === 'enviado') {
            // Llega a la app de los tutores como aviso y notificación push
            $cantidad = app(AvisoTutores::class)->comunicado($comunicado);
            $mensajeExito = "Comunicado enviado a {$cantidad} tutor(es).";
        } else {
            $mensajeExito = 'Comunicado guardado como borrador.';
        }

        return redirect()->route('comunicados.index')->with('success', $mensajeExito);
    }

    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();

        return redirect()->route('comunicados.index')->with('success', 'Comunicado eliminado correctamente.');
    }
}