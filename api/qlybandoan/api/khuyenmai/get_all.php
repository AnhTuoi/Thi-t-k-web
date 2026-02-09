<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once("../../config/db.php");

$sql = "SELECT 
            khuyenmai_id,
            ma_khuyenmai,
            mo_ta,
            loai_giam_gia,
            gia_tri_giam,
            don_hang_toi_thieu,
            ngay_bat_dau,
            ngay_ket_thuc,
            trang_thai,
            hinh_anh
        FROM khuyenmai
        ORDER BY khuyenmai_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Lỗi SQL: " . mysqli_error($conn)
    ]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Không ép kiểu, giữ nguyên dữ liệu từ DB
    $data[] = $row;
}

mysqli_free_result($result);
mysqli_close($conn);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>