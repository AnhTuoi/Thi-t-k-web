<?php
// api/auth.php - API xác thực với MySQLi và PHPMailer

// Khởi động session TRƯỚC mọi thứ
session_start();

error_log("=== AUTH REQUEST ===");
error_log("Action: " . ($_GET['action'] ?? 'none'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session ID: " . session_id());

// CORS headers
header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? "*"));
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cấu hình lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/debug.log');

// Kiểm tra nếu là logout, xử lý riêng
$action = $_GET['action'] ?? '';
error_log("auth.php: requested action={$action}");

if ($action === 'logout') {
    handleLogout();
    exit();
}

// Kết nối database TRƯỚC khi load các thư viện khác
require_once dirname(__DIR__) . '/config/connect.php';

// Lấy kết nối từ connect.php
$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    error_log("Database connection failed");
    exit();
}

// Tải PHPMailer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load email config
$emailConfig = [];
$configPath = dirname(__DIR__) . '/config/email_config.php';
if (file_exists($configPath)) {
    $emailConfig = require $configPath;
} else {
    // Cấu hình mặc định
    $emailConfig = [
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'phungtuoi1502@gmail.com',
            'password' => 'mrof dsmm lfvd mqsx',
            'encryption' => 'tls',
            'from_email' => 'no-reply@foodgo.vn',
            'from_name' => 'FoodGo'
        ],
        'debug' => false
    ];
}

// Xử lý action
switch ($action) {
    case 'login':
        handleLogin($conn);
        break;
    case 'register':
        handleRegister($conn, $emailConfig);
        break;
    case 'activate':
        handleActivateAccount($conn);
        break;
    case 'forgot_password':
        handleForgotPassword($conn, $emailConfig);
        break;
    case 'reset_password':
        handleResetPassword($conn);
        break;
    case 'check_session':
        handleCheckSession();
        break;
    case 'logout':
        // Đã xử lý ở trên
        break;
    case 'test_email':
        handleTestEmail($emailConfig);
        break;
    case 'resend_activation':
        handleResendActivation($conn, $emailConfig);
        break;
    case 'verify_reset_token':
        handleVerifyResetToken($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

if ($conn) {
    $conn->close();
}

// ============ HÀM TẠO ID DUY NHẤT ============
function generateUniqueID($conn, $table, $column, $prefix = '') {
    $maxAttempts = 10;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        // Tạo ID với microtime để đảm bảo duy nhất
        $uniqueId = $prefix . md5(uniqid(mt_rand(), true) . microtime(true));
        
        // Rút ngắn ID
        $uniqueId = substr($uniqueId, 0, 20);
        
        // Kiểm tra ID đã tồn tại chưa
        $checkQuery = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $stmt = $conn->prepare($checkQuery);
        if (!$stmt) {
            error_log("Prepare error in generateUniqueID: " . $conn->error);
            // Fallback
            return $prefix . time() . '_' . mt_rand(10000, 99999);
        }
        $stmt->bind_param("s", $uniqueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] == 0) {
            return $uniqueId;
        }
    }
    
    // Fallback: sử dụng timestamp và random
    return $prefix . time() . '_' . mt_rand(10000, 99999);
}

