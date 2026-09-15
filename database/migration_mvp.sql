-- ============================================================
-- TechNova MVP — Application + Test + Complete Profile migration
-- Run once. Safe to re-run (idempotent-ish).
-- ============================================================

USE `if0_42787265_technova66`;

-- 1) student_details: complete profile fields
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='phone');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `phone` VARCHAR(20) NOT NULL DEFAULT '''' AFTER `year`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='city');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `city` VARCHAR(100) NOT NULL DEFAULT '''' AFTER `phone`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='linkedin');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `linkedin` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `city`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='github');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `github` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `linkedin`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='bio');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `bio` TEXT NULL AFTER `github`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='student_details' AND COLUMN_NAME='resume_path');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `resume_path` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `bio`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- 2) job_tests: test duration (minutes) + max questions
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='job_tests' AND COLUMN_NAME='max_questions');
SET @q = IF(@s = 0, 'ALTER TABLE `job_tests` ADD COLUMN `max_questions` INT NOT NULL DEFAULT 5 AFTER `duration_minutes`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='job_tests' AND COLUMN_NAME='duration_minutes');
SET @q = IF(@s = 0, 'ALTER TABLE `job_tests` ADD COLUMN `duration_minutes` INT NOT NULL DEFAULT 10 AFTER `title`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- 3) applications: attached test results
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='applications' AND COLUMN_NAME='test_score');
SET @q = IF(@s = 0, 'ALTER TABLE `applications` ADD COLUMN `test_score` INT NULL, ADD COLUMN `test_skipped` INT NULL, ADD COLUMN `test_wrong` INT NULL, ADD COLUMN `test_total` INT NULL, ADD COLUMN `test_completed_at` DATETIME NULL AFTER `status`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- 4) courses: certification/audience/creator support
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='courses' AND COLUMN_NAME='is_certification');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `is_certification` TINYINT(1) NOT NULL DEFAULT 0 AFTER `difficulty_level`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='courses' AND COLUMN_NAME='audience');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `audience` ENUM(''students'',''instructors'') NOT NULL DEFAULT ''students'' AFTER `is_certification`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='courses' AND COLUMN_NAME='created_by');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `audience`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- 5) Seed popular industry certification courses (only if not already present)
SET @c = (SELECT COUNT(*) FROM courses WHERE is_certification = 1);
SET @q = IF(@c = 0, 'INSERT INTO `courses` (`title`,`platform`,`link`,`skill_tag`,`difficulty_level`,`is_certification`,`audience`) VALUES
  (''AWS Certified Cloud Practitioner'',''AWS'',''https://aws.amazon.com/certification/certified-cloud-practitioner/'',''Cloud Computing'',''beginner'',1,''students''),
  (''Cisco CCNA - Networking'' ,''Cisco NetAcad'',''https://www.netacad.com/courses/ccna'',''Networking'',''intermediate'',1,''students''),
  (''Google Cloud Digital Leader'',''Google'',''https://cloud.google.com/learn/certification/cloud-digital-leader'',''Cloud Computing'',''beginner'',1,''students''),
  (''Microsoft Azure Fundamentals AZ-900'',''Microsoft'',''https://learn.microsoft.com/en-us/certifications/azure-fundamentals/'',''Cloud Computing'',''beginner'',1,''students''),
  (''CompTIA Security+'',''CompTIA'',''https://www.comptia.org/certifications/security'',''Networking'',''intermediate'',1,''students'')', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='consultancy_opportunities' AND COLUMN_NAME='external_url');
SET @q = IF(@s = 0, 'ALTER TABLE `consultancy_opportunities` ADD COLUMN `external_url` VARCHAR(500) NOT NULL DEFAULT '''' AFTER `required_expertise`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS `mentorship_programs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `industry_id` INT UNSIGNED NOT NULL,
  `program_type` ENUM('mentorship','guest_lecture') NOT NULL DEFAULT 'mentorship',
  `title` VARCHAR(200) NOT NULL,
  `topic` VARCHAR(200) NOT NULL DEFAULT '',
  `description` TEXT NULL,
  `mode` ENUM('online','offline','hybrid') NOT NULL DEFAULT 'online',
  `schedule_date` DATE NULL,
  `external_url` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_mpr_industry` (`industry_id`),
  CONSTRAINT `fk_mpr_industry` FOREIGN KEY (`industry_id`) REFERENCES `industry_details` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `interests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `target_type` ENUM('consultancy','mentorship','research') NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `note` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_interest` (`target_type`, `target_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migration complete' AS status;