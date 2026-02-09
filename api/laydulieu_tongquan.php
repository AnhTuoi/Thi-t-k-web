<?php
// api/laydulieu_tongquan.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../connect.php';

// Hàm lấy kết nối
$conn = getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối đến cơ sở dữ liệu']);
    exit;
}

// Xử lý các action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_dashboard_overview':
            getDashboardOverview($conn);
            break;
            
        case 'get_revenue_statistics':
            getRevenueStatistics($conn);
            break;
            
        case 'get_order_statistics':
            getOrderStatistics($conn);
            break;
            
        case 'get_user_statistics':
            getUserStatistics($conn);
            break;
            
        case 'get_top_foods':
            getTopFoods($conn);
            break;
            
        case 'get_recent_orders':
            getRecentOrders($conn);
            break;
            
        case 'get_dashboard_summary':
            getDashboardSummary($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Error in laydulieu_tongquan.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
}

$conn->close();

// Hàm lấy tổng quan dashboard
function getDashboardOverview($conn) {
    $today = date('Y-m-d');
    $firstDayOfMonth = date('Y-m-01');
    $firstDayOfYear = date('Y-01-01');
    
    // 1. Tổng doanh thu theo ngày, tháng, năm
    $revenueQueries = [
        'daily' => "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue FROM DONHANG 
                   WHERE DATE(ngay_tao) = '$today' AND trang_thai_donhang = 'da_giao'",
        
        'monthly' => "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue FROM DONHANG 
                     WHERE ngay_tao >= '$firstDayOfMonth' AND trang_thai_donhang = 'da_giao'",
        
        'yearly' => "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue FROM DONHANG 
                    WHERE ngay_tao >= '$firstDayOfYear' AND trang_thai_donhang = 'da_giao'"
    ];
    
    $revenue = [];
    foreach ($revenueQueries as $period => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $revenue[$period] = (float)$row['revenue'];
        } else {
            $revenue[$period] = 0;
        }
    }
    
    // 2. Tổng số đơn hàng
    $orderQueries = [
        'total' => "SELECT COUNT(*) as count FROM DONHANG",
        'today' => "SELECT COUNT(*) as count FROM DONHANG WHERE DATE(ngay_tao) = '$today'",
        'pending' => "SELECT COUNT(*) as count FROM DONHANG WHERE trang_thai_donhang = 'cho_xac_nhan'",
        'delivered' => "SELECT COUNT(*) as count FROM DONHANG WHERE trang_thai_donhang = 'da_giao'"
    ];
    
    $orders = [];
    foreach ($orderQueries as $key => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $orders[$key] = (int)$row['count'];
        } else {
            $orders[$key] = 0;
        }
    }
    
    // 3. Tổng số người dùng
    $userQueries = [
        'total' => "SELECT COUNT(*) as count FROM NGUOIDUNG",
        'customers' => "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = 'khach_hang'",
        'staff' => "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = 'nhan_vien'",
        'active' => "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE trang_thai = 'hoat_dong'"
    ];
    
    $users = [];
    foreach ($userQueries as $key => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $users[$key] = (int)$row['count'];
        } else {
            $users[$key] = 0;
        }
    }
    
    // 4. Tổng số món ăn
    $foodQueries = [
        'total' => "SELECT COUNT(*) as count FROM MONAN",
        'available' => "SELECT COUNT(*) as count FROM MONAN WHERE trang_thai = 'dang_ban'",
        'out_of_stock' => "SELECT COUNT(*) as count FROM MONAN WHERE trang_thai = 'het_hang'"
    ];
    
    $foods = [];
    foreach ($foodQueries as $key => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $foods[$key] = (int)$row['count'];
        } else {
            $foods[$key] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'revenue' => $revenue,
            'orders' => $orders,
            'users' => $users,
            'foods' => $foods,
            'periods' => [
                'today' => $today,
                'month' => date('m/Y'),
                'year' => date('Y')
            ]
        ]
    ]);
}

// Hàm lấy thống kê doanh thu theo tháng
function getRevenueStatistics($conn) {
    $months = [];
    $revenues = [];
    
    // Lấy doanh thu 12 tháng gần nhất
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = $month;
        
        $firstDay = date('Y-m-01', strtotime($month));
        $lastDay = date('Y-m-t', strtotime($month));
        
        $query = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue 
                  FROM DONHANG 
                  WHERE ngay_tao >= '$firstDay' 
                  AND ngay_tao <= '$lastDay' 
                  AND trang_thai_donhang = 'da_giao'";
        
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $revenues[] = (float)$row['revenue'];
        } else {
            $revenues[] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'months' => $months,
            'revenues' => $revenues
        ]
    ]);
}

