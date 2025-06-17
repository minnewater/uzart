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

if ($action === 'load_clients') {
    $client = $_GET['client'] ?? '';
    $server_name = $_GET['server_name'] ?? '';
    $limit = $_GET['limit'] ?? 20;
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $limit;

    $clients = [];
    $total_clients = 0;
    $total_pages = 1;

    $table_name = "api_keys";

    $check_table_query = "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = :table_name)";
    $check_stmt = $conn->prepare($check_table_query);
    $check_stmt->bindValue(':table_name', $table_name);
    $check_stmt->execute();
    $table_exists = $check_stmt->fetchColumn();

    $count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
    $params = [];
    if (!empty($client)) {
        $count_query .= " AND client ILIKE :client";
        $params[':client'] = "%$client%";
    }
    if (!empty($server_name)) {
        $count_query .= " AND server_name ILIKE :server_name";
        $params[':server_name'] = "%$server_name%";
    }
    $stmt_count = $conn->prepare($count_query);
    $stmt_count->execute($params);
    $total_clients = $stmt_count->fetchColumn();

    $total_pages = ($total_clients > 0) ? ceil($total_clients / $limit) : 1;

    $query = "SELECT created_at, client, server_name, LENGTH(api_key) as key_length FROM $table_name WHERE 1=1";
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
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "clients" => $clients,
        "total_pages" => $total_pages,
        "current_page" => $page,
        "total_clients" => $total_clients
    ]);
} elseif ($action === 'get_api_key') {
    $client = $_GET['client'] ?? '';
    $server_name = $_GET['server'] ?? '';

    if (empty($client) || empty($server_name)) {
        echo json_encode(["success" => false, "error" => "필수 입력값 누락"]);
        exit();
    }

    $query = "SELECT api_key FROM api_keys WHERE client = :client AND server_name = :server_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':client', $client);
    $stmt->bindParam(':server_name', $server_name);
    $stmt->execute();
    $api_key = $stmt->fetchColumn();

    if ($api_key) {
        echo json_encode(["success" => true, "api_key" => $api_key]);
    } else {
        echo json_encode(["success" => false, "error" => "API Key를 찾을 수 없습니다."]);
    }
} else {
    echo json_encode(["error" => "잘못된 요청"]);
}
?>
