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

// 기본값 설정 ( 오늘 날짜)
$today = date('Y-m-d');

// 필터 기본값
$start_date = $_GET['start_date'] ?? $today; // 기본 날짜 오늘
$end_date = $_GET['end_date'] ?? $today;
$client = $_GET['client'] ?? '';
$server_name = $_GET['server_name'] ?? '';
$message = $_GET['message'] ?? '';
$client_ip = $_GET['client_ip'] ?? '';
$limit = $_GET['limit'] ?? 20; // 나열 개수
$page = $_GET['page'] ?? 1; // 페이지 번호
$offset = ($page - 1) * $limit;

// 날짜 유효성 검사
if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    die("잘못된 시작일자 형식입니다.");
}
if (!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    die("잘못된 종료일자 형식입니다.");
}

$logs = []; // 기본값: 조회 결과 없음
$total_logs = 0;
$total_pages = 1;

// 날짜 범위를 기준으로 테이블 목록 생성
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start, $interval, $end->add($interval));

$tables = [];
foreach ($date_range as $date) {
    $table_name = "uz_auditlog_" . $date->format('Y_m_d');

    // 테이블 존재 여부 확인
    $check_table_query = "
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_name = :table_name
        )
    ";
    $check_stmt = $conn->prepare($check_table_query);
    $check_stmt->bindValue(':table_name', $table_name);
    $check_stmt->execute();
    $table_exists = $check_stmt->fetchColumn();

    if ($table_exists) {
        $tables[] = $table_name;
    }
}

// 전체 개수 가져오기
$query_parts_count = [];
$params = [];

foreach ($tables as $table) {
    $count_query = "SELECT COUNT(*) FROM $table WHERE 1=1";

    if (!empty($client)) { $count_query .= " AND client ILIKE :client"; }
    if (!empty($client_ip)) { $count_query .= " AND client_ip ILIKE :client_ip"; }
    if (!empty($server_name)) { $count_query .= " AND server_name ILIKE :server_name"; }
    if (!empty($message)) { $count_query .= " AND message ILIKE :message"; }

    $query_parts_count[] = $count_query;
}

// 최종 개수 조회
if (!empty($query_parts_count)) {
    $final_count_query = implode(" UNION ALL ", $query_parts_count);
    $stmt_count = $conn->prepare("SELECT SUM(count) FROM (" . $final_count_query . ") AS total_count");
    
    if (!empty($client)) { $stmt_count->bindValue(':client', "%$client%"); }
    if (!empty($client_ip)) { $stmt_count->bindValue(':client_ip', "%$client_ip%"); }
    if (!empty($server_name)) { $stmt_count->bindValue(':server_name', "%$server_name%"); }
    if (!empty($message)) { $stmt_count->bindValue(':message', "%$message%"); }
    
    $stmt_count->execute();
    $total_logs = $stmt_count->fetchColumn();
}

// 총 페이지 개수 계산
$total_pages = ($total_logs > 0) ? ceil($total_logs / $limit) : 1;

// 로그 데이터 생성
$query_parts = [];
$params = [];

foreach ($tables as $table) {
    $subquery = "SELECT * FROM $table WHERE 1=1";

    if (!empty($client)) {
        $subquery .= " AND client ILIKE :client";
        $params[':client'] = '%' . $client . '%';
    }
    if (!empty($client_ip)) {
        $subquery .= " AND client_ip ILIKE :client_ip";
        $params[':client_ip'] = '%' . $client_ip . '%';
    }
    if (!empty($server_name)) {
        $subquery .= " AND server_name ILIKE :server_name";
        $params[':server_name'] = '%' . $server_name . '%';
    }
    if (!empty($message)) {
        $subquery .= " AND message ILIKE :message";
        $params[':message'] = '%' . $message . '%';
    }

    $query_parts[] = $subquery;
}

if (!empty($query_parts)) {
    $query = implode(" UNION ALL ", $query_parts) . " ORDER BY log_time DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// JSON 응답 반환
//echo json_encode($logs);
echo json_encode([
    "logs" => $logs,
    "total_pages" => $total_pages,
    "current_page" => $page,
    "total_logs" => $total_logs
]);

?>
