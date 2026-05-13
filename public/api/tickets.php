<?php

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/TicketController.php';

Cors::handle();

if (session_status() === PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;
$ctrl   = new TicketController();

match (true) {
    $method === 'GET'  && $action === 'stats'                    => $ctrl->stats(),
    $method === 'GET'  && $id === null                           => $ctrl->index(),
    $method === 'GET'  && $id !== null                           => $ctrl->show($id),
    $method === 'POST' && $id === null                           => $ctrl->store(),
    $method === 'PUT'  && $id !== null && $action === 'estado'   => $ctrl->updateEstado($id),
    $method === 'PUT'  && $id !== null && $action === 'asignar'  => $ctrl->asignar($id),
    default => Response::error('Ruta no encontrada', 404),
};
