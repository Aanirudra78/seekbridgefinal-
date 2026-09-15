<?php
// application_tracker.php - Table of all applications with status badges
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];

// ---- Fetch applications ----
$apps = array();
$st = db_query(
  'SELECT a.id, a.status, a.applied_at, a.test_score, a.test_skipped, a.test_wrong, a.test_total,
          i.title AS internship_title, u.name AS company
   FROM applications a
   JOIN internships i ON i.id = a.internship_id
   JOIN users u ON u.id = i.industry_id
   WHERE a.student_id = ?
   ORDER BY a.applied_at DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $apps[] = $row;
mysqli_stmt_close($st);

$statusLabels = array(
  'applied' => 'Applied',
  'test_pending' => 'Test Pending',
  'shortlisted' => 'Shortlisted',
  'rejected' => 'Rejected',
);

$statusColors = array(
  'applied' => 'badge-applied',
  'test_pending' => 'badge-test_pending',
  'shortlisted' => 'badge-shortlisted',
  'rejected' => 'badge-rejected',
);

$pageTitle = 'Application Tracker';
$activeNav = 'tracker';
require 'includes/page_header.php';
?>

<?php if (isset($_GET['applied'])): ?>
  <div class="alert alert-success"><i class="fa-solid fa-circle-check me-1"></i>
    <strong>Application submitted!</strong> Your test score (if any) was attached automatically. The company will review your profile, resume and score.
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-4"><i class="fa-solid fa-magnifying-glass-chart me-2 text-primary"></i>My Applications</h5>

    <?php if (count($apps) === 0): ?>
      <div class="text-center py-5">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
        <p class="mt-3 mb-2">You haven't applied to any internships yet.</p>
        <a href="internships.php" class="btn btn-navy"><i class="fa-solid fa-briefcase"></i> Browse Internships / Jobs</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Internship</th>
              <th>Company</th>
              <th>Applied On</th>
              <th>Test Score</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($apps as $i => $a): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td class="fw-semibold"><?php echo e($a['internship_title']); ?></td>
                <td><?php echo e($a['company']); ?></td>
                <td><?php echo date('d M Y', strtotime($a['applied_at'])); ?></td>
                <td>
                  <?php if ($a['test_score'] !== null): ?>
                    <span class="badge text-bg-success"><?php echo (int)$a['test_score']; ?>%</span>
                    <small class="text-muted-2 d-block"><?php echo (int)$a['test_wrong']; ?> wrong · <?php echo (int)$a['test_skipped']; ?> skipped</small>
                  <?php else: ?>
                    <span class="text-muted-2">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge-status <?php echo $statusColors[$a['status']]; ?>">
                    <?php echo $statusLabels[$a['status']]; ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Legend -->
      <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
        <small class="badge-status badge-applied">Applied</small>
        <small class="badge-status badge-test_pending">Test Pending</small>
        <small class="badge-status badge-shortlisted">Shortlisted</small>
        <small class="badge-status badge-rejected">Rejected</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/page_footer.php'; ?>
