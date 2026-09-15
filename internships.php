<?php
// internships.php - List, search, filter and apply (with profile review + optional timed test)
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'info';
$autoOpen = 0;

// ---- Apply submission (from modal: profile review -> test -> apply) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    $internshipId = (int) ($_POST['internship_id'] ?? 0);

    // 1) Update profile fields student confirmed in the modal
    $st = db_query(
        'UPDATE student_details SET phone=?, city=?, linkedin=?, github=?, bio=? WHERE user_id=?',
        'sssssi',
        array(trim($_POST['phone'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['linkedin'] ?? ''),
              trim($_POST['github'] ?? ''), trim($_POST['bio'] ?? ''), $uid));
    mysqli_stmt_close($st);

    // 2) Duplicate check
    $st = db_query('SELECT id FROM applications WHERE student_id = ? AND internship_id = ?', 'ii', array($uid, $internshipId));
    mysqli_stmt_store_result($st);
    $already = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);

    if ($already) {
        $message = 'You have already applied to this internship.';
        $msgType = 'warning';
    } elseif ($internshipId > 0) {
        $score = null; $skipped = null; $wrong = null; $total = null;

        // 3) If a timed test was taken, compute authoritative results from DB question bank
        if (($_POST['test_taken'] ?? '') === '1' && isset($_POST['test_answers'])) {
            $tst = db_query('SELECT questions FROM job_tests WHERE internship_id = ? LIMIT 1', 'i', array($internshipId));
            $tres = mysqli_stmt_get_result($tst);
            $trow = mysqli_fetch_assoc($tres);
            mysqli_stmt_close($tst);
            if ($trow && $trow['questions']) {
                $qs = json_decode($trow['questions'], true);
                $answers = json_decode($_POST['test_answers'], true);
                if (is_array($qs) && is_array($answers)) {
                    $total = count($qs);
                    $correct = 0; $wrong = 0; $skipped = 0;
                    foreach ($qs as $i => $q) {
                        $sel = isset($answers[$i]) ? (int) $answers[$i] : -1;
                        $ans = isset($q['a']) ? (int) $q['a'] : (int) ($q['answer'] ?? -1);
                        if ($sel === $ans) {
                            $correct++;
                        } elseif ($sel === -1) {
                            $skipped++;
                        } else {
                            $wrong++;
                        }
                    }
                    $score = $total > 0 ? round(($correct / $total) * 100) : 0;
                }
            }
        }

        // 4) Insert application (test results attached if any)
        $ins = db_query(
            'INSERT INTO applications (student_id, internship_id, status, test_score, test_skipped, test_wrong, test_total, test_completed_at)
             VALUES (?, ?, "applied", ?, ?, ?, ?, NOW())',
            'iiiiii', array($uid, $internshipId, $score, $skipped, $wrong, $total));
        mysqli_stmt_close($ins);

        header('Location: my_applications.php?applied=1');
        exit;
    }
}

// ---- Auto-open modal for a specific internship (?apply=ID) ----
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $autoOpen = (int) $_GET['apply'];
}

