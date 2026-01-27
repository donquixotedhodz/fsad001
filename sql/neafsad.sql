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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.approving_authority: ~0 rows (approximately)
INSERT INTO `approving_authority` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'AP 1', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(2, 'AP 2', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(3, 'AP 3', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.audit_logs: ~0 rows (approximately)
INSERT INTO `audit_logs` (`id`, `user_id`, `username`, `action`, `table_name`, `record_id`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
	(1, 3, 'superadmin', 'UPDATE', 'ppe_funds', 1, 'PPE Provident Fund balance updated via Remaining Balance page | Previous Balance: ₱0.00 | New Balance: ₱7,377,280.01 | Increased by: ₱7,377,280.01', '{"fund_name": "PPE Provident Fund", "remaining_balance": "0.00"}', '{"fund_name": "PPE Provident Fund", "remaining_balance": 7377280.01}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 01:58:31'),
	(2, 1, 'admin', 'UPDATE', 'ppe_funds', 1, 'PPE Provident Fund balance updated via Remaining Balance page | Previous Balance: ₱7,377,280.01 | New Balance: ₱7,377,280.02 | Increased by: ₱0.01', '{"fund_name": "PPE Provident Fund", "remaining_balance": "7377280.01"}', '{"fund_name": "PPE Provident Fund", "remaining_balance": 7377280.02}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 02:16:53'),
	(3, 1, 'admin', 'UPDATE', 'ppe_funds', 1, 'PPE Provident Fund balance updated via Remaining Balance page | Previous Balance: ₱7,377,280.02 | New Balance: ₱7,377,280.01 | Decreased by: ₱0.01', '{"fund_name": "PPE Provident Fund", "remaining_balance": "7377280.02"}', '{"fund_name": "PPE Provident Fund", "remaining_balance": 7377280.01}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 02:17:01'),
	(4, 1, 'admin', 'CREATE', 'manap', 1, 'Document uploaded: \'4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf\' | Electric Cooperative: Oriental Mindoro Electric Cooperative, Inc. | Items: ITEM 1, ITEM 2, ITEM 3 | Recommending Approvals: RA 1, RA 3 | Approving Authority: AP 1, AP 2, AP 3 | File: 4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', NULL, '{"ec": "Oriental Mindoro Electric Cooperative, Inc.", "item": "ITEM 1", "file_name": "4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf", "file_size": 5935444, "items_list": ["ITEM 1", "ITEM 2", "ITEM 3"], "approvals_list": ["RA 1", "", "RA 3"], "authority_list": ["AP 1", "AP 2", "AP 3"], "approving_authority": "AP 1", "recommending_approvals": "RA 1"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 05:20:39'),
	(5, 1, 'admin', 'CREATE', 'manap', 2, 'Document uploaded: \'4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf\' | Electric Cooperative: Oriental Mindoro Electric Cooperative, Inc. | Items: ITEM 1, ITEM 2, ITEM 3 | Recommending Approvals: RA 1, RA 3 | Approving Authority: AP 1, AP 2, AP 3 | File: 4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', NULL, '{"ec": "Oriental Mindoro Electric Cooperative, Inc.", "item": "ITEM 2", "file_name": "4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf", "file_size": 5935444, "items_list": ["ITEM 1", "ITEM 2", "ITEM 3"], "approvals_list": ["RA 1", "", "RA 3"], "authority_list": ["AP 1", "AP 2", "AP 3"], "approving_authority": "AP 2", "recommending_approvals": ""}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 05:20:39'),
	(6, 1, 'admin', 'CREATE', 'manap', 3, 'Document uploaded: \'4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf\' | Electric Cooperative: Oriental Mindoro Electric Cooperative, Inc. | Items: ITEM 1, ITEM 2, ITEM 3 | Recommending Approvals: RA 1, RA 3 | Approving Authority: AP 1, AP 2, AP 3 | File: 4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', NULL, '{"ec": "Oriental Mindoro Electric Cooperative, Inc.", "item": "ITEM 3", "file_name": "4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf", "file_size": 5935444, "items_list": ["ITEM 1", "ITEM 2", "ITEM 3"], "approvals_list": ["RA 1", "", "RA 3"], "authority_list": ["AP 1", "AP 2", "AP 3"], "approving_authority": "AP 3", "recommending_approvals": "RA 3"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 05:20:39');

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

-- Dumping data for table neafsad.electric_cooperatives: ~56 rows (approximately)
INSERT INTO `electric_cooperatives` (`id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'Abra Electric Cooperative, Inc.', 'ABRECO', 'Electric cooperative in Abra, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(2, 'Agusan del Norte Electric Cooperative, Inc.', 'ANECO', 'Electric cooperative in Agusan del Norte, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(3, 'Agusan del Sur Electric Cooperative, Inc.', 'ASELCO', 'Electric cooperative in Agusan del Sur, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(4, 'Aklan Electric Cooperative, Inc.', 'AKELCO', 'Electric cooperative in Aklan, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(5, 'Albay Electric Cooperative, Inc.', 'ALECO', 'Electric cooperative in Albay, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(6, 'Antique Electric Cooperative, Inc.', 'ANTECO', 'Electric cooperative in Antique, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(7, 'Aurora Electric Cooperative, Inc.', 'AURELCO', 'Electric cooperative in Aurora, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(8, 'Bantayan Electric Cooperative, Inc.', 'BANELCO', 'Electric cooperative serving Bantayan Island, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(9, 'Basilan Electric Cooperative, Inc.', 'BASELCO', 'Electric cooperative in Basilan, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(10, 'Batanes Electric Cooperative, Inc.', 'BATANELCO', 'Electric cooperative in Batanes, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(11, 'Batangas I Electric Cooperative, Inc.', 'BATELEC I', 'Electric cooperative in Western Batangas, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(12, 'Batangas II Electric Cooperative, Inc.', 'BATELEC II', 'Electric cooperative in Eastern Batangas, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(13, 'Benguet Electric Cooperative, Inc.', 'BENECO', 'Electric cooperative in Benguet and Baguio, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(14, 'Biliran Electric Cooperative, Inc.', 'BILECO', 'Electric cooperative in Biliran, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(15, 'Bohol I Electric Cooperative, Inc.', 'BOHECO I', 'Electric cooperative in Bohol (District I), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(16, 'Bohol II Electric Cooperative, Inc.', 'BOHECO II', 'Electric cooperative in Bohol (District II), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(17, 'Bukidnon Second Electric Cooperative, Inc.', 'BUSECO', 'Electric cooperative in Northern Bukidnon, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(18, 'Busuanga Island Electric Cooperative, Inc.', 'BISELCO', 'Electric cooperative in Busuanga Island, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(19, 'Cagayan I Electric Cooperative, Inc.', 'CAGELCO I', 'Electric cooperative in Western Cagayan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(20, 'Cagayan II Electric Cooperative, Inc.', 'CAGELCO II', 'Electric cooperative in Eastern Cagayan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(21, 'Camarines Norte Electric Cooperative, Inc.', 'CANORECO', 'Electric cooperative in Camarines Norte, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(22, 'Camarines Sur I Electric Cooperative, Inc.', 'CASURECO I', 'Electric cooperative in Camarines Sur (District I), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(23, 'Camarines Sur II Electric Cooperative, Inc.', 'CASURECO II', 'Electric cooperative in Camarines Sur (District II), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(24, 'Camarines Sur III Electric Cooperative, Inc.', 'CASURECO III', 'Electric cooperative in Camarines Sur (District III), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(25, 'Camarines Sur IV Electric Cooperative, Inc.', 'CASURECO IV', 'Electric cooperative in Camarines Sur (District IV), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(26, 'Camiguin Electric Cooperative, Inc.', 'CAMELCO', 'Electric cooperative in Camiguin, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(27, 'Capiz Electric Cooperative, Inc.', 'CAPELCO', 'Electric cooperative in Capiz, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(28, 'Cebu I Electric Cooperative, Inc.', 'CEBECO I', 'Electric cooperative in Cebu (District I), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(29, 'Cebu II Electric Cooperative, Inc.', 'CEBECO II', 'Electric cooperative in Cebu (District II), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(30, 'Cebu III Electric Cooperative, Inc.', 'CEBECO III', 'Electric cooperative in Cebu (District III), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(31, 'Central Pangasinan Electric Cooperative, Inc.', 'CENPELCO', 'Electric cooperative in Central Pangasinan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(32, 'Cotabato Electric Cooperative, Inc.', 'COTELCO', 'Electric cooperative in Cotabato, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(33, 'Cotabato Electric Cooperative – PPALMA', 'COTELCO-PPALMA', 'Electric cooperative serving PPALMA area, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(34, 'Davao Oriental Electric Cooperative, Inc.', 'DORECO', 'Electric cooperative in Davao Oriental, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(35, 'Davao del Sur Electric Cooperative, Inc.', 'DASURECO', 'Electric cooperative in Davao del Sur, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(36, 'Dinagat Island Electric Cooperative, Inc.', 'DIELCO', 'Electric cooperative in Dinagat Islands, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(37, 'First Bukidnon Electric Cooperative, Inc.', 'FIBECO', 'Electric cooperative in Southern Bukidnon, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(38, 'First Catanduanes Electric Cooperative, Inc.', 'FICELCO', 'Electric cooperative in Catanduanes, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(39, 'First Laguna Electric Cooperative, Inc.', 'FLECO', 'Electric cooperative in Laguna, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(40, 'Guimaras Electric Cooperative, Inc.', 'GUIMELCO', 'Electric cooperative in Guimaras, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(41, 'Ifugao Electric Cooperative, Inc.', 'IFELCO', 'Electric cooperative in Ifugao, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(42, 'Isabela I Electric Cooperative, Inc.', 'ISELCO I', 'Electric cooperative in Northern Isabela, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(43, 'Isabela II Electric Cooperative, Inc.', 'ISELCO II', 'Electric cooperative in Southern Isabela, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(44, 'Kalinga-Apayao Electric Cooperative, Inc.', 'KAELCO', 'Electric cooperative in Kalinga and Apayao, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(45, 'Lanao del Norte Electric Cooperative, Inc.', 'LANECO', 'Electric cooperative in Lanao del Norte, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(46, 'Lanao del Sur Electric Cooperative, Inc.', 'LASURECO', 'Electric cooperative in Lanao del Sur, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(47, 'Leyte II Electric Cooperative, Inc.', 'LEYECO II', 'Electric cooperative in Leyte (District II), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(48, 'Leyte III Electric Cooperative, Inc.', 'LEYECO III', 'Electric cooperative in Leyte (District III), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(49, 'Leyte IV Electric Cooperative, Inc.', 'LEYECO IV', 'Electric cooperative in Leyte (District IV), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(50, 'Leyte V Electric Cooperative, Inc.', 'LEYECO V', 'Electric cooperative in Leyte (District V), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(51, 'Lubang Electric Cooperative, Inc.', 'LUBELCO', 'Electric cooperative in Lubang Island, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(52, 'Maguindanao Electric Cooperative, Inc.', 'MAGELCO', 'Electric cooperative in Maguindanao, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(53, 'Marinduque Electric Cooperative, Inc.', 'MARELCO', 'Electric cooperative in Marinduque, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(54, 'Masbate Electric Cooperative, Inc.', 'MASELCO', 'Electric cooperative in Masbate, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(55, 'Misamis Occidental I Electric Cooperative, Inc.', 'MOELCI I', 'Electric cooperative in Misamis Occidental (District I), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(56, 'Misamis Occidental II Electric Cooperative, Inc.', 'MOELCI II', 'Electric cooperative in Misamis Occidental (District II), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(57, 'Misamis Oriental I Electric Cooperative, Inc.', 'MORESCO I', 'Electric cooperative in Misamis Oriental (Zone I), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(58, 'Misamis Oriental II Electric Cooperative, Inc.', 'MORESCO II', 'Electric cooperative in Misamis Oriental (Zone II), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(59, 'Negros Occidental Electric Cooperative, Inc.', 'NOCECO', 'Electric cooperative in Negros Occidental, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(60, 'Negros Oriental I Electric Cooperative, Inc.', 'NORECO I', 'Electric cooperative in Negros Oriental (District I), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(61, 'Negros Oriental II Electric Cooperative, Inc.', 'NORECO II', 'Electric cooperative in Negros Oriental (District II), Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(62, 'Northern Davao Electric Cooperative, Inc.', 'NORDECO', 'Electric cooperative in Northern Davao Region, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(63, 'Northern Negros Electric Cooperative, Inc.', 'NONECO', 'Electric cooperative in Northern Negros, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(64, 'Northern Samar Electric Cooperative, Inc.', 'NORSAMELCO', 'Electric cooperative in Northern Samar, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(65, 'Nueva Ecija I Electric Cooperative, Inc.', 'NEECO I', 'Electric cooperative in Northern Nueva Ecija, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(66, 'Nueva Ecija II Electric Cooperative, Inc. – Area 1', 'NEECO II-A1', 'Electric cooperative in Nueva Ecija (Area I), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(67, 'Nueva Ecija II Electric Cooperative, Inc. – Area 2', 'NEECO II-A2', 'Electric cooperative in Nueva Ecija (Area II), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(68, 'Nueva Vizcaya Electric Cooperative, Inc.', 'NUVELCO', 'Electric cooperative in Nueva Vizcaya, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(69, 'Occidental Mindoro Electric Cooperative, Inc.', 'OMECO', 'Electric cooperative in Occidental Mindoro, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(70, 'Oriental Mindoro Electric Cooperative, Inc.', 'ORMECO', 'Electric cooperative in Oriental Mindoro, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(71, 'Palawan Electric Cooperative, Inc.', 'PALECO', 'Electric cooperative in mainland Palawan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(72, 'Pampanga I Electric Cooperative, Inc.', 'PELCO I', 'Electric cooperative in Pampanga (District I), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(73, 'Pampanga II Electric Cooperative, Inc.', 'PELCO II', 'Electric cooperative in Pampanga (District II), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(74, 'Pampanga III Electric Cooperative, Inc.', 'PELCO III', 'Electric cooperative in Pampanga (District III), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(75, 'Pangasinan I Electric Cooperative, Inc.', 'PANELCO I', 'Electric cooperative in Western Pangasinan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(76, 'Pangasinan II Electric Cooperative, Inc.', 'PANELCO II', 'Electric cooperative in Eastern Pangasinan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(77, 'Peninsula Electric Cooperative, Inc.', 'PENELCO', 'Electric cooperative in Bataan, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(78, 'Province of Siquijor Electric Cooperative, Inc.', 'PROSIELCO', 'Electric cooperative in Siquijor, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(79, 'Quezon I Electric Cooperative, Inc.', 'QUEZELCO I', 'Electric cooperative in Quezon Province (District I), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(80, 'Quezon II Electric Cooperative, Inc.', 'QUEZELCO II', 'Electric cooperative in Quezon Province (District II), Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(81, 'Quirino Electric Cooperative, Inc.', 'QUIRELCO', 'Electric cooperative in Quirino Province, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(82, 'Romblon Electric Cooperative, Inc.', 'ROMELCO', 'Electric cooperative in Romblon archipelago, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(83, 'Samar I Electric Cooperative, Inc.', 'SAMELCO I', 'Electric cooperative in Western Samar, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(84, 'Samar II Electric Cooperative, Inc.', 'SAMELCO II', 'Electric cooperative in Eastern Samar, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(85, 'San Jose City Electric Cooperative, Inc.', 'SAJELCO', 'Electric cooperative in San Jose City, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(86, 'Siargao Electric Cooperative, Inc.', 'SIARELCO', 'Electric cooperative in Siargao Island, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(87, 'Siasi Electric Cooperative, Inc.', 'SIASELCO', 'Electric cooperative in Siasi, Sulu, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(88, 'Sorsogon I Electric Cooperative, Inc.', 'SORECO I', 'Electric cooperative in Western Sorsogon, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(89, 'Sorsogon II Electric Cooperative, Inc.', 'SORECO II', 'Electric cooperative in Eastern Sorsogon, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(90, 'South Cotabato I Electric Cooperative, Inc.', 'SOCOTECO I', 'Electric cooperative in South Cotabato (District I), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(91, 'South Cotabato II Electric Cooperative, Inc.', 'SOCOTECO II', 'Electric cooperative in South Cotabato (District II), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(92, 'Southern Leyte Electric Cooperative, Inc.', 'SOLECO', 'Electric cooperative in Southern Leyte, Visayas', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(93, 'Sultan Kudarat Electric Cooperative, Inc.', 'SUKELCO', 'Electric cooperative in Sultan Kudarat, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(94, 'Sulu Electric Cooperative, Inc.', 'SULECO', 'Electric cooperative in Sulu Province, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(95, 'Surigao del Norte Electric Cooperative, Inc.', 'SURNECO', 'Electric cooperative in Surigao del Norte, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(96, 'Surigao del Sur I Electric Cooperative, Inc.', 'SURSECO I', 'Electric cooperative in Surigao del Sur (District I), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(97, 'Surigao del Sur II Electric Cooperative, Inc.', 'SURSECO II', 'Electric cooperative in Surigao del Sur (District II), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(98, 'Tablas Island Electric Cooperative, Inc.', 'TIELCO', 'Electric cooperative on Tablas Island, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(99, 'Tarlac I Electric Cooperative, Inc.', 'TARELCO I', 'Electric cooperative in Northern Tarlac, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(100, 'Tarlac II Electric Cooperative, Inc.', 'TARELCO II', 'Electric cooperative in Southern Tarlac, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(101, 'Tawi-Tawi Electric Cooperative, Inc.', 'TAWELCO', 'Electric cooperative in Tawi-Tawi, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(102, 'Zamboanga del Sur I Electric Cooperative, Inc.', 'ZAMSURECO I', 'Electric cooperative in Zamboanga del Sur (District I), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(103, 'Zamboanga del Sur II Electric Cooperative, Inc.', 'ZAMSURECO II', 'Electric cooperative in Zamboanga del Sur (District II), Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(104, 'Zamboanga del Norte Electric Cooperative, Inc.', 'ZANECO', 'Electric cooperative in Zamboanga del Norte, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(105, 'Zamboanga City Electric Cooperative, Inc.', 'ZAMCELCO', 'Electric cooperative in Zamboanga City, Mindanao', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(106, 'Zambales I Electric Cooperative, Inc.', 'ZAMECO I', 'Electric cooperative in Northern Zambales, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48'),
	(107, 'Zambales II Electric Cooperative, Inc.', 'ZAMECO II', 'Electric cooperative in Southern Zambales, Luzon', '2026-01-26 02:01:48', '2026-01-26 02:01:48');

-- Dumping structure for table neafsad.items
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.items: ~0 rows (approximately)
INSERT INTO `items` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'ITEM 1', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(2, 'ITEM 2', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(3, 'ITEM 3', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.manap: ~0 rows (approximately)
INSERT INTO `manap` (`id`, `ec`, `item`, `recommending_approvals`, `approving_authority`, `control_point`, `file_path`, `file_name`, `created_at`, `updated_at`) VALUES
	(1, 'Oriental Mindoro Electric Cooperative, Inc.', 'ITEM 1', 'RA 1', 'AP 1', '1. CP 1\n2. CP 2\n3. CP 3', 'uploads/6976f9a795954_1769404839_0.pdf', '4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(2, 'Oriental Mindoro Electric Cooperative, Inc.', 'ITEM 2', '', 'AP 2', '1. CP 1\n2. CP 2\n3. CP 3', 'uploads/6976f9a795954_1769404839_0.pdf', '4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(3, 'Oriental Mindoro Electric Cooperative, Inc.', 'ITEM 3', 'RA 3', 'AP 3', '1. CP 1\n2. CP 2\n3. CP 3', 'uploads/6976f9a795954_1769404839_0.pdf', '4. Appendix B_MANAP Compilation_PENELCO - FINAL.pdf', '2026-01-26 05:20:39', '2026-01-26 05:20:39');

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.ppe: ~0 rows (approximately)
INSERT INTO `ppe` (`id`, `date`, `particulars`, `check_no`, `dv_or_no`, `debit`, `credit`, `balance`, `created_at`, `updated_at`) VALUES
	(5, '2025-09-10', 'INITIAL DEPOSIT', 'ONLINE', '', 0.00, 20000.00, 20000.00, '2026-01-26 05:07:00', '2026-01-26 05:07:00'),
	(6, '2025-09-30', 'TAX WITHHELD', 'ONLINE', '', 0.12, 0.00, 19999.88, '2026-01-26 05:07:36', '2026-01-26 05:07:36'),
	(7, '2025-09-30', 'INTEREST', 'ONLINE', '', 0.00, 0.58, 20000.46, '2026-01-26 05:08:22', '2026-01-26 05:10:17'),
	(8, '2025-09-30', 'REMITTANCE (FEBRURARY - SEPTEMBER 2025)', 'ONLINE', '0001', 0.00, 9905173.71, 9925174.17, '2026-01-26 05:12:29', '2026-01-26 05:12:29'),
	(9, '2025-10-20', 'CHRISTINE MARIE P HORNED', '69001', '2025-10-001', 60000.00, 0.00, 9865174.17, '2026-01-26 05:18:40', '2026-01-26 05:18:40');

-- Dumping structure for table neafsad.ppe_funds
CREATE TABLE IF NOT EXISTS `ppe_funds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fund_name` varchar(255) NOT NULL,
  `remaining_balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fund_name` (`fund_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.ppe_funds: ~0 rows (approximately)

-- Dumping structure for table neafsad.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.departments: ~0 rows (approximately)

-- Dumping structure for table neafsad.teams
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.teams: ~0 rows (approximately)

-- Dumping structure for table neafsad.recommending_approvals
CREATE TABLE IF NOT EXISTS `recommending_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.recommending_approvals: ~0 rows (approximately)
INSERT INTO `recommending_approvals` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'RA 1', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39'),
	(2, 'RA 3', 'Added via document upload', '2026-01-26 05:20:39', '2026-01-26 05:20:39');

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

-- Dumping data for table neafsad.staff: ~1 rows (approximately)
INSERT INTO `staff` (`id`, `name`, `position`, `department`, `email`, `phone`, `hire_date`, `username`, `password`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Carm Lea Agustin', 'OJT', 'IAQSMO', NULL, NULL, NULL, 'ojt', '$2y$10$2grkX8ZhLfiEQVzJACrkSOOnXtE3NXR9E5KoJ4z2srD0hOM6XRgXG', 'active', '2026-01-26 01:53:18', '2026-01-26 01:53:18');

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

-- Dumping data for table neafsad.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `username`, `full_name`, `password`, `role`, `created_at`, `updated_at`) VALUES
	(1, 'admin', 'Administrator', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'administrator', '2026-01-26 01:51:53', '2026-01-26 01:51:53'),
	(2, 'admin123', 'Admin User', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'administrator', '2026-01-26 01:51:53', '2026-01-26 01:51:53'),
	(3, 'superadmin', 'Super Administrator', '$2y$10$axkqKPyAS51uTjR4dNpAdexvZvqTYJ9PlcsVYW/LNRzfXqJMySP3W', 'superadmin', '2026-01-26 01:51:53', '2026-01-26 01:51:53');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
