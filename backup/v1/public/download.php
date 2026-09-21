<?php

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/middleware/Auth.php';
require_once __DIR__ . '/../backend/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::check();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('ID inválido'); }

$db = getDB();
$stmt = $db->prepare(
    'SELECT a.*, t.usuario_id AS ticket_usuario
     FROM adjuntos a
     JOIN tickets t ON t.id = a.ticket_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$adj = $stmt->fetch();

if (!$adj) { http_response_code(404); exit('Archivo no encontrado'); }

$usuario = Auth::usuario();
$esAdmin = in_array((int)$usuario['rol_id'], [2, 3]);
$esDueno = (int)$usuario['id'] === (int)$adj['ticket_usuario'];
if (!$esAdmin && !$esDueno) { http_response_code(403); exit('Acceso denegado'); }

$filePath = __DIR__ . '/../uploads/tickets/' . $adj['nombre_archivo'];
if (!file_exists($filePath)) { http_response_code(404); exit('Archivo no encontrado en disco'); }

header('Content-Description: File Transfer');
header('Content-Type: ' . ($adj['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $adj['nombre_original'] . '"');
header('Content-Length: ' . $adj['tamanio_bytes']);
header('Cache-Control: private, max-age=0');
readfile($filePath);
exit;
