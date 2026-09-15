<?php
// certifications.php - Global industry certifications recommended for students
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];

$tagFilter = trim($_GET['tag'] ?? '');

$sql = 'SELECT c.*, c.skill_tag AS tag FROM courses c
        WHERE c.is_certification = 1';
$types = '';
$params = array();
if ($tagFilter !== '') {
    $sql .= ' AND (c.skill_tag LIKE ? OR c.platform LIKE ?)';
    $types .= 'ss';
    $like = '%' . $tagFilter . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY c.platform, c.title';

$st = mysqli_prepare($conn, $sql);
if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$certs = array();
while ($row = mysqli_fetch_assoc($res)) $certs[] = $row;
mysqli_stmt_close($st);

$allTags = array();
$st = db_query('SELECT DISTINCT skill_tag FROM courses WHERE is_certification = 1 ORDER BY skill_tag');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $allTags[] = $row['skill_tag'];
mysqli_stmt_close($st);

$difficultyLabels = array('beginner'=>'Beginner', 'intermediate'=>'Intermediate', 'advanced'=>'Advanced');
$difficultyColors = array('beginner'=>'text-bg-success', 'intermediate'=>'text-bg-warning', 'advanced'=>'text-bg-danger');

$pageTitle = 'Certifications';
$activeNav = 'certifications';
require 'includes/page_header.php';
?>

<div class="card page-heading">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#f0a500,#ffb733);color:#fff;"><i class="fa-solid fa-award"></i></div>
    <div class="flex-grow-1">
      <h5 class="fw-bold mb-1 text-white">Industry Certifications</h5>
      <p class="small mb-0 text-white-50">Globally recognised certifications from AWS, Cisco, Google, Microsoft &amp; more. Earn one to make your profile stand out to recruiters.</p>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="certifications.php" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="certSearch"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
        <input type="text" class="form-control" name="tag" id="certSearch" value="<?php echo e($tagFilter); ?>" placeholder="Search by tech, platform or tag (e.g. AWS, Cloud)">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-navy" type="submit">Search</button>
        <?php if ($tagFilter): ?><a href="certifications.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
      </div>
      <div class="col-md-5 d-flex flex-wrap gap-2 justify-content-md-end">
        <?php foreach ($allTags as $t): ?>
          <a href="certifications.php?tag=<?php echo e($t); ?>" class="badge <?php echo $tagFilter === $t ? 'text-bg-warning' : 'text-bg-light border'; ?> text-decoration-none"><?php echo e($t); ?></a>
        <?php endforeach; ?>
      </div>
    </form>
  </div>
</div>

<?php if (count($certs) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-award" style="font-size: 3rem; color: #cbd5e1;"></i>
    <p class="mt-3 mb-0">No certifications found for this search.</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($certs as $c): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100 cert-card">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge text-bg-warning"><i class="fa-solid fa-certificate"></i> Global Certification</span>
              <span class="badge <?php echo $difficultyColors[$c['difficulty_level']]; ?>"><?php echo $difficultyLabels[$c['difficulty_level']]; ?></span>
            </div>
            <div class="d-flex align-items-center gap-3 mb-2">
              <div class="cert-logo"><?php echo e(strtoupper(substr($c['platform'], 0, 2))); ?></div>
              <div>
                <h6 class="fw-bold mb-1"><?php echo e($c['title']); ?></h6>
                <small class="text-muted-2">by <span class="fw-semibold"><?php echo e($c['platform']); ?></span></small>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-3 mt-auto">
              <span class="badge bg-navy"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
              <?php if ($c['tag']): ?><span class="badge text-bg-light border"><?php echo e($c['tag']); ?></span><?php endif; ?>
            </div>
            <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-accent w-100"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Start Certification</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer.php'; ?>