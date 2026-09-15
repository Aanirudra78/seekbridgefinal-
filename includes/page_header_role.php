<?php
// page_header_role.php - Generic dashboard header with professional TOP navigation bar
// Use: set $pageTitle before including. Assumes db_connect.php included + require_role() called.
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$activeNav = isset($activeNav) ? $activeNav : '';
$uname = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(substr($uname, 0, 1));

// Per-role config
$roleLabel = 'User';
$roleIcon = 'fa-user';
$dashboardPage = 'industry_dashboard.php';
switch ($_SESSION['role'] ?? '') {
    case 'industry':    $roleLabel = 'Industry'; $roleIcon = 'fa-industry'; $dashboardPage = 'industry_dashboard.php'; break;
    case 'academician': $roleLabel = 'Academician'; $roleIcon = 'fa-chalkboard-user'; $dashboardPage = 'academician_dashboard.php'; break;
    case 'institution': $roleLabel = 'Institution'; $roleIcon = 'fa-building-columns'; $dashboardPage = 'institution_dashboard.php'; break;
}
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

<nav class="navbar navbar-expand-lg portal-navbar sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand brand-mark" href="<?php echo $dashboardPage; ?>">
      <span class="navbar-logo"><img src="assets/img/technova-logo.png" alt="SeekBridge"></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#roleNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="roleNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='dashboard'?'active':''; ?>" href="<?php echo $dashboardPage; ?>"><i class="fa-solid fa-house"></i> <?php echo e($roleLabel); ?> Dashboard</a></li>
        <?php if (($_SESSION['role'] ?? '') === 'academician'): ?>
          <li class="nav-item"><a class="nav-link <?php echo $activeNav==='courses'?'active':''; ?>" href="academician_courses.php"><i class="fa-solid fa-book-open"></i> Courses</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $activeNav==='fdp'?'active':''; ?>" href="academician_fdp.php"><i class="fa-solid fa-calendar-check"></i> FDPs &amp; Training</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $activeNav==='consultancy'?'active':''; ?>" href="academician_consultancy.php"><i class="fa-solid fa-lightbulb"></i> Consultancy &amp; Research</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $activeNav==='mentorship'?'active':''; ?>" href="academician_mentorship.php"><i class="fa-solid fa-people-group"></i> Mentorship &amp; Lectures</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link <?php echo $activeNav==='profile'?'active':''; ?>" href="profile.php"><i class="fa-solid fa-gear"></i> Profile</a></li>
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