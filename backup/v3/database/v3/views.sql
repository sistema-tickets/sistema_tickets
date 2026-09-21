-- ============================================================
--  VISTAS v3  |  bd_tickets
--  Incluye: vistas corregidas de v2 + nuevas vistas de dashboard.
-- ============================================================
--  NOTAS:
--  · DEFINER eliminado (compatible con cualquier usuario MySQL).
--  · Vistas existentes se recrean con CREATE OR REPLACE VIEW.
--  · Las vistas de dashboard son NUEVAS (prefijo v_dash_).
-- ============================================================

USE `bd_tickets`;

-- ============================================================
-- BASE: v_tiempos_estado_ticket
--  Calcula cuántos minutos estuvo cada ticket en cada estado.
--  CORRECCIÓN v3: nada que cambiar; la lógica LEAD() es correcta.
-- ============================================================
CREATE OR REPLACE VIEW `v_tiempos_estado_ticket` AS
SELECT
    base.ticket_id,
    base.estado_nuevo_id                                     AS estado_id,
    base.estado,
    base.entrada,
    base.salida,
    TIMESTAMPDIFF(MINUTE, base.entrada,
        COALESCE(base.salida, NOW()))                        AS minutos_en_estado
FROM (
    SELECT
        h.ticket_id,
        h.estado_nuevo_id,
        e.nombre                                             AS estado,
        h.created_at                                         AS entrada,
        LEAD(h.created_at)
            OVER (PARTITION BY h.ticket_id ORDER BY h.created_at) AS salida
    FROM historial_estados h
    JOIN estados e ON e.id = h.estado_nuevo_id
) base;

-- ============================================================
-- v_promedio_por_estado
--  Promedio de permanencia por estado (para análisis de cuellos).
--  Sin cambios vs v2.
-- ============================================================
CREATE OR REPLACE VIEW `v_promedio_por_estado` AS
SELECT
    v.estado_id,
    v.estado,
    e.es_terminal,
    e.permite_reabrir,
    COUNT(*)                                 AS total_registros,
    ROUND(AVG(v.minutos_en_estado), 1)       AS promedio_minutos,
    ROUND(AVG(v.minutos_en_estado) / 60, 2)  AS promedio_horas,
    MAX(v.minutos_en_estado)                 AS max_minutos,
    MIN(v.minutos_en_estado)                 AS min_minutos
FROM v_tiempos_estado_ticket v
JOIN estados e ON e.id = v.estado_id
GROUP BY v.estado_id, v.estado, e.es_terminal, e.permite_reabrir
ORDER BY promedio_minutos DESC;

-- ============================================================
-- v_promedio_por_asunto
--  SLA y cumplimiento por tipo de asunto.
--  CORRECCIÓN v3: filtro solo estado_id=8 (Cerrado) para SLA
--  real; excluimos Rechazado (9) porque no hubo resolución.
-- ============================================================
CREATE OR REPLACE VIEW `v_promedio_por_asunto` AS
SELECT
    a.id                                                             AS asunto_id,
    a.nombre                                                         AS asunto,
    ta.nombre                                                        AS tipo_asunto,
    p.nombre                                                         AS prioridad_default,
    a.horas_respuesta                                                AS sla_respuesta_h,
    a.horas_resolucion                                               AS sla_resolucion_h,
    COUNT(DISTINCT t.id)                                             AS total_tickets,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)) / 60, 2)
                                                                     AS promedio_horas_resolucion,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)), 1) AS promedio_minutos_resolucion,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
              <= (a.horas_resolucion * 60) THEN 1 ELSE 0 END)       AS dentro_sla,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
               > (a.horas_resolucion * 60) THEN 1 ELSE 0 END)       AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                          <= (a.horas_resolucion * 60) THEN 1 ELSE 0 END)
        / NULLIF(COUNT(DISTINCT t.id), 0), 1)                        AS pct_cumplimiento_sla
