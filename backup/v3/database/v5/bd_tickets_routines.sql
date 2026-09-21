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
 1 AS `prioridad_default`,
 1 AS `sla_respuesta_h`,
 1 AS `sla_resolucion_h`,
 1 AS `total_tickets`,
 1 AS `promedio_horas_resolucion`,
 1 AS `promedio_minutos_resolucion`,
 1 AS `dentro_sla`,
 1 AS `fuera_sla`,
 1 AS `pct_cumplimiento_sla`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_resumen_global`
--

DROP TABLE IF EXISTS `v_dash_resumen_global`;
/*!50001 DROP VIEW IF EXISTS `v_dash_resumen_global`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_resumen_global` AS SELECT 
 1 AS `total_tickets`,
 1 AS `en_espera`,
 1 AS `en_proceso`,
 1 AS `esperando_respuesta`,
 1 AS `otros_activos`,
 1 AS `cerrados`,
 1 AS `rechazados`,
 1 AS `activos`,
 1 AS `criticos_activos`,
 1 AS `alta_prioridad_activos`,
 1 AS `sin_asignar`,
 1 AS `con_gasto`*/;
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
 1 AS `sla_respuesta_h`,
 1 AS `sla_resolucion_h`,
 1 AS `total_tickets`,
 1 AS `promedio_horas_resolucion`,
 1 AS `promedio_minutos_resolucion`,
 1 AS `promedio_horas_primera_respuesta`,
 1 AS `dentro_sla`,
 1 AS `fuera_sla`,
 1 AS `pct_cumplimiento_sla`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_por_linea_negocio`
--

DROP TABLE IF EXISTS `v_dash_por_linea_negocio`;
/*!50001 DROP VIEW IF EXISTS `v_dash_por_linea_negocio`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_por_linea_negocio` AS SELECT 
 1 AS `linea_id`,
 1 AS `linea_negocio`,
 1 AS `total_tickets`,
 1 AS `activos`,
 1 AS `cerrados`,
 1 AS `alta_prioridad_activos`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_por_estado`
--

DROP TABLE IF EXISTS `v_dash_por_estado`;
/*!50001 DROP VIEW IF EXISTS `v_dash_por_estado`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_por_estado` AS SELECT 
 1 AS `estado_id`,
 1 AS `estado`,
 1 AS `es_terminal`,
 1 AS `permite_reabrir`,
 1 AS `total`,
 1 AS `pct_del_total`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_por_sitio`
--

DROP TABLE IF EXISTS `v_dash_por_sitio`;
/*!50001 DROP VIEW IF EXISTS `v_dash_por_sitio`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_por_sitio` AS SELECT 
 1 AS `sitio_id`,
 1 AS `region`,
 1 AS `sitio`,
 1 AS `total_tickets`,
 1 AS `activos`,
 1 AS `cerrados`,
 1 AS `criticos`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_tickets_por_dia`
--

DROP TABLE IF EXISTS `v_dash_tickets_por_dia`;
/*!50001 DROP VIEW IF EXISTS `v_dash_tickets_por_dia`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_tickets_por_dia` AS SELECT 
 1 AS `fecha`,
 1 AS `tickets_creados`,
 1 AS `cerrados_mismo_dia`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_por_tipo_asunto`
--

DROP TABLE IF EXISTS `v_dash_por_tipo_asunto`;
/*!50001 DROP VIEW IF EXISTS `v_dash_por_tipo_asunto`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_por_tipo_asunto` AS SELECT 
 1 AS `tipo_asunto_id`,
 1 AS `tipo_asunto`,
 1 AS `total_tickets`,
 1 AS `activos`,
 1 AS `cerrados`,
 1 AS `pct_del_total`*/;
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
 1 AS `total_asignados`,
 1 AS `activos`,
 1 AS `cerrados`,
 1 AS `rechazados`,
 1 AS `promedio_horas_resolucion`,
 1 AS `promedio_horas_primera_respuesta`,
 1 AS `dentro_sla`,
 1 AS `fuera_sla`,
 1 AS `pct_cumplimiento_sla`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_creados_vs_cerrados`
--

DROP TABLE IF EXISTS `v_dash_creados_vs_cerrados`;
/*!50001 DROP VIEW IF EXISTS `v_dash_creados_vs_cerrados`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_creados_vs_cerrados` AS SELECT 
 1 AS `fecha`,
 1 AS `tickets_creados`,
 1 AS `tickets_cerrados`*/;
