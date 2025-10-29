<?php

// Importaciones necesarias
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auditoria\detalleBitacoraController;
use App\Http\Middleware\verificarToken;

// Ruta para obtener todos los detalles de bitacora
Route::get('/detalle_bitacora', [detalleBitacoraController::class, 'index'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_BITACORA']);

// Ruta para obtener un detalle de bitacora específico
Route::get('/detalle_bitacora/{id}', [detalleBitacoraController::class, 'show'])
    ->middleware(['verificarToken', 'verificarPermiso:VER_BITACORA']);