// ============ HÀM TẠO TOKEN KÍCH HOẠT ============
function generateActivationToken($conn) {
    $maxAttempts = 10;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $token = bin2hex(random_bytes(32));
        
        // Kiểm tra bảng USER_ACTIVATIONS tồn tại
        $checkTableQuery = "SHOW TABLES LIKE 'USER_ACTIVATIONS'";
        $result = $conn->query($checkTableQuery);
        
        if ($result && $result->num_rows > 0) {
            $checkQuery = "SELECT COUNT(*) as count FROM USER_ACTIVATIONS WHERE activation_token = ?";
            $stmt = $conn->prepare($checkQuery);
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row['count'] == 0) {
                    return $token;
                }
            }
        } else {
            return $token;
        }
    }
    
    return bin2hex(random_bytes(32));
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
                    tk.trang_thai,
                    tk.taikhoan_id,
                    tk.ten_dang_nhap,
                    tk.mat_khau
                  FROM TAIKHOAN tk
                  INNER JOIN NGUOIDUNG nd ON tk.nguoidung_id = nd.nguoidung_id
                  WHERE (tk.ten_dang_nhap = ? OR nd.email = ?)
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
        
        // Kiểm tra tài khoản đã kích hoạt chưa - check TAIKHOAN.trang_thai
        if (isset($user['trang_thai']) && $user['trang_thai'] !== 'kich_hoat') {
            // Gửi lại email kích hoạt nếu cần
            $resendLink = "http://" . $_SERVER['HTTP_HOST'] . "/FoodGo/api/auth.php?action=resend_activation&email=" . urlencode($user['email']);
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để kích hoạt tài khoản.',
                'resend_link' => $resendLink
            ]);
            return;
        }
        
        // Xác thực mật khẩu
        if (password_verify($password, $user['mat_khau'])) {
            // Mật khẩu đúng
        } elseif ($user['mat_khau'] === $password) {
            // Tạm thời chấp nhận plain text password (cho tài khoản cũ)
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng']);
            return;
        }
        
        // Cập nhật lần đăng nhập cuối
        try {
            $updateQuery = "UPDATE TAIKHOAN SET lan_dang_nhap_cuoi = NOW() WHERE taikhoan_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if ($updateStmt) {
                $updateStmt->bind_param("s", $user['taikhoan_id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
        
        // Chuẩn bị dữ liệu user trả về
        $userData = [
            'nguoidung_id' => $user['nguoidung_id'],
            'hoten' => $user['hoten'],
            'email' => $user['email'],
            'sodienthoai' => $user['sodienthoai'],
            'diachi' => $user['diachi'],
            'avatar' => $user['avatar'],
            'vai_tro' => $user['vai_tro'],
            'ten_dang_nhap' => $user['ten_dang_nhap']
        ];
        
        // Lưu vào session
        $_SESSION['user'] = $userData;
        
        // Tạo session ID mới để tránh session fixation
        session_regenerate_id(true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $userData,
            'session_id' => session_id()
        ]);
        
    } catch (Exception $e) {
        error_log("Login Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình đăng nhập']);
    }
}

// ============ REGISTER ============
function handleRegister($conn, $emailConfig) {
    try {
        $rawData = file_get_contents('php://input');
        error_log("auth.php: handleRegister raw input=" . substr($rawData, 0, 1000));
        $data = json_decode($rawData, true);
        
        if (!$data) {
            error_log("Invalid JSON: " . $rawData);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        // Validate input
        $requiredFields = ['fullName', 'phone', 'email', 'username', 'password', 'confirmPassword'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                return;
            }
        }
        
        // Extract data
        $fullName = trim($data['fullName']);
        $phone = trim($data['phone']);
        $email = trim($data['email']);
        $address = trim($data['address'] ?? '');
        $username = trim($data['username']);
        $password = $data['password'];
        $confirmPassword = $data['confirmPassword'];
        $userRole = trim($data['userRole'] ?? 'khach_hang');
        
        // Kiểm tra mật khẩu trùng khớp
        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
            return;
        }
        
        // Kiểm tra mật khẩu độ dài
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            return;
        }
        
        // Kiểm tra email hợp lệ
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            return;
        }
        
        // Kiểm tra username hợp lệ
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới']);
            return;
        }
        
        // Kiểm tra username đã tồn tại
        $checkUserQuery = "SELECT taikhoan_id FROM TAIKHOAN WHERE ten_dang_nhap = ?";
        $stmt = $conn->prepare($checkUserQuery);
        if (!$stmt) {
            error_log("Prepare check user error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra tên đăng nhập']);
            return;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
            return;
        }
        $stmt->close();
        
        // Kiểm tra email đã tồn tại
        $checkEmailQuery = "SELECT nguoidung_id FROM NGUOIDUNG WHERE email = ?";
        $stmt = $conn->prepare($checkEmailQuery);
        if (!$stmt) {
            error_log("Prepare check email error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra email']);
            return;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
            return;
        }
        $stmt->close();
        
        // Kiểm tra số điện thoại đã tồn tại
        $checkPhoneQuery = "SELECT nguoidung_id FROM NGUOIDUNG WHERE sodienthoai = ?";
        $stmt = $conn->prepare($checkPhoneQuery);
        if (!$stmt) {
            error_log("Prepare check phone error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra số điện thoại']);
            return;
        }
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Số điện thoại đã được sử dụng']);
            return;
        }
        $stmt->close();
        
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Tạo ID mới cho người dùng
            $nguoidung_id = generateUniqueID($conn, 'NGUOIDUNG', 'nguoidung_id', 'user_');
            
            // Kiểm tra lại ID vừa tạo
            $checkIdQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE nguoidung_id = ?";
            $stmt = $conn->prepare($checkIdQuery);
            $stmt->bind_param("s", $nguoidung_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['count'] > 0) {
                throw new Exception("ID người dùng đã tồn tại, vui lòng thử lại");
            }
            
            // Hash mật khẩu
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Thêm vào bảng NGUOIDUNG
            $insertUserQuery = "INSERT INTO NGUOIDUNG (
                nguoidung_id, 
                hoten, 
                email, 
                sodienthoai, 
                diachi, 
                vai_tro
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertUserQuery);
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị insert NGUOIDUNG: " . $conn->error);
            }
            $stmt->bind_param("ssssss", $nguoidung_id, $fullName, $email, $phone, $address, $userRole);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi thêm người dùng: " . $stmt->error);
            }
            $stmt->close();
            
            // Tạo ID tài khoản
            $taikhoan_id = generateUniqueID($conn, 'TAIKHOAN', 'taikhoan_id', 'acc_');
            
            // Thêm vào bảng TAIKHOAN (trang_thai = chua_kich_hoat until email verification)
            $insertAccountQuery = "INSERT INTO TAIKHOAN (
                taikhoan_id, 
                nguoidung_id, 
                ten_dang_nhap, 
                mat_khau,
                trang_thai
            ) VALUES (?, ?, ?, ?, ?)";
            
            $trang_thai_account = 'chua_kich_hoat'; // Wait for email verification
            $stmt = $conn->prepare($insertAccountQuery);
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị insert TAIKHOAN: " . $conn->error);
            }
            $stmt->bind_param("sssss", $taikhoan_id, $nguoidung_id, $username, $hashedPassword, $trang_thai_account);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi thêm tài khoản: " . $stmt->error);
            }
            $stmt->close();
            
            // Tạo bảng USER_ACTIVATIONS nếu chưa tồn tại
            $createActivationTable = "CREATE TABLE IF NOT EXISTS USER_ACTIVATIONS (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) NOT NULL,
                activation_token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_token (activation_token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$conn->query($createActivationTable)) {
                error_log("Create table USER_ACTIVATIONS error: " . $conn->error);
                // Không throw vẫn tiếp tục
            }
            
            // Tạo token kích hoạt
            $activationToken = generateActivationToken($conn);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Lưu token vào database
            $insertTokenQuery = "INSERT INTO USER_ACTIVATIONS (user_id, activation_token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertTokenQuery);
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị insert token: " . $conn->error);
            }
            $stmt->bind_param("sss", $nguoidung_id, $activationToken, $expiresAt);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi tạo token kích hoạt: " . $stmt->error);
            }
            $stmt->close();
            
            // Commit transaction
            if (!$conn->commit()) {
                throw new Exception("Lỗi commit transaction: " . $conn->error);
            }
            
            // Gửi email kích hoạt
            $emailSent = sendActivationEmail($email, $fullName, $activationToken, $emailConfig);
            
            // Trả về thông tin user
            $userData = [
                'nguoidung_id' => $nguoidung_id,
                'hoten' => $fullName,
                'email' => $email,
                'sodienthoai' => $phone,
                'diachi' => $address,
                'vai_tro' => $userRole,
                'ten_dang_nhap' => $username
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.',
                'user' => $userData,
                'email_sent' => $emailSent
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            if ($conn) {
                $conn->rollback();
            }
            error_log("Register Transaction Error: " . $e->getMessage());
            error_log("SQL State: " . ($conn->sqlstate ?? 'N/A'));
            error_log("Error No: " . ($conn->errno ?? 'N/A'));
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Đã xảy ra lỗi trong quá trình đăng ký',
                'error' => $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Register Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Đã xảy ra lỗi trong quá trình đăng ký',
            'error' => $e->getMessage()
        ]);
    }
}

