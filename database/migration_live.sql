-- ============================================================
-- SEEKBRIDGE LIVE MIGRATION (InfinityFree - if0_42874072_seekbridge1)
-- Idempotent: safe to run 1 or 10 times.
-- HOW: phpMyAdmin -> select DB if0_42874072_seekbridge1 -> Import -> run this.
-- ============================================================

-- ---------- 1) student_details: complete profile fields ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='phone');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `phone` VARCHAR(20) NOT NULL DEFAULT '''' AFTER `year`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='city');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `city` VARCHAR(100) NOT NULL DEFAULT '''' AFTER `phone`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='linkedin');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `linkedin` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `city`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='github');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `github` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `linkedin`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='bio');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `bio` TEXT NULL AFTER `github`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='resume_path');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `resume_path` VARCHAR(255) NOT NULL DEFAULT '''' AFTER `bio`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 2) college codes (college identification feature) ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='institution_details' AND COLUMN_NAME='college_code');
SET @q = IF(@s = 0, 'ALTER TABLE `institution_details` ADD COLUMN `college_code` VARCHAR(20) NULL UNIQUE AFTER `location`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_details' AND COLUMN_NAME='college_code');
SET @q = IF(@s = 0, 'ALTER TABLE `student_details` ADD COLUMN `college_code` VARCHAR(20) NOT NULL DEFAULT '''' AFTER `consent_share_data`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE `institution_details` SET `college_code` = 'IIITD'
WHERE (`college_code` IS NULL OR TRIM(`college_code`) = '') AND `institution_name` = 'IIIT Delhi';

