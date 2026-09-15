<?php
// academician_dashboard.php - Overview: upcoming FDPs, active collaborations,
// recent interest activity.
require_once 'includes/db_connect.php';
require_role('academician');

$uid = (int) $_SESSION['user_id'];

$details = array('college_name'=>'','department'=>'','designation'=>'');
$st = db_query('SELECT college_name, department, designation FROM academician_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = array_merge($details, $dr);
mysqli_stmt_close($st);

$st = db_query('SELECT id FROM academician_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$ad = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($st);
$acadId = $ad ? (int) $ad['id'] : 0;

$upcomingFdps = 0;
$st = db_query("SELECT COUNT(*) AS c FROM fdp_programs WHERE start_date >= CURDATE() OR start_date IS NULL");
$res = mysqli_stmt_get_result($st);
if ($r = mysqli_fetch_assoc($res)) $upcomingFdps = (int) $r['c'];
mysqli_stmt_close($st);

$myInterests = 0;
$st = db_query('SELECT COUNT(*) AS c FROM interests WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
if ($r = mysqli_fetch_assoc($res)) $myInterests = (int) $r['c'];
mysqli_stmt_close($st);

$openResearch = 0;
$st = db_query("SELECT COUNT(*) AS c FROM research_projects WHERE status = 'open'");
$res = mysqli_stmt_get_result($st);
if ($r = mysqli_fetch_assoc($res)) $openResearch = (int) $r['c'];
mysqli_stmt_close($st);

$consultCount = 0;
$st = db_query('SELECT COUNT(*) AS c FROM consultancy_opportunities');
$res = mysqli_stmt_get_result($st);
if ($r = mysqli_fetch_assoc($res)) $consultCount = (int) $r['c'];
mysqli_stmt_close($st);

$st = db_query('SELECT m.schedule_date, m.title FROM mentorship_programs m WHERE m.schedule_date >= CURDATE() ORDER BY m.schedule_date LIMIT 3');
$res = mysqli_stmt_get_result($st);
$upcomingLectures = array();
while ($r = mysqli_fetch_assoc($res)) $upcomingLectures[] = $r;
mysqli_stmt_close($st);

// recent activity: my interests + my posted research
$activity = array();
$st = db_query('SELECT i.target_type, i.target_id, i.created_at FROM interests i WHERE i.user_id = ? ORDER BY i.created_at DESC LIMIT 8', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$interestRows = array();
while ($r = mysqli_fetch_assoc($res)) $interestRows[] = $r;
mysqli_stmt_close($st);

$consultTitles = array();
$st = db_query('SELECT id, title FROM consultancy_opportunities');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $consultTitles[(int) $r['id']] = $r['title'];
mysqli_stmt_close($st);

$mentorTitles = array();
$st = db_query('SELECT id, title FROM mentorship_programs');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $mentorTitles[(int) $r['id']] = $r['title'];
mysqli_stmt_close($st);

$researchTitles = array();
$st = db_query('SELECT id, title FROM research_projects');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $researchTitles[(int) $r['id']] = $r['title'];
mysqli_stmt_close($st);

$typeName = array('consultancy'=>'Consultancy','mentorship'=>'Mentorship / Lecture','research'=>'Research');
$typeIcon = array('consultancy'=>'fa-lightbulb','mentorship'=>'fa-people-group','research'=>'fa-flask');
foreach ($interestRows as $ir) {
    $pool = ($ir['target_type'] === 'consultancy') ? $consultTitles
          : (($ir['target_type'] === 'mentorship') ? $mentorTitles : $researchTitles);
    $title = isset($pool[(int) $ir['target_id']]) ? $pool[(int) $ir['target_id']] : 'Item';
    $activity[] = array(
        'icon'   => $typeIcon[$ir['target_type']],
        'text'   => 'You expressed interest in <b>' . e($title) . '</b> (' . $typeName[$ir['target_type']] . ')',
        'time'   => $ir['created_at'],
        'color'  => '#f0a500',
    );
}

if ($acadId) {
    $st = db_query('SELECT title, posted_at FROM research_projects WHERE academician_id = ? ORDER BY posted_at DESC LIMIT 5', 'i', array($acadId));
    $res = mysqli_stmt_get_result($st);
    $myProjects = array();
    while ($r = mysqli_fetch_assoc($res)) $myProjects[] = $r;
    mysqli_stmt_close($st);
    foreach ($myProjects as $p) {
        $activity[] = array(
            'icon'  => 'fa-flask',
            'text'  => 'You published research project <b>' . e($p['title']) . '</b>',
            'time'  => $p['posted_at'],
            'color' => '#1f9d5c',
        );
    }
}

usort($activity, function ($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
$activity = array_slice($activity, 0, 8);

// ---- Interest breakdown by collaboration type ----
$interestByType = array('consultancy'=>0,'mentorship'=>0,'research'=>0);
foreach ($interestRows as $ir) {
    if (isset($interestByType[$ir['target_type']])) $interestByType[$ir['target_type']]++;
}

$pageTitle = 'Academician Dashboard';
$activeNav = 'dashboard';
require 'includes/page_header_role.php';
?>

<div class="page-heading d-flex align-items-center gap-3 mb-4">
  <div class="avatar-sm" style="width:56px;height:56px;font-size:1.4rem;"><?php echo e(strtoupper(substr($_SESSION['name'], 0, 1))); ?></div>
  <div class="flex-grow-1">
    <h4 class="mb-1"><?php echo e($_SESSION['name']); ?></h4>
    <p class="mb-0"><i class="fa-solid fa-chalkboard-user me-1"></i><?php echo e($details['designation'] ?: 'Academician'); ?>
      <?php if ($details['department']): ?> &middot; <?php echo e($details['department']); ?><?php endif; ?>
      <?php if ($details['college_name']): ?> &middot; <?php echo e($details['college_name']); ?><?php endif; ?>
    </p>
  </div>
  <div class="heading-actions d-none d-md-block">
    <span class="badge bg-accent text-dark"><i class="fa-solid fa-people-group me-1"></i> Collaboration Hub</span>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-3">
    <a href="academician_fdp.php" class="text-decoration-none">
      <div class="stat-card bg-navy">
        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div><div class="stat-num"><?php echo $upcomingFdps; ?></div><div class="stat-label">Upcoming FDPs &amp; Training</div></div>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="academician_consultancy.php?tab=consult" class="text-decoration-none">
      <div class="stat-card bg-accent">
        <div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div>
        <div><div class="stat-num"><?php echo $consultCount; ?></div><div class="stat-label">Consultancy Opportunities</div></div>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="academician_consultancy.php?tab=research" class="text-decoration-none">
      <div class="stat-card bg-success-2">
        <div class="stat-icon"><i class="fa-solid fa-flask"></i></div>
        <div><div class="stat-num"><?php echo $openResearch; ?></div><div class="stat-label">Open Research Topics</div></div>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="academician_mentorship.php" class="text-decoration-none">
      <div class="stat-card bg-primary-soft">
        <div class="stat-icon"><i class="fa-solid fa-hand-pointer"></i></div>
        <div><div class="stat-num"><?php echo $myInterests; ?></div><div class="stat-label">My Interests</div></div>
      </div>
    </a>
  </div>
</div>

<!-- Engagement charts -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-handshake"></i></div>
          <div><h6 class="mb-0">Collaboration Interest</h6><small>Your activity across research, mentorship &amp; consultancy</small></div>
        </div>
        <?php if (array_sum($interestByType) === 0): ?>
          <p class="text-muted-2 mb-0">No interest activity yet — express interest in a consultancy, research topic or mentorship program to get started.</p>
        <?php else: ?>
          <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="chart-box chart-box-sm flex-grow-1" style="max-width:220px;">
              <canvas id="engagementDonut"
                data-counts='<?php echo json_encode(array($interestByType['consultancy'], $interestByType['mentorship'], $interestByType['research'])); ?>'
                data-labels='["Consultancy","Mentorship","Research"]'></canvas>
            </div>
            <div class="chart-legend flex-column" id="engagementLegend"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-bolt"></i></div>
          <div><h6 class="mb-0">Quick Start</h6><small>Jump into any section</small></div>
        </div>
        <div class="d-grid gap-2">
          <a href="academician_fdp.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-chalkboard-user me-2"></i>Browse FDPs &amp; Training</a>
          <a href="academician_consultancy.php?tab=consult" class="btn btn-outline-primary text-start"><i class="fa-solid fa-lightbulb me-2"></i>Consultancy Opportunities</a>
          <a href="academician_consultancy.php?tab=research" class="btn btn-outline-primary text-start"><i class="fa-solid fa-flask me-2"></i>Research Collaboration</a>
          <a href="academician_mentorship.php" class="btn btn-outline-primary text-start"><i class="fa-solid fa-people-group me-2"></i>Mentorship &amp; Guest Lectures</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Recent Activity</h6>
        <?php if (count($activity) === 0): ?>
          <p class="text-muted-2 mb-0">No activity yet — express interest in a consultancy, research topic or mentorship program to get started.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($activity as $a): ?>
              <li class="list-group-item px-0 d-flex align-items-start gap-3">
                <span class="avatar-sm" style="background:<?php echo $a['color']; ?>;"><i class="fa-solid <?php echo $a['icon']; ?>"></i></span>
                <div>
                  <div class="small"><?php echo $a['text']; ?></div>
                  <small class="text-muted-2"><i class="fa-regular fa-clock me-1"></i><?php echo e(date('d M Y, h:i A', strtotime($a['time']))); ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-regular fa-calendar"></i></div>
          <div><h6 class="mb-0">Upcoming Mentorship &amp; Lectures</h6><small>Next scheduled sessions</small></div>
        </div>
        <?php if (count($upcomingLectures) === 0): ?>
          <p class="text-muted-2 mb-0">No scheduled sessions yet. Check back soon!</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($upcomingLectures as $l): ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><?php echo e($l['title']); ?></span>
                <span class="badge bg-navy text-nowrap"><?php echo e(date('d M Y', strtotime($l['schedule_date']))); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    function initCharts() {
      if (typeof Chart === 'undefined') return;
      var cv = document.getElementById('engagementDonut');
    if (!cv || !cv.dataset.counts) return;
    var sc, labels;
    try { sc = JSON.parse(cv.dataset.counts).map(Number); labels = JSON.parse(cv.dataset.labels); }
    catch (e) { return; }
    if (sc.reduce(function (a, b) { return a + b; }, 0) === 0) return;
    var colors = ['#3b6fe0', '#f0a500', '#1f9d5c'];
    var seen = sc.map(function (c) { return c > 0; });
    new Chart(cv, {
      type: 'doughnut',
      data: {
        labels: labels.filter(function (_, i) { return seen[i]; }),
        datasets: [{ data: sc.filter(function (_, i) { return seen[i]; }), backgroundColor: colors.filter(function (_, i) { return seen[i]; }), borderWidth: 3, borderColor: '#fff' }]
      },
      options: { responsive: true, maintainAspectRatio: true, cutout: '68%', plugins: { legend: { display: false } } }
    });
    var leg = document.getElementById('engagementLegend');
    if (leg) {
      leg.innerHTML = labels.map(function (l, i) {
        return '<span class="cl-item"><span class="cl-dot" style="background:' + colors[i] + ';"></span>' + l +
               ' <strong>' + sc[i] + '</strong></span>';
      }).join('');
    }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCharts);
    } else {
      initCharts();
    }
  })();
</script>

<?php require 'includes/page_footer_role.php'; ?>