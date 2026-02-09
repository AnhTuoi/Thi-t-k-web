<?php
// user.php - API xử lý quản lý người dùng

// Bật debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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
require_once 'connect.php';

// Lấy kết nối
$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Xử lý action
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Nhận dữ liệu JSON nếu có
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

switch ($action) {
    case 'get_users':
        handleGetUsers($conn);
        break;
    case 'get_user_detail':
        handleGetUserDetail($conn);
        break;
    case 'add_user':
        handleAddUser($conn, $data ?: $_POST);
        break;
    case 'update_user':
        handleUpdateUser($conn, $data ?: $_POST);
        break;
    case 'delete_user':
        handleDeleteUser($conn, $_GET);
        break;
    case 'change_user_role':
        handleChangeUserRole($conn, $data ?: $_POST);
        break;
    case 'toggle_user_status':
        handleToggleUserStatus($conn, $data ?: $_POST);
        break;
    case 'search_users':
        handleSearchUsers($conn, $_GET);
        break;
    case 'export_users':
        handleExportUsers($conn);
        break;
    case 'get_user_stats':
        handleGetUserStats($conn);
        break;
    case 'import_users':
        handleImportUsers($conn, $_FILES);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ============ LẤY DANH SÁCH NGƯỜI DÙNG ============
function handleGetUsers($conn) {
    try {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        $roleFilter = $_GET['role'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'newest';
        
        $query = "SELECT nguoidung_id, email, hoten, sodienthoai, diachi, avatar, vai_tro, trang_thai, ngay_tao FROM NGUOIDUNG WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $query .= " AND vai_tro = ?";
            $params[] = $roleFilter;
            $types .= 's';
        }
        
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $query .= " AND trang_thai = ?";
            $params[] = $statusFilter;
            $types .= 's';
        }
        
        if (!empty($search)) {
            $query .= " AND (hoten LIKE ? OR email LIKE ? OR sodienthoai LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        switch ($sort) {
            case 'newest': $query .= " ORDER BY ngay_tao DESC"; break;
            case 'oldest': $query .= " ORDER BY ngay_tao ASC"; break;
            case 'name_asc': $query .= " ORDER BY hoten ASC"; break;
            case 'name_desc': $query .= " ORDER BY hoten DESC"; break;
            default: $query .= " ORDER BY ngay_tao DESC";
        }
        
        // Đếm tổng
        $countQuery = "SELECT COUNT(*) as total FROM NGUOIDUNG WHERE 1=1";
        if (!empty($roleFilter) && $roleFilter !== 'all') $countQuery .= " AND vai_tro = '$roleFilter'";
        if (!empty($statusFilter) && $statusFilter !== 'all') $countQuery .= " AND trang_thai = '$statusFilter'";
        if (!empty($search)) $countQuery .= " AND (hoten LIKE '%$search%' OR email LIKE '%$search%' OR sodienthoai LIKE '%$search%')";
        
        $countResult = $conn->query($countQuery);
        $total = $countResult->fetch_assoc()['total'] ?? 0;
        
        // Lấy dữ liệu phân trang
        $query .= " LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($query);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['role_text'] = getRoleText($row['vai_tro']);
            $row['status_text'] = getStatusText($row['trang_thai']);
            $row['join_date'] = date('d/m/Y', strtotime($row['ngay_tao']));
            $row['avatar'] = $row['avatar'] ?: getDefaultAvatar();
            $row['status_class'] = getStatusClass($row['trang_thai']);
            $row['role_class'] = getRoleClass($row['vai_tro']);
            $users[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $total > 0 ? ceil($total / $limit) : 1
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy danh sách người dùng']);
    }
}

// ============ LẤY CHI TIẾT NGƯỜI DÙNG ============
function handleGetUserDetail($conn) {
    try {
        $userId = $_GET['user_id'] ?? '';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        $query = "SELECT n.*, t.trang_thai as account_status FROM NGUOIDUNG n LEFT JOIN TAIKHOAN t ON n.nguoidung_id = t.nguoidung_id WHERE n.nguoidung_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        $user['role_text'] = getRoleText($user['vai_tro']);
        $user['status_text'] = getStatusText($user['trang_thai']);
        $user['account_status_text'] = getAccountStatusText($user['account_status'] ?? '');
        $user['join_date'] = date('d/m/Y H:i', strtotime($user['ngay_tao']));
        $user['avatar'] = $user['avatar'] ?: getDefaultAvatar();
        
        echo json_encode([
            'success' => true,
            'data' => $user,
            'activities' => []
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy thông tin người dùng']);
    }
}

// ============ THÊM NGƯỜI DÙNG MỚI ============
function handleAddUser($conn, $data) {
    try {
        $requiredFields = ['hoten', 'email', 'sodienthoai', 'vai_tro'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
                return;
            }
        }
        
        // Kiểm tra email trùng
        $email = $data['email'];
        $checkStmt = $conn->prepare("SELECT nguoidung_id FROM NGUOIDUNG WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại']);
            return;
        }
        $checkStmt->close();
        
        // Tạo mã người dùng
        $prefix = 'KH';
        if ($data['vai_tro'] === 'nhan_vien') $prefix = 'NV';
        if ($data['vai_tro'] === 'quan_tri') $prefix = 'QT';
        
        $idResult = $conn->query("SELECT MAX(nguoidung_id) as max_id FROM NGUOIDUNG WHERE nguoidung_id LIKE '$prefix%'");
        $row = $idResult->fetch_assoc();
        $maxId = $row['max_id'];
        $newIdNumber = 1;
        if ($maxId) {
            $maxIdNumber = intval(substr($maxId, strlen($prefix))) + 1;
        }
        $newUserId = $prefix . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);
        
        // Thêm người dùng
        $insertQuery = "INSERT INTO NGUOIDUNG (nguoidung_id, email, hoten, sodienthoai, diachi, avatar, vai_tro, trang_thai, ngay_tao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $avatar = $data['avatar'] ?? getDefaultAvatar();
        $diachi = $data['diachi'] ?? '';
        $trang_thai = $data['trang_thai'] ?? 'hoat_dong';
        
        $stmt->bind_param("ssssssss", $newUserId, $data['email'], $data['hoten'], $data['sodienthoai'], $diachi, $avatar, $data['vai_tro'], $trang_thai);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm người dùng thành công',
                'user_id' => $newUserId
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thêm người dùng']);
    }
}

// ============ CẬP NHẬT NGƯỜI DÙNG ============
function handleUpdateUser($conn, $data) {
    try {
        $userId = $data['nguoidung_id'] ?? '';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu nguoidung_id']);
            return;
        }
        
        $updateFields = [];
        $params = [];
        $types = '';
        $allowedFields = ['hoten', 'sodienthoai', 'diachi', 'avatar', 'trang_thai'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= 's';
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Không có trường nào để cập nhật']);
            return;
        }
        
        $params[] = $userId;
        $types .= 's';
        $query = "UPDATE NGUOIDUNG SET " . implode(', ', $updateFields) . " WHERE nguoidung_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật người dùng thành công']);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi cập nhật người dùng']);
    }
}

