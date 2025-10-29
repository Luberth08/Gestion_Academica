<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class aulaController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todas las aulas
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todas las aulas con información del tipo
            $sql = "SELECT a.*, t.nombre as tipo_nombre, t.descripcion as tipo_descripcion
                    FROM aula a
                    LEFT JOIN tipo t ON a.id_tipo = t.id
                    ORDER BY a.piso ASC, a.nro ASC";
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/aulas', 'Se consultaron todas las aulas');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener aulas: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener aulas'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un aula específica por número
    // -------------------------------------------------
    public function show(Request $request, $nro)
    {
        try {
            // Validar que el número de aula sea numérico
            if (!is_numeric($nro)) {
                return response()->json(['message' => 'Número de aula inválido'], 400);
            }

            // Obtenemos la información del aula específica
            $sql = "SELECT a.*, t.nombre as tipo_nombre, t.descripcion as tipo_descripcion
                    FROM aula a
                    LEFT JOIN tipo t ON a.id_tipo = t.id
                    WHERE a.nro = ?";
            $data = DB::select($sql, [$nro]);

            // Verificamos que el aula exista
            if (empty($data)) {
                return response()->json(['message' => 'Aula no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/aulas/{$nro}", "Se consultó el aula número {$nro}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener aula NRO {$nro}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el aula'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear una nueva aula
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nro' => 'required|integer|min:1|unique:aula,nro',
                'piso' => 'nullable|integer|min:0|max:20',
                'capacidad' => 'required|integer|min:0',
                'descripcion' => 'nullable|string',
                'estado' => 'required|string|in:DISPONIBLE,NO DISPONIBLE',
                'id_tipo' => 'nullable|integer|exists:tipo,id'
            ], [
                'nro.required' => 'El número de aula es requerido',
                'nro.unique' => 'El número de aula ya existe',
                'nro.min' => 'El número de aula debe ser mayor a 0',
                'piso.min' => 'El piso no puede ser negativo',
                'piso.max' => 'El piso no puede ser mayor a 20',
                'capacidad.required' => 'La capacidad es requerida',
                'capacidad.min' => 'La capacidad no puede ser negativa',
                'estado.required' => 'El estado es requerido',
                'estado.in' => 'El estado debe ser DISPONIBLE o NO DISPONIBLE',
                'id_tipo.exists' => 'El tipo seleccionado no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos el aula en la base de datos
            DB::insert(
                'INSERT INTO aula (nro, piso, capacidad, descripcion, estado, id_tipo) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $request->nro,
                    $request->piso,
                    $request->capacidad,
                    $request->descripcion,
                    $request->estado,
                    $request->id_tipo
                ]
            );

            // Obtenemos el aula recién creada con información del tipo
            $aulaCreada = DB::select(
                'SELECT a.*, t.nombre as tipo_nombre 
                 FROM aula a 
                 LEFT JOIN tipo t ON a.id_tipo = t.id 
                 WHERE a.nro = ?',
                [$request->nro]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/aulas', "Se creó nueva aula número {$request->nro}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Aula creada correctamente',
                'aula' => $aulaCreada[0]
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear aula: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El número de aula ya existe'
                ], 422);
            }

            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en los datos: capacidad no puede ser negativa o estado inválido'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el aula'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear aula: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el aula'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un aula (actualización completa)
    // -------------------------------------------------
    public function update(Request $request, $nro)
    {
        try {
            // Validar que el número de aula sea numérico
            if (!is_numeric($nro)) {
                return response()->json(['message' => 'Número de aula inválido'], 400);
            }

            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nro' => 'required|integer|min:1|unique:aula,nro,' . $nro . ',nro',
                'piso' => 'nullable|integer|min:0|max:20',
                'capacidad' => 'required|integer|min:0',
                'descripcion' => 'nullable|string',
                'estado' => 'required|string|in:DISPONIBLE,NO DISPONIBLE',
                'id_tipo' => 'nullable|integer|exists:tipo,id'
            ], [
                'nro.unique' => 'El número de aula ya existe',
                'capacidad.min' => 'La capacidad no puede ser negativa',
                'estado.in' => 'El estado debe ser DISPONIBLE o NO DISPONIBLE',
                'id_tipo.exists' => 'El tipo seleccionado no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos el aula en la base de datos
            $affected = DB::update(
                'UPDATE aula SET nro = ?, piso = ?, capacidad = ?, descripcion = ?, estado = ?, id_tipo = ? WHERE nro = ?',
                [
                    $request->nro,
                    $request->piso,
                    $request->capacidad,
                    $request->descripcion,
                    $request->estado,
                    $request->id_tipo,
                    $nro
                ]
            );

            // Verificamos que el aula exista
            if ($affected === 0) {
                return response()->json(['message' => 'Aula no encontrada'], 404);
            }

            // Obtenemos el aula actualizada
            $aulaActualizada = DB::select(
                'SELECT a.*, t.nombre as tipo_nombre 
                 FROM aula a 
                 LEFT JOIN tipo t ON a.id_tipo = t.id 
                 WHERE a.nro = ?',
                [$request->nro]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/aulas/{$nro}", "Se actualizó el aula número {$nro}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Aula actualizada correctamente',
                'aula' => $aulaActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar aula NRO {$nro}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El número de aula ya existe'
                ], 422);
            }

            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en los datos: capacidad no puede ser negativa o estado inválido'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el aula'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar aula NRO {$nro}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el aula'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de un aula
    // -------------------------------------------------
    public function patch(Request $request, $nro)
    {
        try {
            // Validar que el número de aula sea numérico
            if (!is_numeric($nro)) {
                return response()->json(['message' => 'Número de aula inválido'], 400);
            }

            // Verificar que el aula existe
            $aula = DB::select('SELECT * FROM aula WHERE nro = ?', [$nro]);
            
            if (empty($aula)) {
                return response()->json(['message' => 'Aula no encontrada'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'nro' => 'sometimes|required|integer|min:1|unique:aula,nro,' . $nro . ',nro',
                'piso' => 'sometimes|nullable|integer|min:0|max:20',
                'capacidad' => 'sometimes|required|integer|min:0',
                'descripcion' => 'sometimes|nullable|string',
                'estado' => 'sometimes|required|string|in:DISPONIBLE,NO DISPONIBLE',
                'id_tipo' => 'sometimes|nullable|integer|exists:tipo,id'
            ], [
                'nro.unique' => 'El número de aula ya existe',
                'capacidad.min' => 'La capacidad no puede ser negativa',
                'estado.in' => 'El estado debe ser DISPONIBLE o NO DISPONIBLE',
                'id_tipo.exists' => 'El tipo seleccionado no existe'
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
            
            if ($request->filled('nro')) {
                $fields[] = 'nro = ?';
                $values[] = $request->nro;
            }
            if ($request->filled('piso')) {
                $fields[] = 'piso = ?';
                $values[] = $request->piso;
            }
            if ($request->filled('capacidad')) {
                $fields[] = 'capacidad = ?';
                $values[] = $request->capacidad;
            }
            if ($request->has('descripcion')) {
                $fields[] = 'descripcion = ?';
                $values[] = $request->descripcion;
            }
            if ($request->filled('estado')) {
                $fields[] = 'estado = ?';
                $values[] = $request->estado;
            }
            if ($request->has('id_tipo')) {
                $fields[] = 'id_tipo = ?';
                $values[] = $request->id_tipo;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $nro;
            
            $sql = "UPDATE aula SET " . implode(', ', $fields) . " WHERE nro = ?";
            $affected = DB::update($sql, $values);

            if ($affected === 0) {
                return response()->json(['message' => 'No se pudo actualizar el aula'], 500);
            }

            // Obtener el nuevo número si cambió
            $nuevoNro = $request->filled('nro') ? $request->nro : $nro;

            // Obtener el aula actualizada
            $aulaActualizada = DB::select(
                'SELECT a.*, t.nombre as tipo_nombre 
                 FROM aula a 
                 LEFT JOIN tipo t ON a.id_tipo = t.id 
                 WHERE a.nro = ?',
                [$nuevoNro]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/aulas/{$nro}", "Se actualizó parcialmente el aula número {$nro}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Aula actualizada parcialmente correctamente',
                'aula' => $aulaActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH aula NRO {$nro}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El número de aula ya existe'
                ], 422);
            }

            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en los datos: capacidad no puede ser negativa o estado inválido'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el aula'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH aula NRO {$nro}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el aula'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un aula
    // -------------------------------------------------
    public function destroy(Request $request, $nro)
    {
        try {
            // Validar que el número de aula sea numérico
            if (!is_numeric($nro)) {
                return response()->json(['message' => 'Número de aula inválido'], 400);
            }

            // Eliminamos el aula de la base de datos
            $affected = DB::delete('DELETE FROM aula WHERE nro = ?', [$nro]);
            
            // Verificamos que el aula exista
            if ($affected === 0) {
                return response()->json(['message' => 'Aula no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/aulas/{$nro}", "Se eliminó el aula número {$nro}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Aula eliminada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar aula NRO {$nro}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el aula porque tiene horarios de clase asignados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el aula'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar aula NRO {$nro}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el aula'], 500);
        }
    }
}