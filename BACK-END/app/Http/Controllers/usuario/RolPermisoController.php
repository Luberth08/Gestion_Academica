<?php

namespace App\Http\Controllers\usuario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\bitacoraService;

class RolPermisoController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los rol_permiso
    // -------------------------------------------------
    public function index(Request $request) 
    {
        try {
            // Obtenemos las asociaciones rol_permiso de la base de datos
            $data = DB::select("
                SELECT rp.id_rol, r.nombre AS rol_nombre, rp.id_permiso, p.nombre AS permiso_nombre
                FROM rol_permiso rp, rol r, permiso p
                WHERE rp.id_rol = r.id AND rp.id_permiso = p.id
                ORDER BY r.nombre, p.nombre
            ");

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', '/rol_permiso', 'Se consulto todas las asociaciones de los rol_permiso');

            // Devolvemos los resultados
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener asociaciones rol-permiso: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener las asociaciones'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un permiso en especifico asociado a un rol en especifico
    // -------------------------------------------------
    public function show(Request $request, $id_rol, $id_permiso)
    {
        try {
            // Validar que los IDs ingresados sean numéricos
            if (!is_numeric($id_rol) || !is_numeric($id_permiso)) {
                return response()->json(['message' => 'IDs inválidos'], 400);
            }
            
            //Obtenemos la asociacion desde la base de datos
            $data = DB::select("
                SELECT rp.id_rol, r.nombre AS rol_nombre, rp.id_permiso, p.nombre AS permiso_nombre
                FROM rol_permiso rp, rol r, permiso p
                WHERE rp.id_rol = r.id AND rp.id_permiso = p.id 
                AND rp.id_rol = ? AND rp.id_permiso = ?
                ", [$id_rol, $id_permiso]
            );

            // Validamos existencia
            if (empty($data)) {
                return response()->json(['message' => 'Asociación no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', "/rol_permiso/{$id_rol}/{$id_permiso}", "Se consulto la asociacion del rol:{$id_rol} y permiso{$id_permiso}");
            
            // Devolvemos el resultado
            return response()->json($data[0]);
        } catch (\Exception $e) {
            \Log::error("Error al obtener asociación rol {$id_rol} - permiso {$id_permiso}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la asociación'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para asignar un permiso a un rol
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'id_rol' => 'required|integer|exists:rol,id',
                'id_permiso' => 'required|integer|exists:permiso,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si la asociación ya existe
            $existing = DB::select(
                'SELECT * FROM rol_permiso WHERE id_rol = ? AND id_permiso = ?',
                [$request->id_rol, $request->id_permiso]
            );

            if (!empty($existing)) {
                return response()->json([
                    'message' => 'Esta asociación ya existe'
                ], 422);
            }

            // Creamos la asociación
            DB::insert(
                'INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)',
                [$request->id_rol, $request->id_permiso]
            );

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'POST', '/rol_permiso', 'Se agrego un permiso a un rol');

            // Devolvemos confirmacion
            return response()->json([
                'message' => 'Asociación creada exitosamente',
                'id_rol' => $request->id_rol,
                'id_permiso' => $request->id_permiso
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Error de base de datos al crear asociación rol-permiso: " . $e->getMessage());
            return response()->json(['message' => 'Error de base de datos al crear la asociación'], 500);
            
        } catch (\Exception $e) {
            \Log::error("Error inesperado al crear asociación rol-permiso: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear la asociación'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un permiso de un rol
    // -------------------------------------------------
    public function destroy(Request $request, $id_rol, $id_permiso)
    {
        try {
            // Validar que los IDs ingresados sean numéricos
            if (!is_numeric($id_rol) || !is_numeric($id_permiso)) {
                return response()->json(['message' => 'IDs inválidos'], 400);
            }

            // Eliminamos la asociacion de la abse de datos
            $affected = DB::delete(
                'DELETE FROM rol_permiso WHERE id_rol = ? AND id_permiso = ?',
                [$id_rol, $id_permiso]
            );

            // Verificamos existencia
            if ($affected === 0) {
                return response()->json(['message' => 'Asociación no encontrada'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'DELETE', "/rol_permiso/{$id_rol}/{$id_permiso}", "Se elimino la asociacion del rol:{$id_rol} y el permiso:{$id_permiso}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Asociación eliminada exitosamente']);
            
        } catch (\Exception $e) {
            \Log::error("Error al eliminar asociación rol {$id_rol} - permiso {$id_permiso}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar la asociación'], 500);
        }
    }
}