<?php
// test_data.php - Returns a screening test for an internship (JSON).
// Auto-generates + caches questions from the shared question bank if not set.
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Not logged in'));
    exit;
}

header('Content-Type: application/json');

$internshipId = (int) ($_GET['id'] ?? 0);
if ($internshipId <= 0) {
    echo json_encode(array('error' => 'Invalid internship'));
    exit;
}

// 1) Existing stored test?
$st = db_query('SELECT title, duration_minutes, questions FROM job_tests WHERE internship_id = ? LIMIT 1', 'i', array($internshipId));
$res = mysqli_stmt_get_result($st);
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);

$questions = null;
$title = '';
$duration = 10;

if ($row) {
    $title = $row['title'];
    $duration = (int) ($row['duration_minutes'] ?: 10);
    if ($row['questions']) {
        $questions = json_decode($row['questions'], true);
    }
}

// 2) If not stored yet, auto-generate from the internship's required skills
if ($questions === null || !is_array($questions)) {
    $st2 = db_query('SELECT required_skills FROM internships WHERE id = ?', 'i', array($internshipId));
    $res2 = mysqli_stmt_get_result($st2);
    $in = mysqli_fetch_assoc($res2);
    mysqli_free_result($res2);
    mysqli_stmt_close($st2);

    $skills = array_filter(array_map('trim', explode(',', $in['required_skills'] ?? '')));
    $maxQ = (int) ($row['max_questions'] ?? 5);
    if ($maxQ > 100) $maxQ = 100;
    if ($maxQ < 1) $maxQ = 1;

    require_once 'includes/question_bank.php';
    $qs = array();
    $seen = array();
    foreach ($skills as $skill) {
        foreach (get_questions_for_skill($skill, 100) as $q) {
            $key = md5($q['q']);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $qs[] = array('q' => $q['q'], 'o' => $q['o'], 'a' => $q['a']);
            if (count($qs) >= $maxQ) break 2;
        }
    }
    $questions = $qs;
    if ($title === '') $title = 'Screening Test';

    // Cache generated questions
    if ($row) {
        $st3 = db_query('UPDATE job_tests SET questions = ? WHERE internship_id = ?', 'si', array(json_encode($questions), $internshipId));
        mysqli_stmt_close($st3);
    } else {
        $st3 = db_query('INSERT INTO job_tests (internship_id, title, questions, duration_minutes) VALUES (?, ?, ?, ?)', 'issi',
                        array($internshipId, $title, json_encode($questions), $duration));
        mysqli_stmt_close($st3);
    }
}

// 3) Normalize older stored question keys (options/answer -> o/a) and sanitize for client (no answers!)
$normalized = array();
foreach ($questions as $q) {
    if (isset($q['options']) && !isset($q['o'])) $q['o'] = $q['options'];
    if (isset($q['answer']) && !isset($q['a'])) $q['a'] = $q['answer'];
    $normalized[] = $q;
}
$questions = $normalized;

if (count($questions) === 0) {
    echo json_encode(array('test' => false, 'error' => 'No test available'));
    exit;
}

$clean = array();
foreach ($questions as $idx => $q) {
    $clean[] = array('i' => $idx, 'q' => $q['q'], 'o' => $q['o']);
}

echo json_encode(array(
    'test' => true,
    'title' => $title,
    'duration_minutes' => $duration,
    'total' => count($clean),
    'questions' => $clean,
));