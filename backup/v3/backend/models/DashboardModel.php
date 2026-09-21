<?php

require_once __DIR__ . '/../config/database.php';

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function metrics(): array
    {
        return [
            'kpi'             => $this->kpiCards(),
            'chart_semanal'   => $this->chartSemanal(),
            'chart_estado'    => $this->chartEstado(),
            'chart_prioridad' => $this->chartPrioridad(),
            'chart_sla'       => $this->chartSla(),
        ];
    }

    private function kpiCards(): array
    {
        $total = (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets t"
        )->fetchColumn();

        $asignados = (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets t WHERE t.asignado_a IS NOT NULL"
        )->fetchColumn();

        $activos = (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets t
             JOIN estados e ON e.id = t.estado_id
             WHERE e.nombre NOT IN ('Atendido','Cerrado','Rechazado')"
        )->fetchColumn();

        $cerrados = (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets t
             JOIN estados e ON e.id = t.estado_id
             WHERE e.nombre IN ('Atendido','Cerrado')"
        )->fetchColumn();

        $slaStmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) <= sc.horas_resolucion THEN 1 ELSE 0 END) AS dentro
             FROM tickets t
             JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
             WHERE t.estado_id IN (7, 8) AND t.closed_at IS NOT NULL"
        );
        $slaRow    = $slaStmt->fetch();
        $totalSLA  = (int) $slaRow['total'];
        $dentroSLA = (int) $slaRow['dentro'];
        $pctSLA    = $totalSLA > 0 ? round($dentroSLA / $totalSLA * 100) : null;

        return [
            'total'      => $total,
            'asignados'  => $asignados,
            'activos'    => $activos,
            'cerrados'   => $cerrados,
            'pct_sla'    => $pctSLA,
            'dentro_sla' => $dentroSLA,
            'total_sla'  => $totalSLA,
        ];
    }

    private function chartSemanal(): array
    {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, SUM(creados) AS creados, SUM(cerrados) AS cerrados
             FROM (
                 SELECT DATE(t.created_at) AS fecha, COUNT(*) AS creados, 0 AS cerrados
                 FROM tickets t WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                 GROUP BY DATE(t.created_at)
                 UNION ALL
                 SELECT DATE(t.closed_at) AS fecha, 0 AS creados, COUNT(*) AS cerrados
                 FROM tickets t WHERE t.closed_at IS NOT NULL AND t.closed_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                 GROUP BY DATE(t.closed_at)
             ) subq GROUP BY fecha ORDER BY fecha ASC"
        );
        $rows = $stmt->fetchAll();

        $labels = []; $creados = []; $cerrados = [];
        foreach ($rows as $r) {
            $labels[]   = $r['fecha'];
            $creados[]  = (int) $r['creados'];
            $cerrados[] = (int) $r['cerrados'];
        }
        return ['labels' => $labels, 'creados' => $creados, 'cerrados' => $cerrados];
    }

    private function chartEstado(): array
    {
        $stmt = $this->db->query(
            "SELECT e.nombre AS estado, COUNT(t.id) AS total
             FROM tickets t JOIN estados e ON e.id = t.estado_id
             GROUP BY e.id, e.nombre ORDER BY e.id"
        );
        $rows = $stmt->fetchAll();

        $labels = []; $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['estado'];
            $data[]   = (int) $r['total'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

    private function chartPrioridad(): array
    {
        $stmt = $this->db->query(
            "SELECT p.nombre AS prioridad, p.orden, COUNT(t.id) AS total
             FROM tickets t JOIN prioridades p ON p.id = t.prioridad_id
             GROUP BY p.id, p.nombre, p.orden ORDER BY p.orden"
        );
        $rows = $stmt->fetchAll();

        $labels = []; $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['prioridad'];
            $data[]   = (int) $r['total'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

    private function chartSla(): array
    {
        $stmt = $this->db->query(
            "SELECT p.nombre AS prioridad, COUNT(t.id) AS total,
                    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) <= (COALESCE(sc.horas_resolucion, 48) * 60) THEN 1 ELSE 0 END) AS dentro_sla
             FROM tickets t
             JOIN prioridades p ON p.id = t.prioridad_id
             LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
             WHERE t.estado_id IN (7, 8) AND t.closed_at IS NOT NULL
             GROUP BY p.id, p.nombre, p.orden ORDER BY p.orden"
        );
        $rows = $stmt->fetchAll();

        $labels = []; $dentro = []; $total = [];
        foreach ($rows as $r) {
            $labels[] = $r['prioridad'];
            $dentro[] = (int) $r['dentro_sla'];
            $total[]  = (int) $r['total'];
        }
        return ['labels' => $labels, 'dentro' => $dentro, 'total' => $total];
    }

    public function pending(array $filters = [], int $page = 1, int $limit = 20): array
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
        if (!empty($filters['sla_estado'])) {
            $slaVal = $filters['sla_estado'];
            if ($slaVal === 'vencido') {
                $where[] = "CASE WHEN t.estado_id IN (8, 9) THEN NULL WHEN sc.horas_resolucion IS NULL THEN NULL WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60) THEN 'vencido' ELSE NULL END = 'vencido'";
            } elseif ($slaVal === 'en_riesgo') {
                $where[] = "CASE WHEN t.estado_id IN (8, 9) THEN NULL WHEN sc.horas_resolucion IS NULL THEN NULL WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60 * 0.8) AND TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) <= (sc.horas_resolucion * 60) THEN 'en_riesgo' ELSE NULL END = 'en_riesgo'";
            } elseif ($slaVal === 'ok') {
                $where[] = "CASE WHEN t.estado_id IN (8, 9) THEN NULL WHEN sc.horas_resolucion IS NULL THEN NULL WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) <= (sc.horas_resolucion * 60 * 0.8) THEN 'ok' ELSE NULL END = 'ok'";
            }
        }

        $sql = "SELECT t.id, t.titulo, t.numero_ticket, t.created_at, t.updated_at,
                       e.nombre AS estado,
                       p.nombre AS prioridad,
                       u.nombre AS solicitante,
                       a.nombre AS asignado_a,
                       s.nombre AS sitio_nombre,
                       ts.nombre AS tipo_solicitud,
                       ln.nombre AS linea_negocio,
                       CASE
                           WHEN t.estado_id IN (8, 9) THEN NULL
                           WHEN sc.horas_resolucion IS NULL THEN 'sin_sla'
                           WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60) THEN 'vencido'
                           WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60 * 0.8) THEN 'en_riesgo'
                           ELSE 'ok'
                       END AS sla_estado,
                       CASE
                           WHEN t.estado_id IN (8, 9) THEN NULL
                           WHEN sc.horas_resolucion IS NULL THEN NULL
                           ELSE ROUND(GREATEST(sc.horas_resolucion - (TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60), 0), 1)
                       END AS sla_horas_restantes
                FROM tickets t
                JOIN estados         e  ON e.id  = t.estado_id
                JOIN prioridades     p  ON p.id  = t.prioridad_id
                JOIN usuarios        u  ON u.id  = t.creado_por
                LEFT JOIN usuarios   a  ON a.id  = t.asignado_a
                LEFT JOIN sitios          s  ON s.id  = t.sitio_id
                LEFT JOIN tipos_solicitud ts ON ts.id = t.tipo_solicitud_id
                LEFT JOIN lineas_negocio  ln ON ln.id = t.linea_negocio_id
                LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        $countParams = array_slice($params, 0, -2);
        $countSql = "SELECT COUNT(*) FROM tickets t
                     JOIN estados e ON e.id = t.estado_id
                     JOIN prioridades p ON p.id = t.prioridad_id
                     LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
                     WHERE " . implode(' AND ', $where);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        return [
            'tickets'     => $tickets,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => ceil($total / $limit),
        ];
    }
}
