<?php
// auth_header.php - Head section + public navbar for public/auth pages
$pageTitle = isset($pageTitle) ? $pageTitle : 'Academia-Industry Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?> | TechNova — Academia-Industry Portal</title>
  <meta name="description" content="SeekBridge connects students, industries, academicians and institutions through internships, skill assessments and collaboration.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<!-- Public top navbar -->
<nav class="navbar navbar-expand-lg public-navbar sticky-top" id="publicNav">
  <div class="container">
    <a class="navbar-brand brand-mark p-0" href="index.php">
      <span class="navbar-logo"><img src="assets/img/technova-logo.png" alt="SeekBridge"></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#authNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="authNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Platform</a></li>
        <li class="nav-item"><a class="nav-link" href="#how">How it works</a></li>
        <li class="nav-item ms-lg-2"><a class="nav-link" href="login.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</a></li>
        <li class="nav-item ms-lg-2"><a class="btn btn-accent btn-sm px-3" href="register.php"><i class="fa-solid fa-user-plus me-1"></i> Register</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="auth-main">