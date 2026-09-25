<?php
// student_search.php — Search student profiles (Industry + Institution roles)
require_once 'includes/db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if (!in_array($role, array('industry', 'institution'), true)) redirect('login.php');

$uid = (int) $_SESSION['user_id'];

// Institution: get their college_name
$myCollege = '';
if ($role === 'institution') {
    $st = db_query('SELECT institution_name FROM institution_details WHERE user_id = ?', 'i', array($uid));
    $res = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($res);
    $myCollege = $row ? $row['institution_name'] : '';
    mysqli_stmt_close($st);
}

// Collect skills for dropdown
$allSkills = array();
$st = db_query('SELECT id, skill_name FROM skills ORDER BY skill_name');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $allSkills[] = $row;
mysqli_stmt_close($st);

// ---- Search filters ----
$qName      = trim($_GET['q']      ?? '');
$qSkill     = (int) ($_GET['skill'] ?? 0);
$qBranch    = trim($_GET['branch'] ?? '');
$qYear      = trim($_GET['year']   ?? '');
$qCollege   = trim($_GET['college'] ?? '');
$hasFilters = ($qName !== '' || $qSkill > 0 || $qBranch !== '' || $qYear !== '' || ($role === 'industry' && $qCollege !== ''));

// ---- Build query ----
$results = array();
if ($hasFilters || $role === 'institution' || $role === 'industry') {
    $where  = array('u.role = ?');
    $types  = 's';
    $params = array('student');

    // consent_share_data: industry sees only consenting; institution sees own college
    if ($role === 'industry') {
        $where[] = 'sd.consent_share_data = 1';
    } else {
        $where[] = 'LOWER(TRIM(sd.college_name)) = LOWER(?)';
        $types  .= 's';
        $params[] = $myCollege;
    }

    if ($qName !== '') {
        $where[] = '(u.name LIKE ? OR sd.course_branch LIKE ?)';
        $types  .= 'ss';
        $params[] = '%' . $qName . '%';
        $params[] = '%' . $qName . '%';
    }
    if ($qSkill > 0) {
        $where[] = 'ss.skill_id = ?';
        $types  .= 'i';
        $params[] = $qSkill;
    }
    if ($qBranch !== '') {
        $where[] = 'sd.course_branch LIKE ?';
        $types  .= 's';
        $params[] = '%' . $qBranch . '%';
    }
    if ($qYear !== '' && in_array($qYear, array('1','2','3','4','5'), true)) {
        $where[] = 'sd.year = ?';
        $types  .= 'i';
        $params[] = (int) $qYear;
    }
    if ($role === 'industry' && $qCollege !== '') {
        $where[] = 'sd.college_name LIKE ?';
        $types  .= 's';
        $params[] = '%' . $qCollege . '%';
    }

    $sql = "SELECT DISTINCT u.id, u.name, u.email,
                   sd.college_name, sd.course_branch, sd.year, sd.city, sd.resume_path,
                   (SELECT COUNT(*) FROM portfolio_projects pp WHERE pp.student_id = u.id) AS proj_count,
                   (SELECT COUNT(*) FROM portfolio_certificates pc WHERE pc.student_id = u.id) AS cert_count
            FROM users u
            JOIN student_details sd ON sd.user_id = u.id";

    // If skill filter: JOIN through student_skills
    if ($qSkill > 0) {
        $sql .= " JOIN student_skills ss ON ss.student_id = u.id";
    }

    $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY u.name ASC LIMIT 60";

    $st = db_query($sql, $types, $params);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $results[] = $row;
    mysqli_stmt_close($st);

    // Fetch skills for each result
    foreach ($results as &$stu) {
        $stu['skills'] = array();
        $sk = db_query(
            'SELECT s.skill_name, ss.score FROM student_skills ss
             JOIN skills s ON s.id = ss.skill_id
             WHERE ss.student_id = ? ORDER BY ss.score DESC', 'i', array($stu['id']));
        $skRes = mysqli_stmt_get_result($sk);
        while ($sr = mysqli_fetch_assoc($skRes)) $stu['skills'][] = $sr;
        mysqli_stmt_close($sk);
    }
    unset($stu);
}

$pageTitle = 'Search Students';
$activeNav = 'search';
require "includes/page_header_{$role}.php";
?>

<div class="page-heading mb-4">
  <h4 class="mb-1"><i class="fa-solid fa-magnifying-glass me-2"></i>Search Students</h4>
  <p class="text-muted-2 mb-0">
    <?php if ($role === 'institution'): ?>
      View profiles of students from <strong><?php echo e($myCollege); ?></strong>.
    <?php else: ?>
      Browse student profiles across all colleges (students who opted in to share their data).
    <?php endif; ?>
  </p>
</div>

<!-- Search Form -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="student_search.php" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold small" for="sq"><i class="fa-solid fa-user me-1"></i> Name / Branch</label>
        <input type="text" class="form-control" id="sq" name="q" placeholder="e.g. Rahul or Computer" value="<?php echo e($qName); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold small" for="sSkill"><i class="fa-solid fa-brain me-1"></i> Skill</label>
        <select class="form-select" id="sSkill" name="skill">
          <option value="0">All Skills</option>
          <?php foreach ($allSkills as $sk): ?>
            <option value="<?php echo (int)$sk['id']; ?>" <?php echo $qSkill === (int)$sk['id'] ? 'selected' : ''; ?>><?php echo e($sk['skill_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold small" for="sBranch"><i class="fa-solid fa-book me-1"></i> Branch</label>
        <input type="text" class="form-control" id="sBranch" name="branch" placeholder="e.g. CSE" value="<?php echo e($qBranch); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold small" for="sYear"><i class="fa-solid fa-calendar me-1"></i> Year</label>
        <select class="form-select" id="sYear" name="year">
          <option value="">All Years</option>
          <?php for ($y = 1; $y <= 5; $y++): ?>
            <option value="<?php echo $y; ?>" <?php echo $qYear == $y ? 'selected' : ''; ?>><?php echo $y; ?><?php echo $y==1?'st':($y==2?'nd':($y==3?'rd':'th')); ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <?php if ($role === 'industry'): ?>
      <div class="col-md-2">
        <label class="form-label fw-semibold small" for="sCollege"><i class="fa-solid fa-building-columns me-1"></i> College</label>
        <input type="text" class="form-control" id="sCollege" name="college" placeholder="e.g. MIT" value="<?php echo e($qCollege); ?>">
      </div>
      <?php endif; ?>
      <div class="col-md-1 d-grid">
        <button class="btn btn-navy" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </div>
    </form>
  </div>
</div>

<!-- Results -->
<?php if (count($results) === 0):
    if ($role === 'industry' && !$hasFilters): ?>
  <div class="text-center py-5">
    <div class="brand-logo mx-auto mb-3" style="width:64px;height:64px;font-size:1.6rem;"><i class="fa-solid fa-user-group"></i></div>
    <h5 class="fw-bold mb-1">No students have opted in yet</h5>
    <p class="text-muted-2 mb-0">Profiles show up here when students enable "Share my data" in their profile.</p>
  </div>
<?php else: ?>
  <div class="alert alert-light text-center py-4">
    <i class="fa-solid fa-user-xmark" style="font-size:2rem;color:#cbd5e1;"></i>
    <p class="mt-2 mb-0">No students found matching your criteria.</p>
  </div>
<?php endif; ?>
<?php else: ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold text-muted-2 mb-0"><i class="fa-solid fa-list me-1"></i> <?php echo count($results); ?> student(s) found</h6>
  </div>
  <div class="row g-4">
    <?php foreach ($results as $stu): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <!-- Header -->
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="cand-avatar" style="width:48px;height:48px;font-size:1.1rem;min-width:48px;">
                <?php echo e(strtoupper(substr($stu['name'], 0, 1))); ?>
              </div>
              <div class="flex-grow-1 overflow-hidden">
                <h6 class="fw-bold mb-0 text-truncate"><?php echo e($stu['name']); ?></h6>
                <small class="text-muted-2 d-block text-truncate">
                  <?php echo e($stu['college_name'] ?: '—'); ?>
                </small>
              </div>
            </div>

            <!-- Details -->
            <div class="d-flex flex-wrap gap-1 mb-2">
              <?php if ($stu['course_branch']): ?>
                <span class="badge text-bg-primary"><i class="fa-solid fa-book me-1"></i><?php echo e($stu['course_branch']); ?></span>
              <?php endif; ?>
              <?php if ($stu['year']): ?>
                <span class="badge text-bg-secondary"><i class="fa-solid fa-calendar me-1"></i>Year <?php echo (int)$stu['year']; ?></span>
              <?php endif; ?>
              <?php if ($stu['city']): ?>
                <span class="badge text-bg-light border"><i class="fa-solid fa-location-dot me-1"></i><?php echo e($stu['city']); ?></span>
              <?php endif; ?>
            </div>

            <!-- Skills -->
            <div class="mb-3 flex-grow-1">
              <?php if (count($stu['skills']) > 0): ?>
                <small class="text-muted-2 fw-semibold d-block mb-1">Skills:</small>
                <div class="d-flex flex-wrap gap-1">
                  <?php foreach (array_slice($stu['skills'], 0, 5) as $sk): ?>
                    <span class="badge <?php echo (int)$sk['score'] >= 70 ? 'text-bg-success' : ((int)$sk['score'] >= 40 ? 'text-bg-warning' : 'text-bg-danger'); ?>">
                      <?php echo e($sk['skill_name']); ?> · <?php echo (int)$sk['score']; ?>%
                    </span>
                  <?php endforeach; ?>
                  <?php if (count($stu['skills']) > 5): ?>
                    <span class="badge text-bg-light border">+<?php echo count($stu['skills']) - 5; ?> more</span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <small class="text-muted-2">No skills assessed yet.</small>
              <?php endif; ?>
            </div>

            <!-- Stats + view link -->
            <div class="d-flex justify-content-between align-items-center border-top pt-3">
              <div class="d-flex gap-3 small text-muted-2">
                <span><i class="fa-solid fa-diagram-project me-1"></i><?php echo (int)$stu['proj_count']; ?> projects</span>
                <span><i class="fa-solid fa-award me-1"></i><?php echo (int)$stu['cert_count']; ?> certs</span>
              </div>
              <a href="view_student_profile.php?id=<?php echo (int)$stu['id']; ?>" class="btn btn-sm btn-accent">
                <i class="fa-solid fa-eye me-1"></i> View Profile
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>
