-- Migración: Nuevo rol superadmin (id=3)
-- Ejecutar en la base de datos bd_tickets

INSERT IGNORE INTO roles (id, nombre) VALUES (3, 'superadmin');

-- Para crear un usuario superadmin, usa el panel de administración
-- o ejecuta el script PHP: php database/crear_superadmin.php