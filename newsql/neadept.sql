-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for neafsad
CREATE DATABASE IF NOT EXISTS `neafsad` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `neafsad`;

-- Dumping structure for table neafsad.neadept_table
CREATE TABLE IF NOT EXISTS `neadept_table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.neadept_table: ~19 rows (approximately)
DELETE FROM `neadept_table`;
INSERT INTO `neadept_table` (`id`, `name`, `acronym`) VALUES
	(1, 'Legal Servies Office (LSO)', 'LSO'),
	(2, 'Regulatory Affairs Office (RAO)', 'RAO'),
	(3, 'Finance Services Department (FSD)', 'FSD'),
	(4, 'Human Resources &amp; Administration Department (HRAD)', 'HRAD'),
	(5, 'Electric Cooperative Audit Department (ECAD)', 'ECAD'),
	(6, 'Internal Audit and Quality Management Office (IAQSMO)', 'IAQSMO'),
	(7, 'Engineering Department', 'ED'),
	(8, 'Disaster Risk Reduction and Management Department (DRRMD)', 'DRRMD'),
	(9, 'Total Electrification and Renewable Energy Development Department (TEREDD)', 'TEREDD'),
	(10, 'Management and Consultancy Services Office (MCSO)', 'MCSO'),
	(11, 'Institutional Development Department (IDD)', 'IDD'),
	(12, 'Accounts Management &amp; Guarantee Department (AMGD)', 'AMGD'),
	(13, 'Office of the Administrator (OA)', 'OA'),
	(14, 'Office for Performance Assessment and Special Studies (OPASS)', 'OPASS'),
	(15, 'Corporate Communication &amp; Social Marketing Office (CCSMO)', 'CCSMO'),
	(16, 'Rural Electrification Special Program Office (RESPO)', 'RESPO'),
	(17, 'NEA-EC Training Institute (NETI)', 'NETI'),
	(18, 'Information Technology &amp; Communication Services Department (ITCSD)', 'ITCSD'),
	(19, 'Corporate Planning Office (CPO)', 'CPO');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