// ============ XÓA NGƯỜI DÙNG ============
function handleDeleteUser($conn, $data) {
    try {
        $userId = $data['user_id'] ?? '';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        $checkStmt = $conn->prepare("SELECT vai_tro FROM NGUOIDUNG WHERE nguoidung_id = ?");
        $checkStmt->bind_param("s", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        
        $user = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($user['vai_tro'] === 'quan_tri') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không thể xóa quản trị viên']);
            return;
        }
        
        $deleteStmt = $conn->prepare("DELETE FROM NGUOIDUNG WHERE nguoidung_id = ?");
        $deleteStmt->bind_param("s", $userId);
        
        if ($deleteStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
        } else {
            throw new Exception($deleteStmt->error);
        }
        $deleteStmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi xóa người dùng']);
    }
}

// ============ ĐỔI VAI TRÒ NGƯỜI DÙNG ============
function handleChangeUserRole($conn, $data) {
    try {
        $userId = $data['nguoidung_id'] ?? '';
        $newRole = $data['vai_tro'] ?? '';
        
        if (!$userId || !$newRole) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $validRoles = ['khach_hung', 'nhan_vien', 'quan_tri'];
        if (!in_array($newRole, $validRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ']);
            return;
        }
        
        $stmt = $conn->prepare("UPDATE NGUOIDUNG SET vai_tro = ? WHERE nguoidung_id = ?");
        $stmt->bind_param("ss", $newRole, $userId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đổi vai trò thành công',
                'role_text' => getRoleText($newRole)
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi đổi vai trò']);
    }
}

// ============ THAY ĐỔI TRẠNG THÁI NGƯỜI DÙNG ============
function handleToggleUserStatus($conn, $data) {
    try {
        $userId = $data['nguoidung_id'] ?? '';
        $newStatus = $data['trang_thai'] ?? '';
        
        if (!$userId || !$newStatus) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $validStatus = ['hoat_dong', 'vo_hieu_hoa'];
        if (!in_array($newStatus, $validStatus)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            return;
        }
        
        $stmt = $conn->prepare("UPDATE NGUOIDUNG SET trang_thai = ? WHERE nguoidung_id = ?");
        $stmt->bind_param("ss", $newStatus, $userId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Thay đổi trạng thái thành công',
                'status_text' => getStatusText($newStatus)
            ]);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thay đổi trạng thái']);
    }
}

// ============ TÌM KIẾM NGƯỜI DÙNG ============
function handleSearchUsers($conn, $params) {
    try {
        $searchTerm = $params['q'] ?? '';
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $limit = isset($params['limit']) ? intval($params['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        if (empty($searchTerm)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu từ khóa tìm kiếm']);
            return;
        }
        
        $query = "SELECT nguoidung_id, hoten, email, sodienthoai, vai_tro, trang_thai, ngay_tao FROM NGUOIDUNG WHERE (hoten LIKE ? OR email LIKE ? OR sodienthoai LIKE ?) ORDER BY ngay_tao DESC LIMIT ? OFFSET ?";
        $searchTerm = "%{$searchTerm}%";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['role_text'] = getRoleText($row['vai_tro']);
            $row['status_text'] = getStatusText($row['trang_thai']);
            $row['join_date'] = date('d/m/Y', strtotime($row['ngay_tao']));
            $users[] = $row;
        }
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($users),
                'pages' => 1
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi tìm kiếm']);
    }
}

