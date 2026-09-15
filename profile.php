<?php
// profile.php - Edit personal info, change password, consent toggle
require_once 'includes/db_connect.php';
if (!is_logged_in()) { header('Location: login.php'); exit; }

$uid = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$msgType = 'success';
$errors = array();

// ---------- Fetch current details (role-specific) ----------
$details = array('college_name'=>'','course_branch'=>'','year'=>'','consent_share_data'=>0,
                 'phone'=>'','city'=>'','linkedin'=>'','github'=>'','bio'=>'','resume_path'=>'','college_code'=>'',
                 'company_name'=>'','industry_type'=>'','company_size'=>'','website'=>'',
                 'department'=>'','designation'=>'','institution_name'=>'','institution_type'=>'','location'=>'');
switch ($role) {
    case 'student':
        $st = db_query('SELECT college_name, course_branch, year, consent_share_data, phone, city, linkedin, github, bio, resume_path, college_code FROM student_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        $instCode = '';
        break;
    case 'institution':
        $st2 = db_query('SELECT college_code FROM institution_details WHERE user_id = ?', 'i', array($uid));
        $res2 = mysqli_stmt_get_result($st2);
        $dr2 = mysqli_fetch_assoc($res2);
        mysqli_free_result($res2);
        $instCode = $dr2 ? $dr2['college_code'] : '';
        mysqli_stmt_close($st2);
        break;
    case 'industry':
        $st = db_query('SELECT company_name, industry_type, company_size, website FROM industry_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
    case 'academician':
        $st = db_query('SELECT college_name, department, designation FROM academician_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
    case 'institution':
        $st = db_query('SELECT institution_name, institution_type, location, college_code FROM institution_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
}

// ---------- Update profile ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $errors[] = 'Name cannot be empty.';
        }
        if (empty($errors)) {
            $st = db_query('UPDATE users SET name = ? WHERE id = ?', 'si', array($name, $uid));
            mysqli_stmt_close($st);
            $_SESSION['name'] = $name;

            // Update role-specific details
            switch ($role) {
                case 'student':
                    $college = trim($_POST['college_name'] ?? '');
                    if ($college === '') $college = trim($details['college_name'] ?? '');
                    $branch = trim($_POST['course_branch'] ?? '');
                    $year = (int) ($_POST['year'] ?? 0);
                    $consent = isset($_POST['consent_share_data']) ? 1 : 0;
                    $phone = trim($_POST['phone'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $linkedin = trim($_POST['linkedin'] ?? '');
                    $github = trim($_POST['github'] ?? '');
                    $bio = trim($_POST['bio'] ?? '');

                    // ---- College code verification ----
                    $storedCode = trim($details['college_code'] ?? '');
                    $collegeCode = trim($_POST['college_code'] ?? '');
                    if ($collegeCode !== '' && strtoupper($collegeCode) !== strtoupper($storedCode)) {
                        $stv = db_query('SELECT institution_name, institution_type FROM institution_details WHERE college_code = ? LIMIT 1', 's', array(strtoupper($collegeCode)));
                        $rv = mysqli_stmt_get_result($stv);
                        $cRow = mysqli_fetch_assoc($rv);
                        mysqli_free_result($rv);
                        mysqli_stmt_close($stv);
                        if ($cRow) {
                            $college = $cRow['institution_name'];
                            $collegeCode = strtoupper($collegeCode);
                            $message = 'College verified successfully — linked to ' . $cRow['institution_name'] . '.';
                        } else {
                            $errors[] = 'Invalid college code. Ask your college for a valid code, or leave the field empty to type your college name.';
                            $collegeCode = $storedCode;
                        }
                    } else {
                        $collegeCode = $storedCode;
                    }

                    $st2 = db_query(
                        'UPDATE student_details SET college_name=?, course_branch=?, year=?, consent_share_data=?, phone=?, city=?, linkedin=?, github=?, bio=?, college_code=? WHERE user_id=?',
                        'ssiissssssi', array($college, $branch, $year, $consent, $phone, $city, $linkedin, $github, $bio, $collegeCode, $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'industry':
                    $st2 = db_query(
                        'UPDATE industry_details SET company_name=?, industry_type=?, company_size=?, website=? WHERE user_id=?',
                        'ssssi', array(trim($_POST['company_name'] ?? ''), trim($_POST['industry_type'] ?? ''),
                                       trim($_POST['company_size'] ?? ''), trim($_POST['website'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'academician':
                    $st2 = db_query(
                        'UPDATE academician_details SET college_name=?, department=?, designation=? WHERE user_id=?',
                        'sssi', array(trim($_POST['college_name'] ?? ''), trim($_POST['department'] ?? ''),
                                      trim($_POST['designation'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'institution':
                    $st2 = db_query(
                        'UPDATE institution_details SET institution_name=?, institution_type=?, location=? WHERE user_id=?',
                        'sssi', array(trim($_POST['institution_name'] ?? ''), trim($_POST['institution_type'] ?? ''),
                                      trim($_POST['location'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
            }

            // Resume upload (students only)
            if ($role === 'student' && !empty($_FILES['resume_file']['name'])) {
                $allowed = array('pdf','doc','docx','txt');
                $fname = $_FILES['resume_file']['name'];
                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $fsize = $_FILES['resume_file']['size'];
                if (!in_array($ext, $allowed, true)) {
                    $errors[] = 'Resume must be a PDF, DOC, DOCX or TXT file.';
                } elseif ($fsize === false || $fsize > 3 * 1024 * 1024) {
                    $errors[] = 'Resume file size must be under 3 MB.';
                } else {
                    $dir = __DIR__ . '/uploads/resumes';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $save = 'uploads/resumes/' . $uid . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['resume_file']['tmp_name'], __DIR__ . '/' . $save)) {
                        $st = db_query('UPDATE student_details SET resume_path = ? WHERE user_id = ?', 'si', array($save, $uid));
                        mysqli_stmt_close($st);
                        $details['resume_path'] = $save;
                        $message = 'Profile & resume updated successfully.';
                    } else {
                        $errors[] = 'Resume upload failed. Please try again.';
                    }
                }
            }

            if (empty($errors) && $message === '') $message = 'Profile updated successfully.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // verify current password
        $st = db_query('SELECT password FROM users WHERE id = ?', 'i', array($uid));
        mysqli_stmt_store_result($st);
        mysqli_stmt_bind_result($st, $hash);
        mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $nHash = password_hash($new, PASSWORD_DEFAULT);
            $st2 = db_query('UPDATE users SET password = ? WHERE id = ?', 'si', array($nHash, $uid));
            mysqli_stmt_close($st2);
            $message = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'Profile & Settings';
$activeNav = 'profile';

// Use student sidebar for student role, generic sidebar for others
if ($role === 'student') {
    require 'includes/page_header.php';
} else {
    require 'includes/page_header_role.php';
}
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- Personal info -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-user me-2 text-primary"></i>Personal Information</h6>
        <form method="post" action="profile.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_profile">
          <div class="mb-3">
            <label class="form-label" for="pf-name">Full Name</label>
            <input type="text" class="form-control" name="name" id="pf-name" value="<?php echo e($_SESSION['name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="pf-email">Email (cannot be changed)</label>
            <input type="email" class="form-control" id="pf-email" value="<?php echo e($_SESSION['email']); ?>" disabled>
          </div>
          <div class="row g-3 mb-3">
            <?php if ($role === 'student'): ?>
            <div class="col-md-6">
              <label class="form-label" for="pf-college">College / University</label>
              <input type="text" class="form-control" name="college_name" id="pf-college" value="<?php echo e($details['college_name']); ?>" <?php echo $details['college_code'] !== '' ? 'disabled' : ''; ?>>
              <?php if ($details['college_code'] !== ''): ?>
                <div class="form-text text-success"><i class="fa-solid fa-badge-check me-1"></i> College verified via code — name is managed by your college.</div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-collegecode">College Identification Code</label>
              <input type="text" class="form-control" name="college_code" id="pf-collegecode" value="<?php echo e($details['college_code']); ?>" placeholder="e.g. IIITD (ask your college)" maxlength="20" style="text-transform:uppercase;">
              <div class="form-text">Entering your college's unique code auto-links you to it. <a href="#" data-bs-toggle="modal" data-bs-target="#collegeCodeModal">What is this?</a></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-branch">Course / Branch</label>
              <input type="text" class="form-control" name="course_branch" id="pf-branch" value="<?php echo e($details['course_branch']); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="pf-phone">Phone Number</label>
              <input type="tel" class="form-control" name="phone" id="pf-phone" value="<?php echo e($details['phone']); ?>" placeholder="+91 98XXXXXXXX">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="pf-city">City</label>
              <input type="text" class="form-control" name="city" id="pf-city" value="<?php echo e($details['city']); ?>" placeholder="e.g. Delhi">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="pf-linkedin">LinkedIn URL</label>
              <input type="url" class="form-control" name="linkedin" id="pf-linkedin" value="<?php echo e($details['linkedin']); ?>" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-github">GitHub URL</label>
              <input type="url" class="form-control" name="github" id="pf-github" value="<?php echo e($details['github']); ?>" placeholder="https://github.com/...">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-resume">Resume <span class="text-muted">(PDF/DOC, max 3MB)</span></label>
              <input type="file" class="form-control" name="resume_file" id="pf-resume" accept=".pdf,.doc,.docx,.txt">
              <?php if ($details['resume_path']): ?>
                <div class="form-text"><i class="fa-solid fa-file me-1"></i><a href="<?php echo e($details['resume_path']); ?>" target="_blank">View current resume</a></div>
              <?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="pf-bio">Short Bio / About</label>
              <textarea class="form-control" name="bio" id="pf-bio" rows="2" placeholder="A one-liner about you and what you're looking for..."><?php echo e($details['bio']); ?></textarea>
            </div>
            <?php elseif ($role === 'industry'): ?>
            <div class="col-md-6">
              <label class="form-label" for="pf-company">Company Name</label>
              <input type="text" class="form-control" name="company_name" id="pf-company" value="<?php echo e($details['company_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-indtype">Industry Type</label>
              <input type="text" class="form-control" name="industry_type" id="pf-indtype" value="<?php echo e($details['industry_type']); ?>">
            </div>
            <?php elseif ($role === 'academician'): ?>
            <div class="col-md-6">
              <label class="form-label" for="pf-college">College / University</label>
              <input type="text" class="form-control" name="college_name" id="pf-college" value="<?php echo e($details['college_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-dept">Department</label>
              <input type="text" class="form-control" name="department" id="pf-dept" value="<?php echo e($details['department']); ?>">
            </div>
            <?php elseif ($role === 'institution'): ?>
            <div class="col-md-6">
              <label class="form-label" for="pf-instname">Institution Name</label>
              <input type="text" class="form-control" name="institution_name" id="pf-instname" value="<?php echo e($details['institution_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pf-insttype">Institution Type</label>
              <input type="text" class="form-control" name="institution_type" id="pf-insttype" value="<?php echo e($details['institution_type']); ?>">
            </div>
            <?php endif; ?>
          </div>

          <?php if ($role === 'student'): ?>
          <div class="mb-3">
            <label class="form-label" for="pf-year">Year of Study</label>
            <select class="form-select" name="year" id="pf-year">
              <option value="1" <?php echo $details['year']==1?'selected':''; ?>>1st Year</option>
              <option value="2" <?php echo $details['year']==2?'selected':''; ?>>2nd Year</option>
              <option value="3" <?php echo $details['year']==3?'selected':''; ?>>3rd Year</option>
              <option value="4" <?php echo $details['year']==4?'selected':''; ?>>4th Year</option>
              <option value="5" <?php echo $details['year']==5?'selected':''; ?>>5th Year</option>
            </select>
          </div>

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="consent_share_data" id="consentShare" value="1" <?php echo $details['consent_share_data'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="consentShare">
              <i class="fa-solid fa-share-nodes me-1 text-primary"></i> Share my data with institutions
            </label>
            <div class="form-text">Allow institutions to view your skill profile and portfolio for campus-industry collaborations.</div>
          </div>
          <?php elseif ($role === 'industry'): ?>
          <div class="mb-3">
            <label class="form-label" for="pf-size">Company Size</label>
            <select class="form-select" name="company_size" id="pf-size">
              <option value="1-50" <?php echo $details['company_size']=='1-50'?'selected':''; ?>>1 - 50 employees</option>
              <option value="51-250" <?php echo $details['company_size']=='51-250'?'selected':''; ?>>51 - 250 employees</option>
              <option value="251-1000" <?php echo $details['company_size']=='251-1000'?'selected':''; ?>>251 - 1000 employees</option>
              <option value="1000+" <?php echo $details['company_size']=='1000+'?'selected':''; ?>>1000+ employees</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="pf-web">Website <span class="text-muted">(optional)</span></label>
            <input type="url" class="form-control" name="website" id="pf-web" value="<?php echo e($details['website']); ?>">
          </div>
          <?php elseif ($role === 'academician'): ?>
          <div class="mb-3">
            <label class="form-label" for="pf-desig">Designation</label>
            <input type="text" class="form-control" name="designation" id="pf-desig" value="<?php echo e($details['designation']); ?>">
          </div>
          <?php elseif ($role === 'institution'): ?>
          <div class="mb-3">
            <label class="form-label" for="pf-loc">Location</label>
            <input type="text" class="form-control" name="location" id="pf-loc" value="<?php echo e($details['location']); ?>">
          </div>
          <div class="alert alert-success mb-3 d-flex align-items-center gap-3">
            <div class="college-code-pill" id="pfInstCode"><?php echo e($instCode); ?></div>
            <div class="flex-grow-1">
              <div class="fw-semibold small mb-1">Your College Identification Code</div>
              <small class="text-muted-2">Ask your students to enter this code in their profile — they'll get auto-linked to your institution dashboard.</small>
              <div><button type="button" class="btn btn-sm btn-navy mt-2" onclick="copyInstCode()"><i class="fa-solid fa-copy me-1"></i> Copy Code</button></div>
            </div>
          </div>
          <script>
            function copyInstCode() {
              var el = document.getElementById('pfInstCode');
              var ta = document.createElement('textarea');
              ta.value = el.textContent.trim();
              document.body.appendChild(ta);
              ta.select();
              try { document.execCommand('copy'); } catch (e) {}
              document.body.removeChild(ta);
              var b = event.target.closest('button');
              var t = b.innerHTML;
              b.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
              setTimeout(function () { b.innerHTML = t; }, 1500);
            }
          </script>
          <?php endif; ?>

          <button type="submit" class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Change password + account -->
  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-lock me-2 text-primary"></i>Change Password</h6>
        <form method="post" action="profile.php">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label" for="cp-current">Current Password</label>
            <input type="password" class="form-control" name="current_password" id="cp-current" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="cp-new">New Password</label>
            <input type="password" class="form-control" name="new_password" id="cp-new" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="cp-confirm">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" id="cp-confirm" required>
          </div>
          <button type="submit" class="btn btn-navy"><i class="fa-solid fa-key"></i> Update Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3 text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Session</h6>
        <p class="small text-muted-2 mb-3">Signed in as <strong><?php echo e($_SESSION['email']); ?></strong> (<?php echo e(ucfirst($role)); ?>)</p>
        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<?php
if ($role === 'student') {
    require 'includes/page_footer.php';
} else {
    require 'includes/page_footer_role.php';
}
?>

<!-- College code explainer modal -->
<div class="modal fade" id="collegeCodeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="fa-solid fa-id-card-clip me-2 text-primary"></i>College Identification Code</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Registered colleges have a <strong>unique code</strong> that they share with their students.</p>
        <ul class="small mb-3 ps-3">
          <li>Enter your college's code here and it will <strong>auto-verify</strong> that you belong to that college.</li>
          <li>Your college name will be set automatically and cannot be edited while verified.</li>
          <li>Your college can then view its <strong>placement &amp; skill analytics</strong> for verified students.</li>
        </ul>
        <p class="small text-muted-2 mb-0">Ask your college admin for the code. If your college isn't registered, leave the field empty and type your college name manually.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-navy" data-bs-dismiss="modal">Got it</button>
      </div>
    </div>
  </div>
</div>
