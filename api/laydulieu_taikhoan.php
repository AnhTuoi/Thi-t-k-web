<?php
// api/laydulieu_taikhoan.php
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
        case 'get_user_summary':
            getUserSummary($conn);
            break;
            
        case 'get_user_statistics':
            getUserStatistics($conn);
            break;
            
        case 'get_user_growth':
            getUserGrowth($conn);
            break;
            
        case 'get_user_activity':
            getUserActivity($conn);
            break;
            
        case 'get_user_by_role':
            getUserByRole($conn);
            break;
            
        case 'get_user_by_status':
            getUserByStatus($conn);
            break;
            
        case 'get_top_users':
            getTopUsers($conn);
            break;
            
        case 'get_recent_users':
            getRecentUsers($conn);
            break;
            
        case 'get_user_details':
            getUserDetails($conn);
            break;
            
        case 'get_login_statistics':
            getLoginStatistics($conn);
            break;
            
        case 'get_user_segments':
            getUserSegments($conn);
            break;
            
        case 'export_user_report':
            exportUserReport($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Error in laydulieu_taikhoan.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
}

$conn->close();

// Hàm lấy tổng quan người dùng
function getUserSummary($conn) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $thisMonth = date('Y-m-01');
    $lastMonth = date('Y-m-01', strtotime('-1 month'));
    $thisYear = date('Y-01-01');
    $lastYear = date('Y-01-01', strtotime('-1 year'));
    
    // Tổng số người dùng
    $totalUsersQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG";
    $totalUsersResult = $conn->query($totalUsersQuery);
    $totalUsers = $totalUsersResult ? (int)$totalUsersResult->fetch_assoc()['count'] : 0;
    
    // Người dùng mới hôm nay
    $newUsersTodayQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE DATE(ngay_tao) = '$today'";
    $newUsersTodayResult = $conn->query($newUsersTodayQuery);
    $newUsersToday = $newUsersTodayResult ? (int)$newUsersTodayResult->fetch_assoc()['count'] : 0;
    
    // Người dùng mới tháng này
    $newUsersMonthQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE ngay_tao >= '$thisMonth'";
    $newUsersMonthResult = $conn->query($newUsersMonthQuery);
    $newUsersMonth = $newUsersMonthResult ? (int)$newUsersMonthResult->fetch_assoc()['count'] : 0;
    
    // Người dùng mới năm nay
    $newUsersYearQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE ngay_tao >= '$thisYear'";
    $newUsersYearResult = $conn->query($newUsersYearQuery);
    $newUsersYear = $newUsersYearResult ? (int)$newUsersYearResult->fetch_assoc()['count'] : 0;
    
    // Người dùng hoạt động (có đơn hàng trong 30 ngày)
    $activeUsersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as count 
                        FROM DONHANG 
                        WHERE ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $activeUsersResult = $conn->query($activeUsersQuery);
    $activeUsers = $activeUsersResult ? (int)$activeUsersResult->fetch_assoc()['count'] : 0;
    
    // Người dùng VIP (có tổng chi tiêu > 1,000,000)
    $vipUsersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as count 
                     FROM DONHANG 
                     WHERE trang_thai_donhang = 'da_giao'
                     GROUP BY nguoidung_id 
                     HAVING SUM(tong_cuoi_cung) > 1000000";
    $vipUsersResult = $conn->query($vipUsersQuery);
    $vipUsers = $vipUsersResult ? (int)$vipUsersResult->fetch_assoc()['count'] : 0;
    
    // Người dùng theo vai trò
    $roleStats = [];
    $roles = ['khach_hang', 'nhan_vien', 'quan_tri'];
    
    foreach ($roles as $role) {
        $query = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = '$role'";
        $result = $conn->query($query);
        $roleStats[$role] = $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
    
    // Người dùng theo trạng thái
    $statusStats = [];
    $statuses = ['hoat_dong', 'vo_hieu_hoa'];
    
    foreach ($statuses as $status) {
        $query = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE trang_thai = '$status'";
        $result = $conn->query($query);
        $statusStats[$status] = $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => $totalUsers,
            'new_users_today' => $newUsersToday,
            'new_users_month' => $newUsersMonth,
            'new_users_year' => $newUsersYear,
            'active_users' => $activeUsers,
            'vip_users' => $vipUsers,
            'role_stats' => $roleStats,
            'status_stats' => $statusStats,
            'role_labels' => [
                'khach_hang' => 'Khách hàng',
                'nhan_vien' => 'Nhân viên',
                'quan_tri' => 'Quản trị'
            ],
            'status_labels' => [
                'hoat_dong' => 'Hoạt động',
                'vo_hieu_hoa' => 'Vô hiệu hóa'
            ]
        ]
    ]);
}

