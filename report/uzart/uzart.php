<?php
include_once dirname(__FILE__).'/include/_common.php';

// 인증 확인
if (!isset($_SESSION['user_id']) || !isset($_SESSION['dashboardid'])) {
    header("Location: /uzart");
    exit();
}

// action과 기타 파라미터 확인
$action = $_GET['action'] ?? 'dashboard.view';
//error_log("uzart.php called with action: " + $action); // 서버 로그 추가

// 페이지 라우팅 처리
switch ($action) {
    case 'dashboard.view':
        include "www/dashboard.php";
        break;
    case 'pdfviewer.view':
        include "www/pages/pdfviewer.html";
        break;
    default:
        include "www/dashboard.php"; // 기본 홈 페이지
        break;
}
?>
