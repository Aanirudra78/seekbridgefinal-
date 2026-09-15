<?php
// industry_applications.php - Applications received + shortlist management
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';

// ---- Handle status update actions ----
if (isset($_GET['update']) && isset($_GET['status'])) {
    $app_id = (int) $_GET['update'];
    $new_status = $_GET['status'];
    if (in_array($new_status, array('applied','test_pending','shortlisted','rejected'), true)) {
        // ensure the application belongs to this industry
        $st = db_query(
          'UPDATE applications a
           JOIN internships i ON i.id = a.internship_id
           SET a.status = ?
           WHERE a.id = ? AND i.industry_id = ?',
          'sii', array($new_status, $app_id, $uid));
        mysqli_stmt_close($st);
        $message = 'Application status updated.';
    }
}

// ---- Filters ----
$statusFilter = $_GET['status'] ?? '';
$postingFilter = (int)($_GET['posting'] ?? 0);

$sql = 'SELECT a.id, a.status, a.applied_at, a.test_score, a.test_skipped, a.test_wrong, a.test_total, a.test_completed_at,
               i.id AS posting_id, i.title AS posting_title, i.type AS posting_type,
               u.id AS student_id, u.name AS student_name, u.email AS student_email,
               sd.college_name, sd.course_branch, sd.year,
               sd.phone, sd.city, sd.linkedin, sd.github, sd.bio, sd.resume_path
        FROM applications a
        JOIN internships i ON i.id = a.internship_id
        JOIN users u ON u.id = a.student_id
        LEFT JOIN student_details sd ON sd.user_id = u.id
        WHERE i.industry_id = ?';
$types = 'i';
$params = array($uid);

if ($statusFilter !== '') {
    $sql .= ' AND a.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($postingFilter > 0) {
    $sql .= ' AND i.id = ?';
    $types .= 'i';
    $params[] = $postingFilter;
}
$sql .= ' ORDER BY a.applied_at DESC';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$apps = array();
while ($row = mysqli_fetch_assoc($res)) $apps[] = $row;
mysqli_stmt_close($stmt);

// ---- Inline summary data (skills + portfolio counts) per applicant ----
$skillsSummary = array();
$portfolioSummary = array();
$studentIds = array_unique(array_map(function ($a) { return (int) $a['student_id']; }, $apps));
if (count($studentIds) > 0) {
    $in = implode(',', $studentIds);
    $st = db_query('SELECT ss.student_id, s.skill_name, ss.score FROM student_skills ss JOIN skills s ON s.id = ss.skill_id WHERE ss.student_id IN (' . $in . ') ORDER BY ss.score DESC');
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $skillsSummary[$row['student_id']][] = $row;
    mysqli_stmt_close($st);

    $st = db_query('SELECT student_id, COUNT(*) AS c FROM portfolio_projects WHERE student_id IN (' . $in . ') GROUP BY student_id');
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $portfolioSummary[$row['student_id']]['projects'] = (int) $row['c'];
    mysqli_stmt_close($st);

    $st = db_query('SELECT student_id, COUNT(*) AS c FROM portfolio_certificates WHERE student_id IN (' . $in . ') GROUP BY student_id');
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $portfolioSummary[$row['student_id']]['certs'] = (int) $row['c'];
    mysqli_stmt_close($st);
}