// ---- Search / filter ----
$keyword = trim($_GET['q'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');
$typeFilter = $_GET['type'] ?? '';

$sql = 'SELECT i.*, u.name AS company FROM internships i
        JOIN users u ON u.id = i.industry_id
        WHERE 1=1';
$types = '';
$params = array();

if ($keyword !== '') {
    $sql .= ' AND (i.title LIKE ? OR i.description LIKE ? OR u.name LIKE ?)';
    $types .= 'sss';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}
if ($typeFilter === 'internship' || $typeFilter === 'job') {
    $sql .= ' AND i.type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}
if ($skillFilter !== '') {
    $sql .= ' AND i.required_skills LIKE ?';
    $types .= 's';
    array_push($params, '%' . $skillFilter . '%');
}

$sql .= ' ORDER BY i.posted_at DESC';

$st = mysqli_prepare($conn, $sql);
if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$result = mysqli_stmt_get_result($st);
$internships = array();
while ($row = mysqli_fetch_assoc($result)) $internships[] = $row;
mysqli_stmt_close($st);

// ---- Fetch skill list for filter dropdown ----
$skills = array();
$st = db_query('SELECT DISTINCT skill_name FROM skills ORDER BY skill_name');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $skills[] = $row['skill_name'];
mysqli_stmt_close($st);

// ---- Fetch applications the student already submitted ----
$applied = array();
$st = db_query('SELECT internship_id FROM applications WHERE student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $applied[$row['internship_id']] = true;
mysqli_stmt_close($st);

// ---- Student profile summary for the apply modal (pre-filled) ----
$prof = array('name'=>$_SESSION['name'], 'email'=>$_SESSION['email'], 'phone'=>'', 'city'=>'',
              'linkedin'=>'', 'github'=>'', 'bio'=>'', 'college_name'=>'', 'course_branch'=>'', 'year'=>'', 'resume_path'=>'');
$st = db_query('SELECT college_name, course_branch, year, phone, city, linkedin, github, bio, resume_path FROM student_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $prof = array_merge($prof, $dr);
mysqli_stmt_close($st);

$pCount = 0; // portfolio completeness
$st = db_query('SELECT (SELECT COUNT(*) FROM portfolio_projects WHERE student_id=?) + (SELECT COUNT(*) FROM portfolio_certificates WHERE student_id=?) + (SELECT COUNT(*) FROM student_skills WHERE student_id=?) AS c', 'iii', array($uid, $uid, $uid));
$res = mysqli_stmt_get_result($st);
$r2 = mysqli_fetch_assoc($res);
mysqli_free_result($res);
$pCount = (int) ($r2['c'] ?? 0);
mysqli_stmt_close($st);

$pageTitle = 'Internships & Jobs';
$activeNav = 'internships';
require 'includes/page_header.php';
?>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="internships.php" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="ipSearch"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
        <input type="text" class="form-control" name="q" id="ipSearch" value="<?php echo e($keyword); ?>" placeholder="Search by title, keyword or company">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="ipType"><i class="fa-solid fa-briefcase"></i> Type</label>
        <select class="form-select" name="type" id="ipType">
          <option value="">Internships &amp; Jobs</option>
          <option value="internship" <?php echo $typeFilter==='internship'?'selected':''; ?>>Internships</option>
          <option value="job" <?php echo $typeFilter==='job'?'selected':''; ?>>Jobs</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="ipSkill"><i class="fa-solid fa-filter"></i> Skill</label>
        <select class="form-select" name="skill" id="ipSkill">
          <option value="">All Skills</option>
          <?php foreach ($skills as $sk): ?>
            <option value="<?php echo e($sk); ?>" <?php echo $skillFilter === $sk ? 'selected' : ''; ?>><?php echo e($sk); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-navy" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<?php if (count($internships) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-briefcase" style="font-size: 3rem; color: #cbd5e1;"></i>
    <p class="mt-3 mb-0">No internships found matching your criteria.</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($internships as $in): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-bold mb-0"><?php echo e($in['title']); ?></h6>
              <span class="badge <?php echo $in['type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($in['type'])); ?></span>
            </div>
            <p class="small text-primary mb-2"><i class="fa-solid fa-building"></i> <?php echo e($in['company']); ?></p>
            <p class="small text-muted-2 mb-3 flex-grow-1"><?php echo e(substr(strip_tags($in['description']), 0, 140)); ?><?php echo strlen(strip_tags($in['description'])) > 140 ? '...' : ''; ?></p>

            <div class="d-flex flex-wrap gap-2 small mb-3">
              <?php if ($in['salary']): ?><span class="badge text-bg-success"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo e($in['salary']); ?></span><?php endif; ?>
              <?php if ($in['duration']): ?><span class="badge text-bg-secondary"><i class="fa-regular fa-clock"></i> <?php echo e($in['duration']); ?></span><?php endif; ?>
              <?php if ($in['mode']): ?><span class="badge text-bg-info text-dark"><i class="fa-solid fa-location-dot"></i> <?php echo e($in['mode']); ?></span><?php endif; ?>
              <?php if ($in['age_limit']): ?><span class="badge text-bg-dark"><i class="fa-solid fa-cake-candles"></i> <?php echo e($in['age_limit']); ?></span><?php endif; ?>
              <span class="badge text-bg-light border"><i class="fa-solid fa-user"></i> <?php echo (int)$in['no_of_posts']; ?> post(s)</span>
            </div>

            <div class="mb-3">
              <small class="text-muted-2 fw-semibold">Required skills:</small>
              <div class="d-flex flex-wrap gap-1 mt-1">
                <?php foreach (array_filter(array_map('trim', explode(',', $in['required_skills']))) as $rs): ?>
                  <span class="badge text-bg-light border"><?php echo e($rs); ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted-2"><i class="fa-regular fa-clock"></i> <?php echo date('d M Y', strtotime($in['posted_at'])); ?></small>
              <?php if (isset($applied[$in['id']])): ?>
                <span class="badge-status badge-applied">Applied</span>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-accent btn-apply"
                        data-id="<?php echo (int)$in['id']; ?>"
                        data-title="<?php echo e($in['title']); ?>"
                        data-company="<?php echo e($in['company']); ?>"
                        data-type="<?php echo e(ucfirst($in['type'])); ?>">
                  <i class="fa-solid fa-paper-plane"></i> Apply
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ================= APPLY MODAL ================= -->
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header apply-modal-head">
        <div class="d-flex align-items-center gap-3">
          <div class="brand-logo"><i class="fa-solid fa-paper-plane"></i></div>
          <div>
            <h5 class="modal-title mb-0" id="applyTitle">Apply</h5>
            <small id="applySub" class="text-muted-2"></small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Wizard progress -->
        <div class="apply-steps d-flex justify-content-between align-items-center mb-4">
          <div class="apply-ss active" data-s="1"><span class="snum">1</span> Details</div>
          <div class="apply-ss-line"></div>
          <div class="apply-ss" data-s="2"><span class="snum">2</span> Test</div>
          <div class="apply-ss-line"></div>
          <div class="apply-ss" data-s="3"><span class="snum">3</span> Result</div>
        </div>

        <!-- STEP 1: Review / pre-filled details -->
        <div class="apply-step" id="step1">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-user-check me-2 text-primary"></i>Your details — confirm before applying</h6>
            <?php if (!empty($prof['resume_path'])): ?>
              <a href="<?php echo e($prof['resume_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file me-1"></i> Resume</a>
            <?php else: ?>
              <span class="badge text-bg-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> No resume — add in <a href="profile.php" class="text-dark fw-bold">Profile</a></span>
            <?php endif; ?>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="ap-name">Full Name</label>
              <input type="text" class="form-control" id="ap-name" value="<?php echo e($prof['name']); ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-email">Email</label>
              <input type="email" class="form-control" id="ap-email" value="<?php echo e($prof['email']); ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-college">College</label>
              <input type="text" class="form-control" id="ap-college" value="<?php echo e($prof['college_name'] ?: '—'); ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-branch">Course / Branch</label>
              <input type="text" class="form-control" id="ap-branch" value="<?php echo e($prof['course_branch'] ?: '—'); ?><?php echo $prof['year'] ? ' · Year ' . e($prof['year']) : ''; ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-phone"><i class="fa-solid fa-phone me-1"></i> Phone</label>
              <input type="tel" class="form-control" id="ap-phone" value="<?php echo e($prof['phone']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-city"><i class="fa-solid fa-location-dot me-1"></i> City</label>
              <input type="text" class="form-control" id="ap-city" value="<?php echo e($prof['city']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-linkedin"><i class="fa-brands fa-linkedin me-1"></i> LinkedIn</label>
              <input type="url" class="form-control" id="ap-linkedin" value="<?php echo e($prof['linkedin']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ap-github"><i class="fa-brands fa-github me-1"></i> GitHub</label>
              <input type="url" class="form-control" id="ap-github" value="<?php echo e($prof['github']); ?>">
            </div>
            <div class="col-12">
              <label class="form-label" for="ap-bio"><i class="fa-solid fa-quote-left me-1"></i> Short Bio</label>
              <textarea class="form-control" id="ap-bio" rows="2"><?php echo e($prof['bio']); ?></textarea>
            </div>
          </div>
          <div class="alert alert-light small mt-3 mb-0">
            <i class="fa-solid fa-id-card me-1 text-primary"></i>
            Your portfolio (<?php echo $pCount; ?> item(s): skills, projects &amp; certs) will be attached automatically with this application.
          </div>
        </div>

        <!-- STEP 2: Start test / or apply -->
        <div class="apply-step d-none" id="step2">
          <div class="text-center py-4" id="testReady">
            <div class="brand-logo mx-auto mb-3" style="width:64px;height:64px;font-size:1.6rem;"><i class="fa-solid fa-clipboard-question"></i></div>
            <h5 class="fw-bold mb-1" id="testTitle">Screening Test</h5>
            <p class="text-muted-2 mb-1"><i class="fa-regular fa-clock me-1"></i><span id="testDur"></span></p>
            <p class="text-muted-2 small mb-4" id="testInfo">Answer as many as you can. Unanswered questions are marked as skipped.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
              <button type="button" class="btn btn-accent btn-lg px-4" id="startTestBtn"><i class="fa-solid fa-circle-play me-1"></i> Start Test</button>
            </div>
          </div>
          <div class="text-center py-4 d-none" id="noTestReady">
            <div class="brand-logo mx-auto mb-3" style="width:64px;height:64px;font-size:1.6rem;background:linear-gradient(135deg,#1f9d5c,#2fc07b);"><i class="fa-solid fa-circle-check"></i></div>
            <h5 class="fw-bold mb-1">No test required</h5>
            <p class="text-muted-2 mb-4">This company hasn't set a screening test. You can apply directly.</p>
          </div>
        </div>

        <!-- STEP 3 (test active): questions + timer -->
        <div class="apply-step d-none" id="step3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-stopwatch me-2 text-primary"></i><?php echo e($_SESSION['name']); ?>'s Test</h6>
            <div class="timer-box" id="timerBox">--:--</div>
          </div>
          <div class="apply-questions bg-light rounded p-3 mb-3" id="questionsWrap"></div>
          <button type="button" class="btn btn-navy btn-lg w-100" id="submitTestBtn"><i class="fa-solid fa-flag-checkered me-1"></i> Submit Test</button>
        </div>

        <!-- STEP 4: Result -->
        <div class="apply-step d-none" id="step4">
          <div class="text-center py-2 mb-3">
            <div class="brand-logo mx-auto mb-3" style="width:70px;height:70px;font-size:1.8rem;background:linear-gradient(135deg,#1f9d5c,#2fc07b);"><i class="fa-solid fa-check-double"></i></div>
            <h5 class="fw-bold mb-1">Test completed!</h5>
            <p class="text-muted-2 mb-0">Your score was calculated and will be attached to the application.</p>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
              <div class="apply-result bg-navy">
                <div class="num" id="resCorrect">0</div>
                <div class="lbl">Correct</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="apply-result bg-accent">
                <div class="num" id="resWrong">0</div>
                <div class="lbl">Wrong</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="apply-result bg-danger-2">
                <div class="num" id="resSkipped">0</div>
                <div class="lbl">Skipped</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="apply-result bg-success-2">
                <div class="num" id="resTotal">0</div>
                <div class="lbl">Total</div>
              </div>
            </div>
          </div>
          <div class="alert alert-success mb-4"><i class="fa-solid fa-shield-halved me-1"></i>
            Submitting the application automatically sends your <strong>test score</strong>, <strong>skipped</strong> and <strong>wrong</strong> counts to the company along with your profile &amp; resume.
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="prevBtn" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i> Cancel</button>
        <button type="button" class="btn btn-navy d-none" id="nextBtn"><i class="fa-solid fa-arrow-right me-1"></i> Next</button>
        <button type="button" class="btn btn-navy d-none" id="applyNowBtn"><i class="fa-solid fa-paper-plane me-1"></i> Apply Now</button>
        <button type="button" class="btn btn-accent d-none" id="finalApplyBtn"><i class="fa-solid fa-paper-plane me-1"></i> Submit Application</button>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="autoOpenId" value="<?php echo $autoOpen; ?>">

<?php require 'includes/page_footer.php'; ?>
<script src="assets/js/apply.js"></script>