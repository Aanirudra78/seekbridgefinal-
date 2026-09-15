<?php
// db_connect.php - Central database connection + shared helpers
// ============================================================
// PRODUCTION: InfinityFree
//   Host : sql209.infinityfree.com
//   User : if0_42874072
//   DB   : if0_42874072_seekbridge1
// ============================================================

session_start();

// ---- Database credentials (LOCAL - XAMPP) ----
// Yahan apne DB ke credentials daale jate hain.
// GitHub repo ke liye default LOCAL (XAMPP) credentials use kiye hain.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'if0_42787265_technova66');

// ---- Database credentials (PRODUCTION - InfinityFree) ----
// Deploy se pehle neeche wala block activate karein (apna production
// password yahan daalein):
// define('DB_HOST', 'sql209.infinityfree.com');
// define('DB_USER', 'if0_42874072');
// define('DB_PASS', 'YOUR_PRODUCTION_PASSWORD');
// define('DB_NAME', 'if0_42874072_seekbridge1');

// ---- Create connection ----
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// Shared helper functions
// ============================================================

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function redirect_to_dashboard() {
    $map = array(
        'student'      => 'student_dashboard.php',
        'industry'     => 'industry_dashboard.php',
        'academician'  => 'academician_dashboard.php',
        'institution'  => 'institution_dashboard.php',
    );
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if (isset($map[$role])) redirect($map[$role]);
    redirect('login.php');
}

function require_role($need) {
    if (!is_logged_in()) redirect('login.php');
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($role !== $need) redirect_to_dashboard();
}

function db_query($sql, $types = '', $params = array()) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die('DB error: ' . mysqli_error($conn));
    }
    if ($types !== '' && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}