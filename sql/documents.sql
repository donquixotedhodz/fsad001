-- --------------------------------------------------------
-- Table structure for table `documents`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ec` varchar(255) NOT NULL COMMENT 'Electric Cooperative name',
  `item` varchar(1000) NOT NULL COMMENT 'Item description',
  `recommending_approvals` varchar(500) DEFAULT NULL COMMENT 'Recommending approvals',
  `approving_authority` varchar(500) DEFAULT NULL COMMENT 'Approving authority',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'Relative path to file',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'Original file name',
  `file_type` varchar(50) DEFAULT NULL COMMENT 'File MIME type',
  `file_size` bigint DEFAULT NULL COMMENT 'File size in bytes',
  `department` varchar(255) DEFAULT NULL COMMENT 'Department name',
  `team` varchar(255) DEFAULT NULL COMMENT 'Team name',
  `uploaded_by` int DEFAULT NULL COMMENT 'User ID who uploaded',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Upload timestamp',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_ec` (`ec`),
  KEY `idx_item` (`item`(100)),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_department` (`department`),
  KEY `idx_team` (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Document repository for SOMANAP module';
