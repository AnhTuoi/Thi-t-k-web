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
    case 'get_categories':
        handleGetCategories($conn);
        break;
    case 'get_popular_foods':
        handleGetPopularFoods($conn);
        break;
    case 'get_foods_by_main_categories':
        handleGetFoodsByMainCategories($conn);
        break;
    case 'get_promotions':
        handleGetPromotions($conn);
        break;
    case 'get_cart_count':
        handleGetCartCount($conn);
        break;
    case 'add_to_cart':
        handleAddToCart($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ============ LẤY DANH MỤC ============
function handleGetCategories($conn) {
    try {
        // Danh sách danh mục mẫu với icon
        $categories = [
            [
                'id' => 1,
                'name' => 'Món chính',
                'slug' => 'mon-chinh',
                'icon' => 'dinner_dining',
                'description' => 'Các món ăn chính đa dạng'
            ],
            [
                'id' => 2,
                'name' => 'Đồ ăn nhanh',
                'slug' => 'do-an-nhanh',
                'icon' => 'lunch_dining',
                'description' => 'Đồ ăn nhanh tiện lợi'
            ],
            [
                'id' => 3,
                'name' => 'Đồ uống',
                'slug' => 'do-uong',
                'icon' => 'local_bar',
                'description' => 'Nước giải khát đa dạng'
            ],
            [
                'id' => 4,
                'name' => 'Tráng miệng',
                'slug' => 'trang-mieng',
                'icon' => 'icecream',
                'description' => 'Các món tráng miệng hấp dẫn'
            ],
            [
                'id' => 5,
                'name' => 'Combo',
                'slug' => 'combo',
                'icon' => 'package',
                'description' => 'Combo tiết kiệm'
            ],
            [
                'id' => 6,
                'name' => 'Khai vị',
                'slug' => 'khai-vi',
                'icon' => 'tapas',
                'description' => 'Món khai vị hấp dẫn'
            ]
        ];

        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);

    } catch (Exception $e) {
        error_log("Get Categories Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy danh mục']);
    }
}

// ============ LẤY MÓN ĂN PHỔ BIẾN ============
function handleGetPopularFoods($conn) {
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;
        
        $query = "SELECT 
                    monan_id as id,
                    ten_mon as name,
                    mo_ta as description,
                    gia as price,
                    CONCAT(FORMAT(gia, 0), 'đ') as price_formatted,
                    hinh_anh as image_url,
                    danh_gia_tb as avg_rating,
                    trang_thai,
                    so_luong_da_ban as sold_count,
                    CASE 
                        WHEN trang_thai = 'het_hang' THEN 0
                        ELSE 100 
                    END as stock_quantity,
                    ngay_tao as created_at
                  FROM MONAN 
                  WHERE trang_thai = 'dang_ban'
                  ORDER BY so_luong_da_ban DESC, danh_gia_tb DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare Error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn']);
            return;
        }
        
        $stmt->bind_param("i", $limit);
        
        if (!$stmt->execute()) {
            error_log("Execute Error: " . $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn']);
            $stmt->close();
            return;
        }
        
        $result = $stmt->get_result();
        $foods = [];
        
        while ($row = $result->fetch_assoc()) {
            // Xử lý dữ liệu
            $row['short_description'] = strlen($row['description']) > 100 
                ? substr($row['description'], 0, 100) . '...' 
                : $row['description'];
            
            // Thêm URL ảnh mặc định nếu không có
            if (empty($row['image_url'])) {
                $row['image_url'] = getDefaultFoodImage();
            }
            
            $foods[] = $row;
        }
        
        $stmt->close();
        
        // Nếu không có dữ liệu, trả về món mẫu
        if (empty($foods)) {
            $foods = getSampleFoods();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $foods
        ]);

    } catch (Exception $e) {
        error_log("Get Popular Foods Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy món ăn phổ biến']);
    }
}