SET character_set_client = @saved_cs_client;

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
 1 AS `tipo_solicitud`,
 1 AS `genera_gasto`,
 1 AS `desde_estado_actual`,
 1 AS `minutos_en_estado_actual`,
 1 AS `horas_en_estado_actual`,
 1 AS `horas_desde_creacion`,
 1 AS `sla_horas_limite`,
 1 AS `sla_horas_respuesta`,
 1 AS `sla_horas_restantes`,
 1 AS `pct_sla_consumido`,
 1 AS `sla_estado`*/;
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
 1 AS `es_terminal`,
 1 AS `permite_reabrir`,
 1 AS `total_registros`,
 1 AS `promedio_minutos`,
 1 AS `promedio_horas`,
 1 AS `max_minutos`,
 1 AS `min_minutos`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_sla_cumplimiento`
--

DROP TABLE IF EXISTS `v_sla_cumplimiento`;
/*!50001 DROP VIEW IF EXISTS `v_sla_cumplimiento`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_sla_cumplimiento` AS SELECT 
 1 AS `ticket_id`,
 1 AS `numero_ticket`,
 1 AS `fecha_creacion`,
 1 AS `fecha_cierre`,
 1 AS `usuario`,
 1 AS `admin`,
 1 AS `prioridad`,
 1 AS `tipo_asunto`,
 1 AS `asunto`,
 1 AS `tipo_solicitud`,
 1 AS `sla_comprometido_h`,
 1 AS `horas_reales`,
 1 AS `desviacion_h`,
 1 AS `resultado_sla`,
 1 AS `sitio`,
 1 AS `linea_negocio`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_por_prioridad`
--

DROP TABLE IF EXISTS `v_dash_por_prioridad`;
/*!50001 DROP VIEW IF EXISTS `v_dash_por_prioridad`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_por_prioridad` AS SELECT 
 1 AS `prioridad_id`,
 1 AS `prioridad`,
 1 AS `orden`,
 1 AS `total`,
 1 AS `activos`,
 1 AS `cerrados`,
 1 AS `pct_del_total`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_dash_sla_vencidos`
--

DROP TABLE IF EXISTS `v_dash_sla_vencidos`;
/*!50001 DROP VIEW IF EXISTS `v_dash_sla_vencidos`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dash_sla_vencidos` AS SELECT 
 1 AS `ticket_id`,
 1 AS `numero_ticket`,
 1 AS `descripcion`,
 1 AS `estado`,
 1 AS `prioridad`,
 1 AS `usuario`,
 1 AS `admin_asignado`,
 1 AS `sitio`,
 1 AS `sla_horas`,
 1 AS `horas_transcurridas`,
 1 AS `horas_vencido`,
 1 AS `fecha_creacion`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_promedio_por_asunto`
--

/*!50001 DROP VIEW IF EXISTS `v_promedio_por_asunto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_asunto` AS select `a`.`id` AS `asunto_id`,`a`.`nombre` AS `asunto`,`ta`.`nombre` AS `tipo_asunto`,`p`.`nombre` AS `prioridad_default`,`a`.`horas_respuesta` AS `sla_respuesta_h`,`a`.`horas_resolucion` AS `sla_resolucion_h`,count(distinct `t`.`id`) AS `total_tickets`,round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)) / 60),2) AS `promedio_horas_resolucion`,round(avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)),1) AS `promedio_minutos_resolucion`,sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (`a`.`horas_resolucion` * 60)) then 1 else 0 end)) AS `dentro_sla`,sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) > (`a`.`horas_resolucion` * 60)) then 1 else 0 end)) AS `fuera_sla`,round(((100.0 * sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (`a`.`horas_resolucion` * 60)) then 1 else 0 end))) / nullif(count(distinct `t`.`id`),0)),1) AS `pct_cumplimiento_sla` from (((`tickets` `t` join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) join `tipos_asunto` `ta` on((`ta`.`id` = `a`.`tipo_asunto_id`))) join `prioridades` `p` on((`p`.`id` = `a`.`prioridad_default_id`))) where ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null)) group by `a`.`id`,`a`.`nombre`,`ta`.`nombre`,`p`.`nombre`,`a`.`horas_respuesta`,`a`.`horas_resolucion` order by `total_tickets` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_resumen_global`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_resumen_global`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_resumen_global` AS select count(0) AS `total_tickets`,sum((case when (`tickets`.`estado_id` = 1) then 1 else 0 end)) AS `en_espera`,sum((case when (`tickets`.`estado_id` = 2) then 1 else 0 end)) AS `en_proceso`,sum((case when (`tickets`.`estado_id` = 3) then 1 else 0 end)) AS `esperando_respuesta`,sum((case when (`tickets`.`estado_id` in (4,5,6)) then 1 else 0 end)) AS `otros_activos`,sum((case when (`tickets`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,sum((case when (`tickets`.`estado_id` = 9) then 1 else 0 end)) AS `rechazados`,sum((case when (`tickets`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when ((`tickets`.`prioridad_id` = 4) and (`tickets`.`estado_id` not in (8,9))) then 1 else 0 end)) AS `criticos_activos`,sum((case when ((`tickets`.`prioridad_id` = 3) and (`tickets`.`estado_id` not in (8,9))) then 1 else 0 end)) AS `alta_prioridad_activos`,sum((case when ((`tickets`.`admin_id` is null) and (`tickets`.`estado_id` not in (8,9))) then 1 else 0 end)) AS `sin_asignar`,sum((case when (`tickets`.`genera_gasto` = 1) then 1 else 0 end)) AS `con_gasto` from `tickets` */;
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
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_prioridad` AS select `p`.`id` AS `prioridad_id`,`p`.`nombre` AS `prioridad`,`sc`.`horas_respuesta` AS `sla_respuesta_h`,`sc`.`horas_resolucion` AS `sla_resolucion_h`,count(distinct `t`.`id`) AS `total_tickets`,round((avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)) / 60),2) AS `promedio_horas_resolucion`,round(avg(timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`)),1) AS `promedio_minutos_resolucion`,round((avg(`pr`.`minutos_primera_respuesta`) / 60),2) AS `promedio_horas_primera_respuesta`,sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 1 else 0 end)) AS `dentro_sla`,sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) > (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 1 else 0 end)) AS `fuera_sla`,round(((100.0 * sum((case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 1 else 0 end))) / nullif(count(distinct `t`.`id`),0)),1) AS `pct_cumplimiento_sla` from ((((`tickets` `t` join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `p`.`id`))) left join (select `h`.`ticket_id` AS `ticket_id`,timestampdiff(MINUTE,(select `tickets`.`created_at` from `tickets` where (`tickets`.`id` = `h`.`ticket_id`)),min(`h`.`created_at`)) AS `minutos_primera_respuesta` from `historial_estados` `h` where (`h`.`estado_nuevo_id` <> 1) group by `h`.`ticket_id`) `pr` on((`pr`.`ticket_id` = `t`.`id`))) where ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null)) group by `p`.`id`,`p`.`nombre`,`p`.`orden`,`sc`.`horas_respuesta`,`sc`.`horas_resolucion` order by `p`.`orden` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_por_linea_negocio`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_por_linea_negocio`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_por_linea_negocio` AS select `ln`.`id` AS `linea_id`,`ln`.`nombre` AS `linea_negocio`,count(`t`.`id`) AS `total_tickets`,sum((case when (`t`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,sum((case when ((`t`.`prioridad_id` in (3,4)) and (`t`.`estado_id` not in (8,9))) then 1 else 0 end)) AS `alta_prioridad_activos` from (`lineas_negocio` `ln` left join `tickets` `t` on((`t`.`linea_negocio_id` = `ln`.`id`))) where (`ln`.`activo` = 1) group by `ln`.`id`,`ln`.`nombre` order by `activos` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_por_estado`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_por_estado`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_por_estado` AS select `e`.`id` AS `estado_id`,`e`.`nombre` AS `estado`,`e`.`es_terminal` AS `es_terminal`,`e`.`permite_reabrir` AS `permite_reabrir`,count(`t`.`id`) AS `total`,round(((count(`t`.`id`) * 100.0) / nullif((select count(0) from `tickets`),0)),1) AS `pct_del_total` from (`estados` `e` left join `tickets` `t` on((`t`.`estado_id` = `e`.`id`))) group by `e`.`id`,`e`.`nombre`,`e`.`es_terminal`,`e`.`permite_reabrir` order by `e`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_por_sitio`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_por_sitio`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_por_sitio` AS select `s`.`id` AS `sitio_id`,`r`.`nombre` AS `region`,`s`.`nombre` AS `sitio`,count(`t`.`id`) AS `total_tickets`,sum((case when (`t`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,sum((case when (`t`.`prioridad_id` = 4) then 1 else 0 end)) AS `criticos` from ((`sitios` `s` join `regiones` `r` on((`r`.`id` = `s`.`region_id`))) left join `tickets` `t` on((`t`.`sitio_id` = `s`.`id`))) group by `s`.`id`,`r`.`nombre`,`s`.`nombre` order by `activos` desc,`total_tickets` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_tickets_por_dia`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_tickets_por_dia`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_tickets_por_dia` AS select cast(`tickets`.`created_at` as date) AS `fecha`,count(0) AS `tickets_creados`,sum((case when ((`tickets`.`estado_id` = 8) and (cast(`tickets`.`closed_at` as date) = cast(`tickets`.`created_at` as date))) then 1 else 0 end)) AS `cerrados_mismo_dia` from `tickets` group by cast(`tickets`.`created_at` as date) order by `fecha` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_por_tipo_asunto`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_por_tipo_asunto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_por_tipo_asunto` AS select `ta`.`id` AS `tipo_asunto_id`,`ta`.`nombre` AS `tipo_asunto`,count(`t`.`id`) AS `total_tickets`,sum((case when (`t`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,round(((count(`t`.`id`) * 100.0) / nullif((select count(0) from `tickets`),0)),1) AS `pct_del_total` from ((`tipos_asunto` `ta` left join `asuntos` `a` on((`a`.`tipo_asunto_id` = `ta`.`id`))) left join `tickets` `t` on((`t`.`asunto_id` = `a`.`id`))) group by `ta`.`id`,`ta`.`nombre` order by `total_tickets` desc */;
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
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_metricas_admin` AS select `u`.`id` AS `admin_id`,`u`.`nombre` AS `admin`,count(distinct `t`.`id`) AS `total_asignados`,sum((case when (`t`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,sum((case when (`t`.`estado_id` = 9) then 1 else 0 end)) AS `rechazados`,round((avg((case when ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null)) then timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) end)) / 60),1) AS `promedio_horas_resolucion`,round((avg(`pr`.`minutos_primera_respuesta`) / 60),1) AS `promedio_horas_primera_respuesta`,sum((case when ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null) and (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60))) then 1 else 0 end)) AS `dentro_sla`,sum((case when ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null) and (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) > (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60))) then 1 else 0 end)) AS `fuera_sla`,round(((100.0 * sum((case when ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null) and (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60))) then 1 else 0 end))) / nullif(sum((case when ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null)) then 1 else 0 end)),0)),1) AS `pct_cumplimiento_sla` from ((((`usuarios` `u` join `tickets` `t` on((`t`.`admin_id` = `u`.`id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `t`.`prioridad_id`))) left join (select `h`.`ticket_id` AS `ticket_id`,timestampdiff(MINUTE,`tk`.`created_at`,min(`h`.`created_at`)) AS `minutos_primera_respuesta` from (`historial_estados` `h` join `tickets` `tk` on((`tk`.`id` = `h`.`ticket_id`))) where (`h`.`estado_nuevo_id` <> 1) group by `h`.`ticket_id`,`tk`.`created_at`) `pr` on((`pr`.`ticket_id` = `t`.`id`))) where (`u`.`rol_id` = 2) group by `u`.`id`,`u`.`nombre` order by `pct_cumplimiento_sla` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_creados_vs_cerrados`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_creados_vs_cerrados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_creados_vs_cerrados` AS select `t`.`fecha` AS `fecha`,sum(`t`.`creados`) AS `tickets_creados`,sum(`t`.`cerrados`) AS `tickets_cerrados` from (select cast(`tickets`.`created_at` as date) AS `fecha`,count(0) AS `creados`,0 AS `cerrados` from `tickets` group by cast(`tickets`.`created_at` as date) union all select cast(`tickets`.`closed_at` as date) AS `fecha`,0 AS `creados`,count(0) AS `cerrados` from `tickets` where (`tickets`.`closed_at` is not null) group by cast(`tickets`.`closed_at` as date)) `t` group by `t`.`fecha` order by `t`.`fecha` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_tiempos_estado_ticket`
--

/*!50001 DROP VIEW IF EXISTS `v_tiempos_estado_ticket`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tiempos_estado_ticket` AS select `base`.`ticket_id` AS `ticket_id`,`base`.`estado_nuevo_id` AS `estado_id`,`base`.`estado` AS `estado`,`base`.`entrada` AS `entrada`,`base`.`salida` AS `salida`,timestampdiff(MINUTE,`base`.`entrada`,coalesce(`base`.`salida`,now())) AS `minutos_en_estado` from (select `h`.`ticket_id` AS `ticket_id`,`h`.`estado_nuevo_id` AS `estado_nuevo_id`,`e`.`nombre` AS `estado`,`h`.`created_at` AS `entrada`,lead(`h`.`created_at`) OVER (PARTITION BY `h`.`ticket_id` ORDER BY `h`.`created_at` )  AS `salida` from (`historial_estados` `h` join `estados` `e` on((`e`.`id` = `h`.`estado_nuevo_id`)))) `base` */;
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
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tickets_pendientes` AS select `t`.`id` AS `ticket_id`,`t`.`numero_ticket` AS `numero_ticket`,`t`.`descripcion` AS `descripcion`,`e`.`nombre` AS `estado_actual`,`p`.`nombre` AS `prioridad`,`u`.`nombre` AS `usuario`,`adm`.`nombre` AS `admin_asignado`,`s`.`nombre` AS `sitio`,`ln`.`nombre` AS `linea_negocio`,`a`.`nombre` AS `asunto`,`ts`.`nombre` AS `tipo_solicitud`,`t`.`genera_gasto` AS `genera_gasto`,coalesce(`ultimo`.`created_at`,`t`.`created_at`) AS `desde_estado_actual`,timestampdiff(MINUTE,coalesce(`ultimo`.`created_at`,`t`.`created_at`),now()) AS `minutos_en_estado_actual`,round((timestampdiff(MINUTE,coalesce(`ultimo`.`created_at`,`t`.`created_at`),now()) / 60),1) AS `horas_en_estado_actual`,round((timestampdiff(MINUTE,`t`.`created_at`,now()) / 60),1) AS `horas_desde_creacion`,coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) AS `sla_horas_limite`,coalesce(`a`.`horas_respuesta`,`sc`.`horas_respuesta`) AS `sla_horas_respuesta`,round((coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) - (timestampdiff(MINUTE,`t`.`created_at`,now()) / 60)),1) AS `sla_horas_restantes`,round(((timestampdiff(MINUTE,`t`.`created_at`,now()) * 100.0) / nullif((coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60),0)),1) AS `pct_sla_consumido`,(case when (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) is null) then 'sin_sla' when (timestampdiff(MINUTE,`t`.`created_at`,now()) > (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 'vencido' when (timestampdiff(MINUTE,`t`.`created_at`,now()) > ((coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60) * 0.8)) then 'en_riesgo' else 'ok' end) AS `sla_estado` from ((((((((((`tickets` `t` join `estados` `e` on((`e`.`id` = `t`.`estado_id`))) join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) join `usuarios` `u` on((`u`.`id` = `t`.`usuario_id`))) left join `usuarios` `adm` on((`adm`.`id` = `t`.`admin_id`))) left join `sitios` `s` on((`s`.`id` = `t`.`sitio_id`))) left join `lineas_negocio` `ln` on((`ln`.`id` = `t`.`linea_negocio_id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `tipos_solicitud` `ts` on((`ts`.`id` = `t`.`tipo_solicitud_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `t`.`prioridad_id`))) left join (select `historial_estados`.`ticket_id` AS `ticket_id`,max(`historial_estados`.`created_at`) AS `created_at` from `historial_estados` group by `historial_estados`.`ticket_id`) `ultimo` on((`ultimo`.`ticket_id` = `t`.`id`))) where (`t`.`estado_id` not in (8,9)) order by round((coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) - (timestampdiff(MINUTE,`t`.`created_at`,now()) / 60)),1) */;
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
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_promedio_por_estado` AS select `v`.`estado_id` AS `estado_id`,`v`.`estado` AS `estado`,`e`.`es_terminal` AS `es_terminal`,`e`.`permite_reabrir` AS `permite_reabrir`,count(0) AS `total_registros`,round(avg(`v`.`minutos_en_estado`),1) AS `promedio_minutos`,round((avg(`v`.`minutos_en_estado`) / 60),2) AS `promedio_horas`,max(`v`.`minutos_en_estado`) AS `max_minutos`,min(`v`.`minutos_en_estado`) AS `min_minutos` from (`v_tiempos_estado_ticket` `v` join `estados` `e` on((`e`.`id` = `v`.`estado_id`))) group by `v`.`estado_id`,`v`.`estado`,`e`.`es_terminal`,`e`.`permite_reabrir` order by `promedio_minutos` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_sla_cumplimiento`
--

/*!50001 DROP VIEW IF EXISTS `v_sla_cumplimiento`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_sla_cumplimiento` AS select `t`.`id` AS `ticket_id`,`t`.`numero_ticket` AS `numero_ticket`,`t`.`created_at` AS `fecha_creacion`,`t`.`closed_at` AS `fecha_cierre`,`u`.`nombre` AS `usuario`,`adm`.`nombre` AS `admin`,`p`.`nombre` AS `prioridad`,`ta`.`nombre` AS `tipo_asunto`,`a`.`nombre` AS `asunto`,`ts`.`nombre` AS `tipo_solicitud`,coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) AS `sla_comprometido_h`,round((timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) / 60),2) AS `horas_reales`,round(((timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) / 60) - coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`)),2) AS `desviacion_h`,(case when (timestampdiff(MINUTE,`t`.`created_at`,`t`.`closed_at`) <= (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60)) then 'dentro_sla' else 'fuera_sla' end) AS `resultado_sla`,`s`.`nombre` AS `sitio`,`ln`.`nombre` AS `linea_negocio` from (((((((((`tickets` `t` join `usuarios` `u` on((`u`.`id` = `t`.`usuario_id`))) left join `usuarios` `adm` on((`adm`.`id` = `t`.`admin_id`))) join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `tipos_asunto` `ta` on((`ta`.`id` = `a`.`tipo_asunto_id`))) left join `tipos_solicitud` `ts` on((`ts`.`id` = `t`.`tipo_solicitud_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `t`.`prioridad_id`))) left join `sitios` `s` on((`s`.`id` = `t`.`sitio_id`))) left join `lineas_negocio` `ln` on((`ln`.`id` = `t`.`linea_negocio_id`))) where ((`t`.`estado_id` = 8) and (`t`.`closed_at` is not null) and (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) is not null)) order by `t`.`closed_at` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_por_prioridad`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_por_prioridad`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_por_prioridad` AS select `p`.`id` AS `prioridad_id`,`p`.`nombre` AS `prioridad`,`p`.`orden` AS `orden`,count(`t`.`id`) AS `total`,sum((case when (`t`.`estado_id` not in (8,9)) then 1 else 0 end)) AS `activos`,sum((case when (`t`.`estado_id` = 8) then 1 else 0 end)) AS `cerrados`,round(((count(`t`.`id`) * 100.0) / nullif((select count(0) from `tickets`),0)),1) AS `pct_del_total` from (`prioridades` `p` left join `tickets` `t` on((`t`.`prioridad_id` = `p`.`id`))) group by `p`.`id`,`p`.`nombre`,`p`.`orden` order by `p`.`orden` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_dash_sla_vencidos`
--

