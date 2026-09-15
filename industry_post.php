<?php
// industry_post.php - Post Internship or Job + optional test creation
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
require_once 'includes/question_bank.php';

// default type from query string
$defaultType = (isset($_GET['type']) && $_GET['type'] === 'job') ? 'job' : 'internship';

$message = '';
$msgType = 'success';
$errors = array();
$posted = false; // the just-created posting id

// skills available for autogenerate
$allSkills = array();
$st = db_query('SELECT skill_name FROM skills ORDER BY skill_name');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $allSkills[] = $row['skill_name'];
mysqli_stmt_close($st);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = ($_POST['type'] ?? 'internship') === 'job' ? 'job' : 'internship';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $required_skills = trim($_POST['required_skills'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $age_limit = trim($_POST['age_limit'] ?? '');
    $no_of_posts = max(1, (int)($_POST['no_of_posts'] ?? 1));
    $duration = trim($_POST['duration'] ?? '');
    $mode = trim($_POST['mode'] ?? '');

    // test options
    $enable_test = isset($_POST['enable_test']) ? 1 : 0;
    $test_title = trim($_POST['test_title'] ?? '');
    $test_method = $_POST['test_method'] ?? 'manual'; // 'manual' | 'auto'
    $auto_skill = trim($_POST['auto_skill'] ?? '');
    $auto_count = min(100, max(1, (int)($_POST['auto_count'] ?? 5)));
    $test_duration = min(180, max(1, (int)($_POST['test_duration'] ?? 10)));

    // validation
    if ($title === '') $errors[] = 'Please enter a title.';
    if (count($errors) === 0) {
        // insert opportunity
        $stmt = db_query(
          'INSERT INTO internships (industry_id, title, type, description, required_skills, salary, age_limit, no_of_posts, duration, mode)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
          'issssssiss',
          array($uid, $title, $type, $description, $required_skills, $salary, $age_limit, $no_of_posts, $duration, $mode)
        );
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $posted = $newId;

        // test creation
        if ($enable_test && $newId) {
            $questions = array();

            if ($test_method === 'auto' && $auto_skill !== '') {
                $picked = get_questions_for_skill($auto_skill, $auto_count);
                foreach ($picked as $q) {
                    $questions[] = array('q' => $q['q'], 'o' => $q['o'], 'a' => $q['a']);
                }
            } else {
                // manual: parse posted question fields (arrays)
                if (isset($_POST['q_text']) && is_array($_POST['q_text'])) {
                    foreach ($_POST['q_text'] as $idx => $qtext) {
                        $qtext = trim($qtext ?? '');
                        if ($qtext === '') continue;
                        $opts = array();
                        $correct = 0;
                        for ($oi = 0; $oi < 4; $oi++) {
                            $opts[] = trim($_POST['opt'][$idx][$oi] ?? '');
                            if (isset($_POST['correct'][$idx]) && (int)$_POST['correct'][$idx] === $oi) {
                                $correct = $oi;
                            }
                        }
                        $questions[] = array('q' => $qtext, 'o' => $opts, 'a' => $correct);
                    }
                }
            }

            $questionsJson = json_encode($questions);
            $qt = db_query('INSERT INTO job_tests (internship_id, title, questions, duration_minutes, max_questions) VALUES (?, ?, ?, ?, ?)',
                           'issii', array($newId, $test_title ?: $title . ' Test', $questionsJson, $test_duration, $auto_count));
            mysqli_stmt_close($qt);
        }

        $message = ($type === 'job' ? 'Job' : 'Internship') . ' posted successfully!';
        $msgType = 'success';
    }
}

$pageTitle = 'Post Internship / Job';
$activeNav = 'post';
require 'includes/page_header_industry.php';

// available skills for selecting answer
function skill_options_from_bank($skill) {
    global $question_bank;
    return isset($question_bank[$skill]) ? $question_bank[$skill] : array();
}
?>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?>
    <?php if ($posted): ?> <a href="industry_applications.php?posting=<?php echo $posted; ?>" class="fw-semibold">View</a><?php endif; ?>
  </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
          <h5 class="card-title mb-0"><i class="fa-solid fa-plus me-2 text-primary"></i>New Posting</h5>
          <div class="btn-group btn-group-sm">
            <a href="industry_post.php" class="btn <?php echo $defaultType==='internship'?'btn-navy active':'btn-outline-secondary'; ?>"><i class="fa-solid fa-rocket"></i> Internship</a>
            <a href="industry_post.php?type=job" class="btn <?php echo $defaultType==='job'?'btn-navy active':'btn-outline-secondary'; ?>"><i class="fa-solid fa-briefcase"></i> Job</a>
          </div>
        </div>

        <form method="post" action="industry_post.php">
          <input type="hidden" name="type" value="<?php echo $defaultType; ?>">

          <div class="mb-3">
            <label class="form-label" for="ipd-title">Title *</label>
            <input type="text" class="form-control" name="title" id="ipd-title" value="<?php echo e($_POST['title'] ?? ''); ?>" placeholder="<?php echo $defaultType==='job' ? 'e.g. Software Engineer' : 'e.g. Software Development Intern'; ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="ipd-desc">Description</label>
            <textarea class="form-control" name="description" id="ipd-desc" rows="3"><?php echo e($_POST['description'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label" for="ipd-skills">Required Skills (comma separated)</label>
            <input type="text" class="form-control" name="required_skills" id="ipd-skills" value="<?php echo e($_POST['required_skills'] ?? ''); ?>" placeholder="e.g. Python, SQL, Machine Learning">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label" for="ipd-salary">Salary / Stipend</label>
              <input type="text" class="form-control" name="salary" id="ipd-salary" value="<?php echo e($_POST['salary'] ?? ''); ?>" placeholder="e.g. ₹20,000/mo or Negotiable">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="ipd-age">Age Limit</label>
              <input type="text" class="form-control" name="age_limit" id="ipd-age" value="<?php echo e($_POST['age_limit'] ?? ''); ?>" placeholder="e.g. 18-25">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="ipd-posts">No. of Posts</label>
              <input type="number" class="form-control" name="no_of_posts" id="ipd-posts" value="<?php echo e($_POST['no_of_posts'] ?? 1); ?>" min="1">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="ipd-duration">Duration</label>
              <input type="text" class="form-control" name="duration" id="ipd-duration" value="<?php echo e($_POST['duration'] ?? ''); ?>" placeholder="e.g. 3 months (internship) / Full-time (job)">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ipd-mode">Mode</label>
              <select class="form-select" name="mode" id="ipd-mode">
                <option value="">Select mode</option>
                <option value="Remote" <?php echo ($_POST['mode']??'')==='Remote'?'selected':''; ?>>Remote</option>
                <option value="Onsite" <?php echo ($_POST['mode']??'')==='Onsite'?'selected':''; ?>>Onsite</option>
                <option value="Hybrid" <?php echo ($_POST['mode']??'')==='Hybrid'?'selected':''; ?>>Hybrid</option>
              </select>
            </div>
          </div>

          <!-- Test options -->
          <div class="border rounded p-3 bg-light mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="enable_test" id="enableTest" value="1" <?php echo isset($_POST['enable_test'])?'checked':''; ?>>
              <label class="form-check-label fw-semibold" for="enableTest"><i class="fa-solid fa-clipboard-question me-1 text-primary"></i> Create a Test for this posting</label>
            </div>

            <div id="testPanel" class="d-none mt-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="ipd-ttitle">Test Title</label>
                  <input type="text" class="form-control" name="test_title" id="ipd-ttitle" value="<?php echo e($_POST['test_title'] ?? ''); ?>" placeholder="e.g. Technical Screening Test">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="ipd-tdur"><i class="fa-regular fa-clock me-1"></i> Duration (minutes)</label>
                  <input type="number" class="form-control" name="test_duration" id="ipd-tdur" value="<?php echo e($_POST['test_duration'] ?? 10); ?>" min="1" max="180">
                </div>
              </div>
              <p class="small text-muted-2 mt-2"><i class="fa-solid fa-stopwatch me-1"></i> The student gets a live countdown timer and the test auto-submits when time runs out.</p>

              <!-- method tabs -->
              <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="test_method" id="mManual" value="manual" <?php echo ($_POST['test_method']??'manual')==='manual'?'checked':''; ?>>
                <label class="btn btn-outline-primary" for="mManual">Manual Questions</label>
                <input type="radio" class="btn-check" name="test_method" id="mAuto" value="auto" <?php echo ($_POST['test_method']??'')==='auto'?'checked':''; ?>>
                <label class="btn btn-outline-primary" for="mAuto">Auto-generate</label>
              </div>

              <!-- auto generate panel -->
              <div id="autoPanel" class="d-none">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label" for="ipd-askill">Pick Skill</label>
                    <select class="form-select" name="auto_skill" id="ipd-askill">
                      <option value="">Select a skill</option>
                      <?php foreach ($allSkills as $sk): ?>
                        <option value="<?php echo e($sk); ?>" <?php echo ($_POST['auto_skill']??'')===$sk?'selected':''; ?>><?php echo e($sk); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ipd-acount">Number of Questions</label>
                    <input type="number" class="form-control" name="auto_count" id="ipd-acount" value="<?php echo e($_POST['auto_count'] ?? 5); ?>" min="1" max="100">
                  </div>
                </div>
                <p class="small text-muted-2 mt-2 mb-0"><i class="fa-solid fa-wand-magic-sparkles"></i> Questions are auto-generated from our verified question bank for the selected skill.</p>
              </div>

              <!-- manual panel -->
              <div id="manualPanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <small class="fw-semibold text-muted-2">Add your own questions (mark the correct answer)</small>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuestionRow()"><i class="fa-solid fa-plus"></i> Add Question</button>
                </div>
                <div id="questionRows"></div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-navy btn-lg w-100"><i class="fa-solid fa-paper-plane"></i> Post <?php echo $defaultType === 'job' ? 'Job' : 'Internship'; ?></button>
        </form>

      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Posting Tips</h6>
        <ul class="small text-muted-2 mb-0 ps-3">
          <li>Mention required skills clearly — students will use these to match.</li>
          <li>Set age limit and no. of posts so candidates know the criteria.</li>
          <li>Enable a test to screen applicants automatically before shortlisting.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  const enableCheck = document.getElementById('enableTest');
  const testPanel = document.getElementById('testPanel');
  const autoPanel = document.getElementById('autoPanel');
  const manualPanel = document.getElementById('manualPanel');
  let qup = 0;

  function toggleTest() {
    testPanel.classList.toggle('d-none', !enableCheck.checked);
  }
  function toggleMethod() {
    const m = document.querySelector('input[name="test_method"]:checked').value;
    autoPanel.classList.toggle('d-none', m !== 'auto');
    manualPanel.classList.toggle('d-none', m !== 'manual');
  }
  function addQuestionRow() {
    qup++;
    const row = document.createElement('div');
    row.className = 'border rounded p-3 mb-2 bg-white';
    row.innerHTML =
      '<div class="mb-2"><label class="small fw-semibold" for="qt' + qup + '">Question ' + qup + '</label><input type="text" name="q_text[' + (qup-1) + ']" id="qt' + qup + '" class="form-control form-control-sm" placeholder="Enter question"></div>' +
      '<div class="row g-2">' +
        [0,1,2,3].map(function(i){
          return '<div class="col-6"><div class="input-group input-group-sm"><input type="text" name="opt[' + (qup-1) + '][' + i + ']" class="form-control" placeholder="Option ' + (i+1) + '"><input class="btn-check" type="radio" name="correct[' + (qup-1) + ']" id="c' + qup + '_' + i + '" value="' + i + '"' + (i===0?' checked':'') + '><label class="btn btn-outline-success" for="c' + qup + '_' + i + '" title="Correct">✓</label></div></div>';
        }).join('') +
      '</div>';
    document.getElementById('questionRows').appendChild(row);
  }

  enableCheck.addEventListener('change', toggleTest);
  document.querySelectorAll('input[name="test_method"]').forEach(r => r.addEventListener('change', toggleMethod));
  addQuestionRow();
  toggleMethod();
  <?php if (isset($_POST['enable_test'])): ?>toggleTest();<?php endif; ?>
</script>

<?php require 'includes/page_footer_role.php'; ?>
