<?php
header("Content-Type: application/json");
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

include_once(__DIR__ . "/../../include/_common.php");

// CSRF 토큰 검증
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if ($csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(["error" => "Invalid CSRF token"]);
    exit();
}

try {
    $conn = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE client = :client LIMIT :limit");
    $stmt->bindParam(":client", $_GET['client'], PDO::PARAM_STR);
    $stmt->bindParam(":limit", $_GET['limit'], PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(["error" => "Database error"]);
}
?>