// ---- Stats for this industry ----
$stats = array('total'=>0,'test_pending'=>0,'shortlisted'=>0,'rejected'=>0,'applied'=>0);
$st = db_query(
  'SELECT a.status, COUNT(*) AS c FROM applications a
   JOIN internships i ON i.id = a.internship_id
   WHERE i.industry_id = ? GROUP BY a.status', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $stats[$row['status']] = (int)$row['c'];
mysqli_stmt_close($st);
$stats['total'] = array_sum(array_slice($stats, 0));

// ---- My postings for filter dropdown ----
$myPostings = array();
$st = db_query('SELECT id, title, type FROM internships WHERE industry_id = ? ORDER BY posted_at DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $myPostings[] = $row;
mysqli_stmt_close($st);

$statusLabels = array('applied'=>'Applied','test_pending'=>'Test Pending','shortlisted'=>'Shortlisted','rejected'=>'Rejected');
$statusColors = array('applied'=>'badge-applied','test_pending'=>'badge-test_pending','shortlisted'=>'badge-shortlisted','rejected'=>'badge-rejected');

$pageTitle = 'Applications';
$activeNav = 'apps';
require 'includes/page_header_industry.php';
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>

<!-- Summary chips -->
<div class="d-flex flex-wrap gap-2 mb-4">
  <a href="industry_applications.php" class="badge-status badge-applied text-decoration-none <?php echo $statusFilter===''?'':''; ?>">All (<?php echo $stats['total']; ?>)</a>
  <a href="industry_applications.php?status=applied" class="badge-status badge-applied text-decoration-none">Applied (<?php echo $stats['applied']; ?>)</a>
  <a href="industry_applications.php?status=test_pending" class="badge-status badge-test_pending text-decoration-none">Test Pending (<?php echo $stats['test_pending']; ?>)</a>
  <a href="industry_applications.php?status=shortlisted" class="badge-status badge-shortlisted text-decoration-none">Shortlisted (<?php echo $stats['shortlisted']; ?>)</a>
  <a href="industry_applications.php?status=rejected" class="badge-status badge-rejected text-decoration-none">Rejected (<?php echo $stats['rejected']; ?>)</a>
</div>

<!-- Pipeline visualisation -->
<div class="card mb-4">
  <div class="card-body">
    <div class="card-title-banner">
      <div class="b-icon"><i class="fa-solid fa-diagram-project"></i></div>
      <div><h6 class="mb-0">Application Pipeline</h6><small>Funnel view of all applications across your openings</small></div>
    </div>
    <div class="d-flex align-items-center gap-4 flex-wrap">
      <div class="chart-box chart-box-sm flex-grow-1" style="max-width:260px;">
        <canvas id="pipelineDonut" data-counts='<?php echo json_encode(array($stats['applied'], $stats['test_pending'], $stats['shortlisted'], $stats['rejected'])); ?>'></canvas>
      </div>
      <div class="flex-grow-1">
        <div class="row g-2">
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $stats['total']; ?></div><div class="lbl">Total received</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $stats['test_pending']; ?></div><div class="lbl">Awaiting tests</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $stats['shortlisted']; ?></div><div class="lbl">Shortlisted</div></div>
          </div>
          <div class="col-6">
            <div class="chart-summary-pill"><div class="num"><?php echo $stats['rejected']; ?></div><div class="lbl">Rejected</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    function initCharts() {
      if (typeof Chart === 'undefined') return;
      var d = document.getElementById('pipelineDonut');
    if (!d || !d.dataset.counts) return;
    var sc;
    try { sc = JSON.parse(d.dataset.counts).map(Number); } catch (e) { return; }
    if (sc.reduce(function (a, b) { return a + b; }, 0) === 0) return;
    var labels = ['Applied', 'Test Pending', 'Shortlisted', 'Rejected'];
    var colors = ['#3b6fe0', '#f0a500', '#1f9d5c', '#e04f4f'];
    var seen = sc.map(function (c, i) { return c > 0; });
    new Chart(d, {
      type: 'doughnut',
      data: {
        labels: labels.filter(function (_, i) { return seen[i]; }),
        datasets: [{ data: sc.filter(function (_, i) { return seen[i]; }), backgroundColor: colors.filter(function (_, i) { return seen[i]; }), borderWidth: 3, borderColor: '#fff' }]
      },
      options: { responsive: true, maintainAspectRatio: true, cutout: '66%', plugins: { legend: { position: 'bottom' } } }
    });

    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCharts);
    } else {
      initCharts();
    }
  })();
</script>

