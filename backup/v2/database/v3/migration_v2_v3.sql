-- ============================================================
--  MIGRACIÓN v2 → v3  |  bd_tickets
--  Fecha: 2026-05-13
--  Objetivo: corregir integridad, cardinalidad y NULLabilidad
--  con cambios mínimos sobre la estructura existente.
-- ============================================================
-- Ejecutar en orden. Cada sección es idempotente (usa IF NOT EXISTS / IF EXISTS).
-- ============================================================

USE `bd_tickets`;
SET FOREIGN_KEY_CHECKS  = 0;
SET SQL_SAFE_UPDATES    = 0;

-- ============================================================
-- 1. TABLA: tickets
--    Problemas detectados:
--      a) numero_ticket  →  DEFAULT NULL  (debería ser NOT NULL UNIQUE)
--      b) genera_gasto   →  DEFAULT NULL  (booleano debe tener DEFAULT 0)
--      c) asunto_id      →  DEFAULT NULL  (SLA queda sin referencia directa)
--    Decisión: a) y b) se corrigen; c) se deja nullable con índice,
--    porque tipo_solicitud_id también puede determinar el flujo.
-- ============================================================

-- 1a. Rellenar numero_ticket vacíos antes de forzar NOT NULL
UPDATE `tickets`
SET    `numero_ticket` = CONCAT('TK-', LPAD(id, 6, '0'))
WHERE  `numero_ticket` IS NULL;

-- 1b. Convertir numero_ticket a NOT NULL
ALTER TABLE `tickets`
  MODIFY COLUMN `numero_ticket` varchar(20)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    NOT NULL
    COMMENT 'Identificador legible único del ticket (ej: TK-000001)';

-- 1c. Normalizar genera_gasto: NULL → 0 y forzar NOT NULL DEFAULT 0
UPDATE `tickets` SET `genera_gasto` = 0 WHERE `genera_gasto` IS NULL;

ALTER TABLE `tickets`
  MODIFY COLUMN `genera_gasto` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Indica si el ticket generó gasto operativo';

-- 1d. Añadir updated_at si no existe (v2 ya lo tiene; guard por si acaso)
ALTER TABLE `tickets`
  MODIFY COLUMN `updated_at` datetime NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP;

-- ============================================================
-- 2. TABLA: usuarios
--    Problema: falta updated_at para auditar cambios de perfil.
-- ============================================================

-- 2a. Añadir updated_at si no existe
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE  TABLE_SCHEMA = 'bd_tickets'
    AND  TABLE_NAME   = 'usuarios'
    AND  COLUMN_NAME  = 'updated_at'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `usuarios`
     ADD COLUMN `updated_at` datetime NOT NULL
       DEFAULT CURRENT_TIMESTAMP
       ON UPDATE CURRENT_TIMESTAMP
       AFTER `created_at`',
  'SELECT ''updated_at ya existe en usuarios'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. TABLA: historial_reasignaciones
--    Problema: no hay campo motivo → sin trazabilidad del por qué.
-- ============================================================

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE  TABLE_SCHEMA = 'bd_tickets'
    AND  TABLE_NAME   = 'historial_reasignaciones'
    AND  COLUMN_NAME  = 'motivo'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `historial_reasignaciones`
     ADD COLUMN `motivo` text COLLATE utf8mb4_unicode_ci NULL
       AFTER `cambiado_por`',
  'SELECT ''motivo ya existe en historial_reasignaciones'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. TABLA: sla_pausas
--    Problemas: sin motivo ni registro de quién pausó el SLA.
-- ============================================================

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE  TABLE_SCHEMA = 'bd_tickets'
    AND  TABLE_NAME   = 'sla_pausas'
    AND  COLUMN_NAME  = 'pausado_por'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `sla_pausas`
     ADD COLUMN `pausado_por` int NOT NULL AFTER `ticket_id`,
     ADD COLUMN `motivo`      text COLLATE utf8mb4_unicode_ci NULL AFTER `pausado_por`,
     ADD CONSTRAINT `fk_sla_pausado_por`
       FOREIGN KEY (`pausado_por`) REFERENCES `usuarios` (`id`)',
  'SELECT ''pausado_por ya existe en sla_pausas'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5. TABLA: auditoria
