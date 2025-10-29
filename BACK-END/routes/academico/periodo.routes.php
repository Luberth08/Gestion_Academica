<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\periodoController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los periodos
Route::get('/periodo', [periodoController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_PERIODO']);

// Ruta para obtener un periodo específico por ID
Route::get('/periodo/{id}', [periodoController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_PERIODO']);

// Ruta para crear un nuevo periodo
Route::post('/periodo', [periodoController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_PERIODO']);

// Ruta para actualizar un periodo existente
Route::put('/periodo/{id}', [periodoController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_PERIODO']);

// Ruta para actualizar parcialmente un periodo existente
Route::patch('/periodo/{id}', [periodoController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_PERIODO']);

// Ruta para eliminar un periodo
Route::delete('/periodo/{id}', [periodoController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_PERIODO']);
