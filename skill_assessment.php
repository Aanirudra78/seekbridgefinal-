<?php
// skill_assessment.php - Skill assessment quiz (professional UI)
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];
$message = '';

// Shared MCQ bank (also used for industry test autogeneration)
require_once 'includes/question_bank.php';

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    // We'll track score per skill. Build a lookup array.
    // For each skill assessed, count correct answers.
    $perSkill = array();
    foreach ($_POST as $key => $val) {
        if (preg_match('/^q_(.+)_(\d+)$/', $key, $m)) {
            $skill = $m[1];
            $qi = (int)$m[2];
            $answerIdx = (int)$val;
            if (!isset($perSkill[$skill])) $perSkill[$skill] = array('correct' => 0, 'total' => 0);
            $questions = $question_bank[$skill] ?? array();
            $perSkill[$skill]['total']++;
            if (isset($questions[$qi]) && $questions[$qi]['a'] === $answerIdx) {
                $perSkill[$skill]['correct']++;
            }
        }
    }

    if (count($perSkill) > 0) {
        // Delete previous assessments for this student, then insert new ones
        $d = db_query('DELETE FROM student_skills WHERE student_id = ?', 'i', array($uid));
        mysqli_stmt_close($d);

        $saved = 0;
        foreach ($perSkill as $skill => $data) {
            if ($data['total'] === 0) continue;
            $score = round(($data['correct'] / $data['total']) * 100);

            // Get or create skill id
            $st = db_query('SELECT id FROM skills WHERE skill_name = ?', 's', array($skill));
            mysqli_stmt_store_result($st);
            mysqli_stmt_bind_result($st, $sid);
            $exists = mysqli_stmt_fetch($st);
            mysqli_stmt_close($st);

            if (!$exists) {
                $ins = db_query('INSERT INTO skills (skill_name) VALUES (?)', 's', array($skill));
                $sid = mysqli_insert_id($conn);
                mysqli_stmt_close($ins);
            }

            $ins2 = db_query(
                'INSERT INTO student_skills (student_id, skill_id, score) VALUES (?, ?, ?)',
                'iii', array($uid, $sid, $score));
            mysqli_stmt_close($ins2);
            $saved++;
        }

        if ($saved > 0) {
            header('Location: student_dashboard.php?assess=1');
            exit;
        }
    }
}

$pageTitle = 'Skill Assessment';
$activeNav = 'skill';
require 'includes/page_header.php';

