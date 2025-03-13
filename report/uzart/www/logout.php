<?php
session_start();

// 모든 세션 변수 초기화
$_SESSION = array();

// 만약 세션 쿠키를 사용 중이면 쿠키도 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 세션 파괴
session_destroy();

// 로그인 페이지(index.php)로 리다이렉트 (상대경로 주의)
header("Location: /uzart");
exit();
?>
