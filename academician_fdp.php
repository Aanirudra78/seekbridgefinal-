<?php
// academician_fdp.php - FDPs, Workshops & Training posted by industry.
// Our site lists them; registration happens on the company's own portal.
require_once 'includes/db_connect.php';
require_role('academician');

$q = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';

$sql = 'SELECT f.*, u.name AS company_name
        FROM fdp_programs f
        LEFT JOIN users u ON u.id = f.company_id
        WHERE 1=1';
$types = '';
$params = array();
if ($q !== '') {
    $sql .= ' AND (f.title LIKE ? OR f.skills LIKE ? OR u.name LIKE ?)';
    $types .= 'sss';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($typeFilter !== '' && in_array($typeFilter, array('fdp','workshop','training'))) {
    $sql .= ' AND f.program_type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}
$sql .= ' ORDER BY f.start_date IS NULL, f.start_date ASC, f.id DESC';

$st = mysqli_prepare($conn, $sql);
if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$programs = array();
while ($r = mysqli_fetch_assoc($res)) $programs[] = $r;
mysqli_stmt_close($st);

$typeLabels = array('fdp'=>'FDP','workshop'=>'Workshop','training'=>'Industrial Training');
$typeColors = array('fdp'=>'text-bg-primary','workshop'=>'text-bg-success','training'=>'text-bg-warning');

$pageTitle = 'FDPs, Workshops & Training';
$activeNav = 'fdp';
require 'includes/page_header_role.php';
?>

<div class="card page-heading mb-4">
  <div class="card-body d-flex flex-wrap align-items-center gap-3">
    <div class="brand-logo" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#0e2a5c,#1d4ed8);color:#fff;"><i class="fa-solid fa-chalkboard-user"></i></div>
    <div>
      <h4 class="fw-bold mb-1">FDPs, Workshops &amp; Industrial Training</h4>
      <p class="mb-0 fw-regular">Industry publishes faculty development programs here. Registration happens on the company's own portal.</p>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="academician_fdp.php" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label" for="afpSearch"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
        <input type="text" class="form-control" name="q" id="afpSearch" value="<?php echo e($q); ?>" placeholder="Search by title, skill or company">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="afpType">Type</label>
        <select class="form-select" name="type" id="afpType">
          <option value="">All types</option>
          <option value="fdp" <?php echo $typeFilter==='fdp'?'selected':''; ?>>FDP</option>
          <option value="workshop" <?php echo $typeFilter==='workshop'?'selected':''; ?>>Workshop</option>
          <option value="training" <?php echo $typeFilter==='training'?'selected':''; ?>>Industrial Training</option>
        </select>
      </div>
      <div class="col-md-4">
        <button class="btn btn-navy" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i> Filter</button>
        <?php if ($q || $typeFilter): ?><a href="academician_fdp.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (count($programs) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-chalkboard-user" style="font-size:3rem;color:#cbd5e1;"></i>
    <p class="mt-3 mb-0 text-muted-2">No programs found. Check back soon!</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($programs as $p): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge <?php echo $typeColors[$p['program_type']]; ?>"><i class="fa-solid fa-book me-1"></i><?php echo $typeLabels[$p['program_type']]; ?></span>
              <span class="badge text-bg-light border text-muted-2"><?php echo e(ucfirst($p['mode'])); ?></span>
            </div>
            <h6 class="fw-bold mb-1"><?php echo e($p['title']); ?></h6>
            <small class="text-muted-2 mb-2"><i class="fa-solid fa-building me-1"></i><?php echo e($p['company_name'] ?: 'Industry Partner'); ?></small>
            <?php if ($p['description']): ?><p class="text-muted small mb-2"><?php echo nl2br(e(mb_strimwidth($p['description'], 0, 140, '...'))); ?></p><?php endif; ?>
            <div class="d-flex flex-column gap-1 small text-muted-2 mb-2">
              <?php if ($p['start_date']): ?>
                <span><i class="fa-regular fa-calendar me-1"></i><?php echo e(date('d M Y', strtotime($p['start_date']))); ?><?php if ($p['end_date']): ?> &rarr; <?php echo e(date('d M Y', strtotime($p['end_date']))); ?><?php endif; ?></span>
              <?php endif; ?>
              <?php if ($p['skills']): ?><span><i class="fa-solid fa-tag me-1"></i><?php echo e($p['skills']); ?></span><?php endif; ?>
            </div>
            <?php if ($p['external_url']): ?>
              <a href="<?php echo e($p['external_url']); ?>" target="_blank" rel="noopener" class="btn btn-navy mt-auto"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View &amp; Register</a>
            <?php else: ?>
              <span class="btn btn-outline-secondary mt-auto disabled"><i class="fa-solid fa-registered me-1"></i> Registration opens on company portal</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>