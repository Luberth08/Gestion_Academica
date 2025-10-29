<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuario\RolPermisoController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los rol_permiso
Route::get('/rol_permiso', [RolPermisoController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_ROL_PERMISO']);

// Ruta para obtener un rol_permiso específico
Route::get('/rol_permiso/{id_rol}/{id_permiso}', [RolPermisoController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_ROL_PERMISO']);

// Ruta para asignar un permiso a un rol
Route::post('/rol_permiso', [RolPermisoController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_ROL_PERMISO']);

// Ruta para eliminar un permiso de un rol
Route::delete('/rol_permiso/{id_rol}/{id_permiso}', [RolPermisoController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_ROL_PERMISO']);