<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuario\permisoController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los permisos
Route::get('/permiso', [permisoController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_PERMISO']);

// Ruta para obtener un permiso por ID
Route::get('/permiso/{id}', [permisoController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_PERMISO']);

// Ruta para crear un nuevo permiso
Route::post('/permiso', [permisoController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_PERMISO']);

// Ruta para actualizar un permiso existente    
Route::put('/permiso/{id}', [permisoController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_PERMISO']);

// Ruta para eliminar un permiso    
Route::delete('/permiso/{id}', [permisoController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_PERMISO']);
