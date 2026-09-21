<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class TicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function list(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['estado_id'])) {
            $where[] = 't.estado_id = ?';
            $params[] = $filters['estado_id'];
        }
        if (!empty($filters['prioridad_id'])) {
            $where[] = 't.prioridad_id = ?';
            $params[] = $filters['prioridad_id'];
        }
        if (!empty($filters['creado_por'])) {
            $where[] = 't.creado_por = ?';
            $params[] = $filters['creado_por'];
        }
        if (!empty($filters['asignado_a'])) {
            $where[] = 't.asignado_a = ?';
            $params[] = $filters['asignado_a'];
        }
        if (!empty($filters['busqueda'])) {
            $where[] = '(t.titulo LIKE ? OR t.descripcion LIKE ?)';
            $params[] = '%' . $filters['busqueda'] . '%';
            $params[] = '%' . $filters['busqueda'] . '%';
        }

        $sql = 'SELECT t.id, t.titulo, t.created_at, t.updated_at,
                       e.nombre AS estado, p.nombre AS prioridad,
                       u.nombre AS creado_por_nombre,
                       a.nombre AS asignado_a_nombre
                FROM tickets t
                LEFT JOIN estados     e ON e.id = t.estado_id
                LEFT JOIN prioridades p ON p.id = t.prioridad_id
                LEFT JOIN usuarios    u ON u.id = t.creado_por
                LEFT JOIN usuarios    a ON a.id = t.asignado_a
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?';

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countFiltered(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['estado_id']))   { $where[] = 't.estado_id = ?';   $params[] = $filters['estado_id']; }
        if (!empty($filters['prioridad_id'])){ $where[] = 't.prioridad_id = ?'; $params[] = $filters['prioridad_id']; }
        if (!empty($filters['creado_por']))  { $where[] = 't.creado_por = ?';   $params[] = $filters['creado_por']; }
        if (!empty($filters['asignado_a']))  { $where[] = 't.asignado_a = ?';   $params[] = $filters['asignado_a']; }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM tickets t WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*,
                    e.nombre AS estado, p.nombre AS prioridad,
                    u.nombre AS creado_por_nombre,
                    a.nombre AS asignado_a_nombre,
                    s.nombre AS sitio_nombre
             FROM tickets t
             LEFT JOIN estados     e ON e.id = t.estado_id
             LEFT JOIN prioridades p ON p.id = t.prioridad_id
             LEFT JOIN usuarios    u ON u.id = t.creado_por
             LEFT JOIN usuarios    a ON a.id = t.asignado_a
             LEFT JOIN sitios      s ON s.id = t.sitio_id
             WHERE t.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tickets
             (titulo, descripcion, tipo_solicitud_id, asunto_id, prioridad_id,
              estado_id, creado_por, sitio_id, linea_negocio_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['titulo'],
            $data['descripcion'],
            $data['tipo_solicitud_id']  ?? null,
            $data['asunto_id']          ?? null,
            $data['prioridad_id']       ?? PRIORIDAD_MEDIA,
            ESTADO_EN_ESPERA,
            $data['creado_por'],
            $data['sitio_id']           ?? null,
            $data['linea_negocio_id']   ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $id, int $estadoId, int $usuarioId, ?string $comentario = null): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE tickets SET estado_id = ?, updated_at = NOW() WHERE id = ?')
                     ->execute([$estadoId, $id]);

            $this->db->prepare(
                'INSERT INTO historial_estados (ticket_id, estado_id, cambiado_por, comentario)
                 VALUES (?, ?, ?, ?)'
            )->execute([$id, $estadoId, $usuarioId, $comentario]);

            $this->db->commit();
            return true;
        } catch (Exception) {
            $this->db->rollBack();
            return false;
        }
    }

    public function asignar(int $id, int $adminId, ?string $comentario = null): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'UPDATE tickets SET asignado_a = ?, estado_id = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$adminId, ESTADO_EN_PROCESO, $id]);

            $this->db->prepare(
                'INSERT INTO historial_reasignaciones (ticket_id, asignado_a, asignado_por, comentario)
                 VALUES (?, ?, ?, ?)'
            )->execute([$id, $adminId, $adminId, $comentario]);

            $this->db->commit();
            return true;
        } catch (Exception) {
            $this->db->rollBack();
            return false;
        }
    }

    public function statsResumen(): array
    {
        $stmt = $this->db->query(
            'SELECT e.nombre AS estado, COUNT(t.id) AS total
             FROM tickets t
             JOIN estados e ON e.id = t.estado_id
             GROUP BY t.estado_id, e.nombre'
        );
        return $stmt->fetchAll();
    }
}
