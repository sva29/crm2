-- MySQL dump 10.13  Distrib 8.0.35, for Linux (x86_64)
--
-- Host: localhost    Database: vovanb3p_crm
-- ------------------------------------------------------
-- Server version	5.7.21-20-beget-5.7.21-20-1-log

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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atr_title` varchar(255) NOT NULL,
  `l_atr` char(1) NOT NULL,
  `has_fraction` tinyint(1) DEFAULT '0',
  `fr` varchar(255) DEFAULT NULL,
  `has_color` tinyint(1) DEFAULT '0',
  `t_color` varchar(255) DEFAULT NULL,
  `l_c` char(1) DEFAULT NULL,
  `fac` enum('1','2') DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `component_packagings`
--

DROP TABLE IF EXISTS `component_packagings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_packagings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `component_id` int(11) NOT NULL,
  `packaging_type` varchar(50) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `volume` decimal(10,2) NOT NULL,
  `price_per_package` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `component_packagings_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `component_packagings`
--

LOCK TABLES `component_packagings` WRITE;
/*!40000 ALTER TABLE `component_packagings` DISABLE KEYS */;
INSERT INTO `component_packagings` VALUES (1,8,NULL,'Бочка',200.00,67000.00),(2,9,NULL,'Мешок',25.00,625.00),(10,13,'Бочка','',220.00,0.00),(11,12,'Бочка','',200.00,0.00),(12,12,'Ведро','',25.00,0.00);
/*!40000 ALTER TABLE `component_packagings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `components`
--

DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `unit` enum('kg','liter') NOT NULL DEFAULT 'kg',
  `is_container` tinyint(1) DEFAULT '0',
  `concentration` decimal(5,2) DEFAULT '100.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `components`
--