FROM tickets t
JOIN asuntos     a  ON a.id  = t.asunto_id
JOIN tipos_asunto ta ON ta.id = a.tipo_asunto_id
JOIN prioridades  p  ON p.id  = a.prioridad_default_id
WHERE t.estado_id = 8                 -- solo Cerrado
  AND t.closed_at IS NOT NULL
GROUP BY a.id, a.nombre, ta.nombre, p.nombre, a.horas_respuesta, a.horas_resolucion
ORDER BY total_tickets DESC;

-- ============================================================
-- v_promedio_por_prioridad
--  SLA y primera respuesta por nivel de prioridad.
--  CORRECCIÓN v3: primera respuesta usa índice idx_hist_ticket_fecha.
-- ============================================================
CREATE OR REPLACE VIEW `v_promedio_por_prioridad` AS
SELECT
    p.id                                                             AS prioridad_id,
    p.nombre                                                         AS prioridad,
    sc.horas_respuesta                                               AS sla_respuesta_h,
    sc.horas_resolucion                                              AS sla_resolucion_h,
    COUNT(DISTINCT t.id)                                             AS total_tickets,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)) / 60, 2)
                                                                     AS promedio_horas_resolucion,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)), 1) AS promedio_minutos_resolucion,
    ROUND(AVG(pr.minutos_primera_respuesta) / 60, 2)                 AS promedio_horas_primera_respuesta,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
              <= (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60) THEN 1 ELSE 0 END)
                                                                     AS dentro_sla,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
               > (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60) THEN 1 ELSE 0 END)
                                                                     AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                          <= (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60) THEN 1 ELSE 0 END)
        / NULLIF(COUNT(DISTINCT t.id), 0), 1)                        AS pct_cumplimiento_sla
FROM tickets t
JOIN prioridades        p  ON p.id  = t.prioridad_id
LEFT JOIN asuntos       a  ON a.id  = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = p.id
-- Primera respuesta: primera entrada en historial que no sea "En espera"
LEFT JOIN (
    SELECT ticket_id,
           TIMESTAMPDIFF(MINUTE,
               (SELECT created_at FROM tickets WHERE id = h.ticket_id),
               MIN(h.created_at)) AS minutos_primera_respuesta
    FROM historial_estados h
    WHERE h.estado_nuevo_id <> 1
    GROUP BY h.ticket_id
) pr ON pr.ticket_id = t.id
WHERE t.estado_id = 8
  AND t.closed_at IS NOT NULL
GROUP BY p.id, p.nombre, p.orden, sc.horas_respuesta, sc.horas_resolucion
ORDER BY p.orden;

-- ============================================================
-- v_tickets_pendientes
--  Tickets activos con métricas SLA en tiempo real.
--  CORRECCIÓN v3: JOIN con historial cambia a LEFT JOIN con
--  fallback a created_at para tickets sin historial registrado.
-- ============================================================
CREATE OR REPLACE VIEW `v_tickets_pendientes` AS
SELECT
    t.id                                                             AS ticket_id,
    t.numero_ticket,
    t.descripcion,
    e.nombre                                                         AS estado_actual,
    p.nombre                                                         AS prioridad,
    u.nombre                                                         AS usuario,
    adm.nombre                                                       AS admin_asignado,
    s.nombre                                                         AS sitio,
    ln.nombre                                                        AS linea_negocio,
    a.nombre                                                         AS asunto,
    ts.nombre                                                        AS tipo_solicitud,
    t.genera_gasto,
    -- Momento desde el que está en su estado actual
    COALESCE(ultimo.created_at, t.created_at)                        AS desde_estado_actual,
    TIMESTAMPDIFF(MINUTE, COALESCE(ultimo.created_at, t.created_at), NOW())
                                                                     AS minutos_en_estado_actual,
    ROUND(TIMESTAMPDIFF(MINUTE, COALESCE(ultimo.created_at, t.created_at), NOW()) / 60, 1)
                                                                     AS horas_en_estado_actual,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60, 1)        AS horas_desde_creacion,
    -- SLA: usa asunto si existe, sino prioridad como fallback
    COALESCE(a.horas_resolucion,  sc.horas_resolucion)               AS sla_horas_limite,
    COALESCE(a.horas_respuesta,   sc.horas_respuesta)                AS sla_horas_respuesta,
    ROUND(COALESCE(a.horas_resolucion, sc.horas_resolucion)
        - TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60, 1)        AS sla_horas_restantes,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) * 100.0
        / NULLIF(COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60, 0), 1)
                                                                     AS pct_sla_consumido,
    CASE
        WHEN COALESCE(a.horas_resolucion, sc.horas_resolucion) IS NULL THEN 'sin_sla'
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW())
              > (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)          THEN 'vencido'
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW())
              > (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60 * 0.8)    THEN 'en_riesgo'
        ELSE 'ok'
    END                                                              AS sla_estado
