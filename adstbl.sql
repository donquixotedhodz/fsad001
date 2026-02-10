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

-- Dumping structure for table neafsad.ads
CREATE TABLE IF NOT EXISTS `ads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `audit_report` varchar(255) NOT NULL,
  `scope` text,
  `bac_date` date DEFAULT NULL,
  `bac_reso` varchar(255) DEFAULT NULL,
  `boa_date` date DEFAULT NULL,
  `boa_reso` varchar(255) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_report` (`audit_report`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.ads: ~42 rows (approximately)
DELETE FROM `ads`;
INSERT INTO `ads` (`id`, `audit_report`, `scope`, `bac_date`, `bac_reso`, `boa_date`, `boa_reso`, `remarks`, `created_at`, `updated_at`) VALUES
	(1, 'Inventory Audit of IT Equipment as of December 31, 2022', 'as of December 31, 2022', '2024-03-26', '03', '2024-03-26', '', '2023 4th quarter target', '2026-02-04 01:59:24', '2026-02-04 02:11:10'),
	(3, 'Audit of Repairs & Maintenance-Motor Vehicles January 1, 2015  to March 31, 2015', 'January 1, 2015- March 31, 2015', '2017-02-27', '22', NULL, '', '', '2026-02-04 02:37:50', '2026-02-04 02:37:50'),
	(4, 'Audit of Liquidations of SEP Subsidy Funds- CANORECO', 'CY 2013- 2015 (224 sitios)', '2017-02-27', '24', NULL, '', '', '2026-02-04 02:41:48', '2026-02-04 02:41:48'),
	(5, 'Audit of Liquidations of SEP Subsidy Funds- SORECO I SEP 2013 Batch 1 (154 sitios)', 'SEP Batch 1 2013, 154 sitios', '2017-02-27', '25', NULL, '', '', '2026-02-04 02:42:34', '2026-02-04 02:42:34'),
	(6, 'Operations Audit on Repairs and Maintenance Procedures of Motor Vehicles and Status of Motor Vehicles for Disposal', '', '2017-05-19', '28', NULL, '', '', '2026-02-04 02:42:56', '2026-02-04 02:42:56'),
	(7, 'Audit of Other Receivables- Advertisement', 'January 2008- December 2016', '2017-08-09', '60', NULL, '', '', '2026-02-04 02:43:24', '2026-02-04 02:43:24'),
	(8, 'Monitoring and evaluation of audit recommendation on Audit of Travelling Expenses-Local', 'January 1 to March 31, 2016', '2017-09-19', '70/71', NULL, '', '', '2026-02-04 02:48:59', '2026-02-04 02:49:56'),
	(9, 'Report on the  Revised Guidelines/Procedures and Accounting System of NEA Training Funds', '', '2017-10-18', '75', NULL, '', '', '2026-02-04 02:49:43', '2026-02-04 02:49:43'),
	(10, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to ORMECO under SEP Year 2012 Batch 1', 'Sep Year 2012 Batch 1', '2017-11-27', '77', NULL, '', '', '2026-02-04 02:51:01', '2026-02-04 02:51:01'),
	(11, 'Audit of Subsidy Fund for FIBECO', 'SEP Year 2015 Batch 1', '2017-11-27', '79', NULL, '', '', '2026-02-04 02:51:23', '2026-02-04 02:51:23'),
	(12, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to  LUBELCO under SEP Year 2014  Batch 1 (22 sitios)', 'SEP  Year 2014 Batch I for 22 sitios', '2018-01-16', '5', '2018-01-17', '18', '', '2026-02-04 02:52:16', '2026-02-04 02:52:16'),
	(13, 'Audit of  Janitorial Services', 'January to March 2017', '2018-02-19', '8', '2018-02-20', '37', '', '2026-02-04 02:54:36', '2026-02-04 02:55:19'),
	(14, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to  LUBELCO under SEP Year 2013  Batch 1 ( 55 sitios)', 'SEP  Year 2015 Batch I for  55  sitios', '2018-01-16', '6', '2018-01-17', '18', '', '2026-02-04 02:56:06', '2026-02-04 02:56:06'),
	(15, 'Audit of Other Prepaid Expenses', 'January 2012 to March 31,2017', '2018-05-29', '13', '2018-05-30', '115', '', '2026-02-04 05:10:25', '2026-02-04 05:10:25'),
	(16, 'Inventory of ADCOM Cases', 'Unresolved cases  (24 ) as  of April 6, 2018', '2018-04-19', '', NULL, 'for information', '', '2026-02-04 05:11:09', '2026-02-04 05:11:09'),
	(17, 'Inventory of Active/Pending Cases as of May 15, 2018-Legal Service Office', 'Unresolved cases 58 as of May 18, 2018', '2018-05-29', '', '2018-05-30', '115', '', '2026-02-04 05:12:01', '2026-02-04 05:12:01'),
	(18, 'Audit  of Other Prepaid Expenses for January 2012 to March 31, 2017', 'January 2012- March 31, 2017', '2018-05-29', '13', '2018-05-30', '115', '', '2026-02-04 05:12:42', '2026-02-04 05:12:42'),
	(19, 'Revised Guidelines on Travel Expenses', '', '2017-06-26', '17', '2018-06-27', '134', '', '2026-02-04 05:13:20', '2026-02-04 05:13:20'),
	(20, 'Monitoring report on recommendations in the Audit of Other Receivables- Advertisement (various Electric Cooperatives ) for the period January 2008 to December 2016', 'January 2008 to December 2016', '2018-07-24', '19', '2018-07-25', '147', '', '2026-02-04 05:32:05', '2026-02-04 05:32:05'),
	(21, 'Management Compliance &  Responses to Audit Observation Memoranda of the Commission on Audit issued  February 20, 2018 to May 31, 2018', 'February 20, 2018 to May 31, 2018', '2018-07-24', '', '2018-07-25', '', '', '2026-02-04 05:32:41', '2026-02-04 05:32:41'),
	(22, 'Guidelines on Per Diems and Other Compensation entitlements of Members of Board of NEA', '', '2018-08-29', '21', '2018-08-29', '170', '', '2026-02-04 05:39:23', '2026-02-04 05:39:23'),
	(23, 'Monitoring report on recommendations in the audit of other prepaid expenses', 'January 2012- March 31, 2017', '2018-09-20', '23', '2018-09-21', '185', '', '2026-02-04 05:40:02', '2026-02-04 05:40:02'),
	(24, 'Audit of Consultancy Services', 'January 1 to December 31, 2017', '2018-10-22', '24', '2018-10-23', '198', '', '2026-02-04 05:40:51', '2026-02-04 05:40:51'),
	(25, 'Special Assignment', '', NULL, '', NULL, '', '', '2026-02-04 05:40:59', '2026-02-04 05:40:59'),
	(26, 'One Messenger, One Janitor Scheme', 'dated November 21, 2018', NULL, '', NULL, '', '', '2026-02-04 05:41:24', '2026-02-04 05:41:24'),
	(27, 'Monitoring Report on Recommendations in the Audit of Subsidy Funds Released to FIBECO for 196 Sitios under SEP year 2015', 'Year 2015  Batch 1', '2019-01-23', '3', '2019-01-24', '32', '', '2026-02-04 05:44:56', '2026-02-04 05:44:56'),
	(28, 'Audit of Due to GSIS', 'January 1 2018  to March 2018', '2019-02-26', '6', '2019-02-27', '48', '', '2026-02-04 05:45:43', '2026-02-04 05:45:43'),
	(29, 'Audit of Due to  BIR -Witholding Tax', 'January 1 2018  to March 2018', '2019-03-26', '7', '2019-03-26', '61', '', '2026-02-04 05:46:22', '2026-02-04 05:46:22'),
	(30, 'Amendments to the Revised Guidelines and Accounting System of NEA Training Funds', '', '2019-03-26', '8', '2019-03-26', '62', '', '2026-02-04 05:46:49', '2026-02-04 05:46:49'),
	(31, 'Monitoring of Recommendations in the Audit of Janitorial Services for the period January to March 2017', 'January 1 2017  to March 2017', '2019-04-26', '7', '2019-04-29', 'for information', '', '2026-02-04 05:47:23', '2026-02-04 05:47:23'),
	(32, 'Audit of Telephone Expenses- Mobile for the period January to June 2018', 'January 1 2018  to June 2018', '2019-04-26', '10', '2019-04-29', 'for information', '', '2026-02-04 05:47:58', '2026-02-04 05:47:58'),
	(33, 'Monitoring and evaluation of compliance to audit Recommendations on the Audit Due to BIR account', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:00:19', '2026-02-06 08:01:01'),
	(34, 'Audit of Petty Cash Fund', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:00:25', '2026-02-06 08:01:11'),
	(35, 'Audit of PHILRECA\'s Loan with NEA', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:00:33', '2026-02-06 08:01:19'),
	(36, 'Audit of Pag-IBIG Contributions and Loans', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:00:39', '2026-02-06 08:01:27'),
	(37, 'Audit of Philhealth Contributions', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:00:45', '2026-02-06 08:01:42'),
	(38, 'Formulation of Internal Audit Workflow under New Normal', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:01:54', '2026-02-06 08:01:54'),
	(39, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:02:04', '2026-02-06 08:02:04'),
	(40, 'Audit of Hazard Pay', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:02:14', '2026-02-06 08:02:14'),
	(41, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', '', '2020-01-10', '', NULL, '', '', '2026-02-06 08:02:25', '2026-02-06 08:02:25'),
	(42, 'Review of Internal Audit Manual', '', '2020-01-01', '', NULL, '', '', '2026-02-06 08:02:43', '2026-02-06 08:02:43'),
	(43, 'Report on the Audit of Covid - 19 Hazard Pay', '', '2021-02-18', '3', '2021-02-18', '3', '4th Qtr 2020 Target', '2026-02-06 08:03:55', '2026-02-06 08:03:55'),
	(44, 'IAQSMO Standard Operating Policies and Procedures Manual', '', '2021-02-18', '4', '2021-02-18', '4', '4th Qtr 2020 Target', '2026-02-06 08:04:31', '2026-02-06 08:04:31');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
