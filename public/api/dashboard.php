<?php
ob_start();

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/DashboardController.php';

Cors::handle();

if (session_status() === PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$ctrl   = new DashboardController();

match (true) {
    $method === 'GET' && $action === 'metrics' => $ctrl->metrics(),
    $method === 'GET' && $action === 'pending' => $ctrl->pending(),
    default => Response::error('Ruta no encontrada', 404),
};