// Hàm lấy thống kê người dùng chi tiết
function getUserStatistics($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Người dùng mới theo ngày
    $dailyNewUsersQuery = "SELECT 
                            DATE(ngay_tao) as date,
                            COUNT(*) as new_users
                          FROM NGUOIDUNG
                          WHERE DATE(ngay_tao) BETWEEN ? AND ?
                          GROUP BY DATE(ngay_tao)
                          ORDER BY DATE(ngay_tao)";
    
    $stmt = $conn->prepare($dailyNewUsersQuery);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dailyNewUsers = [];
    $totalNewUsers = 0;
    
    while ($row = $result->fetch_assoc()) {
        $dailyNewUsers[] = [
            'date' => $row['date'],
            'date_formatted' => date('d/m/Y', strtotime($row['date'])),
            'new_users' => (int)$row['new_users']
        ];
        $totalNewUsers += (int)$row['new_users'];
    }
    
    // Người dùng hoạt động theo ngày
    $dailyActiveUsersQuery = "SELECT 
                                DATE(d.ngay_tao) as date,
                                COUNT(DISTINCT d.nguoidung_id) as active_users
                              FROM DONHANG d
                              WHERE DATE(d.ngay_tao) BETWEEN ? AND ?
                              GROUP BY DATE(d.ngay_tao)
                              ORDER BY DATE(d.ngay_tao)";
    
    $stmt = $conn->prepare($dailyActiveUsersQuery);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $activeResult = $stmt->get_result();
    
    $dailyActiveUsers = [];
    $totalActiveUsers = 0;
    
    while ($row = $activeResult->fetch_assoc()) {
        $dailyActiveUsers[] = [
            'date' => $row['date'],
            'date_formatted' => date('d/m/Y', strtotime($row['date'])),
            'active_users' => (int)$row['active_users']
        ];
        $totalActiveUsers += (int)$row['active_users'];
    }
    
    // Tỷ lệ người dùng hoạt động
    $activeRate = $totalNewUsers > 0 ? round(($totalActiveUsers / $totalNewUsers) * 100, 1) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'daily_new_users' => $dailyNewUsers,
            'daily_active_users' => $dailyActiveUsers,
            'summary' => [
                'total_new_users' => $totalNewUsers,
                'total_active_users' => $totalActiveUsers,
                'active_rate' => $activeRate,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_date_formatted' => date('d/m/Y', strtotime($startDate)),
                    'end_date_formatted' => date('d/m/Y', strtotime($endDate))
                ]
            ]
        ]
    ]);
}

// Hàm lấy tăng trưởng người dùng
function getUserGrowth($conn) {
    $months = $_GET['months'] ?? 12;
    $months = min(max((int)$months, 1), 24);
    
    $growthData = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $firstDay = date('Y-m-01', strtotime($month));
        $lastDay = date('Y-m-t', strtotime($month));
        
        // Người dùng mới trong tháng
        $newUsersQuery = "SELECT COUNT(*) as new_users 
                         FROM NGUOIDUNG 
                         WHERE ngay_tao >= '$firstDay' AND ngay_tao <= '$lastDay'";
        
        $newUsersResult = $conn->query($newUsersQuery);
        $newUsers = $newUsersResult ? (int)$newUsersResult->fetch_assoc()['new_users'] : 0;
        
        // Tổng người dùng đến cuối tháng
        $totalUsersQuery = "SELECT COUNT(*) as total_users 
                           FROM NGUOIDUNG 
                           WHERE ngay_tao <= '$lastDay'";
        
        $totalUsersResult = $conn->query($totalUsersQuery);
        $totalUsers = $totalUsersResult ? (int)$totalUsersResult->fetch_assoc()['total_users'] : 0;
        
        // Người dùng hoạt động trong tháng
        $activeUsersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as active_users 
                            FROM DONHANG 
                            WHERE ngay_tao >= '$firstDay' AND ngay_tao <= '$lastDay'";
        
        $activeUsersResult = $conn->query($activeUsersQuery);
        $activeUsers = $activeUsersResult ? (int)$activeUsersResult->fetch_assoc()['active_users'] : 0;
        
        $growthData[] = [
            'month' => $month,
            'month_formatted' => date('m/Y', strtotime($month)),
            'month_name' => getMonthName(date('m', strtotime($month))),
            'new_users' => $newUsers,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'growth_rate' => $i > 0 ? $this->calculateGrowthRate($newUsers, $growthData[$months - $i - 1]['new_users']) : 0
        ];
    }
    
    // Tính tổng và trung bình
    $totalNewUsers = array_sum(array_column($growthData, 'new_users'));
    $avgNewUsers = count($growthData) > 0 ? $totalNewUsers / count($growthData) : 0;
    $avgGrowthRate = count($growthData) > 1 ? 
        array_sum(array_column(array_slice($growthData, 1), 'growth_rate')) / (count($growthData) - 1) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => $growthData,
        'summary' => [
            'total_new_users' => $totalNewUsers,
            'avg_new_users' => round($avgNewUsers, 1),
            'avg_growth_rate' => round($avgGrowthRate, 1),
            'current_month_new_users' => $growthData[count($growthData) - 1]['new_users'],
            'current_month_growth' => $growthData[count($growthData) - 1]['growth_rate']
        ]
    ]);
}

