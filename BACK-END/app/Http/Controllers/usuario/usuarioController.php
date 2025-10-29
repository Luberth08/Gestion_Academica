<?php

namespace App\Http\Controllers\usuario;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\bitacoraService;

class UsuarioController extends Controller
{
    // -------------------------------------------------
    // Controlador para obtener todos los usuarios
    // -------------------------------------------------
    public function index(Request $request) 
    {
        try {
            // Obtenemos todos los usuarios
            $data = DB::select("
                SELECT u.id, u.username, u.email, u.ci_persona, u.id_rol, 
                        p.ci, p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                        r.id as rol_id, r.nombre as rol_nombre
                FROM usuario u, persona p, rol r
                WHERE u.ci_persona = p.ci AND u.id_rol = r.id
                ORDER BY p.nombre, p.apellido_p
            ");

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', '/usuario', 'Se consultaron a todos los usuarios');

            // Devolvemos los resultados
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener usuarios: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener usuarios'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para obtener un usuario por su id
    // -------------------------------------------------
    public function show(Request $request, $id)
    {
        try {
            // Validamos que el id recibido sea numerico
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }
            
            // Obtenemos el usuario 
            $data = DB::select("
                SELECT u.id, u.username, u.email, u.ci_persona, u.id_rol, 
                        p.ci, p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                        r.id as rol_id, r.nombre as rol_nombre
                FROM usuario u, persona p, rol r
                WHERE u.ci_persona = p.ci AND u.id_rol = r.id AND u.id = ?
                ", [$id]
            );

            // Verificamos existencia
            if (empty($data)) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'GET', "/usuario/{$id}", "Se consulto al usuario con id:{$id}");
            
            // Devolvemos el resultado
            return response()->json($data[0]);
        } catch (\Exception $e) {
            \Log::error("Error al obtener usuario ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al obtener el usuario'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para agregar un nuevo usuario
    // -------------------------------------------------
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                // Datos de persona
                'ci' => 'required|string|max:15|unique:persona,ci',
                'nombre' => 'required|string|max:50',
                'apellido_p' => 'required|string|max:50',
                'apellido_m' => 'nullable|string|max:50',
                'sexo' => 'required|in:M,F',
                'estado' => 'required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'nullable|string|max:15',
                
                // Datos de usuario
                'username' => 'required|string|max:50|unique:usuario,username',
                'email' => 'required|email|max:320|unique:usuario,email',
                'contrasena' => 'required|string|min:6',
                'id_rol' => 'required|integer|exists:rol,id'
            ], [
                'sexo.in' => 'El campo sexo debe ser M o F',
                'estado.in' => 'El campo estado debe ser: SOLTERO, CASADO, DIVORCIADO, VIUDO u OTRO',
                'ci.unique' => 'La cédula de identidad ya está registrada',
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

            // Creamos a la persona asociada al usuario primero
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

            // Crear el usuario con contraseña hasheada
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
            BitacoraService::logEvent($bitacoraId, 'POST', '/usuario', 'Se agrego un nuevo usuario');

            // Devolvemos confirmacion
            return response()->json([
                'message' => 'Usuario creado correctamente'
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al crear usuario: " . $e->getMessage());
            
            // Manejar errores específicos de base de datos
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al crear el usuario'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al crear usuario: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al crear el usuario'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar informacion de un usuario por su id
    // -------------------------------------------------
    public function update(Request $request, $id)
    {
        try {
            // Validamos el id ingresado
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            DB::beginTransaction();

            // Primero obtenemos el usuario para saber la CI de la persona
            $usuario = DB::select('SELECT * FROM usuario WHERE id = ?', [$id]);
            
            // Verificamos su existencia
            if (empty($usuario)) {
                DB::rollBack();
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            $ciPersona = $usuario[0]->ci_persona;

            // Validaciones
            $validator = Validator::make($request->all(), [
                // Datos de persona
                'ci' => [
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('persona', 'ci')->ignore($ciPersona, 'ci')
                ],
                'nombre' => 'required|string|max:50',
                'apellido_p' => 'required|string|max:50',
                'apellido_m' => 'nullable|string|max:50',
                'sexo' => 'required|in:M,F',
                'estado' => 'required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'nullable|string|max:15',
                
                // Datos de usuario
                'username' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('usuario', 'username')->ignore($id)
                ],
                'email' => [
                    'required',
                    'email',
                    'max:320',
                    Rule::unique('usuario', 'email')->ignore($id)
                ],
                'contrasena' => 'nullable|string|min:6',
                'id_rol' => 'required|integer|exists:rol,id'
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

            // Actualizar la persona
            $personaSql = "UPDATE persona 
                           SET ci = ?, nombre = ?, apellido_p = ?, apellido_m = ?, sexo = ?, estado = ?, telefono = ?
                           WHERE ci = ?";
            
            DB::update($personaSql, [
                $request->ci,
                $request->nombre,
                $request->apellido_p,
                $request->apellido_m,
                $request->sexo,
                $request->estado,
                $request->telefono,
                $ciPersona
            ]);

            // Preparar datos para actualizar usuario
            $updateData = [
                $request->username,
                $request->email,
                $request->id_rol,
                $request->ci,
                $id
            ];

            $sql = "UPDATE usuario SET username = ?, email = ?, id_rol = ?, ci_persona = ? WHERE id = ?";

            // Si se proporciona nueva contraseña, actualizarla
            if ($request->filled('contrasena')) {
                $sql = "UPDATE usuario SET username = ?, email = ?, contrasena = ?, id_rol = ?, ci_persona = ? WHERE id = ?";
                array_splice($updateData, 2, 0, [Hash::make($request->contrasena)]);
            }

            // Actualizar el usuario
            $affected = DB::update($sql, $updateData);

            DB::commit();

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'PUT', "/usuario/{$id}", "Se actualizo la informacion del usuario con id:{$id}");

            // Devolvemos confirmacion
            return response()->json(['message' => 'Usuario actualizado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al actualizar usuario ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el usuario'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al actualizar usuario ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el usuario'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para actualizar parcialmente un usuario por su id
    // -------------------------------------------------
    public function patch(Request $request, $id)
    {
        try {
            // Validamos el id ingresado
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            DB::beginTransaction();

            // Obtener el usuario actual
            $usuario = DB::select('SELECT * FROM usuario WHERE id = ?', [$id]);
            
            if (empty($usuario)) {
                DB::rollBack();
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            $ciPersona = $usuario[0]->ci_persona;
            $usuarioActual = $usuario[0];

            // Obtener la persona actual
            $persona = DB::select('SELECT * FROM persona WHERE ci = ?', [$ciPersona]);
            if (empty($persona)) {
                DB::rollBack();
                return response()->json(['message' => 'Persona no encontrada'], 404);
            }

            $personaActual = $persona[0];

            // Validamos los datos ingresados
            $validator = Validator::make($request->all(), [
                'ci' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:15',
                    Rule::unique('persona', 'ci')->ignore($ciPersona, 'ci')
                ],
                'nombre' => 'sometimes|required|string|max:50',
                'apellido_p' => 'sometimes|required|string|max:50',
                'apellido_m' => 'sometimes|nullable|string|max:50',
                'sexo' => 'sometimes|required|in:M,F',
                'estado' => 'sometimes|required|in:SOLTERO,CASADO,DIVORCIADO,VIUDO,OTRO',
                'telefono' => 'sometimes|nullable|string|max:15',
                
                'username' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('usuario', 'username')->ignore($id)
                ],
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:320',
                    Rule::unique('usuario', 'email')->ignore($id)
                ],
                'contrasena' => 'sometimes|nullable|string|min:6',
                'id_rol' => 'sometimes|required|integer|exists:rol,id'
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
                
                // Construir dinámicamente la consulta UPDATE para persona
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
                
                // Agregar WHERE condition
                $personaValues[] = $ciPersona;
                
                if (!empty($personaFields)) {
                    $personaSql = "UPDATE persona SET " . implode(', ', $personaFields) . " WHERE ci = ?";
                    DB::update($personaSql, $personaValues);
                }
            }

            // Actualizar USUARIO solo si hay campos proporcionados
            if ($request->anyFilled(['username', 'email', 'contrasena', 'id_rol', 'ci'])) {
                $usuarioFields = [];
                $usuarioValues = [];
                
                // Construir dinámicamente la consulta UPDATE para usuario
                if ($request->filled('username')) {
                    $usuarioFields[] = 'username = ?';
                    $usuarioValues[] = $request->username;
                }
                if ($request->filled('email')) {
                    $usuarioFields[] = 'email = ?';
                    $usuarioValues[] = $request->email;
                }
                if ($request->filled('contrasena')) {
                    $usuarioFields[] = 'contrasena = ?';
                    $usuarioValues[] = Hash::make($request->contrasena);
                }
                if ($request->filled('id_rol')) {
                    $usuarioFields[] = 'id_rol = ?';
                    $usuarioValues[] = $request->id_rol;
                }
                if ($request->filled('ci')) {
                    $usuarioFields[] = 'ci_persona = ?';
                    $usuarioValues[] = $request->ci;
                }
                
                // Agregar WHERE condition
                $usuarioValues[] = $id;
                
                if (!empty($usuarioFields)) {
                    $usuarioSql = "UPDATE usuario SET " . implode(', ', $usuarioFields) . " WHERE id = ?";
                    DB::update($usuarioSql, $usuarioValues);
                }
            }

            DB::commit();

            // Obtener el usuario actualizado para la respuesta
            $sql = "SELECT u.id, u.username, u.email, u.ci_persona, u.id_rol, 
                        p.ci, p.nombre, p.apellido_p, p.apellido_m, p.sexo, p.estado, p.telefono,
                        r.id as rol_id, r.nombre as rol_nombre
                    FROM usuario u, persona p, rol r
                    WHERE u.ci_persona = p.ci AND u.id_rol = r.id AND u.id = ?";
            
            $usuarioActualizado = DB::select($sql, [$id]);

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'PATCH', "/usuario/{$id}", "Se actualizo la informacion del usuario con id:{$id}");

            // Devolvemos resultado
            return response()->json([
                'message' => 'Usuario actualizado parcialmente correctamente',
                'usuario' => $usuarioActualizado[0]
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos en PATCH usuario ID {$id}: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique')) {
                return response()->json([
                    'message' => 'Error de duplicación: alguno de los datos ya existe en el sistema'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al actualizar el usuario'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado en PATCH usuario ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al actualizar el usuario'], 500);
        }
    }

    // -------------------------------------------------
    // Controlador para eliminar un usuario por su id
    // -------------------------------------------------
    public function destroy(Request $request, $id)
    {
        try {
            // Validamos el id ingresados
            if (!is_numeric($id)) {
                return response()->json(['message' => 'ID inválido'], 400);
            }

            DB::beginTransaction();

            // Primero obtener la CI de la persona para referencia
            $usuario = DB::select('SELECT ci_persona FROM usuario WHERE id = ?', [$id]);
            
            if (empty($usuario)) {
                DB::rollBack();
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            $ciPersona = $usuario[0]->ci_persona;

            // Eliminar usuario 
            $affected = DB::delete('DELETE FROM usuario WHERE id = ?', [$id]);

            if ($affected === 0) {
                DB::rollBack();
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            DB::commit();

            // Registramos el evento en bitacora
            $bitacoraId = $request->user['bitacoraId'];
            BitacoraService::logEvent($bitacoraId, 'DELETE', "/usuario/{$id}", "Se elimino la informacion del usuario con id:{$id}");

            return response()->json(['message' => 'Usuario eliminado correctamente']);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error("Error de base de datos al eliminar usuario ID {$id}: " . $e->getMessage());
            
            // Manejar violación de FOREIGN KEY constraint
            if (str_contains($e->getMessage(), 'foreign key')) {
                return response()->json([
                    'message' => 'No se puede eliminar el usuario porque tiene registros relacionados'
                ], 422);
            }
            
            return response()->json(['message' => 'Error de base de datos al eliminar el usuario'], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error inesperado al eliminar usuario ID {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor al eliminar el usuario'], 500);
        }
    }
}