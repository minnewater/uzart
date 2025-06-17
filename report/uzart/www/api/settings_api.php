<?php
/**
 * settings_api.php
 * 2025-06-16  —  DB 헬퍼(get_db_connection) + CSRF + “role 컬럼 자동감지” 버전
 *
 * 1) 공통 _common.php 로드 → PDO 헬퍼·CSRF 헬퍼·세션 시작 포함
 * 2) 모든 POST 계열 요청에 CSRF 검증
 */

header("Content-Type: application/json;charset=UTF-8");
session_status() === PHP_SESSION_NONE && session_start();

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

$remote_ip = $_SERVER['REMOTE_ADDR'];
$action    = $_GET['action'] ?? '';

if ($action === 'load_users') {
    // 사용자 조회 (user_group과 groups 조인)
    $query = "
        SELECT u.username, u.name, u.position, u.office_phone, u.mobile_phone, u.email, g.group_name
        FROM users u
        LEFT JOIN user_group ug ON u.id = ug.user_id
        LEFT JOIN groups g ON ug.group_id = g.id
        ORDER BY u.username
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["users" => $users]);
} elseif ($action === 'load_groups') {
    // 현재 사용자 그룹 확인
    $currentUserId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT group_id FROM user_group WHERE user_id = :id LIMIT 1");
    $stmt->bindParam(':id', $currentUserId);
    $stmt->execute();
    $currentGroupId = $stmt->fetchColumn();

    // 그룹 목록 조회
    $query = "SELECT id, group_name FROM groups";
    if ($currentGroupId == 1) {
        $query .= " WHERE id IN (1, 2)";
    } elseif ($currentGroupId == 2) {
        $query .= " WHERE id = 2";
    }
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["groups" => $groups]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 사용자 추가
    $username = $_POST['username'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $group_id = $_POST['group_id'] ?? ''; // role 대신 group_id 사용
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $office_phone = $_POST['office_phone'] ?? '';
    $mobile_phone = $_POST['mobile_phone'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($username) || empty($password) || empty($group_id)) {
        echo json_encode(["success" => false, "error" => "필수 입력값 누락"]);
        exit();
    }

    // users 테이블에 추가
    $query = "
        INSERT INTO users (username, password, name, position, office_phone, mobile_phone, email) 
        VALUES (:username, :password, :name, :position, :office_phone, :mobile_phone, :email)
        RETURNING id
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':name' => $name,
        ':position' => $position,
        ':office_phone' => $office_phone,
        ':mobile_phone' => $mobile_phone,
        ':email' => $email
    ]);
    $newUserId = $stmt->fetchColumn();

    // user_group 테이블에 그룹 매핑
    $query = "INSERT INTO user_group (user_id, group_id, assigned_at) 
              VALUES (:user_id, :group_id, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $newUserId,
        ':group_id' => $group_id
    ]);

    echo json_encode(["success" => true]);
    log_message("INFO", "User $username added", "Uzart", "Uzart", $remote_ip, $conn);
} else {
    echo json_encode(["error" => "잘못된 요청"]);
}
?>
