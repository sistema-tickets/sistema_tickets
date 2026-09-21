-- Migración: limpiar sitios y corregir nombres
-- Ciudades a eliminar: Florencia, Ibagué, Medellín, Pereira, Espinal, San Andrés (corrupto)
-- IDs eliminados: 6, 7, 8, 9, 35, 37

USE bd_tickets;

-- 1. Insertar "San Andrés" (fue eliminado previamente)
INSERT IGNORE INTO sitios (id, region_id, nombre) VALUES (33, 3, 'San Andrés');

-- 2. Desasociar tickets que apunten a los sitios a eliminar (sitio_id → NULL)
UPDATE tickets
SET sitio_id = NULL
WHERE sitio_id IN (6, 7, 8, 9, 35, 37);

-- 3. Eliminar los sitios no válidos
DELETE FROM sitios WHERE id IN (6, 7, 8, 9, 35, 37);