// ============ HÀM KÍCH HOẠT TÀI KHOẢN ============
// ============ HÀM KÍCH HOẠT TÀI KHOẢN ============
function handleActivateAccount($conn) {
    try {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            // Nếu không có token, trả về JSON error
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu token kích hoạt']);
            exit();
        }
        
        // Kiểm tra token và thời hạn
        $query = "SELECT ua.*, nd.email, nd.hoten, nd.nguoidung_id
                  FROM USER_ACTIVATIONS ua
                  JOIN NGUOIDUNG nd ON ua.user_id = nd.nguoidung_id
                  WHERE ua.activation_token = ? AND ua.expires_at > NOW()";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn']);
            exit();
        }
        
        $activationData = $result->fetch_assoc();
        $stmt->close();
        
        // Cập nhật trạng thái kích hoạt trong TAIKHOAN
        $updateQuery = "UPDATE TAIKHOAN SET trang_thai = 'kich_hoat' WHERE nguoidung_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $activationData['nguoidung_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi kích hoạt tài khoản: " . $stmt->error);
        }
        $stmt->close();
        
        // Xóa token đã sử dụng
        $deleteQuery = "DELETE FROM USER_ACTIVATIONS WHERE activation_token = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
        
        // Trả về JSON thành công
        echo json_encode([
            'success' => true,
            'message' => 'Kích hoạt thành công',
            'user' => [
                'hoten' => $activationData['hoten'],
                'email' => $activationData['email'],
                'nguoidung_id' => $activationData['nguoidung_id']
            ]
        ]);
        exit();
        
    } catch (Exception $e) {
        error_log("Activation Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình kích hoạt: ' . $e->getMessage()]);
        exit();
    }
}

