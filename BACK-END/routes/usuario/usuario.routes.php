<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuario\usuarioController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los usuarios
Route::get('/usuario', [usuarioController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_USUARIO']);

// Ruta para obtener un usuario específico
Route::get('/usuario/{id}', [usuarioController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_USUARIO']);

// Ruta para crear un nuevo usuario
Route::post('/usuario', [usuarioController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_USUARIO']);

// Ruta para actualizar un usuario existente
Route::put('/usuario/{id}', [usuarioController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_USUARIO']);

// Ruta para actualizar parcialmente un usuario existente
Route::patch('/usuario/{id}', [usuarioController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_USUARIO']);

// Ruta para eliminar un usuario
Route::delete('/usuario/{id}', [usuarioController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_USUARIO']);