-- ---------- 3) job_tests: duration + max questions ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='job_tests' AND COLUMN_NAME='duration_minutes');
SET @q = IF(@s = 0, 'ALTER TABLE `job_tests` ADD COLUMN `duration_minutes` INT NOT NULL DEFAULT 10 AFTER `title`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='job_tests' AND COLUMN_NAME='max_questions');
SET @q = IF(@s = 0, 'ALTER TABLE `job_tests` ADD COLUMN `max_questions` INT NOT NULL DEFAULT 5 AFTER `duration_minutes`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 4) applications: attached test results ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='applications' AND COLUMN_NAME='test_score');
SET @q = IF(@s = 0,
    'ALTER TABLE `applications` ADD COLUMN `test_score` INT NULL,
     ADD COLUMN `test_skipped` INT NULL,
     ADD COLUMN `test_wrong` INT NULL,
     ADD COLUMN `test_total` INT NULL,
     ADD COLUMN `test_completed_at` DATETIME NULL AFTER `status`',
    'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 5) courses: difficulty / certification / audience / creator ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='difficulty_level');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `difficulty_level` ENUM(''beginner'',''intermediate'',''advanced'') NOT NULL DEFAULT ''beginner'' AFTER `skill_tag`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='is_certification');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `is_certification` TINYINT(1) NOT NULL DEFAULT 0 AFTER `difficulty_level`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='audience');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `audience` ENUM(''students'',''instructors'') NOT NULL DEFAULT ''students'' AFTER `is_certification`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='created_by');
SET @q = IF(@s = 0, 'ALTER TABLE `courses` ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `audience`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 6) institution_details: about + website ----------
SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='institution_details' AND COLUMN_NAME='about');
SET @q = IF(@s = 0, 'ALTER TABLE `institution_details` ADD COLUMN `about` TEXT NULL AFTER `location`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='institution_details' AND COLUMN_NAME='website');
SET @q = IF(@s = 0, 'ALTER TABLE `institution_details` ADD COLUMN `website` VARCHAR(255) NULL AFTER `about`', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 7) New collaboration tables ----------
CREATE TABLE IF NOT EXISTS `fdp_programs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `program_type` ENUM('fdp','workshop','training') NOT NULL DEFAULT 'fdp',
  `description` TEXT NULL,
  `skills` VARCHAR(500) NOT NULL DEFAULT '',
  `mode` ENUM('online','offline','hybrid') NOT NULL DEFAULT 'online',
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `external_url` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_fdp_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `consultancy_opportunities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `industry_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `required_expertise` VARCHAR(500) NOT NULL DEFAULT '',
  `external_url` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_cons_industry` (`industry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `research_projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `industry_id` INT UNSIGNED NULL,
  `academician_id` INT UNSIGNED NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('open','in_progress','completed') NOT NULL DEFAULT 'open',
  `posted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NULL,
  KEY `fk_res_industry` (`industry_id`),
  KEY `fk_res_acad` (`academician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  KEY `fk_mpr_industry` (`industry_id`)
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

-- ---------- 8) Sample collaboration data (only when empty) ----------
-- Resolve the connected industry "TechNova Solutions" + teacher id by EMAIL, so this
-- works on ANY database (local or live) without relying on fixed row ids.
SET @tech = (SELECT d.user_id FROM industry_details d JOIN users u ON u.id = d.user_id WHERE d.company_name = 'TechNova Solutions' LIMIT 1);

SET @p = (SELECT COUNT(*) FROM fdp_programs);
SET @q = IF(@p = 0 AND @tech IS NOT NULL,
  'INSERT INTO `fdp_programs` (`company_id`,`title`,`program_type`,`description`,`skills`,`mode`,`start_date`,`end_date`,`external_url`) VALUES
   (@tech,''Design Thinking for Engineering Faculty'',''workshop'',''Two-day hands-on workshop on running design-thinking sprints inside semester projects.'',''Design Thinking, Prototyping, Ideation'',''online'',''2026-10-12'',''2026-10-13'',''''),
   (@tech,''Advanced Python & Automation FDP'',''fdp'',''Week-long FDP on Python for automating classroom & lab workflows.'',''Python, Automation, Scripting'',''hybrid'',''2026-11-02'',''2026-11-06'','''')',
  'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @co = (SELECT COUNT(*) FROM consultancy_opportunities);
SET @q = IF(@co = 0 AND @tech IS NOT NULL,
  'INSERT INTO `consultancy_opportunities` (`industry_id`,`title`,`description`,`required_expertise`,`external_url`) VALUES
   (@tech,''Edge-AI Model Deployment Consultant'',''We need an expert to advise on model compression and edge hardware choices for our inspection line.'',''TensorFlow Lite, Edge Computing'',''https://technovasolutions.in/consult/edge-ai'')',
  'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @r = (SELECT COUNT(*) FROM research_projects);
SET @q = IF(@r = 0 AND @tech IS NOT NULL,
  'INSERT INTO `research_projects` (`industry_id`,`academician_id`,`title`,`description`,`status`) VALUES
   (@tech,NULL,''AI-assisted STEM assessment'',''Joint research on using LLMs to auto-grade open-ended STEM answers while flagging partial credit.'',''open'')',
  'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SET @m = (SELECT COUNT(*) FROM mentorship_programs);
SET @q = IF(@m = 0 AND @tech IS NOT NULL,
  'INSERT INTO `mentorship_programs` (`industry_id`,`program_type`,`title`,`topic`,`description`,`mode`,`schedule_date`,`external_url`) VALUES
   (@tech,''mentorship'',''TechNova Engineering Mentorship Circle'',''Software Engineering Careers'',''Monthly 1:1 mentoring by senior engineers for faculty guiding final-year projects.'',''online'',''2026-10-20'',''''),
   (@tech,''guest_lecture'',''Guest Lecture: Building Production ML Systems'',''Machine Learning'',''Interactive lecture on taking ML models from notebooks to production.'',''offline'',''2026-12-04'',''https://technovasolutions.in/lectures/production-ml'')',
  'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- Teacher demo interests (only when teacher friendly account exists, via EMAIL)
SET @teacher = (SELECT id FROM users WHERE email = 'teacher@demo.com' LIMIT 1);
SET @rid = (SELECT id FROM research_projects WHERE title = 'AI-assisted STEM assessment' LIMIT 1);
SET @cid = (SELECT id FROM consultancy_opportunities WHERE title = 'Edge-AI Model Deployment Consultant' LIMIT 1);
SET @mid = (SELECT id FROM mentorship_programs WHERE title = 'TechNova Engineering Mentorship Circle' LIMIT 1);

SET @i = (SELECT COUNT(*) FROM interests);
SET @q = IF(@i = 0 AND @teacher IS NOT NULL,
  'INSERT IGNORE INTO `interests` (`target_type`,`target_id`,`user_id`,`note`) VALUES
   (''research'', @rid, @teacher, ''Interest in AI-assisted assessment research''),
   (''consultancy'', @cid, @teacher, ''Open to consulting on Edge-AI deployment projects''),
   (''mentorship'', @mid, @teacher, ''Interested in mentoring via TechNova program'')',
  'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

-- Certification courses seed (only when none)
SET @c = (SELECT COUNT(*) FROM courses WHERE is_certification = 1);
SET @q = IF(@c = 0, 'INSERT INTO `courses` (`title`,`platform`,`link`,`skill_tag`,`difficulty_level`,`is_certification`,`audience`) VALUES
  (''AWS Certified Cloud Practitioner'',''AWS'',''https://aws.amazon.com/certification/certified-cloud-practitioner/'',''Cloud Computing'',''beginner'',1,''students''),
  (''Cisco CCNA - Networking'' ,''Cisco NetAcad'',''https://www.netacad.com/courses/ccna'',''Networking'',''intermediate'',1,''students''),
  (''Google Cloud Digital Leader'',''Google'',''https://cloud.google.com/learn/certification/cloud-digital-leader'',''Cloud Computing'',''beginner'',1,''students''),
  (''Microsoft Azure Fundamentals AZ-900'',''Microsoft'',''https://learn.microsoft.com/en-us/certifications/azure-fundamentals/'',''Cloud Computing'',''beginner'',1,''students''),
  (''CompTIA Security+'',''CompTIA'',''https://www.comptia.org/certifications/security'',''Networking'',''intermediate'',1,''students'')', 'SELECT 1');
PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;

SELECT 'LIVE MIGRATION COMPLETE' AS status;