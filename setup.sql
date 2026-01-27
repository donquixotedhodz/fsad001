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

-- Dumping structure for table neafsad.approving_authority
CREATE TABLE IF NOT EXISTS `approving_authority` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `description` text,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.electric_cooperatives
CREATE TABLE IF NOT EXISTS `electric_cooperatives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

INSERT INTO electric_cooperatives (name, code, description) VALUES
('Abra Electric Cooperative, Inc.', 'ABRECO', 'Electric cooperative in Abra, Luzon'),
('Agusan del Norte Electric Cooperative, Inc.', 'ANECO', 'Electric cooperative in Agusan del Norte, Mindanao'),
('Agusan del Sur Electric Cooperative, Inc.', 'ASELCO', 'Electric cooperative in Agusan del Sur, Mindanao'),
('Aklan Electric Cooperative, Inc.', 'AKELCO', 'Electric cooperative in Aklan, Visayas'),
('Albay Electric Cooperative, Inc.', 'ALECO', 'Electric cooperative in Albay, Luzon'),
('Antique Electric Cooperative, Inc.', 'ANTECO', 'Electric cooperative in Antique, Visayas'),
('Aurora Electric Cooperative, Inc.', 'AURELCO', 'Electric cooperative in Aurora, Luzon'),
('Bantayan Electric Cooperative, Inc.', 'BANELCO', 'Electric cooperative serving Bantayan Island, Visayas'),
('Basilan Electric Cooperative, Inc.', 'BASELCO', 'Electric cooperative in Basilan, Mindanao'),
('Batanes Electric Cooperative, Inc.', 'BATANELCO', 'Electric cooperative in Batanes, Luzon'),
('Batangas I Electric Cooperative, Inc.', 'BATELEC I', 'Electric cooperative in Western Batangas, Luzon'),
('Batangas II Electric Cooperative, Inc.', 'BATELEC II', 'Electric cooperative in Eastern Batangas, Luzon'),
('Benguet Electric Cooperative, Inc.', 'BENECO', 'Electric cooperative in Benguet and Baguio, Luzon'),
('Biliran Electric Cooperative, Inc.', 'BILECO', 'Electric cooperative in Biliran, Visayas'),
('Bohol I Electric Cooperative, Inc.', 'BOHECO I', 'Electric cooperative in Bohol (District I), Visayas'),
('Bohol II Electric Cooperative, Inc.', 'BOHECO II', 'Electric cooperative in Bohol (District II), Visayas'),
('Bukidnon Second Electric Cooperative, Inc.', 'BUSECO', 'Electric cooperative in Northern Bukidnon, Mindanao'),
('Busuanga Island Electric Cooperative, Inc.', 'BISELCO', 'Electric cooperative in Busuanga Island, Luzon'),
('Cagayan I Electric Cooperative, Inc.', 'CAGELCO I', 'Electric cooperative in Western Cagayan, Luzon'),
('Cagayan II Electric Cooperative, Inc.', 'CAGELCO II', 'Electric cooperative in Eastern Cagayan, Luzon'),
('Camarines Norte Electric Cooperative, Inc.', 'CANORECO', 'Electric cooperative in Camarines Norte, Luzon'),
('Camarines Sur I Electric Cooperative, Inc.', 'CASURECO I', 'Electric cooperative in Camarines Sur (District I), Luzon'),
('Camarines Sur II Electric Cooperative, Inc.', 'CASURECO II', 'Electric cooperative in Camarines Sur (District II), Luzon'),
('Camarines Sur III Electric Cooperative, Inc.', 'CASURECO III', 'Electric cooperative in Camarines Sur (District III), Luzon'),
('Camarines Sur IV Electric Cooperative, Inc.', 'CASURECO IV', 'Electric cooperative in Camarines Sur (District IV), Luzon'),
('Camiguin Electric Cooperative, Inc.', 'CAMELCO', 'Electric cooperative in Camiguin, Mindanao'),
('Capiz Electric Cooperative, Inc.', 'CAPELCO', 'Electric cooperative in Capiz, Visayas'),
('Cebu I Electric Cooperative, Inc.', 'CEBECO I', 'Electric cooperative in Cebu (District I), Visayas'),
('Cebu II Electric Cooperative, Inc.', 'CEBECO II', 'Electric cooperative in Cebu (District II), Visayas'),
('Cebu III Electric Cooperative, Inc.', 'CEBECO III', 'Electric cooperative in Cebu (District III), Visayas'),
('Central Pangasinan Electric Cooperative, Inc.', 'CENPELCO', 'Electric cooperative in Central Pangasinan, Luzon'),
('Cotabato Electric Cooperative, Inc.', 'COTELCO', 'Electric cooperative in Cotabato, Mindanao'),
('Cotabato Electric Cooperative – PPALMA', 'COTELCO-PPALMA', 'Electric cooperative serving PPALMA area, Mindanao'),
('Davao Oriental Electric Cooperative, Inc.', 'DORECO', 'Electric cooperative in Davao Oriental, Mindanao'),
('Davao del Sur Electric Cooperative, Inc.', 'DASURECO', 'Electric cooperative in Davao del Sur, Mindanao'),
('Dinagat Island Electric Cooperative, Inc.', 'DIELCO', 'Electric cooperative in Dinagat Islands, Mindanao'),
('First Bukidnon Electric Cooperative, Inc.', 'FIBECO', 'Electric cooperative in Southern Bukidnon, Mindanao'),
('First Catanduanes Electric Cooperative, Inc.', 'FICELCO', 'Electric cooperative in Catanduanes, Luzon'),
('First Laguna Electric Cooperative, Inc.', 'FLECO', 'Electric cooperative in Laguna, Luzon'),
('Guimaras Electric Cooperative, Inc.', 'GUIMELCO', 'Electric cooperative in Guimaras, Visayas'),
('Ifugao Electric Cooperative, Inc.', 'IFELCO', 'Electric cooperative in Ifugao, Luzon'),
('Isabela I Electric Cooperative, Inc.', 'ISELCO I', 'Electric cooperative in Northern Isabela, Luzon'),
('Isabela II Electric Cooperative, Inc.', 'ISELCO II', 'Electric cooperative in Southern Isabela, Luzon'),
('Kalinga-Apayao Electric Cooperative, Inc.', 'KAELCO', 'Electric cooperative in Kalinga and Apayao, Luzon'),
('Lanao del Norte Electric Cooperative, Inc.', 'LANECO', 'Electric cooperative in Lanao del Norte, Mindanao'),
('Lanao del Sur Electric Cooperative, Inc.', 'LASURECO', 'Electric cooperative in Lanao del Sur, Mindanao'),
('Leyte II Electric Cooperative, Inc.', 'LEYECO II', 'Electric cooperative in Leyte (District II), Visayas'),
('Leyte III Electric Cooperative, Inc.', 'LEYECO III', 'Electric cooperative in Leyte (District III), Visayas'),
('Leyte IV Electric Cooperative, Inc.', 'LEYECO IV', 'Electric cooperative in Leyte (District IV), Visayas'),
('Leyte V Electric Cooperative, Inc.', 'LEYECO V', 'Electric cooperative in Leyte (District V), Visayas'),
('Lubang Electric Cooperative, Inc.', 'LUBELCO', 'Electric cooperative in Lubang Island, Luzon'),
('Maguindanao Electric Cooperative, Inc.', 'MAGELCO', 'Electric cooperative in Maguindanao, Mindanao'),
('Marinduque Electric Cooperative, Inc.', 'MARELCO', 'Electric cooperative in Marinduque, Luzon'),
('Masbate Electric Cooperative, Inc.', 'MASELCO', 'Electric cooperative in Masbate, Luzon'),
('Misamis Occidental I Electric Cooperative, Inc.', 'MOELCI I', 'Electric cooperative in Misamis Occidental (District I), Mindanao'),
('Misamis Occidental II Electric Cooperative, Inc.', 'MOELCI II', 'Electric cooperative in Misamis Occidental (District II), Mindanao'),
('Misamis Oriental I Electric Cooperative, Inc.', 'MORESCO I', 'Electric cooperative in Misamis Oriental (Zone I), Mindanao'),
('Misamis Oriental II Electric Cooperative, Inc.', 'MORESCO II', 'Electric cooperative in Misamis Oriental (Zone II), Mindanao'),
('Negros Occidental Electric Cooperative, Inc.', 'NOCECO', 'Electric cooperative in Negros Occidental, Visayas'),
('Negros Oriental I Electric Cooperative, Inc.', 'NORECO I', 'Electric cooperative in Negros Oriental (District I), Visayas'),
('Negros Oriental II Electric Cooperative, Inc.', 'NORECO II', 'Electric cooperative in Negros Oriental (District II), Visayas'),
('Northern Davao Electric Cooperative, Inc.', 'NORDECO', 'Electric cooperative in Northern Davao Region, Mindanao'),
('Northern Negros Electric Cooperative, Inc.', 'NONECO', 'Electric cooperative in Northern Negros, Visayas'),
('Northern Samar Electric Cooperative, Inc.', 'NORSAMELCO', 'Electric cooperative in Northern Samar, Visayas'),
('Nueva Ecija I Electric Cooperative, Inc.', 'NEECO I', 'Electric cooperative in Northern Nueva Ecija, Luzon'),
('Nueva Ecija II Electric Cooperative, Inc. – Area 1', 'NEECO II-A1', 'Electric cooperative in Nueva Ecija (Area I), Luzon'),
('Nueva Ecija II Electric Cooperative, Inc. – Area 2', 'NEECO II-A2', 'Electric cooperative in Nueva Ecija (Area II), Luzon'),
('Nueva Vizcaya Electric Cooperative, Inc.', 'NUVELCO', 'Electric cooperative in Nueva Vizcaya, Luzon'),
('Occidental Mindoro Electric Cooperative, Inc.', 'OMECO', 'Electric cooperative in Occidental Mindoro, Luzon'),
('Oriental Mindoro Electric Cooperative, Inc.', 'ORMECO', 'Electric cooperative in Oriental Mindoro, Luzon'),
('Palawan Electric Cooperative, Inc.', 'PALECO', 'Electric cooperative in mainland Palawan, Luzon'),
('Pampanga I Electric Cooperative, Inc.', 'PELCO I', 'Electric cooperative in Pampanga (District I), Luzon'),
('Pampanga II Electric Cooperative, Inc.', 'PELCO II', 'Electric cooperative in Pampanga (District II), Luzon'),
('Pampanga III Electric Cooperative, Inc.', 'PELCO III', 'Electric cooperative in Pampanga (District III), Luzon'),
('Pangasinan I Electric Cooperative, Inc.', 'PANELCO I', 'Electric cooperative in Western Pangasinan, Luzon'),
('Pangasinan II Electric Cooperative, Inc.', 'PANELCO II', 'Electric cooperative in Eastern Pangasinan, Luzon'),
('Peninsula Electric Cooperative, Inc.', 'PENELCO', 'Electric cooperative in Bataan, Luzon'),
('Province of Siquijor Electric Cooperative, Inc.', 'PROSIELCO', 'Electric cooperative in Siquijor, Visayas'),
('Quezon I Electric Cooperative, Inc.', 'QUEZELCO I', 'Electric cooperative in Quezon Province (District I), Luzon'),
('Quezon II Electric Cooperative, Inc.', 'QUEZELCO II', 'Electric cooperative in Quezon Province (District II), Luzon'),
('Quirino Electric Cooperative, Inc.', 'QUIRELCO', 'Electric cooperative in Quirino Province, Luzon'),
('Romblon Electric Cooperative, Inc.', 'ROMELCO', 'Electric cooperative in Romblon archipelago, Luzon'),
('Samar I Electric Cooperative, Inc.', 'SAMELCO I', 'Electric cooperative in Western Samar, Visayas'),
('Samar II Electric Cooperative, Inc.', 'SAMELCO II', 'Electric cooperative in Eastern Samar, Visayas'),
('San Jose City Electric Cooperative, Inc.', 'SAJELCO', 'Electric cooperative in San Jose City, Luzon'),
('Siargao Electric Cooperative, Inc.', 'SIARELCO', 'Electric cooperative in Siargao Island, Mindanao'),
('Siasi Electric Cooperative, Inc.', 'SIASELCO', 'Electric cooperative in Siasi, Sulu, Mindanao'),
('Sorsogon I Electric Cooperative, Inc.', 'SORECO I', 'Electric cooperative in Western Sorsogon, Luzon'),
('Sorsogon II Electric Cooperative, Inc.', 'SORECO II', 'Electric cooperative in Eastern Sorsogon, Luzon'),
('South Cotabato I Electric Cooperative, Inc.', 'SOCOTECO I', 'Electric cooperative in South Cotabato (District I), Mindanao'),
('South Cotabato II Electric Cooperative, Inc.', 'SOCOTECO II', 'Electric cooperative in South Cotabato (District II), Mindanao'),
('Southern Leyte Electric Cooperative, Inc.', 'SOLECO', 'Electric cooperative in Southern Leyte, Visayas'),
('Sultan Kudarat Electric Cooperative, Inc.', 'SUKELCO', 'Electric cooperative in Sultan Kudarat, Mindanao'),
('Sulu Electric Cooperative, Inc.', 'SULECO', 'Electric cooperative in Sulu Province, Mindanao'),
('Surigao del Norte Electric Cooperative, Inc.', 'SURNECO', 'Electric cooperative in Surigao del Norte, Mindanao'),
('Surigao del Sur I Electric Cooperative, Inc.', 'SURSECO I', 'Electric cooperative in Surigao del Sur (District I), Mindanao'),
('Surigao del Sur II Electric Cooperative, Inc.', 'SURSECO II', 'Electric cooperative in Surigao del Sur (District II), Mindanao'),
('Tablas Island Electric Cooperative, Inc.', 'TIELCO', 'Electric cooperative on Tablas Island, Luzon'),
('Tarlac I Electric Cooperative, Inc.', 'TARELCO I', 'Electric cooperative in Northern Tarlac, Luzon'),
('Tarlac II Electric Cooperative, Inc.', 'TARELCO II', 'Electric cooperative in Southern Tarlac, Luzon'),
('Tawi-Tawi Electric Cooperative, Inc.', 'TAWELCO', 'Electric cooperative in Tawi-Tawi, Mindanao'),
('Zamboanga del Sur I Electric Cooperative, Inc.', 'ZAMSURECO I', 'Electric cooperative in Zamboanga del Sur (District I), Mindanao'),
('Zamboanga del Sur II Electric Cooperative, Inc.', 'ZAMSURECO II', 'Electric cooperative in Zamboanga del Sur (District II), Mindanao'),
('Zamboanga del Norte Electric Cooperative, Inc.', 'ZANECO', 'Electric cooperative in Zamboanga del Norte, Mindanao'),
('Zamboanga City Electric Cooperative, Inc.', 'ZAMCELCO', 'Electric cooperative in Zamboanga City, Mindanao'),
('Zambales I Electric Cooperative, Inc.', 'ZAMECO I', 'Electric cooperative in Northern Zambales, Luzon'),
('Zambales II Electric Cooperative, Inc.', 'ZAMECO II', 'Electric cooperative in Southern Zambales, Luzon');


-- Dumping structure for table neafsad.items
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.manap
CREATE TABLE IF NOT EXISTS `manap` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ec` varchar(100) NOT NULL,
  `item` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `team` varchar(255) DEFAULT NULL,
  `recommending_approvals` varchar(500) DEFAULT NULL,
  `approving_authority` varchar(500) DEFAULT NULL,
  `control_point` varchar(1000) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.ppe
CREATE TABLE IF NOT EXISTS `ppe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `check_no` varchar(100) DEFAULT NULL,
  `dv_or_no` varchar(100) DEFAULT NULL,
  `debit` decimal(12,2) DEFAULT '0.00',
  `credit` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.ppe_funds
CREATE TABLE IF NOT EXISTS `ppe_funds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fund_name` varchar(255) NOT NULL,
  `remaining_balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fund_name` (`fund_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.
INSERT INTO ppe_funds (fund_name, remaining_balance) VALUES ('PPE Provident Fund', 0);

-- Dumping structure for table neafsad.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.teams
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.recommending_approvals
CREATE TABLE IF NOT EXISTS `recommending_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.staff
CREATE TABLE IF NOT EXISTS `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table neafsad.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','administrator','superadmin') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.
-- Insert sample users (password: admin123)
INSERT INTO users (username, full_name, password, role) VALUES ('admin', 'Administrator', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'administrator');
INSERT INTO users (username, full_name, password, role) VALUES ('admin123', 'Admin User', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'administrator');
INSERT INTO users (username, full_name, password, role) VALUES ('superadmin', 'Super Administrator', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'superadmin');
