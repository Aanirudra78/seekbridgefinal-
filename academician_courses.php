<?php
// academician_courses.php - Courses published for instructors/teachers (by industry)
require_once 'includes/db_connect.php';
require_role('academician');

$tagFilter = trim($_GET['tag'] ?? '');

$sql = 'SELECT c.*, u.name AS provider
        FROM courses c
        LEFT JOIN users u ON u.id = c.created_by
        WHERE c.audience = "instructors"';
$types = '';
$params = array();
if ($tagFilter !== '') {
    $sql .= ' AND (c.skill_tag LIKE ? OR c.platform LIKE ? OR u.name LIKE ?)';
    $types .= 'sss';
    $like = '%' . $tagFilter . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY c.id DESC';

$st = mysqli_prepare($conn, $sql);
if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$courses = array();
while ($row = mysqli_fetch_assoc($res)) $courses[] = $row;
mysqli_stmt_close($st);

$difficultyLabels = array('beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced');
$difficultyColors = array('beginner'=>'text-bg-success','intermediate'=>'text-bg-warning','advanced'=>'text-bg-danger');

$pageTitle = 'Courses for Instructors';
$activeNav = 'courses';
require 'includes/page_header_role.php';
?>

<div class="card page-heading">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#1f9d5c,#2fc07b);color:#fff;"><i class="fa-solid fa-book-open"></i></div>
    <div>
      <h4 class="fw-bold mb-1">Faculty Development Courses</h4>
      <p class="mb-0 fw-regular">Industry partners share these courses to help instructors upskill and stay industry-ready.</p>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="academician_courses.php" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="acSearch"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
        <input type="text" class="form-control" name="tag" id="acSearch" value="<?php echo e($tagFilter); ?>" placeholder="Search by topic, platform or provider">
      </div>
      <div class="col-md-3">
        <button class="btn btn-navy" type="submit">Search</button>
        <?php if ($tagFilter): ?><a href="academician_courses.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (count($courses) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-book-open" style="font-size:3rem;color:#cbd5e1;"></i>
    <p class="mt-3 mb-0 text-muted-2">No courses from industry yet. Check back soon!</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($courses as $c): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge text-bg-success"><i class="fa-solid fa-building me-1"></i><?php echo e($c['provider'] ?: 'Industry Partner'); ?></span>
              <span class="badge <?php echo $difficultyColors[$c['difficulty_level']]; ?>"><?php echo $difficultyLabels[$c['difficulty_level']]; ?></span>
            </div>
            <div class="d-flex align-items-center gap-3 mb-2">
              <div class="cert-logo" style="background:linear-gradient(135deg,#1f9d5c,#2fc07b);"><?php echo e(strtoupper(substr($c['platform'], 0, 2))); ?></div>
              <div>
                <h6 class="fw-bold mb-1"><?php echo e($c['title']); ?></h6>
                <small class="text-muted-2"><?php echo e($c['platform']); ?></small>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-auto mb-3">
              <span class="badge bg-navy"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
            </div>
            <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-navy w-100"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Course</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>