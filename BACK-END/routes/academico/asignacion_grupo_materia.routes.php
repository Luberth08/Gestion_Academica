<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\asignacionGrupoMateriaController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las asignaciones de grupo materia
Route::get('/grupo_materia', [asignacionGrupoMateriaController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GRUPO_MATERIA']);

// Ruta para obtener una asignación de grupo materia específica
Route::get('/grupo_materia/{sigla_materia}/{sigla_grupo}', [asignacionGrupoMateriaController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GRUPO_MATERIA']);

// Ruta para crear una nueva asignación de grupo materia
Route::post('/grupo_materia', [asignacionGrupoMateriaController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_GRUPO_MATERIA']);

// Ruta para eliminar una asignación de grupo materia
Route::delete('/grupo_materia/{sigla_materia}/{sigla_grupo}', [asignacionGrupoMateriaController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_GRUPO_MATERIA']);