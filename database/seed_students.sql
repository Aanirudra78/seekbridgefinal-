-- Example students for IIIT Delhi (college analytics demo)
-- Password for all: student123
USE `if0_42787265_technova66`;
SET @std = '$2y$10$DsPmLhFMb.14BYo4pLTaGuBs6iOAEqIIxB9CaMjmMNCfC/2GvOpYu';

INSERT INTO `users` (`name`,`email`,`password`,`role`) VALUES
('Priya Nair',  'priya@demo.com',  @std, 'student'),
('Arjun Mehta', 'arjun@demo.com',  @std, 'student'),
('Sneha Kulkarni','sneha@demo.com',@std, 'student'),
('Vikram Singh','vikram@demo.com', @std, 'student'),
('Ishita Rao',  'ishita@demo.com', @std, 'student'),
('Rohan Das',   'rohan@demo.com',  @std, 'student'),
('Kavya Pillai','kavya@demo.com',  @std, 'student'),
('Aditya Jain', 'aditya@demo.com', @std, 'student'),
('Meera Iyer',  'meera@demo.com',  @std, 'student'),
('Sahil Verma', 'sahil@demo.com',  @std, 'student');

INSERT INTO `student_details` (`user_id`,`college_name`,`course_branch`,`year`,`consent_share_data`)
SELECT u.id, 'IIIT Delhi', 'B.Tech Computer Science', 3, 1 FROM `users` u
WHERE u.email IN ('priya@demo.com','arjun@demo.com','sneha@demo.com','vikram@demo.com','ishita@demo.com',
'rohan@demo.com','kavya@demo.com','aditya@demo.com','meera@demo.com','sahil@demo.com');

-- Skill scores (score > 70 = strong, < 70 = lacking)
-- Python: many lacking
INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='Python'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,40 sc UNION SELECT 'arjun@demo.com',85 UNION SELECT 'sneha@demo.com',55 UNION SELECT 'vikram@demo.com',30 UNION SELECT 'ishita@demo.com',90 UNION SELECT 'rohan@demo.com',45 UNION SELECT 'kavya@demo.com',60 UNION SELECT 'aditya@demo.com',35 UNION SELECT 'meera@demo.com',75 UNION SELECT 'sahil@demo.com',50) s ON s.e=u.email;

INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='SQL'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,50 sc UNION SELECT 'arjun@demo.com',40 UNION SELECT 'sneha@demo.com',65 UNION SELECT 'vikram@demo.com',55 UNION SELECT 'ishita@demo.com',80 UNION SELECT 'rohan@demo.com',45 UNION SELECT 'kavya@demo.com',70 UNION SELECT 'aditya@demo.com',60 UNION SELECT 'meera@demo.com',35 UNION SELECT 'sahil@demo.com',55) s ON s.e=u.email;

INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='Machine Learning'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,35 sc UNION SELECT 'arjun@demo.com',30 UNION SELECT 'sneha@demo.com',45 UNION SELECT 'vikram@demo.com',70 UNION SELECT 'ishita@demo.com',85 UNION SELECT 'rohan@demo.com',25 UNION SELECT 'kavya@demo.com',55 UNION SELECT 'aditya@demo.com',40 UNION SELECT 'meera@demo.com',65 UNION SELECT 'sahil@demo.com',30) s ON s.e=u.email;

INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='Data Analysis'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,60 sc UNION SELECT 'arjun@demo.com',50 UNION SELECT 'sneha@demo.com',40 UNION SELECT 'vikram@demo.com',75 UNION SELECT 'ishita@demo.com',80 UNION SELECT 'rohan@demo.com',55 UNION SELECT 'kavya@demo.com',70 UNION SELECT 'aditya@demo.com',65 UNION SELECT 'meera@demo.com',50 UNION SELECT 'sahil@demo.com',45) s ON s.e=u.email;

INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='Web Development'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,55 sc UNION SELECT 'arjun@demo.com',80 UNION SELECT 'sneha@demo.com',70 UNION SELECT 'vikram@demo.com',40 UNION SELECT 'ishita@demo.com',60 UNION SELECT 'rohan@demo.com',75 UNION SELECT 'kavya@demo.com',45 UNION SELECT 'aditya@demo.com',85 UNION SELECT 'meera@demo.com',60 UNION SELECT 'sahil@demo.com',50) s ON s.e=u.email;

INSERT INTO `student_skills` (`student_id`,`skill_id`,`score`)
SELECT u.id, (SELECT id FROM skills WHERE skill_name='Java'), s.sc
FROM `users` u JOIN (SELECT 'priya@demo.com' e,70 sc UNION SELECT 'arjun@demo.com',60 UNION SELECT 'sneha@demo.com',55 UNION SELECT 'vikram@demo.com',85 UNION SELECT 'ishita@demo.com',65 UNION SELECT 'rohan@demo.com',30 UNION SELECT 'kavya@demo.com',60 UNION SELECT 'aditya@demo.com',75 UNION SELECT 'meera@demo.com',45 UNION SELECT 'sahil@demo.com',55) s ON s.e=u.email;

-- Applications from example students to the demo industry postings
-- Junior Software Engineer (job, id 7): priya applied+shortlisted, arjun applied+test_pending, vikram rejected, ishita applied+pending
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'shortlisted' FROM `users` u, `internships` i WHERE u.email='priya@demo.com' AND i.title='Junior Software Engineer'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'test_pending' FROM `users` u, `internships` i WHERE u.email='arjun@demo.com' AND i.title='Junior Software Engineer'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'rejected' FROM `users` u, `internships` i WHERE u.email='vikram@demo.com' AND i.title='Junior Software Engineer'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'applied' FROM `users` u, `internships` i WHERE u.email='ishita@demo.com' AND i.title='Junior Software Engineer'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);

-- Software Development Intern (internship, id 4): sneha applied, rohan test_pending, kavya rejected, aditya shortlisted
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'applied' FROM `users` u, `internships` i WHERE u.email='sneha@demo.com' AND i.title='Software Development Intern'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'test_pending' FROM `users` u, `internships` i WHERE u.email='rohan@demo.com' AND i.title='Software Development Intern'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'rejected' FROM `users` u, `internships` i WHERE u.email='kavya@demo.com' AND i.title='Software Development Intern'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
INSERT INTO `applications` (`student_id`,`internship_id`,`status`)
SELECT u.id, i.id, 'shortlisted' FROM `users` u, `internships` i WHERE u.email='aditya@demo.com' AND i.title='Software Development Intern'
AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.student_id=u.id AND a.internship_id=i.id);