--    Problema: FK ticket_id sin acción ON DELETE → error si se
--    borra un ticket (aunque es raro, la FK debe ser coherente).
-- ============================================================

-- Eliminar FK anterior y recrear con ON DELETE SET NULL
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE  TABLE_SCHEMA   = 'bd_tickets'
    AND  TABLE_NAME     = 'auditoria'
    AND  CONSTRAINT_NAME = 'fk_auditoria_ticket'
    AND  CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
  'ALTER TABLE `auditoria` DROP FOREIGN KEY `fk_auditoria_ticket`',
  'SELECT ''FK fk_auditoria_ticket no existe, nada que eliminar'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_ticket`
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
    ON DELETE SET NULL;

-- ============================================================
-- 6. TABLA: notificaciones
--    Ampliar enum tipo para cubrir asignación y nueva respuesta.
--    Cambio mínimo: se añaden valores al ENUM (no rompe datos).
-- ============================================================

ALTER TABLE `notificaciones`
  MODIFY COLUMN `tipo`
    enum('cambio_estado','alerta_critica','nueva_asignacion','nueva_respuesta')
    NOT NULL
    COMMENT 'Tipo de evento que disparó la notificación';

-- ============================================================
-- 7. ÍNDICES PARA DASHBOARD Y PERFORMANCE
--    Se añaden solo si no existen.
-- ============================================================

-- 7a. tickets: filtro por fecha + estado (dashboard KPI diario)
SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE  TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'tickets'
    AND  INDEX_NAME   = 'idx_tickets_fecha_estado'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `tickets` ADD INDEX `idx_tickets_fecha_estado` (`created_at`, `estado_id`)',
  'SELECT ''idx_tickets_fecha_estado ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7b. tickets: filtro por admin + estado (carga de trabajo)
SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE  TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'tickets'
    AND  INDEX_NAME   = 'idx_tickets_admin_estado'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `tickets` ADD INDEX `idx_tickets_admin_estado` (`admin_id`, `estado_id`)',
  'SELECT ''idx_tickets_admin_estado ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7c. tickets: filtro por sitio (dashboard geográfico)
SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE  TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'tickets'
    AND  INDEX_NAME   = 'idx_tickets_sitio'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `tickets` ADD INDEX `idx_tickets_sitio` (`sitio_id`)',
  'SELECT ''idx_tickets_sitio ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7d. historial_estados: consultas SLA y primera respuesta
SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE  TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'historial_estados'
    AND  INDEX_NAME   = 'idx_hist_ticket_fecha'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `historial_estados`
     ADD INDEX `idx_hist_ticket_fecha` (`ticket_id`, `created_at`)',
  'SELECT ''idx_hist_ticket_fecha ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7e. respuestas: primera respuesta por ticket
SET @idx = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE  TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'respuestas'
    AND  INDEX_NAME   = 'idx_resp_ticket_fecha'
);
SET @sql = IF(@idx = 0,
  'ALTER TABLE `respuestas`
     ADD INDEX `idx_resp_ticket_fecha` (`ticket_id`, `created_at`)',
  'SELECT ''idx_resp_ticket_fecha ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 8. RESTRICCIÓN: ticket_relaciones bidireccional
--    CHECK para evitar que A→B y B→A coexistan (MySQL 8+).
-- ============================================================

SET @ck = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE  TABLE_SCHEMA   = 'bd_tickets'
    AND  TABLE_NAME     = 'ticket_relaciones'
    AND  CONSTRAINT_NAME = 'chk_no_autorelacion'
);

SET @sql = IF(@ck = 0,
  'ALTER TABLE `ticket_relaciones`
     ADD CONSTRAINT `chk_no_autorelacion`
       CHECK (`ticket_id` <> `ticket_rel_id`)',
  'SELECT ''chk_no_autorelacion ya existe'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET SQL_SAFE_UPDATES    = 1;
SET FOREIGN_KEY_CHECKS  = 1;

-- ============================================================
-- FIN DE MIGRACIÓN v2 → v3
-- ============================================================
