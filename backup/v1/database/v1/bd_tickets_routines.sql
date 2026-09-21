CREATE DATABASE  IF NOT EXISTS `bd_tickets` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `bd_tickets`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: bd_tickets
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary view structure for view `v_tiempos_estado_ticket`
--

DROP TABLE IF EXISTS `v_tiempos_estado_ticket`;
/*!50001 DROP VIEW IF EXISTS `v_tiempos_estado_ticket`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_tiempos_estado_ticket` AS SELECT 
 1 AS `ticket_id`,
 1 AS `estado_id`,
 1 AS `estado`,
 1 AS `entrada`,
 1 AS `salida`,
 1 AS `minutos_en_estado`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_promedio_por_asunto`
--

DROP TABLE IF EXISTS `v_promedio_por_asunto`;
/*!50001 DROP VIEW IF EXISTS `v_promedio_por_asunto`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_promedio_por_asunto` AS SELECT 
 1 AS `asunto_id`,
 1 AS `asunto`,
 1 AS `tipo_asunto`,
 1 AS `total_tickets`,
 1 AS `promedio_minutos_resolucion`,
 1 AS `promedio_horas_resolucion`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_metricas_admin`
--

DROP TABLE IF EXISTS `v_metricas_admin`;
/*!50001 DROP VIEW IF EXISTS `v_metricas_admin`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_metricas_admin` AS SELECT 
 1 AS `admin_id`,
 1 AS `admin`,
 1 AS `total_atendidos`,
 1 AS `cerrados`,
 1 AS `rechazados`,
 1 AS `promedio_horas_resolucion`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_promedio_por_estado`
--

DROP TABLE IF EXISTS `v_promedio_por_estado`;
/*!50001 DROP VIEW IF EXISTS `v_promedio_por_estado`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_promedio_por_estado` AS SELECT 
 1 AS `estado_id`,
 1 AS `estado`,
 1 AS `total_registros`,
 1 AS `promedio_minutos`,
 1 AS `promedio_horas`,
 1 AS `max_minutos`,
 1 AS `min_minutos`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_promedio_por_prioridad`
--

DROP TABLE IF EXISTS `v_promedio_por_prioridad`;
/*!50001 DROP VIEW IF EXISTS `v_promedio_por_prioridad`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_promedio_por_prioridad` AS SELECT 
 1 AS `prioridad_id`,
 1 AS `prioridad`,
 1 AS `total_tickets`,
 1 AS `promedio_minutos_resolucion`,
 1 AS `promedio_horas_resolucion`,
 1 AS `promedio_minutos_primera_respuesta`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_tickets_pendientes`
--

DROP TABLE IF EXISTS `v_tickets_pendientes`;
/*!50001 DROP VIEW IF EXISTS `v_tickets_pendientes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_tickets_pendientes` AS SELECT 
 1 AS `ticket_id`,
 1 AS `numero_ticket`,
 1 AS `descripcion`,
 1 AS `estado_actual`,
 1 AS `prioridad`,
 1 AS `usuario`,
 1 AS `admin_asignado`,
 1 AS `sitio`,
 1 AS `linea_negocio`,
 1 AS `asunto`,
 1 AS `genera_gasto`,
 1 AS `desde_estado_actual`,
 1 AS `minutos_en_estado_actual`,
 1 AS `horas_en_estado_actual`,
 1 AS `horas_desde_creacion`,
 1 AS `sla_horas_limite`,
 1 AS `sla_horas_respuesta`,
 1 AS `sla_estado`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_tiempos_estado_ticket`
--

/*!50001 DROP VIEW IF EXISTS `v_tiempos_estado_ticket`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tiempos_estado_ticket` AS select `base`.`ticket_id` AS `ticket_id`,`base`.`estado_nuevo_id` AS `estado_id`,`base`.`estado` AS `estado`,`base`.`entrada` AS `entrada`,`base`.`salida` AS `salida`,timestampdiff(MINUTE,`base`.`entrada`,coalesce(`base`.`salida`,now())) AS `minutos_en_estado` from (select `h`.`ticket_id` AS `ticket_id`,`h`.`estado_nuevo_id` AS `estado_nuevo_id`,`e`.`nombre` AS `estado`,`h`.`created_at` AS `entrada`,lead(`h`.`created_at`) OVER (PARTITION BY `h`.`ticket_id` ORDER BY `h`.`created_at` )  AS `salida` from (`historial_estados` `h` join `estados` `e` on((`e`.`id` = `h`.`estado_nuevo_id`)))) `base` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_promedio_por_asunto`
--

/*!50001 DROP VIEW IF EXISTS `v_promedio_por_asunto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_asunto` AS select `a`.`id` AS `asunto_id`,`a`.`nombre` AS `asunto`,`ta`.`nombre` AS `tipo_asunto`,count(distinct `t`.`id`) AS `total_tickets`,round(avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)),1) AS `promedio_minutos_resolucion`,round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)) / 60.0),2) AS `promedio_horas_resolucion` from ((`tickets` `t` join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) join `tipos_asunto` `ta` on((`ta`.`id` = `a`.`tipo_asunto_id`))) where (`t`.`closed_at` is not null) group by `a`.`id`,`a`.`nombre`,`ta`.`nombre` order by `promedio_minutos_resolucion` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_metricas_admin`
--

/*!50001 DROP VIEW IF EXISTS `v_metricas_admin`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_metricas_admin` AS select `u`.`id` AS `admin_id`,`u`.`nombre` AS `admin`,count(distinct `t`.`id`) AS `total_atendidos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,sum((case when (`t`.`estado_id` = 9) then 1 else 0 end)) AS `rechazados`,round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)) / 60.0),1) AS `promedio_horas_resolucion` from (`usuarios` `u` join `tickets` `t` on((`t`.`admin_id` = `u`.`id`))) where (`u`.`rol_id` = 2) group by `u`.`id`,`u`.`nombre` order by `cerrados` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_promedio_por_estado`
--

/*!50001 DROP VIEW IF EXISTS `v_promedio_por_estado`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_estado` AS select `v_tiempos_estado_ticket`.`estado_id` AS `estado_id`,`v_tiempos_estado_ticket`.`estado` AS `estado`,count(0) AS `total_registros`,round(avg(`v_tiempos_estado_ticket`.`minutos_en_estado`),1) AS `promedio_minutos`,round((avg(`v_tiempos_estado_ticket`.`minutos_en_estado`) / 60.0),2) AS `promedio_horas`,max(`v_tiempos_estado_ticket`.`minutos_en_estado`) AS `max_minutos`,min(`v_tiempos_estado_ticket`.`minutos_en_estado`) AS `min_minutos` from `v_tiempos_estado_ticket` group by `v_tiempos_estado_ticket`.`estado_id`,`v_tiempos_estado_ticket`.`estado` order by `promedio_minutos` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_promedio_por_prioridad`
--

/*!50001 DROP VIEW IF EXISTS `v_promedio_por_prioridad`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_prioridad` AS select `p`.`id` AS `prioridad_id`,`p`.`nombre` AS `prioridad`,count(distinct `t`.`id`) AS `total_tickets`,round(avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)),1) AS `promedio_minutos_resolucion`,round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)) / 60.0),2) AS `promedio_horas_resolucion`,round(avg((select timestampdiff(MINUTE,`t`.`created_at`,min(`h`.`created_at`)) from `historial_estados` `h` where ((`h`.`ticket_id` = `t`.`id`) and (`h`.`estado_nuevo_id` <> 1)))),1) AS `promedio_minutos_primera_respuesta` from (`tickets` `t` join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) where (`t`.`closed_at` is not null) group by `p`.`id`,`p`.`nombre`,`p`.`orden` order by `p`.`orden` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_tickets_pendientes`
--

/*!50001 DROP VIEW IF EXISTS `v_tickets_pendientes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`tabasco`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tickets_pendientes` AS select `t`.`id` AS `ticket_id`,`t`.`numero_ticket` AS `numero_ticket`,`t`.`descripcion` AS `descripcion`,`e`.`nombre` AS `estado_actual`,`p`.`nombre` AS `prioridad`,`u`.`nombre` AS `usuario`,`adm`.`nombre` AS `admin_asignado`,`s`.`nombre` AS `sitio`,`ln`.`nombre` AS `linea_negocio`,`a`.`nombre` AS `asunto`,`t`.`genera_gasto` AS `genera_gasto`,`ultimo`.`created_at` AS `desde_estado_actual`,timestampdiff(MINUTE,`ultimo`.`created_at`,now()) AS `minutos_en_estado_actual`,round((timestampdiff(MINUTE,`ultimo`.`created_at`,now()) / 60.0),1) AS `horas_en_estado_actual`,round((timestampdiff(MINUTE,`t`.`created_at`,now()) / 60.0),1) AS `horas_desde_creacion`,coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) AS `sla_horas_limite`,coalesce(`a`.`horas_respuesta`,`sc`.`horas_respuesta`) AS `sla_horas_respuesta`,(case when (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) is null) then 'sin_sla' when (timestampdiff(MINUTE,`t`.`created_at`,now()) > (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 'vencido' when (timestampdiff(MINUTE,`t`.`created_at`,now()) > ((coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60) * 0.8)) then 'en_riesgo' else 'ok' end) AS `sla_estado` from (((((((((`tickets` `t` join `estados` `e` on((`e`.`id` = `t`.`estado_id`))) join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) join `usuarios` `u` on((`u`.`id` = `t`.`usuario_id`))) left join `usuarios` `adm` on((`adm`.`id` = `t`.`admin_id`))) left join `sitios` `s` on((`s`.`id` = `t`.`sitio_id`))) left join `lineas_negocio` `ln` on((`ln`.`id` = `t`.`linea_negocio_id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `t`.`prioridad_id`))) join (select `historial_estados`.`ticket_id` AS `ticket_id`,max(`historial_estados`.`created_at`) AS `created_at` from `historial_estados` group by `historial_estados`.`ticket_id`) `ultimo` on((`ultimo`.`ticket_id` = `t`.`id`))) where (`t`.`estado_id` not in (8,9)) order by timestampdiff(MINUTE,`ultimo`.`created_at`,now()) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Dumping events for database 'bd_tickets'
--

--
-- Dumping routines for database 'bd_tickets'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-11 18:05:11
