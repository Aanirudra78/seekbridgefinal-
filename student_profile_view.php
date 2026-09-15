<?php
// student_profile_view.php - Full student profile + resume + test results for an industry recruiter
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
$studentId = (int) ($_GET['student'] ?? 0);

// ---- Only allow viewing students who applied to this industry's postings ----
$ok = false;
$apps = array();
if ($studentId > 0) {
    $st = db_query(
      'SELECT a.id, a.status, a.applied_at, a.test_score, a.test_skipped, a.test_wrong, a.test_total,
              i.title AS posting_title, i.type AS posting_type
       FROM applications a
       JOIN internships i ON i.id = a.internship_id
       WHERE i.industry_id = ? AND a.student_id = ?
       ORDER BY a.applied_at DESC', 'ii', array($uid, $studentId));
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $apps[] = $row;
    mysqli_stmt_close($st);
    $ok = count($apps) > 0;
}

if (!$ok) {
    $pageTitle = 'Profile';
    $activeNav = 'apps';
    require 'includes/page_header_industry.php';
    echo '<div class="alert alert-warning"><i class="fa-solid fa-shield-halved me-1"></i>Not authorized. You can only view students who applied to your postings.</div>';
    require 'includes/page_footer_role.php';
    exit;
}

// ---- Student master data ----
$student = array('name'=>'', 'email'=>'', 'phone'=>'', 'city'=>'', 'linkedin'=>'', 'github'=>'', 'bio'=>'',
                 'college_name'=>'', 'course_branch'=>'', 'year'=>'', 'resume_path'=>'');
$st = db_query(
  'SELECT u.name, u.email, sd.*
   FROM users u
   LEFT JOIN student_details sd ON sd.user_id = u.id
   WHERE u.id = ?', 'i', array($studentId));
$res = mysqli_stmt_get_result($st);
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($st);
if ($row) {
    $student = array(
        'name' => $row['name'], 'email' => $row['email'],
        'phone' => $row['phone'] ?? '', 'city' => $row['city'] ?? '',
        'linkedin' => $row['linkedin'] ?? '', 'github' => $row['github'] ?? '',
        'bio' => $row['bio'] ?? '',
        'college_name' => $row['college_name'] ?? '', 'course_branch' => $row['course_branch'] ?? '',
        'year' => $row['year'] ?? '', 'resume_path' => $row['resume_path'] ?? '',
    );
}

// ---- Skills with assessment scores ----
$skills = array();
$st = db_query(
  'SELECT s.skill_name, ss.score, ss.assessed_at
   FROM student_skills ss JOIN skills s ON s.id = ss.skill_id
   WHERE ss.student_id = ? ORDER BY ss.score DESC', 'i', array($studentId));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $skills[] = $row;
mysqli_stmt_close($st);

// ---- Portfolio ----
$projects = array();
$st = db_query('SELECT * FROM portfolio_projects WHERE student_id = ?', 'i', array($studentId));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $projects[] = $row;
mysqli_stmt_close($st);

$certs = array();
$st = db_query('SELECT * FROM portfolio_certificates WHERE student_id = ?', 'i', array($studentId));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $certs[] = $row;
mysqli_stmt_close($st);

$statusLabels = array('applied'=>'Applied','test_pending'=>'Test Pending','shortlisted'=>'Shortlisted','rejected'=>'Rejected');
$statusColors = array('applied'=>'badge-applied','test_pending'=>'badge-test_pending','shortlisted'=>'badge-shortlisted','rejected'=>'badge-rejected');

$pageTitle = $student['name'] . ' — Candidate Profile';
$activeNav = 'apps';
require 'includes/page_header_industry.php';
?>

<a href="industry_applications.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left me-1"></i> Back to Applications</a>

<!-- Header card -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center gap-4">
      <div class="cand-avatar"><?php echo e(strtoupper(substr($student['name'], 0, 1))); ?></div>
      <div class="flex-grow-1">
        <h4 class="fw-bold mb-1"><?php echo e($student['name']); ?></h4>
        <p class="text-muted-2 mb-2"><i class="fa-solid fa-envelope me-1"></i><?php echo e($student['email']); ?>
          <?php if ($student['phone']): ?> · <i class="fa-solid fa-phone me-1"></i><?php echo e($student['phone']); ?><?php endif; ?>
          <?php if ($student['city']): ?> · <i class="fa-solid fa-location-dot me-1"></i><?php echo e($student['city']); ?><?php endif; ?>
        </p>
        <?php if ($student['college_name']): ?>
          <span class="badge text-bg-light border me-1"><i class="fa-solid fa-graduation-cap me-1"></i><?php echo e($student['college_name']); ?></span>
        <?php endif; ?>
        <?php if ($student['course_branch']): ?>
          <span class="badge text-bg-light border me-1"><?php echo e($student['course_branch']); ?></span>
        <?php endif; ?>
        <?php if ($student['year']): ?>
          <span class="badge text-bg-light border">Year <?php echo e($student['year']); ?></span>
        <?php endif; ?>
      </div>
      <div class="d-flex flex-column gap-2">
        <?php if ($student['resume_path']): ?>
          <a href="<?php echo e($student['resume_path']); ?>" target="_blank" class="btn btn-accent"><i class="fa-solid fa-file me-1"></i> View Resume</a>
        <?php else: ?>
          <span class="badge text-bg-warning text-start"><i class="fa-solid fa-triangle-exclamation me-1"></i> No resume uploaded</span>
        <?php endif; ?>
        <?php if ($student['linkedin']): ?><a href="<?php echo e($student['linkedin']); ?>" target="_blank" class="btn btn-outline-primary"><i class="fa-brands fa-linkedin me-1"></i> LinkedIn</a><?php endif; ?>
        <?php if ($student['github']): ?><a href="<?php echo e($student['github']); ?>" target="_blank" class="btn btn-outline-dark"><i class="fa-brands fa-github me-1"></i> GitHub</a><?php endif; ?>
      </div>
    </div>
    <?php if ($student['bio']): ?>
      <hr>
      <p class="mb-0 text-muted-2"><i class="fa-solid fa-quote-left me-1 text-primary"></i><?php echo e($student['bio']); ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <!-- Test results for this company -->
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-clipboard-check me-2 text-primary"></i>Applications &amp; Test Results (your postings)</h5>
        <?php if (count($apps) === 0): ?>
          <p class="text-muted-2 mb-0">No applications.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr><th>Posting</th><th>Score</th><th>Breakdown</th><th>Applied</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($apps as $a): ?>
                  <tr>
                    <td><span class="badge <?php echo $a['posting_type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($a['posting_type'])); ?></span> <?php echo e($a['posting_title']); ?></td>
                    <td>
                      <?php if ($a['test_score'] !== null): ?>
                        <span class="fw-bold"><?php echo (int)$a['test_score']; ?>%</span>
                      <?php else: ?>
                        <span class="text-muted-2">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($a['test_total'] > 0): ?>
                        <small class="text-muted-2"><?php echo (int)$a['test_wrong']; ?> wrong · <?php echo (int)$a['test_skipped']; ?> skipped / <?php echo (int)$a['test_total']; ?></small>
                      <?php else: ?>
                        <small class="text-muted-2">No test</small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo date('d M Y', strtotime($a['applied_at'])); ?></td>
                    <td><span class="badge-status <?php echo $statusColors[$a['status']]; ?>"><?php echo $statusLabels[$a['status']]; ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Skills -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-brain me-2 text-primary"></i>Assessed Skills</h5>
        <?php if (count($skills) === 0): ?>
          <p class="text-muted-2 mb-0">No skills assessed yet.</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($skills as $sk): ?>
              <div class="col-md-6">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="fw-semibold"><?php echo e($sk['skill_name']); ?></span>
                  <span><?php echo (int)$sk['score']; ?>%</span>
                </div>
                <div class="progress" style="height:8px;">
                  <div class="progress-bar" style="width:<?php echo min(100, (int)$sk['score']); ?>%;background:linear-gradient(90deg,#0e2a5c,#f0a500);"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Projects -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-diagram-project me-2 text-primary"></i>Projects</h5>
        <?php if (count($projects) === 0): ?>
          <p class="text-muted-2 mb-0">No projects added.</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($projects as $p): ?>
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 bg-light">
                  <h6 class="fw-bold mb-1"><?php echo e($p['project_name']); ?></h6>
                  <p class="small text-muted-2 mb-2"><?php echo e($p['description'] ?: ''); ?></p>
                  <?php if ($p['link']): ?><a href="<?php echo e($p['link']); ?>" target="_blank" class="small"><i class="fa-solid fa-up-right-from-square me-1"></i>View</a><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar: certifications + quick actions -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-award me-2 text-primary"></i>Certifications</h5>
        <?php if (count($certs) === 0): ?>
          <p class="text-muted-2 mb-0">No certifications.</p>
        <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($certs as $c): ?>
              <li class="mb-2">
                <div class="fw-semibold small"><?php echo e($c['title']); ?></div>
                <div class="text-muted-2 small"><?php echo e($c['issuer']); ?><?php if ($c['issued_date']): ?> · <?php echo date('M Y', strtotime($c['issued_date'])); ?><?php endif; ?></div>
                <?php if ($c['link']): ?><a href="<?php echo e($c['link']); ?>" target="_blank" class="small"><i class="fa-solid fa-up-right-from-square me-1"></i>Verify</a><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-bolt me-2 text-primary"></i>Quick Actions</h5>
        <?php $firstApp = $apps[0] ?? null; if ($firstApp): ?>
          <div class="d-grid gap-2">
            <a href="industry_applications.php?update=<?php echo (int)$firstApp['id']; ?>&status=shortlisted" class="btn btn-success"><i class="fa-solid fa-user-check me-1"></i> Shortlist</a>
            <a href="industry_applications.php?update=<?php echo (int)$firstApp['id']; ?>&status=rejected" class="btn btn-outline-danger"><i class="fa-solid fa-user-xmark me-1"></i> Reject</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>