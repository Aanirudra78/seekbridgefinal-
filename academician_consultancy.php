<?php
// academician_consultancy.php - Consultancy opportunities + Research collaboration.
require_once 'includes/db_connect.php';
require_role('academician');

$uid = (int) $_SESSION['user_id'];

$st = db_query('SELECT id FROM academician_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$ad = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($st);
$acadId = $ad ? (int) $ad['id'] : 0;

$tab = $_GET['tab'] ?? 'consult';
if (!in_array($tab, array('consult','research'))) $tab = 'consult';

// ---- POST handling ----
$act = $_POST['action'] ?? '';
$msg = '';
$err = '';

if ($act === 'interest') {
    $type = $_POST['target_type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if (in_array($type, array('consultancy','research'))) {
        db_query('INSERT IGNORE INTO interests (target_type,target_id,user_id) VALUES (?,?,?)', 'sii', array($type, $id, $uid));
        $msg = 'Interest recorded. The posting owner can now reach out to you.';
    }
}
if ($act === 'uninterest') {
    $type = $_POST['target_type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    db_query('DELETE FROM interests WHERE target_type=? AND target_id=? AND user_id=?', 'sii', array($type, $id, $uid));
    $msg = 'Interest withdrawn.';
}
if ($act === 'post_research') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title === '') {
        $err = 'Research project title is required.';
    } elseif (!$acadId) {
        $err = 'Complete your academician profile first.';
    } else {
        db_query('INSERT INTO research_projects (industry_id,academician_id,title,description) VALUES (NULL,?,?,?)', 'iss', array($acadId, $title, $desc));
        $msg = 'Your research project is published and open to industry interest.';
    }
}

if ($act !== '') {
    header('Location: academician_consultancy.php?tab=' . $tab . ($msg ? '&msg=' . urlencode($msg) : '') . ($err ? '&err=' . urlencode($err) : ''));
    exit;
}
$msg = $_GET['msg'] ?? $msg;
$err = $_GET['err'] ?? $err;

