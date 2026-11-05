<?php

declare(strict_types=1);

use App\Controllers\AlumnoController;
use App\Controllers\AuthController;
use App\Controllers\BitacoraController;
use App\Controllers\CrudController;
use App\Controllers\DashboardController;
use App\Controllers\PerfilController;
use App\Controllers\ReporteController;
use App\Controllers\UsuarioController;

/** @var App\Core\Router $router */

// ---------------------------------------------------------------- Autenticación
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// ---------------------------------------------------------------- Panel
$router->get('/', [DashboardController::class, 'index'], ['auth']);

// ---------------------------------------------------------------- Perfil
$router->get('/perfil', [PerfilController::class, 'show'], ['auth']);
$router->post('/perfil/password', [PerfilController::class, 'updatePassword'], ['auth']);

// ---------------------------------------------------------------- Reportes (lectura)
$router->get('/reportes/{reporte}', [ReporteController::class, 'show'], ['auth']);
$router->get('/reportes/{reporte}/exportar', [ReporteController::class, 'export'], ['auth']);
$router->get('/alumnos/{id}/ficha', [AlumnoController::class, 'ficha'], ['auth']);

// ---------------------------------------------------------------- CRUD genérico
$router->get('/gestion/{entidad}', [CrudController::class, 'index'], ['auth']);
$router->get('/gestion/{entidad}/exportar', [CrudController::class, 'export'], ['auth']);
$router->get('/gestion/{entidad}/nuevo', [CrudController::class, 'create'], ['auth', 'admin']);
$router->post('/gestion/{entidad}/guardar', [CrudController::class, 'store'], ['auth', 'admin']);
$router->get('/gestion/{entidad}/editar', [CrudController::class, 'edit'], ['auth', 'admin']);
$router->post('/gestion/{entidad}/actualizar', [CrudController::class, 'update'], ['auth', 'admin']);
$router->post('/gestion/{entidad}/eliminar', [CrudController::class, 'destroy'], ['auth', 'admin']);

// ---------------------------------------------------------------- Administración
$router->get('/usuarios', [UsuarioController::class, 'index'], ['auth', 'admin']);
$router->get('/usuarios/nuevo', [UsuarioController::class, 'create'], ['auth', 'admin']);
$router->post('/usuarios/guardar', [UsuarioController::class, 'store'], ['auth', 'admin']);
$router->get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'], ['auth', 'admin']);
$router->post('/usuarios/{id}/actualizar', [UsuarioController::class, 'update'], ['auth', 'admin']);
$router->post('/usuarios/{id}/desbloquear', [UsuarioController::class, 'unlock'], ['auth', 'admin']);
$router->post('/usuarios/{id}/eliminar', [UsuarioController::class, 'destroy'], ['auth', 'admin']);

$router->get('/bitacora', [BitacoraController::class, 'index'], ['auth', 'admin']);
