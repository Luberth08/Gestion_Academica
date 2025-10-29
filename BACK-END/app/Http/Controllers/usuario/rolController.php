<?php

namespace App\Http\Controllers\usuario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\bitacoraService;

class rolController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los roles
    // -------------------------------------------------
    public function index(Request $request) 
    {
        try {
            // Obtenemos todos los roles desde la base de datos
            $data = DB::select("
                SELECT * 
                FROM rol 
                ORDER BY id ASC
            ");

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', '/rol', 'Se consultaron todos los roles');

            // Devolvemos los resultados
            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Error al obtener roles: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener roles'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un rol por su id
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validamos que el ID ingresado sea numérico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Obtenemos el permiso desde la base de datos
            $data = DB::select("
                SELECT * 
                FROM rol 
                WHERE id = ?
            ", [$id]);

            // Verificamos su existencia
            if (empty($data)) {
                return response()->json(['message' => 'Registro no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', "/rol/{$id}", "Se consulto el rol{$id}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener rol ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el rol'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para agregar un nuevo rol
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

            // Insertamos los datos a la base de datos
            DB::insert('INSERT INTO rol (nombre) VALUES (?)', [
                $request->nombre,
            ]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'POST', '/rol', 'Se agrego un nuevo rol');

            // Devolvemos confirmacion
            return response()->json([
                'message' => 'Registro insertado correctamente'
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al insertar rol: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del rol ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el rol'], 500);
        } catch (\Exception $e) {
            \Log::error("Error inesperado al insertar rol: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el rol'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener actualizar un rol por su id
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

            // Actualizamos el rol en la base de datos por su id
            $affected = DB::update('UPDATE rol SET nombre = ? WHERE id = ?', [
                $request->nombre,
                $id
            ]);

            // verificamos su existencia
            if ($affected === 0) {
                return response()->json(['message' => 'Registro no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'PUT', "/rol/{$id}", "Se actualizo el rol con id:{$id}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Registro actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al actualizar rol ID {$id}: " . $e->getMessage());
            
            // Manejar violación de UNIQUE constraint
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'El nombre del rol ya existe'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el rol'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al actualizar rol ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el rol'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un rol por su id
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validamos el id del rol ingresado
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            // Eliminamos el rol de la base de datos
            $affected = DB::delete('DELETE FROM rol WHERE id = ?', [$id]);
            
            // Verificamos existencia del rol
            if ($affected === 0) {
                return response()->json(['message' => 'Registro no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'DELETE', "/rol/{$id}", "Se elimino el rol con id:{$id}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Registro eliminado correctamente']);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al eliminar rol ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el rol porque está siendo utilizado por otros registros'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el rol'], 500);
        } catch (\Exception $e) {
            \Log::error("Error inesperado al eliminar rol ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el rol'], 500);
        }
    }
}