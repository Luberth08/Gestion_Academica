<?php


use app\Http\Controllers\usuario\RolController;

// Administrar Usuario
require __DIR__ . '/usuario/auth.routes.php';
require __DIR__ . '/usuario/docente.routes.php';
require __DIR__ . '/usuario/importar_usuarios.routes.php';
require __DIR__ . '/usuario/permiso.routes.php';
require __DIR__ . '/usuario/rol.routes.php';
require __DIR__ . '/usuario/usuario.routes.php';
require __DIR__ . '/usuario/ver_historial.routes.php';

// Administrar Gestion Academica
require __DIR__ . '/academico/materia.routes.php';
require __DIR__ . '/academico/gestion.routes.php';
require __DIR__ . '/academico/tipo_aula.routes.php';
require __DIR__ . '/academico/aula.routes.php';
require __DIR__ . '/academico/grupo.routes.php';
require __DIR__ . '/academico/asignar_docente.routes.php';
require __DIR__ . '/academico/asignar_aula.routes.php';

// Administrar Asistencia
require __DIR__ . '/asistencia/asistencia.routes.php';

// Administrar Reporte
Route::get('/test', function() {
    $config = [
        'app_env' => app()->environment(),
        'app_debug' => config('app.debug'),
        'db_default' => config('database.default'),
        'db_connections_available' => array_keys(config('database.connections')),
        'pgsql_host' => config('database.connections.pgsql.host'),
        'pgsql_database' => config('database.connections.pgsql.database'),
        'pgsql_username' => config('database.connections.pgsql.username'),
        'env_db_connection' => env('DB_CONNECTION'),
        'env_db_host' => env('DB_HOST'),
    ];

    try {
        \DB::connection()->getPdo();
        return response()->json([
            'status' => 'success',
            'message' => 'DB connection successful',
            'config' => $config
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'DB connection failed: ' . $e->getMessage(),
            'config' => $config,
            'pdo_error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-env', function() {
    return response()->json([
        'db_connection_env' => $_ENV['DB_CONNECTION'] ?? 'NOT SET',
        'db_host_env' => $_ENV['DB_HOST'] ?? 'NOT SET',
        'app_url_env' => $_ENV['APP_URL'] ?? 'NOT SET',
    ]);
});

Route::get('/debug-db', function() {
    // Ver configuración en tiempo real
    $liveConfig = [
        'db_default' => config('database.default'),
        'pgsql_host' => config('database.connections.pgsql.host'),
        'pgsql_database' => config('database.connections.pgsql.database'),
        'pgsql_username' => config('database.connections.pgsql.username'),
        'env_db_host' => env('DB_HOST'),
        'env_db_database' => env('DB_DATABASE'),
    ];
    
    // Verificar archivos de cache
    $cacheFiles = [
        'config_cache_exists' => file_exists(base_path('bootstrap/cache/config.php')),
        'services_cache_exists' => file_exists(base_path('bootstrap/cache/services.php')),
        'packages_cache_exists' => file_exists(base_path('bootstrap/cache/packages.php')),
    ];
    
    return response()->json([
        'live_config' => $liveConfig,
        'cache_files' => $cacheFiles,
        'raw_env_db_host' => $_ENV['DB_HOST'] ?? 'NOT IN ENV',
    ]);
});

// En routes/api.php
Route::get('/debug-rol', function() {
    try {
        // Verificar si el modelo existe
        if (!class_exists('App\\Models\\Rol')) {
            return response()->json(['error' => 'Modelo Rol no existe'], 500);
        }

        // Verificar conexión a base de datos
        \DB::connection()->getPdo();

        // Verificar si la tabla existe
        $tableExists = \Schema::hasTable('rols');
        
        // Contar registros (si la tabla existe)
        $count = $tableExists ? \App\Models\Rol::count() : 0;

        return response()->json([
            'model_exists' => true,
            'db_connected' => true,
            'table_exists' => $tableExists,
            'record_count' => $count,
            'table_name' => (new \App\Models\Rol())->getTable(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug-rol-controller', function() {
    try {
        // Verificar si el controlador existe
        $controllerExists = class_exists('app\Http\Controllers\usuario\RolController');
        
        // Verificar si BitacoraService existe
        $serviceExists = class_exists('app\Services\BitacoraService');
        
        // Verificar si la tabla rol existe
        $tableExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'rol'
            )
        ")[0]->exists;

        // Verificar si la tabla bitacora_eventos existe
        $bitacoraTableExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'bitacora'
            )
        ")[0]->exists;

        return response()->json([
            'rol_controller_exists' => $controllerExists,
            'bitacora_service_exists' => $serviceExists,
            'rol_table_exists' => $tableExists,
            'bitacora_table_exists' => $bitacoraTableExists,
            'available_tables' => DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public'
                ORDER BY table_name
            ")
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug-tabla-rol', function() {
    try {
        // Verificar si la tabla existe
        $tableExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'rol'
            )
        ");

        $tableName = 'rol'; // Cambia si usas otro nombre
        
        // Contar registros si la tabla existe
        if ($tableExists[0]->exists) {
            $count = DB::select("SELECT COUNT(*) as count FROM $tableName")[0]->count;
            $roles = DB::select("SELECT * FROM $tableName LIMIT 5");
        } else {
            $count = 0;
            $roles = [];
        }

        return response()->json([
            'table_exists' => $tableExists[0]->exists,
            'table_name' => $tableName,
            'record_count' => $count,
            'sample_records' => $roles,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'table_exists' => false
        ], 500);
    }
});