// Hàm lấy hoạt động người dùng
function getUserActivity($conn) {
    $days = $_GET['days'] ?? 30;
    $days = min(max((int)$days, 1), 90);
    
    $activityData = [];
    $today = date('Y-m-d');
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Đơn hàng trong ngày
        $ordersQuery = "SELECT 
                         COUNT(*) as total_orders,
                         COUNT(DISTINCT nguoidung_id) as active_users,
                         SUM(tong_cuoi_cung) as total_revenue
                       FROM DONHANG
                       WHERE DATE(ngay_tao) = '$date'";
        
        $ordersResult = $conn->query($ordersQuery);
        $ordersData = $ordersResult ? $ordersResult->fetch_assoc() : null;
        
        // Người dùng mới trong ngày
        $newUsersQuery = "SELECT COUNT(*) as new_users 
                         FROM NGUOIDUNG 
                         WHERE DATE(ngay_tao) = '$date'";
        
        $newUsersResult = $conn->query($newUsersQuery);
        $newUsers = $newUsersResult ? (int)$newUsersResult->fetch_assoc()['new_users'] : 0;
        
        $activityData[] = [
            'date' => $date,
            'date_formatted' => date('d/m/Y', strtotime($date)),
            'day_name' => getDayName(date('D', strtotime($date))),
            'total_orders' => $ordersData ? (int)$ordersData['total_orders'] : 0,
            'active_users' => $ordersData ? (int)$ordersData['active_users'] : 0,
            'total_revenue' => $ordersData ? (float)$ordersData['total_revenue'] : 0,
            'new_users' => $newUsers,
            'avg_order_value' => $ordersData && $ordersData['total_orders'] > 0 ? 
                                 (float)$ordersData['total_revenue'] / (int)$ordersData['total_orders'] : 0
        ];
    }
    
    // Tính tổng và trung bình
    $totalOrders = array_sum(array_column($activityData, 'total_orders'));
    $totalActiveUsers = array_sum(array_column($activityData, 'active_users'));
    $totalNewUsers = array_sum(array_column($activityData, 'new_users'));
    
    $avgDailyOrders = count($activityData) > 0 ? $totalOrders / count($activityData) : 0;
    $avgDailyActiveUsers = count($activityData) > 0 ? $totalActiveUsers / count($activityData) : 0;
    $avgDailyNewUsers = count($activityData) > 0 ? $totalNewUsers / count($activityData) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => $activityData,
        'summary' => [
            'total_orders' => $totalOrders,
            'total_active_users' => $totalActiveUsers,
            'total_new_users' => $totalNewUsers,
            'avg_daily_orders' => round($avgDailyOrders, 1),
            'avg_daily_active_users' => round($avgDailyActiveUsers, 1),
            'avg_daily_new_users' => round($avgDailyNewUsers, 1),
            'period_days' => $days
        ]
    ]);
}

// Hàm lấy người dùng theo vai trò
function getUserByRole($conn) {
    $roles = ['khach_hang', 'nhan_vien', 'quan_tri'];
    $roleData = [];
    
    foreach ($roles as $role) {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN trang_thai = 'hoat_dong' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN DATE(ngay_tao) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                    SUM(CASE WHEN ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_30days
                  FROM NGUOIDUNG
                  WHERE vai_tro = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Thống kê đơn hàng cho khách hàng
        $orderStats = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];
        
        if ($role === 'khach_hang') {
            $orderQuery = "SELECT 
                            COUNT(*) as total_orders,
                            SUM(tong_cuoi_cung) as total_revenue,
                            AVG(tong_cuoi_cung) as avg_order_value
                          FROM DONHANG d
                          JOIN NGUOIDUNG n ON d.nguoidung_id = n.nguoidung_id
                          WHERE n.vai_tro = 'khach_hang'
                            AND d.trang_thai_donhang = 'da_giao'";
            
            $orderResult = $conn->query($orderQuery);
            if ($orderResult) {
                $orderRow = $orderResult->fetch_assoc();
                $orderStats = [
                    'total_orders' => (int)($orderRow['total_orders'] ?? 0),
                    'total_revenue' => (float)($orderRow['total_revenue'] ?? 0),
                    'avg_order_value' => (float)($orderRow['avg_order_value'] ?? 0)
                ];
            }
        }
        
        $roleData[] = [
            'role' => $role,
            'role_name' => getRoleName($role),
            'total_users' => (int)$row['total_users'],
            'active_users' => (int)$row['active_users'],
            'new_today' => (int)$row['new_today'],
            'new_30days' => (int)$row['new_30days'],
            'order_stats' => $orderStats
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $roleData
    ]);
}

