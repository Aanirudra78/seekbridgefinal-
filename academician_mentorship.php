<?php
// academician_mentorship.php - Mentorship programs & guest lecture invites
// posted by industry. Expressing interest keeps an internal record.
require_once 'includes/db_connect.php';
require_role('academician');

$uid = (int) $_SESSION['user_id'];

$act = $_POST['action'] ?? '';
$msg = '';
$err = '';

if ($act === 'interest') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('INSERT IGNORE INTO interests (target_type,target_id,user_id) VALUES ("mentorship",?,?)', 'ii', array($id, $uid));
    $msg = 'Interest recorded. The company can now reach out to you.';
}
if ($act === 'uninterest') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM interests WHERE target_type="mentorship" AND target_id=? AND user_id=?', 'ii', array($id, $uid));
    $msg = 'Interest withdrawn.';
}

if ($act !== '') {
    header('Location: academician_mentorship.php' . ($msg ? '?msg=' . urlencode($msg) : ''));
    exit;
}
$msg = $_GET['msg'] ?? $msg;

$programs = array();
$st = db_query('SELECT m.*, u.name AS company_name
                FROM mentorship_programs m
                LEFT JOIN industry_details i ON i.id = m.industry_id
                LEFT JOIN users u ON u.id = i.user_id
                ORDER BY m.schedule_date IS NULL, m.schedule_date ASC, m.id DESC');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $programs[] = $r;
mysqli_stmt_close($st);

$myInterests = array();
$st = db_query('SELECT target_id FROM interests WHERE target_type="mentorship" AND user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myInterests[(int) $r['target_id']] = true;
mysqli_stmt_close($st);

$typeLabels = array('mentorship'=>'Mentorship Program','guest_lecture'=>'Guest Lecture');
$typeColors = array('mentorship'=>'text-bg-primary','guest_lecture'=>'text-bg-success');

$pageTitle = 'Mentorship & Guest Lectures';
$activeNav = 'mentorship';
require 'includes/page_header_role.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card page-heading mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#0e7490,#06b6d4);color:#fff;"><i class="fa-solid fa-people-group"></i></div>
    <div>
      <h4 class="fw-bold mb-1">Mentorship &amp; Guest Lectures</h4>
      <p class="mb-0 fw-regular">Companies invite faculty to mentorship programs and guest lectures. Express interest to get connected.</p>
    </div>
  </div>
</div>

<?php if (count($programs) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-people-group" style="font-size:3rem;color:#cbd5e1;"></i>
    <p class="mt-3 mb-0 text-muted-2">No mentorship or guest lecture invitations yet. Check back soon!</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($programs as $p): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge <?php echo $typeColors[$p['program_type']]; ?>"><i class="fa-solid fa-microphone me-1"></i><?php echo $typeLabels[$p['program_type']]; ?></span>
              <span class="badge text-bg-light border text-muted-2"><?php echo e(ucfirst($p['mode'])); ?></span>
            </div>
            <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
            <small class="text-muted-2 mb-2"><i class="fa-solid fa-building me-1"></i><?php echo e($p['company_name'] ?: 'Industry Partner'); ?></small>
            <?php if ($p['topic']): ?><span class="badge bg-navy mb-2"><i class="fa-solid fa-tag me-1"></i><?php echo e($p['topic']); ?></span><?php endif; ?>
            <?php if ($p['description']): ?><p class="text-muted small"><?php echo e(mb_strimwidth($p['description'], 0, 120, '...')); ?></p><?php endif; ?>
            <?php if ($p['schedule_date']): ?><small class="text-muted-2 mb-3"><i class="fa-regular fa-calendar me-1"></i><?php echo e(date('d M Y', strtotime($p['schedule_date']))); ?></small><?php endif; ?>
            <div class="mt-auto">
              <?php if (isset($myInterests[$p['id']])): ?>
                <form method="post" action="academician_mentorship.php">
                  <input type="hidden" name="action" value="uninterest">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-outline-secondary w-100"><i class="fa-solid fa-check me-1"></i>Interested — Withdraw</button>
                </form>
              <?php else: ?>
                <form method="post" action="academician_mentorship.php">
                  <input type="hidden" name="action" value="interest">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-navy w-100"><i class="fa-solid fa-hand-pointer me-1"></i>Express Interest</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>