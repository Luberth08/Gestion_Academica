<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\tipoController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los tipos
Route::get('/tipo', [tipoController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_TIPO']);

// Ruta para obtener un tipo específico por ID
Route::get('/tipo/{id}', [tipoController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_TIPO']);

// Ruta para crear un nuevo tipo
Route::post('/tipo', [tipoController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_TIPO']);

// Ruta para actualizar un tipo existente por ID
Route::put('/tipo/{id}', [tipoController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_TIPO']);

// Ruta para actualizar parcialmente un tipo existente por ID
Route::patch('/tipo/{id}', [tipoController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_TIPO']);

// Ruta para eliminar un tipo por ID
Route::delete('/tipo/{id}', [tipoController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_TIPO']);
