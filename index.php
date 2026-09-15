<?php
// index.php - Premium landing page
require_once 'includes/db_connect.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

// ---- Portal-wide quick stats ----
$stats = array('students'=>0, 'industries'=>0, 'postings'=>0, 'placements'=>0);
$res = mysqli_query($conn, "SELECT role, COUNT(*) c FROM users GROUP BY role");
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['role'] === 'student') $stats['students'] = (int)$row['c'];
    if ($row['role'] === 'industry') $stats['industries'] = (int)$row['c'];
}
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM internships");
if ($res && $r = mysqli_fetch_assoc($res)) $stats['postings'] = (int)$r['c'];
$res = mysqli_query($conn, "SELECT COUNT(*) c FROM applications WHERE status IN ('test_pending','shortlisted')");
if ($res && $r = mysqli_fetch_assoc($res)) $stats['placements'] = (int)$r['c'];

$pageTitle = 'Welcome';
require 'includes/auth_header.php';
?>

<!-- ============ HERO ============ -->
<section class="container pt-5 pb-4">
  <div class="landing-hero">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="badge bg-accent text-dark mb-3"><i class="fa-solid fa-handshake me-1"></i> Bridging Universities &amp; Enterprises</span>
        <h1 class="display-5 mb-3">Shape the workforce of tomorrow with one smart platform</h1>
        <p class="lead mb-4">
          SeekBridge connects <strong>students</strong>, <strong>industries</strong>, <strong>academicians</strong>
          and <strong>institutions</strong> to collaborate on skill development, internships, courses
          and campus-industry partnerships — all in one place.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="register.php" class="btn btn-accent btn-lg px-4"><i class="fa-solid fa-user-plus me-1"></i> Get Started Free</a>
          <a href="login.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-right-to-bracket me-1"></i> Sign In</a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="hero-stats row g-3">
          <div class="col-6">
            <div class="hero-stat">
              <div class="num"><?php echo (int)$stats['students']; ?>+</div>
              <div class="lbl">Students onboard</div>
            </div>
          </div>
          <div class="col-6">
            <div class="hero-stat">
              <div class="num"><?php echo (int)$stats['industries']; ?>+</div>
              <div class="lbl">Industry partners</div>
            </div>
          </div>
          <div class="col-6">
            <div class="hero-stat">
              <div class="num"><?php echo (int)$stats['postings']; ?>+</div>
              <div class="lbl">Internships &amp; jobs</div>
            </div>
          </div>
          <div class="col-6">
            <div class="hero-stat">
              <div class="num"><?php echo (int)$stats['placements']; ?>+</div>
              <div class="lbl">Shortlistings made</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURES / ROLES ============ -->
<section id="features" class="container py-5">
  <div class="text-center mb-5">
    <div class="section-eyebrow">Who it's for</div>
    <h2 class="section-title mb-2">Built for every part of the ecosystem</h2>
    <p class="text-muted-2 mx-auto" style="max-width: 620px;">
      Four powerful experiences, one connected platform. Join as whoever you are and start collaborating.
    </p>
  </div>

  <div class="row g-4">
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <h6>For Students</h6>
        <p class="text-muted-2 mb-3">Skill assessments, personalised courses, internships and a portfolio that gets you noticed.</p>
        <a href="register.php" class="btn btn-sm btn-outline-primary mt-auto"><i class="fa-solid fa-arrow-right me-1"></i> Join as Student</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-industry"></i></div>
        <h6>For Industry</h6>
        <p class="text-muted-2 mb-3">Post internships &amp; jobs, auto-generate skill tests and find job-ready talent fast.</p>
        <a href="register.php" class="btn btn-sm btn-outline-primary mt-auto"><i class="fa-solid fa-arrow-right me-1"></i> Partner with us</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        <h6>For Academicians</h6>
        <p class="text-muted-2 mb-3">Collaborate on curriculum, research and industry-partnered projects that matter.</p>
        <a href="register.php" class="btn btn-sm btn-outline-primary mt-auto"><i class="fa-solid fa-arrow-right me-1"></i> Join as Faculty</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-building-columns"></i></div>
        <h6>For Institutions</h6>
        <p class="text-muted-2 mb-3">Track student skill data and placement analytics to build strong partnerships.</p>
        <a href="register.php" class="btn btn-sm btn-outline-primary mt-auto"><i class="fa-solid fa-arrow-right me-1"></i> Register College</a>
      </div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section id="how" class="py-5" style="background: var(--soft-grey);">
  <div class="container py-3">
    <div class="text-center mb-5">
      <div class="section-eyebrow">Simple process</div>
      <h2 class="section-title mb-2">How it works</h2>
      <p class="text-muted-2">Get started in three easy steps.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card step-card h-100"><div class="card-body text-center">
          <div class="step-num">1</div>
          <h6>Create your account</h6>
          <p class="text-muted-2 mb-0">Pick your profile type — student, industry, academician or institution — and register in under a minute.</p>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card step-card h-100"><div class="card-body text-center">
          <div class="step-num">2</div>
          <h6>Assess &amp; grow skills</h6>
          <p class="text-muted-2 mb-0">Students take a smart skill assessment to unlock personalised course and internship recommendations.</p>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card step-card h-100"><div class="card-body text-center">
          <div class="step-num">3</div>
          <h6>Connect, apply &amp; track</h6>
          <p class="text-muted-2 mb-0">Apply to internships, take screening tests, and track your progress from application to shortlist.</p>
        </div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA BAND ============ -->
<section class="container py-5">
  <div class="cta-band text-center">
    <h3 class="fw-bold mb-2">Ready to bridge academia &amp; industry?</h3>
    <p class="mb-4" style="color: rgba(255,255,255,0.85);">Join TechNova today — it's free to get started.</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="register.php" class="btn btn-accent btn-lg px-4"><i class="fa-solid fa-user-plus me-1"></i> Create Free Account</a>
      <a href="login.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-right-to-bracket me-1"></i> I already have an account</a>
    </div>
  </div>
</section>

<?php require 'includes/auth_footer.php'; ?>