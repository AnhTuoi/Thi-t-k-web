<?php
// api/myaccount.php - API xử lý dữ liệu tài khoản cá nhân của nhân viên/admin
session_start();

// Cấu hình lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/debug.log');

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kết nối database
require_once dirname(__DIR__) . '/config/connect.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Xử lý action
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$input = $input ?? $_POST;

switch ($action) {
    case 'get_user_profile':
        handleGetUserProfile($conn);
        break;
    case 'update_user_profile':
        handleUpdateUserProfile($conn, $input);
        break;
    case 'change_password':
        handleChangePassword($conn, $input);
        break;
    case 'update_avatar':
        handleUpdateAvatar($conn);
        break;
    case 'get_admin_stats':
        handleGetAdminStats($conn);
        break;
    case 'get_user_orders':
        handleGetUserOrders($conn);
        break;
    case 'get_recent_activity':
        handleGetRecentActivity($conn);
        break;
    case 'logout_all_devices':
        handleLogoutAllDevices($conn);
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
        
        $query = "SELECT 
                    nguoidung_id,
                    hoten,
                    email,
                    sodienthoai,
                    ngay_sinh,
                    diachi,
                    avatar,
                    vai_tro,
                    trang_thai,
                    ngay_tao
                  FROM NGUOIDUNG 
                  WHERE nguoidung_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $roleNames = [
                'quan_tri' => 'Quản trị viên',
                'nhan_vien' => 'Nhân viên',
                'khach_hang' => 'Khách hàng'
            ];
            
            $statusNames = [
                'hoat_dong' => 'Đang hoạt động',
                'vo_hieu_hoa' => 'Vô hiệu hóa'
            ];
            
            $user = [
                'id' => $row['nguoidung_id'],
                'fullName' => $row['hoten'],
                'email' => $row['email'],
                'phone' => $row['sodienthoai'],
                'birthday' => $row['ngay_sinh'],
                'address' => $row['diachi'],
                'avatar' => $row['avatar'] ?: getDefaultAvatar(),
                'role' => $row['vai_tro'],
                'roleName' => $roleNames[$row['vai_tro']] ?? $row['vai_tro'],
                'accountStatus' => $row['trang_thai'],
                'statusName' => $statusNames[$row['trang_thai']] ?? $row['trang_thai'],
                'createdAt' => $row['ngay_tao']
            ];
            
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        }
        
    } catch (Exception $e) {
        error_log("Get User Profile Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thông tin người dùng']);
    }
}

