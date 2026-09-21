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
-- Table structure for table `sitios`
--

DROP TABLE IF EXISTS `sitios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sitios` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `region_id` smallint NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sitios_nombre_region` (`region_id`,`nombre`),
  CONSTRAINT `fk_sitios_region` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sitios`
--

LOCK TABLES `sitios` WRITE;
/*!40000 ALTER TABLE `sitios` DISABLE KEYS */;
INSERT INTO `sitios` VALUES (2,1,'Cali'),(9,1,'Florencia'),(8,1,'Ibagué'),(6,1,'Medellín'),(4,1,'Neiva'),(5,1,'Pasto'),(7,1,'Pereira'),(3,1,'Popayán'),(1,2,'Bogotá'),(34,3,'Barichara'),(32,3,'Barrancabermeja'),(13,3,'Boyacá'),(12,3,'Bucaramanga'),(21,3,'Cajicá'),(16,3,'Chía'),(27,3,'Chiquinquirá'),(23,3,'Duitama'),(35,3,'Espinal'),(17,3,'Facatativá'),(29,3,'Floridablanca'),(19,3,'Funza'),(10,3,'Fusagasugá'),(11,3,'Girardot'),(30,3,'Girón'),(20,3,'Madrid'),(18,3,'Mosquera'),(25,3,'Paipa'),(31,3,'Piedecuesta'),(28,3,'Puerto'),(33,3,'San'),(14,3,'Soacha'),(24,3,'Sogamoso'),(22,3,'Tunja'),(26,3,'Villa'),(15,3,'Zipaquirá'),(36,4,'Cúcuta'),(37,4,'San Andr├®s');
/*!40000 ALTER TABLE `sitios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-13 18:43:28