/*!50001 DROP VIEW IF EXISTS `v_dash_sla_vencidos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dash_sla_vencidos` AS select `t`.`id` AS `ticket_id`,`t`.`numero_ticket` AS `numero_ticket`,`t`.`descripcion` AS `descripcion`,`e`.`nombre` AS `estado`,`p`.`nombre` AS `prioridad`,`u`.`nombre` AS `usuario`,`adm`.`nombre` AS `admin_asignado`,`s`.`nombre` AS `sitio`,coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) AS `sla_horas`,round((timestampdiff(MINUTE,`t`.`created_at`,now()) / 60),1) AS `horas_transcurridas`,round(((timestampdiff(MINUTE,`t`.`created_at`,now()) / 60) - coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`)),1) AS `horas_vencido`,`t`.`created_at` AS `fecha_creacion` from (((((((`tickets` `t` join `estados` `e` on((`e`.`id` = `t`.`estado_id`))) join `prioridades` `p` on((`p`.`id` = `t`.`prioridad_id`))) join `usuarios` `u` on((`u`.`id` = `t`.`usuario_id`))) left join `usuarios` `adm` on((`adm`.`id` = `t`.`admin_id`))) left join `sitios` `s` on((`s`.`id` = `t`.`sitio_id`))) left join `asuntos` `a` on((`a`.`id` = `t`.`asunto_id`))) left join `sla_configuracion` `sc` on((`sc`.`prioridad_id` = `t`.`prioridad_id`))) where ((`t`.`estado_id` not in (8,9)) and (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) is not null) and (timestampdiff(MINUTE,`t`.`created_at`,now()) > (coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`) * 60))) order by round(((timestampdiff(MINUTE,`t`.`created_at`,now()) / 60) - coalesce(`a`.`horas_resolucion`,`sc`.`horas_resolucion`)),1) desc */;
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

-- Dump completed on 2026-05-13 18:43:29
