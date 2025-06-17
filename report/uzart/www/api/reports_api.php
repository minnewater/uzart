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

$action = $_GET['action'] ?? '';
$today = date('Y-m-d');
$start_date = $_GET['start_date'] ?? $today;
$end_date = $_GET['end_date'] ?? $today;

if ($action == "load_clients") {
    try {
        $stmt = $conn->query("SELECT DISTINCT client FROM uz_srvdata ORDER BY client ASC");
        echo json_encode(["clients" => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "클라이언트 조회 오류: " . $e->getMessage()]);
    }
    exit();
} elseif ($action == "load_report") {
    $start_date = $_GET['start_date'] ?? $today;
    $end_date = $_GET['end_date'] ?? $today;
    $client = $_GET['client'] ?? '';
    if (empty($client)) {
        echo json_encode(["error" => "클라이언트 값을 입력하세요."]);
        exit();
    }
    try {
	$query = "SELECT u.*, COALESCE(c.check_status, false) AS check_status FROM uz_srvdata u LEFT JOIN uz_srvdata_check c ON u.id = c.id WHERE u.client = :client  AND DATE(u.created_at) BETWEEN :start_date AND :end_date";

        $params = [
            ":client" => $client,
            ":start_date" => $start_date,
            ":end_date" => $end_date
        ];
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(["error" => "데이터 조회 중 오류 발생: " . $e->getMessage()]);
    }
    exit();
} elseif ($action == "view_report") {
    $report_id = $_GET['id'] ?? '';
    if (!$report_id) {
        echo json_encode(["error" => "잘못된 요청입니다."]);
        exit();
    }
    try {
        $stmt = $conn->prepare("SELECT * FROM uz_srvdata WHERE id = :id");
        $stmt->bindParam(":id", $report_id, PDO::PARAM_INT);
        $stmt->execute();
        $reportData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reportData) {
            echo json_encode(["error" => "보고서 데이터를 찾을 수 없습니다."]);
            exit();
        }
        echo json_encode(["success" => true, "report" => $reportData]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(["error" => "데이터 조회 중 오류 발생: " . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(["error" => "잘못된 요청입니다."]);
    exit();
}
?>