// load existing assessment
$existing = array();
$st = db_query(
  'SELECT s.skill_name, ss.score FROM student_skills ss JOIN skills s ON s.id = ss.skill_id WHERE ss.student_id = ?',
  'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $existing[$row['skill_name']] = (int)$row['score'];
mysqli_stmt_close($st);

$numSkills = count($question_bank);
$numQuestions = array_sum(array_map('count', $question_bank));
$strengthCount = count(array_filter($existing, fn($v) => $v >= 70));
$gapCount = count($existing) - $strengthCount;
$avgScore = count($existing) > 0 ? round(array_sum($existing) / count($existing)) : 0;
?>

<?php if (isset($_GET['assess'])): ?>
  <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Your assessment has been saved successfully!</div>
<?php endif; ?>

<!-- Professional page heading -->
<div class="page-heading d-flex flex-wrap align-items-center gap-3 mb-4">
  <div class="brand-logo" style="width:60px;height:60px;font-size:1.6rem;background:linear-gradient(135deg,#f0a500,#ffb733);color:#fff;"><i class="fa-solid fa-clipboard-question"></i></div>
  <div class="flex-grow-1">
    <h4 class="mb-1">Skill Assessment Hub</h4>
    <p class="mb-0">Prove your expertise across <?php echo $numSkills; ?> skills &middot; <?php echo $numQuestions; ?> questions &middot; results feed your dashboard, courses &amp; applications</p>
  </div>
  <div class="heading-actions d-flex gap-2 flex-wrap">
    <span class="badge bg-accent text-dark"><i class="fa-solid fa-star me-1"></i> Current Average: <?php echo $avgScore; ?>%</span>
  </div>
</div>

<!-- Quick summary chips -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card bg-navy">
      <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
      <div><div class="stat-num"><?php echo $numSkills; ?></div><div class="stat-label">Skills Available</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-accent">
      <div class="stat-icon"><i class="fa-solid fa-list-check"></i></div>
      <div><div class="stat-num"><?php echo $numQuestions; ?></div><div class="stat-label">Total Questions</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-success-2">
      <div class="stat-icon"><i class="fa-solid fa-thumbs-up"></i></div>
      <div><div class="stat-num"><?php echo $strengthCount; ?></div><div class="stat-label">Your Strengths (70+)</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-danger-2">
      <div class="stat-icon"><i class="fa-solid fa-brain"></i></div>
      <div><div class="stat-num"><?php echo $gapCount; ?></div><div class="stat-label">Areas To Improve</div></div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- ==================== QUIZ (left) ==================== -->
  <div class="col-lg-8">
    <form method="post" action="skill_assessment.php" id="skillForm">
      <input type="hidden" name="submit_assessment" value="1">
      <?php foreach ($question_bank as $skill => $questions): ?>
        <?php $lastScore = $existing[$skill] ?? null; $qk = e($skill); ?>
        <div class="quiz-card mb-4">
          <div class="quiz-head">
            <div class="d-flex align-items-center gap-3">
              <div class="brand-logo" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-code"></i></div>
              <div>
                <h6><?php echo $qk; ?></h6>
                <span class="quiz-questions"><i class="fa-regular fa-circle-question me-1"></i><?php echo count($questions); ?> questions</span>
              </div>
            </div>
            <?php if ($lastScore !== null): ?>
              <span class="badge <?php echo $lastScore >= 70 ? 'text-bg-success' : 'text-bg-warning'; ?>" style="font-size:0.85rem;">
                <i class="fa-solid <?php echo $lastScore >= 70 ? 'fa-circle-check' : 'fa-arrow-trend-up'; ?> me-1"></i>Last Score: <?php echo $lastScore; ?>/100
              </span>
            <?php else: ?>
              <span class="badge bg-accent text-dark">Not yet assessed</span>
            <?php endif; ?>
          </div>
          <div class="quiz-body">
            <?php foreach ($questions as $qi => $qq): ?>
              <div class="mb-4">
                <div class="fw-semibold mb-2 d-flex align-items-start gap-2">
                  <span class="badge bg-navy rounded-pill mt-1" style="min-width:26px;"><?php echo ($qi+1); ?></span>
                  <span><?php echo e($qq['q']); ?></span>
                </div>
                <div class="row g-2">
                  <?php foreach ($qq['o'] as $oi => $opt): ?>
                    <div class="col-md-6">
                      <label class="q-opt" for="opt_<?php echo $qk; ?>_<?php echo $qi; ?>_<?php echo $oi; ?>">
                        <input type="radio" name="q_<?php echo $qk; ?>_<?php echo $qi; ?>" id="opt_<?php echo $qk; ?>_<?php echo $qi; ?>_<?php echo $oi; ?>" value="<?php echo $oi; ?>" required>
                        <span class="opt-letter"><?php echo chr(65 + $oi); ?></span><?php echo e($opt); ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="card bg-gradient-soft border-0 mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
          <i class="fa-solid fa-shield-halved" style="font-size:1.6rem;color:var(--navy);"></i>
          <div class="flex-grow-1">
            <strong class="d-block text-navy">Save your results</strong>
            <span class="small text-muted-2">Your scores update your Skill Profile on the dashboard, power course recommendations and get attached to every application.</span>
          </div>
          <button type="submit" name="submit_assessment" class="btn btn-accent btn-lg">
            <i class="fa-solid fa-check-double"></i> Submit Assessment
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- ==================== RESULTS SIDEBAR (right) ==================== -->
  <div class="col-lg-4">
    <?php if (count($existing) > 0): ?>
      <div class="card mb-4">
        <div class="card-body">
          <div class="card-title-banner">
            <div class="b-icon"><i class="fa-solid fa-chart-pie"></i></div>
            <div><h6 class="mb-0">Your Skill Profile</h6><small><?php echo count($existing); ?> skill(s) assessed</small></div>
          </div>

          <div class="chart-box chart-box-sm">
            <canvas id="skillDoughnut" data-skills='<?php echo json_encode(array_keys($existing)); ?>' data-scores='<?php echo json_encode(array_values($existing)); ?>'></canvas>
          </div>

          <div class="chart-legend" id="skillLegend"></div>

          <hr class="my-4">
          <div class="d-flex align-items-center mb-3">
            <div style="flex:1;"><span class="text-success fw-bold"><i class="fa-solid fa-thumbs-up me-1"></i>Strengths</span></div>
            <span class="badge text-bg-success"><?php echo $strengthCount; ?></span>
          </div>
          <div class="d-flex align-items-center mb-3">
            <div style="flex:1;"><span class="text-warning fw-bold"><i class="fa-solid fa-brain me-1"></i>Areas to improve</span></div>
            <span class="badge text-bg-warning"><?php echo $gapCount; ?></span>
          </div>
          <div class="d-flex align-items-center">
            <div style="flex:1;"><span class="fw-bold text-navy"><i class="fa-solid fa-gauge-high me-1"></i>Overall average</span></div>
            <span class="badge bg-navy"><?php echo $avgScore; ?>%</span>
          </div>

          <hr class="my-4">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-bars-progress me-2 text-primary"></i>Score per skill</h6>
          <div class="row g-2">
            <?php foreach ($existing as $name => $score): ?>
              <div class="col-12">
                <div class="skill-tile d-flex align-items-center gap-3">
                  <div class="flex-grow-1">
                    <div class="st-name"><?php echo e($name); ?></div>
                    <div class="st-bar"><span style="width:<?php echo $score; ?>%;background:<?php echo $score >= 70 ? '#2e9e5b' : '#f0a500'; ?>;"></span></div>
                    <div class="st-meta"><?php echo $score >= 70 ? 'Proficient' : 'Needs practice'; ?></div>
                  </div>
                  <span class="badge <?php echo $score >= 70 ? 'text-bg-success' : 'text-bg-warning'; ?>" style="font-size:0.9rem;"><?php echo $score; ?>%</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body">
        <div class="card-title-banner">
          <div class="b-icon"><i class="fa-solid fa-circle-info"></i></div>
          <div><h6 class="mb-0">How scoring works</h6><small>Read before you start</small></div>
        </div>
        <ul class="list-unstyled small mb-0 d-grid gap-3">
          <li class="d-flex gap-2"><i class="fa-solid fa-check-circle text-success mt-1"></i><span>Each skill has <strong>5 multiple-choice questions</strong>.</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-percent text-primary mt-1"></i><span>Your skill score = <strong>percentage of correct answers</strong> (0–100).</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-star text-warning mt-1"></i><span>Scores of <strong>70+</strong> are counted as strengths, lower scores as areas to improve.</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-rotate text-danger mt-1"></i><span>Re-take any time — your latest result replaces the old one.</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-paper-plane text-navy mt-1"></i><span>Results automatically attach to every job / internship application.</span></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ================= PRE-TEST INSTRUCTIONS ================= -->
<div class="test-intro active" id="saIntro">
  <div class="ti-card">
    <div class="ti-head">
      <div class="ti-logo"><i class="fa-solid fa-shield-halved"></i></div>
      <h5>Skill Assessment — Proctored Mode</h5>
      <p>Read the rules carefully before you start</p>
    </div>
    <div class="ti-body">
      <div class="ti-rule">
        <div class="ri"><i class="fa-solid fa-stopwatch"></i></div>
        <div><strong>Total time: 10 minutes (fixed)</strong><span>The test has a live 10 minute countdown and auto-submits when time runs out. It cannot be paused.</span></div>
      </div>
      <div class="ti-rule">
        <div class="ri"><i class="fa-solid fa-lock"></i></div>
        <div><strong>Your screen will be locked</strong><span>Once you start, only the test is visible. Navigation, menus and other page content stay hidden while it runs.</span></div>
      </div>
      <div class="ti-rule">
        <div class="ri"><i class="fa-solid fa-repeat"></i></div>
        <div><strong>Tab / window switching is monitored</strong><span>Leaving this window is detected. The 1st offence shows a warning; on the 2nd offence the test submits automatically.</span></div>
      </div>
      <div class="ti-rule">
        <div class="ri"><i class="fa-solid fa-ban"></i></div>
        <div><strong>No refresh, back button or closing the tab</strong><span>Refreshing or leaving the page is treated as an offence and can submit your test at any point.</span></div>
      </div>
    </div>
    <div class="ti-footer">
      <label class="ti-check">
        <input type="checkbox" id="saAgree">
        <span>I understand the test is proctored — the timer cannot be paused and leaving the window is monitored.</span>
      </label>
      <button type="button" class="btn btn-accent btn-lg w-100" id="saStartBtn" disabled>
        <i class="fa-solid fa-circle-play me-1"></i> Accept &amp; Start Test
      </button>
    </div>
  </div>
</div>

<!-- ================= LOCKED TEST SCREEN ================= -->
<div class="test-lock" id="saLock">
  <div class="tl-topbar">
    <div>
      <div class="tl-title"><i class="fa-solid fa-shield-halved"></i> Skill Assessment</div>
      <div class="tl-sub">Proctored mode active — screen is locked</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
      <span class="tl-rule-chip"><i class="fa-solid fa-repeat"></i> 2 offences = auto-submit</span>
      <div class="tl-timer" id="saTimer">10:00</div>
    </div>
  </div>
  <div class="tl-warn" id="saWarn"></div>
  <div class="tl-body" id="saBody"></div>
  <div class="tl-footer">
    <button type="button" class="btn btn-navy btn-lg" id="saSubmitBtn" form="skillForm">
      <i class="fa-solid fa-flag-checkered me-1"></i> Submit Test
    </button>
  </div>
</div>

<script>
  // --- Selectable question options ---
  document.querySelectorAll('.q-opt').forEach(function (opt) {
    var input = opt.querySelector('input');
    input.addEventListener('change', function () {
      document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (r) {
        r.closest('.q-opt').classList.remove('selected');
      });
      opt.classList.add('selected');
    });
  });

  // --- Skill profile doughnut ---
  (function () {
    function initSkillChart() {
      if (typeof Chart === 'undefined') return;
      var cv = document.getElementById('skillDoughnut');
    if (!cv) return;
    var skills, scores;
    try {
      skills = JSON.parse(cv.dataset.skills || '[]');
      scores = JSON.parse(cv.dataset.scores || '[]').map(Number);
    } catch (e) { return; }
    if (skills.length === 0) return;

    var palette = ['#0e2a5c', '#3b6fe0', '#1f9d5c', '#f0a500', '#e04f4f',
                   '#7a5cf0', '#2fb7c0', '#d08a00', '#34405a', '#f2727f'];
    var colors = skills.map(function (_, i) { return palette[i % palette.length]; });
    var barColors = scores.map(function (s) { return s >= 70 ? '#2e9e5b' : '#f0a500'; });

    new Chart(cv, {
      type: 'doughnut',
      data: { labels: skills, datasets: [{ data: scores, backgroundColor: colors, borderWidth: 3, borderColor: '#fff' }] },
      options: {
        responsive: true, maintainAspectRatio: true, cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ' ' + ctx.label + ': ' + ctx.raw + '/100';
              }
            }
          }
        }
      }
    });

    var legend = document.getElementById('skillLegend');
    if (legend) {
      legend.innerHTML = skills.map(function (s, i) {
        return '<span class="cl-item"><span class="cl-dot" style="background:' + colors[i] + ';"></span>' + s +
               ' <strong>' + scores[i] + '%</strong></span>';
      }).join('');
    }

    // decorate existing score tiles with mini bar colours
    document.querySelectorAll('.skill-tile .st-bar > span').forEach(function (bar) {
      bar.style.background = bar.style.background || barColors[0];
    });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSkillChart);
    } else {
      initSkillChart();
    }
  })();
