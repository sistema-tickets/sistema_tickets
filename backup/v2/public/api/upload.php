<?php
ob_start();

require_once dirname(__DIR__, 2) . '/backend/config/app.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Cors.php';
require_once dirname(__DIR__, 2) . '/backend/helpers/Response.php';
require_once dirname(__DIR__, 2) . '/backend/config/database.php';
require_once dirname(__DIR__, 2) . '/backend/middleware/Auth.php';
require_once dirname(__DIR__, 2) . '/backend/config/constants.php';

Cors::handle();
if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::check();
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    if ($ticketId <= 0) Response::error('ticket_id requerido', 400);

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) Response::error('Archivo inválido', 400);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg','jpeg','png','gif','pdf','txt','doc','docx','xls','xlsx','zip','rar','msg','eml'];
    if (!in_array($ext, $allowedExts)) Response::error('Tipo de archivo no permitido', 400);

    $uploadDir = dirname(__DIR__, 2) . '/uploads/tickets/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    $nombreArchivo = $ticketId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = $uploadDir . $nombreArchivo;

    if (!move_uploaded_file($file['tmp_name'], $destino)) Response::error('Error al guardar', 500);

    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO adjuntos (ticket_id, nombre_original, nombre_archivo, mime_type, tamanio_bytes, subido_por)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $ticketId,
        $file['name'],
        $nombreArchivo,
        $file['type'],
        $file['size'],
        $usuario['id'],
    ]);
    $adjId = (int)$db->lastInsertId();

    Response::success(['id' => $adjId, 'nombre' => $file['name'], 'archivo' => $nombreArchivo], 'Archivo subido', 201);

} elseif ($method === 'DELETE') {
    Auth::checkAdmin();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) Response::error('id requerido', 400);

    $db = getDB();
    $stmt = $db->prepare('SELECT nombre_archivo FROM adjuntos WHERE id = ?');
    $stmt->execute([$id]);
    $adj = $stmt->fetch();
    if (!$adj) Response::error('Archivo no encontrado', 404);

    $filePath = dirname(__DIR__, 2) . '/uploads/tickets/' . $adj['nombre_archivo'];
    if (file_exists($filePath)) unlink($filePath);

    $db->prepare('DELETE FROM adjuntos WHERE id = ?')->execute([$id]);
    Response::success(null, 'Archivo eliminado');

} else {
    Response::error('Método no permitido', 405);
}
