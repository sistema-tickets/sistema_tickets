<?php
ob_start();

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/TicketController.php';

Cors::handle();

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $action = $_GET['action'] ?? null;
    $ctrl   = new TicketController();

    match (true) {
        $method === 'GET'  && $action === 'stats'                     => $ctrl->stats(),
        $method === 'GET'  && $id === null                            => $ctrl->index(),
        $method === 'GET'  && $id !== null && $action === 'timeline'  => $ctrl->timeline($id),
        $method === 'GET'  && $id !== null                            => $ctrl->show($id),
        $method === 'POST' && $id === null                            => $ctrl->store(),
        $method === 'POST' && $id !== null && $action === 'responder' => $ctrl->responder($id),
        $method === 'PUT'  && $id !== null && $action === 'estado'    => $ctrl->updateEstado($id),
        $method === 'PUT'  && $id !== null && $action === 'asignar'   => $ctrl->asignar($id),
        $method === 'PUT'  && $id !== null && $action === 'asunto'    => $ctrl->updateAsunto($id),
        default => Response::error('Ruta no encontrada', 404),
    };
} catch (\Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
