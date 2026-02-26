-- Add image attachment column to aom_table for COA Observation
ALTER TABLE `aom_table` ADD COLUMN `coa_observation_image` VARCHAR(255) DEFAULT NULL AFTER `coa_observation`;