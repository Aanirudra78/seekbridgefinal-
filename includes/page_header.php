<?php
// page_header.php - Student dashboard header with professional TOP navigation bar
// Requires db_connect.php already included AND require_role() already called
// Set $pageTitle and $activeNav before including this.

$activeNav = isset($activeNav) ? $activeNav : '';
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$uname = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(substr($uname, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?> | <?php echo e($uname); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="topnav-layout">

<!-- Top navigation bar -->
<nav class="navbar navbar-expand-lg portal-navbar sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand brand-mark" href="student_dashboard.php">
      <span class="navbar-logo"><img src="assets/img/technova-logo.png" alt="SeekBridge"></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="studentNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='dashboard'?'active':''; ?>" href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='skill'?'active':''; ?>" href="skill_assessment.php"><i class="fa-solid fa-clipboard-question"></i> Skill Test</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='internships'?'active':''; ?>" href="internships.php"><i class="fa-solid fa-briefcase"></i> Internships / Jobs</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='courses'?'active':''; ?>" href="courses.php"><i class="fa-solid fa-book-open"></i> Courses</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='certifications'?'active':''; ?>" href="certifications.php"><i class="fa-solid fa-award"></i> Certification</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='portfolio'?'active':''; ?>" href="portfolio.php"><i class="fa-solid fa-id-card"></i> Portfolio</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='tracker'?'active':''; ?>" href="my_applications.php"><i class="fa-solid fa-magnifying-glass-chart"></i> Applications</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-primary" href="profile.php"><i class="fa-solid fa-gear"></i> <span class="d-none d-lg-inline">Profile</span></a>
        <div class="user-chip-light d-flex align-items-center gap-2">
          <span class="avatar-sm"><?php echo e($initial); ?></span>
          <span class="d-none d-md-inline"><?php echo e($uname); ?></span>
        </div>
        <a class="btn btn-sm btn-accent" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span class="d-none d-lg-inline">Logout</span></a>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4 page-wrap mx-auto">