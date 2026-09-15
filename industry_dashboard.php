<?php
// industry_dashboard.php - Industry dashboard home (with analytics)
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];

// ---- Fetch industry (company) details ----
$details = array('company_name'=>'','industry_type'=>'','company_size'=>'','website'=>'');
$st = db_query('SELECT company_name, industry_type, company_size, website FROM industry_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = array_merge($details, $dr);
mysqli_stmt_close($st);

// ---- Stats ----
$myPostings = array();
$st = db_query(
  'SELECT i.*,
     (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id) AS app_count,
     (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id AND a.status = "shortlisted") AS short_count
   FROM internships i WHERE i.industry_id = ? ORDER BY i.posted_at DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $myPostings[] = $row;
mysqli_stmt_close($st);

$totalApps = 0; $shortlisted = 0; $testPending = 0;
foreach ($myPostings as $p) {
    $totalApps += (int)$p['app_count'];
    $shortlisted += (int)$p['short_count'];
}

// ---- Application status distribution ----
$statusCount = array('applied'=>0,'test_pending'=>0,'shortlisted'=>0,'rejected'=>0);
$st = db_query(
  'SELECT a.status, COUNT(*) AS c FROM applications a
   JOIN internships i ON i.id = a.internship_id
   WHERE i.industry_id = ? GROUP BY a.status', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) {
    if (isset($statusCount[$row['status']])) $statusCount[$row['status']] += (int)$row['c'];
}
mysqli_stmt_close($st);
$testPending = $statusCount['test_pending'];

$pageTitle = 'Industry Dashboard';
$activeNav = 'dashboard';
require 'includes/page_header_industry.php';
?>

<!-- Company header -->
<div class="page-heading d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
  <div class="flex-grow-1">
    <h4 class="mb-1"><i class="fa-solid fa-industry me-2"></i><?php echo e($details['company_name'] ?: $_SESSION['name']); ?></h4>
    <p class="mb-0">
      <i class="fa-solid fa-tag me-1"></i><?php echo e($details['industry_type'] ?: 'Industry Partner'); ?>
      <?php if ($details['company_size']): ?> &middot; <?php echo e($details['company_size']); ?> employees<?php endif; ?>
      <?php if ($details['website']): ?> &middot; <a href="<?php echo e($details['website']); ?>" class="text-white fw-semibold" target="_blank"><i class="fa-solid fa-globe me-1"></i><?php echo e($details['website']); ?></a><?php endif; ?>
    </p>
  </div>
  <div class="heading-actions">
    <a href="industry_post.php" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus me-1"></i> Post New Opening</a>
    <a href="industry_profile.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-pen me-1"></i> About / Company</a>
  </div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card bg-navy">
      <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
      <div><div class="stat-num"><?php echo count($myPostings); ?></div><div class="stat-label">Internships / Jobs Posted</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card bg-accent">
      <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
      <div><div class="stat-num"><?php echo $totalApps; ?></div><div class="stat-label">Applications Received</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card bg-warning text-dark" style="background:linear-gradient(135deg,#f0a500,#ffb733);">
      <div class="stat-icon"><i class="fa-solid fa-stopwatch"></i></div>
      <div><div class="stat-num"><?php echo $testPending; ?></div><div class="stat-label">Pending Tests</div></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card bg-success-2">
      <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
      <div><div class="stat-num"><?php echo $shortlisted; ?></div><div class="stat-label">Shortlisted Candidates</div></div>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-column"></i></div>
          <div><h6 class="mb-0">Applications per Opening</h6><small>How each posting is performing</small></div>
        </div>
        <?php if (count($myPostings) === 0): ?>
          <p class="text-muted-2 mb-0">No postings yet. Create an Internship or Job to start receiving applications.</p>
        <?php else: ?>
          <div class="chart-box">
            <canvas id="postingBar"
              data-titles='<?php echo json_encode(array_slice(array_map(fn($p) => mb_substr($p['title'], 0, 22) . (mb_strlen($p['title']) > 22 ? '…' : ''), $myPostings), 0, 8)); ?>'
              data-counts='<?php echo json_encode(array_map(fn($p) => (int)$p['app_count'], array_slice($myPostings, 0, 8))); ?>'></canvas>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
          <div><h6 class="mb-0">Pipeline Health</h6><small>Application status breakdown</small></div>
        </div>
        <?php if ($totalApps === 0): ?>
          <div class="text-center py-4">
            <i class="fa-solid fa-inbox text-muted" style="font-size:2.6rem;"></i>
            <p class="mt-3 mb-2 text-muted-2">No applications received yet.</p>
            <a href="industry_post.php" class="btn btn-sm btn-accent"><i class="fa-solid fa-plus me-1"></i> Post an Opening</a>
          </div>
        <?php else: ?>
          <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="chart-box chart-box-sm flex-grow-1" style="min-width:170px;">
              <canvas id="pipelineDonut" data-counts='<?php echo json_encode(array_values($statusCount)); ?>'></canvas>
            </div>
            <div class="chart-legend flex-column" id="pipelineLegend"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <a href="industry_post.php?type=internship" class="text-decoration-none">
      <div class="card card-hover h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-navy text-white" style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-rocket"></i></div>
        <div><h6 class="fw-bold mb-1 text-dark">Post Internship</h6><p class="small text-muted-2 mb-0">Create a new internship opening with skills, salary, age limit, posts &amp; test.</p></div>
        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
      </div></div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="industry_post.php?type=job" class="text-decoration-none">
      <div class="card card-hover h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-accent text-dark" style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-briefcase"></i></div>
        <div><h6 class="fw-bold mb-1 text-dark">Post Job</h6><p class="small text-muted-2 mb-0">Create a full-time job opening with the same options.</p></div>
        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
      </div></div>
    </a>
  </div>
</div>

<!-- My postings -->
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="card-title mb-0"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>My Posted Openings</h5>
      <a href="industry_post.php" class="btn btn-sm btn-navy"><i class="fa-solid fa-plus"></i> New Posting</a>
    </div>
    <?php if (count($myPostings) === 0): ?>
      <p class="text-muted-2 mb-0">You haven't posted anything yet. Click "Post Internship / Job" to get started.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th>Title</th><th>Type</th><th>Mode</th><th>Salary</th><th>Posts</th><th>Applications</th><th>Shortlisted</th><th>Posted On</th></tr>
          </thead>
          <tbody>
            <?php foreach ($myPostings as $p): ?>
              <tr>
                <td class="fw-semibold"><?php echo e($p['title']); ?></td>
                <td><span class="badge <?php echo $p['type']==='job' ? 'text-bg-warning' : 'text-bg-primary'; ?>"><?php echo e(ucfirst($p['type'])); ?></span></td>
                <td><?php echo e($p['mode'] ?: '-'); ?></td>
                <td><?php echo e($p['salary'] ?: '-'); ?></td>
                <td><?php echo $p['no_of_posts']; ?></td>
                <td><a href="industry_applications.php?posting=<?php echo $p['id']; ?>" class="text-decoration-none"><?php echo $p['app_count']; ?></a></td>
                <td><span class="badge text-bg-success"><?php echo $p['short_count']; ?></span></td>
                <td><?php echo date('d M Y', strtotime($p['posted_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  (function () {
    function initCharts() {
      if (typeof Chart === 'undefined') return;

      // ---- Bar: applications per posting ----
    var bar = document.getElementById('postingBar');
    if (bar && bar.dataset.titles) {
      var titles, cnts;
      try {
        titles = JSON.parse(bar.dataset.titles);
        cnts = JSON.parse(bar.dataset.counts).map(Number);
      } catch (e) { titles = cnts = null; }
      if (titles && titles.length > 0) {
        new Chart(bar, {
          type: 'bar',
          data: {
            labels: titles,
            datasets: [{
              label: 'Applications',
              data: cnts,
              backgroundColor: 'rgba(59,111,224,0.75)',
              hoverBackgroundColor: '#0e2a5c',
              borderRadius: 8,
              maxBarThickness: 44
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0, color: '#a7b1c5' }, grid: { color: '#eef2f9' } },
              x: { ticks: { color: '#6b7890', font: { size: 11 } }, grid: { display: false } }
            }
          }
        });
      }
    }

    // ---- Doughnut: pipeline health ----
    var donut = document.getElementById('pipelineDonut');
    if (donut && donut.dataset.counts) {
      var sc;
      try { sc = JSON.parse(donut.dataset.counts).map(Number); } catch (e) { sc = null; }
      if (sc && sc.reduce(function (a, b) { return a + b; }, 0) > 0) {
        var labels = ['Applied', 'Test Pending', 'Shortlisted', 'Rejected'];
        var colors = ['#3b6fe0', '#f0a500', '#1f9d5c', '#e04f4f'];
        var seen = sc.map(function (c, i) { return c > 0; });
        var fL = labels.filter(function (_, i) { return seen[i]; });
        var fD = sc.filter(function (_, i) { return seen[i]; });
        var fC = colors.filter(function (_, i) { return seen[i]; });

        new Chart(donut, {
          type: 'doughnut',
          data: { labels: fL, datasets: [{ data: fD, backgroundColor: fC, borderWidth: 3, borderColor: '#fff' }] },
          options: { responsive: true, maintainAspectRatio: true, cutout: '70%', plugins: { legend: { display: false } } }
        });

        var leg = document.getElementById('pipelineLegend');
        if (leg) {
          leg.innerHTML = fL.map(function (l, i) {
            return '<span class="cl-item"><span class="cl-dot" style="background:' + fC[i] + ';"></span>' + l +
                   ' <strong>' + fD[i] + '</strong></span>';
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

<?php require 'includes/page_footer_role.php'; ?>