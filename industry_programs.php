<?php
// industry_programs.php - Industry posts FDPs/workshops, consultancy,
// mentorship/guest lectures and research collaboration topics.
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];

$st = db_query('SELECT id FROM industry_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$ind = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($st);
$indId = $ind ? (int) $ind['id'] : 0;

$msg = '';
$err = '';
$act = $_POST['action'] ?? '';

if (!$indId) {
    $err = 'Complete your company profile first (name is required).';
}

if ($act === 'add_fdp' && $indId) {
    $title = trim($_POST['title'] ?? '');
    $ptype = in_array($_POST['program_type'] ?? '', array('fdp','workshop','training')) ? $_POST['program_type'] : 'fdp';
    $desc = trim($_POST['description'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $mode = in_array($_POST['mode'] ?? '', array('online','offline','hybrid')) ? $_POST['mode'] : 'online';
    $start = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $url = trim($_POST['external_url'] ?? '');
    if ($title === '') {
        $err = 'Program title is required.';
    } else {
        db_query('INSERT INTO fdp_programs (company_id,title,program_type,description,skills,mode,start_date,end_date,external_url) VALUES (?,?,?,?,?,?,?,?,?)',
            'issssssss', array($uid, $title, $ptype, $desc, $skills, $mode, $start, $end, $url));
        $msg = 'Program published successfully.';
    }
}

if ($act === 'add_consult' && $indId) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $exp = trim($_POST['required_expertise'] ?? '');
    $url = trim($_POST['external_url'] ?? '');
    if ($title === '') {
        $err = 'Consultancy title is required.';
    } else {
        db_query('INSERT INTO consultancy_opportunities (industry_id,title,description,required_expertise,external_url) VALUES (?,?,?,?,?)',
            'issss', array($indId, $title, $desc, $exp, $url));
        $msg = 'Consultancy opportunity published.';
    }
}

if ($act === 'add_mentor' && $indId) {
    $title = trim($_POST['title'] ?? '');
    $ptype = in_array($_POST['program_type'] ?? '', array('mentorship','guest_lecture')) ? $_POST['program_type'] : 'mentorship';
    $topic = trim($_POST['topic'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $mode = in_array($_POST['mode'] ?? '', array('online','offline','hybrid')) ? $_POST['mode'] : 'online';
    $date = !empty($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
    if ($title === '') {
        $err = 'Program title is required.';
    } else {
        db_query('INSERT INTO mentorship_programs (industry_id,program_type,title,topic,description,mode,schedule_date) VALUES (?,?,?,?,?,?,?)',
            'issssss', array($indId, $ptype, $title, $topic, $desc, $mode, $date));
        $msg = 'Mentorship / guest lecture published.';
    }
}

if ($act === 'add_research' && $indId) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title === '') {
        $err = 'Research topic title is required.';
    } else {
        db_query('INSERT INTO research_projects (industry_id,academician_id,title,description) VALUES (?,NULL,?,?)',
            'iss', array($indId, $title, $desc));
        $msg = 'Research topic published & open for faculty interest.';
    }
}

if ($act === 'del_fdp') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM fdp_programs WHERE id = ? AND company_id = ?', 'ii', array($id, $uid));
    $msg = 'Program deleted.';
}
if ($act === 'del_consult') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM consultancy_opportunities WHERE id = ? AND industry_id = ?', 'ii', array($id, $indId));
    $msg = 'Consultancy deleted.';
}
if ($act === 'del_mentor') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM mentorship_programs WHERE id = ? AND industry_id = ?', 'ii', array($id, $indId));
    $msg = 'Program deleted.';
}
if ($act === 'del_research') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM research_projects WHERE id = ? AND industry_id = ?', 'ii', array($id, $indId));
    $msg = 'Research topic deleted.';
}

if ($act === 'interest_research') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('INSERT IGNORE INTO interests (target_type,target_id,user_id) VALUES ("research",?,?)', 'ii', array($id, $uid));
    $msg = 'Interest recorded.';
}
if ($act === 'uninterest_research') {
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM interests WHERE target_type="research" AND target_id=? AND user_id=?', 'ii', array($id, $uid));
    $msg = 'Interest withdrawn.';
}

if ($act !== '') {
    header('Location: industry_programs.php?tab=' . urlencode($_GET['tab'] ?? 'fdp') . ($msg ? '&msg=' . urlencode($msg) : '') . ($err ? '&err=' . urlencode($err) : ''));
    exit;
}
$msg = $_GET['msg'] ?? $msg;
$err = $_GET['err'] ?? $err;

