<?php
// api/account.php - API xử lý dữ liệu tài khoản
session_start();

// Cấu hình lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/debug.log');

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kết nối database
require_once '../connect.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Xử lý action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_user_profile':
        handleGetUserProfile($conn);
        break;
    case 'update_user_profile':
        handleUpdateUserProfile($conn);
        break;
    case 'change_password':
        handleChangePassword($conn);
        break;
    case 'get_user_stats':
        handleGetUserStats($conn);
        break;
    case 'get_recent_orders':
        handleGetRecentOrders($conn);
        break;
    case 'get_user_orders':
        handleGetUserOrders($conn);
        break;
    case 'update_avatar':
        handleUpdateAvatar($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

// ============ LẤY THÔNG TIN NGƯỜI DÙNG ============
function handleGetUserProfile($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Lấy thông tin người dùng từ cả NGUOIDUNG và TAIKHOAN
        $query = "SELECT 
                    n.nguoidung_id,
                    n.email,
                    n.hoten,
                    n.sodienthoai,
                    n.diachi,
                    n.avatar,
                    n.vai_tro,
                    n.ngay_tao,
                    t.ten_dang_nhap,
                    t.trang_thai as trang_thai_tk
                  FROM NGUOIDUNG n
                  LEFT JOIN TAIKHOAN t ON n.nguoidung_id = t.nguoidung_id
                  WHERE n.nguoidung_id = ?
                  AND n.trang_thai = 'hoat_dong'";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        
        $row = $result->fetch_assoc();
        
        // Định dạng vai trò
        $roleNames = [
            'khach_hang' => 'Khách hàng',
            'nhan_vien' => 'Nhân viên',
            'quan_tri' => 'Quản trị viên'
        ];
        
        $userProfile = [
            'id' => $row['nguoidung_id'],
            'fullName' => $row['hoten'],
            'email' => $row['email'],
            'phone' => $row['sodienthoai'],
            'address' => $row['diachi'],
            'avatar' => $row['avatar'] ?: getDefaultAvatar($row['nguoidung_id']),
            'role' => $row['vai_tro'],
            'roleName' => $roleNames[$row['vai_tro']] ?? 'Khách hàng',
            'username' => $row['ten_dang_nhap'],
            'createdAt' => $row['ngay_tao'],
            'accountStatus' => $row['trang_thai_tk']
        ];
        
        $stmt->close();
        
        echo json_encode(['success' => true, 'data' => $userProfile]);
        
    } catch (Exception $e) {
        error_log("Get User Profile Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thông tin người dùng']);
    }
}

// ============ CẬP NHẬT THÔNG TIN NGƯỜI DÙNG ============
function handleUpdateUserProfile($conn) {
    try {
        // Kiểm tra method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
            return;
        }
        
        // Đọc dữ liệu JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        $userId = $data['user_id'] ?? '';
        $fullName = $data['full_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        
        if (empty($userId) || empty($fullName)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Cập nhật thông tin người dùng
        $query = "UPDATE NGUOIDUNG 
                  SET hoten = ?, 
                      sodienthoai = ?, 
                      diachi = ?
                  WHERE nguoidung_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("ssss", $fullName, $phone, $address, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Lấy thông tin cập nhật
            $selectQuery = "SELECT * FROM NGUOIDUNG WHERE nguoidung_id = ?";
            $selectStmt = $conn->prepare($selectQuery);
            $selectStmt->bind_param("s", $userId);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $updatedUser = $result->fetch_assoc();
            $selectStmt->close();
            
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cập nhật thông tin thành công',
                'data' => [
                    'fullName' => $updatedUser['hoten'],
                    'phone' => $updatedUser['sodienthoai'],
                    'address' => $updatedUser['diachi']
                ]
            ]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Không có thay đổi nào']);
        }
        
    } catch (Exception $e) {
        error_log("Update User Profile Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật thông tin']);
    }
}

// ============ ĐỔI MẬT KHẨU ============
function handleChangePassword($conn) {
    try {
        // Kiểm tra method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
            return;
        }
        
        // Đọc dữ liệu JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        $userId = $data['user_id'] ?? '';
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Kiểm tra mật khẩu mới
        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự']);
            return;
        }
        
        // Lấy mật khẩu hiện tại từ database
        $query = "SELECT mat_khau FROM TAIKHOAN WHERE nguoidung_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại']);
            return;
        }
        
        $row = $result->fetch_assoc();
        $hashedCurrentPassword = $row['mat_khau'];
        
        // Kiểm tra mật khẩu hiện tại (trong thực tế, nên sử dụng password_verify)
        // Ở đây giả sử mật khẩu đã được hash bằng password_hash
        if (!password_verify($currentPassword, $hashedCurrentPassword)) {
            // Nếu không phải hash, kiểm tra trực tiếp (cho demo)
            if ($hashedCurrentPassword !== md5($currentPassword) && $hashedCurrentPassword !== $currentPassword) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
                return;
            }
        }
        
        $stmt->close();
        
        // Hash mật khẩu mới
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu mới
        $updateQuery = "UPDATE TAIKHOAN SET mat_khau = ? WHERE nguoidung_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $hashedNewPassword, $userId);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows > 0) {
            $updateStmt->close();
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
        } else {
            $updateStmt->close();
            echo json_encode(['success' => false, 'message' => 'Không thể đổi mật khẩu']);
        }
        
    } catch (Exception $e) {
        error_log("Change Password Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi đổi mật khẩu']);
    }
}

