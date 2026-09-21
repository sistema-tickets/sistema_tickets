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
-- Table structure for table `asuntos`
--

DROP TABLE IF EXISTS `asuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asuntos` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `tipo_asunto_id` smallint NOT NULL,
  `prioridad_default_id` smallint NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `horas_respuesta` decimal(5,1) NOT NULL COMMENT 'SLA primera respuesta (horas)',
  `horas_resolucion` decimal(5,1) NOT NULL COMMENT 'SLA resoluci├│n total (horas)',
  PRIMARY KEY (`id`),
  KEY `fk_asuntos_tipo_asunto` (`tipo_asunto_id`),
  KEY `fk_asuntos_prioridad` (`prioridad_default_id`),
  CONSTRAINT `fk_asuntos_prioridad` FOREIGN KEY (`prioridad_default_id`) REFERENCES `prioridades` (`id`),
  CONSTRAINT `fk_asuntos_tipo_asunto` FOREIGN KEY (`tipo_asunto_id`) REFERENCES `tipos_asunto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asuntos`
--

LOCK TABLES `asuntos` WRITE;
/*!40000 ALTER TABLE `asuntos` DISABLE KEYS */;
INSERT INTO `asuntos` VALUES (1,1,1,'Compra de equipo de cómputo (Laptop y Desktop)',4.0,48.0),(2,1,2,'Requerimiento de equipo de cómputo',2.0,48.0),(3,1,2,'Cambio de laptop o equipo de escritorio',2.0,48.0),(4,1,2,'Reasignación de equipo',2.0,48.0),(5,1,2,'Problemas de hardware',2.0,48.0),(6,1,2,'Configuración de perfil o cuenta en equipo de cómputo',2.0,48.0),(7,2,2,'Mantenimiento correctivo de equipo de cómputo',2.0,48.0),(8,2,2,'Mantenimiento preventivo de equipo de cómputo',2.0,48.0),(9,2,2,'Mantenimiento detectivo de equipo de cómputo',2.0,48.0),(10,2,2,'Mantenimiento preventivo + correctivo de equipo de cómputo',2.0,48.0),(11,3,1,'Instalación de software',4.0,48.0),(12,3,1,'Permisos o accesos a plataformas de Office 365',4.0,48.0),(13,3,1,'Instalación de impresora',1.0,8.0),(14,3,3,'Soporte de impresoras en sitio',1.0,24.0),(15,3,2,'Problemas de software',2.0,48.0),(16,4,3,'Cuenta bloqueada o expirada',1.0,8.0),(17,4,3,'Problemas de buzón de correo',1.0,8.0),(18,4,1,'Baja de cuenta de correo',4.0,48.0),(19,4,1,'Baja de cuenta de AutoDesk',4.0,48.0),(20,4,1,'Gestión para alta de usuario AX y/o VPN',4.0,48.0),(21,4,1,'Cambio de contraseña de usuario de red (envío de instructivos)',4.0,48.0),(22,4,2,'Configuración de correo',2.0,48.0),(23,4,2,'Asignación de licencia de correo',2.0,48.0),(24,4,2,'Restablecimiento protocolos MFA',2.0,48.0),(25,4,2,'Creación de usuarios OpenVPN',2.0,48.0),(26,4,2,'Soporte de usuarios del Cliente Claro',2.0,48.0),(27,5,3,'Problemas de conexión a red',1.0,8.0),(28,5,3,'Problemas de acceso a red oficinas',1.0,24.0),(29,6,3,'Problemas con el sistema AX',1.0,8.0),(30,6,1,'Revisión de documentos para registro en AX',4.0,48.0),(31,7,1,'Cruce de Usuario Plan Padrino - Trino',1.0,5.0),(32,7,3,'Problemas con usuario de Trino',1.0,24.0),(33,7,3,'Problemas con App Trino Técnico',1.0,24.0),(34,7,3,'Problemas con mesa de control Trino',1.0,48.0),(35,7,2,'Creación de usuarios Trino',2.0,48.0),(36,8,1,'Respaldo o traslado de información (Backup)',4.0,48.0),(37,9,1,'Proyectos y/o mejoras',4.0,48.0),(38,9,1,'Desarrollo Web Simple',4.0,336.0),(39,9,2,'Desarrollo Web Normal',4.0,336.0),(40,9,3,'Desarrollo Web Avanzado',4.0,336.0);
/*!40000 ALTER TABLE `asuntos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-13 12:36:37
