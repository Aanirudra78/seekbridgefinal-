<?php
// industry_courses.php - Industry adds courses for instructors (teachers)
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';
$errors = array();
$posted = false;

// ---- Delete a course ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $st = db_query('DELETE FROM courses WHERE id = ? AND created_by = ?', 'ii', array($delId, $uid));
    mysqli_stmt_close($st);
    $message = 'Course deleted.';
}

// ---- Add a course ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_course') {
    $title = trim($_POST['title'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $skill_tag = trim($_POST['skill_tag'] ?? '');
    $difficulty = in_array($_POST['difficulty'] ?? '', array('beginner','intermediate','advanced'), true) ? $_POST['difficulty'] : 'beginner';

    if ($title === '') $errors[] = 'Please enter a course title.';
    if ($platform === '') $errors[] = 'Please enter the platform (e.g. Coursera, NPTEL).';
    if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL)) $errors[] = 'Please enter a valid course link.';

    if (count($errors) === 0) {
        $st = db_query(
            'INSERT INTO courses (title, platform, link, skill_tag, difficulty_level, is_certification, audience, created_by)
             VALUES (?, ?, ?, ?, ?, 0, "instructors", ?)',
            'sssssi', array($title, $platform, $link, $skill_tag, $difficulty, $uid));
        mysqli_stmt_close($st);
        $message = 'Course published for instructors.';
        $posted = true;
    }
}

// ---- My courses ----
$myCourses = array();
$st = db_query(
  'SELECT c.*, u.name AS company FROM courses c LEFT JOIN users u ON u.id = c.created_by
   WHERE c.created_by = ? ORDER BY c.id DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $myCourses[] = $row;
mysqli_stmt_close($st);

$allSkills = array();
$st = db_query('SELECT skill_name FROM skills ORDER BY skill_name');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $allSkills[] = $row['skill_name'];
mysqli_stmt_close($st);

$difficultyLabels = array('beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced');
$difficultyColors = array('beginner'=>'text-bg-success','intermediate'=>'text-bg-warning','advanced'=>'text-bg-danger');

$pageTitle = 'My Courses';
$activeNav = 'courses';
require 'includes/page_header_industry.php';
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-1"><i class="fa-solid fa-plus me-2 text-primary"></i>Add Course for Instructors</h5>
        <p class="small text-muted-2 mb-4">
          <i class="fa-solid fa-chalkboard-user me-1"></i> These courses are published for <strong>teachers / instructors</strong>
          on the platform to upskill themselves. Students see industry certifications separately.
        </p>

        <form method="post" action="industry_courses.php">
          <input type="hidden" name="action" value="add_course">
          <div class="mb-3">
            <label class="form-label" for="icTitle">Course Title *</label>
            <input type="text" class="form-control" name="title" id="icTitle" value="<?php echo e($_POST['title'] ?? ''); ?>" placeholder="e.g. AI in Teaching & Assessment" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="icPlatform">Platform *</label>
            <input type="text" class="form-control" name="platform" id="icPlatform" value="<?php echo e($_POST['platform'] ?? ''); ?>" placeholder="e.g. Coursera, NPTEL, Udemy" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="icLink">Course Link *</label>
            <input type="url" class="form-control" name="link" id="icLink" value="<?php echo e($_POST['link'] ?? ''); ?>" placeholder="https://..." required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label class="form-label" for="icSkill">Skill / Topic Tag</label>
              <input type="text" class="form-control" name="skill_tag" id="icSkill" value="<?php echo e($_POST['skill_tag'] ?? ''); ?>" placeholder="e.g. Machine Learning" list="skillTags">
              <datalist id="skillTags">
                <?php foreach ($allSkills as $sk): ?><option value="<?php echo e($sk); ?>"><?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="icDiff">Difficulty</label>
              <select class="form-select" name="difficulty" id="icDiff">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-navy w-100"><i class="fa-solid fa-book-open me-1"></i> Publish Course</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-book-open me-2 text-primary"></i>Courses Published by You (<?php echo count($myCourses); ?>)</h5>
        <?php if (count($myCourses) === 0): ?>
          <div class="text-center py-5">
            <i class="fa-solid fa-book" style="font-size:3rem;color:#cbd5e1;"></i>
            <p class="mt-3 mb-0 text-muted-2">No courses published yet. Add your first course for instructors.</p>
          </div>
        <?php else: ?>
          <div class="d-grid gap-3">
            <?php foreach ($myCourses as $c): ?>
              <div class="border rounded p-3 d-flex flex-wrap align-items-center gap-3">
                <div class="cert-logo"><?php echo e(strtoupper(substr($c['platform'], 0, 2))); ?></div>
                <div class="flex-grow-1" style="min-width:200px;">
                  <h6 class="fw-bold mb-1"><?php echo e($c['title']); ?></h6>
                  <small class="text-muted-2"><?php echo e($c['platform']); ?> · <span class="badge bg-navy py-0"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
                    <span class="badge <?php echo $difficultyColors[$c['difficulty_level']]; ?> py-0"><?php echo $difficultyLabels[$c['difficulty_level']]; ?></span></small>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                  <a href="industry_courses.php?delete=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this course?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>