// ============ LẤY THỐNG KÊ NGƯỜI DÙNG ============
function handleGetUserStats($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Lấy thống kê đơn hàng
        $ordersQuery = "SELECT 
                          COUNT(*) as total_orders,
                          COALESCE(SUM(tong_cuoi_cung), 0) as total_spent,
                          COALESCE(SUM(
                            CASE 
                              WHEN trang_thai_donhang = 'da_giao' THEN 1 
                              ELSE 0 
                            END
                          ), 0) as completed_orders
                        FROM DONHANG 
                        WHERE nguoidung_id = ?";
        
        $stmt = $conn->prepare($ordersQuery);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $totalOrders = intval($row['total_orders'] ?? 0);
        $totalSpent = floatval($row['total_spent'] ?? 0);
        $completedOrders = intval($row['completed_orders'] ?? 0);
        
        $stmt->close();
        
        // Tính điểm tích lũy (giả sử 1 điểm = 10,000đ)
        $points = floor($totalSpent / 10000);
        
        // Xác định hạng thành viên
        $membership = 'Thành viên';
        if ($totalSpent >= 5000000) {
            $membership = 'Khách hàng Vàng';
        } elseif ($totalSpent >= 2000000) {
            $membership = 'Khách hàng Bạc';
        } elseif ($totalSpent >= 500000) {
            $membership = 'Khách hàng Thân thiết';
        }
        
        $userStats = [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'total_spent_formatted' => number_format($totalSpent, 0, ',', '.') . 'đ',
            'completed_orders' => $completedOrders,
            'points' => $points,
            'membership' => $membership,
            'join_date' => date('Y-m-d') // Thực tế nên lấy từ ngày tạo tài khoản
        ];
        
        echo json_encode(['success' => true, 'data' => $userStats]);
        
    } catch (Exception $e) {
        error_log("Get User Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thống kê người dùng']);
    }
}

// ============ LẤY ĐƠN HÀNG GẦN ĐÂY ============
function handleGetRecentOrders($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        $query = "SELECT 
                    d.donhang_id,
                    d.tong_cuoi_cung,
                    d.trang_thai_donhang,
                    d.ngay_tao,
                    d.phuong_thuc_thanhtoan,
                    COUNT(c.monan_id) as item_count
                  FROM DONHANG d
                  LEFT JOIN CHITIETDONHANG c ON d.donhang_id = c.donhang_id
                  WHERE d.nguoidung_id = ?
                  GROUP BY d.donhang_id
                  ORDER BY d.ngay_tao DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("si", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        $statusNames = [
            'cho_xac_nhan' => 'Chờ xác nhận',
            'da_xac_nhan' => 'Đã xác nhận',
            'dang_chuan_bi' => 'Đang chuẩn bị',
            'san_sang' => 'Sẵn sàng',
            'dang_giao' => 'Đang giao hàng',
            'da_giao' => 'Đã giao',
            'da_huy' => 'Đã hủy'
        ];
        
        $statusColors = [
            'cho_xac_nhan' => 'warning',
            'da_xac_nhan' => 'info',
            'dang_chuan_bi' => 'primary',
            'san_sang' => 'success',
            'dang_giao' => 'secondary',
            'da_giao' => 'success',
            'da_huy' => 'danger'
        ];
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['trang_thai_donhang'];
            
            $order = [
                'id' => $row['donhang_id'],
                'total' => floatval($row['tong_cuoi_cung']),
                'total_formatted' => number_format($row['tong_cuoi_cung'], 0, ',', '.') . 'đ',
                'status' => $status,
                'status_name' => $statusNames[$status] ?? $status,
                'status_color' => $statusColors[$status] ?? 'secondary',
                'date' => $row['ngay_tao'],
                'date_formatted' => date('d/m/Y H:i', strtotime($row['ngay_tao'])),
                'payment_method' => $row['phuong_thuc_thanhtoan'],
                'item_count' => intval($row['item_count'])
            ];
            $orders[] = $order;
        }
        
        $stmt->close();
        
        echo json_encode(['success' => true, 'data' => $orders]);
        
    } catch (Exception $e) {
        error_log("Get Recent Orders Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy đơn hàng gần đây']);
    }
}

