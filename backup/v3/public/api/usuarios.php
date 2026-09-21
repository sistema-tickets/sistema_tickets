<?php
ob_start();

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/UsuarioController.php';

Cors::handle();

if (session_status() === PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$ctrl   = new UsuarioController();

match (true) {
    $method === 'GET'   && $id === null => $ctrl->index(),
    $method === 'POST'  && $id === null => $ctrl->store(),
    $method === 'PUT'   && $id !== null => $ctrl->update($id),
    $method === 'DELETE' && $id !== null => $ctrl->destroy($id),
    default => Response::error('Ruta no encontrada', 404),
};
