<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class periodoController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los periodos
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todos los periodos ordenados por nombre
            $sql = "SELECT * FROM periodo ORDER BY nombre ASC";
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/periodos', 'Se consultaron todos los periodos');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener periodos: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener periodos'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un periodo específico por ID
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos la información del periodo específico
            $sql = "SELECT * FROM periodo WHERE id = ?";
            $data = DB::select($sql, [$id]);

            // Verificamos que el periodo exista
            if (empty($data)) {
                return response()->json(['message' => 'Periodo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/periodos/{$id}", "Se consultó el periodo con ID: {$id}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener periodo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el periodo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear un nuevo periodo
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:10|unique:periodo,nombre',
                'cantidad_meses' => 'required|integer|min:1|max:12'
            ], [
                'nombre.unique' => 'El nombre del periodo ya está en uso',
                'nombre.required' => 'El nombre del periodo es requerido',
                'nombre.max' => 'El nombre no puede tener más de 10 caracteres',
                'cantidad_meses.required' => 'La cantidad de meses es requerida',
                'cantidad_meses.min' => 'La cantidad de meses debe ser al menos 1',
                'cantidad_meses.max' => 'La cantidad de meses no puede ser mayor a 12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos el periodo en la base de datos
            DB::insert(
                'INSERT INTO periodo (nombre, cantidad_meses) VALUES (?, ?)',
                [$request->nombre, $request->cantidad_meses]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/periodos', "Se creó nuevo periodo: {$request->nombre}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Periodo creado correctamente'
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear periodo: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del periodo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el periodo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear periodo: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el periodo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un periodo (actualización completa)
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
                    'max:10',
                    Rule::unique('periodo', 'nombre')->ignore($id)
                ],
                'cantidad_meses' => 'required|integer|min:1|max:12'
            ], [
                'nombre.unique' => 'El nombre del periodo ya está en uso',
                'cantidad_meses.min' => 'La cantidad de meses debe ser al menos 1',
                'cantidad_meses.max' => 'La cantidad de meses no puede ser mayor a 12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos el periodo en la base de datos
            $affected = DB::update(
                'UPDATE periodo SET nombre = ?, cantidad_meses = ? WHERE id = ?',
                [$request->nombre, $request->cantidad_meses, $id]
            );

            // Verificamos que el periodo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Periodo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/periodos/{$id}", "Se actualizó el periodo con ID: {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Periodo actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar periodo ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del periodo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el periodo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar periodo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el periodo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de periodo
    // -------------------------------------------------
    public function patch(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Verificar que el periodo existe
            $periodo = DB::select('SELECT * FROM periodo WHERE id = ?', [$id]);
            
            if (empty($periodo)) {
                return response()->json(['message' => 'Periodo no encontrado'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('periodo', 'nombre')->ignore($id)
                ],
                'cantidad_meses' => 'sometimes|required|integer|min:1|max:12'
            ], [
                'nombre.unique' => 'El nombre del periodo ya está en uso',
                'cantidad_meses.min' => 'La cantidad de meses debe ser al menos 1',
                'cantidad_meses.max' => 'La cantidad de meses no puede ser mayor a 12'
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
            if ($request->filled('cantidad_meses')) {
                $fields[] = 'cantidad_meses = ?';
                $values[] = $request->cantidad_meses;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $id;
            
            $sql = "UPDATE periodo SET " . implode(', ', $fields) . " WHERE id = ?";
            $affected = DB::update($sql, $values);

            // Obtener el periodo actualizado
            $periodoActualizado = DB::select('SELECT * FROM periodo WHERE id = ?', [$id]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/periodos/{$id}", "Se actualizó parcialmente el periodo con ID: {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Periodo actualizado parcialmente correctamente',
                'periodo' => $periodoActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH periodo ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del periodo ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el periodo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH periodo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el periodo'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un periodo
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos el periodo de la base de datos
            $affected = DB::delete('DELETE FROM periodo WHERE id = ?', [$id]);
            
            // Verificamos que el periodo exista
            if ($affected === 0) {
                return response()->json(['message' => 'Periodo no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/periodos/{$id}", "Se eliminó el periodo con ID: {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Periodo eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar periodo ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el periodo porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el periodo'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar periodo ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el periodo'], 500);
        }
    }
}