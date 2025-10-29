<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\carreraController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las carreras
Route::get('/carrera', [carreraController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_CARRERA']);

// Ruta para obtener una carrera específica por ID
Route::get('/carrera/{id}', [carreraController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_CARRERA']);

// Ruta para crear una nueva carrera
Route::post('/carrera', [carreraController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_CARRERA']);

// Ruta para actualizar una carrera existente
Route::put('/carrera/{id}', [carreraController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_CARRERA']);

// Ruta para actualizar parcialmente una carrera existente
Route::patch('/carrera/{id}', [carreraController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_CARRERA']);

// Ruta para eliminar una carrera
Route::delete('/carrera/{id}', [carreraController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_CARRERA']);
