<?php
// portfolio.php - Student portfolio: projects, certificates, skills
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';

// ---------- Handle actions ----------

// Add project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_project') {
    $name = trim($_POST['project_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    if ($name !== '') {
        $stmt = db_query('INSERT INTO portfolio_projects (student_id, project_name, description, link) VALUES (?,?,?,?)', 'isss', array($uid, $name, $desc, $link));
        mysqli_stmt_close($stmt);
        $message = 'Project added to your portfolio.';
    }
}

// Delete project
if (isset($_GET['del_project']) && is_numeric($_GET['del_project'])) {
    $stmt = db_query('DELETE FROM portfolio_projects WHERE id = ? AND student_id = ?', 'ii', array((int)$_GET['del_project'], $uid));
    mysqli_stmt_close($stmt);
    $message = 'Project deleted.';
}

// Add certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cert') {
    $title = trim($_POST['cert_title'] ?? '');
    $issuer = trim($_POST['issuer'] ?? '');
    $date = !empty($_POST['issued_date']) ? $_POST['issued_date'] : null;
    $link = trim($_POST['link'] ?? '');
    if ($title !== '') {
        $stmt = db_query('INSERT INTO portfolio_certificates (student_id, title, issuer, issued_date, link) VALUES (?,?,?,?,?)', 'issss', array($uid, $title, $issuer, $date, $link));
        mysqli_stmt_close($stmt);
        $message = 'Certificate added to your portfolio.';
    }
}

// Delete certificate
if (isset($_GET['del_cert']) && is_numeric($_GET['del_cert'])) {
    $stmt = db_query('DELETE FROM portfolio_certificates WHERE id = ? AND student_id = ?', 'ii', array((int)$_GET['del_cert'], $uid));
    mysqli_stmt_close($stmt);
    $message = 'Certificate deleted.';
}

// Add skill manually
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $skillName = trim($_POST['skill_name'] ?? '');
    if ($skillName !== '') {
        $score = (int) ($_POST['score'] ?? 50);
        // get skill id
        $st = db_query('SELECT id FROM skills WHERE skill_name = ?', 's', array($skillName));
        mysqli_stmt_store_result($st);
        mysqli_stmt_bind_result($st, $sid);
        $exists = mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);
        if (!$exists) {
            $ins = db_query('INSERT INTO skills (skill_name) VALUES (?)', 's', array($skillName));
            $sid = mysqli_insert_id($conn);
            mysqli_stmt_close($ins);
        }
        // avoid duplicate
        $dup = db_query('SELECT id FROM student_skills WHERE student_id = ? AND skill_id = ?', 'ii', array($uid, $sid));
        mysqli_stmt_store_result($dup);
        $dupCount = mysqli_stmt_num_rows($dup);
        mysqli_stmt_close($dup);
        if ($dupCount == 0) {
            $ins2 = db_query('INSERT INTO student_skills (student_id, skill_id, score) VALUES (?,?,?)', 'iii', array($uid, $sid, $score));
            mysqli_stmt_close($ins2);
            $message = 'Skill added to your portfolio.';
        } else {
            $message = 'This skill is already in your portfolio.';
            $msgType = 'warning';
        }
    }
}

// Delete skill
if (isset($_GET['del_skill']) && is_numeric($_GET['del_skill'])) {
    $stmt = db_query('DELETE FROM student_skills WHERE id = ? AND student_id = ?', 'ii', array((int)$_GET['del_skill'], $uid));
    mysqli_stmt_close($stmt);
    $message = 'Skill removed.';
}

