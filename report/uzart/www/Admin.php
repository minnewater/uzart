<?php
include_once("../include/config.php");

$username = "Admin";
$password = password_hash("dnpfzmf2@", PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
$stmt->bindValue(":username", $username);
$stmt->bindValue(":password", $password);
$stmt->execute();

echo "Admin 계정이 생성되었습니다.";
?>

