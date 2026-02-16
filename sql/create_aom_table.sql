CREATE TABLE `aom_table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `coa_observation` text DEFAULT NULL,
  `coa_recommendation` text DEFAULT NULL,
  `comments_justification` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_aom_department` FOREIGN KEY (`department_id`) REFERENCES `neadept_table` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
