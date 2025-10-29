<?php

namespace App\Http\Controllers\auditoria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class detalleBitacoraController extends Controller
{
    public function index()
    {
        try {
            $sql = "SELECT db.*, 
                           b.id_usuario,
                           u.username,
                           p.nombre as usuario_nombre,
                           p.apellido_p as usuario_apellido
                    FROM detalle_bitacora db
                    INNER JOIN bitacora b ON db.id_bitacora = b.id
                    INNER JOIN usuario u ON b.id_usuario = u.id
                    INNER JOIN persona p ON u.ci_persona = p.ci
                    ORDER BY db.fecha DESC, db.hora DESC";
            $data = DB::select($sql);
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener detalles de bitácora: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener detalles de bitácora'], 500);
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            $sql = "SELECT db.*, 
                           b.id_usuario,
                           u.username,
                           p.nombre as usuario_nombre,
                           p.apellido_p as usuario_apellido,
                           p.apellido_m as usuario_apellido_m,
                           p.ci as usuario_ci
                    FROM detalle_bitacora db
                    INNER JOIN bitacora b ON db.id_bitacora = b.id
                    INNER JOIN usuario u ON b.id_usuario = u.id
                    INNER JOIN persona p ON u.ci_persona = p.ci
                    WHERE db.id = ?";
            $data = DB::select($sql, [$id]);

            if (empty($data)) {
                return response()->json(['message' => 'Detalle de bitácora no encontrado'], 404);
            }

            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener detalle de bitácora ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el detalle de bitácora'], 500);
        }
    }
}