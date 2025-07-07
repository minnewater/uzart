<?php
/**
 * _common.php
 * 공통 함수 및 설정 파일
 */

include_once(__DIR__ . "/config.inc.php");

// 개발 환경에서 오류 보고 활성화 (배포 시 false로 변경)
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 세션 시작: 이미 시작되지 않았다면 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    // ──── CSRF 토큰 헬퍼 ─────────────────────────
    // 세션당 토큰 생성
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * 요청에서 전달된 토큰을 검증
     * @param string|null $token
     * @return bool
     */
    function verify_csrf_token(?string $token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * <form> 안에 삽입용 hidden 필드
     * @return string
     */
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' .
               htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, "UTF-8") .
               '">';
    }
    // ────────────────────────────────────────────

/**
 * 입력값을 sanitize하는 함수
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    } else {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * JSON 디코딩 후 오류 체크
 */
function safe_json_decode($json) {
    $result = json_decode($json, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
}

/**
 * DB 핸들러 반환
 */
function get_db_connection() {
    global $conn;
    return $conn;
}

/**
 * 로그 기록 (파일 또는 error_log에 기록)
 *
 * @param string $message
 */
function app_log($message) {
    error_log($message);
    // 추가로 파일 기록 등을 구현할 수 있음.
}


function log_message($level, $message, $client_name, $hn, $ip, $conn) {
    // 날짜 별 로그 파일
    $log_table = 'uz_auditlog_' . date('Y_m_d'); //  테이블 이름을 YYYY-MM-DD.log 형식으로 설정
    $check_log_table_query = "
        SELECT EXISTS (
          SELECT FROM information_schema.tables
          WHERE table_name = :table_name
        )
    ";
    $chkLog_stmt = $conn->prepare($check_log_table_query);
    $chkLog_stmt->bindValue(':table_name', $log_table);
    $chkLog_stmt->execute();
    $log_table_exists = $chkLog_stmt->fetchColumn();

    // 테이블 없으면 생성
    if (!$log_table_exists) {
        $create_log_table_query = "
          CREATE TABLE \"$log_table\" (
            id SERIAL PRIMARY KEY,
            log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            level TEXT NOT NULL,
            message TEXT NOT NULL,
            client TEXT,
            server_name TEXT NOT NULL,
            client_ip TEXT NOT NULL
          )
        ";
        $conn->exec($create_log_table_query);
    }

    $inst_log_query = "
        INSERT INTO \"$log_table\" (level, message, client, server_name, client_ip)
        VALUES (:level, :message, :client_name, :server_name, :client_ip)
    ";
    $inst_log_stmt = $conn->prepare($inst_log_query);
    $inst_log_stmt->bindValue(':level', $level, PDO::PARAM_STR);
    $inst_log_stmt->bindValue(':message', $message, PDO::PARAM_STR);
    $inst_log_stmt->bindValue(':client_name', $client_name, PDO::PARAM_STR);
    $inst_log_stmt->bindValue(':server_name', $hn, PDO::PARAM_STR);
    $inst_log_stmt->bindValue(':client_ip', $ip, PDO::PARAM_STR);
    $inst_log_stmt->execute();
}
