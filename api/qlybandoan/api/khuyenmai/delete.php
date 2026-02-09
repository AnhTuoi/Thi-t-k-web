<?php
require_once("../../config/db.php");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['khuyenmai_id']) || $data['khuyenmai_id'] === "") {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu khuyenmai_id"
    ]);
    exit;
}

$id = $data['khuyenmai_id'];

$sql = "DELETE FROM khuyenmai WHERE khuyenmai_id = ?";
$stmt = mysqli_prepare($conn, $sql);

// Detect kiểu ID
if (is_numeric($id) && strpos($id, '.') === false) {
    mysqli_stmt_bind_param($stmt, "i", $id); // INT
} else {
    mysqli_stmt_bind_param($stmt, "s", $id); // VARCHAR
}

if (mysqli_stmt_execute($stmt)) {
    $affected = mysqli_stmt_affected_rows($stmt);
    
    if ($affected > 0) {
        echo json_encode(["success" => true, "message" => "Xóa thành công"]);
    } else {
        echo json_encode(["success" => false, "message" => "Không tìm thấy khuyến mãi"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Lỗi: " . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>