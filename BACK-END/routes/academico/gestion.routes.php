<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\academico\gestionController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las gestiones
Route::get('/gestion', [gestionController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GESTION']);

// Ruta para obtener una gestion específica por ID
Route::get('/gestion/{id}', [gestionController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_GESTION']);

// Ruta para crear una nueva gestion
Route::post('/gestion', [gestionController::class, 'store'])
    ->middleware(['verificarToken', 'verificarPermiso:CREAR_GESTION']);

// Ruta para actualizar una gestion existente por ID
Route::put('/gestion/{id}', [gestionController::Class, 'update'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_GESTION']);

// Ruta para actualizar parcialmente una gestion existente por ID
Route::patch('/gestion/{id}', [gestionController::Class, 'patch'])
    ->middleware(['verificarToken', 'verificarPermiso:MODIFICAR_GESTION']);

// Ruta para eliminar una gestion por ID
Route::delete('/gestion/{id}', [gestionController::Class, 'destroy'])
    ->middleware(['verificarToken', 'verificarPermiso:ELIMINAR_GESTION']);
