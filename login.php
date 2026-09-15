<?php
// login.php - Two-step login: select role, then email + password
require_once 'includes/db_connect.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$errors = array();
$selectedRole = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedRole = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // validate role
    if (!in_array($selectedRole, array('student','academician','industry','institution'))) {
        $errors[] = 'Please select your account type.';
    }
    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    }

    if (empty($errors)) {
        $stmt = db_query('SELECT id, name, email, password, role FROM users WHERE email = ?', 's', array($email));
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $uname, $uemail, $uhash, $urole);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($found && password_verify($password, $uhash) && $urole === $selectedRole) {
            // success -> set session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $uid;
            $_SESSION['name'] = $uname;
            $_SESSION['email'] = $uemail;
            $_SESSION['role'] = $urole;
            redirect_to_dashboard();
        } elseif ($found && password_verify($password, $uhash) && $urole !== $selectedRole) {
            $errors[] = 'This account is not registered as ' . ucfirst($selectedRole) . '. Please select the correct account type.';
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require 'includes/auth_header.php';
?>

<div class="auth-card-wrap">

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
      <?php foreach ($errors as $err): ?><div><i class="fa-solid fa-circle-exclamation me-1"></i><?php echo e($err); ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="auth-card">
    <div class="card-body">

      <!-- Step 1: Role selection -->
      <div id="roleStep">
        <div class="auth-head mb-4">
          <span class="auth-badge"><i class="fa-solid fa-user-lock"></i> Secure Sign In</span>
          <h3>Welcome back!</h3>
          <p>Step 1 — Tell us who you are</p>
        </div>
        <div class="row g-3">
          <div class="col-6 col-md-3">
            <div class="role-card" data-role="student" onclick="selectRole('student', this)">
              <div class="role-icon"><i class="fa-solid fa-graduation-cap"></i></div>
              <h6>Student</h6>
              <p>Seeking internships</p>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="role-card" data-role="academician" onclick="selectRole('academician', this)">
              <div class="role-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
              <h6>Teacher</h6>
              <p>Faculty &amp; research</p>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="role-card" data-role="industry" onclick="selectRole('industry', this)">
              <div class="role-icon"><i class="fa-solid fa-industry"></i></div>
              <h6>Industry</h6>
              <p>Hiring talent</p>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="role-card" data-role="institution" onclick="selectRole('institution', this)">
              <div class="role-icon"><i class="fa-solid fa-building-columns"></i></div>
              <h6>College</h6>
              <p>Institutions</p>
            </div>
          </div>
        </div>
        <p class="text-center mt-4 mb-0 text-muted-2">
          Don't have an account? <a href="register.php" class="fw-bold">Register here</a>
        </p>
      </div>

      <!-- Step 2: Credentials -->
      <form method="post" action="login.php" id="loginForm" class="d-none" onsubmit="return validateLogin(event)">
        <input type="hidden" name="role" id="selectedRole">
        <div class="auth-head mb-4">
          <span class="auth-badge"><i class="fa-solid fa-key"></i> Step 2 of 2</span>
          <h3>Sign in as <span class="text-accent" id="roleLabel"></span></h3>
          <p>Enter your credentials to continue</p>
        </div>
        <div class="mb-3">
          <label class="form-label" for="loginEmail"><i class="fa-solid fa-envelope me-1"></i> Email Address</label>
          <input type="email" class="form-control form-control-lg" name="email" id="loginEmail" placeholder="you@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="loginPassword"><i class="fa-solid fa-lock me-1"></i> Password</label>
          <input type="password" class="form-control form-control-lg" name="password" id="loginPassword" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-navy btn-lg w-100 mt-2"><i class="fa-solid fa-right-to-bracket me-1"></i> Sign In</button>
        <div class="text-center mt-3">
          <button type="button" class="btn btn-link btn-sm" onclick="backToRole()"><i class="fa-solid fa-arrow-left me-1"></i> Choose a different account type</button>
        </div>
      </form>

    </div>
  </div>

  <!-- Demo accounts box -->
  <div class="auth-card mt-4">
    <div class="card-body py-3 px-4">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="fa-solid fa-circle-info text-primary"></i>
        <span class="fw-bold small text-dark">Demo Accounts</span>
      </div>
      <div class="row small">
        <div class="col-12 col-md-6">
          <div class="d-flex justify-content-between border-bottom py-1 gap-2"><span><i class="fa-solid fa-graduation-cap me-1 text-muted"></i>Student</span><code>student@demo.com / student123</code></div>
          <div class="d-flex justify-content-between border-bottom py-1 gap-2"><span><i class="fa-solid fa-chalkboard-user me-1 text-muted"></i>Teacher</span><code>teacher@demo.com / teacher123</code></div>
        </div>
        <div class="col-12 col-md-6">
          <div class="d-flex justify-content-between border-bottom py-1 gap-2"><span><i class="fa-solid fa-industry me-1 text-muted"></i>Industry</span><code>industry@demo.com / industry123</code></div>
          <div class="d-flex justify-content-between py-1 gap-2"><span><i class="fa-solid fa-building-columns me-1 text-muted"></i>College</span><code>college@demo.com / college123</code></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const roleNames = { student:'Student', academician:'Teacher', industry:'Industry', institution:'College' };
  let chosenRole = '';

  function selectRole(role, el) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    chosenRole = role;
    proceed();
  }

  function proceed() {
    if (!chosenRole || !roleNames[chosenRole]) return;
    document.getElementById('roleStep').classList.add('d-none');
    document.getElementById('loginForm').classList.remove('d-none');
    document.getElementById('selectedRole').value = chosenRole;
    document.getElementById('roleLabel').textContent = roleNames[chosenRole];
  }

  function backToRole() {
    document.getElementById('loginForm').classList.add('d-none');
    document.getElementById('roleStep').classList.remove('d-none');
  }

  function validateLogin(e) {
    if (!chosenRole) {
      e.preventDefault();
      alert('Please select your account type.');
      return false;
    }
    const em = document.getElementById('loginEmail').value.trim();
    const pw = document.getElementById('loginPassword').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
      e.preventDefault();
      alert('Please enter a valid email address.');
      return false;
    }
    if (pw === '') {
      e.preventDefault();
      alert('Please enter your password.');
      return false;
    }
    return true;
  }

  // If a role came back from validation error on POST, jump straight to form
  <?php if ($selectedRole !== ''): ?>
  document.addEventListener('DOMContentLoaded', function(){ proceed(); });
  <?php endif; ?>
</script>

<?php require 'includes/auth_footer.php'; ?>