// ============ FORGOT PASSWORD ============
function handleForgotPassword($conn, $emailConfig) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
            return;
        }
        
        $email = trim($data['email']);
        
        // Kiểm tra email hợp lệ
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            return;
        }
        
        // Kiểm tra email có tồn tại trong hệ thống
        $query = "SELECT nd.nguoidung_id, nd.hoten, nd.email, tk.taikhoan_id 
                  FROM NGUOIDUNG nd
                  INNER JOIN TAIKHOAN tk ON nd.nguoidung_id = tk.nguoidung_id
                  WHERE nd.email = ?
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            // Vẫn trả về thành công để bảo mật
            echo json_encode([
                'success' => true,
                'message' => 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn khôi phục mật khẩu.'
            ]);
            return;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Tạo token khôi phục mật khẩu
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Kiểm tra và tạo bảng PASSWORD_RESETS nếu chưa tồn tại
        $checkTableQuery = "SHOW TABLES LIKE 'PASSWORD_RESETS'";
        $result = $conn->query($checkTableQuery);
        
        if ($result && $result->num_rows === 0) {
            // Tạo bảng
            $createTableQuery = "CREATE TABLE IF NOT EXISTS PASSWORD_RESETS (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_email (email),
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$conn->query($createTableQuery)) {
                error_log("Create table error: " . $conn->error);
            }
        }
        
        // Lưu token vào database
        $insertTokenQuery = "INSERT INTO PASSWORD_RESETS (email, token, expires_at, created_at) 
                            VALUES (?, ?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                            token = VALUES(token), 
                            expires_at = VALUES(expires_at), 
                            created_at = NOW()";
        
        $stmt = $conn->prepare($insertTokenQuery);
        $stmt->bind_param("sss", $email, $token, $expires);
        
        if (!$stmt->execute()) {
            error_log("Token Insert Error: " . $stmt->error);
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo token khôi phục']);
            return;
        }
        $stmt->close();
        
        // Tạo link reset password - FIXED PATH
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/FoodGo/Fontend/ResetPassword.html?token=" . $token . "&email=" . urlencode($email);
        
        // Gửi email reset password
        $emailSent = sendPasswordResetEmail($email, $user['hoten'], $resetLink, $emailConfig);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Hướng dẫn khôi phục mật khẩu đã được gửi đến email của bạn.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Forgot Password Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình xử lý']);
    }
}

