-- Add profile fields to staff table if they don't exist
ALTER TABLE `staff` ADD COLUMN `full_name` VARCHAR(255) DEFAULT NULL AFTER `name`;
ALTER TABLE `staff` ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL AFTER `full_name`;