// ============ LẤY MÓN ĂN THEO DANH MỤC CHÍNH ============
function handleGetFoodsByMainCategories($conn) {
    try {
        // Danh mục chính cần lấy
        $mainCategories = [
            'mon_chinh' => 1,
            'mon_phu' => 2,  // Dùng cho "Đồ ăn nhanh"
            'do_uong' => 3,
            'trang_mieng' => 4,
            'combo' => 5
        ];
        
        $result = [];
        
        foreach ($mainCategories as $category => $categoryId) {
            $query = "SELECT 
                        monan_id as id,
                        ten_mon as name,
                        mo_ta as description,
                        gia as price,
                        CONCAT(FORMAT(gia, 0), 'đ') as price_formatted,
                        hinh_anh as image_url,
                        danh_gia_tb as avg_rating,
                        trang_thai,
                        CASE 
                            WHEN trang_thai = 'het_hang' THEN 0
                            ELSE 100 
                        END as stock_quantity
                      FROM MONAN 
                      WHERE danh_muc = ?
                      AND trang_thai = 'dang_ban'
                      ORDER BY so_luong_da_ban DESC
                      LIMIT 8";
            
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $category);
                
                if ($stmt->execute()) {
                    $foodsResult = $stmt->get_result();
                    $foods = [];
                    
                    while ($row = $foodsResult->fetch_assoc()) {
                        // Xử lý dữ liệu
                        $row['short_description'] = strlen($row['description']) > 80 
                            ? substr($row['description'], 0, 80) . '...' 
                            : $row['description'];
                        
                        if (empty($row['image_url'])) {
                            $row['image_url'] = getDefaultFoodImage();
                        }
                        
                        $foods[] = $row;
                    }
                    
                    $result[] = [
                        'id' => $categoryId,
                        'name' => getCategoryName($category),
                        'slug' => str_replace('_', '-', $category),
                        'foods' => $foods
                    ];
                }
                $stmt->close();
            }
        }
        
        // Nếu không có dữ liệu, trả về mẫu
        if (empty(array_filter($result, function($cat) { return !empty($cat['foods']); }))) {
            $result = getSampleCategoryFoods();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);

    } catch (Exception $e) {
        error_log("Get Foods By Categories Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy món ăn theo danh mục']);
    }
}

// ============ LẤY KHUYẾN MÃI ============
function handleGetPromotions($conn) {
    try {
        $currentDate = date('Y-m-d');
        
        $query = "SELECT 
                    khuyenmai_id as id,
                    ma_khuyenmai as code,
                    mo_ta as description,
                    loai_giam_gia as discount_type,
                    gia_tri_giam as discount_value,
                    don_hang_toi_thieu as min_order_amount,
                    giam_toi_da as max_discount,
                    gioi_han_su_dung as usage_limit,
                    so_lan_da_su_dung as used_count,
                    ngay_bat_dau as start_date,
                    ngay_ket_thuc as end_date,
                    trang_thai as status
                  FROM KHUYENMAI 
                  WHERE trang_thai = 'dang_ap_dung'
                  AND ngay_bat_dau <= ?
                  AND ngay_ket_thuc >= ?
                  ORDER BY gia_tri_giam DESC
                  LIMIT 3";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare Error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn']);
            return;
        }
        
        $stmt->bind_param("ss", $currentDate, $currentDate);
        
        if (!$stmt->execute()) {
            error_log("Execute Error: " . $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi thực thi truy vấn']);
            $stmt->close();
            return;
        }
        
        $result = $stmt->get_result();
        $promotions = [];
        
        while ($row = $result->fetch_assoc()) {
            // Tính số ngày còn lại
            $endDate = new DateTime($row['end_date']);
            $currentDateObj = new DateTime($currentDate);
            $daysLeft = $currentDateObj->diff($endDate)->days;
            
            // Tạo tiêu đề hấp dẫn
            if ($row['discount_type'] == 'phan_tram') {
                $title = "Giảm {$row['discount_value']}% đơn hàng";
                if ($row['min_order_amount'] > 0) {
                    $title .= " từ " . number_format($row['min_order_amount'], 0, ',', '.') . "đ";
                }
            } else {
                $title = "Giảm ngay " . number_format($row['discount_value'], 0, ',', '.') . "đ";
                if ($row['min_order_amount'] > 0) {
                    $title .= " cho đơn từ " . number_format($row['min_order_amount'], 0, ',', '.') . "đ";
                }
            }
            
            $row['title'] = $title;
            $row['days_left'] = $daysLeft;
            $promotions[] = $row;
        }
        
        $stmt->close();
        
        // Nếu không có khuyến mãi, trả về mẫu
        if (empty($promotions)) {
            $promotions = getSamplePromotions();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $promotions
        ]);

    } catch (Exception $e) {
        error_log("Get Promotions Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi lấy khuyến mãi']);
    }
}

// ============ LẤY SỐ LƯỢNG GIỎ HÀNG ============
function handleGetCartCount($conn) {
    try {
        // Lấy user_id từ session hoặc request
        $userId = null;
        
        if (isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['nguoidung_id'];
        } elseif (isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
        }
        
        if (!$userId) {
            echo json_encode([
                'success' => true,
                'count' => 0,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        // Giả sử có bảng GIOHANG
        // Trong thực tế, cần tạo bảng giỏ hàng
        // Tạm thời trả về 0
        echo json_encode([
            'success' => true,
            'count' => 0
        ]);

    } catch (Exception $e) {
        error_log("Get Cart Count Exception: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'count' => 0,
            'message' => $e->getMessage()
        ]);
    }
}

// ============ THÊM VÀO GIỎ HÀNG ============
function handleAddToCart($conn) {
    try {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }
        
        if (empty($data['user_id']) || empty($data['food_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin user hoặc món ăn']);
            return;
        }
        
        $userId = $data['user_id'];
        $foodId = $data['food_id'];
        $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
        
        // Kiểm tra món ăn tồn tại và còn hàng
        $checkQuery = "SELECT trang_thai FROM MONAN WHERE monan_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $foodId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
            return;
        }
        
        $food = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($food['trang_thai'] !== 'dang_ban') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Món ăn hiện không bán']);
            return;
        }
        
        // Trong thực tế: thêm vào bảng giỏ hàng
        // Tạm thời trả về thành công
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng'
        ]);

    } catch (Exception $e) {
        error_log("Add to Cart Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi khi thêm vào giỏ hàng']);
    }
}