// ============ RESET PASSWORD ============
function handleResetPassword($conn) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        $requiredFields = ['token', 'email', 'password', 'confirmPassword'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                return;
            }
        }
        
        $token = trim($data['token']);
        $email = trim($data['email']);
        $password = $data['password'];
        $confirmPassword = $data['confirmPassword'];
        
        // Kiểm tra mật khẩu trùng khớp
        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
            return;
        }
        
        // Kiểm tra mật khẩu độ dài
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            return;
        }
        
        // Kiểm tra bảng PASSWORD_RESETS tồn tại
        $checkTableQuery = "SHOW TABLES LIKE 'PASSWORD_RESETS'";
        $result = $conn->query($checkTableQuery);
        
        if (!$result || $result->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
            return;
        }
        
        // Kiểm tra token hợp lệ
        $checkTokenQuery = "SELECT * FROM PASSWORD_RESETS 
                           WHERE email = ? AND token = ? AND expires_at > NOW() 
                           ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $conn->prepare($checkTokenQuery);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn']);
            return;
        }
        
        $tokenData = $result->fetch_assoc();
        $stmt->close();
        
        // Cập nhật mật khẩu mới
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $updatePasswordQuery = "UPDATE TAIKHOAN tk
                               INNER JOIN NGUOIDUNG nd ON tk.nguoidung_id = nd.nguoidung_id
                               SET tk.mat_khau = ?
                               WHERE nd.email = ?";
        
        $stmt = $conn->prepare($updatePasswordQuery);
        $stmt->bind_param("ss", $hashedPassword, $email);
        
        if (!$stmt->execute()) {
            error_log("Password Update Error: " . $stmt->error);
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật mật khẩu']);
            return;
        }
        $stmt->close();
        
        // Xóa token đã sử dụng
        $deleteTokenQuery = "DELETE FROM PASSWORD_RESETS WHERE email = ? AND token = ?";
        $stmt = $conn->prepare($deleteTokenQuery);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.'
        ]);
        
    } catch (Exception $e) {
        error_log("Reset Password Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình đặt lại mật khẩu']);
    }
}

// ============ VERIFY RESET TOKEN ============
function handleVerifyResetToken($conn) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        if (empty($data['token']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu token hoặc email']);
            return;
        }
        
        $token = trim($data['token']);
        $email = trim($data['email']);
        
        // Kiểm tra bảng PASSWORD_RESETS tồn tại
        $checkTableQuery = "SHOW TABLES LIKE 'PASSWORD_RESETS'";
        $result = $conn->query($checkTableQuery);
        
        if (!$result || $result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
            return;
        }
        
        // Kiểm tra token hợp lệ
        $checkTokenQuery = "SELECT * FROM PASSWORD_RESETS 
                           WHERE email = ? AND token = ? AND expires_at > NOW() 
                           ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $conn->prepare($checkTokenQuery);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn']);
            return;
        }
        
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Token hợp lệ']);
        
    } catch (Exception $e) {
        error_log("Verify Token Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình xác thực token']);
    }
}

// ============ CHECK SESSION ============
function handleCheckSession() {
    error_log("Session check - user data: " . json_encode($_SESSION['user'] ?? 'empty'));
    
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'user' => $_SESSION['user'],
            'session_active' => true,
            'session_id' => session_id()
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Not logged in',
            'session_active' => false
        ]);
    }
}

// ============ LOGOUT ============
function handleLogout() {
    // Xóa toàn bộ session data
    $_SESSION = array();
    
    // Xóa session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Xóa remember cookie nếu có
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Clear any existing output
    if (ob_get_length()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đã đăng xuất',
        'clearLocalStorage' => true,
        'session_destroyed' => true
    ]);
    exit();
}