// Hàm lấy thống kê đơn hàng
function getOrderStatistics($conn) {
    $statusCounts = [];
    $statuses = ['cho_xac_nhan', 'da_xac_nhan', 'dang_giao', 'da_giao', 'da_huy'];
    
    foreach ($statuses as $status) {
        $query = "SELECT COUNT(*) as count FROM DONHANG WHERE trang_thai_donhang = '$status'";
        $result = $conn->query($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $statusCounts[$status] = (int)$row['count'];
        } else {
            $statusCounts[$status] = 0;
        }
    }
    
    // Đếm đơn hàng theo ngày trong tuần
    $dailyOrders = [];
    $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    foreach ($daysOfWeek as $day) {
        $query = "SELECT COUNT(*) as count FROM DONHANG 
                 WHERE DAYNAME(ngay_tao) = '$day' 
                 AND ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $dailyOrders[$day] = (int)$row['count'];
        } else {
            $dailyOrders[$day] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'status_counts' => $statusCounts,
            'daily_orders' => $dailyOrders,
            'status_labels' => [
                'cho_xac_nhan' => 'Chờ xác nhận',
                'da_xac_nhan' => 'Đã xác nhận',
                'dang_giao' => 'Đang giao',
                'da_giao' => 'Đã giao',
                'da_huy' => 'Đã hủy'
            ]
        ]
    ]);
}

// Hàm lấy thống kê người dùng
function getUserStatistics($conn) {
    // Thống kê người dùng theo vai trò
    $roleStats = [];
    $roles = ['khach_hang', 'nhan_vien', 'quan_tri'];
    
    foreach ($roles as $role) {
        $query = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = '$role'";
        $result = $conn->query($query);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $roleStats[$role] = (int)$row['count'];
        } else {
            $roleStats[$role] = 0;
        }
    }
    
    // Thống kê người dùng mới theo tháng
    $monthlyNewUsers = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $firstDay = date('Y-m-01', strtotime($month));
        $lastDay = date('Y-m-t', strtotime($month));
        
        $query = "SELECT COUNT(*) as count FROM NGUOIDUNG 
                 WHERE ngay_tao >= '$firstDay' AND ngay_tao <= '$lastDay'";
        
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $monthlyNewUsers[$month] = (int)$row['count'];
        } else {
            $monthlyNewUsers[$month] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'role_stats' => $roleStats,
            'monthly_new_users' => $monthlyNewUsers,
            'role_labels' => [
                'khach_hang' => 'Khách hàng',
                'nhan_vien' => 'Nhân viên',
                'quan_tri' => 'Quản trị'
            ]
        ]
    ]);
}

// Hàm lấy top món ăn bán chạy
function getTopFoods($conn) {
    $limit = $_GET['limit'] ?? 10;
    $limit = min(max((int)$limit, 1), 20); // Giới hạn 1-20
    
    $query = "SELECT m.monan_id, m.ten_mon, m.gia, m.hinh_anh, 
                     COALESCE(SUM(c.so_luong), 0) as total_sold,
                     COALESCE(AVG(d.diem_danhgia), 0) as avg_rating
              FROM MONAN m
              LEFT JOIN CHITIETDONHANG c ON m.monan_id = c.monan_id
              LEFT JOIN DANHGIA d ON m.monan_id = d.monan_id AND d.trang_thai = 'da_duyet'
              GROUP BY m.monan_id, m.ten_mon, m.gia, m.hinh_anh
              ORDER BY total_sold DESC, avg_rating DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $topFoods = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $topFoods[] = [
                'id' => $row['monan_id'],
                'name' => $row['ten_mon'],
                'price' => (float)$row['gia'],
                'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
                'image_url' => $row['hinh_anh'] ?: 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop',
                'total_sold' => (int)$row['total_sold'],
                'avg_rating' => round((float)$row['avg_rating'], 1)
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $topFoods
    ]);
}

