<?php
session_start();
include_once("../include/_common.php");
$remote_ip = $_SERVER['REMOTE_ADDR']; // 접속 IP

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 사용자 검증
    //$stmt = $conn->prepare("SELECT id, password, name, position, office_phone, mobile_phone, email FROM users WHERE username = :username");
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = :username");
    $stmt->bindValue(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
	session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];

	// 추가 사용자 정보 세션 저장
	$_SESSION['username'] = $username;
//        $_SESSION['user_name']  = $user['name'];
//        $_SESSION['user_role']  = $user['position'];
//        $_SESSION['user_office'] = $user['office_phone'];
//        $_SESSION['user_phone'] = $user['mobile_phone'];
//        $_SESSION['user_mail']  = $user['email'];
        $_SESSION['dashboardid'] = bin2hex(random_bytes(16));
	log_message("INFO", "$username has login", "Uzart", "Uzart", $remote_ip, $conn); // 로깅
        header("Location: /uzart/uzart.php?action=dashboard.view&page=home"); // 로그인 후 이동할 페이지
        exit();
    } else {
	log_message("ERROR", "$username had failed to login with $password", "Uzart", "Uzart", $remote_ip, $conn);
        echo "<script>alert('아이디 또는 비밀번호가 올바르지 않습니다.'); window.location.href='index.php';</script>";
	exit();
    }
} else {
header("Location: /uzart");
}
?>

