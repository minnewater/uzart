<?php
// CLI script to create an admin user
// Usage: php create_admin.php <username> <password>

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once(__DIR__ . '/../include/_common.php');

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];

if ($argc < 3) {
    fwrite(STDERR, "Usage: php create_admin.php <username> <password>\n");
    exit(1);
}

$username = $argv[1];
$passwordHash = password_hash($argv[2], PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, password) VALUES (:username, :password)"
);
$stmt->bindValue(':username', $username);
$stmt->bindValue(':password', $passwordHash);
$stmt->execute();

echo "Admin user created. ID: {$conn->lastInsertId()}\n";
