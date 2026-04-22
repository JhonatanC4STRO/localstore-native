-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: tiendalocal
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL DEFAULT '0',
  `action` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,14,'Usuario #13 → estado: suspended','2026-04-19 03:03:51'),(2,14,'Usuario #13 → estado: active','2026-04-19 03:03:51'),(3,14,'Admin creado: pruebax2@test.com (#16), rol: admin','2026-04-19 03:03:51'),(4,0,'Usuario #14 → estado: suspended','2026-04-19 03:06:12'),(5,0,'Usuario #14 → estado: active','2026-04-19 03:06:18'),(6,0,'Admin creado: pepeadmin@gmail.com (#17), rol: admin','2026-04-19 03:08:22'),(7,0,'Usuario #12 eliminado (soft delete)','2026-04-19 03:08:56'),(8,17,'Producto #12 → admin_status: deleted','2026-04-20 23:35:59'),(9,17,'Usuario #9 → estado: suspended','2026-04-21 00:03:28'),(10,0,'Usuario #9 → estado: active','2026-04-21 00:05:01'),(11,0,'Producto #12 → admin_status: deleted','2026-04-21 00:06:22'),(12,0,'Producto #12 → admin_status: deleted','2026-04-21 00:06:39'),(13,0,'Producto #12 → admin_status: deleted','2026-04-21 00:09:59');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Vehículos'),(2,'Propiedades en venta'),(3,'Propiedades en alquiler'),(4,'Electrónica'),(5,'Celulares y accesorios'),(6,'Computadores y tablets'),(7,'Hogar y muebles'),(8,'Electrodomésticos'),(9,'Ropa y accesorios'),(10,'Calzado'),(11,'Belleza y cuidado personal'),(12,'Deportes y fitness'),(13,'Juguetes y juegos'),(14,'Mascotas'),(15,'Herramientas'),(16,'Jardín y exterior'),(17,'Instrumentos musicales'),(18,'Arte y coleccionables'),(19,'Libros y revistas'),(20,'Videojuegos y consolas'),(21,'Bicicletas'),(22,'Motocicletas'),(23,'Servicios');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `buyer_id` int DEFAULT NULL,
  `seller_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hidden_by_buyer` tinyint(1) NOT NULL DEFAULT '0',
  `hidden_by_seller` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,9,2,9,'2026-03-22 00:46:58',1,0),(2,9,9,9,'2026-03-22 00:49:19',1,0),(4,6,2,2,'2026-03-22 01:04:40',1,0),(11,11,6,2,'2026-03-22 02:34:29',0,1),(12,5,2,6,'2026-03-22 02:43:25',1,0),(13,0,2,9,'2026-03-24 18:24:36',1,0),(14,NULL,2,9,'2026-03-24 18:36:09',1,0),(15,10,2,9,'2026-03-28 02:52:59',1,0),(16,NULL,2,6,'2026-03-28 07:33:00',1,0),(18,7,11,2,'2026-04-09 14:20:08',0,1),(19,13,2,10,'2026-04-10 20:33:47',1,0),(20,NULL,10,2,'2026-04-13 01:23:03',0,1),(21,NULL,12,2,'2026-04-13 01:31:14',0,1),(22,7,12,2,'2026-04-13 01:33:06',0,1),(23,NULL,13,2,'2026-04-13 01:43:03',0,1),(24,4,13,2,'2026-04-13 01:56:41',0,1),(25,NULL,2,10,'2026-04-13 11:09:53',1,0),(26,NULL,3,2,'2026-04-15 18:30:02',0,1),(27,16,0,3,'2026-04-18 22:04:09',0,0),(28,14,2,10,'2026-04-21 01:05:56',0,0);
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_product` (`user_id`,`product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
INSERT INTO `favorites` VALUES (5,3,10,'2026-04-16 00:38:31'),(11,3,6,'2026-04-16 00:42:23'),(12,3,7,'2026-04-16 00:42:48'),(13,3,4,'2026-04-16 00:43:01'),(15,2,14,'2026-04-21 04:24:17');
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int DEFAULT NULL,
  `sender_id` int DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (6,4,2,'hola amigo jorge, que vale este producto','2026-03-22 01:44:37',0),(40,11,6,'Hola! Me interesa tu producto: Yamaha XTZ 150 – Potencia, estilo y aventura gg papa.','2026-03-22 02:34:29',0),(41,11,2,'si senior esta disponible','2026-03-22 02:40:31',0),(42,11,6,'ah como lo vende mbro','2026-03-22 02:41:26',0),(43,11,2,'ah 50 mil','2026-03-22 02:42:13',0),(44,11,6,'muy caro','2026-03-22 02:42:18',0),(45,12,2,'Hola! Me interesa tu producto: Ropa de segunda.','2026-03-22 02:43:25',0),(46,12,6,'si claro','2026-03-22 02:43:38',1),(47,12,2,'que costo tiene','2026-03-22 02:43:45',0),(48,12,6,'20 pesos','2026-03-22 02:43:50',1),(49,11,2,'🛒🛒','2026-03-23 01:37:16',0),(50,12,2,'hol','2026-03-23 01:39:17',0),(51,12,2,'hol','2026-03-23 01:39:51',0),(52,12,2,'asd','2026-03-23 01:39:54',0),(53,12,6,'¿Está disponible?','2026-03-23 02:27:05',1),(54,12,6,'¿Cuándo podemos vernos?','2026-03-23 02:27:07',1),(55,12,6,'¿Acepta ofertas?','2026-03-23 02:27:07',1),(56,12,2,'hh','2026-03-24 02:18:16',0),(57,12,2,'¿Acepta ofertas?','2026-03-24 02:18:29',0),(58,12,6,'sapo','2026-03-24 17:06:14',1),(59,1,2,'si senor esta disponiblee','2026-03-24 18:12:26',0),(60,14,2,'¡Hola! Me gustaría contactarte.','2026-03-24 18:36:09',0),(61,12,2,'hola','2026-03-24 18:50:30',0),(62,12,2,'hola','2026-03-26 18:50:08',0),(63,15,2,'¡Hola! Me interesa tu producto: Banano.','2026-03-28 02:52:59',0),(64,1,2,'hh','2026-03-28 06:55:38',0),(65,1,2,'h','2026-03-28 06:55:39',0),(66,15,2,'h','2026-03-28 06:55:41',0),(67,15,2,'h','2026-03-28 06:55:42',0),(68,15,2,'h','2026-03-28 06:55:42',0),(69,15,2,'h','2026-03-28 06:55:42',0),(70,16,2,'¡Hola! Me gustaría contactarte.','2026-03-28 07:33:00',0),(71,15,2,'sd','2026-04-07 11:24:29',0),(72,15,2,'v','2026-04-07 11:25:13',0),(73,1,2,'v','2026-04-07 11:25:16',0),(74,1,2,'rt','2026-04-07 11:25:18',0),(75,1,2,'r','2026-04-07 11:25:19',0),(76,1,2,'w','2026-04-07 11:25:19',0),(77,12,2,'hola buenas dias','2026-04-07 11:25:48',0),(78,12,6,'hola companiero','2026-04-07 11:26:15',1),(80,18,11,'¡Hola! Me interesa tu producto: Reloj melo melo.','2026-04-09 14:20:08',1),(81,19,2,'¡Hola! Me interesa tu producto: Zapatos para bebé.','2026-04-10 20:33:47',1),(82,20,10,'¡Hola! Me gustaría contactarte.','2026-04-13 01:23:03',1),(83,21,12,'¡Hola! Me gustaría contactarte.','2026-04-13 01:31:14',1),(84,22,12,'¡Hola! Me interesa tu producto: Reloj melo melo.','2026-04-13 01:33:06',1),(85,23,13,'¡Hola! Me gustaría contactarte.','2026-04-13 01:43:03',1),(86,23,13,'Prueba de chat general funcionando','2026-04-13 01:46:41',1),(87,23,13,'Prueba de chat general funcionando','2026-04-13 01:46:48',1),(88,24,13,'¡Hola! Me interesa tu producto: aaaaaaa.','2026-04-13 01:56:41',1),(89,25,2,'¡Hola! Me gustaría contactarte.','2026-04-13 11:09:53',1),(90,25,2,'hola topo','2026-04-13 11:23:03',1),(91,25,10,'l','2026-04-13 11:31:38',1),(92,25,10,'hols','2026-04-13 11:32:49',1),(93,25,2,'l','2026-04-13 11:33:41',1),(94,26,3,'¡Hola! Me gustaría contactarte.','2026-04-15 18:30:02',1),(95,25,2,'hola','2026-04-18 00:19:14',1),(96,27,0,'¡Hola! Me interesa tu producto: Zapatos nikes.','2026-04-18 22:04:09',0),(97,28,2,'¡Hola! Me interesa tu producto: Hoodie azul.','2026-04-21 01:05:56',1);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (4,4,'1773751599_1-2-3-1-fig2.png'),(5,5,'1773777337_1.jpg'),(6,5,'1773777337_2.jpg'),(7,5,'1773777337_3.jpg'),(8,5,'1773777337_4.jpg'),(9,5,'1773777337_belt.jpg'),(10,6,'1773780264_1.jpg'),(11,6,'1773780264_2.jpg'),(12,6,'1773780264_3.jpg'),(13,6,'1773780264_4.jpg'),(14,6,'1773780264_belt.jpg'),(15,7,'1773904092_watch-1.jpg'),(16,7,'1773904092_watch-2.jpg'),(17,8,'1773926063_4.jpg'),(18,9,'1773941716_perfume.jpg'),(19,10,'1773952702_OIP.webp'),(20,11,'1773953292_xtz150_azul-ABS-1.png'),(21,13,'1775852874_jewellery-1.jpg'),(22,14,'1775853472_2.jpg'),(23,14,'1775853472_3.jpg'),(24,15,'1775853519_4.jpg'),(26,16,'1776281754_938c3c8e.jpg'),(27,18,'1776731538_jewellery-2.jpg');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_promotions`
--

DROP TABLE IF EXISTS `product_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `plan_type` enum('basic','recommended','premium') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('pending','active','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_status` (`product_id`,`status`),
  KEY `idx_end_date` (`end_date`),
  CONSTRAINT `fk_pp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_promotions`
--

LOCK TABLES `product_promotions` WRITE;
/*!40000 ALTER TABLE `product_promotions` DISABLE KEYS */;
INSERT INTO `product_promotions` VALUES (1,11,'recommended',5000.00,'2026-04-07 01:33:38','2026-04-14 01:33:38','expired','2026-04-07 01:33:38'),(2,8,'basic',3000.00,'2026-04-07 01:52:11','2026-04-10 01:52:11','expired','2026-04-07 01:52:11'),(3,7,'premium',10000.00,'2026-04-07 02:05:52','2026-04-14 02:05:52','expired','2026-04-07 02:05:52'),(4,4,'premium',10000.00,'2026-04-07 02:08:20','2026-04-14 02:08:20','expired','2026-04-07 02:08:20'),(5,11,'premium',10000.00,'2026-04-14 20:31:07','2026-04-21 20:31:07','active','2026-04-14 20:31:07'),(6,14,'premium',10000.00,'2026-04-14 20:37:47','2026-04-21 20:37:47','active','2026-04-14 20:37:47'),(7,16,'basic',3000.00,'2026-04-15 19:26:46','2026-04-18 19:26:46','expired','2026-04-15 19:26:46');
/*!40000 ALTER TABLE `product_promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reports`
--

DROP TABLE IF EXISTS `product_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reason` varchar(60) NOT NULL,
  `description` text NOT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `admin_notes` text,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_reporter` (`reporter_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reports`
--

LOCK TABLES `product_reports` WRITE;
/*!40000 ALTER TABLE `product_reports` DISABLE KEYS */;
INSERT INTO `product_reports` VALUES (1,12,2,'precio_enganoso','No tiene imagen y es sospechoso',NULL,'resolved','Se elimino porque no tiene una imagen',0,'2026-04-21 00:09:39','2026-04-21 00:05:50');
/*!40000 ALTER TABLE `product_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `status` enum('disponible','vendido') DEFAULT NULL,
  `longitude` varchar(150) DEFAULT NULL,
  `latitude` varchar(150) DEFAULT NULL,
  `condition_type` enum('nuevo','usado','reacondicionado') NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `admin_status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `promo_priority` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0=normal 1=basic 2=recommended 3=premium',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_user` (`user_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (4,'aaaaaaa',40000.00,'kkkkkkkkkkkkkkkkkkkkkkkkk',18,'disponible','-75.6133198825274','1.616839538700291','nuevo',2,'2026-03-19 22:01:11','active','2026-04-07 02:08:20',3),(5,'Ropa de segunda',1.00,'Ropa',1,'disponible','-75.61330411456768','1.6167760913179823','nuevo',6,'2026-03-19 22:01:11','active',NULL,0),(6,'Jorge ek vendedor',1.00,'ok',2,'disponible','','','nuevo',2,'2026-03-19 22:01:11','active',NULL,0),(7,'Reloj melo melo',50000.00,'Bonito reloj de segunda mano',8,'disponible','-75.61329911602752','1.6167660646744626','nuevo',2,'2026-03-19 22:01:11','active','2026-04-07 02:05:52',3),(8,'Habitacion',55000.00,'fcytgvuhbijhbjkbjlhb',3,'disponible','-75.6133044761098','1.6167665606239667','nuevo',2,'2026-03-19 22:01:11','active','2026-04-21 00:43:22',1),(9,'Perfume',67000.00,'Alguna descripcion',11,'disponible','-75.58279626602778','1.603570307182992','nuevo',9,'2026-03-19 22:01:11','active',NULL,0),(10,'Banano',200.00,'Fesco y bonito',13,'disponible','-75.58269362757557','1.6036404789771672','nuevo',9,'2026-03-19 22:01:11','active','2026-04-14 20:49:34',0),(11,'Yamaha XTZ 150 – Potencia, estilo y aventura gg papa',13000002.00,'Se vende Yamaha XTZ 150, una moto versátil y confiable, perfecta tanto para la ciudad como para caminos destapados. Su diseño robusto y deportivo la convierte en la opción ideal para quienes buscan rendimiento, comodidad y estilo en un solo vehículo.\r\n\r\n🔹 Motor 150cc eficiente y potente\r\n🔹 Diseño doble propósito (on-road / off-road)\r\n🔹 Suspensión alta ideal para terrenos irregulares\r\n🔹 Excelente rendimiento de combustible\r\n🔹 Frenos de alta seguridad\r\n🔹 Marca reconocida: Yamaha\r\n\r\n💥 Perfecta para trabajo, viajes o uso diario.\r\n💥 Bajo consumo y mantenimiento económico.\r\n\r\n📍 Lista para traspaso inmediato\r\n📞 Contáctame para más información o verla en persona',22,'disponible','-75.58280833324847','1.6035725933783145','nuevo',2,'2026-03-19 22:01:11','active','2026-04-14 20:31:07',3),(13,'Zapatos para bebé',80000.00,'👶💖 ¡Los más pequeños también merecen estilo!\r\n\r\nEstos zapatitos para bebé son tan adorables como cómodos. Perfectos para acompañar sus primeros pasos. 🥰\r\n\r\n✔️ Suaves y seguros\r\n✔️ Diseño adorable\r\n✔️ Máxima comodidad\r\n\r\n✨ ¡Haz que cada paso sea especial!\r\n',10,'disponible','-75.582698998952','1.603636251545','usado',10,'2026-04-10 20:27:54','active',NULL,0),(14,'Hoodie azul',120000.00,'💙 Comodidad + estilo en una sola prenda 💙\r\n\r\nEste hoodie azul es perfecto para tu día a día. Ya sea para salir o relajarte, siempre vas a verte bien. 😎\r\n\r\n✔️ Suave y cómodo\r\n✔️ Diseño moderno\r\n✔️ Ideal para cualquier clima\r\n\r\n🔥 ¡No te quedes sin el tuyo!\r\n',9,'disponible','-75.582732764286','1.603624290067','usado',10,'2026-04-10 20:37:52','active','2026-04-21 05:39:11',3),(15,'Sombrero elegante',450000.00,'✨ Dale un toque de elegancia a tu estilo ✨\r\n\r\nEste sombrero es el complemento perfecto para cualquier outfit. Ideal para un look casual o algo más sofisticado. 🤎\r\n\r\n✔️ Diseño clásico\r\n✔️ Cómodo y ligero\r\n✔️ Perfecto para cualquier ocasión\r\n\r\n🔥 ¡Hazlo parte de tu estilo hoy!\r\n',9,'disponible','-75.582686809164','1.6036500653369','nuevo',10,'2026-04-10 20:38:39','active',NULL,0),(16,'Zapatos nikes',155000.00,'Zapatos nike nuevos ubicados en florencia caquetas',10,'disponible','-75.611616009245','1.6101254929562','nuevo',3,'2026-04-15 19:00:20','active','2026-04-15 19:29:39',1),(18,'Anillo',1200000.00,'Anillo de oro y diamantes',5,'disponible','-75.6133071570859','1.6167940590117815','usado',18,'2026-04-21 00:32:18','active',NULL,0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`product_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,9,2,3,'el produfotjorjpor','2026-03-28 07:15:25'),(3,13,2,5,'Exelente','2026-04-13 11:39:10');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_verifications`
--

DROP TABLE IF EXISTS `seller_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `verification_type` varchar(20) NOT NULL DEFAULT 'negocio',
  `store_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `description` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_verifications`
--

LOCK TABLES `seller_verifications` WRITE;
/*!40000 ALTER TABLE `seller_verifications` DISABLE KEYS */;
INSERT INTO `seller_verifications` VALUES (5,3,'persona','Maria Lopez','','Florencia','3214567788','Cédula de ciudadanía','public/uploads/verificaciones/verif_3_1776282452.pdf','Porque si','approved',NULL,0,'2026-04-15 19:55:27','2026-04-15 19:47:32','2026-04-15 19:55:27'),(7,2,'negocio','ShopShon','Ropa y moda','Florencia','3144788677','Cámara de Comercio','public/uploads/verificaciones/verif_2_1776469709.pdf','Vendo ropa de primera mano y original','rejected','Los datos no coinciden con el registro.',0,'2026-04-17 23:49:23','2026-04-17 23:48:29','2026-04-17 23:49:23');
/*!40000 ALTER TABLE `seller_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_reports`
--

DROP TABLE IF EXISTS `user_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reported_user_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reason` varchar(60) NOT NULL,
  `description` text NOT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `admin_notes` text,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reported` (`reported_user_id`),
  KEY `idx_reporter` (`reporter_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_reports`
--

LOCK TABLES `user_reports` WRITE;
/*!40000 ALTER TABLE `user_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_settings` (
  `user_id` int NOT NULL,
  `notif_email_messages` tinyint(1) NOT NULL DEFAULT '1',
  `notif_email_reviews` tinyint(1) NOT NULL DEFAULT '1',
  `notif_email_sales` tinyint(1) NOT NULL DEFAULT '1',
  `notif_email_promos` tinyint(1) NOT NULL DEFAULT '1',
  `notif_browser` tinyint(1) NOT NULL DEFAULT '1',
  `privacy_show_phone` tinyint(1) NOT NULL DEFAULT '1',
  `privacy_show_email` tinyint(1) NOT NULL DEFAULT '0',
  `privacy_who_can_message` enum('all','verified','nobody') NOT NULL DEFAULT 'all',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (10,1,1,1,1,1,1,0,'all','2026-04-21 01:05:45');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type_user` enum('usuario','tienda') NOT NULL DEFAULT 'usuario',
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `bio` text,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blocked','suspended') NOT NULL DEFAULT 'active',
  `role` enum('user','seller','admin','super_admin') NOT NULL DEFAULT 'user',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Jhonatan castro calderon','shonano@gmail.com','123','2026-03-17 06:05:26','usuario','3144788677','Florencia','Juan xxiii','Esto es un demo gg','u2_1775561589.png','active','seller','2026-04-07 11:33:09',NULL,NULL),(3,'Maria Lopez','maria@gmail.com','123','2026-03-17 12:11:46','usuario',NULL,NULL,NULL,NULL,NULL,'active','seller','2026-04-15 19:00:20',NULL,NULL),(4,'El esquinazo','esquinazo@gmail.com','123','2026-03-17 18:05:15','usuario',NULL,NULL,NULL,NULL,NULL,'active','user','2026-04-03 04:10:45',NULL,NULL),(6,'FerroMax','ferromax@gmail.com','123','2026-03-17 18:06:54','tienda',NULL,NULL,NULL,NULL,NULL,'active','seller','2026-04-03 04:28:46',NULL,NULL),(8,'','','','2026-03-19 02:18:57','usuario',NULL,NULL,NULL,NULL,NULL,'active','user','2026-04-03 04:10:45',NULL,NULL),(9,'Claude Lopez','claudia@gmail.com','123','2026-03-19 17:31:46','usuario',NULL,NULL,NULL,NULL,NULL,'active','seller','2026-04-21 00:05:01',NULL,NULL),(10,'Topogigio','topo@gmail.com','123','2026-03-19 18:14:05','usuario',NULL,NULL,NULL,NULL,NULL,'active','seller','2026-04-10 20:13:14',NULL,NULL),(11,'Gustavo Adolfo','gustavo@gmail.com','123','2026-03-19 20:49:06','usuario',NULL,NULL,NULL,NULL,NULL,'active','user','2026-04-03 04:10:45',NULL,NULL),(12,'Test User','testuser@example.com','Password123!','2026-04-13 01:30:54','usuario',NULL,NULL,NULL,NULL,NULL,'blocked','user','2026-04-19 03:08:56','2026-04-19 03:08:56',NULL),(13,'Tester User','tester@example.com','password123','2026-04-13 01:42:50','usuario',NULL,NULL,NULL,NULL,NULL,'active','seller','2026-04-19 03:03:51',NULL,NULL),(14,'Prueba admin','admin2@gmail.com','$2y$10$Jp0qoCe3h.dVHLyEkTYH0ON57cFSBrO4nN1WOIwHlZ8hHwtrNulri','2026-04-19 02:58:57','usuario',NULL,NULL,NULL,NULL,NULL,'active','admin','2026-04-19 03:06:18',NULL,NULL),(17,'Pepe admin','pepeadmin@gmail.com','$2y$10$.pTYs/lKGpgSh8cLc/DnLuufp7xNUDL1486zJBT.Xpg12JL3fQW0e','2026-04-19 03:08:22','usuario',NULL,NULL,NULL,NULL,NULL,'active','admin','2026-04-19 03:08:22',NULL,NULL),(18,'Administrador','admin@gmail.com','$2y$10$f/pGYVtDuNdRFjZkOGkbgum56m1UeA9MuLwLCHaz9ocaiwT5Q2VE6','2026-04-21 00:19:05','usuario',NULL,NULL,NULL,NULL,NULL,'active','admin','2026-04-21 00:19:05',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tiendalocal'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `expire_promotions` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = cp850 */ ;;
/*!50003 SET character_set_results = cp850 */ ;;
/*!50003 SET collation_connection  = cp850_general_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `expire_promotions` ON SCHEDULE EVERY 1 HOUR STARTS '2026-04-06 20:31:13' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE `product_promotions`
    SET    `status` = 'expired'
    WHERE  `status` = 'active'
      AND  `end_date` < NOW() */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

--
-- Dumping routines for database 'tiendalocal'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-21  0:47:57
