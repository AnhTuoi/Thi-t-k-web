<?php
session_start();
include "connect.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT * FROM taikhoan WHERE tendangnhap = ? AND matkhau = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['user_id'] = 1;
    echo json_encode(["login" => true]);
} else {
    echo json_encode(["login" => false]);
}