// Hàm lấy người dùng theo trạng thái
function getUserByStatus($conn) {
    $statuses = ['hoat_dong', 'vo_hieu_hoa'];
    $statusData = [];
    
    foreach ($statuses as $status) {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN vai_tro = 'khach_hang' THEN 1 ELSE 0 END) as customers,
                    SUM(CASE WHEN vai_tro = 'nhan_vien' THEN 1 ELSE 0 END) as staff,
                    SUM(CASE WHEN vai_tro = 'quan_tri' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN DATE(ngay_tao) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_30days
                  FROM NGUOIDUNG
                  WHERE trang_thai = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $statusData[] = [
            'status' => $status,
            'status_name' => getStatusName($status),
            'total_users' => (int)$row['total_users'],
            'customers' => (int)$row['customers'],
            'staff' => (int)$row['staff'],
            'admins' => (int)$row['admins'],
            'new_30days' => (int)$row['new_30days']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $statusData
    ]);
}

// Hàm lấy top người dùng
function getTopUsers($conn) {
    $limit = $_GET['limit'] ?? 10;
    $limit = min(max((int)$limit, 1), 50);
    $type = $_GET['type'] ?? 'revenue'; // revenue, orders, frequency
    
    switch ($type) {
        case 'revenue':
            $query = "SELECT 
                        n.nguoidung_id,
                        n.hoten,
                        n.email,
                        n.sodienthoai,
                        n.vai_tro,
                        COUNT(d.donhang_id) as total_orders,
                        SUM(d.tong_cuoi_cung) as total_revenue,
                        AVG(d.tong_cuoi_cung) as avg_order_value,
                        MAX(d.ngay_tao) as last_order_date
                      FROM NGUOIDUNG n
                      LEFT JOIN DONHANG d ON n.nguoidung_id = d.nguoidung_id AND d.trang_thai_donhang = 'da_giao'
                      WHERE n.vai_tro = 'khach_hang'
                      GROUP BY n.nguoidung_id, n.hoten, n.email, n.sodienthoai, n.vai_tro
                      ORDER BY total_revenue DESC
                      LIMIT ?";
            $orderBy = 'total_revenue';
            break;
            
        case 'orders':
            $query = "SELECT 
                        n.nguoidung_id,
                        n.hoten,
                        n.email,
                        n.sodienthoai,
                        n.vai_tro,
                        COUNT(d.donhang_id) as total_orders,
                        SUM(d.tong_cuoi_cung) as total_revenue,
                        AVG(d.tong_cuoi_cung) as avg_order_value,
                        MAX(d.ngay_tao) as last_order_date
                      FROM NGUOIDUNG n
                      LEFT JOIN DONHANG d ON n.nguoidung_id = d.nguoidung_id AND d.trang_thai_donhang = 'da_giao'
                      WHERE n.vai_tro = 'khach_hang'
                      GROUP BY n.nguoidung_id, n.hoten, n.email, n.sodienthoai, n.vai_tro
                      ORDER BY total_orders DESC
                      LIMIT ?";
            $orderBy = 'total_orders';
            break;
            
        case 'frequency':
            $query = "SELECT 
                        n.nguoidung_id,
                        n.hoten,
                        n.email,
                        n.sodienthoai,
                        n.vai_tro,
                        COUNT(d.donhang_id) as total_orders,
                        SUM(d.tong_cuoi_cung) as total_revenue,
                        AVG(d.tong_cuoi_cung) as avg_order_value,
                        DATEDIFF(CURDATE(), MIN(d.ngay_tao)) as days_since_first_order,
                        COUNT(d.donhang_id) / GREATEST(DATEDIFF(CURDATE(), MIN(d.ngay_tao)), 1) as orders_per_day,
                        MAX(d.ngay_tao) as last_order_date
                      FROM NGUOIDUNG n
                      LEFT JOIN DONHANG d ON n.nguoidung_id = d.nguoidung_id AND d.trang_thai_donhang = 'da_giao'
                      WHERE n.vai_tro = 'khach_hang'
                        AND d.ngay_tao IS NOT NULL
                      GROUP BY n.nguoidung_id, n.hoten, n.email, n.sodienthoai, n.vai_tro
                      HAVING days_since_first_order > 0
                      ORDER BY orders_per_day DESC
                      LIMIT ?";
            $orderBy = 'orders_per_day';
            break;
            
        default:
            $query = "SELECT 
                        n.nguoidung_id,
                        n.hoten,
                        n.email,
                        n.sodienthoai,
                        n.vai_tro,
                        COUNT(d.donhang_id) as total_orders,
                        SUM(d.tong_cuoi_cung) as total_revenue,
                        AVG(d.tong_cuoi_cung) as avg_order_value,
                        MAX(d.ngay_tao) as last_order_date
                      FROM NGUOIDUNG n
                      LEFT JOIN DONHANG d ON n.nguoidung_id = d.nguoidung_id AND d.trang_thai_donhang = 'da_giao'
                      WHERE n.vai_tro = 'khach_hang'
                      GROUP BY n.nguoidung_id, n.hoten, n.email, n.sodienthoai, n.vai_tro
                      ORDER BY total_revenue DESC
                      LIMIT ?";
            $orderBy = 'total_revenue';
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $topUsers = [];
    
    while ($row = $result->fetch_assoc()) {
        $topUsers[] = [
            'user_id' => $row['nguoidung_id'],
            'full_name' => $row['hoten'],
            'email' => $row['email'],
            'phone' => $row['sodienthoai'],
            'role' => $row['vai_tro'],
            'role_name' => getRoleName($row['vai_tro']),
            'total_orders' => (int)$row['total_orders'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'avg_order_value' => (float)$row['avg_order_value'],
            'avg_order_value_formatted' => number_format($row['avg_order_value'], 0, ',', '.') . 'đ',
            'last_order_date' => $row['last_order_date'],
            'last_order_date_formatted' => $row['last_order_date'] ? date('d/m/Y', strtotime($row['last_order_date'])) : 'Chưa có',
            'days_since_last_order' => $row['last_order_date'] ? floor((time() - strtotime($row['last_order_date'])) / (60 * 60 * 24)) : null,
            'orders_per_day' => isset($row['orders_per_day']) ? round((float)$row['orders_per_day'], 2) : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $topUsers,
        'type' => $type,
        'order_by' => $orderBy
    ]);
}

// Hàm lấy người dùng gần đây
function getRecentUsers($conn) {
    $limit = $_GET['limit'] ?? 10;
    $limit = min(max((int)$limit, 1), 50);
    
    $query = "SELECT 
                n.nguoidung_id,
                n.hoten,
                n.email,
                n.sodienthoai,
                n.vai_tro,
                n.ngay_tao,
                t.trang_thai as account_status,
                t.lan_dang_nhap_cuoi
              FROM NGUOIDUNG n
              LEFT JOIN TAIKHOAN t ON n.nguoidung_id = t.nguoidung_id
              ORDER BY n.ngay_tao DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recentUsers = [];
    
    while ($row = $result->fetch_assoc()) {
        // Đếm số đơn hàng
        $ordersQuery = "SELECT COUNT(*) as order_count 
                       FROM DONHANG 
                       WHERE nguoidung_id = ?";
        
        $orderStmt = $conn->prepare($ordersQuery);
        $orderStmt->bind_param('s', $row['nguoidung_id']);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderCount = $orderResult ? (int)$orderResult->fetch_assoc()['order_count'] : 0;
        
        $recentUsers[] = [
            'user_id' => $row['nguoidung_id'],
            'full_name' => $row['hoten'],
            'email' => $row['email'],
            'phone' => $row['sodienthoai'],
            'role' => $row['vai_tro'],
            'role_name' => getRoleName($row['vai_tro']),
            'registration_date' => $row['ngay_tao'],
            'registration_date_formatted' => date('d/m/Y H:i', strtotime($row['ngay_tao'])),
            'account_status' => $row['account_status'] ?? 'unknown',
            'account_status_name' => getAccountStatusName($row['account_status'] ?? 'unknown'),
            'last_login' => $row['lan_dang_nhap_cuoi'],
            'last_login_formatted' => $row['lan_dang_nhap_cuoi'] ? date('d/m/Y H:i', strtotime($row['lan_dang_nhap_cuoi'])) : 'Chưa đăng nhập',
            'order_count' => $orderCount,
            'days_since_registration' => floor((time() - strtotime($row['ngay_tao'])) / (60 * 60 * 24))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $recentUsers
    ]);
}

// Hàm lấy chi tiết người dùng
function getUserDetails($conn) {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin người dùng']);
        return;
    }
    
    $query = "SELECT 
                n.*,
                t.ten_dang_nhap,
                t.trang_thai as account_status,
                t.lan_dang_nhap_cuoi,
                t.ngay_tao as account_created
              FROM NGUOIDUNG n
              LEFT JOIN TAIKHOAN t ON n.nguoidung_id = t.nguoidung_id
              WHERE n.nguoidung_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Thông tin đơn hàng
    $ordersQuery = "SELECT 
                      COUNT(*) as total_orders,
                      SUM(tong_cuoi_cung) as total_revenue,
                      AVG(tong_cuoi_cung) as avg_order_value,
                      MIN(ngay_tao) as first_order_date,
                      MAX(ngay_tao) as last_order_date
                    FROM DONHANG
                    WHERE nguoidung_id = ?
                      AND trang_thai_donhang = 'da_giao'";
    
    $orderStmt = $conn->prepare($ordersQuery);
    $orderStmt->bind_param('s', $userId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $orderStats = $orderResult ? $orderResult->fetch_assoc() : null;
    
    // Đơn hàng gần đây
    $recentOrdersQuery = "SELECT 
                            donhang_id,
                            tong_cuoi_cung,
                            trang_thai_donhang,
                            ngay_tao
                          FROM DONHANG
                          WHERE nguoidung_id = ?
                          ORDER BY ngay_tao DESC
                          LIMIT 5";
    
    $recentStmt = $conn->prepare($recentOrdersQuery);
    $recentStmt->bind_param('s', $userId);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    
    $recentOrders = [];
    while ($row = $recentResult->fetch_assoc()) {
        $recentOrders[] = [
            'order_id' => $row['donhang_id'],
            'total' => (float)$row['tong_cuoi_cung'],
            'total_formatted' => number_format($row['tong_cuoi_cung'], 0, ',', '.') . 'đ',
            'status' => $row['trang_thai_donhang'],
            'status_name' => getOrderStatusName($row['trang_thai_donhang']),
            'order_date' => $row['ngay_tao'],
            'order_date_formatted' => date('d/m/Y H:i', strtotime($row['ngay_tao']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_info' => [
                'user_id' => $user['nguoidung_id'],
                'full_name' => $user['hoten'],
                'email' => $user['email'],
                'phone' => $user['sodienthoai'],
                'address' => $user['diachi'],
                'role' => $user['vai_tro'],
                'role_name' => getRoleName($user['vai_tro']),
                'status' => $user['trang_thai'],
                'status_name' => getStatusName($user['trang_thai']),
                'registration_date' => $user['ngay_tao'],
                'registration_date_formatted' => date('d/m/Y H:i', strtotime($user['ngay_tao'])),
                'avatar' => $user['avatar']
            ],
            'account_info' => [
                'username' => $user['ten_dang_nhap'],
                'account_status' => $user['account_status'],
                'account_status_name' => getAccountStatusName($user['account_status']),
                'last_login' => $user['lan_dang_nhap_cuoi'],
                'last_login_formatted' => $user['lan_dang_nhap_cuoi'] ? date('d/m/Y H:i', strtotime($user['lan_dang_nhap_cuoi'])) : 'Chưa đăng nhập',
                'account_created' => $user['account_created'],
                'account_created_formatted' => $user['account_created'] ? date('d/m/Y H:i', strtotime($user['account_created'])) : 'N/A'
            ],
            'order_stats' => $orderStats ? [
                'total_orders' => (int)$orderStats['total_orders'],
                'total_revenue' => (float)$orderStats['total_revenue'],
                'total_revenue_formatted' => number_format($orderStats['total_revenue'], 0, ',', '.') . 'đ',
                'avg_order_value' => (float)$orderStats['avg_order_value'],
                'avg_order_value_formatted' => number_format($orderStats['avg_order_value'], 0, ',', '.') . 'đ',
                'first_order_date' => $orderStats['first_order_date'],
                'first_order_date_formatted' => $orderStats['first_order_date'] ? date('d/m/Y', strtotime($orderStats['first_order_date'])) : 'Chưa có',
                'last_order_date' => $orderStats['last_order_date'],
                'last_order_date_formatted' => $orderStats['last_order_date'] ? date('d/m/Y', strtotime($orderStats['last_order_date'])) : 'Chưa có'
            ] : null,
            'recent_orders' => $recentOrders
        ]
    ]);
}

// Hàm lấy thống kê đăng nhập
function getLoginStatistics($conn) {
    $days = $_GET['days'] ?? 30;
    $days = min(max((int)$days, 1), 90);
    
    $loginData = [];
    $today = date('Y-m-d');
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Số lần đăng nhập trong ngày
        $loginsQuery = "SELECT COUNT(*) as login_count 
                       FROM TAIKHOAN 
                       WHERE DATE(lan_dang_nhap_cuoi) = '$date'";
        
        $loginsResult = $conn->query($loginsQuery);
        $loginCount = $loginsResult ? (int)$loginsResult->fetch_assoc()['login_count'] : 0;
        
        // Người dùng đăng nhập trong ngày
        $usersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as unique_users 
                      FROM TAIKHOAN 
                      WHERE DATE(lan_dang_nhap_cuoi) = '$date'";
        
        $usersResult = $conn->query($usersQuery);
        $uniqueUsers = $usersResult ? (int)$usersResult->fetch_assoc()['unique_users'] : 0;
        
        $loginData[] = [
            'date' => $date,
            'date_formatted' => date('d/m/Y', strtotime($date)),
            'day_name' => getDayName(date('D', strtotime($date))),
            'login_count' => $loginCount,
            'unique_users' => $uniqueUsers,
            'avg_logins_per_user' => $uniqueUsers > 0 ? round($loginCount / $uniqueUsers, 2) : 0
        ];
    }
    
    // Tính tổng và trung bình
    $totalLogins = array_sum(array_column($loginData, 'login_count'));
    $totalUniqueUsers = array_sum(array_column($loginData, 'unique_users'));
    
    $avgDailyLogins = count($loginData) > 0 ? $totalLogins / count($loginData) : 0;
    $avgDailyUniqueUsers = count($loginData) > 0 ? $totalUniqueUsers / count($loginData) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => $loginData,
        'summary' => [
            'total_logins' => $totalLogins,
            'total_unique_users' => $totalUniqueUsers,
            'avg_daily_logins' => round($avgDailyLogins, 1),
            'avg_daily_unique_users' => round($avgDailyUniqueUsers, 1),
            'avg_logins_per_user' => $totalUniqueUsers > 0 ? round($totalLogins / $totalUniqueUsers, 2) : 0,
            'period_days' => $days
        ]
    ]);
}