// ============ CẬP NHẬT THÔNG TIN NGƯỜI DÙNG ============
function handleUpdateUserProfile($conn, $input) {
    try {
        $userId = $input['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Kiểm tra tồn tại user
        $checkQuery = "SELECT nguoidung_id FROM NGUOIDUNG WHERE nguoidung_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        $checkStmt->close();
        
        // Chuẩn bị dữ liệu cập nhật
        $fields = [];
        $params = [];
        $types = "";
        
        if (isset($input['full_name']) && !empty($input['full_name'])) {
            $fields[] = "hoten = ?";
            $params[] = $input['full_name'];
            $types .= "s";
        }
        
        if (isset($input['phone']) && !empty($input['phone'])) {
            $fields[] = "sodienthoai = ?";
            $params[] = $input['phone'];
            $types .= "s";
        }
        
        if (isset($input['birthday']) && !empty($input['birthday'])) {
            $fields[] = "ngay_sinh = ?";
            $params[] = $input['birthday'];
            $types .= "s";
        }
        
        if (isset($input['address']) && !empty($input['address'])) {
            $fields[] = "diachi = ?";
            $params[] = $input['address'];
            $types .= "s";
        }
        
        // Không có gì để cập nhật
        if (empty($fields)) {
            echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để cập nhật']);
            return;
        }
        
        // Thêm userId vào params cuối cùng
        $params[] = $userId;
        $types .= "s";
        
        $query = "UPDATE NGUOIDUNG SET " . implode(", ", $fields) . " WHERE nguoidung_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Ghi log hoạt động
            logActivity($conn, $userId, 'profile_update', 'Cập nhật thông tin cá nhân');
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
        } else {
            throw new Exception("Update failed");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Update User Profile Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật thông tin người dùng']);
    }
}

// ============ ĐỔI MẬT KHẨU ============
function handleChangePassword($conn, $input) {
    try {
        $userId = $input['user_id'] ?? '';
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Kiểm tra mật khẩu hiện tại từ bảng TAIKHOAN
        $checkQuery = "SELECT mat_khau FROM TAIKHOAN WHERE nguoidung_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        
        $row = $checkResult->fetch_assoc();
        $storedPassword = $row['mat_khau'];
        
        // Kiểm tra mật khẩu (giả sử mật khẩu không mã hóa - trong thực tế nên dùng password_verify)
        if ($currentPassword !== $storedPassword) {
            $checkStmt->close();
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
            return;
        }
        
        $checkStmt->close();
        
        // Kiểm tra độ mạnh mật khẩu mới
        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự']);
            return;
        }
        
        // Cập nhật mật khẩu mới
        $updateQuery = "UPDATE TAIKHOAN SET mat_khau = ? WHERE nguoidung_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        
        if (!$updateStmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $updateStmt->bind_param("ss", $newPassword, $userId);
        
        if ($updateStmt->execute()) {
            // Ghi log thay đổi mật khẩu
            logActivity($conn, $userId, 'change_password', 'Đổi mật khẩu thành công');
            
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
        } else {
            throw new Exception("Password update failed");
        }
        
        $updateStmt->close();
        
    } catch (Exception $e) {
        error_log("Change Password Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi đổi mật khẩu']);
    }
}

// ============ CẬP NHẬT AVATAR ============
function handleUpdateAvatar($conn) {
    try {
        $userId = $_POST['user_id'] ?? '';
        $useDefault = isset($_POST['use_default']) && $_POST['use_default'] == 'true';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Kiểm tra tồn tại user
        $checkQuery = "SELECT nguoidung_id FROM NGUOIDUNG WHERE nguoidung_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        $checkStmt->close();
        
        $avatarUrl = '';
        
        if ($useDefault) {
            // Đặt lại ảnh mặc định
            $avatarUrl = getDefaultAvatar();
        } else {
            // Upload ảnh mới
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                
                // Kiểm tra loại file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP)']);
                    return;
                }
                
                // Kiểm tra kích thước (tối đa 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File quá lớn (tối đa 5MB)']);
                    return;
                }
                
                // Tạo tên file mới
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                $uploadDir = dirname(__DIR__) . '/uploads/avatars/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $uploadPath = $uploadDir . $newFilename;
                
                // Di chuyển file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $avatarUrl = '/FoodGo/uploads/avatars/' . $newFilename;
                } else {
                    throw new Exception("File upload failed");
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Không có file ảnh được tải lên']);
                return;
            }
        }
        
        // Cập nhật database
        $updateQuery = "UPDATE NGUOIDUNG SET avatar = ? WHERE nguoidung_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        
        if (!$updateStmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $updateStmt->bind_param("ss", $avatarUrl, $userId);
        
        if ($updateStmt->execute()) {
            // Ghi log thay đổi avatar
            logActivity($conn, $userId, 'update_avatar', $useDefault ? 'Đặt lại ảnh mặc định' : 'Cập nhật ảnh đại diện');
            
            echo json_encode([
                'success' => true, 
                'message' => $useDefault ? 'Đã đặt lại ảnh mặc định' : 'Cập nhật ảnh đại diện thành công',
                'avatar_url' => $avatarUrl
            ]);
        } else {
            throw new Exception("Avatar update failed");
        }
        
        $updateStmt->close();
        
    } catch (Exception $e) {
        error_log("Update Avatar Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật ảnh đại diện']);
    }
}

// ============ LẤY THỐNG KÊ ADMIN ============
function handleGetAdminStats($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        $today = date('Y-m-d');
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Đơn hàng xử lý hôm nay
        $ordersQuery = "SELECT COUNT(*) as orders_today
                        FROM DONHANG 
                        WHERE DATE(ngay_tao) = ? 
                        AND trang_thai_donhang IN ('da_xac_nhan', 'dang_giao', 'da_giao')";
        
        $ordersStmt = $conn->prepare($ordersQuery);
        $ordersStmt->bind_param("s", $today);
        $ordersStmt->execute();
        $ordersResult = $ordersStmt->get_result();
        $ordersData = $ordersResult->fetch_assoc();
        $ordersStmt->close();
        
        // Tổng số món ăn
        $menuQuery = "SELECT COUNT(*) as menu_items FROM MONAN WHERE trang_thai = 'hoat_dong'";
        $menuResult = $conn->query($menuQuery);
        $menuData = $menuResult->fetch_assoc();
        
        // Tổng số người dùng
        $usersQuery = "SELECT COUNT(*) as total_users FROM NGUOIDUNG WHERE vai_tro = 'khach_hang'";
        $usersResult = $conn->query($usersQuery);
        $usersData = $usersResult->fetch_assoc();
        
        // Doanh thu hôm nay
        $revenueQuery = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as today_revenue
                         FROM DONHANG 
                         WHERE DATE(ngay_tao) = ? 
                         AND trang_thai_donhang = 'da_giao'";
        
        $revenueStmt = $conn->prepare($revenueQuery);
        $revenueStmt->bind_param("s", $today);
        $revenueStmt->execute();
        $revenueResult = $revenueStmt->get_result();
        $revenueData = $revenueResult->fetch_assoc();
        $revenueStmt->close();
        
        // Format revenue
        $revenue = floatval($revenueData['today_revenue'] ?? 0);
        $formattedRevenue = $revenue >= 1000000 ? 
                           number_format($revenue / 1000000, 1) . 'M' : 
                           number_format($revenue / 1000) . 'K';
        
        $stats = [
            'orders_today' => intval($ordersData['orders_today'] ?? 0),
            'menu_items' => intval($menuData['menu_items'] ?? 0),
            'total_users' => intval($usersData['total_users'] ?? 0),
            'today_revenue' => $formattedRevenue
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        error_log("Get Admin Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thống kê admin']);
    }
}

// ============ LẤY HOẠT ĐỘNG GẦN ĐÂY ============
function handleGetRecentActivity($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Lấy các hoạt động từ đánh giá
        $query = "SELECT 
                    'rating' as type,
                    CONCAT('Đã đánh giá món ', ma.ten_mon) as description,
                    dg.ngay_tao as time,
                    CONCAT('Điểm: ', dg.diem_danhgia) as details
                  FROM DANHGIA dg
                  JOIN MONAN ma ON dg.monan_id = ma.monan_id
                  WHERE dg.nguoidung_id = ?
                  UNION ALL
                  SELECT 
                    'order' as type,
                    CONCAT('Đã đặt đơn hàng ', dh.donhang_id) as description,
                    dh.ngay_tao as time,
                    CONCAT('Tổng tiền: ', FORMAT(dh.tong_cuoi_cung, 0), 'đ') as details
                  FROM DONHANG dh
                  WHERE dh.nguoidung_id = ?
                  ORDER BY time DESC
                  LIMIT 10";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("ss", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        
        while ($row = $result->fetch_assoc()) {
            $activity = [
                'type' => $row['type'],
                'description' => $row['description'],
                'time' => $row['time'],
                'time_formatted' => date('H:i d/m/Y', strtotime($row['time'])),
                'details' => $row['details']
            ];
            $activities[] = $activity;
        }
        
        $stmt->close();
        
        // Nếu không có hoạt động, trả về mẫu
        if (empty($activities)) {
            $activities = getSampleActivities($userId);
        }
        
        echo json_encode(['success' => true, 'data' => $activities]);
        
    } catch (Exception $e) {
        error_log("Get Recent Activity Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy hoạt động gần đây']);
    }
}

// ============ ĐĂNG XUẤT KHỎI TẤT CẢ THIẾT BỊ ============
function handleLogoutAllDevices($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        // Cập nhật last login time để logout tất cả sessions
        $updateQuery = "UPDATE TAIKHOAN SET lan_dang_nhap_cuoi = NULL WHERE nguoidung_id = ?";
        $stmt = $conn->prepare($updateQuery);
        
        if ($stmt) {
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Ghi log hoạt động
        logActivity($conn, $userId, 'logout_all', 'Đăng xuất khỏi tất cả thiết bị');
        
        echo json_encode(['success' => true, 'message' => 'Đã đăng xuất khỏi tất cả thiết bị']);
        
    } catch (Exception $e) {
        error_log("Logout All Devices Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi đăng xuất']);
    }
}

// ============ HÀM HỖ TRỢ ============

function getDefaultAvatar() {
    $avatars = [
        'https://api.dicebear.com/7.x/avataaars/svg?seed=User',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=FoodGo',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Admin'
    ];
    return $avatars[array_rand($avatars)];
}

function logActivity($conn, $userId, $type, $description, $details = '') {
    try {
        // Tạm thời chỉ ghi log file
        error_log("Activity - User: $userId, Type: $type, Description: $description, Details: $details");
        
    } catch (Exception $e) {
        error_log("Log Activity Error: " . $e->getMessage());
    }
}

function getSampleActivities($userId) {
    $currentTime = date('Y-m-d H:i:s');
    
    return [
        [
            'type' => 'system',
            'description' => 'Đăng nhập hệ thống quản trị',
            'time_formatted' => date('H:i d/m/Y'),
            'details' => 'Từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Không xác định')
        ],
        [
            'type' => 'profile_update',
            'description' => 'Cập nhật thông tin cá nhân',
            'time_formatted' => date('H:i d/m/Y', strtotime('-1 day')),
            'details' => 'Thông tin đã được cập nhật'
        ],
        [
            'type' => 'password_change',
            'description' => 'Đổi mật khẩu hệ thống',
            'time_formatted' => date('H:i d/m/Y', strtotime('-3 days')),
            'details' => 'Mật khẩu đã được thay đổi'
        ]
    ];
}
?>