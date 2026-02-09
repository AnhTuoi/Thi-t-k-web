<?php
session_start();
require "connect.php";

if (!isset($_SESSION['user'])) {
    echo "NOT_LOGIN";
    exit;
}

$nguoidung_id = $_SESSION['user']['nguoidung_id'];
$tongtien = $_POST['tongtien'];
$giohang = json_decode($_POST['giohang'], true);

$conn->query("
    INSERT INTO DONHANG (nguoidung_id, tong_tien, trang_thai)
    VALUES ($nguoidung_id, $tongtien, 'cho_xu_ly')
");

$donhang_id = $conn->insert_id;

foreach ($giohang as $item) {
    $conn->query("
        INSERT INTO CHITIETDONHANG (donhang_id, monan_id, so_luong, don_gia)
        VALUES ($donhang_id, {$item['id']}, {$item['qty']}, {$item['price']})
    ");
}

echo "ORDER_OK";