FROM tickets t
JOIN estados    e   ON e.id  = t.estado_id
JOIN prioridades p  ON p.id  = t.prioridad_id
JOIN usuarios   u   ON u.id  = t.usuario_id
LEFT JOIN usuarios        adm ON adm.id = t.admin_id
LEFT JOIN sitios          s   ON s.id   = t.sitio_id
LEFT JOIN lineas_negocio  ln  ON ln.id  = t.linea_negocio_id
LEFT JOIN asuntos         a   ON a.id   = t.asunto_id
LEFT JOIN tipos_solicitud ts  ON ts.id  = t.tipo_solicitud_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
-- Último cambio de estado (LEFT JOIN para tickets sin historial)
LEFT JOIN (
    SELECT ticket_id, MAX(created_at) AS created_at
    FROM   historial_estados
    GROUP  BY ticket_id
) ultimo ON ultimo.ticket_id = t.id
WHERE t.estado_id NOT IN (8, 9)       -- excluye Cerrado y Rechazado
ORDER BY sla_horas_restantes ASC;     -- los más urgentes primero

-- ============================================================
-- v_sla_cumplimiento
--  Detalle por ticket cerrado (histórico de cumplimiento).
--  Sin cambios estructurales vs v2; ya es correcto.
-- ============================================================
CREATE OR REPLACE VIEW `v_sla_cumplimiento` AS
SELECT
    t.id                                                             AS ticket_id,
    t.numero_ticket,
    t.created_at                                                     AS fecha_creacion,
    t.closed_at                                                      AS fecha_cierre,
    u.nombre                                                         AS usuario,
    adm.nombre                                                       AS admin,
    p.nombre                                                         AS prioridad,
    ta.nombre                                                        AS tipo_asunto,
    a.nombre                                                         AS asunto,
    ts.nombre                                                        AS tipo_solicitud,
    COALESCE(a.horas_resolucion, sc.horas_resolucion)                AS sla_comprometido_h,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) / 60, 2) AS horas_reales,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) / 60
        - COALESCE(a.horas_resolucion, sc.horas_resolucion), 2)      AS desviacion_h,
    CASE
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
              <= (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)
            THEN 'dentro_sla'
        ELSE 'fuera_sla'
    END                                                              AS resultado_sla,
    s.nombre                                                         AS sitio,
    ln.nombre                                                        AS linea_negocio
FROM tickets t
JOIN usuarios     u   ON u.id   = t.usuario_id
LEFT JOIN usuarios        adm  ON adm.id  = t.admin_id
JOIN prioridades  p   ON p.id   = t.prioridad_id
LEFT JOIN asuntos         a    ON a.id    = t.asunto_id
LEFT JOIN tipos_asunto    ta   ON ta.id   = a.tipo_asunto_id
LEFT JOIN tipos_solicitud ts   ON ts.id   = t.tipo_solicitud_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
LEFT JOIN sitios          s    ON s.id    = t.sitio_id
LEFT JOIN lineas_negocio  ln   ON ln.id   = t.linea_negocio_id
WHERE t.estado_id = 8                 -- solo Cerrado
  AND t.closed_at IS NOT NULL
  AND COALESCE(a.horas_resolucion, sc.horas_resolucion) IS NOT NULL
