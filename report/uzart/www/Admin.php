<?php
/**
 * Admin 계정 최초 생성 스크립트
 * --------------------------------
 * • http://…/Admin.php 로 1회 호출 후 반드시 삭제/주석!
 */
include_once("../include/_common.php");   // ← 경로 수정 (기존 config.php → _common.php)

$username = "Admin";
$password = password_hash("dnpfzmf2@", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, password) VALUES (:username, :password)"
);
$stmt->bindValue(":username", $username);
$stmt->bindValue(":password", $password);
$stmt->execute();

echo "✅  Admin 계정이 생성되었습니다. (ID: {$conn->lastInsertId()})";
?>
