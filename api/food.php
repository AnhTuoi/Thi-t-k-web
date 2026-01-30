<?php
// api/foods.php - API xử lý món ăn và giỏ hàng
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
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'add_to_cart':
        handleAddToCart($conn);
        break;
    case 'get_cart':
        handleGetCart($conn);
        break;
    case 'update_cart':
        handleUpdateCart($conn);
        break;
    case 'remove_from_cart':
        handleRemoveFromCart($conn);
        break;
    case 'search_foods':
        handleSearchFoods($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ============ THÊM VÀO GIỎ HÀNG (Phiên bản đầy đủ) ============
function handleAddToCart($conn) {
    try {
        // Nhận dữ liệu từ POST hoặc input JSON
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data && !empty($_POST)) {
            $data = $_POST;
        }
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        // Kiểm tra dữ liệu bắt buộc
        $requiredFields = ['user_id', 'food_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
                return;
            }
        }
        
        $userId = $data['user_id'];
        $foodId = $data['food_id'];
        $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
        
        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
            return;
        }
        
        // Kiểm tra user tồn tại
        $userQuery = "SELECT nguoidung_id FROM NGUOIDUNG WHERE nguoidung_id = ? AND trang_thai = 'hoat_dong'";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("s", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            $userStmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            return;
        }
        $userStmt->close();
        
        // Kiểm tra món ăn tồn tại và có bán
        $foodQuery = "SELECT monan_id, ten_mon, gia, trang_thai FROM MONAN WHERE monan_id = ?";
        $foodStmt = $conn->prepare($foodQuery);
        $foodStmt->bind_param("s", $foodId);
        $foodStmt->execute();
        $foodResult = $foodStmt->get_result();
        
        if ($foodResult->num_rows === 0) {
            $foodStmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
            return;
        }
        
        $food = $foodResult->fetch_assoc();
        $foodStmt->close();
        
        if ($food['trang_thai'] !== 'dang_ban') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Món ăn hiện không bán']);
            return;
        }
        
        // Kiểm tra giỏ hàng tồn tại - tạo bảng nếu chưa có
        checkCartTable($conn);
        
        // Kiểm tra món đã có trong giỏ chưa
        $cartQuery = "SELECT cart_id, quantity FROM GIOHANG WHERE nguoidung_id = ? AND monan_id = ?";
        $cartStmt = $conn->prepare($cartQuery);
        $cartStmt->bind_param("ss", $userId, $foodId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();
        
        if ($cartResult->num_rows > 0) {
            // Cập nhật số lượng
            $cartItem = $cartResult->fetch_assoc();
            $newQuantity = $cartItem['quantity'] + $quantity;
            
            $updateQuery = "UPDATE GIOHANG SET quantity = ?, updated_at = NOW() WHERE cart_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $newQuantity, $cartItem['cart_id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $message = 'Đã cập nhật số lượng trong giỏ hàng';
        } else {
            // Thêm mới vào giỏ
            $insertQuery = "INSERT INTO GIOHANG (nguoidung_id, monan_id, quantity, unit_price, created_at) 
                           VALUES (?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("ssid", $userId, $foodId, $quantity, $food['gia']);
            $insertStmt->execute();
            $insertStmt->close();
            
            $message = 'Đã thêm vào giỏ hàng';
        }
        
        $cartStmt->close();
        
        // Lấy số lượng hiện tại trong giỏ
        $countQuery = "SELECT SUM(quantity) as total FROM GIOHANG WHERE nguoidung_id = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("s", $userId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $cartCount = $countResult->fetch_assoc()['total'] ?? 0;
        $countStmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'food_name' => $food['ten_mon'],
            'cart_count' => $cartCount
        ]);

    } catch (Exception $e) {
        error_log("Add to Cart Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thêm vào giỏ hàng']);
    }
}

