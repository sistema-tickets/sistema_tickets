<?php

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/AuthController.php';

Cors::handle();

if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$ctrl   = new AuthController();

match (true) {
    $method === 'POST' && $action === 'login'    => $ctrl->login(),
    $method === 'POST' && $action === 'register' => $ctrl->register(),
    $method === 'POST' && $action === 'logout'   => $ctrl->logout(),
    $method === 'GET'  && $action === 'me'       => $ctrl->me(),
    default => Response::error('Acción no válida', 404),
};
