<?php
// page_header_institution.php - Institution dashboard header with professional TOP navigation bar
// Assumes db_connect.php included + require_role('institution') called.
$section = isset($_GET['s']) ? $_GET['s'] : 'jobs';
$pageTitle = isset($pageTitle) ? $pageTitle : 'Institution Dashboard';
$uname = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(substr($uname, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?> | Institution</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="topnav-layout">

<nav class="navbar navbar-expand-lg portal-navbar sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand brand-mark" href="institution_dashboard.php">
      <span class="navbar-logo"><img src="assets/img/technova-logo.png" alt="SeekBridge"></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#instNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="instNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link <?php echo $section==='jobs'?'active':''; ?>" href="institution_dashboard.php?s=jobs"><i class="fa-solid fa-briefcase"></i> Jobs</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $section==='internship'?'active':''; ?>" href="institution_dashboard.php?s=internship"><i class="fa-solid fa-rocket"></i> Internship</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $section==='skill'?'active':''; ?>" href="institution_dashboard.php?s=skill"><i class="fa-solid fa-chart-simple"></i> Skill</a></li>
        <li class="nav-item"><a class="nav-link <?php echo ($section==='students' && isset($_GET['spage'])) ? 'active' : ''; ?>" href="student_search.php"><i class="fa-solid fa-magnifying-glass"></i> Students</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $section==='industry'?'active':''; ?>" href="institution_dashboard.php?s=industry"><i class="fa-solid fa-industry"></i> Industry</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $section==='about'?'active':''; ?>" href="institution_dashboard.php?s=about"><i class="fa-solid fa-building-columns"></i> About</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <div class="user-chip-light d-flex align-items-center gap-2">
          <span class="avatar-sm"><?php echo e($initial); ?></span>
          <span class="d-none d-md-inline"><?php echo e($uname); ?></span>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="profile.php"><i class="fa-solid fa-gear"></i> Profile</a>
        <a class="btn btn-sm btn-accent" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span class="d-none d-lg-inline">Logout</span></a>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4 page-wrap mx-auto">