// ============ HÀM HỖ TRỢ ============

function getCategoryName($categorySlug) {
    $names = [
        'mon_chinh' => 'Món chính',
        'mon_phu' => 'Đồ ăn nhanh',
        'do_uong' => 'Đồ uống',
        'trang_mieng' => 'Tráng miệng',
        'khai_vi' => 'Khai vị',
        'combo' => 'Combo'
    ];
    
    return $names[$categorySlug] ?? $categorySlug;
}

function getDefaultFoodImage() {
    // Danh sách ảnh mặc định từ Unsplash
    $defaultImages = [
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w-800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=800&auto=format&fit=crop'
    ];
    
    return $defaultImages[array_rand($defaultImages)];
}

function getSampleFoods() {
    return [
        [
            'id' => 'MA001',
            'name' => 'Phở Bò',
            'description' => 'Phở bò truyền thống với nước dùng đậm đà',
            'price' => 50000,
            'price_formatted' => '50.000đ',
            'original_price' => 55000,
            'original_price_formatted' => '55.000đ',
            'image_url' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800&auto=format&fit=crop',
            'avg_rating' => 4.8,
            'stock_quantity' => 50,
            'sold_count' => 234,
            'created_at' => '2024-01-15 10:30:00'
        ],
        [
            'id' => 'MA002',
            'name' => 'Cơm Gà Xối Mỡ',
            'description' => 'Cơm gà giòn rụm với da vàng óng',
            'price' => 45000,
            'price_formatted' => '45.000đ',
            'image_url' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=800&auto=format&fit=crop',
            'avg_rating' => 4.6,
            'stock_quantity' => 30,
            'sold_count' => 189,
            'created_at' => '2024-01-10 14:20:00'
        ],
        [
            'id' => 'MA003',
            'name' => 'Trà Đào Cam Sả',
            'description' => 'Trà đào thơm ngon với cam sả tươi mát',
            'price' => 25000,
            'price_formatted' => '25.000đ',
            'image_url' => 'https://images.unsplash.com/photo-1567306301408-9b74779a11af?w=800&auto=format&fit=crop',
            'avg_rating' => 4.5,
            'stock_quantity' => 100,
            'sold_count' => 456,
            'created_at' => '2024-01-05 09:15:00'
        ]
    ];
}

function getSampleCategoryFoods() {
    return [
        [
            'id' => 1,
            'name' => 'Món chính',
            'slug' => 'mon-chinh',
            'foods' => [
                [
                    'id' => 'MA001',
                    'name' => 'Phở Bò',
                    'description' => 'Phở bò truyền thống',
                    'price' => 50000,
                    'price_formatted' => '50.000đ',
                    'image_url' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?w=800&auto=format&fit=crop',
                    'avg_rating' => 4.8,
                    'stock_quantity' => 50
                ],
                [
                    'id' => 'MA002',
                    'name' => 'Cơm Gà Xối Mỡ',
                    'description' => 'Cơm gà giòn rụm',
                    'price' => 45000,
                    'price_formatted' => '45.000đ',
                    'image_url' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=800&auto=format&fit=crop',
                    'avg_rating' => 4.6,
                    'stock_quantity' => 30
                ]
            ]
        ],
        [
            'id' => 3,
            'name' => 'Đồ uống',
            'slug' => 'do-uong',
            'foods' => [
                [
                    'id' => 'MA003',
                    'name' => 'Trà Đào Cam Sả',
                    'description' => 'Trà đào thơm ngon',
                    'price' => 25000,
                    'price_formatted' => '25.000đ',
                    'image_url' => 'https://images.unsplash.com/photo-1567306301408-9b74779a11af?w=800&auto=format&fit=crop',
                    'avg_rating' => 4.5,
                    'stock_quantity' => 100
                ]
            ]
        ]
    ];
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
            'usage_limit' => 100,
            'used_count' => 45,
            'start_date' => $currentDate,
            'end_date' => $endDate,
            'status' => 'dang_ap_dung',
            'days_left' => 15
        ],
        [
            'id' => 'KM002',
            'code' => 'FREESHIP',
            'title' => 'Miễn phí vận chuyển',
            'description' => 'Miễn phí vận chuyển cho mọi đơn hàng',
            'discount_type' => 'so_tien_co_dinh',
            'discount_value' => 15000,
            'min_order_amount' => 0,
            'max_discount' => null,
            'usage_limit' => null,
            'used_count' => 23,
            'start_date' => $currentDate,
            'end_date' => $endDate,
            'status' => 'dang_ap_dung',
            'days_left' => 15
        ]
    ];
}

$conn->close();
?>