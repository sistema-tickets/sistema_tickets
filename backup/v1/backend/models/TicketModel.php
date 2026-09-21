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
        if (!empty($filters['usuario_id'])) {
            $where[] = 't.usuario_id = ?';
            $params[] = $filters['usuario_id'];
        }
        if (!empty($filters['admin_id'])) {
            $where[] = 't.admin_id = ?';
            $params[] = $filters['admin_id'];
        }
        if (!empty($filters['tipo_solicitud_id'])) {
            $where[] = 't.tipo_solicitud_id = ?';
            $params[] = $filters['tipo_solicitud_id'];
        }
        if (!empty($filters['busqueda'])) {
            $where[] = 't.descripcion LIKE ?';
            $params[] = '%' . $filters['busqueda'] . '%';
        }

        $sql = 'SELECT t.id, t.numero_ticket, t.descripcion, t.created_at, t.updated_at,
                       e.nombre AS estado,
                       p.nombre AS prioridad,
                       u.nombre AS usuario_nombre,
                       a.nombre AS admin_nombre,
                       s.nombre AS sitio_nombre,
                       ln.nombre AS linea_negocio_nombre,
                       ts.nombre AS tipo_solicitud_nombre,
                       CASE
                           WHEN t.estado_id IN (8, 9) THEN NULL
                           WHEN sc.horas_resolucion IS NULL THEN NULL
                           WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60) THEN \'vencido\'
                           WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60 * 0.8) THEN \'en_riesgo\'
                           ELSE \'ok\'
                       END AS sla_estado
                FROM tickets t
                LEFT JOIN estados          e  ON e.id  = t.estado_id
                LEFT JOIN prioridades      p  ON p.id  = t.prioridad_id
                LEFT JOIN usuarios         u  ON u.id  = t.usuario_id
                LEFT JOIN usuarios         a  ON a.id  = t.admin_id
                LEFT JOIN sitios           s  ON s.id  = t.sitio_id
                LEFT JOIN lineas_negocio   ln ON ln.id = t.linea_negocio_id
                LEFT JOIN tipos_solicitud  ts ON ts.id = t.tipo_solicitud_id
                LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
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

        if (!empty($filters['estado_id']))        { $where[] = 't.estado_id = ?';        $params[] = $filters['estado_id']; }
        if (!empty($filters['prioridad_id']))      { $where[] = 't.prioridad_id = ?';     $params[] = $filters['prioridad_id']; }
        if (!empty($filters['usuario_id']))        { $where[] = 't.usuario_id = ?';       $params[] = $filters['usuario_id']; }
        if (!empty($filters['admin_id']))          { $where[] = 't.admin_id = ?';         $params[] = $filters['admin_id']; }
        if (!empty($filters['tipo_solicitud_id'])) { $where[] = 't.tipo_solicitud_id = ?'; $params[] = $filters['tipo_solicitud_id']; }
        if (!empty($filters['busqueda'])) {
            $where[] = 't.descripcion LIKE ?';
            $params[] = '%' . $filters['busqueda'] . '%';
        }

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
                    e.nombre  AS estado,
                    p.nombre  AS prioridad,
                    u.nombre  AS usuario_nombre,
                    a.nombre  AS admin_nombre,
                    s.nombre  AS sitio_nombre,
                    ln.nombre AS linea_negocio_nombre,
                    ts.nombre AS tipo_solicitud_nombre,
                    asn.nombre AS asunto_nombre,
                    ta.nombre  AS tipo_asunto_nombre
             FROM tickets t
             LEFT JOIN estados         e   ON e.id   = t.estado_id
             LEFT JOIN prioridades     p   ON p.id   = t.prioridad_id
             LEFT JOIN usuarios        u   ON u.id   = t.usuario_id
             LEFT JOIN usuarios        a   ON a.id   = t.admin_id
             LEFT JOIN sitios          s   ON s.id   = t.sitio_id
             LEFT JOIN lineas_negocio  ln  ON ln.id  = t.linea_negocio_id
             LEFT JOIN tipos_solicitud ts  ON ts.id  = t.tipo_solicitud_id
             LEFT JOIN asuntos         asn ON asn.id = t.asunto_id
             LEFT JOIN tipos_asunto    ta  ON ta.id  = asn.tipo_asunto_id
             WHERE t.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        // Generar numero_ticket sin depender de UUID() (36 chars no cabe en varchar(20))
        $nextSt = $this->db->query("SELECT CONCAT('TKT-', LPAD(COALESCE(MAX(id), 0) + 1, 5, '0')) FROM tickets");
        $numeroTicket = $nextSt->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO tickets
             (numero_ticket, descripcion, tipo_solicitud_id, asunto_id, prioridad_id,
              estado_id, usuario_id, sitio_id, linea_negocio_id, celular, admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $numeroTicket,
            $data['descripcion'],
            $data['tipo_solicitud_id']  ?? null,
            $data['asunto_id']          ?? null,
            $data['prioridad_id']       ?? PRIORIDAD_MEDIA,
            $data['estado_id']          ?? ESTADO_EN_ESPERA,
            $data['usuario_id'],
            $data['sitio_id']           ?? null,
            $data['linea_negocio_id']   ?? null,
            $data['celular']            ?? null,
            $data['admin_id']           ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $id, int $estadoId, int $usuarioId, ?string $nota = null, ?int $prioridadId = null): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT estado_id FROM tickets WHERE id = ?');
            $stmt->execute([$id]);
            $estadoAnterior = (int) $stmt->fetchColumn() ?: null;

            if ($prioridadId) {
                $this->db->prepare('UPDATE tickets SET estado_id = ?, prioridad_id = ?, updated_at = NOW() WHERE id = ?')
                         ->execute([$estadoId, $prioridadId, $id]);
            } else {
                $this->db->prepare('UPDATE tickets SET estado_id = ?, updated_at = NOW() WHERE id = ?')
                         ->execute([$estadoId, $id]);
            }

            if (in_array($estadoId, [ESTADO_CERRADO, ESTADO_RECHAZADO])) {
                $this->db->prepare('UPDATE tickets SET closed_at = NOW() WHERE id = ? AND closed_at IS NULL')
                         ->execute([$id]);
            }

            $this->db->prepare(
                'INSERT INTO historial_estados (ticket_id, estado_anterior_id, estado_nuevo_id, cambiado_por, nota)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $estadoAnterior, $estadoId, $usuarioId, $nota]);

            $this->db->commit();
            return true;
        } catch (Exception) {
            $this->db->rollBack();
            return false;
        }
    }

    public function asignar(int $id, int $adminId, int $cambiadoPor, ?string $motivo = null): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT admin_id, estado_id FROM tickets WHERE id = ?');
            $stmt->execute([$id]);
            $ticket = $stmt->fetch();

            $this->db->prepare(
                'UPDATE tickets SET admin_id = ?, estado_id = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$adminId, ESTADO_EN_PROCESO, $id]);

            $this->db->prepare(
                'INSERT INTO historial_reasignaciones (ticket_id, admin_anterior, admin_nuevo, cambiado_por, motivo)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $ticket['admin_id'], $adminId, $cambiadoPor, $motivo]);

            $this->db->prepare(
                'INSERT INTO historial_estados (ticket_id, estado_anterior_id, estado_nuevo_id, cambiado_por, nota)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $ticket['estado_id'], ESTADO_EN_PROCESO, $cambiadoPor, $motivo]);

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

    public function updateAsunto(int $id, ?int $asuntoId): bool
    {
        $stmt = $this->db->prepare('UPDATE tickets SET asunto_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$asuntoId, $id]);
        return $stmt->rowCount() > 0;
    }

    public function crearRespuesta(int $ticketId, int $usuarioId, string $contenido): int
    {
        $this->db->prepare(
            'INSERT INTO respuestas (ticket_id, usuario_id, contenido) VALUES (?, ?, ?)'
        )->execute([$ticketId, $usuarioId, $contenido]);
        return (int)$this->db->lastInsertId();
    }

    public function getTimeline(int $ticketId): array
    {
        $historial = $this->db->prepare(
            'SELECT h.id, h.nota AS contenido, h.created_at,
                    \'estado\' AS tipo,
                    ea.nombre AS estado_anterior,
                    en.nombre AS estado_nuevo,
                    u.nombre AS usuario_nombre
             FROM historial_estados h
             LEFT JOIN estados ea ON ea.id = h.estado_anterior_id
             LEFT JOIN estados en ON en.id = h.estado_nuevo_id
             LEFT JOIN usuarios u ON u.id = h.cambiado_por
             WHERE h.ticket_id = ?
             ORDER BY h.created_at ASC'
        );
        $historial->execute([$ticketId]);
        $items = $historial->fetchAll();

        $respuestas = $this->db->prepare(
            'SELECT r.id, r.contenido, r.created_at,
                    \'respuesta\' AS tipo,
                    NULL AS estado_anterior, NULL AS estado_nuevo,
                    u.nombre AS usuario_nombre
             FROM respuestas r
             LEFT JOIN usuarios u ON u.id = r.usuario_id
             WHERE r.ticket_id = ?
             ORDER BY r.created_at ASC'
        );
        $respuestas->execute([$ticketId]);
        $items = array_merge($items, $respuestas->fetchAll());

        $adjuntos = $this->db->prepare(
            'SELECT a.id, a.nombre_original, a.tamanio_bytes, a.mime_type, a.created_at,
                    \'adjunto\' AS tipo,
                    u.nombre AS usuario_nombre
             FROM adjuntos a
             LEFT JOIN usuarios u ON u.id = a.subido_por
             WHERE a.ticket_id = ?
             ORDER BY a.created_at ASC'
        );
        $adjuntos->execute([$ticketId]);
        $items = array_merge($items, $adjuntos->fetchAll());

        usort($items, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
        return $items;
    }

    public function getAdjuntos(int $ticketId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.nombre_original, a.tamanio_bytes, a.mime_type, a.created_at,
                    u.nombre AS subido_por_nombre
             FROM adjuntos a
             LEFT JOIN usuarios u ON u.id = a.subido_por
             WHERE a.ticket_id = ?
             ORDER BY a.created_at ASC'
        );
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }
}