// Hàm lấy phân khúc người dùng
function getUserSegments($conn) {
    $segments = [];
    
    // 1. Người dùng mới (đăng ký trong 7 ngày)
    $newUsersQuery = "SELECT COUNT(*) as count 
                     FROM NGUOIDUNG 
                     WHERE ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       AND vai_tro = 'khach_hang'";
    
    $newUsersResult = $conn->query($newUsersQuery);
    $newUsers = $newUsersResult ? (int)$newUsersResult->fetch_assoc()['count'] : 0;
    
    // 2. Người dùng thường xuyên (có đơn hàng trong 30 ngày)
    $activeUsersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as count 
                        FROM DONHANG 
                        WHERE ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          AND nguoidung_id IN (SELECT nguoidung_id FROM NGUOIDUNG WHERE vai_tro = 'khach_hang')";
    
    $activeUsersResult = $conn->query($activeUsersQuery);
    $activeUsers = $activeUsersResult ? (int)$activeUsersResult->fetch_assoc()['count'] : 0;
    
    // 3. Người dùng VIP (tổng chi tiêu > 1,000,000)
    $vipUsersQuery = "SELECT COUNT(DISTINCT nguoidung_id) as count 
                     FROM DONHANG 
                     WHERE trang_thai_donhang = 'da_giao'
                       AND nguoidung_id IN (SELECT nguoidung_id FROM NGUOIDUNG WHERE vai_tro = 'khach_hang')
                     GROUP BY nguoidung_id 
                     HAVING SUM(tong_cuoi_cung) > 1000000";
    
    $vipUsersResult = $conn->query($vipUsersQuery);
    $vipUsers = $vipUsersResult ? (int)$vipUsersResult->fetch_assoc()['count'] : 0;
    
    // 4. Người dùng không hoạt động (không có đơn hàng trong 90 ngày)
    $inactiveUsersQuery = "SELECT COUNT(*) as count 
                          FROM NGUOIDUNG n
                          WHERE n.vai_tro = 'khach_hang'
                            AND NOT EXISTS (
                              SELECT 1 FROM DONHANG d 
                              WHERE d.nguoidung_id = n.nguoidung_id 
                                AND d.ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                            )";
    
    $inactiveUsersResult = $conn->query($inactiveUsersQuery);
    $inactiveUsers = $inactiveUsersResult ? (int)$inactiveUsersResult->fetch_assoc()['count'] : 0;
    
    // 5. Người dùng một lần (chỉ có 1 đơn hàng)
    $oneTimeUsersQuery = "SELECT COUNT(*) as count 
                         FROM (
                           SELECT nguoidung_id, COUNT(*) as order_count
                           FROM DONHANG
                           WHERE nguoidung_id IN (SELECT nguoidung_id FROM NGUOIDUNG WHERE vai_tro = 'khach_hang')
                           GROUP BY nguoidung_id
                           HAVING order_count = 1
                         ) as temp";
    
    $oneTimeUsersResult = $conn->query($oneTimeUsersQuery);
    $oneTimeUsers = $oneTimeUsersResult ? (int)$oneTimeUsersResult->fetch_assoc()['count'] : 0;
    
    // 6. Người dùng trung thành (có trên 5 đơn hàng)
    $loyalUsersQuery = "SELECT COUNT(*) as count 
                       FROM (
                         SELECT nguoidung_id, COUNT(*) as order_count
                         FROM DONHANG
                         WHERE nguoidung_id IN (SELECT nguoidung_id FROM NGUOIDUNG WHERE vai_tro = 'khach_hang')
                         GROUP BY nguoidung_id
                         HAVING order_count >= 5
                       ) as temp";
    
    $loyalUsersResult = $conn->query($loyalUsersQuery);
    $loyalUsers = $loyalUsersResult ? (int)$loyalUsersResult->fetch_assoc()['count'] : 0;
    
    $segments = [
        [
            'segment' => 'new_users',
            'segment_name' => 'Người dùng mới',
            'description' => 'Đăng ký trong 7 ngày qua',
            'count' => $newUsers,
            'color' => '#3b82f6'
        ],
        [
            'segment' => 'active_users',
            'segment_name' => 'Người dùng hoạt động',
            'description' => 'Có đơn hàng trong 30 ngày',
            'count' => $activeUsers,
            'color' => '#10b981'
        ],
        [
            'segment' => 'vip_users',
            'segment_name' => 'Người dùng VIP',
            'description' => 'Tổng chi tiêu > 1,000,000đ',
            'count' => $vipUsers,
            'color' => '#8b5cf6'
        ],
        [
            'segment' => 'inactive_users',
            'segment_name' => 'Người dùng không hoạt động',
            'description' => 'Không có đơn hàng trong 90 ngày',
            'count' => $inactiveUsers,
            'color' => '#6b7280'
        ],
        [
            'segment' => 'one_time_users',
            'segment_name' => 'Người dùng một lần',
            'description' => 'Chỉ có 1 đơn hàng',
            'count' => $oneTimeUsers,
            'color' => '#f59e0b'
        ],
        [
            'segment' => 'loyal_users',
            'segment_name' => 'Người dùng trung thành',
            'description' => 'Có trên 5 đơn hàng',
            'count' => $loyalUsers,
            'color' => '#ef4444'
        ]
    ];
    
    // Tổng số khách hàng
    $totalCustomersQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = 'khach_hang'";
    $totalCustomersResult = $conn->query($totalCustomersQuery);
    $totalCustomers = $totalCustomersResult ? (int)$totalCustomersResult->fetch_assoc()['count'] : 0;
    
    // Tính phần trăm cho mỗi phân khúc
    foreach ($segments as &$segment) {
        $segment['percentage'] = $totalCustomers > 0 ? round(($segment['count'] / $totalCustomers) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $segments,
        'summary' => [
            'total_customers' => $totalCustomers,
            'segment_count' => count($segments)
        ]
    ]);
}

