# SeekBridge — Academia-Industry Collaboration Portal

A PHP + MySQL web portal connecting **students**, **industries**, **academicians**, and **institutions** for internships, skill assessment, courses, and collaboration.

> Live deployment: `http://seekbridge.rf.gd` (InfinityFree)

## Tech Stack
- **Backend:** PHP (procedural, mysqli, no framework)
- **Database:** MySQL (via phpMyAdmin)
- **Frontend:** HTML, CSS, Bootstrap 5, vanilla JavaScript + Chart.js (local `assets/js/chart.umd.min.js`)

## Directory Structure
```
seekbridge/
├── index.php                    # Landing page
├── register.php                 # Role selection + dynamic registration
├── login.php                    # Login + role-based redirect
├── logout.php                   # Session destroy
├── student_dashboard.php        # Student dashboard (skill charts)
├── skill_assessment.php         # MCQ skill quiz (auto score)
├── internships.php              # Search/filter/apply internships & jobs
├── courses.php                  # Recommended courses (by skill gaps)
├── my_applications.php          # Student's application list
├── application_tracker.php      # Application status table
├── portfolio.php                # Student projects/certificates/skills CRUD
├── certifications.php           # Student certifications view
├── profile.php                  # Edit info, college code, password, consent (all roles)
├── student_profile_view.php     # Public student profile view
├── industry_dashboard.php       # Industry dashboard (charts)
├── industry_post.php            # Post internship/job + create test (manual/auto)
├── industry_applications.php    # Received applications + status management
├── industry_courses.php         # Industry course listing
├── industry_profile.php         # Company info / about
├── industry_programs.php        # Industry programs
├── academician_dashboard.php    # Academician dashboard (collab interest donut)
├── academician_consultancy.php  # Consultancy opportunities
├── academician_courses.php      # Academician courses
├── academician_fdp.php          # FDP listings
├── academician_mentorship.php   # Mentorship programs
├── institution_dashboard.php    # Institution dashboard (college code + verified students)
├── assets/
│   ├── css/style.css
│   ├── img/technova-logo.*
│   ├── js/apply.js, register.js, chart.umd.min.js
├── includes/
│   ├── db_connect.php           # Central mysqli connection + helpers
│   ├── auth_header.php / auth_footer.php
│   ├── page_header*.php / page_footer*.php  # Role-based layouts
│   └── question_bank.php        # Shared question bank for tests
└── database/
    ├── schema.sql               # Full DB schema + seed skills
    ├── seed_data.sql / seed_students.sql   # Demo data + accounts
    ├── migration_mvp.sql / migration_academician.sql / migration_industry.sql
    └── migration_live.sql       # Live deploy migration (idempotent)
```

## Setup (XAMPP / localhost)
1. **Start XAMPP** → start **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\seekbridge`.
3. Create DB in phpMyAdmin (`http://localhost/phpmyadmin`):
   - New database → e.g. `if0_42787265_technova66`
   - Import `database/schema.sql`, then `database/migration_mvp.sql`, `database/migration_academician.sql`, `database/migration_industry.sql`, and optionally `database/seed_data.sql`.
4. Default local credentials are already set in `includes/db_connect.php`:
   ```
   DB_HOST = localhost | DB_USER = root | DB_PASS = '' | DB_NAME = if0_42787265_technova66
   ```
5. Open `http://localhost/seekbridge/index.php`

## Demo Accounts (two-step login: pick role first, then email + password)
| Role      | Email                | Password    |
|-----------|----------------------|-------------|
| Student   | `student@demo.com`   | `student123`|
| Teacher   | `teacher@demo.com`   | `teacher123`|
| Industry  | `industry@demo.com`  | `industry123`|
| College   | `college@demo.com`   | `college123`|

## Deploy on InfinityFree (live)
1. Upload the whole folder to InfinityFree **File Manager → htdocs** (folders merge, files overwrite).
2. **Do NOT overwrite** the production `includes/db_connect.php` (it has the live DB credentials) — or re-fill it with your production values before upload.
3. In cPanel **phpMyAdmin**, select your DB (e.g. `if0_42874072_seekbridge1`) and import `database/migration_live.sql` (safe to run twice).
4. Open your site URL. Verify:
   - College login → college code banner + verified students
   - Teacher login → Collaboration Interest donut chart
   - Student profile → "College Identification Code" field
   - Dashboards show charts (Chart.js is bundled locally)

## Security Notes
- All SQL uses **prepared statements** (mysqli).
- Passwords hashed with `password_hash()` / verified with `password_verify()`.
- Every protected page calls `require_role()` to enforce session-based role checks.
- Output escaped with `htmlspecialchars()` (helper `e()`) to prevent XSS.

## Default User Roles
- **Student** → college, course/branch, year → `student_details`
- **Industry** → company, type, size, website → `industry_details`
- **Academician** → college, department, designation → `academician_details`
- **Institution** → institution name, type, location → `institution_details`