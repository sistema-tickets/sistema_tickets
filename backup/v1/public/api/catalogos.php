<?php

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/config/database.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Auth.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/CatalogoController.php';

Cors::handle();
if (session_status() === PHP_SESSION_NONE) session_start();

Auth::check();

$method = $_SERVER['REQUEST_METHOD'];
$type   = $_GET['type'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

$ctrl = new CatalogoController();

// Read-only catalog lists — no admin required
if ($method === 'GET' && !$id) {
    if ($type === 'sitios') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, region_id, nombre FROM sitios ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'lineas') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, nombre, activo FROM lineas_negocio ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'tipos_solicitud') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, nombre FROM tipos_solicitud ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'tipos_asunto') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, nombre FROM tipos_asunto ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'asuntos') {
        $db = getDB();
        $tipoId = isset($_GET['tipo_asunto_id']) ? (int)$_GET['tipo_asunto_id'] : null;
        if ($tipoId) {
            $stmt = $db->prepare(
                'SELECT id, tipo_asunto_id, nombre, prioridad_default_id FROM asuntos WHERE tipo_asunto_id = ? ORDER BY nombre'
            );
            $stmt->execute([$tipoId]);
        } else {
            $stmt = $db->query('SELECT id, tipo_asunto_id, nombre, prioridad_default_id FROM asuntos ORDER BY nombre');
        }
        Response::success($stmt->fetchAll());
    } elseif ($type === 'lugar_incidencia') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, nombre FROM lugar_incidencia ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'regiones') {
        $db = getDB();
        Response::success(
            $db->query('SELECT id, codigo, nombre FROM regiones ORDER BY nombre')->fetchAll()
        );
    } elseif ($type === 'usuarios') {
        Auth::checkAdmin();
        $db = getDB();
        $rol = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
        $sql = 'SELECT id, nombre, email, sitio_id, linea_negocio_id FROM usuarios WHERE activo = 1';
        if ($rol > 0) $sql .= ' AND rol_id = ' . $rol;
        $sql .= ' ORDER BY nombre';
        Response::success($db->query($sql)->fetchAll());
    } else {
        Response::error('Tipo de catálogo no válido', 400);
    }
    exit;
}

// CRUD operations — admin only
switch ($method) {
    case 'GET':
        $ctrl->list($type);
        break;
    case 'POST':
        $ctrl->store($type);
        break;
    case 'PUT':
        if (!$id) Response::error('ID requerido', 400);
        $ctrl->update($type, $id);
        break;
    case 'DELETE':
        if (!$id) Response::error('ID requerido', 400);
        $ctrl->destroy($type, $id);
        break;
    default:
        Response::error('Método no permitido', 405);
}
