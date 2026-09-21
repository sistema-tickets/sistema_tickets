-- Migración: Normalizar lineas_negocio con columnas y datos de tickets.lineas_de_negocio
-- Ejecutar en la base de datos bd_tickets
-- Requiere que la base 'tickets' con la tabla 'lineas_de_negocio' exista en el mismo servidor
-- Elimina líneas que no existían en la tabla original (Sede Cali y/o Popayán,
-- Sede Medellín y Eje Cafetero, Radio Bases Obra Civil (FO),
-- Operación Neiva Proyecto Tolhuca, Operación Ibagué Proyecto Tolhuca,
-- Aseguramiento de la Información)
-- Sede Cali Norte se conserva porque tiene tickets asociados

USE `bd_tickets`;

-- 1. Agregar columnas nuevas
ALTER TABLE `lineas_negocio`
  ADD COLUMN `correo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `activo`,
  ADD COLUMN `nombre_encargado` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `correo`,
  ADD COLUMN `apellidos_encargado` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `nombre_encargado`;

-- 2. Migrar datos desde tickets.lineas_de_negocio
UPDATE `lineas_negocio` n
  JOIN `tickets`.`lineas_de_negocio` v ON
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      LOWER(TRIM(REPLACE(REPLACE(n.nombre, '\n', ''), '\r', ''))),
    'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ñ','n')
    =
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      LOWER(v.cod_linea),
    'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ñ','n')
  SET
    n.correo           = v.correo,
    n.nombre_encargado = v.nombre_encargado,
    n.apellidos_encargado = v.apellidos_encargado;

-- 3. Eliminar líneas que no estaban en la tabla original (sin tickets asociados)
DELETE FROM `lineas_negocio` WHERE `id` IN (14, 17, 20, 24, 25, 28);
