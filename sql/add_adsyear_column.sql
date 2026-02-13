-- Add adsyear column to ads table
ALTER TABLE `ads` ADD COLUMN `adsyear` YEAR DEFAULT NULL AFTER `audit_report`;