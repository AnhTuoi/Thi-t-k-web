<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . "/../../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['khuyenmai_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu ID khuyến mãi"
    ]);
    exit;
}

$khuyenmai_id = $input['khuyenmai_id']; // Giữ nguyên kiểu dữ liệu
$hinh_anh = trim($input['hinh_anh'] ?? '');
$ma_khuyenmai = trim($input['ma_khuyenmai'] ?? '');
$mo_ta = trim($input['mo_ta'] ?? '');
$loai_giam_gia = trim($input['loai_giam_gia'] ?? '');
$gia_tri_giam = isset($input['gia_tri_giam']) ? floatval($input['gia_tri_giam']) : 0;
$don_hang_toi_thieu = isset($input['don_hang_toi_thieu']) ? floatval($input['don_hang_toi_thieu']) : 0;
$ngay_bat_dau = trim($input['ngay_bat_dau'] ?? '');
$ngay_ket_thuc = trim($input['ngay_ket_thuc'] ?? '');
$trang_thai = trim($input['trang_thai'] ?? 'dang_ap_dung');

if (empty($ma_khuyenmai) || empty($mo_ta) || empty($loai_giam_gia) || empty($ngay_bat_dau) || empty($ngay_ket_thuc)) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu dữ liệu bắt buộc"
    ]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_bat_dau) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_ket_thuc)) {
    echo json_encode([
        "success" => false,
        "message" => "Định dạng ngày không hợp lệ"
    ]);
    exit;
}

if (strtotime($ngay_ket_thuc) < strtotime($ngay_bat_dau)) {
    echo json_encode([
        "success" => false,
        "message" => "Ngày kết thúc phải sau ngày bắt đầu"
    ]);
    exit;
}

// Kiểm tra mã khuyến mãi trùng
$check_sql = "SELECT khuyenmai_id FROM khuyenmai WHERE ma_khuyenmai = ? AND khuyenmai_id != ?";
$check_stmt = $conn->prepare($check_sql);

// Detect kiểu ID (int hay string)
if (is_numeric($khuyenmai_id)) {
    $check_stmt->bind_param("si", $ma_khuyenmai, $khuyenmai_id);
} else {
    $check_stmt->bind_param("ss", $ma_khuyenmai, $khuyenmai_id);
}

$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $check_stmt->close();
    echo json_encode([
        "success" => false,
        "message" => "Mã khuyến mãi đã tồn tại"
    ]);
    exit;
}
$check_stmt->close();

// UPDATE
$sql = "UPDATE khuyenmai SET
            hinh_anh = ?,
            ma_khuyenmai = ?,
            mo_ta = ?,
            loai_giam_gia = ?,
            gia_tri_giam = ?,
            don_hang_toi_thieu = ?,
            ngay_bat_dau = ?,
            ngay_ket_thuc = ?,
            trang_thai = ?
        WHERE khuyenmai_id = ?";

$stmt = $conn->prepare($sql);

// Detect kiểu ID
if (is_numeric($khuyenmai_id) && strpos($khuyenmai_id, '.') === false) {
    // INT
    $stmt->bind_param("ssssddssi", $hinh_anh, $ma_khuyenmai, $mo_ta, $loai_giam_gia, $gia_tri_giam, $don_hang_toi_thieu, $ngay_bat_dau, $ngay_ket_thuc, $trang_thai, $khuyenmai_id);
} else {
    // VARCHAR
    $stmt->bind_param("ssssddssss", $hinh_anh, $ma_khuyenmai, $mo_ta, $loai_giam_gia, $gia_tri_giam, $don_hang_toi_thieu, $ngay_bat_dau, $ngay_ket_thuc, $trang_thai, $khuyenmai_id);
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Cập nhật khuyến mãi thành công"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Lỗi: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>