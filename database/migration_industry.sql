-- ============================================================
-- Migration: industry features
-- Adds fields to internships for jobs + posting options
-- Run against academia_portal AFTER schema.sql
-- ============================================================

USE `if0_42787265_technova66`;

-- Add opportunity type + posting fields to internships
ALTER TABLE `internships`
  ADD COLUMN `type` ENUM('internship','job') NOT NULL DEFAULT 'internship' AFTER `title`,
  ADD COLUMN `salary` VARCHAR(100) NOT NULL DEFAULT '' AFTER `required_skills`,
  ADD COLUMN `age_limit` VARCHAR(50) NOT NULL DEFAULT '' AFTER `salary`,
  ADD COLUMN `no_of_posts` INT NOT NULL DEFAULT 1 AFTER `age_limit`,
  ADD COLUMN `duration` VARCHAR(50) NOT NULL DEFAULT '' AFTER `no_of_posts`,
  ADD COLUMN `mode` VARCHAR(50) NOT NULL DEFAULT '' AFTER `duration`;
