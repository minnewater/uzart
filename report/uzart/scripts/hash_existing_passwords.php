<?php
/**
 * 일괄 비밀번호 해시 전환 스크립트
 * ------------------------------------------
 * • 기존 users.password 컬럼이 평문/MD5 등일 경우
 *   => BCrypt(PASSWORD_DEFAULT) 로 재해싱
 * • 이미 $2y$ 로 시작하는 BCrypt 해시는 건너뜀
 * • 실행 예: php hash_existing_passwords.php
 *
 * ※ 안전을 위하여 반드시 DB 백업 후 실행하세요!
 */
require_once(__DIR__ . '/../include/_common.php');

$updated = 0;
$skipped = 0;

$stmt = $conn->query("SELECT id, username, password FROM users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = (int)$row['id'];
    $username = $row['username'];
    $plain    = $row['password'];

    // 이미 BCrypt($2y$ / $2a$ / $2b$) 해시라면 스킵
    if (preg_match('/^\$2[aby]\$/', $plain)) {
        $skipped++;
        continue;
    }

    // 평문·기타 해시 → BCrypt 재해싱
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $upd = $conn->prepare("UPDATE users SET password = :pwd WHERE id = :id");
    $upd->execute([':pwd' => $hash, ':id' => $id]);

    printf("[INFO] user %-20s (#%d) → hashed\n", $username, $id);
    $updated++;
}

echo "------------------------------------------\n";
printf("✔ DONE  (updated: %d, skipped: %d)\n", $updated, $skipped);
echo "------------------------------------------\n";

