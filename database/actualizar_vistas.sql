USE bd_tickets;

-- ============================================================
-- v_promedio_por_estado — añade flags es_terminal / permite_reabrir
-- ============================================================
CREATE OR REPLACE VIEW v_promedio_por_estado AS
SELECT
    v.estado_id,
    v.estado,
    e.es_terminal,
    e.permite_reabrir,
    COUNT(*)                                    AS total_registros,
    ROUND(AVG(v.minutos_en_estado), 1)          AS promedio_minutos,
    ROUND(AVG(v.minutos_en_estado) / 60.0, 2)  AS promedio_horas,
    MAX(v.minutos_en_estado)                    AS max_minutos,
    MIN(v.minutos_en_estado)                    AS min_minutos
FROM v_tiempos_estado_ticket v
JOIN estados e ON e.id = v.estado_id
GROUP BY v.estado_id, v.estado, e.es_terminal, e.permite_reabrir
ORDER BY promedio_minutos DESC;

-- ============================================================
-- v_promedio_por_asunto — añade SLA comprometido y % cumplimiento
-- ============================================================
CREATE OR REPLACE VIEW v_promedio_por_asunto AS
SELECT
    a.id                                                                         AS asunto_id,
    a.nombre                                                                     AS asunto,
    ta.nombre                                                                    AS tipo_asunto,
    p.nombre                                                                     AS prioridad_default,
    a.horas_respuesta                                                            AS sla_respuesta_h,
    a.horas_resolucion                                                           AS sla_resolucion_h,
    COUNT(DISTINCT t.id)                                                         AS total_tickets,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)) / 60.0, 2)      AS promedio_horas_resolucion,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)), 1)             AS promedio_minutos_resolucion,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                  <= a.horas_resolucion * 60 THEN 1 ELSE 0 END)                 AS dentro_sla,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                  > a.horas_resolucion * 60 THEN 1 ELSE 0 END)                  AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                              <= a.horas_resolucion * 60 THEN 1 ELSE 0 END)
        / NULLIF(COUNT(DISTINCT t.id), 0)
    , 1)                                                                         AS pct_cumplimiento_sla
FROM tickets t
JOIN asuntos a       ON a.id  = t.asunto_id
JOIN tipos_asunto ta ON ta.id = a.tipo_asunto_id
JOIN prioridades p   ON p.id  = a.prioridad_default_id
WHERE t.closed_at IS NOT NULL
GROUP BY a.id, a.nombre, ta.nombre, p.nombre, a.horas_respuesta, a.horas_resolucion
ORDER BY total_tickets DESC;

-- ============================================================
-- v_promedio_por_prioridad — añade SLA referencia y % cumplimiento
-- ============================================================
CREATE OR REPLACE VIEW v_promedio_por_prioridad AS
SELECT
    p.id                                                                         AS prioridad_id,
    p.nombre                                                                     AS prioridad,
    sc.horas_respuesta                                                           AS sla_respuesta_h,
    sc.horas_resolucion                                                          AS sla_resolucion_h,
    COUNT(DISTINCT t.id)                                                         AS total_tickets,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)) / 60.0, 2)      AS promedio_horas_resolucion,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)), 1)             AS promedio_minutos_resolucion,
    ROUND(AVG((
        SELECT TIMESTAMPDIFF(MINUTE, t.created_at, MIN(h.created_at))
        FROM historial_estados h
        WHERE h.ticket_id = t.id AND h.estado_nuevo_id <> 1
    )) / 60.0, 2)                                                                AS promedio_horas_primera_respuesta,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                  <= COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
             THEN 1 ELSE 0 END)                                                  AS dentro_sla,
    SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                  > COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
             THEN 1 ELSE 0 END)                                                  AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                              <= COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
                         THEN 1 ELSE 0 END)
        / NULLIF(COUNT(DISTINCT t.id), 0)
    , 1)                                                                         AS pct_cumplimiento_sla
FROM tickets t
JOIN prioridades p ON p.id = t.prioridad_id
LEFT JOIN asuntos a ON a.id = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = p.id
WHERE t.closed_at IS NOT NULL
GROUP BY p.id, p.nombre, p.orden, sc.horas_respuesta, sc.horas_resolucion
ORDER BY p.orden;

-- ============================================================
-- v_tickets_pendientes — añade horas restantes y % SLA consumido
-- ============================================================
CREATE OR REPLACE VIEW v_tickets_pendientes AS
SELECT
    t.id                                                                         AS ticket_id,
    t.numero_ticket,
    t.descripcion,
    e.nombre                                                                     AS estado_actual,
    p.nombre                                                                     AS prioridad,
    u.nombre                                                                     AS usuario,
    adm.nombre                                                                   AS admin_asignado,
    s.nombre                                                                     AS sitio,
    ln.nombre                                                                    AS linea_negocio,
    a.nombre                                                                     AS asunto,
    t.genera_gasto,
    ultimo.created_at                                                            AS desde_estado_actual,
    TIMESTAMPDIFF(MINUTE, ultimo.created_at, NOW())                              AS minutos_en_estado_actual,
    ROUND(TIMESTAMPDIFF(MINUTE, ultimo.created_at, NOW()) / 60.0, 1)            AS horas_en_estado_actual,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60.0, 1)                 AS horas_desde_creacion,
    COALESCE(a.horas_resolucion, sc.horas_resolucion)                           AS sla_horas_limite,
    COALESCE(a.horas_respuesta,  sc.horas_respuesta)                            AS sla_horas_respuesta,
    ROUND(
        COALESCE(a.horas_resolucion, sc.horas_resolucion)
        - TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60.0
    , 1)                                                                         AS sla_horas_restantes,
    ROUND(
        TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) * 100.0
        / NULLIF(COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60, 0)
    , 1)                                                                         AS pct_sla_consumido,
    CASE
        WHEN COALESCE(a.horas_resolucion, sc.horas_resolucion) IS NULL          THEN 'sin_sla'
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW())
             > COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60           THEN 'vencido'
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW())
             > COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60 * 0.8    THEN 'en_riesgo'
        ELSE 'ok'
    END                                                                          AS sla_estado
