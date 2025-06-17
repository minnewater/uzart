<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// CSRF 토큰 유지 (로그인 후 새로 생성되었는지 확인)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['dashboardid'])) {
    header("Location: /uzart");
    exit();
}
include_once __DIR__ . "/../include/_common.php";
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대시보드 - Uzart</title>
    <?php if (function_exists('csrf_field')): ?>
        <meta name="csrf-token"
              content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/uzart/www/css/style.css">
    <script src="/uzart/www/js/csrf.js" defer></script>
    <script>
        var csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
        document.cookie = "csrf_token=" + csrfToken;
    </script>
    <!-- jQuery 라이브러리 -->
    <script src="www/js/jquery-3.6.0.min.js"></script>
    <!-- contentLoader.js 호출 (sidebarToggle.js 제거) -->
    <script src="www/js/contentLoader.js"></script>
  <!-- ✅ PDF.js 라이브러리를 먼저 로드 -->
  <script src="/uzart/www/js/pdf.min.js"></script>
  <script src="/uzart/www/js/pdf.worker.min.js"></script> 
</head>
<body>
    <div class="dashboard-container">
        <!-- 좌측 사이드바 -->
        <nav class="sidebar">
            <button id="toggleSidebar" class="toggle-btn">☰</button>
            <div class="logo">
                <a href="/uzart/uzart.php?action=dashboard.view&page=home" class="menu-link">
                    <img src="www/assets/logo.png" alt="Uzart Logo">
                </a>
            </div>
            <ul>
                <li>
                    <a href="/uzart/uzart.php?action=dashboard.view&page=home" class="menu-link">
                        <span class="menu-icon">🏠</span>
                        <span class="menu-text"> 홈</span>
                    </a>
                </li>
                <li>
                    <a href="/uzart/uzart.php?action=dashboard.view&page=reports" class="menu-link">
                        <span class="menu-icon">📊</span>
                        <span class="menu-text"> 보고서</span>
                    </a>
                </li>
                <li>
                    <a href="/uzart/uzart.php?action=dashboard.view&page=clients" class="menu-link">
                        <span class="menu-icon">👤</span>
                        <span class="menu-text"> 고객 관리</span>
                    </a>
                </li>
                <li>
                    <a href="/uzart/uzart.php?action=dashboard.view&page=logs" class="menu-link">
                        <span class="menu-icon">📜</span>
                        <span class="menu-text"> 로그 관리</span>
                    </a>
                </li>
                <li>
                    <a href="/uzart/uzart.php?action=dashboard.view&page=settings" class="menu-link">
                        <span class="menu-icon">⚙️</span>
                        <span class="menu-text"> 설정</span>
                    </a>
                </li>
            </ul>
            <a href="www/logout.php" class="logout-btn">
                <span class="menu-icon">🚪</span>
                <span class="menu-text"> 로그아웃</span>
            </a>
        </nav>
    </div>
    <!-- 메인 컨텐츠 영역 -->
    <div class="content">
        <?php
            $page = $_GET['page'] ?? 'home';
            $allowed_pages = ['home', 'reports', 'settings', 'clients', 'logs'];
            if (!in_array($page, $allowed_pages)) {
                $page = 'home';
            }
            include "pages/$page.php";
        ?>
    </div>

</body>
</html>