// Hàm xuất báo cáo người dùng
function exportUserReport($conn) {
    $type = $_GET['type'] ?? 'summary';
    $format = $_GET['format'] ?? 'json';
    
    // For now, return JSON. Can be extended to CSV or PDF
    switch ($type) {
        case 'summary':
            getUserSummary($conn);
            break;
        case 'statistics':
            getUserStatistics($conn);
            break;
        case 'growth':
            getUserGrowth($conn);
            break;
        case 'segments':
            getUserSegments($conn);
            break;
        default:
            getUserSummary($conn);
    }
}

// Helper functions
function getRoleName($role) {
    $roles = [
        'khach_hang' => 'Khách hàng',
        'nhan_vien' => 'Nhân viên',
        'quan_tri' => 'Quản trị viên'
    ];
    return $roles[$role] ?? $role;
}

function getStatusName($status) {
    $statuses = [
        'hoat_dong' => 'Hoạt động',
        'vo_hieu_hoa' => 'Vô hiệu hóa'
    ];
    return $statuses[$status] ?? $status;
}

function getAccountStatusName($status) {
    $statuses = [
        'kich_hoat' => 'Kích hoạt',
        'chua_kich_hoat' => 'Chưa kích hoạt',
        'khoa' => 'Đã khóa'
    ];
    return $statuses[$status] ?? $status;
}

function getOrderStatusName($status) {
    $statuses = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'dang_giao' => 'Đang giao',
        'da_giao' => 'Đã giao',
        'da_huy' => 'Đã hủy'
    ];
    return $statuses[$status] ?? $status;
}

function getMonthName($month) {
    $months = [
        '01' => 'Tháng 1', '02' => 'Tháng 2', '03' => 'Tháng 3',
        '04' => 'Tháng 4', '05' => 'Tháng 5', '06' => 'Tháng 6',
        '07' => 'Tháng 7', '08' => 'Tháng 8', '09' => 'Tháng 9',
        '10' => 'Tháng 10', '11' => 'Tháng 11', '12' => 'Tháng 12'
    ];
    return $months[$month] ?? $month;
}

function getDayName($day) {
    $days = [
        'Mon' => 'Thứ 2',
        'Tue' => 'Thứ 3',
        'Wed' => 'Thứ 4',
        'Thu' => 'Thứ 5',
        'Fri' => 'Thứ 6',
        'Sat' => 'Thứ 7',
        'Sun' => 'Chủ nhật'
    ];
    return $days[$day] ?? $day;
}

function calculateGrowthRate($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}
?>