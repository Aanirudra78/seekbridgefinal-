-- ============================================================
-- SEED DATA - Example accounts + sample content
-- Run AFTER schema.sql in the `academia_portal` database.
--
-- EXAMPLE LOGINS (all passwords end with "123"):
--   Student  : student@demo.com      / student123
--   Academic : teacher@demo.com      / teacher123
--   Industry : industry@demo.com     / industry123
--   College  : college@demo.com      / college123
-- ============================================================

USE `if0_42787265_technova66`;

-- ---------- Example users (password hashed with password_hash) ----------
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Rahul Sharma',  'student@demo.com',   '$2y$10$DsPmLhFMb.14BYo4pLTaGuBs6iOAEqIIxB9CaMjmMNCfC/2GvOpYu', 'student'),
('Dr. Anita Verma', 'teacher@demo.com', '$2y$10$31MMz7l9NpcMHah/M9ZnI.n/BXlUA4H8mjgimTlqBP7lvaCheP7hO', 'academician'),
('TechNova Solutions', 'industry@demo.com', '$2y$10$CS1VXUPJ8MRmMGHAsqrXeeiuV2JZ2y6MVQ5KgJRGsAI9gsupQpK3O', 'industry'),
('IIIT Delhi', 'college@demo.com', '$2y$10$2RKgXR3.V4cQswaqUC4tQO.UJHufmNpt1Hlx.mSlWzOZ/B.6uMWHa', 'institution');

-- ---------- Role details ----------
INSERT INTO `student_details` (`user_id`, `college_name`, `course_branch`, `year`, `consent_share_data`)
SELECT id, 'IIIT Delhi', 'B.Tech Computer Science', 3, 1 FROM `users` WHERE `email` = 'student@demo.com';

INSERT INTO `academician_details` (`user_id`, `college_name`, `department`, `designation`)
SELECT id, 'IIIT Delhi', 'Computer Science', 'Associate Professor' FROM `users` WHERE `email` = 'teacher@demo.com';

INSERT INTO `industry_details` (`user_id`, `company_name`, `industry_type`, `company_size`, `website`)
SELECT id, 'TechNova Solutions', 'IT Services', '251-1000', 'https://technova.example.com' FROM `users` WHERE `email` = 'industry@demo.com';

INSERT INTO `institution_details` (`user_id`, `institution_name`, `institution_type`, `location`)
SELECT id, 'IIIT Delhi', 'University', 'New Delhi' FROM `users` WHERE `email` = 'college@demo.com';

-- ---------- Sample skills ----------
INSERT IGNORE INTO `skills` (`skill_name`) VALUES
('Python'), ('Java'), ('SQL'), ('Machine Learning'), ('Data Analysis'),
('Web Development'), ('Networking'), ('Cloud Computing'), ('Communication'), ('Leadership');

-- ---------- Sample student skills (assessment) ----------
INSERT INTO `student_skills` (`student_id`, `skill_id`, `score`)
SELECT u.id, s.id, CASE s.skill_name
    WHEN 'Python' THEN 85
    WHEN 'SQL' THEN 45
    WHEN 'Web Development' THEN 60
    WHEN 'Machine Learning' THEN 30
    ELSE 55 END
FROM `users` u, `skills` s
WHERE u.email = 'student@demo.com';

-- ---------- Sample courses ----------
INSERT INTO `courses` (`title`, `platform`, `link`, `skill_tag`) VALUES
('Python for Everybody', 'Coursera', 'https://www.coursera.org/specializations/python', 'Python'),
('SQL Basics', 'Kaggle', 'https://www.kaggle.com/learn', 'SQL'),
('Intro to Machine Learning', 'Kaggle', 'https://www.kaggle.com/learn', 'Machine Learning'),
('Full Stack Web Development', 'Udemy', 'https://www.udemy.com', 'Web Development'),
('Cloud Computing Fundamentals', 'Coursera', 'https://www.coursera.org', 'Cloud Computing'),
('Data Analysis with Python', 'DataCamp', 'https://www.datacamp.com', 'Data Analysis');

-- ---------- Sample internships ----
INSERT INTO `internships` (`industry_id`, `title`, `type`, `description`, `required_skills`, `salary`, `age_limit`, `no_of_posts`, `duration`, `mode`)
SELECT u.id, 'Software Development Intern', 'internship', 'Build and maintain web applications using modern frameworks. 3-month paid internship.', 'Python,Web Development,SQL', '₹20,000/mo', '18-25', 3, '3 months', 'Remote'
FROM `users` u WHERE u.email = 'industry@demo.com';

INSERT INTO `internships` (`industry_id`, `title`, `type`, `description`, `required_skills`, `salary`, `age_limit`, `no_of_posts`, `duration`, `mode`)
SELECT u.id, 'Data Science Intern', 'internship', 'Work with real datasets to build predictive models and dashboards.', 'Python,Machine Learning,Data Analysis,SQL', '₹25,000/mo', '18-26', 2, '6 months', 'Hybrid'
FROM `users` u WHERE u.email = 'industry@demo.com';

INSERT INTO `internships` (`industry_id`, `title`, `type`, `description`, `required_skills`, `salary`, `age_limit`, `no_of_posts`, `duration`, `mode`)
SELECT u.id, 'Cloud & Networking Intern', 'internship', 'Assist in cloud infrastructure setup and network monitoring.', 'Cloud Computing,Networking', '₹18,000/mo', '18-25', 2, '3 months', 'Onsite'
FROM `users` u WHERE u.email = 'industry@demo.com';

INSERT INTO `internships` (`industry_id`, `title`, `type`, `description`, `required_skills`, `salary`, `age_limit`, `no_of_posts`, `duration`, `mode`)
SELECT u.id, 'Junior Software Engineer', 'job', 'Full-time role building scalable backend services and web applications.', 'Python,SQL,Java,Web Development', '₹6 LPA', '21-30', 5, 'Full-time', 'Onsite'
FROM `users` u WHERE u.email = 'industry@demo.com';

-- ---------- Sample application from the demo student (so industry sees requests) ----------
INSERT INTO `applications` (`student_id`, `internship_id`, `status`)
SELECT us.id, i.id, 'applied'
FROM `users` us, `internships` i
WHERE us.email = 'student@demo.com' AND i.title = 'Software Development Intern'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id = us.id AND a.internship_id = i.id);

-- ---------- Sample test for the software intern posting ----------
INSERT INTO `job_tests` (`internship_id`, `title`, `questions`)
SELECT i.id, 'Software Intern Screening Test',
  '[
    {"q":"Which keyword is used to define a function in Python?","options":["def","func","function","define"],"answer":0},
    {"q":"Which clause is used to filter records in SQL?","options":["WHERE","GROUP BY","ORDER BY","HAVING"],"answer":0},
    {"q":"Which language defines the structure of a web page?","options":["CSS","HTML","JavaScript","PHP"],"answer":1}
  ]'
FROM `internships` i WHERE i.title = 'Software Development Intern';

