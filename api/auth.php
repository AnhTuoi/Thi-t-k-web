<?php
// api/auth.php - API xác thực với MySQLi
session_start();

// Cấu hình lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/debug.log');

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kết nối database
require_once '../connect.php';

// Lấy kết nối từ connect.php
$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Xử lý action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($conn);
        break;
    case 'check_session':
        handleCheckSession();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ============ LOGIN ============
function handleLogin($conn) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data) {
            error_log("Invalid JSON: " . $rawData);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        if (empty($data['loginInput']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            return;
        }
        
        $loginInput = trim($data['loginInput']);
        $password = $data['password'];
        
        // Query với JOIN giữa TAIKHOAN và NGUOIDUNG
        $query = "SELECT 
                    nd.nguoidung_id, 
                    nd.email, 
                    nd.hoten, 
                    nd.sodienthoai, 
                    nd.diachi, 
                    nd.avatar, 
                    nd.vai_tro, 
                    nd.ngay_tao, 
                    nd.trang_thai,
                    tk.taikhoan_id,
                    tk.ten_dang_nhap,
                    tk.mat_khau,
                    tk.loai_xac_thuc,
                    tk.trang_thai as tk_trang_thai
                  FROM TAIKHOAN tk
                  INNER JOIN NGUOIDUNG nd ON tk.nguoidung_id = nd.nguoidung_id
                  WHERE (tk.ten_dang_nhap = ? OR nd.email = ?)
                  AND nd.trang_thai = 'hoat_dong'
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare Error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn']);
            return;
        }
        
        $stmt->bind_param("ss", $loginInput, $loginInput);
        if (!$stmt->execute()) {
            error_log("Execute Error: " . $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn']);
            $stmt->close();
            return;
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại']);
            $stmt->close();
            return;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Kiểm tra trạng thái tài khoản
        if ($user['tk_trang_thai'] !== 'kich_hoat') {
            if ($user['tk_trang_thai'] === 'khoa') {
                $message = 'Tài khoản đã bị khóa';
            } else {
                $message = 'Tài khoản chưa được kích hoạt';
            }
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => $message]);
            return;
        }
        
        // Xác thực mật khẩu
        // Lưu ý: Trong database mẫu, mật khẩu được lưu plain text (không hash)
        // Trong thực tế, nên dùng password_verify()
        if ($user['mat_khau'] !== $password) {
            // Nếu muốn dùng hash trong tương lai, uncomment dòng dưới
            // if (!password_verify($password, $user['mat_khau'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng']);
            return;
        }
        
        // Cập nhật lần đăng nhập cuối
        $updateQuery = "UPDATE TAIKHOAN SET lan_dang_nhap_cuoi = NOW() WHERE taikhoan_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if ($updateStmt) {
            $updateStmt->bind_param("s", $user['taikhoan_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // Chuẩn bị dữ liệu user trả về (loại bỏ thông tin nhạy cảm)
        $userData = [
            'nguoidung_id' => $user['nguoidung_id'],
            'hoten' => $user['hoten'],
            'email' => $user['email'],
            'sodienthoai' => $user['sodienthoai'],
            'diachi' => $user['diachi'],
            'avatar' => $user['avatar'],
            'vai_tro' => $user['vai_tro'],
            'ten_dang_nhap' => $user['ten_dang_nhap'],
            'loai_xac_thuc' => $user['loai_xac_thuc']
        ];
        
        // Lưu vào session
        $_SESSION['user'] = $userData;
        
        // Nếu có ghi nhớ đăng nhập
        if (isset($data['rememberMe']) && $data['rememberMe']) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 ngày
            
            // Lưu token vào database (cần thêm bảng remember_tokens)
            // Đây là phần mở rộng, có thể bổ sung sau
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $userData
        ]);
        
    } catch (Exception $e) {
        error_log("Login Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình đăng nhập']);
    }
}

// ============ CHECK SESSION ============
function handleCheckSession() {
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'user' => $_SESSION['user']
        ]);
    } else {
        // Kiểm tra remember token
        if (isset($_COOKIE['remember_token'])) {
            // Xử lý remember token (cần implement)
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
        }
    }
}

// ============ LOGOUT ============
function handleLogout() {
    // Xóa session
    session_unset();
    session_destroy();
    
    // Xóa remember cookie nếu có
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
}

$conn->close();
?>