// ============ RESEND ACTIVATION EMAIL ============
function handleResendActivation($conn, $emailConfig) {
    try {
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            return;
        }
        
        // Tìm user chưa kích hoạt
        $query = "SELECT nd.nguoidung_id, nd.hoten, nd.email, ua.activation_token, tk.trang_thai
                  FROM NGUOIDUNG nd
                  INNER JOIN TAIKHOAN tk ON nd.nguoidung_id = tk.nguoidung_id
                  LEFT JOIN USER_ACTIVATIONS ua ON nd.nguoidung_id = ua.user_id
                  WHERE nd.email = ? AND tk.trang_thai = 'chua_kich_hoat'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản chưa kích hoạt với email này']);
            return;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Tạo token mới nếu không có
        if (empty($user['activation_token'])) {
            $activationToken = generateActivationToken($conn);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $insertTokenQuery = "INSERT INTO USER_ACTIVATIONS (user_id, activation_token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertTokenQuery);
            $stmt->bind_param("sss", $user['nguoidung_id'], $activationToken, $expiresAt);
            $stmt->execute();
            $stmt->close();
        } else {
            $activationToken = $user['activation_token'];
        }
        
        // Gửi email
        $emailSent = sendActivationEmail($user['email'], $user['hoten'], $activationToken, $emailConfig);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Email kích hoạt đã được gửi lại. Vui lòng kiểm tra hòm thư của bạn.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Resend Activation Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi trong quá trình gửi lại email']);
    }
}

// ============ TEST EMAIL ============
function handleTestEmail($emailConfig) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        $testEmail = $data['email'] ?? 'test@example.com';
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp']['username'];
        $mail->Password   = $emailConfig['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp']['port'];
        
        // Debug
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };
        
        // Recipients
        $mail->setFrom($emailConfig['smtp']['from_email'], $emailConfig['smtp']['from_name']);
        $mail->addAddress($testEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from FoodGo System';
        $mail->Body    = '<h1>Test Email Successful!</h1><p>This is a test email from FoodGo system.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
        $mail->AltBody = 'Test Email Successful! This is a test email from FoodGo system. Time: ' . date('Y-m-d H:i:s');
        
        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Test email sent successfully to ' . $testEmail
        ]);
        
    } catch (Exception $e) {
        error_log("Test Email Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send test email: ' . $e->getMessage()
        ]);
    }
}

// ============ EMAIL FUNCTIONS WITH PHPMailer ============
function sendActivationEmail($toEmail, $fullName, $activationToken, $emailConfig) {
    try {
        $subject = "Kích hoạt tài khoản FoodGo";
        
        // Tạo link kích hoạt - FIXED PATH
        $activationLink = "http://" . $_SERVER['HTTP_HOST'] . "/FoodGo/Fontend/ActivateAccount.html?token=" . $activationToken;
        
        $message = "
        <html>
        <head>
            <title>Kích hoạt tài khoản FoodGo</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f48c25; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background-color: #f9f9f9; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #f48c25; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>FoodGo</h1>
                    <p>Trải nghiệm ẩm thực hoàn hảo</p>
                </div>
                <div class='content'>
                    <h2>Xin chào $fullName!</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại FoodGo.</p>
                    <p>Để bắt đầu sử dụng tài khoản, vui lòng kích hoạt bằng cách nhấp vào nút bên dưới:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$activationLink' class='button'>Kích hoạt tài khoản</a>
                    </p>
                    <p>Hoặc sao chép và dán liên kết sau vào trình duyệt của bạn:</p>
                    <p style='word-break: break-all; background-color: #eee; padding: 10px; border-radius: 5px;'>
                        $activationLink
                    </p>
                    <div class='warning'>
                        <p><strong>Lưu ý quan trọng:</strong></p>
                        <ul>
                            <li>Liên kết này sẽ hết hạn sau 24 giờ</li>
                            <li>Nếu bạn không đăng ký tài khoản, vui lòng bỏ qua email này</li>
                            <li>Sau khi kích hoạt, bạn có thể đăng nhập và sử dụng tất cả tính năng</li>
                        </ul>
                    </div>
                    <div class='footer'>
                        <p>© 2024 FoodGo. Tất cả các quyền được bảo lưu.</p>
                        <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp']['username'];
        $mail->Password   = $emailConfig['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp']['port'];
        
        // Debug
        if ($emailConfig['debug']) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };
        }
        
        // Recipients
        $mail->setFrom($emailConfig['smtp']['from_email'], $emailConfig['smtp']['from_name']);
        $mail->addAddress($toEmail);
        $mail->addReplyTo('support@foodgo.vn', 'FoodGo Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        
        $mail->send();
        error_log("Activation email sent successfully to: $toEmail");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send activation email to $toEmail: " . $e->getMessage());
        return false;
    }
}

