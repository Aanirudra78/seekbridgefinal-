-- ============================================================
-- TechNova — Academician Collaboration Hub migration
-- Reuses existing tables: fdp_programs, consultancy_opportunities,
-- research_projects. Adds: mentorship_programs, interests.
-- Idempotent. Seeds only into empty tables/columns.
-- ============================================================

USE `if0_42787265_technova66`;

-- ------------------------------------------------------------
-- 1) consultancy_opportunities: add optional external link
--    (existing columns: industry_id, title, description, required_expertise)
-- ------------------------------------------------------------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='if0_42787265_technova66' AND TABLE_NAME='consultancy_opportunities' AND COLUMN_NAME='external_url');
SET @q = IF(@s = 0, 'ALTER TABLE `consultancy_opportunities` ADD COLUMN `external_url` VARCHAR(500) NOT NULL DEFAULT '''' AFTER `required_expertise`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

INSERT INTO `consultancy_opportunities` (industry_id, title, description, required_expertise, external_url)
SELECT * FROM (
  SELECT 1, 'Edge-AI Model Deployment Consultant',
         'We are exploring on-device ML models for our inspection line. Need an expert to advise on model compression and edge hardware choices.',
         'TensorFlow Lite, Edge Computing, Optimization', 'https://technovasolutions.in/consult/edge-ai'
  UNION ALL
  SELECT 1, 'Subject Expert for NAAC / AICTE Accreditation',
         'Assist our higher-education vertical in preparing documentation and self-study reports for NAAC and AICTE accreditation.',
         'NAAC, AICTE, Outcome-Based Education', ''
) t
WHERE NOT EXISTS (SELECT 1 FROM `consultancy_opportunities` WHERE `external_url` <> '');

-- ------------------------------------------------------------
-- 2) mentorship_programs: industry posts mentorship / guest lectures
-- ------------------------------------------------------------
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

INSERT INTO `mentorship_programs`
  (industry_id, program_type, title, topic, description, mode, schedule_date, external_url)
SELECT * FROM (
  SELECT 1, 'mentorship', 'TechNova Engineering Mentorship Circle',
         'Software Engineering Careers',
         'Monthly 1:1 mentoring by senior engineers for faculty guiding final-year student projects.',
         'online', '2026-10-20', ''
  UNION ALL
  SELECT 1, 'guest_lecture', 'Guest Lecture: Building Production ML Systems',
         'Machine Learning',
         'An interactive lecture for students and faculty on taking ML models from notebooks to production.',
         'offline', '2026-12-04', 'https://technovasolutions.in/lectures/production-ml'
) t
WHERE NOT EXISTS (SELECT 1 FROM `mentorship_programs`);

-- ------------------------------------------------------------
-- 3) interests: express-interest records
--    target_type: 'consultancy' | 'mentorship' | 'research'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `interests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `target_type` ENUM('consultancy','mentorship','research') NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `note` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_interest` (`target_type`, `target_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `interests` (target_type, target_id, user_id, note)
SELECT * FROM (
  SELECT 'research',    1, 2, 'Dr. Anita Verma would like to co-investigate this project.'
  UNION ALL
  SELECT 'consultancy', 1, 2, ''
  UNION ALL
  SELECT 'mentorship',  1, 2, ''
) t
WHERE NOT EXISTS (SELECT 1 FROM `interests`);

SELECT 'Academician migration complete' AS status;