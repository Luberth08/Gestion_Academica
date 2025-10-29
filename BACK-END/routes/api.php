<?php

// Administrar Usuario
require __DIR__ . '/usuario/rol.routes.php';
require __DIR__ . '/usuario/permiso.routes.php';
require __DIR__ . '/usuario/rol_permiso.routes.php';
require __DIR__ . '/usuario/usuario.routes.php';
require __DIR__ . '/usuario/auth.routes.php';

// Administrar Auditoria
require __DIR__ . '/auditoria/bitacora.routes.php';
require __DIR__ . '/auditoria/detalle_bitacora.routes.php';

// Administrar Gestion Academica
require __DIR__ . '/academico/docente.routes.php';
require __DIR__ . '/academico/carrera.routes.php';
require __DIR__ . '/academico/materia.routes.php';
require __DIR__ . '/academico/grupo.routes.php';
require __DIR__ . '/academico/asignacion_grupo_materia.routes.php';
require __DIR__ . '/academico/periodo.routes.php';
require __DIR__ . '/academico/gestion.routes.php';
require __DIR__ . '/academico/tipo.routes.php';
require __DIR__ . '/academico/aula.routes.php';