FROM tickets t
JOIN  estados      e   ON e.id   = t.estado_id
JOIN  prioridades  p   ON p.id   = t.prioridad_id
JOIN  usuarios     u   ON u.id   = t.usuario_id
LEFT JOIN usuarios adm ON adm.id = t.admin_id
LEFT JOIN sitios   s   ON s.id   = t.sitio_id
LEFT JOIN lineas_negocio ln ON ln.id = t.linea_negocio_id
LEFT JOIN asuntos  a   ON a.id   = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
JOIN (
    SELECT ticket_id, MAX(created_at) AS created_at
    FROM historial_estados GROUP BY ticket_id
) ultimo ON ultimo.ticket_id = t.id
WHERE t.estado_id NOT IN (8, 9)
ORDER BY sla_horas_restantes ASC;

-- ============================================================
-- v_metricas_admin — cumplimiento SLA, activos, primera respuesta
-- ============================================================
CREATE OR REPLACE VIEW v_metricas_admin AS
SELECT
    u.id                                                                         AS admin_id,
    u.nombre                                                                     AS admin,
    COUNT(DISTINCT t.id)                                                         AS total_asignados,
    SUM(CASE WHEN t.estado_id NOT IN (8,9) THEN 1 ELSE 0 END)                  AS activos,
    SUM(CASE WHEN t.estado_id = 8 THEN 1 ELSE 0 END)                           AS cerrados,
    SUM(CASE WHEN t.estado_id = 9 THEN 1 ELSE 0 END)                           AS rechazados,
    ROUND(AVG(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                   THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) END
    ) / 60.0, 1)                                                                 AS promedio_horas_resolucion,
    ROUND(AVG((
        SELECT TIMESTAMPDIFF(MINUTE, t.created_at, MIN(h.created_at))
        FROM historial_estados h
        WHERE h.ticket_id = t.id AND h.estado_nuevo_id <> 1
    )) / 60.0, 1)                                                                AS promedio_horas_primera_respuesta,
    SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                  AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                      <= COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
             THEN 1 ELSE 0 END)                                                  AS dentro_sla,
    SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                  AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                      > COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
             THEN 1 ELSE 0 END)                                                  AS fuera_sla,
    ROUND(
        100.0 * SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                              AND TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
                                  <= COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
                         THEN 1 ELSE 0 END)
        / NULLIF(SUM(CASE WHEN t.estado_id = 8 AND t.closed_at IS NOT NULL
                          THEN 1 ELSE 0 END), 0)
    , 1)                                                                         AS pct_cumplimiento_sla
FROM usuarios u
JOIN tickets t ON t.admin_id = u.id
LEFT JOIN asuntos a ON a.id = t.asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
WHERE u.rol_id = 2
GROUP BY u.id, u.nombre
ORDER BY pct_cumplimiento_sla DESC;

-- ============================================================
-- v_sla_cumplimiento (NUEVA) — detalle de cumplimiento por ticket
-- ============================================================
CREATE OR REPLACE VIEW v_sla_cumplimiento AS
SELECT
    t.id                                                                         AS ticket_id,
    t.numero_ticket,
    t.created_at                                                                 AS fecha_creacion,
    t.closed_at                                                                  AS fecha_cierre,
    u.nombre                                                                     AS usuario,
    adm.nombre                                                                   AS admin,
    p.nombre                                                                     AS prioridad,
    ta.nombre                                                                    AS tipo_asunto,
    a.nombre                                                                     AS asunto,
    COALESCE(a.horas_resolucion, sc.horas_resolucion)                           AS sla_comprometido_h,
    ROUND(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) / 60.0, 2)          AS horas_reales,
    ROUND(
        TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) / 60.0
        - COALESCE(a.horas_resolucion, sc.horas_resolucion)
    , 2)                                                                         AS desviacion_h,
    CASE
        WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)
             <= COALESCE(a.horas_resolucion, sc.horas_resolucion) * 60
        THEN 'dentro_sla'
        ELSE 'fuera_sla'
    END                                                                          AS resultado_sla,
    s.nombre                                                                     AS sitio,
    ln.nombre                                                                    AS linea_negocio
FROM tickets t
JOIN  usuarios     u   ON u.id   = t.usuario_id
LEFT JOIN usuarios adm ON adm.id = t.admin_id
JOIN  prioridades  p   ON p.id   = t.prioridad_id
LEFT JOIN asuntos  a   ON a.id   = t.asunto_id
LEFT JOIN tipos_asunto ta ON ta.id = a.tipo_asunto_id
LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id
LEFT JOIN sitios   s   ON s.id   = t.sitio_id
LEFT JOIN lineas_negocio ln ON ln.id = t.linea_negocio_id
WHERE t.estado_id = 8
  AND t.closed_at IS NOT NULL
  AND COALESCE(a.horas_resolucion, sc.horas_resolucion) IS NOT NULL
ORDER BY t.closed_at DESC;