ORDER BY t.closed_at DESC;

-- ============================================================
-- v_metricas_admin
--  Rendimiento por administrador.
--  CORRECCIÓN v3: subquery correlacionada reemplazada por JOIN
--  a subconsulta agrupada (mucho más eficiente en tablas grandes).
-- ============================================================
CREATE OR REPLACE VIEW `v_metricas_admin` AS
SELECT
    u.id                                                             AS admin_id,
    u.nombre                                                         AS admin,
    COUNT(DISTINCT t.id)                                             AS total_asignados,
    SUM(CASE WHEN t.estado_id NOT IN (8, 9) THEN 1 ELSE 0 END)      AS activos,
    SUM(CASE WHEN t.estado_id = 8           THEN 1 ELSE 0 END)      AS cerrados,
    SUM(CASE WHEN t.estado_id = 9           THEN 1 ELSE 0 END)      AS rechazados,
    ROUND(
        AVG(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                 THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) END) / 60, 1)
                                                                     AS promedio_horas_resolucion,
    -- Primera respuesta: usando JOIN en lugar de subquery correlacionada
    ROUND(AVG(pr.minutos_primera_respuesta) / 60, 1)                 AS promedio_horas_primera_respuesta,
    SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
              AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                  <= (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)
             THEN 1 ELSE 0 END)                                      AS dentro_sla,
    SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
              AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                   > (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)
             THEN 1 ELSE 0 END)                                      AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                          AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                              <= (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)
                         THEN 1 ELSE 0 END)
        / NULLIF(SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                          THEN 1 ELSE 0 END), 0), 1)                 AS pct_cumplimiento_sla
FROM usuarios u
JOIN tickets t ON t.admin_id = u.id
LEFT JOIN asuntos          a  ON a.id  = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
-- Primera respuesta: unión a subconsulta agrupada (evita N+1)
LEFT JOIN (
    SELECT h.ticket_id,
           TIMESTAMPDIFF(MINUTE,
               tk.created_at,
               MIN(h.created_at)) AS minutos_primera_respuesta
    FROM   historial_estados h
    JOIN   tickets tk ON tk.id = h.ticket_id
    WHERE  h.estado_nuevo_id <> 1     -- cualquier estado distinto a "En espera"
    GROUP  BY h.ticket_id, tk.created_at
) pr ON pr.ticket_id = t.id
WHERE u.rol_id = 2                    -- solo administradores
GROUP BY u.id, u.nombre
ORDER BY pct_cumplimiento_sla DESC;

-- ============================================================
-- ██████  NUEVAS VISTAS DE DASHBOARD  ██████
-- ============================================================

