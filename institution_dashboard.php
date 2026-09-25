<?php
// institution_dashboard.php - Institution (College) dashboard
// Sections via ?s= jobs|internship|skill|industry|about
require_once 'includes/db_connect.php';
require_role('institution');

$uid = (int) $_SESSION['user_id'];
$section = isset($_GET['s']) ? $_GET['s'] : 'jobs';
if (!in_array($section, array('jobs','internship','skill','industry','about'), true)) $section = 'jobs';

// ---- Institution details ----
$inst = array('institution_name'=>'','institution_type'=>'','location'=>'','about'=>'','college_code'=>'');
$st = db_query('SELECT institution_name, institution_type, location, about, college_code FROM institution_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $inst = array_merge($inst, $dr);
mysqli_stmt_close($st);

$collegeName = $inst['institution_name'];

// ---- College unique code ----
function gen_college_code($conn, $len = 6) {
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($tries = 0; $tries < 40; $tries++) {
        $code = '';
        for ($i = 0; $i < $len; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $st = mysqli_prepare($conn, 'SELECT id FROM institution_details WHERE college_code = ? LIMIT 1');
        mysqli_stmt_bind_param($st, 's', $code);
        mysqli_stmt_execute($st);
        mysqli_stmt_store_result($st);
        $found = mysqli_stmt_num_rows($st) > 0;
        mysqli_stmt_close($st);
        if (!$found) return $code;
    }
    return $code . random_int(10, 99);
}

// auto-generate a code if the institution does not have one yet
if (empty($inst['college_code'])) {
    $newCode = gen_college_code($conn);
    $st = db_query('UPDATE institution_details SET college_code = ? WHERE user_id = ?', 'si', array($newCode, $uid));
    mysqli_stmt_close($st);
    $inst['college_code'] = $newCode;
}

// regenerate code on demand
$codeMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regenerate_code') {
    $newCode = gen_college_code($conn);
    $st = db_query('UPDATE institution_details SET college_code = ? WHERE user_id = ?', 'si', array($newCode, $uid));
    mysqli_stmt_close($st);
    $inst['college_code'] = $newCode;
    $codeMsg = 'New college code generated. Share it with your students.';
}

// students who verified via this code
$verifiedStudents = array();
$st = db_query('SELECT u.id AS student_id, u.name, u.email, sd.course_branch, sd.year FROM student_details sd JOIN users u ON u.id = sd.user_id WHERE UPPER(sd.college_code) = ? ORDER BY u.name', 's', array($inst['college_code']));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $verifiedStudents[] = $row;
mysqli_stmt_close($st);

// ---- The college's students (by matching college_name, case-insensitive) ----
// Build a helper: returns list of student user_ids for this college
function college_student_ids($conn, $collegeName) {
    $ids = array();
    if ($collegeName === '') return $ids;
    $st = mysqli_prepare($conn,
        'SELECT sd.user_id FROM student_details sd
         WHERE LOWER(TRIM(sd.college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $ids[] = (int)$row['user_id'];
    mysqli_stmt_close($st);
    return $ids;
}
$studentIds = college_student_ids($conn, $collegeName);

// ==== DATA PER SECTION ====

// JOBS / INTERNSHIP: per-industry breakdown for the college's students
// Returns list of industries with counts, restricted to a given opportunity type
function industry_breakdown($conn, $collegeName, $type) {
    $rows = array();
    $st = mysqli_prepare($conn,
        'SELECT u.id AS industry_id, u.name AS company,
                COUNT(DISTINCT a.student_id) AS applied,
                COUNT(DISTINCT CASE WHEN a.status IN ("test_pending","shortlisted") THEN a.student_id END) AS selected,
                COUNT(DISTINCT CASE WHEN a.status = "rejected" THEN a.student_id END) AS rejected,
                COUNT(DISTINCT CASE WHEN a.status = "applied" THEN a.student_id END) AS pending
         FROM applications a
         JOIN internships i ON i.id = a.internship_id
         JOIN users u ON u.id = i.industry_id
         JOIN student_details sd ON sd.user_id = a.student_id
         WHERE i.type = ? AND LOWER(TRIM(sd.college_name)) = LOWER(?)
         GROUP BY u.id, u.name
         ORDER BY applied DESC');
    mysqli_stmt_bind_param($st, 'ss', $type, $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    mysqli_stmt_close($st);
    return $rows;
}

// SKILL: total students, test takers, per-skill "lacking" counts
function skill_analytics($conn, $collegeName) {
    $result = array('total'=>0, 'test_takers'=>0, 'skills'=>array());
    if ($collegeName === '') return $result;
    // total students in college
    $st = mysqli_prepare($conn, 'SELECT COUNT(*) FROM student_details WHERE LOWER(TRIM(college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st); mysqli_stmt_bind_result($st, $total);
    mysqli_stmt_fetch($st); $result['total'] = (int)$total; mysqli_stmt_close($st);
    // students who took assessment (have >=1 student_skills row) in this college
    $st = mysqli_prepare($conn,
      'SELECT COUNT(DISTINCT ss.student_id) FROM student_skills ss
       JOIN student_details sd ON sd.user_id = ss.student_id
       WHERE LOWER(TRIM(sd.college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st); mysqli_stmt_bind_result($st, $tt);
    mysqli_stmt_fetch($st); $result['test_takers'] = (int)$tt; mysqli_stmt_close($st);
    // per-skill: # students lacking (score < 70 / low) in this college
    $st = mysqli_prepare($conn,
      'SELECT s.skill_name, COUNT(DISTINCT ss.student_id) AS lacking,
              ROUND(AVG(ss.score)) AS avg_score
       FROM student_skills ss
       JOIN skills s ON s.id = ss.skill_id
       JOIN student_details sd ON sd.user_id = ss.student_id
       WHERE LOWER(TRIM(sd.college_name)) = LOWER(?) AND ss.score < 70
       GROUP BY s.id, s.skill_name
       ORDER BY lacking DESC');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $result['skills'][] = $row;
    mysqli_stmt_close($st);
    return $result;
}

// INDUSTRY: portal-wide industry stats
$industryStats = array();
$st = db_query(
  'SELECT u.name AS company, u.id AS industry_id,
          COUNT(DISTINCT i.id) AS postings,
          COUNT(DISTINCT CASE WHEN i.type="job" THEN i.id END) AS jobs,
          COUNT(DISTINCT CASE WHEN i.type="internship" THEN i.id END) AS internships
   FROM users u
   LEFT JOIN internships i ON i.industry_id = u.id
   WHERE u.role = "industry"
   GROUP BY u.id, u.name ORDER BY postings DESC');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $industryStats[] = $row;
mysqli_stmt_close($st);

$totalIndustries = count($industryStats);
$totalJobPosts = 0; $totalInternPosts = 0; $totalPosts = 0;
foreach ($industryStats as $is) { $totalJobPosts += (int)$is['jobs']; $totalInternPosts += (int)$is['internships']; $totalPosts += (int)$is['postings']; }

$pageTitle = 'Institution Dashboard';
require 'includes/page_header_institution.php';
?>

<!-- Header -->
<div class="page-heading d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
  <div class="flex-grow-1">
    <h4 class="mb-1"><i class="fa-solid fa-building-columns me-2"></i><?php echo e($inst['institution_name'] ?: $_SESSION['name']); ?></h4>
    <p class="mb-0">
      <i class="fa-solid fa-tag me-1"></i><?php echo e($inst['institution_type'] ?: 'Institution'); ?>
      <?php if ($inst['location']): ?> &middot; <i class="fa-solid fa-location-dot me-1"></i><?php echo e($inst['location']); ?><?php endif; ?>
    </p>
  </div>
  <div class="heading-actions">
    <a href="institution_dashboard.php?s=about" class="btn btn-accent btn-sm"><i class="fa-solid fa-pen me-1"></i> Update About</a>
  </div>
</div>

<?php if ($codeMsg): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle me-1"></i><?php echo e($codeMsg); ?></div><?php endif; ?>

<!-- College code banner -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <div class="brand-logo" style="width:58px;height:58px;font-size:1.6rem;background:linear-gradient(135deg,#065f46,#0d9488);color:#fff;"><i class="fa-solid fa-id-card-clip"></i></div>
        <div class="flex-grow-1" style="min-width:230px;">
          <h6 class="fw-bold mb-1">College Identification Code</h6>
          <p class="mb-0 small text-muted-2">Share this code with your students. They enter it in their profile, and they get automatically linked to your college dashboard.</p>
        </div>
        <div class="d-flex flex-column align-items-stretch gap-2" style="min-width:210px;">
          <span class="college-code-pill" id="collegeCodePill"><?php echo e($inst['college_code']); ?></span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-navy" type="button" onclick="copyCollegeCode()"><i class="fa-solid fa-copy me-1"></i>Copy</button>
            <form method="post" action="institution_dashboard.php?s=<?php echo e($section); ?>" onsubmit="return confirm('Generate a new code? Old links will stop working for new students.');">
              <input type="hidden" name="action" value="regenerate_code">
              <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fa-solid fa-rotate me-1"></i>Regenerate</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <div class="text-center me-3">
          <div class="stat-num" style="font-size:2.2rem;"><?php echo count($verifiedStudents); ?></div>
          <div class="small text-muted-2 fw-regular">Verified</div>
          <div class="small text-muted-2 fw-regular">Students</div>
        </div>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-1">Your College Students</h6>
          <p class="mb-0 small text-muted-2"><?php echo count($studentIds); ?> total student(s) linked to "<?php echo e($collegeName); ?>" — <?php echo count($verifiedStudents); ?> verified via your code.</p>
          <?php if (count($verifiedStudents) > 0): ?>
            <a href="student_search.php" class="btn btn-sm btn-accent mt-2"><i class="fa-solid fa-users me-1"></i> View List</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function copyCollegeCode() {
    var el = document.getElementById('collegeCodePill');
    var code = el.textContent.trim();
    var ta = document.createElement('textarea');
    ta.value = code;
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    var b = el.closest('.card').querySelector('.btn-navy');
    if (b) { var t = b.innerHTML; b.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copied!'; setTimeout(function () { b.innerHTML = t; }, 1500); }
  }
</script>

<?php if (count($studentIds) === 0): ?>
  <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i>
    No student data is linked to "<?php echo e($collegeName); ?>" yet. Share your college code above, or students can register with this college name to appear in your analytics.
  </div>
<?php endif; ?>

<?php
// ========== JOBS SECTION ==========
if ($section === 'jobs'):
    $jrows = industry_breakdown($conn, $collegeName, 'job');
    $jAgg = array('selected'=>0,'pending'=>0,'rejected'=>0,'applied'=>0);
    foreach ($jrows as $r) {
        $jAgg['applied']   += (int)$r['applied'];
        $jAgg['selected']  += (int)$r['selected'];
        $jAgg['pending']   += (int)$r['pending'];
        $jAgg['rejected']  += (int)$r['rejected'];
    }
?>
  <h5 class="section-title"><i class="fa-solid fa-briefcase me-2"></i>Jobs — Placement Overview</h5>
  <p class="text-muted-2 mb-3">How many students from <?php echo e($collegeName); ?> applied / got selected / are pending / rejected per company.</p>
  <?php if (count($jrows) === 0): ?>
    <div class="alert alert-light">No job applications from your college yet.</div>
  <?php else: ?>
    <div class="card mb-4">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
          <div><h6 class="mb-0">Jobs Pipeline</h6><small>Aggregate placement status for your students</small></div>
        </div>
        <div class="d-flex align-items-center gap-4 flex-wrap">
          <div class="chart-box chart-box-sm flex-grow-1" style="max-width:260px;">
            <canvas id="jobsDonut" data-counts='<?php echo json_encode(array($jAgg['selected'], $jAgg['pending'], $jAgg['rejected'])); ?>' data-labels='["Selected","Pending","Rejected"]'></canvas>
          </div>
          <div class="chart-legend flex-column" id="jobsLegend"></div>
        </div>
      </div>
    </div>
    <div class="row g-4">
      <?php foreach ($jrows as $r): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card card-hover h-100"><div class="card-body">
            <div class="tbl-company mb-3">
              <div class="tbl-c-logo"><?php echo e(strtoupper(substr($r['company'], 0, 2))); ?></div>
              <h6 class="fw-bold text-primary mb-0"><?php echo e($r['company']); ?></h6>
            </div>
            <div class="row text-center g-2">
              <div class="col-6"><div class="stat-num text-success" style="font-size:1.5rem;"><?php echo (int)$r['selected']; ?></div><div class="small text-muted-2">Selected</div></div>
              <div class="col-6"><div class="stat-num text-warning" style="font-size:1.5rem;"><?php echo (int)$r['pending']; ?></div><div class="small text-muted-2">Pending</div></div>
              <div class="col-6"><div class="stat-num text-danger" style="font-size:1.5rem;"><?php echo (int)$r['rejected']; ?></div><div class="small text-muted-2">Rejected</div></div>
              <div class="col-6"><div class="stat-num" style="font-size:1.5rem;"><?php echo (int)$r['applied']; ?></div><div class="small text-muted-2">Total Applied</div></div>
            </div>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
// ========== INTERNSHIP SECTION ==========
if ($section === 'internship'):
    $iro = industry_breakdown($conn, $collegeName, 'internship');
    $iAgg = array('selected'=>0,'pending'=>0,'rejected'=>0,'applied'=>0);
    foreach ($iro as $r) {
        $iAgg['applied']   += (int)$r['applied'];
        $iAgg['selected']  += (int)$r['selected'];
        $iAgg['pending']   += (int)$r['pending'];
        $iAgg['rejected']  += (int)$r['rejected'];
    }
?>
  <h5 class="section-title"><i class="fa-solid fa-rocket me-2"></i>Internships — Overview</h5>
  <p class="text-muted-2 mb-3">Students from <?php echo e($collegeName); ?> applying to internships per company.</p>
  <?php if (count($iro) === 0): ?>
    <div class="alert alert-light">No internship applications from your college yet.</div>
  <?php else: ?>
    <div class="card mb-4">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
          <div><h6 class="mb-0">Internship Pipeline</h6><small>Aggregate status for your students</small></div>
        </div>
        <div class="d-flex align-items-center gap-4 flex-wrap">
          <div class="chart-box chart-box-sm flex-grow-1" style="max-width:260px;">
            <canvas id="internDonut" data-counts='<?php echo json_encode(array($iAgg['selected'], $iAgg['pending'], $iAgg['rejected'])); ?>' data-labels='["Selected","Pending","Rejected"]'></canvas>
          </div>
          <div class="chart-legend flex-column" id="internLegend"></div>
        </div>
      </div>
    </div>
    <div class="row g-4">
      <?php foreach ($iro as $r): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card card-hover h-100"><div class="card-body">
            <div class="tbl-company mb-3">
              <div class="tbl-c-logo"><?php echo e(strtoupper(substr($r['company'], 0, 2))); ?></div>
              <h6 class="fw-bold text-primary mb-0"><?php echo e($r['company']); ?></h6>
            </div>
            <div class="row text-center g-2">
              <div class="col-6"><div class="stat-num text-success" style="font-size:1.5rem;"><?php echo (int)$r['selected']; ?></div><div class="small text-muted-2">Selected</div></div>
              <div class="col-6"><div class="stat-num text-warning" style="font-size:1.5rem;"><?php echo (int)$r['pending']; ?></div><div class="small text-muted-2">Pending</div></div>
              <div class="col-6"><div class="stat-num text-danger" style="font-size:1.5rem;"><?php echo (int)$r['rejected']; ?></div><div class="small text-muted-2">Rejected</div></div>
              <div class="col-6"><div class="stat-num" style="font-size:1.5rem;"><?php echo (int)$r['applied']; ?></div><div class="small text-muted-2">Total Applied</div></div>
            </div>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
// ========== SKILL SECTION ==========
if ($section === 'skill'):
    $sa = skill_analytics($conn, $collegeName);
?>
  <h5 class="section-title"><i class="fa-solid fa-chart-simple me-2"></i>Skill Analytics — <?php echo e($collegeName); ?></h5>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card bg-navy">
        <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
        <div><div class="stat-num"><?php echo $sa['total']; ?></div><div class="stat-label">Total Students</div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card bg-accent">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
        <div><div class="stat-num"><?php echo $sa['test_takers']; ?></div><div class="stat-label">Took Skill Test</div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card bg-success-2">
        <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div><div class="stat-num"><?php echo $sa['total'] - $sa['test_takers']; ?></div><div class="stat-label">Yet to Test</div></div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="card-title-banner">
        <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <div><h6 class="mb-0">Skill Assessment Coverage</h6><small>Share of students who have completed the assessment</small></div>
      </div>
      <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="chart-box chart-box-sm flex-grow-1" style="max-width:240px;">
          <canvas id="covDonut" data-counts='<?php echo json_encode(array($sa['test_takers'], max(0, $sa['total'] - $sa['test_takers']))); ?>' data-labels='["Tested","Yet to test"]'></canvas>
        </div>
        <div class="chart-legend flex-column" id="covLegend"></div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="card-title-banner">
        <div class="b-icon"><i class="fa-solid fa-chart-column"></i></div>
        <div><h6 class="mb-0">Skill Gap Analysis</h6><small>Students scoring below 70 per skill</small></div>
      </div>
      <?php if (count($sa['skills']) === 0): ?>
        <p class="text-muted-2 mb-0">No skill assessment data available for your college yet.</p>
      <?php else: ?>
        <div class="chart-box">
          <canvas id="gapBar"
            data-skills='<?php echo json_encode(array_column($sa['skills'], 'skill_name')); ?>'
            data-lacking='<?php echo json_encode(array_map(fn($x) => (int)$x['lacking'], $sa['skills'])); ?>'></canvas>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Students Lacking Skills (score &lt; 70)</h6>
      <?php if (count($sa['skills']) === 0): ?>
        <p class="text-muted-2 mb-0">No skill assessment data available for your college yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light"><tr><th>Skill</th><th>Avg Score</th><th># Students Lacking</th><th style="width:40%;">Trend</th></tr></thead>
            <tbody>
              <?php foreach ($sa['skills'] as $sk): ?>
                <?php $pct = $sa['total'] > 0 ? round(((int)$sk['lacking'] / $sa['total']) * 100) : 0; ?>
                <tr>
                  <td class="fw-semibold"><?php echo e($sk['skill_name']); ?></td>
                  <td><span class="badge <?php echo (int)$sk['avg_score']>=50?'text-bg-warning':'text-bg-danger'; ?>"><?php echo (int)$sk['avg_score']; ?>/100</span></td>
                  <td><span class="badge text-bg-danger"><?php echo (int)$sk['lacking']; ?> students</span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2 small text-muted-2">
                      <div class="progress flex-grow-1" style="height:8px;"><div class="progress-bar bg-danger" style="width:<?php echo $pct; ?>%;"></div></div>
                      <?php echo $pct; ?>%
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
<?php endif; ?>

<?php
// ========== INDUSTRY SECTION ==========
if ($section === 'industry'):
?>
  <h5 class="section-title"><i class="fa-solid fa-industry me-2"></i>Industry Partners on the Portal</h5>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card bg-navy"><div class="stat-icon"><i class="fa-solid fa-industry"></i></div><div><div class="stat-num"><?php echo $totalIndustries; ?></div><div class="stat-label">Industries</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-accent"><div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div><div><div class="stat-num"><?php echo $totalPosts; ?></div><div class="stat-label">Total Postings</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-success-2"><div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div><div><div class="stat-num"><?php echo $totalJobPosts; ?></div><div class="stat-label">Jobs</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-danger-2"><div class="stat-icon"><i class="fa-solid fa-rocket"></i></div><div><div class="stat-num"><?php echo $totalInternPosts; ?></div><div class="stat-label">Internships</div></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="card-title-banner">
        <div class="b-icon"><i class="fa-solid fa-chart-column"></i></div>
        <div><h6 class="mb-0">Postings per Industry Partner</h6><small>Who is actively hiring on the portal</small></div>
      </div>
      <?php if (count($industryStats) === 0): ?>
        <p class="text-muted-2 mb-0">No industries registered yet.</p>
      <?php else: ?>
        <div class="chart-box">
          <canvas id="indBar"
            data-companies='<?php echo json_encode(array_map(fn($is) => mb_substr($is['company'], 0, 18) . (mb_strlen($is['company']) > 18 ? '…' : ''), array_slice($industryStats, 0, 10))); ?>'
            data-jobs='<?php echo json_encode(array_map(fn($is) => (int)$is['jobs'], array_slice($industryStats, 0, 10))); ?>'
            data-internships='<?php echo json_encode(array_map(fn($is) => (int)$is['internships'], array_slice($industryStats, 0, 10))); ?>'></canvas>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fa-solid fa-list me-2 text-primary"></i>Industry-wise Postings</h6>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light"><tr><th>Company</th><th>Postings</th><th>Internships</th><th>Jobs</th></tr></thead>
          <tbody>
            <?php foreach ($industryStats as $is): ?>
              <tr>
                <td class="fw-semibold"><i class="fa-solid fa-building me-1 text-muted"></i> <?php echo e($is['company']); ?></td>
                <td><span class="badge text-bg-primary"><?php echo (int)$is['postings']; ?></span></td>
                <td><span class="badge text-bg-info text-dark"><?php echo (int)$is['internships']; ?></span></td>
                <td><span class="badge text-bg-warning"><?php echo (int)$is['jobs']; ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($industryStats) === 0): ?><tr><td colspan="4" class="text-center text-muted-2">No industries registered yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
// ========== ABOUT SECTION ==========
if ($section === 'about'):
    $errors = array(); $msg = ''; $msgType = 'success';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_inst') {
        $iname = trim($_POST['institution_name'] ?? '');
        $itype = trim($_POST['institution_type'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $about = trim($_POST['about'] ?? '');
        if ($iname === '') $errors[] = 'Institution name cannot be empty.';
        if (empty($errors)) {
            $st = db_query('UPDATE institution_details SET institution_name=?, institution_type=?, location=?, about=? WHERE user_id=?', 'ssssi', array($iname, $itype, $loc, $about, $uid));
            mysqli_stmt_close($st);
            $msg = 'Institution information updated.';
            $inst['institution_name']=$iname; $inst['institution_type']=$itype; $inst['location']=$loc; $inst['about']=$about;
        }
    }
?>
  <h5 class="section-title"><i class="fa-solid fa-building-columns me-2"></i>About Institution</h5>
  <?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($msg); ?></div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fa-solid fa-pen me-2 text-primary"></i>Institution Information</h6>
          <form method="post" action="institution_dashboard.php?s=about">
            <input type="hidden" name="action" value="update_inst">
            <div class="mb-3"><label class="form-label" for="inst-inname">Institution Name</label><input type="text" class="form-control" name="institution_name" id="inst-inname" value="<?php echo e($inst['institution_name']); ?>"></div>
            <div class="row g-3 mb-3">
              <div class="col-md-6"><label class="form-label" for="inst-intype">Institution Type</label><input type="text" class="form-control" name="institution_type" id="inst-intype" value="<?php echo e($inst['institution_type']); ?>"></div>
              <div class="col-md-6"><label class="form-label" for="inst-loc">Location</label><input type="text" class="form-control" name="location" id="inst-loc" value="<?php echo e($inst['location']); ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label" for="inst-about">About</label><textarea class="form-control" name="about" id="inst-about" rows="4" placeholder="About your institution..."><?php echo e($inst['about']); ?></textarea></div>
            <button class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> Save</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Preview</h6>
          <h5 class="fw-bold"><?php echo e($inst['institution_name']); ?></h5>
          <p class="text-muted-2 mb-2"><?php echo e($inst['institution_type']); ?> &middot; <?php echo e($inst['location']); ?></p>
          <p class="mb-0"><?php echo e($inst['about'] ?: 'No about text yet.'); ?></p>
          <hr>
          <p class="small text-muted-2 mb-0"><i class="fa-solid fa-shield-halved"></i> Signed in as <strong><?php echo e($_SESSION['email']); ?></strong> (Institution)</p>
          <a href="logout.php" class="btn btn-outline-danger w-100 mt-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Verified students list -->
  <div class="card mt-4">
    <div class="card-body">
      <div class="card-title-banner mb-3">
        <div class="b-icon"><i class="fa-solid fa-users"></i></div>
        <div>
          <h6 class="mb-0">Verified Students using your College Code</h6>
          <small>Students who entered code <strong><?php echo e($inst['college_code']); ?></strong> in their profile</small>
        </div>
      </div>
      <?php if (count($verifiedStudents) === 0): ?>
        <p class="text-muted-2 mb-0">No student has verified with your code yet. Ask your students to enter <code><?php echo e($inst['college_code']); ?></code> under Profile &rarr; College Identification Code.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Student</th><th>Email</th><th>Course / Branch</th><th>Year</th><th></th></tr></thead>
            <tbody>
              <?php $n = 1; foreach ($verifiedStudents as $vs): ?>
                <tr>
                  <td><?php echo $n++; ?></td>
                  <td class="fw-semibold"><?php echo e($vs['name']); ?></td>
                  <td><?php echo e($vs['email']); ?></td>
                  <td><?php echo e($vs['course_branch'] ?: '—'); ?></td>
                  <td><?php echo e($vs['year'] ?: '—'); ?></td>
                  <td class="text-end"><a href="view_student_profile.php?id=<?php echo (int)$vs['student_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i> View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<script>
  (function () {
    function initCharts() {
      if (typeof Chart === 'undefined') return;

      function buildDonut(id) {
      var cv = document.getElementById(id);
      if (!cv || !cv.dataset.counts) return;
      var sc;
      try { sc = JSON.parse(cv.dataset.counts).map(Number); } catch (e) { return; }
      var labels = [];
      try { labels = JSON.parse(cv.dataset.labels || '[]'); } catch (e) { labels = []; }
      if (sc.reduce(function (a, b) { return a + b; }, 0) === 0) return;
      var colorsMap = { Selected: '#1f9d5c', Pending: '#f0a500', Rejected: '#e04f4f', Tested: '#3b6fe0', 'Yet to test': '#cbd5e1' };
      var colors = labels.map(function (l) { return colorsMap[l] || '#3b6fe0'; });
      var seen = sc.map(function (c) { return c > 0; });
      new Chart(cv, {
        type: 'doughnut',
        data: {
          labels: labels.filter(function (_, i) { return seen[i]; }),
          datasets: [{ data: sc.filter(function (_, i) { return seen[i]; }), backgroundColor: colors.filter(function (_, i) { return seen[i]; }), borderWidth: 3, borderColor: '#fff' }]
        },
        options: { responsive: true, maintainAspectRatio: true, cutout: '68%', plugins: { legend: { display: false } } }
      });
      var leg = document.getElementById(id.replace('Donut', 'Legend'));
      if (leg) {
        leg.innerHTML = labels.map(function (l, i) {
          return '<span class="cl-item"><span class="cl-dot" style="background:' + colors[i] + ';"></span>' + l +
                 ' <strong>' + sc[i] + '</strong></span>';
        }).join('');
      }
    }

    buildDonut('jobsDonut');
    buildDonut('internDonut');
    buildDonut('covDonut');

    // Skill gap bar chart
    var gap = document.getElementById('gapBar');
    if (gap && gap.dataset.skills && gap.dataset.skills !== '[]') {
      var gs, gl;
      try { gs = JSON.parse(gap.dataset.skills); gl = JSON.parse(gap.dataset.lacking).map(Number); } catch (e) { gs = gl = null; }
      if (gs && gs.length > 0) {
        new Chart(gap, {
          type: 'bar',
          data: {
            labels: gs,
            datasets: [{
              label: 'Students lacking',
              data: gl,
              backgroundColor: 'rgba(224,79,79,0.75)',
              hoverBackgroundColor: '#e04f4f',
              borderRadius: 8,
              maxBarThickness: 48
            }]
          },
          options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0, color: '#a7b1c5' }, grid: { color: '#eef2f9' } },
              x: { ticks: { color: '#6b7890', font: { size: 11 } }, grid: { display: false } }
            }
          }
        });
      }
    }

    // Industry postings stacked bar
    var ind = document.getElementById('indBar');
    if (ind && ind.dataset.companies) {
      var comps, jobs, ints;
      try {
        comps = JSON.parse(ind.dataset.companies);
        jobs = JSON.parse(ind.dataset.jobs).map(Number);
        ints = JSON.parse(ind.dataset.internships).map(Number);
      } catch (e) { comps = jobs = ints = null; }
      if (comps && comps.length > 0) {
        new Chart(ind, {
          type: 'bar',
          data: {
            labels: comps,
            datasets: [
              { label: 'Jobs', data: jobs, backgroundColor: 'rgba(240,165,0,0.85)', borderRadius: 6, maxBarThickness: 24 },
              { label: 'Internships', data: ints, backgroundColor: 'rgba(59,111,224,0.85)', borderRadius: 6, maxBarThickness: 24 }
            ]
          },
          options: {
            responsive: true, maintainAspectRatio: true,
            scales: {
              y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0, color: '#a7b1c5' }, grid: { color: '#eef2f9' } },
              x: { stacked: true, ticks: { color: '#6b7890', font: { size: 11 } }, grid: { display: false } }
            },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } } }
          }
        });
      }
    }

    } // end initCharts

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCharts);
    } else {
      initCharts();
    }
  })();
</script>

<?php require 'includes/page_footer_role.php'; ?>
