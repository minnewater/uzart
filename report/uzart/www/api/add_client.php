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
$remote_ip = $_SERVER['REMOTE_ADDR']; // 접속 IP

// 데이터베이스 연결 설정
$conn = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$client = $_POST['client'] ?? '';
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

