<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class gestionController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todas las gestiones
    // -------------------------------------------------
    public function index(Request $request)
    {
        try {
            // Obtenemos todas las gestiones con información del periodo
            $sql = "SELECT g.*, p.nombre as periodo_nombre, p.cantidad_meses
                    FROM gestion g
                    INNER JOIN periodo p ON g.id_periodo = p.id
                    ORDER BY g.anio DESC, g.nro_periodo DESC";
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/gestiones', 'Se consultaron todas las gestiones');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener gestiones: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener gestiones'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener una gestión específica por ID
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos la información de la gestión específica
            $sql = "SELECT g.*, p.nombre as periodo_nombre, p.cantidad_meses
                    FROM gestion g
                    INNER JOIN periodo p ON g.id_periodo = p.id
                    WHERE g.id = ?";
            $data = DB::select($sql, [$id]);

            // Verificamos que la gestión exista
            if (empty($data)) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/gestiones/{$id}", "Se consultó la gestión con ID: {$id}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener gestión ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la gestión'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear una nueva gestión
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'anio' => 'required|integer|min:2000|max:2100',
                'nro_periodo' => 'required|integer|min:1',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'id_periodo' => 'required|integer|exists:periodo,id'
            ], [
                'anio.required' => 'El año es requerido',
                'anio.min' => 'El año debe ser mayor o igual a 2000',
                'anio.max' => 'El año no puede ser mayor a 2100',
                'nro_periodo.required' => 'El número de período es requerido',
                'nro_periodo.min' => 'El número de período debe ser al menos 1',
                'fecha_inicio.required' => 'La fecha de inicio es requerida',
                'fecha_fin.required' => 'La fecha de fin es requerida',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'id_periodo.required' => 'El período es requerido',
                'id_periodo.exists' => 'El período seleccionado no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista una gestión con el mismo año y número de período
            $existe = DB::select(
                'SELECT id FROM gestion WHERE anio = ? AND nro_periodo = ?',
                [$request->anio, $request->nro_periodo]
            );

            if (!empty($existe)) {
                return response()->json([
                    'message' => 'Ya existe una gestión con el mismo año y número de período'
                ], 422);
            }

            // Insertamos la gestión en la base de datos
            DB::insert(
                'INSERT INTO gestion (anio, nro_periodo, fecha_inicio, fecha_fin, id_periodo) VALUES (?, ?, ?, ?, ?)',
                [
                    $request->anio,
                    $request->nro_periodo,
                    $request->fecha_inicio,
                    $request->fecha_fin,
                    $request->id_periodo
                ]
            );

            // Obtener la gestión recién creada
            $gestionCreada = DB::select(
                'SELECT g.*, p.nombre as periodo_nombre 
                 FROM gestion g 
                 INNER JOIN periodo p ON g.id_periodo = p.id 
                 WHERE g.anio = ? AND g.nro_periodo = ?',
                [$request->anio, $request->nro_periodo]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/gestiones', "Se creó nueva gestión: Año {$request->anio} - Periodo {$request->nro_periodo}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Gestión creada correctamente',
                'gestion' => $gestionCreada[0]
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear gestión: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en las fechas: La fecha de fin debe ser posterior a la fecha de inicio'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear la gestión'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear gestión: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear la gestión'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar una gestión (actualización completa)
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
                'anio' => 'required|integer|min:2000|max:2100',
                'nro_periodo' => 'required|integer|min:1',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'id_periodo' => 'required|integer|exists:periodo,id'
            ], [
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'id_periodo.exists' => 'El período seleccionado no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista otra gestión con el mismo año y número de período
            $existe = DB::select(
                'SELECT id FROM gestion WHERE anio = ? AND nro_periodo = ? AND id != ?',
                [$request->anio, $request->nro_periodo, $id]
            );

            if (!empty($existe)) {
                return response()->json([
                    'message' => 'Ya existe otra gestión con el mismo año y número de período'
                ], 422);
            }

            // Actualizamos la gestión en la base de datos
            $affected = DB::update(
                'UPDATE gestion SET anio = ?, nro_periodo = ?, fecha_inicio = ?, fecha_fin = ?, id_periodo = ? WHERE id = ?',
                [
                    $request->anio,
                    $request->nro_periodo,
                    $request->fecha_inicio,
                    $request->fecha_fin,
                    $request->id_periodo,
                    $id
                ]
            );

            // Verificamos que la gestión exista
            if ($affected === 0) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }

            // Obtener la gestión actualizada
            $gestionActualizada = DB::select(
                'SELECT g.*, p.nombre as periodo_nombre 
                 FROM gestion g 
                 INNER JOIN periodo p ON g.id_periodo = p.id 
                 WHERE g.id = ?',
                [$id]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/gestiones/{$id}", "Se actualizó la gestión con ID: {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Gestión actualizada correctamente',
                'gestion' => $gestionActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar gestión ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en las fechas: La fecha de fin debe ser posterior a la fecha de inicio'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la gestión'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar gestión ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la gestión'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de gestión
    // -------------------------------------------------
    public function patch(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Verificar que la gestión existe
            $gestion = DB::select('SELECT * FROM gestion WHERE id = ?', [$id]);
            
            if (empty($gestion)) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }

            // Validamos los datos ingresados (parciales)
            $validator = Validator::make($request->all(), [
                'anio' => 'sometimes|required|integer|min:2000|max:2100',
                'nro_periodo' => 'sometimes|required|integer|min:1',
                'fecha_inicio' => 'sometimes|required|date',
                'fecha_fin' => 'sometimes|required|date|after:fecha_inicio',
                'id_periodo' => 'sometimes|required|integer|exists:periodo,id'
            ], [
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'id_periodo.exists' => 'El período seleccionado no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Si se actualiza año o nro_periodo, verificar que no exista duplicado
            if ($request->filled('anio') || $request->filled('nro_periodo')) {
                $anio = $request->filled('anio') ? $request->anio : $gestion[0]->anio;
                $nroPeriodo = $request->filled('nro_periodo') ? $request->nro_periodo : $gestion[0]->nro_periodo;

                $existe = DB::select(
                    'SELECT id FROM gestion WHERE anio = ? AND nro_periodo = ? AND id != ?',
                    [$anio, $nroPeriodo, $id]
                );

                if (!empty($existe)) {
                    return response()->json([
                        'message' => 'Ya existe otra gestión con el mismo año y número de período'
                    ], 422);
                }
            }

            // Construir dinámicamente la consulta UPDATE
            $fields = [];
            $values = [];
            
            if ($request->filled('anio')) {
                $fields[] = 'anio = ?';
                $values[] = $request->anio;
            }
            if ($request->filled('nro_periodo')) {
                $fields[] = 'nro_periodo = ?';
                $values[] = $request->nro_periodo;
            }
            if ($request->filled('fecha_inicio')) {
                $fields[] = 'fecha_inicio = ?';
                $values[] = $request->fecha_inicio;
            }
            if ($request->filled('fecha_fin')) {
                $fields[] = 'fecha_fin = ?';
                $values[] = $request->fecha_fin;
            }
            if ($request->filled('id_periodo')) {
                $fields[] = 'id_periodo = ?';
                $values[] = $request->id_periodo;
            }
            
            // Verificamos que hayan campos para actualizar
            if (empty($fields)) {
                return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
            }
            
            $values[] = $id;
            
            $sql = "UPDATE gestion SET " . implode(', ', $fields) . " WHERE id = ?";
            $affected = DB::update($sql, $values);

            if ($affected === 0) {
                return response()->json(['message' => 'No se pudo actualizar la gestión'], 500);
            }

            // Obtener la gestión actualizada
            $gestionActualizada = DB::select(
                'SELECT g.*, p.nombre as periodo_nombre 
                 FROM gestion g 
                 INNER JOIN periodo p ON g.id_periodo = p.id 
                 WHERE g.id = ?',
                [$id]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/gestiones/{$id}", "Se actualizó parcialmente la gestión con ID: {$id}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Gestión actualizada parcialmente correctamente',
                'gestion' => $gestionActualizada[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos en PATCH gestión ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'check')) {
                return response()->json([
                    'message' => 'Error en las fechas: La fecha de fin debe ser posterior a la fecha de inicio'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar la gestión'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado en PATCH gestión ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar la gestión'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar una gestión
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos la gestión de la base de datos
            $affected = DB::delete('DELETE FROM gestion WHERE id = ?', [$id]);
            
            // Verificamos que la gestión exista
            if ($affected === 0) {
                return response()->json(['message' => 'Gestión no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/gestiones/{$id}", "Se eliminó la gestión con ID: {$id}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Gestión eliminada correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar gestión ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar la gestión porque tiene registros relacionados en oferta académica'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar la gestión'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar gestión ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar la gestión'], 500);
        }
    }
}