LOCK TABLES `components` WRITE;
/*!40000 ALTER TABLE `components` DISABLE KEYS */;
INSERT INTO `components` VALUES (1,'ПАВ SLES СЛЕС (Лауретсульфат натрия) 70%',207.00,'kg',0,70.00),(2,'Кокамидопропилбетаин (45%)',175.00,'kg',0,45.00),(3,'Кокоамфодиацетат динатрия Miconol C2M',207.00,'kg',0,100.00),(4,'Диэтаноламид кокосового масла',305.00,'kg',0,100.00),(5,'Акриловый замутнитель (KY-Ф-301)',420.00,'liter',0,100.00),(6,'Лимонная кислота',115.00,'kg',0,100.00),(7,'Отдушка',3000.00,'liter',0,100.00),(8,'Консервант cmit-mit 1.5% ',335.00,'liter',0,100.00),(9,' Хлорид натрия',25.00,'kg',0,100.00),(10,'Вода',0.00,'kg',0,100.00),(11,'Канистра 10 л',120.00,'',1,100.00),(12,'Кокоамидопропилбетаин 30% ',158.00,'kg',0,30.00),(13,'Алкилполиглюкозид с12-с14 50%',230.00,'kg',0,100.00);
/*!40000 ALTER TABLE `components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_component_prices`
--

DROP TABLE IF EXISTS `order_component_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_component_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `order_component_prices_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_component_prices_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_component_prices`
--

LOCK TABLES `order_component_prices` WRITE;
/*!40000 ALTER TABLE `order_component_prices` DISABLE KEYS */;
INSERT INTO `order_component_prices` VALUES (1,2,1,207.00,NULL),(2,2,2,175.00,NULL),(3,2,3,207.00,NULL),(4,2,4,305.00,NULL),(5,2,5,420.00,NULL),(6,2,6,115.00,NULL),(7,2,7,3000.00,NULL),(8,2,8,335.00,NULL),(9,2,10,0.00,NULL),(10,2,11,120.00,NULL),(11,4,1,207.00,NULL),(12,4,2,175.00,NULL),(13,4,3,207.00,NULL),(14,4,4,305.00,NULL),(15,4,5,420.00,NULL),(16,4,6,115.00,NULL),(17,4,7,3000.00,NULL),(18,4,8,335.00,NULL),(19,4,10,0.00,NULL),(20,5,1,207.00,NULL),(21,5,2,175.00,NULL),(22,5,3,207.00,NULL),(23,5,4,305.00,NULL),(24,5,5,420.00,NULL),(25,5,6,115.00,NULL),(26,5,7,3000.00,NULL),(27,5,8,335.00,NULL),(28,5,10,0.00,NULL),(29,6,1,207.00,NULL),(30,6,2,175.00,NULL),(31,6,3,207.00,NULL),(32,6,4,305.00,NULL),(33,6,5,420.00,NULL),(34,6,6,115.00,NULL),(35,6,7,3000.00,NULL),(36,6,8,335.00,NULL),(37,6,10,0.00,NULL),(38,7,1,207.00,NULL),(39,7,2,175.00,NULL),(40,7,3,207.00,NULL),(41,7,4,305.00,NULL),(42,7,5,420.00,NULL),(43,7,6,115.00,NULL),(44,7,7,3000.00,NULL),(45,7,8,335.00,NULL),(46,7,10,0.00,NULL),(47,8,1,207.00,NULL),(48,8,2,175.00,NULL),(49,8,3,207.00,NULL),(50,8,4,305.00,NULL),(51,8,5,420.00,NULL),(52,8,6,115.00,NULL),(53,8,7,3000.00,NULL),(54,8,8,335.00,NULL),(55,8,10,0.00,NULL),(56,9,1,207.00,NULL),(57,9,2,175.00,NULL),(58,9,3,207.00,NULL),(59,9,4,305.00,NULL),(60,9,5,420.00,NULL),(61,9,6,115.00,NULL),(62,9,7,3000.00,NULL),(63,9,8,335.00,NULL),(64,9,10,0.00,NULL),(65,10,1,207.00,NULL),(66,10,2,175.00,NULL),(67,10,3,207.00,NULL),(68,10,4,305.00,NULL),(69,10,5,420.00,NULL),(70,10,6,115.00,NULL),(71,10,7,3000.00,NULL),(72,10,8,335.00,NULL),(73,10,10,0.00,NULL),(74,12,1,207.00,NULL),(75,12,2,175.00,NULL),(76,12,3,207.00,NULL),(77,12,4,305.00,NULL),(78,12,5,420.00,NULL),(79,12,6,115.00,NULL),(80,12,7,3000.00,NULL),(81,12,8,335.00,NULL),(82,13,1,207.00,NULL),(83,13,2,175.00,NULL),(84,13,3,207.00,NULL),(85,13,4,305.00,NULL),(86,13,5,420.00,NULL),(87,13,6,115.00,NULL),(88,13,7,3000.00,NULL),(89,13,8,335.00,NULL),(90,14,1,207.00,NULL),(91,14,2,175.00,NULL),(92,14,3,207.00,NULL),(93,14,4,305.00,NULL),(94,14,5,420.00,NULL),(95,14,6,115.00,NULL),(96,14,7,3000.00,NULL),(97,14,8,335.00,NULL),(98,18,1,207.00,NULL),(99,18,2,175.00,NULL),(100,18,3,207.00,NULL),(101,18,4,305.00,NULL),(102,18,5,420.00,NULL),(103,18,6,115.00,NULL),(104,18,7,3000.00,NULL),(105,18,8,335.00,NULL),(106,18,11,120.00,NULL),(107,19,11,120.00,NULL),(108,20,1,207.00,NULL),(109,20,2,175.00,NULL),(110,20,3,207.00,NULL),(111,20,4,305.00,NULL),(112,20,5,420.00,NULL),(113,20,6,115.00,NULL),(114,20,7,3000.00,NULL),(115,20,8,335.00,NULL),(116,21,1,207.00,NULL),(117,21,2,175.00,NULL),(118,21,3,207.00,NULL),(119,21,4,305.00,NULL),(120,21,5,420.00,NULL),(121,21,6,115.00,NULL),(122,21,7,3000.00,NULL),(123,21,8,335.00,NULL),(124,21,11,120.00,NULL),(125,22,1,207.00,NULL),(126,22,2,175.00,NULL),(127,22,3,207.00,NULL),(128,22,4,305.00,NULL),(129,22,5,420.00,NULL),(130,22,6,115.00,NULL),(131,22,7,3000.00,NULL),(132,22,8,335.00,NULL),(133,22,11,120.00,NULL),(135,24,1,207.00,NULL),(136,24,2,175.00,NULL),(137,24,3,207.00,NULL),(138,24,4,305.00,NULL),(139,24,5,420.00,NULL),(140,24,6,115.00,NULL),(141,24,7,3000.00,NULL),(142,24,8,335.00,NULL),(143,24,10,0.00,NULL),(144,25,11,120.00,NULL),(145,26,1,207.00,NULL),(146,26,2,175.00,NULL),(147,26,3,207.00,NULL),(148,26,4,305.00,NULL),(149,26,5,420.00,NULL),(150,26,6,115.00,NULL),(151,26,7,3000.00,NULL),(152,26,8,335.00,NULL),(153,26,10,0.00,NULL),(154,26,11,120.00,NULL);
/*!40000 ALTER TABLE `order_component_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,18,1,100.00),(2,18,1,100.00),(3,18,NULL,100.00),(4,19,NULL,100.00),(5,20,1,100.00),(6,21,1,100.00),(7,21,1,100.00),(8,21,NULL,100.00),(9,22,1,100.00),(10,22,1,100.00),(11,22,NULL,100.00),(12,23,1,100.00),(13,24,1,100.00),(14,25,NULL,100.00),(15,26,1,100.00),(16,26,1,100.00),(17,26,NULL,100.00),(18,27,1,100.00),(19,27,1,100.00),(20,27,NULL,100.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_container_labors`
--

DROP TABLE IF EXISTS `product_container_labors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_container_labors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `container_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `container_id` (`container_id`),
  CONSTRAINT `product_container_labors_ibfk_1` FOREIGN KEY (`container_id`) REFERENCES `product_containers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_container_labors`
--

LOCK TABLES `product_container_labors` WRITE;
/*!40000 ALTER TABLE `product_container_labors` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_container_labors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_containers`
--

DROP TABLE IF EXISTS `product_containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_containers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `volume` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_containers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_containers`
--

LOCK TABLES `product_containers` WRITE;
/*!40000 ALTER TABLE `product_containers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_containers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `target_volume` varchar(50) NOT NULL DEFAULT 'percent',
  `base_volume` decimal(10,2) DEFAULT '1000.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Жидкое крем-мыло','percent',1000.00),(2,'Средства для мытья пола','1000',1000.00);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'2025-09-19 15:50:13',0.00),(2,'2025-09-19 15:51:02',151475.00),(3,'2025-09-19 15:51:09',0.00),(4,'2025-09-19 15:55:07',31475.00),(5,'2025-09-19 23:04:22',31475.00),(6,'2025-09-19 23:07:27',31475.00),(7,'2025-09-19 23:10:15',31475.00),(8,'2025-09-19 23:10:35',3147.50),(9,'2025-09-19 23:13:45',31475.00),(10,'2025-09-19 23:20:28',62950.00),(11,'2025-09-19 23:28:40',0.00),(12,'2025-09-19 23:33:11',47689.39),(13,'2025-09-19 23:38:29',6410.39),(14,'2025-09-19 23:39:02',64103.87),(15,'2025-09-19 23:39:30',0.00),(16,'2025-09-19 23:39:44',0.00),(17,'2025-09-19 23:42:31',0.00),(18,'2025-09-19 23:48:43',18410.39),(19,'2025-09-19 23:48:59',12000.00),(20,'2025-09-19 23:49:16',3205.19),(21,'2025-09-20 20:38:28',18410.39),(22,'2025-09-20 20:44:54',18410.39),(23,'2025-09-20 21:25:52',0.00),(24,'2025-09-20 21:28:52',3205.19),(25,'2025-09-20 21:28:58',12000.00),(26,'2025-09-20 21:29:13',18410.39),(27,'2025-09-20 22:11:36',0.00);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_type` enum('percent','absolute') NOT NULL DEFAULT 'absolute',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (59,1,1,6.00,'percent'),(60,1,2,3.00,'percent'),(61,1,3,1.00,'percent'),(62,1,4,1.00,'percent'),(63,1,5,1.00,'percent'),(64,1,6,1.00,'percent'),(65,1,7,0.10,'percent'),(66,1,8,0.10,'percent'),(67,1,10,85.00,'percent'),(80,2,10,975.00,'absolute'),(81,2,12,10.00,'absolute'),(82,2,13,15.00,'absolute'),(83,2,7,1.50,'absolute');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Администратор системы'),(2,'technologist_lkm','Технолог ЛКМ'),(3,'technologist_bh','Технолог БХ'),(4,'employee_lkm','Сотрудник ЛКМ');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `current_role` varchar(255) NOT NULL DEFAULT 'Сотрудник ЛКМ',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'vovanb3p_crm'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-22 23:10:45
