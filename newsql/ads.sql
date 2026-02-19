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
  `adsyear` year DEFAULT NULL,
  `scope` text,
  `bac_date` date DEFAULT NULL,
  `bac_reso` varchar(255) DEFAULT NULL,
  `boa_date` date DEFAULT NULL,
  `boa_reso` varchar(255) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.ads: ~94 rows (approximately)
DELETE FROM `ads`;
INSERT INTO `ads` (`id`, `audit_report`, `adsyear`, `scope`, `bac_date`, `bac_reso`, `boa_date`, `boa_reso`, `remarks`, `created_at`, `updated_at`) VALUES
	(3, 'Audit of Repairs & Maintenance-Motor Vehicles January 1, 2015  to March 31, 2015', '2017', 'January 1, 2015- March 31, 2015', '2017-02-27', '22', NULL, '', '', '2026-02-04 02:37:50', '2026-02-11 00:34:44'),
	(4, 'Audit of Liquidations of SEP Subsidy Funds- CANORECO', '2017', 'CY 2013- 2015 (224 sitios)', '2017-02-27', '24', NULL, '', '', '2026-02-04 02:41:48', '2026-02-11 00:34:34'),
	(5, 'Audit of Liquidations of SEP Subsidy Funds- SORECO I SEP 2013 Batch 1 (154 sitios)', '2017', 'SEP Batch 1 2013, 154 sitios', '2017-02-27', '25', NULL, '', '', '2026-02-04 02:42:34', '2026-02-11 00:34:22'),
	(6, 'Operations Audit on Repairs and Maintenance Procedures of Motor Vehicles and Status of Motor Vehicles for Disposal', '2017', '', '2017-05-19', '28', NULL, '', '', '2026-02-04 02:42:56', '2026-02-11 00:34:10'),
	(7, 'Audit of Other Receivables- Advertisement', '2017', 'January 2008- December 2016', '2017-08-09', '60', NULL, '', '', '2026-02-04 02:43:24', '2026-02-11 00:33:56'),
	(8, 'Monitoring and evaluation of audit recommendation on Audit of Travelling Expenses-Local', '2017', 'January 1 to March 31, 2016', '2017-09-19', '70/71', NULL, '', '', '2026-02-04 02:48:59', '2026-02-11 00:33:31'),
	(9, 'Report on the  Revised Guidelines/Procedures and Accounting System of NEA Training Funds', '2017', '', '2017-10-18', '75', NULL, '', '', '2026-02-04 02:49:43', '2026-02-11 00:28:36'),
	(10, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to ORMECO under SEP Year 2012 Batch 1', '2017', 'Sep Year 2012 Batch 1', '2017-11-27', '77', NULL, '', '', '2026-02-04 02:51:01', '2026-02-11 00:28:20'),
	(11, 'Audit of Subsidy Fund for FIBECO', '2017', 'SEP Year 2015 Batch 1', '2017-11-27', '79', NULL, '', '', '2026-02-04 02:51:23', '2026-02-11 00:27:10'),
	(12, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to  LUBELCO under SEP Year 2014  Batch 1 (22 sitios)', '2018', 'SEP  Year 2014 Batch I for 22 sitios', '2018-01-16', '5', '2018-01-17', '18', '', '2026-02-04 02:52:16', '2026-02-11 00:35:01'),
	(13, 'Audit of  Janitorial Services', '2018', 'January to March 2017', '2018-02-19', '8', '2018-02-20', '37', '', '2026-02-04 02:54:36', '2026-02-11 00:35:24'),
	(14, 'Monitoring  Report on Recommendations in Audit of Subsidy Released to  LUBELCO under SEP Year 2013  Batch 1 ( 55 sitios)', '2018', 'SEP  Year 2015 Batch I for  55  sitios', '2018-01-16', '6', '2018-01-17', '18', '', '2026-02-04 02:56:06', '2026-02-11 00:35:14'),
	(15, 'Audit of Other Prepaid Expenses', '2018', 'January 2012 to March 31,2017', '2018-05-29', '13', '2018-05-30', '115', '', '2026-02-04 05:10:25', '2026-02-11 00:35:40'),
	(16, 'Inventory of ADCOM Cases', '2018', 'Unresolved cases  (24 ) as  of April 6, 2018', '2018-04-19', '', NULL, 'for information', '', '2026-02-04 05:11:09', '2026-02-11 00:35:53'),
	(17, 'Inventory of Active/Pending Cases as of May 15, 2018-Legal Service Office', '2018', 'Unresolved cases 58 as of May 18, 2018', '2018-05-29', '', '2018-05-30', '115', '', '2026-02-04 05:12:01', '2026-02-11 00:36:11'),
	(18, 'Audit  of Other Prepaid Expenses for January 2012 to March 31, 2017', '2018', 'January 2012- March 31, 2017', '2018-05-29', '13', '2018-05-30', '115', '', '2026-02-04 05:12:42', '2026-02-11 00:36:20'),
	(19, 'Revised Guidelines on Travel Expenses', '2018', '', '2017-06-26', '17', '2018-06-27', '134', '', '2026-02-04 05:13:20', '2026-02-11 00:36:30'),
	(20, 'Monitoring report on recommendations in the Audit of Other Receivables- Advertisement (various Electric Cooperatives ) for the period January 2008 to December 2016', '2018', 'January 2008 to December 2016', '2018-07-24', '19', '2018-07-25', '147', '', '2026-02-04 05:32:05', '2026-02-11 00:36:39'),
	(21, 'Management Compliance &  Responses to Audit Observation Memoranda of the Commission on Audit issued  February 20, 2018 to May 31, 2018', '2018', 'February 20, 2018 to May 31, 2018', '2018-07-24', '', '2018-07-25', '', '', '2026-02-04 05:32:41', '2026-02-11 00:36:51'),
	(22, 'Guidelines on Per Diems and Other Compensation entitlements of Members of Board of NEA', '2018', '', '2018-08-29', '21', '2018-08-29', '170', '', '2026-02-04 05:39:23', '2026-02-11 00:37:02'),
	(23, 'Monitoring report on recommendations in the audit of other prepaid expenses', '2018', 'January 2012- March 31, 2017', '2018-09-20', '23', '2018-09-21', '185', '', '2026-02-04 05:40:02', '2026-02-11 00:37:11'),
	(24, 'Audit of Consultancy Services', '2018', 'January 1 to December 31, 2017', '2018-10-22', '24', '2018-10-23', '198', '', '2026-02-04 05:40:51', '2026-02-11 00:37:20'),
	(25, 'Special Assignment', '2018', '', NULL, '', NULL, '', '', '2026-02-04 05:40:59', '2026-02-11 00:37:36'),
	(26, 'One Messenger, One Janitor Scheme', '2018', 'dated November 21, 2018', NULL, '', NULL, '', '', '2026-02-04 05:41:24', '2026-02-11 00:37:50'),
	(27, 'Monitoring Report on Recommendations in the Audit of Subsidy Funds Released to FIBECO for 196 Sitios under SEP year 2015', '2019', 'Year 2015  Batch 1', '2019-01-23', '3', '2019-01-24', '32', '', '2026-02-04 05:44:56', '2026-02-11 00:38:04'),
	(28, 'Audit of Due to GSIS', '2019', 'January 1 2018  to March 2018', '2019-02-26', '6', '2019-02-27', '48', '', '2026-02-04 05:45:43', '2026-02-11 00:39:14'),
	(29, 'Audit of Due to  BIR -Witholding Tax', '2019', 'January 1 2018  to March 2018', '2019-03-26', '7', '2019-03-26', '61', '', '2026-02-04 05:46:22', '2026-02-11 00:39:04'),
	(30, 'Amendments to the Revised Guidelines and Accounting System of NEA Training Funds', '2019', '', '2019-03-26', '8', '2019-03-26', '62', '', '2026-02-04 05:46:49', '2026-02-11 00:38:53'),
	(31, 'Monitoring of Recommendations in the Audit of Janitorial Services for the period January to March 2017', '2019', 'January 1 2017  to March 2017', '2019-04-26', '7', '2019-04-29', 'for information', '', '2026-02-04 05:47:23', '2026-02-11 00:38:42'),
	(32, 'Audit of Telephone Expenses- Mobile for the period January to June 2018', '2019', 'January 1 2018  to June 2018', '2019-04-26', '10', '2019-04-29', 'for information', '', '2026-02-04 05:47:58', '2026-02-11 00:39:36'),
	(33, 'Monitoring and evaluation of compliance to audit Recommendations on the Audit Due to BIR account', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:00:19', '2026-02-11 00:40:00'),
	(34, 'Audit of Petty Cash Fund', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:00:25', '2026-02-11 00:40:14'),
	(35, 'Audit of PHILRECA\'s Loan with NEA', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:00:33', '2026-02-11 00:40:27'),
	(36, 'Audit of Pag-IBIG Contributions and Loans', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:00:39', '2026-02-11 00:40:40'),
	(37, 'Audit of Philhealth Contributions', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:00:45', '2026-02-11 00:40:58'),
	(38, 'Formulation of Internal Audit Workflow under New Normal', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:01:54', '2026-02-11 00:41:11'),
	(39, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:02:04', '2026-02-11 00:41:25'),
	(40, 'Audit of Hazard Pay', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:02:14', '2026-02-11 00:41:42'),
	(41, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:02:25', '2026-02-11 00:41:56'),
	(42, 'Review of Internal Audit Manual', '2020', '', NULL, '', NULL, '', '', '2026-02-06 08:02:43', '2026-02-11 00:42:07'),
	(43, 'Report on the Audit of Covid - 19 Hazard Pay', '2021', '', '2021-02-18', '3', '2021-02-18', '3', '4th Qtr 2020 Target', '2026-02-06 08:03:55', '2026-02-11 00:42:24'),
	(44, 'IAQSMO Standard Operating Policies and Procedures Manual', '2021', '', '2021-02-18', '4', '2021-02-18', '4', '4th Qtr 2020 Target', '2026-02-06 08:04:31', '2026-02-11 00:42:33'),
	(45, 'Remote Internal Audit Process of IAQSMO', '2021', '', '2021-02-18', '', '2021-02-18', '', '', '2026-02-10 06:56:23', '2026-02-11 00:42:40'),
	(46, 'Modified Rates on Speakers, Honorarium', '2021', '', '2021-05-21', '5', '2021-05-27', '62', '', '2026-02-10 06:57:05', '2026-02-11 00:42:48'),
	(47, 'Guidelines in the Provision of NEA Resource Lecturers conducted by ECs/Other Government Agencies/Private Institutions', '2021', '', '2021-05-21', '6', '2021-05-27', '61', '', '2026-02-10 06:57:34', '2026-02-11 00:42:55'),
	(48, 'Study of Board Administrators\' Allowances/ Entitlements', '2021', '', '2021-05-21', '', '2021-05-27', 'For information', 'Special Assignment', '2026-02-10 06:58:15', '2026-02-11 00:43:04'),
	(49, 'Government Accounting Manual - Volume 11 Accounting Forms', '2021', '', '2021-05-21', '', '2021-05-27', 'For information', 'Special Assignment', '2026-02-10 06:58:51', '2026-02-11 00:43:11'),
	(50, 'Conduct of Random Surprise Audit for NEA Employees Working-from-Home', '2021', '', '2021-08-26', '', '2021-08-27', '', '2nd Qtr 2021 Target', '2026-02-10 06:59:27', '2026-02-11 00:43:20'),
	(51, 'Update on the Tax Declarations submitted by ECs as collaterals of their loans to ECs', '2021', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:00:11', '2026-02-11 00:43:27'),
	(52, 'NEA Memorandums re: Policy Guidelines on Subsidy Releases to ECs', '2021', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:01:06', '2026-02-11 00:43:36'),
	(53, 'Update on the validated documents from COA re: Study of BOA Allowance', '2021', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:02:18', '2026-02-11 00:43:44'),
	(54, 'Conduct of Appraisal of NEA Departments\' Structure', '2021', '', NULL, '', NULL, '', '', '2026-02-10 07:02:27', '2026-02-11 00:43:54'),
	(55, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '2021', '', NULL, '', NULL, '', '', '2026-02-10 07:04:52', '2026-02-11 00:44:09'),
	(56, 'NEA\'s Quality Management System - Internal Quality Audit of Departmental ISO 9001:2015 Procedure', '2021', '', '2021-11-19', '', '2021-11-26', '', '', '2026-02-10 07:05:24', '2026-02-11 00:44:16'),
	(57, 'Update on the current proof of payment/s of current real property taxes for Ecs that submitted Tax Declarations as Collateral for their loans to NEA', '2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:05:58', '2026-02-11 00:44:23'),
	(58, 'Prospect on Sitio Electrification Subsidy Fund thru NTF-ELCAC', '2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:06:32', '2026-02-11 00:44:31'),
	(59, 'Updates/Clarification re: BOA Allowances', '2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:06:59', '2026-02-11 00:44:38'),
	(60, 'Status of PHILRECA Loan Balance', '2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:07:53', '2026-02-11 00:44:46'),
	(61, 'Conduct of Random Surprise Audit for NEA Employees WFH for the months of August and September 2021', '2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:08:20', '2026-02-11 00:44:54'),
	(62, 'Conduct of Random Surprise Audit for NEA Employees WFH for the months of October, November, and December 2021', '2021', '', NULL, '', NULL, '', 'Special Assignment', '2026-02-10 07:10:35', '2026-02-11 00:45:01'),
	(63, 'Audit of Government Service Insurance System (GSIS)', '2021', '', NULL, '', NULL, '', '', '2026-02-10 07:10:42', '2026-02-11 00:45:07'),
	(64, 'Report on Appraisal of NEA Departments\' Structure', '2022', '', NULL, '', NULL, '', '', '2026-02-10 07:23:08', '2026-02-11 00:45:19'),
	(65, 'Report on the Conduct of Random Surprise Audit for NEA Employees WFH for the month of January 2022', '2022', 'January 2022', '2022-03-16', '', NULL, '', '', '2026-02-10 07:23:31', '2026-02-11 00:45:41'),
	(66, 'Audit on Cash Collecting Officer', '2022', '', NULL, '', NULL, '', '', '2026-02-10 07:23:37', '2026-02-11 00:45:48'),
	(67, 'Audit on Cash in Bank - Local Currency, Savings Account', '2022', '', NULL, '', NULL, '', '', '2026-02-10 07:23:42', '2026-02-11 00:45:55'),
	(68, 'Audit on Cash in Bank - Local Currency, Current Account', '2022', '', NULL, '', NULL, '', '', '2026-02-10 07:23:50', '2026-02-11 00:46:03'),
	(69, 'Audit on IT Equipment and Software', '2022', '', NULL, '', NULL, '', '', '2026-02-10 07:23:56', '2026-02-11 00:46:12'),
	(70, 'Audit on Hazard Pay - MECQ', '2022', '', '2022-06-20', '', NULL, '', '', '2026-02-10 07:24:12', '2026-02-11 00:46:20'),
	(71, 'Audit on Recruitment and Promotion', '2022', '', '2022-06-20', '', NULL, '', '', '2026-02-10 07:24:28', '2026-02-11 00:46:27'),
	(72, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '2022', '', '2022-09-21', 'none', NULL, 'For information', '', '2026-02-10 07:24:51', '2026-02-11 00:46:39'),
	(73, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', '2022', 'Calendar Year 2022', '2022-11-21', 'none', NULL, 'For information', '', '2026-02-10 07:25:13', '2026-02-11 00:46:49'),
	(74, 'Evaluation/Validation of NEA 2022 Performance Scorecard Accomplishment Report (4th Quarter 2022)', '2023', 'October to December 2022', '2023-03-21', 'none', '2022-03-22', 'For information', 'With SAQSD', '2026-02-10 07:25:54', '2026-02-11 00:47:04'),
	(75, 'Monitoring and Evaluation of Compliance to Audit Recommendations on the Audit of Due to GSIS Account', '2023', '', '2023-03-21', 'none', '2023-03-22', 'For information', '', '2026-02-10 07:26:55', '2026-02-11 00:47:11'),
	(76, 'Evaluation/Validation of NEA 2023 Performance Scorecard Accomplishment Report (1st Quarter 2023)', '2023', 'January to March 2023', '2023-06-14', 'none', NULL, 'For information', 'With SAQSD', '2026-02-10 07:27:27', '2026-02-11 00:47:19'),
	(77, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '2023', 'December 21, 2022 to May 12, 2023', '2023-06-14', 'none', NULL, 'For information', '', '2026-02-10 07:27:53', '2026-02-11 00:47:31'),
	(78, 'Audit of Cash - Local Currency', '2023', '', NULL, '', NULL, '', '', '2026-02-10 07:28:01', '2026-02-11 00:47:41'),
	(79, 'Monitoring on the Compliance to Previous Audit Recommendations on Audit of Due to GSIS Account', '2023', '', '2023-06-14', '', NULL, '', 'Matters arising from BAC meeting on Mar. 21, 2023', '2026-02-10 07:28:17', '2026-02-11 00:47:49'),
	(80, 'Status of Uncomplied Recommendations on the Audit of Due to GSIS', '2023', '', NULL, '', NULL, '', '', '2026-02-10 07:28:52', '2026-02-11 00:47:56'),
	(81, 'Review of Guidelines on Honorarium', '2023', '', NULL, '', NULL, '', '', '2026-02-10 07:29:01', '2026-02-11 00:48:03'),
	(82, 'Internal Quality Audit of Departmental ISO 9001:2015 Procedure - ITCSD, FSD, CCSMO, ECAD, NETI, RAO, OCS, LSO', '2023', 'Calendar Year 2023', '2023-12-11', '', NULL, '', '', '2026-02-10 07:29:30', '2026-02-11 00:48:16'),
	(83, 'Inventory Audit of IT Equipment as of December 31, 2022', '2024', 'as of December 31, 2022', '2024-03-26', '03', '2024-03-26', '', '2023 4th qtr target', '2026-02-10 07:31:05', '2026-02-11 00:48:40'),
	(84, 'Audit of the submitted Transfer Certificate of Title (TCTs) as Loan Security/Collateral of Electric Cooperatives to NEA as of December 31, 2023', '2024', 'as of December 31, 2023', '2024-03-26', '04', '2024-03-26', '', 'As per BAC instruction last Dec. 11, 2023', '2026-02-10 07:31:39', '2026-02-11 00:48:47'),
	(85, 'Audit on Salary Differentials of NEA Employees for the period October 05, 2021 to July 31, 2023', '2024', 'October 5, 2021 - July 31, 2023', '2024-07-18', '', '2024-07-23', '', 'With SAQSD', '2026-02-10 07:32:11', '2026-02-11 00:48:54'),
	(86, 'Status Report on Recommendations on the Inventory Audit of IT Equipment as of December 31, 2022', '2024', 'March 27, 2024 - June 30, 2024', '2024-07-18', 'none', '2024-07-23', 'For information', '', '2026-02-10 07:32:41', '2026-02-11 00:49:02'),
	(87, 'Review of EC\'s Manual of Approvals (MANAP) - COTELCO', '2024', '', '2024-09-18', '', NULL, '', 'With SAQSD', '2026-02-10 07:33:06', '2026-02-11 00:49:15'),
	(88, 'Summary of Management Compliances and Responses to Audit Observation Memoranda (AOM) of COA', '2024', 'November 20, 2023 to April 12, 2024', '2024-09-18', '', NULL, '', '', '2026-02-10 07:33:21', '2026-02-11 00:49:22'),
	(89, 'Monitoring of Audit of Cash - Local Currency, Current and Savings Account for the Period January 1 to March 31, 2023', '2024', 'January 1 to March 31, 2023', '2024-09-18', '06', NULL, '', 'Page 3', '2026-02-10 07:33:53', '2026-02-11 00:49:29'),
	(90, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', '2024', 'Calendar Year 2024', '2024-09-18', '', NULL, '', 'With SAQSD', '2026-02-10 07:34:16', '2026-02-11 00:49:53'),
	(91, 'Status and Monitoring Report of Internal Quality Audit (IQA) Recommendations and Opportunities for Improvement (OFIs) for the ISO 9001:2015 Quality Management System (QMS) <C.Y. 2023>', '2024', '', '2024-09-18', '08', NULL, '', '', '2026-02-10 07:34:36', '2026-02-11 00:50:06'),
	(92, 'Review of EC\'s Manual of Approvals (MANAP) - DORELCO', '2024', '', '2024-12-19', '', NULL, '', '', '2026-02-10 07:34:47', '2026-02-11 00:50:17'),
	(93, 'Status of Unidentified Collections in the Audit of Cash in Bank - Local Currency, Current and Savings Account as of November 30, 2024', '2024', 'as of November 30, 2024', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:01', '2026-02-11 00:50:25'),
	(94, 'NEA Performance Scorecard Validation Report as of September 30, 2024', '2024', 'as of September 30, 2024', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:14', '2026-02-11 00:50:33'),
	(95, 'RAO Procedure for the Conduct of Competitive Selection Process of the Electric Cooperatives\' Power Supply Procurement', '2024', '', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:26', '2026-02-11 00:50:39'),
	(96, 'Review of EC\'s Manual of Approvals (MANAP) - SAMELCO II', '2024', '', NULL, '', NULL, '', '', '2026-02-10 07:35:39', '2026-02-16 08:07:10');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
