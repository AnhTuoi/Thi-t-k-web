<?php
// api/home.php - API xử lý dữ liệu trang chủ
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
require_once dirname(__DIR__) . '/config/connect.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Xử lý action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_categories':
        handleGetCategories($conn);
        break;
    case 'get_popular_foods':
        handleGetPopularFoods($conn);
        break;
    case 'get_foods_by_category':
        handleGetFoodsByCategory($conn);
        break;
    case 'get_foods_by_main_categories':  // THÊM MỚI: Lấy món ăn theo các danh mục chính
        handleGetFoodsByMainCategories($conn);
        break;
    case 'get_promotions':
        handleGetPromotions($conn);
        break;
    case 'get_stats':
        handleGetStats($conn);
        break;
    case 'get_cart_count':
        handleGetCartCount($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

// ============ LẤY DANH MỤC ============
function handleGetCategories($conn) {
    try {
        // Danh sách danh mục từ database
        $query = "SELECT DISTINCT danh_muc FROM MONAN WHERE trang_thai = 'dang_ban'";
        $result = $conn->query($query);
        
        $categories = [];
        $categoryNames = [
            'mon_chinh' => ['name' => 'Món chính', 'icon' => 'dinner_dining'],
            'mon_phu' => ['name' => 'Món phụ', 'icon' => 'lunch_dining'],
            'do_uong' => ['name' => 'Đồ uống', 'icon' => 'local_bar'],
            'trang_mieng' => ['name' => 'Tráng miệng', 'icon' => 'icecream'],
            'khai_vi' => ['name' => 'Khai vị', 'icon' => 'tapas'],
            'combo' => ['name' => 'Combo', 'icon' => 'package']
        ];
        
        $index = 1;
        while ($row = $result->fetch_assoc()) {
            $categorySlug = $row['danh_muc'];
            if (isset($categoryNames[$categorySlug])) {
                $categories[] = [
                    'id' => $index++,
                    'name' => $categoryNames[$categorySlug]['name'],
                    'slug' => $categorySlug,
                    'icon' => $categoryNames[$categorySlug]['icon']
                ];
            }
        }
        
        // Nếu không có danh mục, trả về mẫu
        if (empty($categories)) {
            $categories = [
                ['id' => 1, 'name' => 'Món chính', 'slug' => 'mon_chinh', 'icon' => 'dinner_dining'],
                ['id' => 2, 'name' => 'Món phụ', 'slug' => 'mon_phu', 'icon' => 'lunch_dining'],
                ['id' => 3, 'name' => 'Đồ uống', 'slug' => 'do_uong', 'icon' => 'local_bar'],
                ['id' => 4, 'name' => 'Tráng miệng', 'slug' => 'trang_mieng', 'icon' => 'icecream'],
                ['id' => 5, 'name' => 'Khai vị', 'slug' => 'khai_vi', 'icon' => 'tapas'],
                ['id' => 6, 'name' => 'Combo', 'slug' => 'combo', 'icon' => 'package']
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $categories]);
        
    } catch (Exception $e) {
        error_log("Get Categories Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy danh mục']);
    }
}

// ============ LẤY MÓN ĂN PHỔ BIẾN ============
function handleGetPopularFoods($conn) {
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
        
        $query = "SELECT 
                    monan_id,
                    ten_mon,
                    mo_ta,
                    gia,
                    hinh_anh,
                    danh_gia_tb,
                    so_luong_da_ban,
                    trang_thai
                  FROM MONAN 
                  WHERE trang_thai = 'dang_ban'
                  ORDER BY so_luong_da_ban DESC, danh_gia_tb DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $foods = [];
        while ($row = $result->fetch_assoc()) {
            $food = [
                'id' => $row['monan_id'],
                'name' => $row['ten_mon'],
                'description' => $row['mo_ta'],
                'price' => floatval($row['gia']),
                'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
                'image_url' => $row['hinh_anh'] ?: getDefaultFoodImage(),
                'avg_rating' => floatval($row['danh_gia_tb']),
                'sold_count' => intval($row['so_luong_da_ban']),
                'stock_quantity' => $row['trang_thai'] === 'het_hang' ? 0 : 100
            ];
            $foods[] = $food;
        }
        
        $stmt->close();
        
        // Nếu không có dữ liệu, trả về mẫu từ database mẫu
        if (empty($foods)) {
            $foods = getSampleFoodsFromDB($conn);
        }
        
        echo json_encode(['success' => true, 'data' => $foods]);
        
    } catch (Exception $e) {
        error_log("Get Popular Foods Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy món ăn phổ biến']);
    }
}

// ============ LẤY MÓN ĂN THEO DANH MỤC ============
function handleGetFoodsByCategory($conn) {
    try {
        $category = $_GET['category'] ?? '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
        
        if (empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu danh mục']);
            return;
        }
        
        $query = "SELECT 
                    monan_id,
                    ten_mon,
                    mo_ta,
                    gia,
                    hinh_anh,
                    danh_gia_tb,
                    so_luong_da_ban,
                    trang_thai
                  FROM MONAN 
                  WHERE danh_muc = ? 
                  AND trang_thai = 'dang_ban'
                  ORDER BY so_luong_da_ban DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("si", $category, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $foods = [];
        while ($row = $result->fetch_assoc()) {
            $food = [
                'id' => $row['monan_id'],
                'name' => $row['ten_mon'],
                'description' => $row['mo_ta'],
                'price' => floatval($row['gia']),
                'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
                'image_url' => $row['hinh_anh'] ?: getDefaultFoodImage(),
                'avg_rating' => floatval($row['danh_gia_tb']),
                'sold_count' => intval($row['so_luong_da_ban']),
                'stock_quantity' => $row['trang_thai'] === 'het_hang' ? 0 : 100
            ];
            $foods[] = $food;
        }
        
        $stmt->close();
        
        echo json_encode(['success' => true, 'data' => $foods]);
        
    } catch (Exception $e) {
        error_log("Get Foods By Category Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy món ăn theo danh mục']);
    }
}

// ============ LẤY MÓN ĂN THEO CÁC DANH MỤC CHÍNH ============
function handleGetFoodsByMainCategories($conn) {
    try {
        // Danh sách các danh mục chính cần hiển thị
        $mainCategories = [
            1 => 'mon_chinh',
            2 => 'mon_phu',
            3 => 'do_uong',
            4 => 'trang_mieng',
            5 => 'combo'
        ];
        
        $result = [];
        
        foreach ($mainCategories as $id => $categorySlug) {
            $query = "SELECT 
                        monan_id,
                        ten_mon,
                        mo_ta,
                        gia,
                        hinh_anh,
                        danh_gia_tb,
                        so_luong_da_ban,
                        trang_thai
                      FROM MONAN 
                      WHERE danh_muc = ? 
                      AND trang_thai = 'dang_ban'
                      ORDER BY so_luong_da_ban DESC
                      LIMIT 8";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                continue;
            }
            
            $stmt->bind_param("s", $categorySlug);
            $stmt->execute();
            $foodsResult = $stmt->get_result();
            
            $foods = [];
            while ($row = $foodsResult->fetch_assoc()) {
                $food = [
                    'id' => $row['monan_id'],
                    'name' => $row['ten_mon'],
                    'description' => $row['mo_ta'],
                    'price' => floatval($row['gia']),
                    'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
                    'image_url' => $row['hinh_anh'] ?: getDefaultFoodImage(),
                    'avg_rating' => floatval($row['danh_gia_tb']),
                    'sold_count' => intval($row['so_luong_da_ban']),
                    'stock_quantity' => $row['trang_thai'] === 'het_hang' ? 0 : 100
                ];
                $foods[] = $food;
            }
            
            $stmt->close();
            
            // Chỉ thêm category nếu có món ăn
            if (!empty($foods)) {
                $categoryNames = [
                    'mon_chinh' => 'Món chính',
                    'mon_phu' => 'Món phụ',
                    'do_uong' => 'Đồ uống',
                    'trang_mieng' => 'Tráng miệng',
                    'khai_vi' => 'Khai vị',
                    'combo' => 'Combo'
                ];
                
                $result[] = [
                    'id' => $id,
                    'name' => $categoryNames[$categorySlug] ?? ucfirst(str_replace('_', ' ', $categorySlug)),
                    'slug' => $categorySlug,
                    'icon' => 'restaurant_menu',
                    'foods' => $foods
                ];
            }
        }
        
        // Nếu không có dữ liệu, trả về mẫu
        if (empty($result)) {
            $result = getSampleCategoryFoods($conn);
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("Get Foods By Main Categories Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy món ăn theo danh mục chính']);
    }
}

// ============ LẤY KHUYẾN MÃI ============
function handleGetPromotions($conn) {
    try {
        $currentDate = date('Y-m-d');
        
        $query = "SELECT 
                    khuyenmai_id,
                    ma_khuyenmai,
                    mo_ta,
                    loai_giam_gia,
                    gia_tri_giam,
                    don_hang_toi_thieu,
                    giam_toi_da,
                    ngay_bat_dau,
                    ngay_ket_thuc
                  FROM KHUYENMAI 
                  WHERE trang_thai = 'dang_ap_dung'
                  AND ? BETWEEN ngay_bat_dau AND ngay_ket_thuc
                  ORDER BY gia_tri_giam DESC
                  LIMIT 3";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed");
        }
        
        $stmt->bind_param("s", $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $promotions = [];
        while ($row = $result->fetch_assoc()) {
            // Tính số ngày còn lại
            $endDate = new DateTime($row['ngay_ket_thuc']);
            $currentDateObj = new DateTime($currentDate);
            $daysLeft = $currentDateObj->diff($endDate)->days;
            
            // Tạo tiêu đề
            $title = '';
            if ($row['loai_giam_gia'] == 'phan_tram') {
                $title = "Giảm {$row['gia_tri_giam']}%";
                if ($row['don_hang_toi_thieu'] > 0) {
                    $title .= " cho đơn từ " . number_format($row['don_hang_toi_thieu'], 0, ',', '.') . "đ";
                }
            } else {
                $title = "Giảm " . number_format($row['gia_tri_giam'], 0, ',', '.') . "đ";
                if ($row['don_hang_toi_thieu'] > 0) {
                    $title .= " cho đơn từ " . number_format($row['don_hang_toi_thieu'], 0, ',', '.') . "đ";
                }
            }
            
            $promotion = [
                'id' => $row['khuyenmai_id'],
                'code' => $row['ma_khuyenmai'],
                'title' => $title,
                'description' => $row['mo_ta'],
                'discount_type' => $row['loai_giam_gia'],
                'discount_value' => floatval($row['gia_tri_giam']),
                'min_order_amount' => floatval($row['don_hang_toi_thieu']),
                'max_discount' => $row['giam_toi_da'] ? floatval($row['giam_toi_da']) : null,
                'start_date' => $row['ngay_bat_dau'],
                'end_date' => $row['ngay_ket_thuc'],
                'days_left' => $daysLeft
            ];
            $promotions[] = $promotion;
        }
        
        $stmt->close();
        
        // Nếu không có khuyến mãi, trả về mẫu
        if (empty($promotions)) {
            $promotions = getSamplePromotions();
        }
        
        echo json_encode(['success' => true, 'data' => $promotions]);
        
    } catch (Exception $e) {
        error_log("Get Promotions Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy khuyến mãi']);
    }
}

// ============ LẤY THỐNG KÊ ============
function handleGetStats($conn) {
    try {
        // Tổng số món ăn
        $foodsQuery = "SELECT COUNT(*) as total FROM MONAN WHERE trang_thai = 'dang_ban'";
        $foodsResult = $conn->query($foodsQuery);
        $totalFoods = $foodsResult->fetch_assoc()['total'] ?? 0;
        
        // Tổng số cửa hàng (giả định 1)
        $totalRestaurants = 1;
        
        // Tổng số đơn hàng đã giao
        $deliveriesQuery = "SELECT COUNT(*) as total FROM DONHANG WHERE trang_thai_donhang = 'da_giao'";
        $deliveriesResult = $conn->query($deliveriesQuery);
        $totalDeliveries = $deliveriesResult->fetch_assoc()['total'] ?? 0;
        
        // Tổng số người dùng
        $usersQuery = "SELECT COUNT(*) as total FROM NGUOIDUNG WHERE trang_thai = 'hoat_dong' AND vai_tro = 'khach_hang'";
        $usersResult = $conn->query($usersQuery);
        $totalUsers = $usersResult->fetch_assoc()['total'] ?? 0;
        
        $stats = [
            'total_foods' => intval($totalFoods),
            'total_restaurants' => intval($totalRestaurants),
            'total_deliveries' => intval($totalDeliveries),
            'total_users' => intval($totalUsers)
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        error_log("Get Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy thống kê']);
    }
}

// ============ LẤY SỐ LƯỢNG GIỎ HÀNG ============
function handleGetCartCount($conn) {
    try {
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => true, 'count' => 0]);
            return;
        }
        
        // Giả sử có bảng GIOHANG
        $query = "SELECT COALESCE(SUM(so_luong), 0) as total 
                  FROM GIOHANG 
                  WHERE nguoidung_id = ?";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['total'] ?? 0;
            $stmt->close();
        } else {
            // Nếu bảng không tồn tại, trả về 0
            $count = 0;
        }
        
        echo json_encode(['success' => true, 'count' => $count]);
        
    } catch (Exception $e) {
        error_log("Get Cart Count Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi lấy số lượng giỏ hàng']);
    }
}

// ============ HÀM HỖ TRỢ ============

function getDefaultFoodImage() {
    $images = [
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&auto=format&fit=crop'
    ];
    return $images[array_rand($images)];
}

function getSampleFoodsFromDB($conn) {
    // Lấy dữ liệu mẫu từ database nếu có
    $query = "SELECT * FROM MONAN LIMIT 3";
    $result = $conn->query($query);
    
    $foods = [];
    while ($row = $result->fetch_assoc()) {
        $foods[] = [
            'id' => $row['monan_id'],
            'name' => $row['ten_mon'],
            'description' => $row['mo_ta'],
            'price' => floatval($row['gia']),
            'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
            'image_url' => $row['hinh_anh'] ?: getDefaultFoodImage(),
            'avg_rating' => floatval($row['danh_gia_tb']),
            'sold_count' => intval($row['so_luong_da_ban']),
            'stock_quantity' => $row['trang_thai'] === 'het_hang' ? 0 : 100
        ];
    }
    
    return $foods;
}

function getSampleCategoryFoods($conn) {
    // Tạo dữ liệu mẫu cho các danh mục
    $sampleCategories = [
        [
            'id' => 1,
            'name' => 'Món chính',
            'slug' => 'mon_chinh',
            'icon' => 'dinner_dining',
            'foods' => getSampleFoodsFromDB($conn)
        ]
    ];
    
    return $sampleCategories;
}

function getSamplePromotions() {
    $currentDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+15 days'));
    
    return [
        [
            'id' => 'KM001',
            'code' => 'GIAM10',
            'title' => 'Giảm 10% cho đơn từ 100k',
            'description' => 'Áp dụng cho tất cả đơn hàng từ 100.000đ trở lên',
            'discount_type' => 'phan_tram',
            'discount_value' => 10,
            'min_order_amount' => 100000,
            'max_discount' => 20000,
            'start_date' => $currentDate,
            'end_date' => $endDate,
            'days_left' => 15
        ]
    ];
}
?>