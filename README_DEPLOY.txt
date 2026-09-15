==============================================================
 SEEKBRIDGE - LIVE DEPLOY (InfinityFree) - seekbridge.rf.gd
==============================================================
TOTAL 3 easy steps. ~2 minutes.

STEP 1 - UPLOAD FILES
----------------------
Login to InfinityFree -> cPanel -> File Manager -> htdocs

Upload the CONTENTS of THIS folder into htdocs (same folders merge,
files overwrite). Ye folder ke saare files utha ke htdocs me daal do.

 files:       (sab upload karo, foldar structure same rakho)
   academician_dashboard.php
   application_tracker.php
   assets/css/style.css
   assets/js/chart.umd.min.js     <- ye NAYA hai (local Chart.js)
   assets/img/technova-logo.jpeg
   assets/img/technova-logo.png
   certifications.php
   includes/auth_footer.php
   includes/page_footer.php
   includes/page_footer_role.php
   industry_applications.php
   industry_dashboard.php
   institution_dashboard.php
   my_applications.php
   portfolio.php
   profile.php
   skill_assessment.php
   student_dashboard.php
   database/migration_live.sql

IMPORTANT: includes/db_connect.php uthana is NOT KARNA!
  (live ka db_connect.php already PRODUCTION pe hai aur sahi hai.
   LOCAL wali file uthaoge to live ki DB bikhar jayegi.)

STEP 2 - DATABASE (phpMyAdmin)
-------------------------------
InfinityFree cPanel -> phpMyAdmin
-> left side mein DATABASE CHUNO:  if0_42874072_seekbridge1
-> top mein "Import" tab
-> "Choose File" -> select: database/migration_live.sql
-> "Go" / Import karo
-> "SQL query executed successfully" aana chahiye.
   (Ye script safe/idempotent hai - 2 baar bhala chala do, dikkat nahi.)

STEP 3 - CHECK
---------------
Kholo:  http://seekbridge.rf.gd/index.php  (ya apna domain)
- College login -> dashboard -> college code banner + verified students
- Teacher login -> dashboard -> Collaboration Interest donut (3 slices)
- Student login -> profile -> "College Identification Code" field
- Industry / Student dashboards -> charts dikhne chahiye

AGAR KOI PAGE FATAL ERROR DE DE TO BATAO:
  wo error ka message screenshot/select karke bhej do, main fix kar dunga.
==============================================================