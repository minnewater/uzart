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

// 데이터베이스 연결 설정
$conn = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 필터 기본값
$client = $_GET['client'] ?? '';
$server_name = $_GET['server_name'] ?? '';
$limit = $_GET['limit'] ?? 20; // 나열 개수
$page = $_GET['page'] ?? 1; // 페이지 번호
$offset = ($page - 1) * $limit;

$users = []; // 기본값: 조회 결과 없음
$total_users = 0;
$total_pages = 1;

$table_name = "api_keys";

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


// 전체 개수 가져오기
$params = [];
$count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";

if (!empty($client)) { $count_query .= " AND client ILIKE :client"; }
if (!empty($server_name)) { $count_query .= " AND server_name ILIKE :server_name"; }

$stmt_count = $conn->prepare($count_query);
$stmt_count->execute();
$total_users = $stmt_count->fetchColumn();

// 총 페이지 개수 계산
$total_pages = ($total_users > 0) ? ceil($total_users / $limit) : 1;

// API Key 데이터 가져오기
$query = "SELECT created_at, client, server_name, api_key FROM $table_name WHERE 1=1";

if (!empty($client)) {
    $query .= " AND client ILIKE :client";
}
if (!empty($server_name)) {
    $query .= " AND server_name ILIKE :server_name";
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);

if (!empty($client)) {
    $stmt->bindValue(':client', "%$client%");
}
if (!empty($server_name)) {
    $stmt->bindValue(':server_name', "%$server_name%");
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON 응답 반환
echo json_encode([
    "users" => $users,
    "total_pages" => $total_pages,
    "current_page" => $page,
    "total_users" => $total_users
]);
?>
