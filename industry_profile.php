<?php
// industry_profile.php - Company / About update for industry
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';
$errors = array();

$details = array('company_name'=>'','industry_type'=>'','company_size'=>'','website'=>'','about'=>'');
$st = db_query('SELECT company_name, industry_type, company_size, website, about FROM industry_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = array_merge($details, $dr);
mysqli_stmt_close($st);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_company') {
        $company = trim($_POST['company_name'] ?? '');
        $itype = trim($_POST['industry_type'] ?? '');
        $isize = trim($_POST['company_size'] ?? '');
        $web = trim($_POST['website'] ?? '');
        $about = trim($_POST['about'] ?? '');

        if ($company === '') $errors[] = 'Company name cannot be empty.';

        if (empty($errors)) {
            $st = db_query(
                'UPDATE industry_details SET company_name=?, industry_type=?, company_size=?, website=?, about=? WHERE user_id=?',
                'sssssi', array($company, $itype, $isize, $web, $about, $uid));
            mysqli_stmt_close($st);
            $message = 'Company / About updated successfully.';
            // refresh
            $details['company_name'] = $company; $details['industry_type'] = $itype;
            $details['company_size'] = $isize; $details['website'] = $web; $details['about'] = $about;
        }
    }
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $st = db_query('SELECT password FROM users WHERE id = ?', 'i', array($uid));
        mysqli_stmt_store_result($st);
        mysqli_stmt_bind_result($st, $hash);
        mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);
        if (!password_verify($current, $hash)) $errors[] = 'Current password is incorrect.';
        elseif (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
        elseif ($new !== $confirm) $errors[] = 'New passwords do not match.';
        else {
            $nHash = password_hash($new, PASSWORD_DEFAULT);
            $st2 = db_query('UPDATE users SET password = ? WHERE id = ?', 'si', array($nHash, $uid));
            mysqli_stmt_close($st2);
            $message = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'About / Company';
$activeNav = 'profile';
require 'includes/page_header_industry.php';
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-building me-2 text-primary"></i>Company / About</h6>
        <form method="post" action="industry_profile.php">
          <input type="hidden" name="action" value="update_company">
          <div class="mb-3">
            <label class="form-label" for="ip-company">Company Name</label>
            <input type="text" class="form-control" name="company_name" id="ip-company" value="<?php echo e($details['company_name']); ?>">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="ip-indtype">Industry Type</label>
              <input type="text" class="form-control" name="industry_type" id="ip-indtype" value="<?php echo e($details['industry_type']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="ip-size">Company Size</label>
              <select class="form-select" name="company_size" id="ip-size">
                <option value="1-50" <?php echo $details['company_size']=='1-50'?'selected':''; ?>>1 - 50</option>
                <option value="51-250" <?php echo $details['company_size']=='51-250'?'selected':''; ?>>51 - 250</option>
                <option value="251-1000" <?php echo $details['company_size']=='251-1000'?'selected':''; ?>>251 - 1000</option>
                <option value="1000+" <?php echo $details['company_size']=='1000+'?'selected':''; ?>>1000+</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="ip-web">Website</label>
            <input type="url" class="form-control" name="website" id="ip-web" value="<?php echo e($details['website']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="ip-about">About Company</label>
            <textarea class="form-control" name="about" id="ip-about" rows="4" placeholder="Tell students about your company, culture, and what you look for..."><?php echo e($details['about']); ?></textarea>
          </div>
          <button class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> Save Company Info</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-lock me-2 text-primary"></i>Change Password</h6>
        <form method="post" action="industry_profile.php">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3"><label class="form-label" for="ip-curpass">Current Password</label><input type="password" class="form-control" name="current_password" id="ip-curpass" required></div>
          <div class="mb-3"><label class="form-label" for="ip-newpass">New Password</label><input type="password" class="form-control" name="new_password" id="ip-newpass" required></div>
          <div class="mb-3"><label class="form-label" for="ip-confpass">Confirm New Password</label><input type="password" class="form-control" name="confirm_password" id="ip-confpass" required></div>
          <button class="btn btn-navy w-100"><i class="fa-solid fa-key"></i> Update Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Account</h6>
        <p class="small text-muted-2 mb-3">Signed in as <strong><?php echo e($_SESSION['email']); ?></strong> (Industry)</p>
        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>
