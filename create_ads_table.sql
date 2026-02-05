-- Create table for AD Scorecard - Audit Decision (ADS) Scorecard Report
CREATE TABLE IF NOT EXISTS `ads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `audit_report` varchar(255) NOT NULL,
  `scope` text,
  `bac_date` date,
  `bac_reso` varchar(255),
  `boa_date` date,
  `boa_reso` varchar(255),
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_report` (`audit_report`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