<!-- Filter by posting -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="industry_applications.php" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label" for="postingFilter">Filter by Posting</label>
        <select class="form-select" name="posting" id="postingFilter">
          <option value="0">All postings</option>
          <?php foreach ($myPostings as $mp): ?>
            <option value="<?php echo $mp['id']; ?>" <?php echo $postingFilter===$mp['id']?'selected':''; ?>><?php echo e($mp['title']); ?> (<?php echo e(ucfirst($mp['type'])); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?php echo e($statusFilter); ?>"><?php endif; ?>
      <div class="col-md-2">
        <button class="btn btn-navy w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="fa-solid fa-user-graduate me-2 text-primary"></i>Received Applications
      <?php if ($statusFilter): ?> <span class="badge text-bg-secondary"><?php echo e(ucwords(str_replace('_',' ',$statusFilter))); ?></span><?php endif; ?>
      <?php if ($postingFilter): ?> <span class="badge text-bg-info text-dark"><?php echo e($postingFilter); ?></span><?php endif; ?>
    </h5>

    <?php if (count($apps) === 0): ?>
      <div class="text-center py-5">
        <i class="fa-solid fa-inbox" style="font-size:3rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0 text-muted-2">No applications found<?php echo $statusFilter ? ' with this status' : ''; ?>.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th></th><th>Student</th><th>Posting</th><th>Test</th><th>Applied On</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($apps as $i => $a):
              $sk = $skillsSummary[$a['student_id']] ?? array();
              $pf = $portfolioSummary[$a['student_id']] ?? array();
              $projectsC = (int)($pf['projects'] ?? 0);
              $certsC = (int)($pf['certs'] ?? 0);
            ?>
              <tr>
                <td class="text-center align-middle">
                  <button type="button" class="btn btn-sm btn-outline-secondary app-toggle" data-app="<?php echo (int)$a['id']; ?>" title="View full details"><i class="fa-solid fa-chevron-down"></i></button>
                </td>
                <td>
                  <div class="fw-semibold"><?php echo e($a['student_name']); ?></div>
                  <small class="text-muted-2"><?php echo e($a['student_email']); ?><br><?php echo e($a['college_name'] ?: ''); ?> <?php echo e($a['course_branch'] ?: ''); ?> <?php echo $a['year'] ? ('· Yr '.$a['year']) : ''; ?></small>
                  <a href="student_profile_view.php?student=<?php echo (int)$a['student_id']; ?>" class="small fw-semibold text-decoration-none d-inline-block mt-1" title="Open full candidate profile"><i class="fa-solid fa-id-card me-1"></i>Profile</a>
                </td>
                <td><span class="badge <?php echo $a['posting_type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($a['posting_type'])); ?></span> <?php echo e($a['posting_title']); ?></td>
                <td>
                  <?php if ($a['test_score'] !== null): ?>
                    <span class="fw-bold"><?php echo (int)$a['test_score']; ?>%</span>
                    <small class="d-block text-muted-2"><?php echo (int)$a['test_wrong']; ?> wrong · <?php echo (int)$a['test_skipped']; ?> skipped / <?php echo (int)$a['test_total']; ?></small>
                  <?php else: ?>
                    <span class="text-muted-2">No test</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($a['applied_at'])); ?></td>
                <td><span class="badge-status <?php echo $statusColors[$a['status']]; ?>"><?php echo $statusLabels[$a['status']]; ?></span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=test_pending<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-warning" title="Mark Test Pending">Test</a>
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=shortlisted<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-success" title="Shortlist">Shortlist</a>
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=rejected<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-danger" title="Reject">Reject</a>
                  </div>
                </td>
              </tr>
              <tr class="d-none" id="app-detail-<?php echo (int)$a['id']; ?>">
                <td colspan="7" class="p-0">
                  <div class="app-detail p-4">
                    <div class="row g-4">
                      <!-- Contact -->
                      <div class="col-md-6 col-xl-3">
                        <h6 class="dt-title"><i class="fa-solid fa-address-book me-2"></i>Contact</h6>
                        <ul class="list-unstyled small mb-2">
                          <li class="mb-1"><i class="fa-solid fa-envelope me-2 text-primary"></i><?php echo e($a['student_email']); ?></li>
                          <li class="mb-1"><i class="fa-solid fa-phone me-2 text-primary"></i><?php echo e($a['phone'] ?: 'Not provided'); ?></li>
                          <li class="mb-1"><i class="fa-solid fa-location-dot me-2 text-primary"></i><?php echo e($a['city'] ?: 'Not provided'); ?></li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2">
                          <?php if ($a['linkedin']): ?><a href="<?php echo e($a['linkedin']); ?>" target="_blank" class="dt-link"><i class="fa-brands fa-linkedin"></i> LinkedIn</a><?php endif; ?>
                          <?php if ($a['github']): ?><a href="<?php echo e($a['github']); ?>" target="_blank" class="dt-link"><i class="fa-brands fa-github"></i> GitHub</a><?php endif; ?>
                          <?php if ($a['resume_path']): ?><a href="<?php echo e($a['resume_path']); ?>" target="_blank" class="dt-link text-success"><i class="fa-solid fa-file"></i> Resume</a><?php else: ?><span class="badge text-bg-warning">No resume</span><?php endif; ?>
                        </div>
                      </div>
                      <!-- Education -->
                      <div class="col-md-6 col-xl-3">
                        <h6 class="dt-title"><i class="fa-solid fa-graduation-cap me-2"></i>Education</h6>
                        <ul class="list-unstyled small mb-0">
                          <li class="mb-1"><strong><?php echo e($a['college_name'] ?: '—'); ?></strong></li>
                          <li class="mb-1"><?php echo e($a['course_branch'] ?: '—'); ?><?php echo $a['year'] ? (' · Year '.$a['year']) : ''; ?></li>
                        </ul>
                        <div class="mt-2 small text-muted-2">
                          <i class="fa-solid fa-diagram-project me-1 text-primary"></i><?php echo $projectsC; ?> project(s)
                          <span class="mx-1">·</span>
                          <i class="fa-solid fa-award me-1 text-primary"></i><?php echo $certsC; ?> cert(s)
                        </div>
                      </div>
                      <!-- Skills -->
                      <div class="col-md-6 col-xl-3">
                        <h6 class="dt-title"><i class="fa-solid fa-brain me-2"></i>Assessed Skills</h6>
                        <?php if (count($sk) === 0): ?>
                          <p class="small text-muted-2 mb-0">No skills assessed yet.</p>
                        <?php else: ?>
                          <div class="d-flex flex-wrap gap-1">
                            <?php foreach (array_slice($sk, 0, 6) as $skk): ?>
                              <span class="badge text-bg-light border"><?php echo e($skk['skill_name']); ?><span class="text-success fw-semibold"> <?php echo (int)$skk['score']; ?>%</span></span>
                            <?php endforeach; ?>
                            <?php if (count($sk) > 6): ?><span class="small text-muted-2 align-self-center">+<?php echo count($sk) - 6; ?> more</span><?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                      <!-- Bio + test -->
                      <div class="col-md-6 col-xl-3">
                        <h6 class="dt-title"><i class="fa-solid fa-quote-left me-2"></i>About / Test</h6>
                        <p class="small text-muted-2 mb-2"><?php echo e($a['bio'] ?: 'No bio added.'); ?></p>
                        <?php if ($a['test_score'] !== null): ?>
                          <div class="small"><span class="fw-semibold">Test score:</span> <span class="badge text-bg-success"><?php echo (int)$a['test_score']; ?>%</span></div>
                          <div class="small text-muted-2"><?php echo (int)$a['test_wrong']; ?> wrong · <?php echo (int)$a['test_skipped']; ?> skipped/<?php echo (int)$a['test_total']; ?><?php if ($a['test_completed_at']): ?> · <?php echo date('d M Y H:i', strtotime($a['test_completed_at'])); ?><?php endif; ?></div>
                        <?php else: ?>
                          <div class="small text-muted-2">No screening test taken.</div>
                        <?php endif; ?>
                        <div class="mt-3 d-flex flex-wrap gap-2">
                          <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=shortlisted<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-user-check me-1"></i> Shortlist</a>
                          <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=rejected<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-user-xmark me-1"></i> Reject</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.querySelectorAll('.app-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.dataset.app;
      var row = document.getElementById('app-detail-' + id);
      if (!row) return;
      var open = !row.classList.contains('d-none');
      row.classList.toggle('d-none', open);
      btn.querySelector('i').className = open ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
    });
  });
</script>

<?php require 'includes/page_footer_role.php'; ?>
