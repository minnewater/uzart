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

$remote_ip = $_SERVER['REMOTE_ADDR'];

$client      = $_POST['client']      ?? '';
$server_name = $_POST['server_name'] ?? '';

if ($client === '' || $server_name === '') {
    echo json_encode(["success" => false, "error" => "필수 입력값 누락"]);
    exit();
}

// API Key 생성 및 DB 저장
$query = "INSERT INTO api_keys (api_key, server_name, client) VALUES (gen_random_uuid()::TEXT, :server_name, :client)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':server_name', $server_name);
$stmt->bindParam(':client', $client);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
    log_message("INFO", "Client: $client, Server: $server_name is added", "Uzart", "Uzart", $remote_ip, $conn); // 로깅
} else {
    echo json_encode(["success" => false, "error" => "DB 저장 실패"]);
}
?>

