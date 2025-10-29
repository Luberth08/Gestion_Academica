<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuario\rolController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los roles
Route::get('/rol', [rolController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_ROL']);

// Ruta para obtener un rol específico    
Route::get('/rol/{id}', [rolController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_ROL']);

// Ruta para crear un nuevo rol
Route::post('/rol', [rolController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_ROL']);

// Ruta para actualizar un rol existente
Route::put('/rol/{id}', [rolController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_ROL']);

// Ruta para eliminar un rol
Route::delete('/rol/{id}', [rolController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_ROL']);