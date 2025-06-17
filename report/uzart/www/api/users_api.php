<?php
header("Content-Type: application/json");
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized access"]);
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

try {
    $conn   = get_db_connection();

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

