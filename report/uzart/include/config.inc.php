<?php
// 데이터베이스 연결 정보
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'uzart');
define('DB_USER', 'postgres');
define('DB_PASS', 'dnpfzmf2@');

try {
    // PDO 연결 생성 (PostgreSQL 예시)
    $conn = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // 예외 발생 시 PDOException을 throw하도록 설정
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 기본 페치 모드 설정
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 필요에 따라 연결 문자셋 설정 (PostgreSQL은 별도 설정이 필요 없을 수 있음)
} catch (PDOException $e) {
    // 연결 실패 시 사용자에게 노출되지 않도록 메시지를 기록하고, 예외를 종료합니다.
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed.");
}
