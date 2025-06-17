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
$remote_ip = $_SERVER['REMOTE_ADDR'];
$userId = $_SESSION['username'];

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

$client = $_GET['client'] ?? '';
$server = $_GET['server'] ?? '';

if ($client === '' || $server === '') {
    die("잘못된 요청입니다.");
}

$query = "SELECT api_key FROM api_keys WHERE client = :client AND server_name = :server";
$stmt = $conn->prepare($query);
$stmt->bindParam(':client', $client);
$stmt->bindParam(':server', $server);
$stmt->execute();
$apiKey = $stmt->fetchColumn();

if (empty($apiKey)) {
    die("해당 클라이언트와 서버의 API Key가 없습니다.");
}

$scriptPath = "/data/report/uzart/www/api/uzart_api.sh";
$outputFile = "/tmp/uzart_{$client}_{$server}" . ".sh";
$outputFile2 = "/tmp/uzart_{$client}_{$server}" . ".sh~";

$command = escapeshellcmd("$scriptPath $apiKey $outputFile");
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . basename($outputFile));
    header("Content-Length: " . filesize($outputFile));
    readfile($outputFile);
    unlink($outputFile);
    unlink($outputFile2);
    log_message("INFO", "$userId download API script", $client, $server, $remote_ip, $conn);
    exit;
} else {
    echo json_encode(["success" => false, "error" => "❌ 스크립트 실행 실패"]);
    exit;
}
?>
