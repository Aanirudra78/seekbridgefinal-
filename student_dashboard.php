<?php
// student_dashboard.php - Student dashboard home (analytics + recommendations)
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];

// ---- Fetch student_details ----
$details = array('college_name'=>'','course_branch'=>'','year'=>'');
$st = db_query('SELECT college_name, course_branch, year FROM student_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = $dr;
mysqli_stmt_close($st);

// ---- Fetch student skills (assessment) ----
$skills = array();
$st = db_query(
  'SELECT s.skill_name, ss.score FROM student_skills ss
   JOIN skills s ON s.id = ss.skill_id
   WHERE ss.student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $skills[] = $row; }
mysqli_stmt_close($st);
$hasAssessment = count($skills) > 0;

// strengths = skills with score >= 70, gaps = skills with score < 70
$strengths = array_filter($skills, fn($s) => $s['score'] >= 70);
$gaps = array_filter($skills, fn($s) => $s['score'] < 70);

// ---- Quick stats + status breakdown ----
$totalApps = 0; $pending = 0; $shortlisted = 0; $rejected = 0;
$statusCount = array('applied'=>0,'test_pending'=>0,'shortlisted'=>0,'rejected'=>0);
$st = db_query('SELECT COUNT(*) AS c, status FROM applications WHERE student_id = ? GROUP BY status', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) {
    $totalApps += (int)$row['c'];
    if (isset($statusCount[$row['status']])) $statusCount[$row['status']] += (int)$row['c'];
}
mysqli_stmt_close($st);
$pending = $statusCount['applied'] + $statusCount['test_pending'];
$shortlisted = $statusCount['shortlisted'];
$rejected = $statusCount['rejected'];

// "courses completed" - count from portfolio_certificates as a proxy
$coursesDone = 0;
$st = db_query('SELECT COUNT(*) AS c FROM portfolio_certificates WHERE student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$r2 = mysqli_fetch_assoc($res);
mysqli_free_result($res);
$coursesDone = (int)($r2['c'] ?? 0);
mysqli_stmt_close($st);

// ---- Recommended courses (based on skill gaps) ----
$recommendedCourses = array();
$st = db_query('SELECT * FROM courses ORDER BY id DESC LIMIT 6');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $recommendedCourses[] = $row; }
mysqli_stmt_close($st);

// ---- Recommended internships ----
$recommendedInternships = array();
$st = db_query(
  'SELECT i.*, u.name AS company FROM internships i
   JOIN users u ON u.id = i.industry_id
   ORDER BY i.posted_at DESC
   LIMIT 6');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $recommendedInternships[] = $row; }
mysqli_stmt_close($st);

// ---- Recent activity ----
$activity = array();
$st = db_query(
  'SELECT a.status, a.applied_at, i.title FROM applications a
   JOIN internships i ON i.id = a.internship_id
   WHERE a.student_id = ?
   ORDER BY a.applied_at DESC LIMIT 6', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $activity[] = $row; }
mysqli_stmt_close($st);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require 'includes/page_header.php';
?>

<!-- Top welcome -->
<div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-3">
  <div>
    <div class="d-flex align-items-center gap-3">
      <div class="avatar-sm" style="width:56px;height:56px;font-size:1.4rem;"><?php echo e(strtoupper(substr($_SESSION['name'], 0, 1))); ?></div>
      <div>
        <h4 class="mb-1">Hello, <?php echo e($_SESSION['name']); ?> 👋</h4>
        <p class="mb-0">
          <i class="fa-solid fa-building-columns me-1"></i>
          <?php echo e($details['college_name'] ?: 'Your college'); ?>
          &middot; <?php echo e($details['course_branch'] ?: 'Course'); ?>
          <?php if ($details['year']): ?> &middot; Year <?php echo e($details['year']); ?><?php endif; ?>
        </p>
      </div>
    </div>
  </div>
  <div class="heading-actions d-flex gap-2 flex-wrap">
    <a href="skill_assessment.php" class="btn btn-accent btn-sm"><i class="fa-solid fa-clipboard-question me-1"></i> Skill Test</a>
    <a href="internships.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-briefcase me-1"></i> Browse Internships / Jobs</a>
  </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-navy">
      <div class="stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
      <div><div class="stat-num"><?php echo $totalApps; ?></div><div class="stat-label">Total Applications</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-accent">
      <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
      <div><div class="stat-num"><?php echo $pending; ?></div><div class="stat-label">Pending / In Progress</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-success-2">
      <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
      <div><div class="stat-num"><?php echo $shortlisted; ?></div><div class="stat-label">Shortlisted</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-danger-2">
      <div class="stat-icon"><i class="fa-solid fa-award"></i></div>
      <div><div class="stat-num"><?php echo $coursesDone; ?></div><div class="stat-label">Courses Completed</div></div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Skill profile summary + radar chart -->
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-simple"></i></div>
          <div><h6 class="mb-0">Skill Profile Summary</h6><small>Your assessed skills at a glance</small></div>
          <span class="ms-auto badge <?php echo $hasAssessment ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $hasAssessment ? count($skills) . ' skills assessed' : 'Not assessed'; ?></span>
        </div>

        <?php if (!$hasAssessment): ?>
          <div class="text-center py-4">
            <i class="fa-solid fa-clipboard-question text-primary" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted-2">You haven't taken the skill assessment yet. Unlock personalised recommendations and let companies see your strengths.</p>
            <a href="skill_assessment.php" class="btn btn-navy"><i class="fa-solid fa-play"></i> Start Assessment</a>
          </div>
        <?php else: ?>
          <div class="row g-4 align-items-center">
            <div class="col-md-6">
              <div class="chart-box">
                <canvas id="skillRadar" data-skills='<?php echo json_encode(array_column($skills, 'skill_name')); ?>' data-scores='<?php echo json_encode(array_column($skills, 'score')); ?>'></canvas>
              </div>
            </div>
            <div class="col-md-6">
              <?php if (count($strengths) > 0): ?>
                <p class="mb-2 fw-semibold small text-success"><i class="fa-solid fa-thumbs-up"></i> Strengths</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <?php foreach ($strengths as $s): ?>
                    <span class="badge text-bg-success"><?php echo e($s['skill_name']); ?> (<?php echo (int)$s['score']; ?>/100)</span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (count($gaps) > 0): ?>
                <p class="mb-2 fw-semibold small text-warning"><i class="fa-solid fa-brain"></i> Areas to Improve</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <?php foreach ($gaps as $g): ?>
                    <span class="badge text-bg-warning"><?php echo e($g['skill_name']); ?> (<?php echo (int)$g['score']; ?>/100)</span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-success mb-3">Great! You have strong scores across all assessed skills.</div>
              <?php endif; ?>

              <div class="d-flex flex-wrap gap-2">
                <a href="skill_assessment.php" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate me-1"></i> Retake</a>
                <a href="portfolio.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-id-card me-1"></i> View Portfolio</a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Application status doughnut -->
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
          <div><h6 class="mb-0">Application Status</h6><small><?php echo $totalApps; ?> total application(s)</small></div>
          <a href="my_applications.php" class="ms-auto btn btn-sm btn-outline-primary">Track</a>
        </div>

        <?php if ($totalApps === 0): ?>
          <div class="text-center py-4">
            <i class="fa-solid fa-briefcase text-muted" style="font-size:2.6rem;"></i>
            <p class="mt-3 mb-2 text-muted-2">No applications yet. Start applying to internships &amp; jobs.</p>
            <a href="internships.php" class="btn btn-sm btn-accent"><i class="fa-solid fa-paper-plane me-1"></i> Browse Openings</a>
          </div>
        <?php else: ?>
          <div class="chart-box chart-box-sm">
            <canvas id="appDoughnut" data-counts='<?php echo json_encode(array_values($statusCount)); ?>'></canvas>
          </div>
          <div class="chart-legend justify-content-center" id="appLegend"></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">

  <!-- Recent activity -->
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <div><h6 class="mb-0">Recent Activity</h6><small>Your latest application moves</small></div>
        </div>
        <?php if (count($activity) === 0): ?>
          <p class="text-muted-2 mb-0">No activity yet. Browse internships to get started.</p>
        <?php else: ?>
          <div class="timeline">
            <?php
            $statusMeta = array(
              'applied'      => array('icon' => 'fa-file-signature', 'label' => 'Applied'),
              'test_pending' => array('icon' => 'fa-hourglass-half', 'label' => 'Test Pending'),
              'shortlisted'  => array('icon' => 'fa-check-double',   'label' => 'Shortlisted'),
              'rejected'     => array('icon' => 'fa-circle-xmark',   'label' => 'Rejected'),
              'selected'     => array('icon' => 'fa-medal',          'label' => 'Selected'),
            );
            ?>
            <?php foreach ($activity as $a): ?>
              <?php $sm = $statusMeta[$a['status']] ?? array('icon' => 'fa-file', 'label' => ucwords(str_replace('_', ' ', $a['status']))); ?>
              <div class="tl-item tl-<?php echo e($a['status']); ?>">
                <div class="tl-head">
                  <span class="tl-icon"><i class="fa-solid <?php echo $sm['icon']; ?>"></i></span>
                  <span class="tl-text fw-semibold small">Applied to <strong><?php echo e($a['title']); ?></strong></span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="badge-status badge-<?php echo e($a['status']); ?>"><?php echo e($sm['label']); ?></span>
                  <span class="tl-time"><i class="fa-regular fa-clock me-1"></i><?php echo date('d M Y, g:i A', strtotime($a['applied_at'])); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Profile completeness + skills tiles -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-gauge-high"></i></div>
          <div><h6 class="mb-0">Profile Pulse</h6><small>Quick stats about your profile</small></div>
        </div>

        <div class="row g-3">
          <?php if ($hasAssessment): ?>
            <?php foreach (array_slice($skills, 0, 4) as $sk): ?>
              <div class="col-md-6">
                <div class="skill-tile">
                  <div class="st-name"><?php echo e($sk['skill_name']); ?></div>
                  <div class="st-bar"><span style="width:<?php echo (int)$sk['score']; ?>%;background:<?php echo (int)$sk['score'] >= 70 ? '#2e9e5b' : '#f0a500'; ?>;"></span></div>
                  <div class="st-meta"><?php echo (int)$sk['score']; ?>/100 &middot; <?php echo (int)$sk['score'] >= 70 ? '<span class="text-success">Strength</span>' : '<span class="text-warning">Improve</span>'; ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (count($skills) > 4): ?>
              <div class="col-12"><p class="small text-muted-2 mb-0">+<?php echo count($skills) - 4; ?> more skill(s) — <a href="skill_assessment.php">assess now</a></p></div>
            <?php endif; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-light mb-0"><i class="fa-solid fa-circle-info me-1 text-primary"></i> Complete the <a href="skill_assessment.php">skill assessment</a> to see your profile pulse.</div>
            </div>
          <?php endif; ?>
        </div>

        <hr class="my-4">
        <div class="row text-center g-2">
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo count($skills); ?></div><div class="lbl">Assessed skills</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $coursesDone; ?></div><div class="lbl">Certifications</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $shortlisted; ?></div><div class="lbl">Shortlisted</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $rejected; ?></div><div class="lbl">Rejected</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recommended courses -->
<div class="mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-book-open me-2"></i>Recommended Courses For You</h5>
    <a href="courses.php" class="btn btn-sm btn-navy">View All</a>
  </div>
  <div class="row g-3">
    <?php foreach (array_slice($recommendedCourses, 0, 3) as $c): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-hover h-100">
          <div class="card-body">
            <span class="badge bg-navy mb-2"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
            <h6 class="fw-bold"><?php echo e($c['title']); ?></h6>
            <p class="small text-muted-2 mb-2">Platform: <?php echo e($c['platform'] ?: 'N/A'); ?></p>
            <?php if ($c['link']): ?>
              <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-sm btn-navy"><i class="fa-solid fa-arrow-up-right-from-square"></i> Start Learning</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (count($recommendedCourses) === 0): ?>
      <div class="col-12"><div class="alert alert-light">No courses available yet. Check back soon.</div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Recommended internships -->
<div class="mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-briefcase me-2"></i>Matching Internships &amp; Jobs</h5>
    <a href="internships.php" class="btn btn-sm btn-outline-primary">View All</a>
  </div>
  <div class="row g-3">
    <?php foreach (array_slice($recommendedInternships, 0, 3) as $in): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-hover h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-bold mb-0"><?php echo e($in['title']); ?></h6>
              <span class="badge <?php echo $in['type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($in['type'])); ?></span>
            </div>
            <p class="small text-primary mb-2"><i class="fa-solid fa-building"></i> <?php echo e($in['company']); ?></p>
            <p class="small text-muted-2 mb-3"><?php echo e(substr(strip_tags($in['description']), 0, 120)); ?>...</p>
            <a href="internships.php" class="btn btn-sm btn-accent"><i class="fa-solid fa-paper-plane"></i> Apply</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (count($recommendedInternships) === 0): ?>
      <div class="col-12"><div class="alert alert-light">No internships posted yet.</div></div>
    <?php endif; ?>
  </div>
</div>

<script>
  (function () {
    function initCharts() {
      if (typeof Chart === 'undefined') return;

      // ---- Skill radar chart ----
    var radar = document.getElementById('skillRadar');
    if (radar && radar.dataset.skills && radar.dataset.skills !== '[]') {
      var rSkills, rScores;
      try {
        rSkills = JSON.parse(radar.dataset.skills);
        rScores = JSON.parse(radar.dataset.scores).map(Number);
      } catch (e) { rSkills = rScores = null; }
      if (rSkills && rScores.length > 0) {
        new Chart(radar, {
          type: 'radar',
          data: {
            labels: rSkills,
            datasets: [{
              label: 'Skill Score',
              data: rScores,
              backgroundColor: 'rgba(59,111,224,0.16)',
              borderColor: '#3b6fe0',
              borderWidth: 2,
              pointBackgroundColor: rScores.map(function (s) { return s >= 70 ? '#1f9d5c' : '#f0a500'; }),
              pointRadius: 4,
              pointHoverRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
              r: {
                min: 0, max: 100,
                ticks: { stepSize: 25, backdropColor: 'transparent', color: '#a7b1c5', font: { size: 10 } },
                grid: { color: '#e8eef8' },
                pointLabels: { font: { size: 11, weight: '600' }, color: '#34405a' }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (ctx) { return ' ' + ctx.label + ': ' + ctx.raw + '/100'; }
                }
              }
            }
          }
        });
      }
    }

    // ---- Application status doughnut ----
    var app = document.getElementById('appDoughnut');
    if (app && app.dataset.counts) {
      var counts;
      try { counts = JSON.parse(app.dataset.counts).map(Number); }
      catch (e) { counts = null; }
      if (counts && counts.reduce(function (a, b) { return a + b; }, 0) > 0) {
        var labels = ['Applied', 'Test Pending', 'Shortlisted', 'Rejected'];
        var colors = ['#3b6fe0', '#f0a500', '#1f9d5c', '#e04f4f'];
        var seen = counts.map(function (c, i) { return c > 0; });
        var fLabels = labels.filter(function (_, i) { return seen[i]; });
        var fData = counts.filter(function (_, i) { return seen[i]; });
        var fCols = colors.filter(function (_, i) { return seen[i]; });

        new Chart(app, {
          type: 'doughnut',
          data: { labels: fLabels, datasets: [{ data: fData, backgroundColor: fCols, borderWidth: 3, borderColor: '#fff' }] },
          options: {
            responsive: true, maintainAspectRatio: true, cutout: '68%',
            plugins: { legend: { display: false } }
          }
        });

        var leg = document.getElementById('appLegend');
        if (leg) {
          leg.innerHTML = fLabels.map(function (l, i) {
            return '<span class="cl-item"><span class="cl-dot" style="background:' + fCols[i] + ';"></span>' + l +
                   ' <strong>' + fData[i] + '</strong></span>';
          }).join('');
        }
      }
    }

    } // end initCharts

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCharts);
    } else {
      initCharts();
    }
  })();
</script>

<?php require 'includes/page_footer.php'; ?>