-- ============================================================
-- v_dash_resumen_global
--  KPIs de un vistazo para la tarjeta superior del dashboard.
--  Una sola fila con los totales actuales.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_resumen_global` AS
SELECT
    COUNT(*)                                                         AS total_tickets,
    SUM(CASE WHEN estado_id = 1  THEN 1 ELSE 0 END)                 AS en_espera,
    SUM(CASE WHEN estado_id = 2  THEN 1 ELSE 0 END)                 AS en_proceso,
    SUM(CASE WHEN estado_id = 3  THEN 1 ELSE 0 END)                 AS esperando_respuesta,
    SUM(CASE WHEN estado_id IN (4,5,6) THEN 1 ELSE 0 END)           AS otros_activos,
    SUM(CASE WHEN estado_id = 8  THEN 1 ELSE 0 END)                 AS cerrados,
    SUM(CASE WHEN estado_id = 9  THEN 1 ELSE 0 END)                 AS rechazados,
    SUM(CASE WHEN estado_id NOT IN (8,9) THEN 1 ELSE 0 END)         AS activos,
    SUM(CASE WHEN prioridad_id = 4 AND estado_id NOT IN (8,9)
             THEN 1 ELSE 0 END)                                      AS criticos_activos,
    SUM(CASE WHEN prioridad_id = 3 AND estado_id NOT IN (8,9)
             THEN 1 ELSE 0 END)                                      AS alta_prioridad_activos,
    SUM(CASE WHEN admin_id IS NULL AND estado_id NOT IN (8,9)
             THEN 1 ELSE 0 END)                                      AS sin_asignar,
    SUM(CASE WHEN genera_gasto = 1 THEN 1 ELSE 0 END)               AS con_gasto
FROM tickets;

-- ============================================================
-- v_dash_tickets_por_dia
--  Tendencia de tickets creados y cerrados por día.
--  Útil para gráficas de línea o barras en el dashboard.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_tickets_por_dia` AS
SELECT
    DATE(created_at)                          AS fecha,
    COUNT(*)                                  AS tickets_creados,
    SUM(CASE WHEN estado_id = 8
              AND DATE(closed_at) = DATE(created_at)
             THEN 1 ELSE 0 END)               AS cerrados_mismo_dia
FROM tickets
GROUP BY DATE(created_at)
ORDER BY fecha DESC;

-- ============================================================
-- v_dash_creados_vs_cerrados
--  Creados vs cerrados por día (incluyendo días donde solo
--  se cierran tickets de días anteriores).
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_creados_vs_cerrados` AS
SELECT
    fecha,
    SUM(creados)  AS tickets_creados,
    SUM(cerrados) AS tickets_cerrados
FROM (
    SELECT DATE(created_at) AS fecha, COUNT(*) AS creados, 0 AS cerrados
    FROM   tickets
    GROUP  BY DATE(created_at)
    UNION ALL
    SELECT DATE(closed_at)  AS fecha, 0 AS creados, COUNT(*) AS cerrados
    FROM   tickets
    WHERE  closed_at IS NOT NULL
    GROUP  BY DATE(closed_at)
) t
GROUP BY fecha
ORDER BY fecha DESC;

-- ============================================================
-- v_dash_por_estado
--  Conteo y porcentaje de tickets por estado.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_por_estado` AS
SELECT
    e.id                                                             AS estado_id,
    e.nombre                                                         AS estado,
    e.es_terminal,
    e.permite_reabrir,
    COUNT(t.id)                                                      AS total,
    ROUND(COUNT(t.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM tickets), 0), 1)
                                                                     AS pct_del_total
FROM estados e
LEFT JOIN tickets t ON t.estado_id = e.id
GROUP BY e.id, e.nombre, e.es_terminal, e.permite_reabrir
ORDER BY e.id;

-- ============================================================
-- v_dash_por_prioridad
--  Distribución de tickets activos por prioridad.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_por_prioridad` AS
SELECT
    p.id                                                             AS prioridad_id,
    p.nombre                                                         AS prioridad,
    p.orden,
    COUNT(t.id)                                                      AS total,
    SUM(CASE WHEN t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)       AS activos,
    SUM(CASE WHEN t.estado_id = 8          THEN 1 ELSE 0 END)       AS cerrados,
    ROUND(COUNT(t.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM tickets), 0), 1)
                                                                     AS pct_del_total
FROM prioridades p
LEFT JOIN tickets t ON t.prioridad_id = p.id
GROUP BY p.id, p.nombre, p.orden
ORDER BY p.orden;

-- ============================================================
-- v_dash_por_sitio
--  Carga de tickets por sitio/sede — ideal para mapas o barras.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_por_sitio` AS
SELECT
    s.id                                                             AS sitio_id,
    r.nombre                                                         AS region,
    s.nombre                                                         AS sitio,
    COUNT(t.id)                                                      AS total_tickets,
    SUM(CASE WHEN t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)       AS activos,
    SUM(CASE WHEN t.estado_id = 8          THEN 1 ELSE 0 END)       AS cerrados,
    SUM(CASE WHEN t.prioridad_id = 4       THEN 1 ELSE 0 END)       AS criticos
