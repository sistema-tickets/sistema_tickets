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
-- Table structure for table `lineas_negocio`
--

DROP TABLE IF EXISTS `lineas_negocio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_negocio` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lineas_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lineas_negocio`
--

LOCK TABLES `lineas_negocio` WRITE;
/*!40000 ALTER TABLE `lineas_negocio` DISABLE KEYS */;
INSERT INTO `lineas_negocio` VALUES (1,'Sistemas Nacional',1),(2,'Gestión Humana Nacional',1),(3,'Logística Nacional',1),(4,'Controlar Costos y Gastos Nacional\n',1),(5,'Administrar Contratistas y Jurídica',1),(6,'Instalaciones HFC Bogotá',1),(7,'Mantenimiento HFC Bogotá',1),(8,'Cundinamarca y Bogotá HFC y FO\n',1),(9,'Mantenimiento FO Bogotá\n',1),(10,'Proyectos E&N FTTH y Diseño Bogotá\n',1),(11,'Empresas y Negocios Bogotá',1),(12,'Sede Cali Sur',1),(13,'Sede Cali Norte',1),(14,'Sede Cali y/o Popayán',1),(15,'Sedes Santanderes',1),(16,'Sedes Boyacá',1),(17,'Sede Medellín y Eje Cafetero\n',1),(18,'Radio Bases Obra Civil',1),(19,'Radio Bases Mtto y Modernización',1),(20,'Radio Bases Obra Civil (FO)',1),(21,'Mintic',1),(22,'Liquidación de Mano de Obra\n',1),(23,'Operación Neiva\n',1),(24,'Operación Neiva Proyecto Tolhuca',1),(25,'Operación Ibagué Proyecto Tolhuca',1),(26,'Operación Florencia Proyecto Tolhuca\n',1),(27,'Control de Materiales',1),(28,'Aseguramiento de la Información',1);
/*!40000 ALTER TABLE `lineas_negocio` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-13 18:43:27
