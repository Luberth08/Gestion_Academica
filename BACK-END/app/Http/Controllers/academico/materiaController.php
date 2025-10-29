<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class materiaController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todas las materias
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todas las materias ordenadas por nombre
            $sql = "SELECT * FROM materia ORDER BY nombre ASC";
            $data = DB::select($sql);
            
            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/materias', 'Se consultaron todas las materias');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener materias: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener materias'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener una materia específica por sigla
    // -------------------------------------------------
    public function show(Request $request, $sigla)
    {
        try {
            // Obtenemos la información de la materia específica
            $sql = "SELECT * FROM materia WHERE sigla = ?";
            $data = DB::select($sql, [$sigla]);

            // Verificamos que la materia exista
            if (empty($data)) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/materias/{$sigla}", "Se consultó la materia con sigla {$sigla}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener materia sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la materia'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear una nueva materia
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'sigla' => 'required|string|max:20|unique:materia,sigla',
                'nombre' => 'required|string|max:255|unique:materia,nombre',
                'descripcion' => 'nullable|string',
                'creditos' => 'required|integer|min:0'
            ], [
                'sigla.unique' => 'La sigla de la materia ya está en uso',
                'nombre.unique' => 'El nombre de la materia ya está en uso',
                'sigla.required' => 'La sigla de la materia es requerida',
                'nombre.required' => 'El nombre de la materia es requerido',
                'creditos.required' => 'Los créditos son requeridos',
                'creditos.min' => 'Los créditos no pueden ser negativos'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos la materia en la base de datos
            DB::insert(
                'INSERT INTO materia (sigla, nombre, descripcion, creditos) VALUES (?, ?, ?, ?)',
                [$request->sigla, $request->nombre, $request->descripcion, $request->creditos]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/materias', "Se creó nueva materia con sigla {$request->sigla}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Materia creada correctamente'
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear materia: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla o nombre de la materia ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear la materia'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear materia: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear la materia'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar una materia (actualización completa)
    // -------------------------------------------------
    public function update(Request $request, $sigla)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'sigla' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('materia', 'sigla')->ignore($sigla, 'sigla')
                ],
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materia', 'nombre')->ignore($sigla, 'sigla')
                ],
                'descripcion' => 'sometimes|nullable|string',
                'creditos' => 'sometimes|required|integer|min:0'
            ], [
                'sigla.unique' => 'La sigla de la materia ya está en uso',
                'nombre.unique' => 'El nombre de la materia ya está en uso',
                'creditos.min' => 'Los créditos no pueden ser negativos'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Construir dinámicamente la consulta UPDATE
            $fields = [];
            $values = [];
            
            if ($request->filled('sigla')) {
                $fields[] = 'sigla = ?';
                $values[] = $request->sigla;
            }
            if ($request->filled('nombre')) {
                $fields[] = 'nombre = ?';
                $values[] = $request->nombre;
            }
            if ($request->filled('descripcion')) {
                $fields[] = 'descripcion = ?';
                $values[] = $request->descripcion;
            }
            if ($request->filled('creditos')) {
                $fields[] = 'creditos = ?';
                $values[] = $request->creditos;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $sigla;
            
            $sql = "UPDATE materia SET " . implode(', ', $fields) . " WHERE sigla = ?";
            $affected = DB::update($sql, $values);

            // Verificamos que la materia exista
            if ($affected === 0) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/materias/{$sigla}", "Se actualizó la materia con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Materia actualizada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar materia sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla o nombre de la materia ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la materia'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar materia sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la materia'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de una materia
    // -------------------------------------------------
    public function patch(Request $request, $sigla)
    {
        try {
            // Verificar que la materia existe
            $materia = DB::select('SELECT * FROM materia WHERE sigla = ?', [$sigla]);
            
            if (empty($materia)) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'sigla' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('materia', 'sigla')->ignore($sigla, 'sigla')
                ],
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materia', 'nombre')->ignore($sigla, 'sigla')
                ],
                'descripcion' => 'sometimes|nullable|string',
                'creditos' => 'sometimes|required|integer|min:0'
            ], [
                'sigla.unique' => 'La sigla de la materia ya está en uso',
                'nombre.unique' => 'El nombre de la materia ya está en uso',
                'creditos.min' => 'Los créditos no pueden ser negativos'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Construir dinámicamente la consulta UPDATE
            $fields = [];
            $values = [];
            
            if ($request->filled('sigla')) {
                $fields[] = 'sigla = ?';
                $values[] = $request->sigla;
            }
            if ($request->filled('nombre')) {
                $fields[] = 'nombre = ?';
                $values[] = $request->nombre;
            }
            if ($request->filled('descripcion')) {
                $fields[] = 'descripcion = ?';
                $values[] = $request->descripcion;
            }
            if ($request->filled('creditos')) {
                $fields[] = 'creditos = ?';
                $values[] = $request->creditos;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $sigla;
            
            $sql = "UPDATE materia SET " . implode(', ', $fields) . " WHERE sigla = ?";
            $affected = DB::update($sql, $values);

            // Obtener la materia actualizada (usar nueva sigla si se cambió)
            $nuevaSigla = $request->filled('sigla') ? $request->sigla : $sigla;
            $materiaActualizada = DB::select('SELECT * FROM materia WHERE sigla = ?', [$nuevaSigla]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/materias/{$sigla}", "Se actualizó parcialmente la materia con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Materia actualizada parcialmente correctamente',
                'materia' => $materiaActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH materia sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'La sigla o nombre de la materia ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la materia'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH materia sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la materia'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar una materia
    // -------------------------------------------------
    public function destroy(Request $request, $sigla)
    {
        try {
            // Eliminamos la materia de la base de datos
            $affected = DB::delete('DELETE FROM materia WHERE sigla = ?', [$sigla]);
            
            // Verificamos que la materia exista
            if ($affected === 0) {
                return response()->json(['message' => 'Materia no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/materias/{$sigla}", "Se eliminó la materia con sigla {$sigla}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Materia eliminada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar materia sigla {$sigla}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar la materia porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar la materia'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar materia sigla {$sigla}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar la materia'], 500);
        }
    }
}