function sendPasswordResetEmail($toEmail, $fullName, $resetLink, $emailConfig) {
    try {
        $subject = "Yêu cầu đặt lại mật khẩu - FoodGo";
        
        $message = "
        <html>
        <head>
            <title>Đặt lại mật khẩu FoodGo</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f48c25; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background-color: #f9f9f9; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #f48c25; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>FoodGo</h1>
                    <p>Trải nghiệm ẩm thực hoàn hảo</p>
                </div>
                <div class='content'>
                    <h2>Xin chào $fullName!</h2>
                    <p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                    <p>Để đặt lại mật khẩu, vui lòng nhấp vào nút bên dưới:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' class='button'>Đặt lại mật khẩu</a>
                    </p>
                    <p>Hoặc sao chép và dán liên kết sau vào trình duyệt của bạn:</p>
                    <p style='word-break: break-all; background-color: #eee; padding: 10px; border-radius: 5px;'>
                        $resetLink
                    </p>
                    <div class='warning'>
                        <p><strong>Lưu ý quan trọng:</strong></p>
                        <ul>
                            <li>Liên kết này sẽ hết hạn sau 1 giờ</li>
                            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
                            <li>Để bảo mật, không chia sẻ liên kết này với bất kỳ ai</li>
                        </ul>
                    </div>
                    <div class='footer'>
                        <p>© 2024 FoodGo. Tất cả các quyền được bảo lưu.</p>
                        <p>Đây là email tự động, vui lòng không trả lời email này.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp']['username'];
        $mail->Password   = $emailConfig['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp']['port'];
        
        // Debug
        if ($emailConfig['debug']) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };
        }
        
        // Recipients
        $mail->setFrom($emailConfig['smtp']['from_email'], $emailConfig['smtp']['from_name']);
        $mail->addAddress($toEmail);
        $mail->addReplyTo('support@foodgo.vn', 'FoodGo Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        
        $mail->send();
        error_log("Password reset email sent successfully to: $toEmail");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send password reset email to $toEmail: " . $e->getMessage());
        return false;
    }
}

// ============ FALLBACK ACTIVATION PAGE ============
// Nếu action=activate được gọi trực tiếp mà không redirect
if ($action === 'activate' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Kiểm tra token
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        // Hiển thị trang lỗi
        echo "
        <!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Lỗi kích hoạt - FoodGo</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #f48c25 0%, #ff6b6b 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container { 
                    background: white; 
                    padding: 40px; 
                    border-radius: 20px; 
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
                    text-align: center;
                    max-width: 500px;
                    width: 100%;
                }
                .icon { 
                    font-size: 80px; 
                    margin-bottom: 20px; 
                    color: #f44336;
                }
                h1 { 
                    color: #f48c25; 
                    margin-bottom: 15px;
                }
                p { 
                    color: #666; 
                    margin-bottom: 10px;
                }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: #f48c25; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 10px; 
                    font-weight: bold; 
                    margin-top: 25px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='icon'>✗</div>
                <h1>Liên kết không hợp lệ</h1>
                <p>Liên kết kích hoạt không hợp lệ hoặc đã hết hạn.</p>
                <p>Vui lòng yêu cầu gửi lại email kích hoạt.</p>
                <a href='../Fontend/Dangnhap.html' class='button'>Quay lại đăng nhập</a>
            </div>
        </body>
        </html>";
        exit();
    }
    
    // Xử lý kích hoạt
    handleActivateAccount($conn);
}
?>