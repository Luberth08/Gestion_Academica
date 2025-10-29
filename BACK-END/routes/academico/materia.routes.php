<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\materiaController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las materias
Route::get('/materia', [materiaController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_MATERIA']);

// Ruta para obtener una materia específica por su sigla
Route::get('/materia/{sigla}', [materiaController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_MATERIA']);

// Ruta para crear una nueva materia
Route::post('/materia', [materiaController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_MATERIA']);

// Ruta para actualizar una materia existente
Route::put('/materia/{sigla}', [materiaController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_MATERIA']);

// Ruta para actualizar parcialmente una materia existente
Route::patch('/materia/{sigla}', [materiaController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_MATERIA']);

// Ruta para eliminar una materia
Route::delete('/materia/{sigla}', [materiaController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_MATERIA']);
