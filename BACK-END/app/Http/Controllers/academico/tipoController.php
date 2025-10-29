<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class tipoController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los tipos
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todos los tipos ordenados por nombre
            $sql = "SELECT * FROM tipo ORDER BY nombre ASC";
            $data = DB::select($sql);
            
            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/tipos', 'Se consultaron todos los tipos');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener tipos: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener tipos'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un tipo específico por ID
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos la información del tipo específico
            $sql = "SELECT * FROM tipo WHERE id = ?";
            $data = DB::select($sql, [$id]);

            // Verificamos que el tipo exista
            if (empty($data)) {
                return response()->json(['message' => 'Tipo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/tipos/{$id}", "Se consultó el tipo con ID {$id}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener tipo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el tipo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear un nuevo tipo
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:50|unique:tipo,nombre',
                'descripcion' => 'nullable|string'
            ], [
                'nombre.required' => 'El nombre del tipo es requerido',
                'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
                'nombre.unique' => 'El nombre del tipo ya está en uso',
                'descripcion.string' => 'La descripción debe ser texto válido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos el tipo en la base de datos
            DB::insert(
                'INSERT INTO tipo (nombre, descripcion) VALUES (?, ?)',
                [$request->nombre, $request->descripcion]
            );

            // Obtenemos el tipo recién creado
            $tipoCreado = DB::select(
                'SELECT * FROM tipo WHERE nombre = ?',
                [$request->nombre]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/tipos', "Se creó nuevo tipo: {$request->nombre}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Tipo creado correctamente',
                'tipo' => $tipoCreado[0]
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear tipo: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del tipo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el tipo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear tipo: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el tipo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un tipo (actualización completa)
    // -------------------------------------------------
    public function update(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nombre' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tipo', 'nombre')->ignore($id)
                ],
                'descripcion' => 'nullable|string'
            ], [
                'nombre.required' => 'El nombre del tipo es requerido',
                'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
                'nombre.unique' => 'El nombre del tipo ya está en uso',
                'descripcion.string' => 'La descripción debe ser texto válido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos el tipo en la base de datos
            $affected = DB::update(
                'UPDATE tipo SET nombre = ?, descripcion = ? WHERE id = ?',
                [$request->nombre, $request->descripcion, $id]
            );

            // Verificamos que el tipo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Tipo no encontrado'], 404);
            }

            // Obtenemos el tipo actualizado
            $tipoActualizado = DB::select('SELECT * FROM tipo WHERE id = ?', [$id]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/tipos/{$id}", "Se actualizó el tipo con ID {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Tipo actualizado correctamente',
                'tipo' => $tipoActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar tipo ID {$id}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del tipo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el tipo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar tipo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el tipo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de un tipo
    // -------------------------------------------------
    public function patch(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Verificar que el tipo existe
            $tipo = DB::select('SELECT * FROM tipo WHERE id = ?', [$id]);
            
            if (empty($tipo)) {
                return response()->json(['message' => 'Tipo no encontrado'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tipo', 'nombre')->ignore($id)
                ],
                'descripcion' => 'sometimes|nullable|string'
            ], [
                'nombre.unique' => 'El nombre del tipo ya está en uso',
                'nombre.max' => 'El nombre no puede tener más de 50 caracteres'
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
            
            if ($request->filled('nombre')) {
                $fields[] = 'nombre = ?';
                $values[] = $request->nombre;
            }
            if ($request->has('descripcion')) {
                $fields[] = 'descripcion = ?';
                $values[] = $request->descripcion;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $id;
            
            $sql = "UPDATE tipo SET " . implode(', ', $fields) . " WHERE id = ?";
            $affected = DB::update($sql, $values);

            if ($affected === 0) {
                return response()->json(['message' => 'No se pudo actualizar el tipo'], 500);
            }

            // Obtener el tipo actualizado
            $tipoActualizado = DB::select('SELECT * FROM tipo WHERE id = ?', [$id]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/tipos/{$id}", "Se actualizó parcialmente el tipo con ID {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Tipo actualizado parcialmente correctamente',
                'tipo' => $tipoActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH tipo ID {$id}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del tipo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el tipo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH tipo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el tipo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un tipo
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos el tipo de la base de datos
            $affected = DB::delete('DELETE FROM tipo WHERE id = ?', [$id]);
            
            // Verificamos que el tipo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Tipo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/tipos/{$id}", "Se eliminó el tipo con ID {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Tipo eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar tipo ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el tipo porque tiene aulas asignadas'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el tipo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar tipo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el tipo'], 500);
        }
    }
}