// ============ KIỂM TRA VÀ TẠO BẢNG GIỎ HÀNG ============
function checkCartTable($conn) {
    // Kiểm tra bảng GIOHANG đã tồn tại chưa
    $checkQuery = "SHOW TABLES LIKE 'GIOHANG'";
    $result = $conn->query($checkQuery);
    
    if ($result->num_rows === 0) {
        // Tạo bảng giỏ hàng
        $createTable = "CREATE TABLE IF NOT EXISTS GIOHANG (
            cart_id INT AUTO_INCREMENT PRIMARY KEY,
            nguoidung_id VARCHAR(10) NOT NULL,
            monan_id VARCHAR(10) NOT NULL,
            quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
            unit_price DECIMAL(10,2) NOT NULL CHECK (unit_price >= 0),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
            FOREIGN KEY (monan_id) REFERENCES MONAN(monan_id) ON DELETE CASCADE,
            UNIQUE KEY unique_cart_item (nguoidung_id, monan_id),
            INDEX idx_nguoidung (nguoidung_id),
            INDEX idx_monan (monan_id)
        )";
        
        $conn->query($createTable);
    }
}

// ============ LẤY GIỎ HÀNG ============
function handleGetCart($conn) {
    try {
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
            return;
        }
        
        checkCartTable($conn);
        
        $query = "SELECT 
                    g.cart_id,
                    g.monan_id as food_id,
                    m.ten_mon as food_name,
                    m.mo_ta as description,
                    m.hinh_anh as image_url,
                    g.quantity,
                    g.unit_price as price,
                    (g.quantity * g.unit_price) as total_price,
                    m.trang_thai as food_status
                  FROM GIOHANG g
                  INNER JOIN MONAN m ON g.monan_id = m.monan_id
                  WHERE g.nguoidung_id = ?
                  ORDER BY g.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare Error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn']);
            return;
        }
        
        $stmt->bind_param("s", $userId);
        
        if (!$stmt->execute()) {
            error_log("Execute Error: " . $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn']);
            $stmt->close();
            return;
        }
        
        $result = $stmt->get_result();
        $cartItems = [];
        $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Định dạng giá
            $row['price_formatted'] = number_format($row['price'], 0, ',', '.') . 'đ';
            $row['total_price_formatted'] = number_format($row['total_price'], 0, ',', '.') . 'đ';
            
            // Xử lý ảnh
            if (empty($row['image_url'])) {
                $row['image_url'] = getDefaultFoodImage();
            }
            
            $cartItems[] = $row;
            $total += $row['total_price'];
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $cartItems,
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.') . 'đ',
            'item_count' => count($cartItems)
        ]);

    } catch (Exception $e) {
        error_log("Get Cart Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy giỏ hàng']);
    }
}

// ============ CẬP NHẬT GIỎ HÀNG ============
function handleUpdateCart($conn) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        $cartId = $data['cart_id'] ?? null;
        $quantity = $data['quantity'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        if (!$cartId || !$quantity || !$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        if ($quantity <= 0) {
            // Nếu số lượng <= 0, xóa khỏi giỏ
            $deleteQuery = "DELETE FROM GIOHANG WHERE cart_id = ? AND nguoidung_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param("is", $cartId, $userId);
            $deleteStmt->execute();
            $affectedRows = $deleteStmt->affected_rows;
            $deleteStmt->close();
            
            if ($affectedRows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã xóa khỏi giỏ hàng',
                    'deleted' => true
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy item trong giỏ hàng'
                ]);
            }
            return;
        }
        
        // Cập nhật số lượng
        $updateQuery = "UPDATE GIOHANG SET quantity = ?, updated_at = NOW() 
                       WHERE cart_id = ? AND nguoidung_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("iis", $quantity, $cartId, $userId);
        $updateStmt->execute();
        $affectedRows = $updateStmt->affected_rows;
        $updateStmt->close();
        
        if ($affectedRows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật giỏ hàng'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy item trong giỏ hàng'
            ]);
        }

    } catch (Exception $e) {
        error_log("Update Cart Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi cập nhật giỏ hàng']);
    }
}

