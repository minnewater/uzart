<?php
session_start();
include_once("../include/_common.php");
$remote_ip = $_SERVER['REMOTE_ADDR']; // 접속 IP

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF 토큰 검증
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        log_message("WARN", "CSRF token mismatch at login", "Uzart", "Uzart", $remote_ip, $conn);
        http_response_code(403);
        die("Invalid request token.");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 사용자 검증
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = :username");
    $stmt->bindValue(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);              // 세션 고정 공격 방지
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['dashboardid'] = bin2hex(random_bytes(16));

        log_message("INFO", "$username has login", "Uzart", "Uzart", $remote_ip, $conn);
        header("Location: /uzart/uzart.php?action=dashboard.view&page=home");
        exit();
    } else {
        log_message("ERROR", "$username had failed to login", "Uzart", "Uzart", $remote_ip, $conn);
        echo "<script>alert('아이디 또는 비밀번호가 올바르지 않습니다.'); window.location.href='index.php';</script>";
        exit();
    }
} else {
    header("Location: /uzart");
    exit();
}

