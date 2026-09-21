-- Migración: Agregar tabla lugares_incidencia y columna FK en tickets
-- Ejecutar en MySQL Workbench

-- 1. Crear tabla de lugares de incidencia
CREATE TABLE IF NOT EXISTS `lugares_incidencia` (
  `id`   smallint      NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lugar_incidencia_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar opciones
INSERT INTO `lugares_incidencia` (`nombre`) VALUES
  ('Oficina'),
  ('Home office'),
  ('Campo');

-- 3. Eliminar columna VARCHAR anterior si existe
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = 'bd_tickets' AND TABLE_NAME = 'tickets'
               AND COLUMN_NAME = 'lugar_incidencia');
SET @sql := IF(@exist > 0,
  'ALTER TABLE tickets DROP COLUMN lugar_incidencia',
  'SELECT ''columna lugar_incidencia no existe, se crea nueva''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Agregar columna FK en tickets
ALTER TABLE tickets
  ADD COLUMN lugar_incidencia_id smallint DEFAULT NULL AFTER tipo_solicitud_id,
  ADD CONSTRAINT fk_tickets_lugar_incidencia
    FOREIGN KEY (lugar_incidencia_id) REFERENCES lugares_incidencia (id);
