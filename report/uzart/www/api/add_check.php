<?php
header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['dashboardid'])) {
    header("Location: /uzart");
    exit();
}

include_once(__DIR__ . "/../../include/_common.php");

/* ── CSRF (POST 전용) ──────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
        exit();
    }
}

/* ── DB 연결  ───────────────────────── */
$conn   = get_db_connection();
$action = $_GET['action'] ?? '';

$ids          = $_POST['ids']          ?? '';
$check_status = $_POST['check_status'] ?? 'true';
$net_err      = $_POST['net_err']      ?? '이상 없음';
$tmp_log      = $_POST['tmp_log']      ?? '이상 없음';
$web_log      = $_POST['web_log']      ?? '해당사항 없음';
$was_log      = $_POST['was_log']      ?? '해당사항 없음';
$db_log       = $_POST['db_log']       ?? '해당사항 없음';
$sys_log      = $_POST['sys_log']      ?? '이상 없음';
$comments     = $_POST['comments']     ?? '';
$user_id      = $_SESSION['user_id'];

$idArr = array_filter(array_map('intval', explode(',', $ids)));
if (empty($idArr)) {
    echo json_encode(["success" => false, "error" => "유효한 보고서 ID가 없습니다."]);
    exit();
}

$query = "INSERT INTO uz_srvdata_check (id, check_status, net_err, tmp_log, web_log, was_log, db_log, sys_log, comments, user_id)
          VALUES (:id, :check_status, :net_err, :tmp_log, :web_log, :was_log, :db_log, :sys_log, :comments, :user_id)
          ON CONFLICT (id) DO UPDATE SET
          check_status = EXCLUDED.check_status, net_err = EXCLUDED.net_err, tmp_log = EXCLUDED.tmp_log,
          web_log = EXCLUDED.web_log, was_log = EXCLUDED.was_log, db_log = EXCLUDED.db_log,
          sys_log = EXCLUDED.sys_log, comments = EXCLUDED.comments";
$stmt = $conn->prepare($query);

foreach ($idArr as $id) {
    $stmt->execute([
        ':id' => $id,
        ':check_status' => $check_status,
        ':net_err' => $net_err,
        ':tmp_log' => $tmp_log,
        ':web_log' => $web_log,
        ':was_log' => $was_log,
        ':db_log' => $db_log,
        ':sys_log' => $sys_log,
	':comments' => $comments,
	':user_id' => $user_id
    ]);
}

echo json_encode(["success" => true]);
?>