// Hàm lấy đơn hàng gần đây
function getRecentOrders($conn) {
    $limit = $_GET['limit'] ?? 5;
    $limit = min(max((int)$limit, 1), 20);
    
    $query = "SELECT d.donhang_id, d.nguoidung_id, u.hoten, d.tong_cuoi_cung, 
                     d.trang_thai_donhang, d.ngay_tao,
                     COUNT(c.monan_id) as item_count
              FROM DONHANG d
              JOIN NGUOIDUNG u ON d.nguoidung_id = u.nguoidung_id
              LEFT JOIN CHITIETDONHANG c ON d.donhang_id = c.donhang_id
              GROUP BY d.donhang_id, d.nguoidung_id, u.hoten, d.tong_cuoi_cung, 
                       d.trang_thai_donhang, d.ngay_tao
              ORDER BY d.ngay_tao DESC
              LIMIT $limit";
    
    $result = $conn->query($query);
    $recentOrders = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentOrders[] = [
                'id' => $row['donhang_id'],
                'customer_id' => $row['nguoidung_id'],
                'customer_name' => $row['hoten'],
                'total' => (float)$row['tong_cuoi_cung'],
                'total_formatted' => number_format($row['tong_cuoi_cung'], 0, ',', '.') . 'đ',
                'status' => $row['trang_thai_donhang'],
                'status_text' => getOrderStatusText($row['trang_thai_donhang']),
                'item_count' => (int)$row['item_count'],
                'order_date' => $row['ngay_tao'],
                'order_date_formatted' => date('d/m/Y H:i', strtotime($row['ngay_tao']))
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $recentOrders
    ]);
}

// Hàm lấy tổng quan chi tiết
function getDashboardSummary($conn) {
    // Tổng doanh thu
    $totalRevenueQuery = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as total 
                         FROM DONHANG 
                         WHERE trang_thai_donhang = 'da_giao'";
    
    $totalRevenueResult = $conn->query($totalRevenueQuery);
    $totalRevenue = $totalRevenueResult ? (float)$totalRevenueResult->fetch_assoc()['total'] : 0;
    
    // Tổng đơn hàng
    $totalOrdersQuery = "SELECT COUNT(*) as total FROM DONHANG";
    $totalOrdersResult = $conn->query($totalOrdersQuery);
    $totalOrders = $totalOrdersResult ? (int)$totalOrdersResult->fetch_assoc()['total'] : 0;
    
    // Tổng người dùng
    $totalUsersQuery = "SELECT COUNT(*) as total FROM NGUOIDUNG";
    $totalUsersResult = $conn->query($totalUsersQuery);
    $totalUsers = $totalUsersResult ? (int)$totalUsersResult->fetch_assoc()['total'] : 0;
    
    // Đơn hàng hôm nay
    $today = date('Y-m-d');
    $todayOrdersQuery = "SELECT COUNT(*) as total FROM DONHANG WHERE DATE(ngay_tao) = '$today'";
    $todayOrdersResult = $conn->query($todayOrdersQuery);
    $todayOrders = $todayOrdersResult ? (int)$todayOrdersResult->fetch_assoc()['total'] : 0;
    
    // Doanh thu hôm nay
    $todayRevenueQuery = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as total 
                         FROM DONHANG 
                         WHERE DATE(ngay_tao) = '$today' 
                         AND trang_thai_donhang = 'da_giao'";
    
    $todayRevenueResult = $conn->query($todayRevenueQuery);
    $todayRevenue = $todayRevenueResult ? (float)$todayRevenueResult->fetch_assoc()['total'] : 0;
    
    // Người dùng mới hôm nay
    $newUsersTodayQuery = "SELECT COUNT(*) as total FROM NGUOIDUNG WHERE DATE(ngay_tao) = '$today'";
    $newUsersTodayResult = $conn->query($newUsersTodayQuery);
    $newUsersToday = $newUsersTodayResult ? (int)$newUsersTodayResult->fetch_assoc()['total'] : 0;
    
    // Đơn hàng đang chờ xử lý
    $pendingOrdersQuery = "SELECT COUNT(*) as total FROM DONHANG WHERE trang_thai_donhang IN ('cho_xac_nhan', 'da_xac_nhan', 'dang_giao')";
    $pendingOrdersResult = $conn->query($pendingOrdersQuery);
    $pendingOrders = $pendingOrdersResult ? (int)$pendingOrdersResult->fetch_assoc()['total'] : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'total_orders' => $totalOrders,
            'total_users' => $totalUsers,
            'today_orders' => $todayOrders,
            'today_revenue' => $todayRevenue,
            'today_revenue_formatted' => number_format($todayRevenue, 0, ',', '.') . 'đ',
            'new_users_today' => $newUsersToday,
            'pending_orders' => $pendingOrders,
            'date' => date('d/m/Y')
        ]
    ]);
}

// Hàm chuyển đổi trạng thái đơn hàng sang tiếng Việt
function getOrderStatusText($status) {
    $statusMap = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'dang_giao' => 'Đang giao',
        'da_giao' => 'Đã giao',
        'da_huy' => 'Đã hủy'
    ];
    
    return $statusMap[$status] ?? $status;
}
?>