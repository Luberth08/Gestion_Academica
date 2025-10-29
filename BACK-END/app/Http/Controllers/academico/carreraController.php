<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class carreraController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todas las carreras
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todas las carreras ordenadas por nombre
            $sql = "SELECT * FROM carrera ORDER BY nombre ASC";
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/carreras', 'Se consultaron todas las carreras');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener carreras: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener carreras'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener una carrera específica por ID
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos la información de la carrera específica
            $sql = "SELECT * FROM carrera WHERE id = ?";
            $data = DB::select($sql, [$id]);

            // Verificamos que la carrera exista
            if (empty($data)) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/carreras/{$id}", "Se consultó la carrera con ID: {$id}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener carrera ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la carrera'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear una nueva carrera
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'codigo' => 'required|string|max:20|unique:carrera,codigo',
                'descripcion' => 'nullable|string'
            ], [
                'codigo.unique' => 'El código de carrera ya está en uso',
                'nombre.required' => 'El nombre de la carrera es requerido',
                'codigo.required' => 'El código de la carrera es requerido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos la carrera en la base de datos
            DB::insert(
                'INSERT INTO carrera (nombre, codigo, descripcion) VALUES (?, ?, ?)',
                [$request->nombre, $request->codigo, $request->descripcion]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/carreras', "Se creó nueva carrera: {$request->nombre} (Código: {$request->codigo})");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Carrera creada correctamente'
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear carrera: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El código de carrera ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear la carrera'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear carrera: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear la carrera'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar una carrera (actualización completa)
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
                'nombre' => 'required|string|max:100',
                'codigo' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('carrera', 'codigo')->ignore($id)
                ],
                'descripcion' => 'nullable|string'
            ], [
                'codigo.unique' => 'El código de carrera ya está en uso'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos la carrera en la base de datos
            $affected = DB::update(
                'UPDATE carrera SET nombre = ?, codigo = ?, descripcion = ? WHERE id = ?',
                [$request->nombre, $request->codigo, $request->descripcion, $id]
            );

            // Verificamos que la carrera exista
            if ($affected === 0) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/carreras/{$id}", "Se actualizó la carrera con ID: {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Carrera actualizada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar carrera ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El código de carrera ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la carrera'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar carrera ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la carrera'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de carrera
    // -------------------------------------------------
    public function patch(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Verificar que la carrera existe
            $carrera = DB::select('SELECT * FROM carrera WHERE id = ?', [$id]);
            
            if (empty($carrera)) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:100',
                'codigo' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('carrera', 'codigo')->ignore($id)
                ],
                'descripcion' => 'sometimes|nullable|string'
            ], [
                'codigo.unique' => 'El código de carrera ya está en uso'
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
            if ($request->filled('codigo')) {
                $fields[] = 'codigo = ?';
                $values[] = $request->codigo;
            }
            if ($request->filled('descripcion')) {
                $fields[] = 'descripcion = ?';
                $values[] = $request->descripcion;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $id;
            
            $sql = "UPDATE carrera SET " . implode(', ', $fields) . " WHERE id = ?";
            $affected = DB::update($sql, $values);

            // Obtener la carrera actualizada
            $carreraActualizada = DB::select('SELECT * FROM carrera WHERE id = ?', [$id]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/carreras/{$id}", "Se actualizó parcialmente la carrera con ID: {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Carrera actualizada parcialmente correctamente',
                'carrera' => $carreraActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH carrera ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El código de carrera ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la carrera'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH carrera ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la carrera'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar una carrera
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos la carrera de la base de datos
            $affected = DB::delete('DELETE FROM carrera WHERE id = ?', [$id]);
            
            // Verificamos que la carrera exista
            if ($affected === 0) {
                return response()->json(['message' => 'Carrera no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/carreras/{$id}", "Se eliminó la carrera con ID: {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Carrera eliminada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar carrera ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar la carrera porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar la carrera'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar carrera ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar la carrera'], 500);
        }
    }
}