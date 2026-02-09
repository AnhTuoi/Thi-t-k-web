<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . "/../../config/db.php";

try {
    // Lấy dữ liệu từ request
    $raw_input = file_get_contents("php://input");
    
    error_log("CREATE Request: " . $raw_input);
    
    $data = json_decode($raw_input, true);

    if (!$data) {
        http_response_code(400);
        throw new Exception("Dữ liệu không hợp lệ");
    }

    // Lấy dữ liệu và validate
    $hinh_anh = isset($data['hinh_anh']) ? trim($data['hinh_anh']) : '';
    $ma_khuyenmai = isset($data['ma_khuyenmai']) ? trim($data['ma_khuyenmai']) : '';
    $mo_ta = isset($data['mo_ta']) ? trim($data['mo_ta']) : '';
    $loai_giam_gia = isset($data['loai_giam_gia']) ? trim($data['loai_giam_gia']) : '';
    $gia_tri_giam = isset($data['gia_tri_giam']) ? (float)$data['gia_tri_giam'] : 0;
    $don_hang_toi_thieu = isset($data['don_hang_toi_thieu']) ? (float)$data['don_hang_toi_thieu'] : 0;
    $ngay_bat_dau = isset($data['ngay_bat_dau']) ? trim($data['ngay_bat_dau']) : '';
    $ngay_ket_thuc = isset($data['ngay_ket_thuc']) ? trim($data['ngay_ket_thuc']) : '';
    $trang_thai = isset($data['trang_thai']) ? trim($data['trang_thai']) : 'dang_ap_dung';

    error_log("Parsed: ma_khuyenmai='$ma_khuyenmai', mo_ta='$mo_ta'");

    // Validate bắt buộc
    if (empty($ma_khuyenmai)) {
        http_response_code(400);
        throw new Exception("Mã khuyến mãi không được để trống");
    }
    if (empty($mo_ta)) {
        http_response_code(400);
        throw new Exception("Tên khuyến mãi không được để trống");
    }
    if (empty($loai_giam_gia)) {
        http_response_code(400);
        throw new Exception("Loại giảm giá không được để trống");
    }
    if (empty($ngay_bat_dau)) {
        http_response_code(400);
        throw new Exception("Ngày bắt đầu không được để trống");
    }
    if (empty($ngay_ket_thuc)) {
        http_response_code(400);
        throw new Exception("Ngày kết thúc không được để trống");
    }

    // Kiểm tra mã khuyến mãi trùng
    $check_sql = "SELECT khuyenmai_id FROM khuyenmai WHERE ma_khuyenmai = ?";
    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        error_log("Prepare error: " . $conn->error);
        throw new Exception("Lỗi database: " . $conn->error);
    }

    $check_stmt->bind_param("s", $ma_khuyenmai);
    
    if (!$check_stmt->execute()) {
        error_log("Execute check error: " . $check_stmt->error);
        $check_stmt->close();
        throw new Exception("Lỗi kiểm tra: " . $check_stmt->error);
    }

    $check_result = $check_stmt->get_result();

    if ($check_result && $check_result->num_rows > 0) {
        $check_stmt->close();
        http_response_code(409);
        throw new Exception("Mã khuyến mãi '$ma_khuyenmai' đã tồn tại");
    }
    $check_stmt->close();

    // INSERT
    $insert_sql = "INSERT INTO khuyenmai 
                   (hinh_anh, ma_khuyenmai, mo_ta, loai_giam_gia, gia_tri_giam, don_hang_toi_thieu, ngay_bat_dau, ngay_ket_thuc, trang_thai)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt) {
        error_log("Insert prepare error: " . $conn->error);
        throw new Exception("Lỗi prepare insert: " . $conn->error);
    }

    // Bind param
    if (!$insert_stmt->bind_param(
        "ssssddsss",
        $hinh_anh,
        $ma_khuyenmai,
        $mo_ta,
        $loai_giam_gia,
        $gia_tri_giam,
        $don_hang_toi_thieu,
        $ngay_bat_dau,
        $ngay_ket_thuc,
        $trang_thai
    )) {
        error_log("Bind param error: " . $insert_stmt->error);
        $insert_stmt->close();
        throw new Exception("Lỗi bind_param: " . $insert_stmt->error);
    }

    error_log("Executing insert with: ma_khuyenmai=$ma_khuyenmai");

    // Execute
    if (!$insert_stmt->execute()) {
        error_log("Execute insert error: " . $insert_stmt->error);
        $error_msg = $insert_stmt->error;
        $insert_stmt->close();
        throw new Exception("Lỗi execute insert: " . $error_msg);
    }

    // ✅ FIX: Lấy insert_id TRƯỚC khi close statement
    $insert_id = $conn->insert_id;
    
    // Đóng statement
    $insert_stmt->close();

    error_log("SUCCESS: ID=$insert_id, ma_khuyenmai=$ma_khuyenmai");

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Thêm khuyến mãi thành công",
        "khuyenmai_id" => $insert_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // ✅ FIX: Chỉ close connection ở cuối (trong finally block)
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>