$tab = $_GET['tab'] ?? 'fdp';
if (!in_array($tab, array('fdp','consult','mentor','research'))) $tab = 'fdp';

// ---- load my lists ----
$myFdps = array();
$st = db_query('SELECT * FROM fdp_programs WHERE company_id = ? ORDER BY id DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myFdps[] = $r;
mysqli_stmt_close($st);

$myConsults = array();
$st = db_query('SELECT * FROM consultancy_opportunities WHERE industry_id = ? ORDER BY id DESC', 'i', array($indId));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myConsults[] = $r;
mysqli_stmt_close($st);

$myMentors = array();
$st = db_query('SELECT * FROM mentorship_programs WHERE industry_id = ? ORDER BY id DESC', 'i', array($indId));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myMentors[] = $r;
mysqli_stmt_close($st);

$myResearch = array();
$st = db_query('SELECT r.*, u.name AS teacher_name
                FROM research_projects r
                LEFT JOIN academician_details a ON a.id = r.academician_id
                LEFT JOIN users u ON u.id = a.user_id
                WHERE r.industry_id = ? ORDER BY r.id DESC', 'i', array($indId));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myResearch[] = $r;
mysqli_stmt_close($st);

$interestedResearch = array();
$st = db_query('SELECT target_id FROM interests WHERE target_type="research" AND user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $interestedResearch[(int) $r['target_id']] = true;
mysqli_stmt_close($st);

$allResearch = array();
$st = db_query('SELECT r.*,
                       u_teach.name AS teacher_name, u_ind.name AS company_name
                FROM research_projects r
                LEFT JOIN academician_details a ON a.id = r.academician_id
                LEFT JOIN users u_teach ON u_teach.id = a.user_id
                LEFT JOIN industry_details i2 ON i2.id = r.industry_id
                LEFT JOIN users u_ind ON u_ind.id = i2.user_id
                ORDER BY r.id DESC');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $allResearch[] = $r;
mysqli_stmt_close($st);

$programTypeLabels = array('fdp'=>'FDP','workshop'=>'Workshop','training'=>'Training');
$programTypeColors = array('fdp'=>'text-bg-primary','workshop'=>'text-bg-success','training'=>'text-bg-warning');
$mentorTypeLabels = array('mentorship'=>'Mentorship','guest_lecture'=>'Guest Lecture');
$mentorTypeColors = array('mentorship'=>'text-bg-primary','guest_lecture'=>'text-bg-success');

$pageTitle = 'Programs & Collaboration';
$activeNav = 'programs';
require 'includes/page_header_industry.php';
?>

<?php if ($err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-success"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card page-heading mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;"><i class="fa-solid fa-handshake"></i></div>
    <div>
      <h4 class="fw-bold mb-1">Faculty Programs &amp; Collaboration</h4>
      <p class="mb-0 fw-regular">Publish FDPs, consultancy opportunities, mentorship / guest lectures and research topics for faculty.</p>
    </div>
  </div>
</div>

<ul class="nav nav-pills mb-4 flex-wrap gap-2">
  <li class="nav-item"><a class="nav-link <?php echo $tab==='fdp'?'active':''; ?>" href="industry_programs.php?tab=fdp"><i class="fa-solid fa-chalkboard-user me-1"></i> FDPs &amp; Training</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $tab==='consult'?'active':''; ?>" href="industry_programs.php?tab=consult"><i class="fa-solid fa-lightbulb me-1"></i> Consultancy</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $tab==='mentor'?'active':''; ?>" href="industry_programs.php?tab=mentor"><i class="fa-solid fa-people-group me-1"></i> Mentorship / Lectures</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $tab==='research'?'active':''; ?>" href="industry_programs.php?tab=research"><i class="fa-solid fa-flask me-1"></i> Research Topics</a></li>
</ul>

<div class="row g-4">
  <div class="col-lg-5">
    <?php if ($tab === 'fdp'): ?>
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Post an FDP / Workshop / Training</h6>
        <form method="post" action="industry_programs.php?tab=fdp">
          <input type="hidden" name="action" value="add_fdp">
          <div class="mb-3">
            <label class="form-label" for="fdp-title">Program Title *</label>
            <input type="text" class="form-control" name="title" id="fdp-title" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="fdp-type">Type</label>
              <select class="form-select" name="program_type" id="fdp-type">
                <option value="fdp">FDP</option>
                <option value="workshop">Workshop</option>
                <option value="training">Industrial Training</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="fdp-mode">Mode</label>
              <select class="form-select" name="mode" id="fdp-mode">
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="fdp-desc">Description</label>
            <textarea class="form-control" name="description" id="fdp-desc" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="fdp-skills">Skills covered (comma separated)</label>
            <input type="text" class="form-control" name="skills" id="fdp-skills">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="fdp-start">Start date</label>
              <input type="date" class="form-control" name="start_date" id="fdp-start">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="fdp-end">End date</label>
              <input type="date" class="form-control" name="end_date" id="fdp-end">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="fdp-url"><i class="fa-solid fa-link me-1"></i>Registration link (your portal)</label>
            <input type="url" class="form-control" name="external_url" id="fdp-url" placeholder="https://yourcompany.com/register...">
            <div class="form-text">Teachers will be redirected here to register on your portal.</div>
          </div>
          <button class="btn btn-navy w-100"><i class="fa-solid fa-paper-plane me-1"></i> Publish Program</button>
        </form>
      </div>
    </div>
    <?php elseif ($tab === 'consult'): ?>
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Post a Consultancy Opportunity</h6>
        <form method="post" action="industry_programs.php?tab=consult">
          <input type="hidden" name="action" value="add_consult">
          <div class="mb-3">
            <label class="form-label" for="cs-title">Title *</label>
            <input type="text" class="form-control" name="title" id="cs-title" required placeholder="e.g. AI Model Validation Expert">
          </div>
          <div class="mb-3">
            <label class="form-label" for="cs-exp">Required expertise</label>
            <input type="text" class="form-control" name="required_expertise" id="cs-exp" placeholder="e.g. Deep Learning, Statistics">
          </div>
          <div class="mb-3">
            <label class="form-label" for="cs-desc">Description</label>
            <textarea class="form-control" name="description" id="cs-desc" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="cs-url"><i class="fa-solid fa-link me-1"></i>Apply link (optional)</label>
            <input type="url" class="form-control" name="external_url" id="cs-url" placeholder="https://yourcompany.com/consult/...">
          </div>
          <button class="btn btn-navy w-100"><i class="fa-solid fa-paper-plane me-1"></i> Publish Opportunity</button>
        </form>
      </div>
    </div>
    <?php elseif ($tab === 'mentor'): ?>
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Post Mentorship / Guest Lecture Invite</h6>
        <form method="post" action="industry_programs.php?tab=mentor">
          <input type="hidden" name="action" value="add_mentor">
          <div class="mb-3">
            <label class="form-label" for="mn-title">Program Title *</label>
            <input type="text" class="form-control" name="title" id="mn-title" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="mn-type">Type</label>
              <select class="form-select" name="program_type" id="mn-type">
                <option value="mentorship">Mentorship Program</option>
                <option value="guest_lecture">Guest Lecture</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="mn-mode">Mode</label>
              <select class="form-select" name="mode" id="mn-mode">
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="mn-topic">Topic / Area</label>
            <input type="text" class="form-control" name="topic" id="mn-topic" placeholder="e.g. Software Engineering Careers">
          </div>
          <div class="mb-3">
            <label class="form-label" for="mn-desc">Description</label>
            <textarea class="form-control" name="description" id="mn-desc" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="mn-date">Schedule date</label>
            <input type="date" class="form-control" name="schedule_date" id="mn-date">
          </div>
          <button class="btn btn-navy w-100"><i class="fa-solid fa-paper-plane me-1"></i> Publish Program</button>
        </form>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Post a Research Topic</h6>
        <form method="post" action="industry_programs.php?tab=research">
          <input type="hidden" name="action" value="add_research">
          <div class="mb-3">
            <label class="form-label" for="rs-title">Research Topic *</label>
            <input type="text" class="form-control" name="title" id="rs-title" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="rs-desc">Description &amp; what you are looking for</label>
            <textarea class="form-control" name="description" id="rs-desc" rows="4"></textarea>
          </div>
          <button class="btn btn-navy w-100"><i class="fa-solid fa-paper-plane me-1"></i> Publish &amp; Open for Interest</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7">
    <?php if ($tab === 'fdp'): ?>
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>My Programs (<?php echo count($myFdps); ?>)</h6>
      <?php if (count($myFdps) === 0): ?>
        <div class="alert alert-light text-center py-4"><p class="mb-0 text-muted-2">No programs yet.</p></div>
      <?php else: ?>
        <?php foreach ($myFdps as $p): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge <?php echo $programTypeColors[$p['program_type']]; ?> me-1"><?php echo $programTypeLabels[$p['program_type']]; ?></span>
                  <span class="badge text-bg-light border"><?php echo e(ucfirst($p['mode'])); ?></span>
                </div>
                <form method="post" action="industry_programs.php?tab=fdp" onsubmit="return confirm('Delete this program?')">
                  <input type="hidden" name="action" value="del_fdp">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
              <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
              <?php if ($p['start_date']): ?>
                <small class="text-muted-2"><i class="fa-regular fa-calendar me-1"></i><?php echo e(date('d M Y', strtotime($p['start_date']))); ?> <?php if ($p['end_date']): ?>to <?php echo e(date('d M Y', strtotime($p['end_date']))); ?><?php endif; ?></small>
              <?php endif; ?>
              <?php if ($p['skills']): ?><div class="mt-2"><span class="badge bg-navy"><?php echo e($p['skills']); ?></span></div><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php elseif ($tab === 'consult'): ?>
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>My Consultancy Postings (<?php echo count($myConsults); ?>)</h6>
      <?php if (count($myConsults) === 0): ?>
        <div class="alert alert-light text-center py-4"><p class="mb-0 text-muted-2">No consultancy opportunities yet.</p></div>
      <?php else: ?>
        <?php foreach ($myConsults as $p): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
                  <?php if ($p['required_expertise']): ?><span class="badge bg-navy"><?php echo e($p['required_expertise']); ?></span><?php endif; ?>
                </div>
                <form method="post" action="industry_programs.php?tab=consult" onsubmit="return confirm('Delete this posting?')">
                  <input type="hidden" name="action" value="del_consult">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php elseif ($tab === 'mentor'): ?>
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>My Mentorship / Lectures (<?php echo count($myMentors); ?>)</h6>
      <?php if (count($myMentors) === 0): ?>
        <div class="alert alert-light text-center py-4"><p class="mb-0 text-muted-2">No programs yet.</p></div>
      <?php else: ?>
        <?php foreach ($myMentors as $p): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge <?php echo $mentorTypeColors[$p['program_type']]; ?> me-1"><?php echo $mentorTypeLabels[$p['program_type']]; ?></span>
                  <span class="badge text-bg-light border"><?php echo e(ucfirst($p['mode'])); ?></span>
                </div>
                <form method="post" action="industry_programs.php?tab=mentor" onsubmit="return confirm('Delete this program?')">
                  <input type="hidden" name="action" value="del_mentor">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
              <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
              <?php if ($p['topic']): ?><small class="text-muted-2"><i class="fa-solid fa-tag me-1"></i><?php echo e($p['topic']); ?></small><?php endif; ?>
              <?php if ($p['schedule_date']): ?><br><small class="text-muted-2"><i class="fa-regular fa-calendar me-1"></i><?php echo e(date('d M Y', strtotime($p['schedule_date']))); ?></small><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php else: ?>
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>My Research Topics (<?php echo count($myResearch); ?>)</h6>
      <?php if (count($myResearch) === 0): ?>
        <div class="alert alert-light text-center py-4"><p class="mb-0 text-muted-2">No research topics yet.</p></div>
      <?php else: ?>
        <?php foreach ($myResearch as $p): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
                  <?php if ($p['teacher_name']): ?><span class="badge text-bg-success"><i class="fa-solid fa-graduation-cap me-1"></i><?php echo e($p['teacher_name']); ?></span><?php endif; ?>
                </div>
                <form method="post" action="industry_programs.php?tab=research" onsubmit="return confirm('Delete this topic?')">
                  <input type="hidden" name="action" value="del_research">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <hr>
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-flask me-2"></i>Open Research Topics (all)</h6>
      <?php foreach ($allResearch as $p): ?>
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
                <small class="text-muted-2">
                  <?php echo $p['company_name'] ? '<i class="fa-solid fa-building me-1"></i>' . e($p['company_name']) : '<i class="fa-solid fa-graduation-cap me-1"></i>' . e($p['teacher_name'] ?? 'Teacher'); ?>
                </small>
              </div>
              <div class="text-end">
                <?php if ($p['industry_id'] == $indId): ?>
                  <span class="badge text-bg-light border">Your topic</span>
                <?php elseif (isset($interestedResearch[$p['id']])): ?>
                  <form method="post" action="industry_programs.php?tab=research">
                    <input type="hidden" name="action" value="uninterest_research">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-check me-1"></i>Interested</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="industry_programs.php?tab=research">
                    <input type="hidden" name="action" value="interest_research">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    <button class="btn btn-sm btn-navy"><i class="fa-solid fa-hand-pointer me-1"></i>Express Interest</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>