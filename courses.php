<?php
// courses.php - Recommended courses filtered by student's skill gaps
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];

// ---- Get student's skill gaps (skills with score < 70) ----
$gapSkills = array();
$st = db_query(
  'SELECT s.skill_name FROM student_skills ss
   JOIN skills s ON s.id = ss.skill_id
   WHERE ss.student_id = ? AND ss.score < 70', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $gapSkills[] = strtolower($row['skill_name']);
mysqli_stmt_close($st);

// ---- Get a list of skills the student is NOT strong in (including unassessed from all skills) ----
$allSkills = array();
$st = db_query('SELECT skill_name FROM skills');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $allSkills[] = strtolower($row['skill_name']);
mysqli_stmt_close($st);

// ---- Fetch all regular courses (certifications are on the Certification page) ----
$courses = array();
$st = db_query('SELECT * FROM courses WHERE is_certification = 0 ORDER BY skill_tag, id');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $courses[] = $row;
mysqli_stmt_close($st);

// ---- Filter: match courses whose skill_tag is a gap skill, else show all ----
$priority = array();
$others = array();
foreach ($courses as $c) {
    $tag = strtolower(trim($c['skill_tag']));
    if (in_array($tag, $gapSkills)) {
        $priority[] = $c;
    } else {
        $others[] = $c;
    }
}
$ordered = array_merge($priority, $others);

$pageTitle = 'Recommended Courses';
$activeNav = 'courses';
require 'includes/page_header.php';
?>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title"><i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>Personalised Recommendations</h5>
    <?php if (count($gapSkills) > 0): ?>
      <p class="small text-muted-2 mb-2">Courses matching your skill gaps are shown first:</p>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($gapSkills as $gs): ?>
          <span class="badge text-bg-warning"><?php echo e(ucfirst($gs)); ?></span>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="small text-muted-2 mb-0">
        Complete the <a href="skill_assessment.php">skill assessment</a> to unlock personalised course recommendations.
        Showing all available courses for now.
      </p>
    <?php endif; ?>
  </div>
</div>

<?php if (count($ordered) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-book-open" style="font-size: 3rem; color: #cbd5e1;"></i>
    <p class="mt-3 mb-0">No courses available yet.</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($ordered as $c): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between mb-2">
              <span class="badge bg-navy"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
              <?php if (in_array(strtolower(trim($c['skill_tag'])), $gapSkills)): ?>
                <span class="badge text-bg-warning"><i class="fa-solid fa-star"></i> Recommended</span>
              <?php endif; ?>
            </div>
            <h6 class="fw-bold"><?php echo e($c['title']); ?></h6>
            <p class="small text-muted-2 mb-3">Platform: <?php echo e($c['platform'] ?: 'N/A'); ?></p>
            <div class="mt-auto">
              <?php if ($c['link']): ?>
                <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-navy w-100"><i class="fa-solid fa-arrow-up-right-from-square"></i> Go to Course</a>
              <?php else: ?>
                <button class="btn btn-outline-secondary w-100" disabled>Link not available</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer.php'; ?>
