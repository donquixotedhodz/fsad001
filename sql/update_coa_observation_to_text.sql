-- Update coa_observation column to TEXT to store JSON arrays
ALTER TABLE `aom_table` MODIFY COLUMN `coa_observation` TEXT DEFAULT NULL;