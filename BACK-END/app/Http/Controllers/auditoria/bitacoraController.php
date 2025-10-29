<?php

namespace App\Http\Controllers\auditoria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class bitacoraController extends Controller
{
    public function index()
    {
        try {
            $sql = "SELECT b.*, 
                           u.username, 
                           p.nombre as usuario_nombre,
                           p.apellido_p as usuario_apellido,
                           p.ci as usuario_ci
                    FROM bitacora b
                    INNER JOIN usuario u ON b.id_usuario = u.id
                    INNER JOIN persona p ON u.ci_persona = p.ci
                    ORDER BY b.fecha_inicio DESC, b.hora_inicio DESC";
            $data = DB::select($sql);
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener bitácoras: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener bitácoras'], 500);
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            $sql = "SELECT b.*, 
                           u.username, 
                           p.nombre as usuario_nombre,
                           p.apellido_p as usuario_apellido,
                           p.apellido_m as usuario_apellido_m,
                           p.ci as usuario_ci
                    FROM bitacora b
                    INNER JOIN usuario u ON b.id_usuario = u.id
                    INNER JOIN persona p ON u.ci_persona = p.ci
                    WHERE b.id = ?";
            $data = DB::select($sql, [$id]);

            if (empty($data)) {
                return response()->json(['message' => 'Bitácora no encontrada'], 404);
            }

            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener bitácora ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener la bitácora'], 500);
        }
    }
}