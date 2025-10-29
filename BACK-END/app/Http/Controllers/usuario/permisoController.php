<?php

namespace App\Http\Controllers\usuario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\bitacoraService;

class permisoController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los permisos
    // -------------------------------------------------
    public function index(Request $request) 
    {
        try {
            // Obtenemos todos los permisos
            $data = DB::select("
                SELECT * 
                FROM permiso 
                ORDER BY id ASC
            ");

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', '/permiso', 'Se consultaron todos los permiso');

            // Devolvemos el resultado
            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Error al obtener permisos: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener permisos'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener permiso por id
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos info del permiso en cuestion
            $data = DB::select("
                SELECT * 
                FROM permiso 
                WHERE id = ?
                ", [$id]
            );

            // Verificamos que el permiso exista
            if (empty($data)) {
                return response()->json(['message' => 'Registro no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', "/permiso/{$id}", "Se consultaron el permiso{$id}");
            
            // Devolvemos el resultado
            return response()->json($data[0]);
        } catch (\Exception $e) {
            \Log::error("Error al obtener permiso ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el permiso'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para agregar un nuevo permiso
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Insertamos los datos en la base de datos
            DB::insert('INSERT INTO permiso (nombre) VALUES (?)', [
                $request->nombre,
            ]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'POST', '/permiso', 'se agrego un nuevo permiso');

            // Devolvemos confirmacion
            return response()->json([
                'message' => 'Permiso creado correctamente'
            ], 201); 
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear permiso: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del permiso ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el permiso'], 500);
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear permiso: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el permiso'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un permiso por su id
    // -------------------------------------------------
    public function update(Request $request, $id)
    {
        try {
            // Validamos los datos ingresados
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizamos el permiso en la base de datos
            $affected = DB::update('UPDATE permiso SET nombre = ? WHERE id = ?', [
                $request->nombre,
                $id
            ]);

            
            // Verificamos existencia del permiso a actualizar
            if ($affected === 0) {
                return response()->json(['message' => 'Permiso no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'PUT', "/permiso/{$id}", "Se actualizo el permiso con id:{$id}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Permiso actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar permiso ID {$id}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del permiso ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el permiso'], 500);
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar permiso ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el permiso'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un permiso por su id
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validamos el id ingresado
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos el permiso de la base de datos
            $affected = DB::delete('DELETE FROM permiso WHERE id = ?', [$id]);
            
            // Verificamos la existencia del permiso
            if ($affected === 0) {
                return response()->json(['message' => 'Permiso no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'DELETE', "/permiso/{$id}", "Se elimino el permiso con id:{$id}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Permiso eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar permiso ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint (si hay relaciones con tablas de roles)
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el permiso porque está siendo utilizado por roles del sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el permiso'], 500);
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar permiso ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el permiso'], 500);
        }
    }
}