// ---------- Fetch data ----------
$projects = array();
$st = db_query('SELECT * FROM portfolio_projects WHERE student_id = ? ORDER BY id DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $projects[] = $row;
mysqli_stmt_close($st);

$certs = array();
$st = db_query('SELECT * FROM portfolio_certificates WHERE student_id = ? ORDER BY id DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $certs[] = $row;
mysqli_stmt_close($st);

$skills = array();
$st = db_query(
  'SELECT ss.id AS ssid, s.skill_name, ss.score FROM student_skills ss
   JOIN skills s ON s.id = ss.skill_id
   WHERE ss.student_id = ? ORDER BY ss.score DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $skills[] = $row;
mysqli_stmt_close($st);

$pageTitle = 'My Portfolio';
$activeNav = 'portfolio';
require 'includes/page_header.php';
?>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Skills -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-star me-2 text-primary"></i>My Skills</h6>
        <form method="post" action="portfolio.php" class="mb-3">
          <input type="hidden" name="action" value="add_skill">
          <div class="input-group mb-2">
            <input type="text" class="form-control" name="skill_name" placeholder="Skill name" required>
          </div>
          <div class="input-group mb-2">
            <label class="input-group-text">Score</label>
            <input type="number" class="form-control" name="score" min="1" max="100" value="50">
          </div>
          <button class="btn btn-navy btn-sm w-100" type="submit"><i class="fa-solid fa-plus"></i> Add Skill</button>
        </form>
        <?php if (count($skills) === 0): ?>
          <p class="text-muted-2 small mb-0">No skills listed yet.</p>
        <?php else: ?>
          <?php foreach ($skills as $sk): ?>
            <div class="skill-tile mb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="st-name"><?php echo e($sk['skill_name']); ?></span>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge <?php echo $sk['score'] >= 70 ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo $sk['score']; ?>%</span>
                  <a href="portfolio.php?del_skill=<?php echo $sk['ssid']; ?>" class="text-danger" title="Remove"><i class="fa-solid fa-trash"></i></a>
                </div>
              </div>
              <div class="st-bar"><span style="width:<?php echo $sk['score']; ?>%;background:<?php echo $sk['score']>=70?'#2e9e5b':'#f5a623'; ?>;"></span></div>
              <div class="st-meta"><?php echo $sk['score'] >= 70 ? '<i class="fa-solid fa-thumbs-up text-success"></i> Proficient' : '<i class="fa-solid fa-arrow-trend-up text-warning"></i> Keep improving'; ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Projects + certificates -->
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-diagram-project me-2 text-primary"></i>Projects</h6>
        <form method="post" action="portfolio.php" class="border rounded p-3 mb-3 bg-light">
          <input type="hidden" name="action" value="add_project">
          <div class="row g-2">
            <div class="col-md-6"><input type="text" class="form-control" name="project_name" placeholder="Project name" required></div>
            <div class="col-md-6"><input type="text" class="form-control" name="description" placeholder="Short description"></div>
            <div class="col-md-8"><input type="text" class="form-control" name="link" placeholder="Project link (optional)"></div>
            <div class="col-md-4"><button class="btn btn-navy w-100" type="submit"><i class="fa-solid fa-plus"></i> Add Project</button></div>
          </div>
        </form>
        <?php if (count($projects) === 0): ?>
          <p class="text-muted-2 small mb-0">No projects added yet.</p>
        <?php else: ?>
          <?php foreach ($projects as $p): ?>
            <div class="d-flex justify-content-between align-items-start border-bottom py-2">
              <div>
                <div class="fw-semibold"><?php echo e($p['project_name']); ?></div>
                <small class="text-muted-2"><?php echo e($p['description']); ?></small>
                <?php if ($p['link']): ?><div><a href="<?php echo e($p['link']); ?>" target="_blank" class="small"><?php echo e($p['link']); ?></a></div><?php endif; ?>
              </div>
              <a href="portfolio.php?del_project=<?php echo $p['id']; ?>" class="text-danger"><i class="fa-solid fa-trash"></i></a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-award me-2 text-primary"></i>Certificates</h6>
        <form method="post" action="portfolio.php" class="border rounded p-3 mb-3 bg-light">
          <input type="hidden" name="action" value="add_cert">
          <div class="row g-2">
            <div class="col-md-4"><input type="text" class="form-control" name="cert_title" placeholder="Certificate title" required></div>
            <div class="col-md-3"><input type="text" class="form-control" name="issuer" placeholder="Issuer"></div>
            <div class="col-md-2"><input type="date" class="form-control" name="issued_date"></div>
            <div class="col-md-3"><button class="btn btn-navy w-100" type="submit"><i class="fa-solid fa-plus"></i> Add Cert</button></div>
          </div>
        </form>
        <?php if (count($certs) === 0): ?>
          <p class="text-muted-2 small mb-0">No certificates added yet.</p>
        <?php else: ?>
          <?php foreach ($certs as $c): ?>
            <div class="d-flex justify-content-between align-items-start border-bottom py-2">
              <div>
                <div class="fw-semibold"><?php echo e($c['title']); ?></div>
                <small class="text-muted-2"><?php echo e($c['issuer']); ?><?php if ($c['issued_date']): ?> &middot; <?php echo date('M Y', strtotime($c['issued_date'])); ?><?php endif; ?></small>
                <?php if ($c['link']): ?><div><a href="<?php echo e($c['link']); ?>" target="_blank" class="small">View certificate</a></div><?php endif; ?>
              </div>
              <a href="portfolio.php?del_cert=<?php echo $c['id']; ?>" class="text-danger"><i class="fa-solid fa-trash"></i></a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/page_footer.php'; ?>
