-- ============================================================
-- Academia-Industry Collaboration Portal
-- Database Schema: if0_42787265_technova66 (InfinityFree)
-- Run this in phpMyAdmin (SQL tab) on InfinityFree
-- ============================================================

USE `if0_42787265_technova66`;

-- ------------------------------------------------------------
-- 1. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','industry','academician','institution') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. student_details
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `college_name` VARCHAR(150) NOT NULL DEFAULT '',
  `course_branch` VARCHAR(150) NOT NULL DEFAULT '',
  `year` INT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_user` (`user_id`),
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. industry_details
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `industry_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(150) NOT NULL DEFAULT '',
  `industry_type` VARCHAR(100) NOT NULL DEFAULT '',
  `company_size` VARCHAR(50) NOT NULL DEFAULT '',
  `website` VARCHAR(255) NULL DEFAULT NULL,
  `about` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_industry_user` (`user_id`),
  CONSTRAINT `fk_industry_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. academician_details
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academician_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `college_name` VARCHAR(150) NOT NULL DEFAULT '',
  `department` VARCHAR(100) NOT NULL DEFAULT '',
  `designation` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `fk_acad_user` (`user_id`),
  CONSTRAINT `fk_acad_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. institution_details
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `institution_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `institution_name` VARCHAR(150) NOT NULL DEFAULT '',
  `institution_type` VARCHAR(100) NOT NULL DEFAULT '',
  `location` VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `fk_inst_user` (`user_id`),
  CONSTRAINT `fk_inst_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. skills
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skill_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_skill_name` (`skill_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. student_skills
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `skill_id` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `assessed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_studskill_student` (`student_id`),
  KEY `fk_studskill_skill` (`skill_id`),
  CONSTRAINT `fk_studskill_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_studskill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. courses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `platform` VARCHAR(100) NOT NULL DEFAULT '',
  `link` VARCHAR(500) NOT NULL DEFAULT '',
  `skill_tag` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. internships
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `internships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `industry_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `type` ENUM('internship','job') NOT NULL DEFAULT 'internship',
  `description` TEXT NULL,
  `required_skills` VARCHAR(500) NOT NULL DEFAULT '',
  `salary` VARCHAR(100) NOT NULL DEFAULT '',
  `age_limit` VARCHAR(50) NOT NULL DEFAULT '',
  `no_of_posts` INT NOT NULL DEFAULT 1,
  `duration` VARCHAR(50) NOT NULL DEFAULT '',
  `mode` VARCHAR(50) NOT NULL DEFAULT '',
  `posted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_inter_industry` (`industry_id`),
  CONSTRAINT `fk_inter_industry` FOREIGN KEY (`industry_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. applications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `internship_id` INT UNSIGNED NOT NULL,
  `status` ENUM('applied','test_pending','shortlisted','rejected') NOT NULL DEFAULT 'applied',
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_app_student` (`student_id`),
  KEY `fk_app_internship` (`internship_id`),
  CONSTRAINT `fk_app_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_internship` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 11. job_tests (questions stored as JSON)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_tests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `internship_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL DEFAULT '',
  `questions` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_jt_internship` (`internship_id`),
  CONSTRAINT `fk_jt_internship` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 12. test_scores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `test_scores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `taken_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ts_application` (`application_id`),
  CONSTRAINT `fk_ts_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Portfolio tables (for student portfolio page)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portfolio_projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `project_name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `link` VARCHAR(500) NULL,
  PRIMARY KEY (`id`),
  KEY `fk_proj_student` (`student_id`),
  CONSTRAINT `fk_proj_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `portfolio_certificates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `issuer` VARCHAR(150) NOT NULL DEFAULT '',
  `issued_date` DATE NULL,
  `link` VARCHAR(500) NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cert_student` (`student_id`),
  CONSTRAINT `fk_cert_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- institution consent flag (share my data with institution)
ALTER TABLE `student_details`
  ADD COLUMN `consent_share_data` TINYINT(1) NOT NULL DEFAULT 0 AFTER `year`;

-- ------------------------------------------------------------
-- SAMPLE SEED DATA (optional - uncomment to test with dummy data)
-- ------------------------------------------------------------
-- Skills
INSERT INTO `skills` (`skill_name`) VALUES
('Python'), ('Java'), ('SQL'), ('Machine Learning'), ('Data Analysis'),
('Web Development'), ('Networking'), ('Cloud Computing'), ('Communication'), ('Leadership');

-- Some courses
-- INSERT INTO `courses` (`title`,`platform`,`link`,`skill_tag`) VALUES
-- ('Python for Everybody', 'Coursera', 'https://www.coursera.org/specializations/python', 'Python'),
-- ('SQL Basics', 'Kaggle', 'https://www.kaggle.com/learn', 'SQL'),
-- ('Intro to Machine Learning', 'Kaggle', 'https://www.kaggle.com/learn', 'Machine Learning');