// ---- load data ----
$consultancies = array();
$st = db_query('SELECT c.*, u.name AS company_name
                FROM consultancy_opportunities c
                LEFT JOIN industry_details i ON i.id = c.industry_id
                LEFT JOIN users u ON u.id = i.user_id
                ORDER BY c.id DESC');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $consultancies[] = $r;
mysqli_stmt_close($st);

$research = array();
$st = db_query('SELECT r.*,
                       u_teach.name AS teacher_name, u_ind.name AS company_name
                FROM research_projects r
                LEFT JOIN academician_details a ON a.id = r.academician_id
                LEFT JOIN users u_teach ON u_teach.id = a.user_id
                LEFT JOIN industry_details i2 ON i2.id = r.industry_id
                LEFT JOIN users u_ind ON u_ind.id = i2.user_id
                ORDER BY r.id DESC');
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $research[] = $r;
mysqli_stmt_close($st);

// my interests
$myInterests = array();
$st = db_query('SELECT target_type, target_id FROM interests WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($res)) $myInterests[$r['target_type']][(int) $r['target_id']] = true;
mysqli_stmt_close($st);

$statusLabels = array('open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed');
$statusColors = array('open'=>'text-bg-success','in_progress'=>'text-bg-primary','completed'=>'text-bg-secondary');

function render_interest_form($type, $id, $interested) {
    if ($interested) {
        echo '<form method="post" action="academician_consultancy.php?tab=' . ($type === 'research' ? 'research' : 'consult') . '">';
        echo '<input type="hidden" name="action" value="uninterest">';
        echo '<input type="hidden" name="target_type" value="' . $type . '">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        echo '<button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-check me-1"></i>Interested</button>';
        echo '</form>';
    } else {
        echo '<form method="post" action="academician_consultancy.php?tab=' . ($type === 'research' ? 'research' : 'consult') . '">';
        echo '<input type="hidden" name="action" value="interest">';
        echo '<input type="hidden" name="target_type" value="' . $type . '">';
        echo '<input type="hidden" name="id" value="' . $id . '">';
        echo '<button class="btn btn-sm btn-navy"><i class="fa-solid fa-hand-pointer me-1"></i>Express Interest</button>';
        echo '</form>';
    }
}

$pageTitle = 'Consultancy & Research';
$activeNav = 'consultancy';
require 'includes/page_header_role.php';
?>

<?php if ($err): ?><div class="alert alert-danger"><?php echo e($err); ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-success"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card page-heading mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;"><i class="fa-solid fa-lightbulb"></i></div>
    <div>
      <h4 class="fw-bold mb-1">Consultancy &amp; Research Collaboration</h4>
      <p class="mb-0 fw-regular">Pick up industry consultancy work, or collaborate on joint research projects.</p>
    </div>
  </div>
</div>

<ul class="nav nav-pills mb-4 flex-wrap gap-2">
  <li class="nav-item"><a class="nav-link <?php echo $tab==='consult'?'active':''; ?>" href="academician_consultancy.php?tab=consult"><i class="fa-solid fa-lightbulb me-1"></i> Consultancy Opportunities</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $tab==='research'?'active':''; ?>" href="academician_consultancy.php?tab=research"><i class="fa-solid fa-flask me-1"></i> Research Collaboration</a></li>
</ul>

<?php if ($tab === 'consult'): ?>
  <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>Open Opportunities (<?php echo count($consultancies); ?>)</h6>
  <?php if (count($consultancies) === 0): ?>
    <div class="alert alert-light text-center py-5"><p class="mb-0 text-muted-2">No consultancy opportunities yet.</p></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($consultancies as $c): ?>
        <div class="col-md-6">
          <div class="card card-hover h-100">
            <div class="card-body d-flex flex-column">
              <h6 class="fw-bold mb-1"><?php echo e($c['title']); ?></h6>
              <small class="text-muted-2 mb-2"><i class="fa-solid fa-building me-1"></i><?php echo e($c['company_name'] ?: 'Industry Partner'); ?></small>
              <?php if ($c['description']): ?><p class="text-muted small"><?php echo nl2br(e($c['description'])); ?></p><?php endif; ?>
              <?php if ($c['required_expertise']): ?>
                <div class="mb-2"><span class="badge bg-navy"><i class="fa-solid fa-tag me-1"></i><?php echo e($c['required_expertise']); ?></span></div>
              <?php endif; ?>
              <div class="d-flex flex-wrap gap-2 mt-auto">
                <?php render_interest_form('consultancy', $c['id'], isset($myInterests['consultancy'][$c['id']])); ?>
                <?php if ($c['external_url']): ?>
                  <a href="<?php echo e($c['external_url']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Apply Directly</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="row g-4">
    <div class="col-lg-7">
      <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-list me-2"></i>Research Collaboration (<?php echo count($research); ?>)</h6>
      <?php if (count($research) === 0): ?>
        <div class="alert alert-light text-center py-5"><p class="mb-0 text-muted-2">No research collaborations yet — be the first to post one!</p></div>
      <?php else: ?>
        <?php foreach ($research as $r): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="badge <?php echo $statusColors[$r['status']]; ?>"><?php echo $statusLabels[$r['status']]; ?></span>
                    <?php if ($r['company_name']): ?><span class="badge text-bg-light border"><i class="fa-solid fa-building me-1"></i><?php echo e($r['company_name']); ?></span><?php endif; ?>
                    <?php if ($r['teacher_name']): ?><span class="badge text-bg-light border"><i class="fa-solid fa-graduation-cap me-1"></i><?php echo e($r['teacher_name']); ?></span><?php endif; ?>
                  </div>
                  <h6 class="fw-bold mb-1"><?php echo e($r['title']); ?></h6>
                  <?php if ($r['description']): ?><p class="text-muted small mb-0"><?php echo nl2br(e($r['description'])); ?></p><?php endif; ?>
                </div>
                <div class="text-end flex-shrink-0">
                  <?php $mine = ($r['academician_id'] == $acadId); ?>
                  <?php if ($mine): ?>
                    <span class="badge text-bg-light border">Your project</span>
                  <?php else: ?>
                    <?php render_interest_form('research', $r['id'], isset($myInterests['research'][$r['id']])); ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fa-solid fa-circle-plus me-2 text-primary"></i>Post Your Own Research Project</h6>
          <p class="text-muted small">Publish a topic you want to research with industry — companies can then express interest and reach out.</p>
          <form method="post" action="academician_consultancy.php?tab=research">
            <input type="hidden" name="action" value="post_research">
            <div class="mb-3">
              <label class="form-label" for="rp-title">Project Title *</label>
              <input type="text" class="form-control" name="title" id="rp-title" required placeholder="e.g. AI-assisted STEM assessment">
            </div>
            <div class="mb-3">
              <label class="form-label" for="rp-desc">Description &amp; what you need</label>
              <textarea class="form-control" name="description" id="rp-desc" rows="4" placeholder="Research question, expected outcomes, and the kind of industry partner you seek..."></textarea>
            </div>
            <button class="btn btn-navy w-100"><i class="fa-solid fa-paper-plane me-1"></i> Publish Project</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>