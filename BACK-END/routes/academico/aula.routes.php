<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\aulaController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las aulas
Route::get('/aula', [aulaController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_AULA']);

// Ruta para obtener una aula específica por su número
Route::get('/aula/{nro}', [aulaController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_AULA']);

// Ruta para crear una nueva aula
Route::post('/aula', [aulaController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_AULA']);

// Ruta para actualizar una aula existente
Route::put('/aula/{nro}', [aulaController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_AULA']);

// Ruta para actualizar parcialmente una aula existente
Route::patch('/aula/{nro}', [aulaController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_AULA']);

// Ruta para eliminar una aula
Route::delete('/aula/{nro}', [aulaController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_AULA']);
