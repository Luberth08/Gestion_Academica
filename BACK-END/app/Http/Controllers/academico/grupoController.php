<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class grupoController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los grupos
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todos los grupos ordenados por sigla
            $sql = "SELECT * FROM grupo ORDER BY sigla ASC";
            $data = DB::select($sql);
            
            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/grupos', 'Se consultaron todos los grupos');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener grupos: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener grupos'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un grupo específico por sigla
    // -------------------------------------------------
    public function show(Request $request, $sigla)
    {
        try {
            // Obtenemos la información del grupo específico
            $sql = "SELECT * FROM grupo WHERE sigla = ?";
            $data = DB::select($sql, [$sigla]);

            // Verificamos que el grupo exista
            if (empty($data)) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/grupos/{$sigla}", "Se consultó el grupo con sigla {$sigla}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener grupo sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el grupo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear un nuevo grupo
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'sigla' => 'required|string|max:20|unique:grupo,sigla'
            ], [
                'sigla.unique' => 'La sigla del grupo ya está en uso',
                'sigla.required' => 'La sigla del grupo es requerida',
                'sigla.max' => 'La sigla no puede tener más de 20 caracteres'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos el grupo en la base de datos
            DB::insert('INSERT INTO grupo (sigla) VALUES (?)', [$request->sigla]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/grupos', "Se creó nuevo grupo con sigla {$request->sigla}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Grupo creado correctamente'
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear grupo: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla del grupo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el grupo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear grupo: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el grupo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un grupo (actualización completa)
    // -------------------------------------------------
    public function update(Request $request, $sigla)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'sigla' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('grupo', 'sigla')->ignore($sigla, 'sigla')
                ]
            ], [
                'sigla.unique' => 'La sigla del grupo ya está en uso',
                'sigla.required' => 'La sigla del grupo es requerida',
                'sigla.max' => 'La sigla no puede tener más de 20 caracteres'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos el grupo en la base de datos
            $affected = DB::update(
                'UPDATE grupo SET sigla = ? WHERE sigla = ?',
                [$request->sigla, $sigla]
            );

            // Verificamos que el grupo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/grupos/{$sigla}", "Se actualizó el grupo con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Grupo actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar grupo sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla del grupo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el grupo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar grupo sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el grupo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de un grupo
    // -------------------------------------------------
    public function patch(Request $request, $sigla)
    {
        try {
            // Verificar que el grupo existe
            $grupo = DB::select('SELECT * FROM grupo WHERE sigla = ?', [$sigla]);
            
            if (empty($grupo)) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'sigla' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('grupo', 'sigla')->ignore($sigla, 'sigla')
                ]
            ], [
                'sigla.unique' => 'La sigla del grupo ya está en uso',
                'sigla.required' => 'La sigla del grupo es requerida',
                'sigla.max' => 'La sigla no puede tener más de 20 caracteres'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar la sigla
            $affected = DB::update(
                'UPDATE grupo SET sigla = ? WHERE sigla = ?',
                [$request->sigla, $sigla]
            );

            // Obtener el grupo actualizado
            $grupoActualizado = DB::select('SELECT * FROM grupo WHERE sigla = ?', [$request->sigla]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/grupos/{$sigla}", "Se actualizó parcialmente el grupo con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Grupo actualizado correctamente',
                'grupo' => $grupoActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH grupo sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla del grupo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el grupo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH grupo sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el grupo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un grupo
    // -------------------------------------------------
    public function destroy(Request $request, $sigla)
    {
        try {
            // Eliminamos el grupo de la base de datos
            $affected = DB::delete('DELETE FROM grupo WHERE sigla = ?', [$sigla]);
            
            // Verificamos que el grupo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/grupos/{$sigla}", "Se eliminó el grupo con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Grupo eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar grupo sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el grupo porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el grupo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar grupo sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el grupo'], 500);
        }
    }
}