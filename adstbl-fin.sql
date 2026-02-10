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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neafsad.ads: ~95 rows (approximately)
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
	(44, 'IAQSMO Standard Operating Policies and Procedures Manual', '', '2021-02-18', '4', '2021-02-18', '4', '4th Qtr 2020 Target', '2026-02-06 08:04:31', '2026-02-06 08:04:31'),
	(45, 'Remote Internal Audit Process of IAQSMO', '', '2021-02-18', '', '2021-02-18', '', '', '2026-02-10 06:56:23', '2026-02-10 06:56:23'),
	(46, 'Modified Rates on Speakers, Honorarium', '', '2021-05-21', '5', '2021-05-27', '62', '', '2026-02-10 06:57:05', '2026-02-10 06:57:05'),
	(47, 'Guidelines in the Provision of NEA Resource Lecturers conducted by ECs/Other Government Agencies/Private Institutions', '', '2021-05-21', '6', '2021-05-27', '61', '', '2026-02-10 06:57:34', '2026-02-10 06:57:34'),
	(48, 'Study of Board Administrators\' Allowances/ Entitlements', '', '2021-05-21', '', '2021-05-27', 'For information', 'Special Assignment', '2026-02-10 06:58:15', '2026-02-10 06:58:15'),
	(49, 'Government Accounting Manual - Volume 11 Accounting Forms', '', '2021-05-21', '', '2021-05-27', 'For information', 'Special Assignment', '2026-02-10 06:58:51', '2026-02-10 06:58:51'),
	(50, 'Conduct of Random Surprise Audit for NEA Employees Working-from-Home', '', '2021-08-26', '', '2021-08-27', '', '2nd Qtr 2021 Target', '2026-02-10 06:59:27', '2026-02-10 06:59:27'),
	(51, 'Update on the Tax Declarations submitted by ECs as collaterals of their loans to ECs', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:00:11', '2026-02-10 07:00:11'),
	(52, 'NEA Memorandums re: Policy Guidelines on Subsidy Releases to ECs', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:01:06', '2026-02-10 07:01:06'),
	(53, 'Update on the validated documents from COA re: Study of BOA Allowance', '', '2021-08-26', '', '2021-08-27', 'For information', 'Special Assignment', '2026-02-10 07:02:18', '2026-02-10 07:02:18'),
	(54, 'Conduct of Appraisal of NEA Departments\' Structure', '', NULL, '', NULL, '', '', '2026-02-10 07:02:27', '2026-02-10 07:02:27'),
	(55, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '', NULL, '', NULL, '', '', '2026-02-10 07:04:52', '2026-02-10 07:04:52'),
	(56, 'NEA\'s Quality Management System - Internal Quality Audit of Departmental ISO 9001:2015 Procedure', '', '2021-11-19', '', '2021-11-26', '', '', '2026-02-10 07:05:24', '2026-02-10 07:05:24'),
	(57, 'Update on the current proof of payment/s of current real property taxes for Ecs that submitted Tax Declarations as Collateral for their loans to NEA', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:05:58', '2026-02-10 07:05:58'),
	(58, 'Prospect on Sitio Electrification Subsidy Fund thru NTF-ELCAC', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:06:32', '2026-02-10 07:06:32'),
	(59, 'Updates/Clarification re: BOA Allowances', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:06:59', '2026-02-10 07:06:59'),
	(60, 'Status of PHILRECA Loan Balance', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:07:53', '2026-02-10 07:07:53'),
	(61, 'Conduct of Random Surprise Audit for NEA Employees WFH for the months of August and September 2021', '', '2021-11-19', '', '2021-11-26', '', 'Special Assignment', '2026-02-10 07:08:20', '2026-02-10 07:08:20'),
	(62, 'Conduct of Random Surprise Audit for NEA Employees WFH for the months of October, November, and December 2021', '', NULL, '', NULL, '', 'Special Assignment', '2026-02-10 07:10:35', '2026-02-10 07:10:35'),
	(63, 'Audit of Government Service Insurance System (GSIS)', '', NULL, '', NULL, '', '', '2026-02-10 07:10:42', '2026-02-10 07:10:42'),
	(64, 'Report on Appraisal of NEA Departments\' Structure', '', NULL, '', NULL, '', '', '2026-02-10 07:23:08', '2026-02-10 07:23:08'),
	(65, 'Report on the Conduct of Random Surprise Audit for NEA Employees WFH for the month of January 2022', 'January 2022', '2022-03-16', '', NULL, '', '', '2026-02-10 07:23:31', '2026-02-10 07:23:31'),
	(66, 'Audit on Cash Collecting Officer', '', NULL, '', NULL, '', '', '2026-02-10 07:23:37', '2026-02-10 07:23:37'),
	(67, 'Audit on Cash in Bank - Local Currency, Savings Account', '', NULL, '', NULL, '', '', '2026-02-10 07:23:42', '2026-02-10 07:23:42'),
	(68, 'Audit on Cash in Bank - Local Currency, Current Account', '', NULL, '', NULL, '', '', '2026-02-10 07:23:50', '2026-02-10 07:23:50'),
	(69, 'Audit on IT Equipment and Software', '', NULL, '', NULL, '', '', '2026-02-10 07:23:56', '2026-02-10 07:23:56'),
	(70, 'Audit on Hazard Pay - MECQ', '', '2022-06-20', '', NULL, '', '', '2026-02-10 07:24:12', '2026-02-10 07:24:12'),
	(71, 'Audit on Recruitment and Promotion', '', '2022-06-20', '', NULL, '', '', '2026-02-10 07:24:28', '2026-02-10 07:24:28'),
	(72, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', '', '2022-09-21', 'none', NULL, 'For information', '', '2026-02-10 07:24:51', '2026-02-10 07:24:51'),
	(73, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', 'Calendar Year 2022', '2022-11-21', 'none', NULL, 'For information', '', '2026-02-10 07:25:13', '2026-02-10 07:25:13'),
	(74, 'Evaluation/Validation of NEA 2022 Performance Scorecard Accomplishment Report (4th Quarter 2022)', 'October to December 2022', '2023-03-21', 'none', '2022-03-22', 'For information', 'With SAQSD', '2026-02-10 07:25:54', '2026-02-10 07:25:54'),
	(75, 'Monitoring and Evaluation of Compliance to Audit Recommendations on the Audit of Due to GSIS Account', '', '2023-03-21', 'none', '2023-03-22', 'For information', '', '2026-02-10 07:26:55', '2026-02-10 07:26:55'),
	(76, 'Evaluation/Validation of NEA 2023 Performance Scorecard Accomplishment Report (1st Quarter 2023)', 'January to March 2023', '2023-06-14', 'none', NULL, 'For information', 'With SAQSD', '2026-02-10 07:27:27', '2026-02-10 07:27:27'),
	(77, 'Summary of Management Compliances & Responses to Audit Observation Memoranda (AOM) of COA', 'December 21, 2022 to May 12, 2023', '2023-06-14', 'none', NULL, 'For information', '', '2026-02-10 07:27:53', '2026-02-10 07:27:53'),
	(78, 'Audit of Cash - Local Currency', '', NULL, '', NULL, '', '', '2026-02-10 07:28:01', '2026-02-10 07:28:01'),
	(79, 'Monitoring on the Compliance to Previous Audit Recommendations on Audit of Due to GSIS Account', '', '2023-06-14', '', NULL, '', 'Matters arising from BAC meeting on Mar. 21, 2023', '2026-02-10 07:28:17', '2026-02-10 07:28:17'),
	(80, 'Status of Uncomplied Recommendations on the Audit of Due to GSIS', '', NULL, '', NULL, '', '', '2026-02-10 07:28:52', '2026-02-10 07:28:52'),
	(81, 'Review of Guidelines on Honorarium', '', NULL, '', NULL, '', '', '2026-02-10 07:29:01', '2026-02-10 07:29:01'),
	(82, 'Internal Quality Audit of Departmental ISO 9001:2015 Procedure - ITCSD, FSD, CCSMO, ECAD, NETI, RAO, OCS, LSO', 'Calendar Year 2023', '2023-12-11', '', NULL, '', '', '2026-02-10 07:29:30', '2026-02-10 07:36:37'),
	(83, 'Inventory Audit of IT Equipment as of December 31, 2022', 'as of December 31, 2022', '2024-03-26', '03', '2024-03-26', '', '2023 4th qtr target', '2026-02-10 07:31:05', '2026-02-10 07:31:05'),
	(84, 'Audit of the submitted Transfer Certificate of Title (TCTs) as Loan Security/Collateral of Electric Cooperatives to NEA as of December 31, 2023', 'as of December 31, 2023', '2024-03-26', '04', '2024-03-26', '', 'As per BAC instruction last Dec. 11, 2023', '2026-02-10 07:31:39', '2026-02-10 07:31:39'),
	(85, 'Audit on Salary Differentials of NEA Employees for the period October 05, 2021 to July 31, 2023', 'October 5, 2021 - July 31, 2023', '2024-07-18', '', '2024-07-23', '', 'With SAQSD', '2026-02-10 07:32:11', '2026-02-10 07:32:11'),
	(86, 'Status Report on Recommendations on the Inventory Audit of IT Equipment as of December 31, 2022', 'March 27, 2024 - June 30, 2024', '2024-07-18', 'none', '2024-07-23', 'For information', '', '2026-02-10 07:32:41', '2026-02-10 07:32:41'),
	(87, 'Review of EC\'s Manual of Approvals (MANAP) - COTELCO', '', '2024-09-18', '', NULL, '', 'With SAQSD', '2026-02-10 07:33:06', '2026-02-10 07:33:06'),
	(88, 'Summary of Management Compliances and Responses to Audit Observation Memoranda (AOM) of COA', 'November 20, 2023 to April 12, 2024', '2024-09-18', '', NULL, '', '', '2026-02-10 07:33:21', '2026-02-10 07:33:21'),
	(89, 'Monitoring of Audit of Cash - Local Currency, Current and Savings Account for the Period January 1 to March 31, 2023', 'January 1 to March 31, 2023', '2024-09-18', '06', NULL, '', 'Page 3', '2026-02-10 07:33:53', '2026-02-10 07:33:53'),
	(90, 'Internal Quality Audit of Department\'s ISO 9001:2015 Procedure', 'Calendar Year 2024', '2024-09-18', '', NULL, '', 'With SAQSD', '2026-02-10 07:34:16', '2026-02-10 07:34:16'),
	(91, 'Status and Monitoring Report of Internal Quality Audit (IQA) Recommendations and Opportunities for Improvement (OFIs) for the ISO 9001:2015 Quality Management System (QMS) <C.Y. 2023>', '', '2024-09-18', '08', NULL, '', '', '2026-02-10 07:34:36', '2026-02-10 07:34:36'),
	(92, 'Review of EC\'s Manual of Approvals (MANAP) - DORELCO', '', '2024-12-19', '', NULL, '', '', '2026-02-10 07:34:47', '2026-02-10 07:36:07'),
	(93, 'Status of Unidentified Collections in the Audit of Cash in Bank - Local Currency, Current and Savings Account as of November 30, 2024', 'as of November 30, 2024', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:01', '2026-02-10 07:35:01'),
	(94, 'NEA Performance Scorecard Validation Report as of September 30, 2024', 'as of September 30, 2024', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:14', '2026-02-10 07:35:14'),
	(95, 'RAO Procedure for the Conduct of Competitive Selection Process of the Electric Cooperatives\' Power Supply Procurement', '', '2024-12-19', '', NULL, '', '', '2026-02-10 07:35:26', '2026-02-10 07:35:26'),
	(96, 'Review of EC\'s Manual of Approvals (MANAP) - SAMELCO II', '', NULL, '', NULL, '', '', '2026-02-10 07:35:39', '2026-02-10 07:35:39');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
