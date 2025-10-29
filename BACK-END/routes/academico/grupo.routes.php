<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\grupoController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los grupos
Route::get('/grupo', [grupoController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GRUPO']);

// Ruta para obtener un grupo específico por su sigla
Route::get('/grupo/{sigla}', [grupoController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GRUPO']);

// Ruta para crear un nuevo grupo
Route::post('/grupo', [grupoController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_GRUPO']);

// Ruta para actualizar un grupo existente
Route::put('/grupo/{sigla}', [grupoController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_GRUPO']);

// Ruta para actualizar parcialmente un grupo existente
Route::patch('/grupo/{sigla}', [grupoController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_GRUPO']);

// Ruta para eliminar un grupo
Route::delete('/grupo/{sigla}', [grupoController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_GRUPO']);
