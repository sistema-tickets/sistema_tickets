<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class CatalogoController
{
    private PDO $db;
    private array $catalogos;

    public function __construct()
    {
        $this->db = getDB();
        $this->catalogos = [
            'regiones' => [
                'table' => 'regiones',
                'fields' => ['codigo', 'nombre'],
            ],
            'sitios' => [
                'table' => 'sitios',
                'fields' => ['region_id', 'nombre'],
            ],
            'lineas' => [
                'table' => 'lineas_negocio',
                'fields' => ['nombre', 'activo'],
            ],
            'tipos_solicitud' => [
                'table' => 'tipos_solicitud',
                'fields' => ['nombre'],
            ],
            'tipos_asunto' => [
                'table' => 'tipos_asunto',
                'fields' => ['nombre'],
            ],
            'lugar_incidencia' => [
                'table' => 'lugar_incidencia',
                'fields' => ['nombre'],
            ],
        ];
    }

    public function list(string $type): void
    {
        $cfg = $this->catalogos[$type] ?? null;
        if (!$cfg) Response::error('Tipo no válido', 400);

        $data = $this->db->query("SELECT * FROM {$cfg['table']} ORDER BY nombre")->fetchAll();
        Response::success($data);
    }

    public function store(string $type): void
    {
        Auth::checkSuperAdmin();
        $cfg = $this->catalogos[$type] ?? null;
        if (!$cfg) Response::error('Tipo no válido', 400);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('nombre', $body['nombre'] ?? null);
        if ($type === 'sitios') $v->required('region_id', $body['region_id'] ?? null);
        if ($v->fails()) Response::error('Datos inválidos', 422, $v->errors());

        $cols = implode(', ', $cfg['fields']);
        $ph   = implode(', ', array_fill(0, count($cfg['fields']), '?'));
        $vals = [];
        foreach ($cfg['fields'] as $f) $vals[] = $body[$f] ?? null;

        $this->db->prepare("INSERT INTO {$cfg['table']} ($cols) VALUES ($ph)")->execute($vals);
        Response::success(['id' => (int)$this->db->lastInsertId()], 'Creado', 201);
    }

    public function update(string $type, int $id): void
    {
        Auth::checkSuperAdmin();
        $cfg = $this->catalogos[$type] ?? null;
        if (!$cfg) Response::error('Tipo no válido', 400);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $sets = [];
        $vals = [];
        foreach ($cfg['fields'] as $f) {
            if (array_key_exists($f, $body)) {
                $sets[] = "$f = ?";
                $vals[] = $body[$f];
            }
        }
        if (empty($sets)) Response::error('Sin datos para actualizar', 400);

        $vals[] = $id;
        $this->db->prepare("UPDATE {$cfg['table']} SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
        Response::success(null, 'Actualizado');
    }

    public function destroy(string $type, int $id): void
    {
        Auth::checkSuperAdmin();
        $cfg = $this->catalogos[$type] ?? null;
        if (!$cfg) Response::error('Tipo no válido', 400);

        // Check dependencies before deleting
        if ($type === 'sitios') {
            $check = $this->db->prepare('SELECT COUNT(*) FROM tickets WHERE sitio_id = ?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) Response::error('No se puede eliminar: hay tickets asociados a este sitio', 409);

            $check2 = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE sitio_id = ?');
            $check2->execute([$id]);
            if ((int)$check2->fetchColumn() > 0) Response::error('No se puede eliminar: hay usuarios asociados a este sitio', 409);
        }
        if ($type === 'lineas') {
            $check = $this->db->prepare('SELECT COUNT(*) FROM tickets WHERE linea_negocio_id = ?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) Response::error('No se puede eliminar: hay tickets asociados a esta línea', 409);
        }

        $this->db->prepare("DELETE FROM {$cfg['table']} WHERE id = ?")->execute([$id]);
        Response::success(null, 'Eliminado');
    }
}