</script>

<script src="assets/js/guard.js"></script>
<script>
  // ================= ANTI-CHEAT GUARD (Skill Assessment) =================
  (function () {
    var form = document.getElementById('skillForm');
    if (!form) return;
    var intro = document.getElementById('saIntro');
    var lock = document.getElementById('saLock');
    var body = document.getElementById('saBody');
    var timerEl = document.getElementById('saTimer');
    var agree = document.getElementById('saAgree');
    var startBtn = document.getElementById('saStartBtn');
    var submitBtn = document.getElementById('saSubmitBtn');

    var DURATION = 10 * 60; // 10 minutes fixed for skill assessment
    var timerId = null;
    var timerDeadline = 0;
    var timerTick = null;
    var guard = null;
    var allowNav = false;

    agree.addEventListener('change', function () {
      startBtn.disabled = !agree.checked;
    });

    function startTest() {
      intro.classList.remove('active');
      lock.classList.add('active');
      if (body && form) body.appendChild(form);
      SeekGuard.enterFullscreen();
      startTimer();
      guard = SeekGuard.start({
        maxViolations: 2,
        onContinue: function () {
          if (window.SeekGuard) SeekGuard.enterFullscreen();
          window.focus();
        },
        onFullscreenExit: function () {
          if (window.SeekGuard) SeekGuard.enterFullscreen();
        },
        onAutoSubmit: function () { submitTest(true); }
      });
    }

    // deadline-based countdown — does NOT pause in background tabs
    function startTimer() {
      timerDeadline = Date.now() + DURATION * 1000;
      timerTick = function () {
        var rem = Math.max(0, Math.round((timerDeadline - Date.now()) / 1000));
        timerEl.textContent = SeekGuard.secondsToText(rem);
        if (rem <= 60) timerEl.classList.add('low');
        if (rem <= 0) {
          clearInterval(timerId); timerId = null;
          submitTest(true);
        }
      };
      timerTick();
      timerId = setInterval(timerTick, 500);
    }
    document.addEventListener('visibilitychange', function () {
      if (lock.classList.contains('active') && timerTick) timerTick();
    });
    window.addEventListener('focus', function () {
      if (lock.classList.contains('active') && timerTick) timerTick();
    });

    function submitTest(auto) {
      if (timerId) { clearInterval(timerId); timerId = null; }
      if (guard) guard.stop();
      timerTick = null;
      SeekGuard.exitFullscreen();
      allowNav = true; // our own submission must NOT be blocked by beforeunload
      form.submit(); // bypasses HTML5 required validation so unanswered Qs still submit
    }

    startBtn.addEventListener('click', startTest);
    submitBtn.addEventListener('click', function () { submitTest(false); });
    window.addEventListener('beforeunload', function (e) {
      if (!allowNav && lock.classList.contains('active')) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  })();
</script>

<?php require 'includes/page_footer.php'; ?>