// ============ XÓA KHỎI GIỎ HÀNG ============
function handleRemoveFromCart($conn) {
    try {
        $cartId = $_GET['cart_id'] ?? null;
        $userId = $_GET['user_id'] ?? null;
        
        if (!$cartId || !$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu cart_id hoặc user_id']);
            return;
        }
        
        $deleteQuery = "DELETE FROM GIOHANG WHERE cart_id = ? AND nguoidung_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("is", $cartId, $userId);
        $deleteStmt->execute();
        $affectedRows = $deleteStmt->affected_rows;
        $deleteStmt->close();
        
        if ($affectedRows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi giỏ hàng'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy item trong giỏ hàng'
            ]);
        }

    } catch (Exception $e) {
        error_log("Remove from Cart Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi xóa khỏi giỏ hàng']);
    }
}

// ============ TÌM KIẾM MÓN ĂN ============
function handleSearchFoods($conn) {
    try {
        $searchTerm = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
        $offset = ($page - 1) * $limit;
        
        // Xây dựng câu truy vấn
        $query = "SELECT 
                    monan_id as id,
                    ten_mon as name,
                    mo_ta as description,
                    gia as price,
                    CONCAT(FORMAT(gia, 0), 'đ') as price_formatted,
                    hinh_anh as image_url,
                    danh_gia_tb as avg_rating,
                    danh_muc as category,
                    so_luong_da_ban as sold_count,
                    CASE 
                        WHEN trang_thai = 'het_hang' THEN 0
                        ELSE 100 
                    END as stock_quantity
                  FROM MONAN 
                  WHERE trang_thai = 'dang_ban'";
        
        $params = [];
        $types = '';
        
        if (!empty($searchTerm)) {
            $query .= " AND (ten_mon LIKE ? OR mo_ta LIKE ?)";
            $searchTerm = "%{$searchTerm}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        if (!empty($category)) {
            $query .= " AND danh_muc = ?";
            $params[] = str_replace('-', '_', $category);
            $types .= 's';
        }
        
        // Đếm tổng số bản ghi
        $countQuery = "SELECT COUNT(*) as total FROM MONAN WHERE trang_thai = 'dang_ban'";
        if (!empty($searchTerm)) {
            $countQuery .= " AND (ten_mon LIKE ? OR mo_ta LIKE ?)";
        }
        if (!empty($category)) {
            $countQuery .= " AND danh_muc = ?";
        }
        
        $countStmt = $conn->prepare($countQuery);
        if ($params) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Lấy dữ liệu phân trang
        $query .= " ORDER BY so_luong_da_ban DESC, danh_gia_tb DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $foods = [];
        while ($row = $result->fetch_assoc()) {
            // Xử lý dữ liệu
            $row['short_description'] = strlen($row['description']) > 100 
                ? substr($row['description'], 0, 100) . '...' 
                : $row['description'];
            
            if (empty($row['image_url'])) {
                $row['image_url'] = getDefaultFoodImage();
            }
            
            $row['category_name'] = getCategoryName($row['category']);
            
            $foods[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $foods,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } catch (Exception $e) {
        error_log("Search Foods Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi tìm kiếm món ăn']);
    }
}

// Hàm hỗ trợ lấy ảnh mặc định
function getDefaultFoodImage() {
    $defaultImages = [
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&auto=format&fit=crop'
    ];
    
    return $defaultImages[array_rand($defaultImages)];
}

// Hàm hỗ trợ lấy tên danh mục
function getCategoryName($categorySlug) {
    $names = [
        'mon_chinh' => 'Món chính',
        'mon_phu' => 'Món phụ',
        'do_uong' => 'Đồ uống',
        'trang_mieng' => 'Tráng miệng',
        'khai_vi' => 'Khai vị',
        'combo' => 'Combo'
    ];
    
    return $names[$categorySlug] ?? $categorySlug;
}

$conn->close();
?>