FROM sitios s
JOIN regiones r ON r.id = s.region_id
LEFT JOIN tickets t ON t.sitio_id = s.id
GROUP BY s.id, r.nombre, s.nombre
ORDER BY activos DESC, total_tickets DESC;

-- ============================================================
-- v_dash_por_linea_negocio
--  Carga de tickets por línea de negocio.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_por_linea_negocio` AS
SELECT
    ln.id                                                            AS linea_id,
    ln.nombre                                                        AS linea_negocio,
    COUNT(t.id)                                                      AS total_tickets,
    SUM(CASE WHEN t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)       AS activos,
    SUM(CASE WHEN t.estado_id = 8          THEN 1 ELSE 0 END)       AS cerrados,
    SUM(CASE WHEN t.prioridad_id IN (3,4)
              AND t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)       AS alta_prioridad_activos
FROM lineas_negocio ln
LEFT JOIN tickets t ON t.linea_negocio_id = ln.id
WHERE ln.activo = 1
GROUP BY ln.id, ln.nombre
ORDER BY activos DESC;

-- ============================================================
-- v_dash_por_tipo_asunto
--  Distribución por categoría de asunto (hardware, software, etc.)
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_por_tipo_asunto` AS
SELECT
    ta.id                                                            AS tipo_asunto_id,
    ta.nombre                                                        AS tipo_asunto,
    COUNT(t.id)                                                      AS total_tickets,
    SUM(CASE WHEN t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)       AS activos,
    SUM(CASE WHEN t.estado_id = 8          THEN 1 ELSE 0 END)       AS cerrados,
    ROUND(COUNT(t.id) * 100.0
        / NULLIF((SELECT COUNT(*) FROM tickets), 0), 1)              AS pct_del_total
FROM tipos_asunto ta
LEFT JOIN asuntos  a ON a.tipo_asunto_id = ta.id
LEFT JOIN tickets  t ON t.asunto_id = a.id
GROUP BY ta.id, ta.nombre
ORDER BY total_tickets DESC;

-- ============================================================
-- v_dash_sla_vencidos
--  Tickets activos con SLA vencido — para alertas críticas.
-- ============================================================
CREATE OR REPLACE VIEW `v_dash_sla_vencidos` AS
SELECT
    t.id                                                             AS ticket_id,
    t.numero_ticket,
    t.descripcion,
    e.nombre                                                         AS estado,
    p.nombre                                                         AS prioridad,
    u.nombre                                                         AS usuario,
    adm.nombre                                                       AS admin_asignado,
    s.nombre                                                         AS sitio,
    COALESCE(a.horas_resolucion, sc.horas_resolucion)                AS sla_horas,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60, 1)        AS horas_transcurridas,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60
        - COALESCE(a.horas_resolucion, sc.horas_resolucion), 1)      AS horas_vencido,
    t.created_at                                                     AS fecha_creacion
FROM tickets t
JOIN estados    e   ON e.id  = t.estado_id
JOIN prioridades p  ON p.id  = t.prioridad_id
JOIN usuarios   u   ON u.id  = t.usuario_id
LEFT JOIN usuarios        adm ON adm.id = t.admin_id
LEFT JOIN sitios          s   ON s.id   = t.sitio_id
LEFT JOIN asuntos         a   ON a.id   = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
WHERE t.estado_id NOT IN (8, 9)
  AND COALESCE(a.horas_resolucion, sc.horas_resolucion) IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, t.created_at, NOW())
       > (COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60)
ORDER BY horas_vencido DESC;

-- ============================================================
-- FIN DE VISTAS v3
-- ============================================================
