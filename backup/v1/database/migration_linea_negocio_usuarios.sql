-- Migración: Asignar linea_negocio_id a usuarios según su línea más frecuente en tickets
-- Ejecutar en la base de datos bd_tickets

USE `bd_tickets`;

UPDATE `usuarios` u
  JOIN (
    SELECT `usuario_id`, `linea_negocio_id`
    FROM (
      SELECT
        t.`usuario_id`,
        t.`linea_negocio_id`,
        ROW_NUMBER() OVER (
          PARTITION BY t.`usuario_id`
          ORDER BY COUNT(*) DESC, t.`linea_negocio_id` ASC
        ) AS rn
      FROM `tickets` t
      WHERE t.`linea_negocio_id` IS NOT NULL
      GROUP BY t.`usuario_id`, t.`linea_negocio_id`
    ) AS `ranked`
    WHERE `rn` = 1
  ) AS `best` ON `best`.`usuario_id` = u.`id`
  SET u.`linea_negocio_id` = `best`.`linea_negocio_id`
  WHERE u.`linea_negocio_id` IS NULL;
