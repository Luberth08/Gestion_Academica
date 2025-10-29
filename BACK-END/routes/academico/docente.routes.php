<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\docenteController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las docentes
Route::get('/docente', [docenteController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_DOCENTE']);

// Ruta para obtener una docente específica por ID
Route::get('/docente/{id}', [docenteController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_DOCENTE']);

// Ruta para crear una nueva docente
Route::post('/docente', [docenteController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_DOCENTE']);

// Ruta para actualizar una docente existente
Route::put('/docente/{id}', [docenteController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_DOCENTE']);

// Ruta para actualizar parcialmente una docente existente
Route::patch('/docente/{id}', [docenteController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_DOCENTE']);

// Ruta para eliminar una docente
Route::delete('/docente/{id}', [docenteController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_DOCENTE']);
