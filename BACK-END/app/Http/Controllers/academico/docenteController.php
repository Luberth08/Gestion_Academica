<?php

namespace App\Http\Controllers\academico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class docenteController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los docentes con información de persona
    // -------------------------------------------------
    public function index(Request $request) 
    {
        try {
            // Obtenemos todos los docentes con información completa
            $sql = "SELECT d.ci, d.codigo, 
                           p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                           u.id as usuario_id, u.username, u.email, r.nombre as rol_nombre
                    FROM docente d, persona p, usuario u, rol r
                    WHERE d.ci = p.ci 
                    AND p.ci = u.ci_persona 
                    AND u.id_rol = r.id
                    ORDER BY p.nombre, p.apellido_p";
            
            $data = DB::select($sql);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', '/docentes', 'Se consultaron todos los docentes');

            // Devolvemos el resultado
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener docentes: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener docentes'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un docente específico por CI
    // -------------------------------------------------
    public function show(Request $request, $ci)
    {
        try {
            // Obtenemos la información completa del docente específico
            $sql = "SELECT d.ci, d.codigo, 
                           p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                           u.id as usuario_id, u.username, u.email, r.id as rol_id, r.nombre as rol_nombre
                    FROM docente d, persona p, usuario u, rol r
                    WHERE d.ci = p.ci 
                    AND p.ci = u.ci_persona 
                    AND u.id_rol = r.id
                    AND d.ci = ?";
            
            $data = DB::select($sql, [$ci]);

            // Verificamos que el docente exista
            if (empty($data)) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'GET', "/docentes/{$ci}", "Se consultó el docente con CI: {$ci}");

            // Devolvemos el resultado
            return response()->json($data[0]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener docente CI {$ci}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el docente'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para crear nuevo docente (y persona y usuario obligatoriamente)
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validamos todos los datos ingresados
            $validator = Validator::make($request->all(), [
                // Datos de persona
                'ci' => 'required|string|max:15|unique:persona,ci',
                'nombre' => 'required|string|max:50',
                'apellido_p' => 'required|string|max:50',
                'apellido_m' => 'nullable|string|max:50',
                'sexo' => 'required|in:M,F',
                'estado' => 'required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'nullable|string|max:15',
                
                // Datos de docente
                'codigo' => 'required|string|max:20|unique:docente,codigo',

                // Datos de usuario (OBLIGATORIOS)
                'username' => 'required|string|max:50|unique:usuario,username',
                'email' => 'required|email|max:320|unique:usuario,email',
                'contrasena' => 'required|string|min:6',
                'id_rol' => 'required|integer|exists:rol,id'
            ], [
                'sexo.in' => 'El campo sexo debe ser M o F',
                'estado.in' => 'El campo estado debe ser: SOLTERO, CASADO, DIVORCIADO, VIUDO u OTRO',
                'ci.unique' => 'La cédula de identidad ya está registrada',
                'codigo.unique' => 'El código de docente ya está en uso',
                'username.unique' => 'El nombre de usuario ya está en uso',
                'email.unique' => 'El correo electrónico ya está registrado'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear la persona en la base de datos
            $personaSql = "INSERT INTO persona (ci, nombre, apellido_p, apellido_m, sexo, estado, telefono) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            DB::insert($personaSql, [
                $request->ci,
                $request->nombre,
                $request->apellido_p,
                $request->apellido_m,
                $request->sexo,
                $request->estado,
                $request->telefono
            ]);

            // Crear el docente en la base de datos
            $docenteSql = "INSERT INTO docente (ci, codigo) VALUES (?, ?)";
            DB::insert($docenteSql, [
                $request->ci,
                $request->codigo
            ]);

            // Crear el usuario en la base de datos (OBLIGATORIO)
            $usuarioSql = "INSERT INTO usuario (username, email, contrasena, ci_persona, id_rol) 
                           VALUES (?, ?, ?, ?, ?)";
            
            DB::insert($usuarioSql, [
                $request->username,
                $request->email,
                Hash::make($request->contrasena),
                $request->ci,
                $request->id_rol
            ]);

            DB::commit();

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'POST', '/docentes', "Se creó nuevo docente: {$request->nombre} {$request->apellido_p} (CI: {$request->ci})");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Docente y usuario creados correctamente'
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al crear docente: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el docente'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al crear docente: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el docente'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar un docente (actualización completa)
    // -------------------------------------------------
    public function update(Request $request, $ci)
    {
        try {
            DB::beginTransaction();

            // Verificar que el docente existe
            $docente = DB::select('SELECT * FROM docente WHERE ci = ?', [$ci]);
            
            if (empty($docente)) {
                DB::rollBack();
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                // Datos de persona
                'ci' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('persona', 'ci')->ignore($ci, 'ci')
                ],
                'nombre' => 'sometimes|required|string|max:50',
                'apellido_p' => 'sometimes|required|string|max:50',
                'apellido_m' => 'sometimes|nullable|string|max:50',
                'sexo' => 'sometimes|required|in:M,F',
                'estado' => 'sometimes|required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'sometimes|nullable|string|max:15',
                
                // Datos de docente
                'codigo' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('docente', 'codigo')->ignore($ci, 'ci')
                ]
            ], [
                'sexo.in' => 'El campo sexo debe ser M o F',
                'estado.in' => 'El campo estado debe ser: SOLTERO, CASADO, DIVORCIADO, VIUDO u OTRO'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar PERSONA si hay campos proporcionados
            if ($request->anyFilled(['ci', 'nombre', 'apellido_p', 'apellido_m', 'sexo', 'estado', 'telefono'])) {
                $personaFields = [];
                $personaValues = [];
                
                if ($request->filled('ci')) {
                    $personaFields[] = 'ci = ?';
                    $personaValues[] = $request->ci;
                }
                if ($request->filled('nombre')) {
                    $personaFields[] = 'nombre = ?';
                    $personaValues[] = $request->nombre;
                }
                if ($request->filled('apellido_p')) {
                    $personaFields[] = 'apellido_p = ?';
                    $personaValues[] = $request->apellido_p;
                }
                if ($request->filled('apellido_m')) {
                    $personaFields[] = 'apellido_m = ?';
                    $personaValues[] = $request->apellido_m;
                }
                if ($request->filled('sexo')) {
                    $personaFields[] = 'sexo = ?';
                    $personaValues[] = $request->sexo;
                }
                if ($request->filled('estado')) {
                    $personaFields[] = 'estado = ?';
                    $personaValues[] = $request->estado;
                }
                if ($request->filled('telefono')) {
                    $personaFields[] = 'telefono = ?';
                    $personaValues[] = $request->telefono;
                }
                
                $personaValues[] = $ci;
                
                if (!empty($personaFields)) {
                    $personaSql = "UPDATE persona SET " . implode(', ', $personaFields) . " WHERE ci = ?";
                    DB::update($personaSql, $personaValues);
                }
            }

            // Actualizar DOCENTE si hay campos proporcionados
            if ($request->anyFilled(['codigo', 'ci'])) {
                $docenteFields = [];
                $docenteValues = [];
                
                if ($request->filled('codigo')) {
                    $docenteFields[] = 'codigo = ?';
                    $docenteValues[] = $request->codigo;
                }
                if ($request->filled('ci')) {
                    $docenteFields[] = 'ci = ?';
                    $docenteValues[] = $request->ci;
                }
                
                $docenteValues[] = $ci;
                
                if (!empty($docenteFields)) {
                    $docenteSql = "UPDATE docente SET " . implode(', ', $docenteFields) . " WHERE ci = ?";
                    DB::update($docenteSql, $docenteValues);
                }
            }

            DB::commit();

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PUT', "/docentes/{$ci}", "Se actualizó el docente con CI: {$ci}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Docente actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al actualizar docente CI {$ci}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el docente'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al actualizar docente CI {$ci}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el docente'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualización parcial de docente
    // -------------------------------------------------
    public function patch(Request $request, $ci)
    {
        try {
            DB::beginTransaction();

            // Verificar que el docente existe
            $docente = DB::select('SELECT * FROM docente WHERE ci = ?', [$ci]);
            
            if (empty($docente)) {
                DB::rollBack();
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Reglas de validación para campos opcionales
            $validator = Validator::make($request->all(), [
                // Datos de persona (opcionales)
                'ci' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('persona', 'ci')->ignore($ci, 'ci')
                ],
                'nombre' => 'sometimes|required|string|max:50',
                'apellido_p' => 'sometimes|required|string|max:50',
                'apellido_m' => 'sometimes|nullable|string|max:50',
                'sexo' => 'sometimes|required|in:M,F',
                'estado' => 'sometimes|required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'sometimes|nullable|string|max:15',
                
                // Datos de docente (opcionales)
                'codigo' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('docente', 'codigo')->ignore($ci, 'ci')
                ]
            ], [
                'sexo.in' => 'El campo sexo debe ser M o F',
                'estado.in' => 'El campo estado debe ser: SOLTERO, CASADO, DIVORCIADO, VIUDO u OTRO'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar PERSONA solo si hay campos proporcionados
            if ($request->anyFilled(['ci', 'nombre', 'apellido_p', 'apellido_m', 'sexo', 'estado', 'telefono'])) {
                $personaFields = [];
                $personaValues = [];
                
                if ($request->filled('ci')) {
                    $personaFields[] = 'ci = ?';
                    $personaValues[] = $request->ci;
                }
                if ($request->filled('nombre')) {
                    $personaFields[] = 'nombre = ?';
                    $personaValues[] = $request->nombre;
                }
                if ($request->filled('apellido_p')) {
                    $personaFields[] = 'apellido_p = ?';
                    $personaValues[] = $request->apellido_p;
                }
                if ($request->filled('apellido_m')) {
                    $personaFields[] = 'apellido_m = ?';
                    $personaValues[] = $request->apellido_m;
                }
                if ($request->filled('sexo')) {
                    $personaFields[] = 'sexo = ?';
                    $personaValues[] = $request->sexo;
                }
                if ($request->filled('estado')) {
                    $personaFields[] = 'estado = ?';
                    $personaValues[] = $request->estado;
                }
                if ($request->filled('telefono')) {
                    $personaFields[] = 'telefono = ?';
                    $personaValues[] = $request->telefono;
                }
                
                $personaValues[] = $ci;
                
                if (!empty($personaFields)) {
                    $personaSql = "UPDATE persona SET " . implode(', ', $personaFields) . " WHERE ci = ?";
                    DB::update($personaSql, $personaValues);
                }
            }

            // Actualizar DOCENTE solo si hay campos proporcionados
            if ($request->anyFilled(['codigo', 'ci'])) {
                $docenteFields = [];
                $docenteValues = [];
                
                if ($request->filled('codigo')) {
                    $docenteFields[] = 'codigo = ?';
                    $docenteValues[] = $request->codigo;
                }
                if ($request->filled('ci')) {
                    $docenteFields[] = 'ci = ?';
                    $docenteValues[] = $request->ci;
                }
                
                $docenteValues[] = $ci;
                
                if (!empty($docenteFields)) {
                    $docenteSql = "UPDATE docente SET " . implode(', ', $docenteFields) . " WHERE ci = ?";
                    DB::update($docenteSql, $docenteValues);
                }
            }

            DB::commit();

            // Obtener el docente actualizado para la respuesta
            $sql = "SELECT d.ci, d.codigo, 
                           p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                           u.id as usuario_id, u.username, u.email, r.id as rol_id, r.nombre as rol_nombre
                    FROM docente d, persona p, usuario u, rol r
                    WHERE d.ci = p.ci 
                    AND p.ci = u.ci_persona 
                    AND u.id_rol = r.id
                    AND d.ci = ?";
            
            $docenteActualizado = DB::select($sql, [$ci]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'PATCH', "/docentes/{$ci}", "Se actualizó parcialmente el docente con CI: {$ci}");

            // Devolvemos confirmación
            return response()->json([
                'message' => 'Docente actualizado parcialmente correctamente',
                'docente' => $docenteActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos en PATCH docente CI {$ci}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el docente'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado en PATCH docente CI {$ci}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el docente'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un docente
    // -------------------------------------------------
    public function destroy(Request $request, $ci)
    {
        try {
            DB::beginTransaction();

            // Verificar que el docente existe
            $docente = DB::select('SELECT * FROM docente WHERE ci = ?', [$ci]);
            
            if (empty($docente)) {
                DB::rollBack();
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Eliminar docente (la persona se elimina automáticamente por CASCADE)
            $affected = DB::delete('DELETE FROM docente WHERE ci = ?', [$ci]);

            if ($affected === 0) {
                DB::rollBack();
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            DB::commit();

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            bitacoraService::logEvent($bitacoraId, 'DELETE', "/docentes/{$ci}", "Se eliminó el docente con CI: {$ci}");

            // Devolvemos confirmación
            return response()->json(['message' => 'Docente eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al eliminar docente CI {$ci}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el docente porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el docente'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al eliminar docente CI {$ci}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el docente'], 500);
        }
    }
}