<?php

use App\Middleware\CsrfMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\TenantMiddleware;

// Rutas públicas
$router->get('/', function() {
    return 'ISO 27001 Platform - Coming Soon';
});

$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login', [CsrfMiddleware::class]);

$router->get('/registro', 'AuthController@showRegister');
$router->post('/registro', 'AuthController@register', [CsrfMiddleware::class]);

$router->get('/logout', 'AuthController@logout');

// API pública
$router->post('/api/check-email', 'AuthController@checkEmail', [CsrfMiddleware::class]);

// Rutas protegidas
$router->group([AuthMiddleware::class, TenantMiddleware::class], function($router) {
    
    // Dashboard
    $router->get('/dashboard', 'DashboardController@index');
    
    // Controles
    $router->get('/controles', 'ControlController@index');
    $router->get('/controles/{id}', 'ControlController@show');
    $router->post('/controles/{id}/evaluar', 'ControlController@evaluate', [CsrfMiddleware::class]);
    
    // GAP Analysis
    $router->get('/gap', 'GapController@index');
    $router->get('/gap/crear', 'GapController@create');
    $router->post('/gap', 'GapController@store', [CsrfMiddleware::class]);
    $router->get('/gap/{id}', 'GapController@show');
    $router->post('/gap/{id}/accion', 'GapController@addAction', [CsrfMiddleware::class]);
    $router->post('/gap/{id}/completar-accion', 'GapController@completeAction', [CsrfMiddleware::class]);
    $router->delete('/gap/{id}', 'GapController@delete', [CsrfMiddleware::class]);
    
    // Evidencias
    $router->get('/evidencias', 'EvidenciaController@index');
    $router->get('/evidencias/subir', 'EvidenciaController@create');
    $router->post('/evidencias', 'EvidenciaController@store', [CsrfMiddleware::class]);
    $router->get('/evidencias/{id}/descargar', 'EvidenciaController@download');
    $router->post('/evidencias/{id}/validar', 'EvidenciaController@validateEvidencia', [CsrfMiddleware::class]);
    
    // Requerimientos
    $router->get('/requerimientos', 'RequerimientoController@index');
    $router->get('/requerimientos/{id}', 'RequerimientoController@show');
});
