<?php
if (isset($_SESSION['user_id']) && isset($_SESSION['dashboardid'])) {
    header("Location: /uzart/uzart.php"); // 로그인 후 이동할 페이지
    exit();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - Uzart</title>
    <link rel="stylesheet" href="uzart/www/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="uzart/www/assets/logo.png" alt="Uzart Logo" class="logo">
            <form action="uzart/www/login.php" method="POST">
                <input type="text" name="username" placeholder="아이디" required>
                <input type="password" name="password" placeholder="비밀번호" required>
                <button type="submit">로그인</button>
            </form>
        </div>
    </div>
</body>
</html>