// ============ LẤY TẤT CẢ ĐƠN HÀNG CỦA NGƯỜI DÙNG ============
function handleGetUserOrders($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $perPage;
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Xây dựng điều kiện WHERE
        $whereClause = "WHERE d.nguoidung_id = ?";
        $params = [$userId];
        $paramTypes = "s";
        
        if (!empty($status)) {
            $whereClause .= " AND d.trang_thai_donhang = ?";
            $params[] = $status;
            $paramTypes .= "s";
        }
        
        // Đếm tổng số đơn hàng
        $countQuery = "SELECT COUNT(*) as total FROM DONHANG d $whereClause";
        $countStmt = $conn->prepare($countQuery);
        if ($countStmt) {
            $countStmt->bind_param($paramTypes, ...$params);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalCount = $countResult->fetch_assoc()['total'] ?? 0;
            $countStmt->close();
        } else {
            $totalCount = 0;
        }
        
        // Lấy danh sách đơn hàng
        $query = "SELECT 
                    d.donhang_id,
                    d.tong_cuoi_cung,
                    d.trang_thai_donhang,
                    d.ngay_tao,
                    d.phuong_thuc_thanhtoan,
                    d.diachi_giaohang,
                    COALESCE(GROUP_CONCAT(DISTINCT m.ten_mon SEPARATOR ', '), '') as items
                  FROM DONHANG d
                  LEFT JOIN CHITIETDONHANG c ON d.donhang_id = c.donhang_id
                  LEFT JOIN MONAN m ON c.monan_id = m.monan_id
                  $whereClause
                  GROUP BY d.donhang_id
                  ORDER BY d.ngay_tao DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $paramTypes .= "ii";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        $statusNames = [
            'cho_xac_nhan' => 'Chờ xác nhận',
            'da_xac_nhan' => 'Đã xác nhận',
            'dang_chuan_bi' => 'Đang chuẩn bị',
            'san_sang' => 'Sẵn sàng',
            'dang_giao' => 'Đang giao hàng',
            'da_giao' => 'Đã giao',
            'da_huy' => 'Đã hủy'
        ];
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['trang_thai_donhang'];
            
            $order = [
                'id' => $row['donhang_id'],
                'total' => floatval($row['tong_cuoi_cung']),
                'total_formatted' => number_format($row['tong_cuoi_cung'], 0, ',', '.') . 'đ',
                'status' => $status,
                'status_name' => $statusNames[$status] ?? $status,
                'date' => $row['ngay_tao'],
                'date_formatted' => date('d/m/Y H:i', strtotime($row['ngay_tao'])),
                'payment_method' => $row['phuong_thuc_thanhtoan'],
                'delivery_address' => $row['diachi_giaohang'],
                'items' => $row['items']
            ];
            $orders[] = $order;
        }
        
        $stmt->close();
        
        $response = [
            'success' => true,
            'data' => $orders,
            'pagination' => [
                'total' => $totalCount,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalCount / $perPage)
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Get User Orders Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy đơn hàng của người dùng']);
    }
}

// ============ CẬP NHẬT AVATAR ============
function handleUpdateAvatar($conn) {
    try {
        // Kiểm tra method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
            return;
        }
        
        // Kiểm tra có file upload không
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Không có file upload hoặc file bị lỗi']);
            return;
        }
        
        $userId = $_POST['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Kiểm tra file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP)']);
            return;
        }
        
        // Kiểm tra file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['avatar']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File quá lớn (tối đa 5MB)']);
            return;
        }
        
        // Tạo tên file mới
        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        
        // Thư mục lưu trữ (tạo nếu chưa tồn tại)
        $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $newFilename;
        
        // Di chuyển file
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
            // Đường dẫn URL để truy cập ảnh (include base path /FoodGo/)
            $avatarUrl = '/FoodGo/uploads/avatars/' . $newFilename;
            
            // Cập nhật database
            $query = "UPDATE NGUOIDUNG SET avatar = ? WHERE nguoidung_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $avatarUrl, $userId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cập nhật ảnh đại diện thành công',
                    'avatar_url' => $avatarUrl
                ]);
            } else {
                $stmt->close();
                // Xóa file đã upload nếu update db thất bại
                unlink($uploadPath);
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật ảnh đại diện']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi upload file']);
        }
        
    } catch (Exception $e) {
        error_log("Update Avatar Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật ảnh đại diện']);
    }
}

// ============ HÀM HỖ TRỢ ============

function getDefaultAvatar($userId) {
    // Tạo avatar mặc định dựa trên user_id
    $colors = ['#f48c25', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6'];
    $color = $colors[abs(crc32($userId)) % count($colors)];
    
    // Trả về URL avatar mặc định hoặc base64
    return "https://ui-avatars.com/api/?name=" . urlencode($userId) . "&background=" . substr($color, 1) . "&color=fff&size=200";
}
?>
