<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\bitacoraService;

class asignacionGrupoMateriaController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todas las asignaciones grupo-materia
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todas las asignaciones con información de materia y grupo
            $sql = "SELECT agm.sigla_materia, agm.sigla_grupo, 
                           m.nombre as materia_nombre, m.creditos,
                           g.sigla as grupo_sigla
                    FROM asignacion_grupo_materia agm, materia m, grupo g
                    WHERE agm.sigla_materia = m.sigla AND agm.sigla_grupo = g.sigla
                    ORDER BY m.nombre, g.sigla";
            
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/grupo_materia', 'Se consultaron todas las asignaciones grupo-materia');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener asignaciones grupo-materia: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener asignaciones'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener una asignación específica
    // -------------------------------------------------
    public function show(Request $request, $sigla_materia, $sigla_grupo)
    {
        try {
            // Obtenemos la información de la asignación específica
            $sql = "SELECT agm.sigla_materia, agm.sigla_grupo, 
                           m.nombre as materia_nombre, m.creditos, m.descripcion,
                           g.sigla as grupo_sigla
                    FROM asignacion_grupo_materia agm, materia m, grupo g
                    WHERE agm.sigla_materia = m.sigla AND agm.sigla_grupo = g.sigla
                    AND agm.sigla_materia = ? AND agm.sigla_grupo = ?";
            
            $data = DB::select($sql, [$sigla_materia, $sigla_grupo]);

            // Verificamos que la asignación exista
            if (empty($data)) {
                return response()->json(['message' => 'Asignación no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/grupo_materia/{$sigla_materia}/{$sigla_grupo}", "Se consultó la asignación materia: {$sigla_materia} - grupo: {$sigla_grupo}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener asignación materia {$sigla_materia} - grupo {$sigla_grupo}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la asignación'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear una nueva asignación grupo-materia
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'sigla_materia' => 'required|string|max:20|exists:materia,sigla',
                'sigla_grupo' => 'required|string|max:20|exists:grupo,sigla'
            ], [
                'sigla_materia.exists' => 'La materia especificada no existe',
                'sigla_grupo.exists' => 'El grupo especificado no existe',
                'sigla_materia.required' => 'La sigla de la materia es requerida',
                'sigla_grupo.required' => 'La sigla del grupo es requerida'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificamos si la asignación ya existe
            $existing = DB::select(
                'SELECT * FROM asignacion_grupo_materia WHERE sigla_materia = ? AND sigla_grupo = ?',
                [$request->sigla_materia, $request->sigla_grupo]
            );

            if (!empty($existing)) {
                return response()->json([
                    'message' => 'Esta asignación ya existe'
                ], 422);
            }

            // Creamos la asignación en la base de datos
            DB::insert(
                'INSERT INTO asignacion_grupo_materia (sigla_materia, sigla_grupo) VALUES (?, ?)',
                [$request->sigla_materia, $request->sigla_grupo]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/grupo_materia', "Se creó nueva asignación materia: {$request->sigla_materia} - grupo: {$request->sigla_grupo}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Asignación creada exitosamente',
                'sigla_materia' => $request->sigla_materia,
                'sigla_grupo' => $request->sigla_grupo
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear asignación grupo-materia: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'Error: La materia o el grupo no existen'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear la asignación'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear asignación grupo-materia: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear la asignación'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar una asignación grupo-materia
    // -------------------------------------------------
    public function destroy(Request $request, $sigla_materia, $sigla_grupo)
    {
        try {
            // Eliminamos la asignación de la base de datos
            $affected = DB::delete(
                'DELETE FROM asignacion_grupo_materia WHERE sigla_materia = ? AND sigla_grupo = ?',
                [$sigla_materia, $sigla_grupo]
            );

            // Verificamos la existencia de la asignación
            if ($affected === 0) {
                return response()->json(['message' => 'Asignación no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/grupo_materia/{$sigla_materia}/{$sigla_grupo}", "Se eliminó la asignación materia: {$sigla_materia} - grupo: {$sigla_grupo}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Asignación eliminada exitosamente']);
            
        } catch (\Exception $e) {
            \Log::error("Error al eliminar asignación materia {$sigla_materia} - grupo {$sigla_grupo}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar la asignación'], 500);
        }
    }
}