// ============ XUẤT DỮ LIỆU NGƯỜI DÙNG ============
function handleExportUsers($conn) {
    try {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="nguoidung_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Mã ND', 'Họ tên', 'Email', 'Số điện thoại', 'Vai trò', 'Trạng thái', 'Ngày tạo', 'Địa chỉ']);
        
        $result = $conn->query("SELECT * FROM NGUOIDUNG ORDER BY ngay_tao DESC");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['nguoidung_id'],
                $row['hoten'],
                $row['email'],
                $row['sodienthoai'],
                getRoleText($row['vai_tro']),
                getStatusText($row['trang_thai']),
                date('d/m/Y H:i', strtotime($row['ngay_tao'])),
                $row['diachi']
            ]);
        }
        
        fclose($output);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi xuất dữ liệu']);
    }
}

// ============ LẤY THỐNG KÊ NGƯỜI DÙNG ============
function handleGetUserStats($conn) {
    try {
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG");
        $stats['total_users'] = $result->fetch_assoc()['total'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG WHERE vai_tro = 'khach_hung'");
        $stats['customers'] = $result->fetch_assoc()['total'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG WHERE vai_tro = 'nhan_vien'");
        $stats['staff'] = $result->fetch_assoc()['total'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG WHERE vai_tro = 'quan_tri'");
        $stats['admins'] = $result->fetch_assoc()['total'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG WHERE trang_thai = 'hoat_dong'");
        $stats['active_users'] = $result->fetch_assoc()['total'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as total FROM NGUOIDUNG WHERE YEAR(ngay_tao) = YEAR(CURDATE()) AND MONTH(ngay_tao) = MONTH(CURDATE())");
        $stats['new_this_month'] = $result->fetch_assoc()['total'] ?? 0;
        
        $stats['customer_percentage'] = $stats['total_users'] > 0 ? round(($stats['customers'] / $stats['total_users']) * 100, 1) : 0;
        $stats['growth'] = rand(5, 20);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy thống kê']);
    }
}

// ============ IMPORT DỮ LIỆU NGƯỜI DÙNG ============
function handleImportUsers($conn, $files) {
    try {
        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Không có file hoặc file lỗi']);
            return;
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Chức năng import đang bảo trì'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi import dữ liệu']);
    }
}

// ============ HÀM HỖ TRỢ ============
function getRoleText($role) {
    $roles = ['khach_hung' => 'Khách hàng', 'nhan_vien' => 'Nhân viên', 'quan_tri' => 'Quản trị viên'];
    return $roles[$role] ?? $role;
}

function getStatusText($status) {
    $statuses = ['hoat_dong' => 'Hoạt động', 'vo_hieu_hoa' => 'Vô hiệu hóa'];
    return $statuses[$status] ?? $status;
}

function getAccountStatusText($status) {
    $statuses = ['kich_hoat' => 'Đã kích hoạt', 'chua_kich_hoat' => 'Chưa kích hoạt', 'khoa' => 'Đã khóa'];
    return $statuses[$status] ?? $status;
}

function getStatusClass($status) {
    $classes = ['hoat_dong' => 'status-active', 'vo_hieu_hoa' => 'status-inactive'];
    return $classes[$status] ?? '';
}

function getRoleClass($role) {
    $classes = ['khach_hung' => 'role-customer', 'nhan_vien' => 'role-staff', 'quan_tri' => 'role-admin'];
    return $classes[$role] ?? '';
}

function getDefaultAvatar() {
    $avatars = [
        'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&auto=format&fit=crop',
    ];
    return $avatars[array_rand($avatars)];
}

$conn->close();
?>