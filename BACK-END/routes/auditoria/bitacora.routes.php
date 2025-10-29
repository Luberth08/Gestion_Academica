<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auditoria\bitacoraController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todas las bitacoras
Route::get('/bitacora', [bitacoraController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_BITACORA']);

// Ruta para obtener una bitacora específica
Route::get('/bitacora/{id}', [bitacoraController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_BITACORA']);
