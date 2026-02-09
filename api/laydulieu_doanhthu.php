<?php
// api/laydulieu_doanhthu.php
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
        case 'get_revenue_daily':
            getRevenueDaily($conn);
            break;
            
        case 'get_revenue_monthly':
            getRevenueMonthly($conn);
            break;
            
        case 'get_revenue_yearly':
            getRevenueYearly($conn);
            break;
            
        case 'get_revenue_by_category':
            getRevenueByCategory($conn);
            break;
            
        case 'get_revenue_by_food':
            getRevenueByFood($conn);
            break;
            
        case 'get_revenue_by_payment_method':
            getRevenueByPaymentMethod($conn);
            break;
            
        case 'get_revenue_by_time_period':
            getRevenueByTimePeriod($conn);
            break;
            
        case 'get_revenue_summary':
            getRevenueSummary($conn);
            break;
            
        case 'get_revenue_comparison':
            getRevenueComparison($conn);
            break;
            
        case 'export_revenue_report':
            exportRevenueReport($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    error_log("Error in laydulieu_doanhthu.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()]);
}

$conn->close();

// Hàm lấy doanh thu theo ngày
function getRevenueDaily($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Validate dates
    if (!validateDate($startDate) || !validateDate($endDate)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng ngày không hợp lệ']);
        return;
    }
    
    $query = "SELECT 
                DATE(ngay_tao) as date,
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue,
                AVG(tong_cuoi_cung) as avg_order_value,
                COUNT(DISTINCT nguoidung_id) as unique_customers
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND DATE(ngay_tao) BETWEEN ? AND ?
              GROUP BY DATE(ngay_tao)
              ORDER BY DATE(ngay_tao)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dailyData = [];
    $totalRevenue = 0;
    $totalOrders = 0;
    
    while ($row = $result->fetch_assoc()) {
        $dailyData[] = [
            'date' => $row['date'],
            'date_formatted' => date('d/m/Y', strtotime($row['date'])),
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'avg_order_value' => (float)$row['avg_order_value'],
            'avg_order_value_formatted' => number_format($row['avg_order_value'], 0, ',', '.') . 'đ',
            'unique_customers' => (int)$row['unique_customers']
        ];
        
        $totalRevenue += (float)$row['total_revenue'];
        $totalOrders += (int)$row['order_count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dailyData,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'total_orders' => $totalOrders,
            'avg_daily_revenue' => count($dailyData) > 0 ? $totalRevenue / count($dailyData) : 0,
            'avg_daily_orders' => count($dailyData) > 0 ? $totalOrders / count($dailyData) : 0,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_date_formatted' => date('d/m/Y', strtotime($startDate)),
                'end_date_formatted' => date('d/m/Y', strtotime($endDate))
            ]
        ]
    ]);
}

// Hàm lấy doanh thu theo tháng
function getRevenueMonthly($conn) {
    $year = $_GET['year'] ?? date('Y');
    $year = (int)$year;
    
    $query = "SELECT 
                DATE_FORMAT(ngay_tao, '%Y-%m') as month,
                MONTH(ngay_tao) as month_number,
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue,
                AVG(tong_cuoi_cung) as avg_order_value,
                COUNT(DISTINCT nguoidung_id) as unique_customers
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND YEAR(ngay_tao) = ?
              GROUP BY DATE_FORMAT(ngay_tao, '%Y-%m'), MONTH(ngay_tao)
              ORDER BY DATE_FORMAT(ngay_tao, '%Y-%m')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $monthlyData = [];
    $monthNames = [
        '01' => 'Tháng 1', '02' => 'Tháng 2', '03' => 'Tháng 3', '04' => 'Tháng 4',
        '05' => 'Tháng 5', '06' => 'Tháng 6', '07' => 'Tháng 7', '08' => 'Tháng 8',
        '09' => 'Tháng 9', '10' => 'Tháng 10', '11' => 'Tháng 11', '12' => 'Tháng 12'
    ];
    
    // Initialize all months
    for ($i = 1; $i <= 12; $i++) {
        $monthKey = sprintf('%02d', $i);
        $monthlyData[$monthKey] = [
            'month' => $monthKey,
            'month_name' => $monthNames[$monthKey],
            'order_count' => 0,
            'total_revenue' => 0,
            'avg_order_value' => 0,
            'unique_customers' => 0
        ];
    }
    
    while ($row = $result->fetch_assoc()) {
        $monthKey = sprintf('%02d', $row['month_number']);
        $monthlyData[$monthKey] = [
            'month' => $row['month'],
            'month_name' => $monthNames[$monthKey],
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'avg_order_value' => (float)$row['avg_order_value'],
            'avg_order_value_formatted' => number_format($row['avg_order_value'], 0, ',', '.') . 'đ',
            'unique_customers' => (int)$row['unique_customers']
        ];
    }
    
    // Convert to indexed array
    $monthlyArray = array_values($monthlyData);
    
    $totalRevenue = array_sum(array_column($monthlyArray, 'total_revenue'));
    $totalOrders = array_sum(array_column($monthlyArray, 'order_count'));
    
    echo json_encode([
        'success' => true,
        'data' => $monthlyArray,
        'summary' => [
            'year' => $year,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'total_orders' => $totalOrders,
            'avg_monthly_revenue' => count(array_filter($monthlyArray, function($m) { return $m['total_revenue'] > 0; })) > 0 ? 
                                   $totalRevenue / count(array_filter($monthlyArray, function($m) { return $m['total_revenue'] > 0; })) : 0,
            'avg_monthly_orders' => count(array_filter($monthlyArray, function($m) { return $m['order_count'] > 0; })) > 0 ? 
                                   $totalOrders / count(array_filter($monthlyArray, function($m) { return $m['order_count'] > 0; })) : 0
        ]
    ]);
}

// Hàm lấy doanh thu theo năm
function getRevenueYearly($conn) {
    $years = $_GET['years'] ?? 5;
    $years = (int)$years;
    
    $query = "SELECT 
                YEAR(ngay_tao) as year,
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue,
                AVG(tong_cuoi_cung) as avg_order_value,
                COUNT(DISTINCT nguoidung_id) as unique_customers
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND YEAR(ngay_tao) >= YEAR(CURDATE()) - ?
              GROUP BY YEAR(ngay_tao)
              ORDER BY YEAR(ngay_tao)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $years);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $yearlyData = [];
    
    while ($row = $result->fetch_assoc()) {
        $yearlyData[] = [
            'year' => (int)$row['year'],
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'avg_order_value' => (float)$row['avg_order_value'],
            'avg_order_value_formatted' => number_format($row['avg_order_value'], 0, ',', '.') . 'đ',
            'unique_customers' => (int)$row['unique_customers']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $yearlyData
    ]);
}

// Hàm lấy doanh thu theo danh mục
function getRevenueByCategory($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $query = "SELECT 
                m.danh_muc,
                COUNT(*) as order_count,
                SUM(c.thanh_tien) as total_revenue,
                SUM(c.so_luong) as total_quantity,
                COUNT(DISTINCT d.donhang_id) as unique_orders
              FROM CHITIETDONHANG c
              JOIN DONHANG d ON c.donhang_id = d.donhang_id
              JOIN MONAN m ON c.monan_id = m.monan_id
              WHERE d.trang_thai_donhang = 'da_giao'
                AND DATE(d.ngay_tao) BETWEEN ? AND ?
              GROUP BY m.danh_muc
              ORDER BY total_revenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categoryData = [];
    $totalRevenue = 0;
    
    while ($row = $result->fetch_assoc()) {
        $categoryName = getCategoryName($row['danh_muc']);
        
        $categoryData[] = [
            'category_id' => $row['danh_muc'],
            'category_name' => $categoryName,
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'total_quantity' => (int)$row['total_quantity'],
            'unique_orders' => (int)$row['unique_orders']
        ];
        
        $totalRevenue += (float)$row['total_revenue'];
    }
    
    // Calculate percentage for each category
    foreach ($categoryData as &$category) {
        $category['percentage'] = $totalRevenue > 0 ? round(($category['total_revenue'] / $totalRevenue) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $categoryData,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'category_count' => count($categoryData)
        ]
    ]);
}

// Hàm lấy doanh thu theo món ăn
function getRevenueByFood($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $limit = $_GET['limit'] ?? 10;
    $limit = min(max((int)$limit, 1), 50);
    
    $query = "SELECT 
                m.monan_id,
                m.ten_mon,
                m.danh_muc,
                m.gia,
                COUNT(*) as order_count,
                SUM(c.thanh_tien) as total_revenue,
                SUM(c.so_luong) as total_quantity,
                AVG(dg.diem_danhgia) as avg_rating
              FROM CHITIETDONHANG c
              JOIN DONHANG d ON c.donhang_id = d.donhang_id
              JOIN MONAN m ON c.monan_id = m.monan_id
              LEFT JOIN DANHGIA dg ON m.monan_id = dg.monan_id AND dg.trang_thai = 'da_duyet'
              WHERE d.trang_thai_donhang = 'da_giao'
                AND DATE(d.ngay_tao) BETWEEN ? AND ?
              GROUP BY m.monan_id, m.ten_mon, m.danh_muc, m.gia
              ORDER BY total_revenue DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $startDate, $endDate, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $foodData = [];
    $totalRevenue = 0;
    
    while ($row = $result->fetch_assoc()) {
        $foodData[] = [
            'food_id' => $row['monan_id'],
            'food_name' => $row['ten_mon'],
            'category' => $row['danh_muc'],
            'category_name' => getCategoryName($row['danh_muc']),
            'price' => (float)$row['gia'],
            'price_formatted' => number_format($row['gia'], 0, ',', '.') . 'đ',
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'total_quantity' => (int)$row['total_quantity'],
            'avg_rating' => $row['avg_rating'] ? round((float)$row['avg_rating'], 1) : 0,
            'revenue_per_item' => (int)$row['total_quantity'] > 0 ? (float)$row['total_revenue'] / (int)$row['total_quantity'] : 0
        ];
        
        $totalRevenue += (float)$row['total_revenue'];
    }
    
    // Calculate percentage for each food
    foreach ($foodData as &$food) {
        $food['percentage'] = $totalRevenue > 0 ? round(($food['total_revenue'] / $totalRevenue) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $foodData,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'food_count' => count($foodData)
        ]
    ]);
}

// Hàm lấy doanh thu theo phương thức thanh toán
function getRevenueByPaymentMethod($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $query = "SELECT 
                phuong_thuc_thanhtoan,
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue,
                AVG(tong_cuoi_cung) as avg_order_value
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND DATE(ngay_tao) BETWEEN ? AND ?
              GROUP BY phuong_thuc_thanhtoan
              ORDER BY total_revenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $paymentData = [];
    $totalRevenue = 0;
    
    while ($row = $result->fetch_assoc()) {
        $paymentMethod = getPaymentMethodName($row['phuong_thuc_thanhtoan']);
        
        $paymentData[] = [
            'payment_method' => $row['phuong_thuc_thanhtoan'],
            'payment_method_name' => $paymentMethod,
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue'],
            'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ',
            'avg_order_value' => (float)$row['avg_order_value'],
            'avg_order_value_formatted' => number_format($row['avg_order_value'], 0, ',', '.') . 'đ'
        ];
        
        $totalRevenue += (float)$row['total_revenue'];
    }
    
    // Calculate percentage for each payment method
    foreach ($paymentData as &$payment) {
        $payment['percentage'] = $totalRevenue > 0 ? round(($payment['total_revenue'] / $totalRevenue) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $paymentData,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'payment_method_count' => count($paymentData)
        ]
    ]);
}

// Hàm lấy doanh thu theo khoảng thời gian trong ngày
function getRevenueByTimePeriod($conn) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $query = "SELECT 
                HOUR(ngay_tao) as hour,
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND DATE(ngay_tao) BETWEEN ? AND ?
              GROUP BY HOUR(ngay_tao)
              ORDER BY HOUR(ngay_tao)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $timeData = [];
    
    // Initialize all hours
    for ($i = 0; $i < 24; $i++) {
        $timeData[$i] = [
            'hour' => $i,
            'hour_formatted' => sprintf('%02d:00', $i),
            'order_count' => 0,
            'total_revenue' => 0
        ];
    }
    
    while ($row = $result->fetch_assoc()) {
        $hour = (int)$row['hour'];
        if ($hour >= 0 && $hour < 24) {
            $timeData[$hour] = [
                'hour' => $hour,
                'hour_formatted' => sprintf('%02d:00', $hour),
                'order_count' => (int)$row['order_count'],
                'total_revenue' => (float)$row['total_revenue'],
                'total_revenue_formatted' => number_format($row['total_revenue'], 0, ',', '.') . 'đ'
            ];
        }
    }
    
    // Convert to indexed array
    $timeArray = array_values($timeData);
    
    // Find peak hours
    $peakHours = array_slice($timeArray, 0);
    usort($peakHours, function($a, $b) {
        return $b['total_revenue'] <=> $a['total_revenue'];
    });
    
    echo json_encode([
        'success' => true,
        'data' => $timeArray,
        'peak_hours' => array_slice($peakHours, 0, 3)
    ]);
}

// Hàm lấy tổng quan doanh thu
function getRevenueSummary($conn) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $thisMonth = date('Y-m-01');
    $lastMonth = date('Y-m-01', strtotime('-1 month'));
    $thisYear = date('Y-01-01');
    $lastYear = date('Y-01-01', strtotime('-1 year'));
    
    // Today's revenue
    $todayQuery = "SELECT 
                    COUNT(*) as order_count,
                    SUM(tong_cuoi_cung) as total_revenue,
                    AVG(tong_cuoi_cung) as avg_order_value
                  FROM DONHANG
                  WHERE trang_thai_donhang = 'da_giao'
                    AND DATE(ngay_tao) = ?";
    
    // Yesterday's revenue
    $yesterdayQuery = "SELECT 
                        COUNT(*) as order_count,
                        SUM(tong_cuoi_cung) as total_revenue
                      FROM DONHANG
                      WHERE trang_thai_donhang = 'da_giao'
                        AND DATE(ngay_tao) = ?";
    
    // This month's revenue
    $thisMonthQuery = "SELECT 
                        COUNT(*) as order_count,
                        SUM(tong_cuoi_cung) as total_revenue
                      FROM DONHANG
                      WHERE trang_thai_donhang = 'da_giao'
                        AND ngay_tao >= ?";
    
    // Last month's revenue
    $lastMonthQuery = "SELECT 
                        COUNT(*) as order_count,
                        SUM(tong_cuoi_cung) as total_revenue
                      FROM DONHANG
                      WHERE trang_thai_donhang = 'da_giao'
                        AND ngay_tao >= ? 
                        AND ngay_tao < DATE_ADD(?, INTERVAL 1 MONTH)";
    
    // Execute queries
    $todayData = executeRevenueQuery($conn, $todayQuery, $today);
    $yesterdayData = executeRevenueQuery($conn, $yesterdayQuery, $yesterday);
    $thisMonthData = executeRevenueQuery($conn, $thisMonthQuery, $thisMonth);
    $lastMonthData = executeRevenueQuery($conn, $lastMonthQuery, $lastMonth, $lastMonth);
    
    // Calculate growth percentages
    $todayGrowth = calculateGrowthPercentage($todayData['total_revenue'], $yesterdayData['total_revenue']);
    $monthGrowth = calculateGrowthPercentage($thisMonthData['total_revenue'], $lastMonthData['total_revenue']);
    
    // Get top selling category
    $topCategoryQuery = "SELECT 
                          m.danh_muc,
                          SUM(c.thanh_tien) as total_revenue
                        FROM CHITIETDONHANG c
                        JOIN DONHANG d ON c.donhang_id = d.donhang_id
                        JOIN MONAN m ON c.monan_id = m.monan_id
                        WHERE d.trang_thai_donhang = 'da_giao'
                          AND DATE(d.ngay_tao) = ?
                        GROUP BY m.danh_muc
                        ORDER BY total_revenue DESC
                        LIMIT 1";
    
    $stmt = $conn->prepare($topCategoryQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $categoryResult = $stmt->get_result();
    $topCategory = $categoryResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'today' => [
                'order_count' => (int)$todayData['order_count'],
                'total_revenue' => (float)$todayData['total_revenue'],
                'total_revenue_formatted' => number_format($todayData['total_revenue'], 0, ',', '.') . 'đ',
                'avg_order_value' => (float)$todayData['avg_order_value'],
                'avg_order_value_formatted' => number_format($todayData['avg_order_value'], 0, ',', '.') . 'đ'
            ],
            'yesterday' => [
                'order_count' => (int)$yesterdayData['order_count'],
                'total_revenue' => (float)$yesterdayData['total_revenue'],
                'total_revenue_formatted' => number_format($yesterdayData['total_revenue'], 0, ',', '.') . 'đ'
            ],
            'this_month' => [
                'order_count' => (int)$thisMonthData['order_count'],
                'total_revenue' => (float)$thisMonthData['total_revenue'],
                'total_revenue_formatted' => number_format($thisMonthData['total_revenue'], 0, ',', '.') . 'đ'
            ],
            'last_month' => [
                'order_count' => (int)$lastMonthData['order_count'],
                'total_revenue' => (float)$lastMonthData['total_revenue'],
                'total_revenue_formatted' => number_format($lastMonthData['total_revenue'], 0, ',', '.') . 'đ'
            ],
            'growth' => [
                'today' => $todayGrowth,
                'month' => $monthGrowth
            ],
            'top_category' => $topCategory ? [
                'category' => $topCategory['danh_muc'],
                'category_name' => getCategoryName($topCategory['danh_muc']),
                'revenue' => (float)$topCategory['total_revenue'],
                'revenue_formatted' => number_format($topCategory['total_revenue'], 0, ',', '.') . 'đ'
            ] : null
        ]
    ]);
}

// Hàm lấy so sánh doanh thu
function getRevenueComparison($conn) {
    $period = $_GET['period'] ?? 'month'; // month, quarter, year
    $compareWith = $_GET['compare_with'] ?? 'previous'; // previous, same_period_last_year
    
    $currentData = [];
    $comparisonData = [];
    
    if ($period === 'month') {
        $currentStart = date('Y-m-01');
        $currentEnd = date('Y-m-d');
        
        if ($compareWith === 'previous') {
            $compareStart = date('Y-m-01', strtotime('-1 month'));
            $compareEnd = date('Y-m-t', strtotime('-1 month'));
        } else { // same_period_last_year
            $compareStart = date('Y-m-01', strtotime('-1 year'));
            $compareEnd = date('Y-m-d', strtotime('-1 year'));
        }
        
        $currentData = getPeriodRevenue($conn, $currentStart, $currentEnd);
        $comparisonData = getPeriodRevenue($conn, $compareStart, $compareEnd);
        
        $growth = calculateGrowthPercentage($currentData['total_revenue'], $comparisonData['total_revenue']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'current_period' => [
                    'period' => 'Tháng này',
                    'start_date' => $currentStart,
                    'end_date' => $currentEnd,
                    'order_count' => $currentData['order_count'],
                    'total_revenue' => $currentData['total_revenue'],
                    'total_revenue_formatted' => number_format($currentData['total_revenue'], 0, ',', '.') . 'đ',
                    'avg_order_value' => $currentData['avg_order_value'],
                    'unique_customers' => $currentData['unique_customers']
                ],
                'comparison_period' => [
                    'period' => $compareWith === 'previous' ? 'Tháng trước' : 'Cùng kỳ năm ngoái',
                    'start_date' => $compareStart,
                    'end_date' => $compareEnd,
                    'order_count' => $comparisonData['order_count'],
                    'total_revenue' => $comparisonData['total_revenue'],
                    'total_revenue_formatted' => number_format($comparisonData['total_revenue'], 0, ',', '.') . 'đ',
                    'avg_order_value' => $comparisonData['avg_order_value'],
                    'unique_customers' => $comparisonData['unique_customers']
                ],
                'growth' => $growth
            ]
        ]);
    }
}

// Hàm xuất báo cáo doanh thu
function exportRevenueReport($conn) {
    $type = $_GET['type'] ?? 'daily';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $format = $_GET['format'] ?? 'json'; // json, csv, pdf
    
    // For now, return JSON. Can be extended to CSV or PDF
    if ($type === 'daily') {
        getRevenueDaily($conn);
    } elseif ($type === 'monthly') {
        getRevenueMonthly($conn);
    } elseif ($type === 'by_category') {
        getRevenueByCategory($conn);
    } elseif ($type === 'by_food') {
        getRevenueByFood($conn);
    }
}

// Helper function để thực thi query doanh thu
function executeRevenueQuery($conn, $query, ...$params) {
    $stmt = $conn->prepare($query);
    
    if (count($params) === 1) {
        $stmt->bind_param('s', $params[0]);
    } elseif (count($params) === 2) {
        $stmt->bind_param('ss', $params[0], $params[1]);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return [
        'order_count' => $row ? $row['order_count'] : 0,
        'total_revenue' => $row ? $row['total_revenue'] : 0,
        'avg_order_value' => $row ? $row['avg_order_value'] : 0
    ];
}

// Helper function để lấy doanh thu theo khoảng thời gian
function getPeriodRevenue($conn, $startDate, $endDate) {
    $query = "SELECT 
                COUNT(*) as order_count,
                SUM(tong_cuoi_cung) as total_revenue,
                AVG(tong_cuoi_cung) as avg_order_value,
                COUNT(DISTINCT nguoidung_id) as unique_customers
              FROM DONHANG
              WHERE trang_thai_donhang = 'da_giao'
                AND DATE(ngay_tao) BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return [
        'order_count' => $row ? (int)$row['order_count'] : 0,
        'total_revenue' => $row ? (float)$row['total_revenue'] : 0,
        'avg_order_value' => $row ? (float)$row['avg_order_value'] : 0,
        'unique_customers' => $row ? (int)$row['unique_customers'] : 0
    ];
}

// Helper function để tính phần trăm tăng trưởng
function calculateGrowthPercentage($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    
    return round((($current - $previous) / $previous) * 100, 1);
}

// Helper function để validate date
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Helper function để lấy tên danh mục
function getCategoryName($categoryId) {
    $categories = [
        'khai_vi' => 'Khai vị',
        'mon_chinh' => 'Món chính',
        'mon_phu' => 'Món phụ',
        'trang_mieng' => 'Tráng miệng',
        'do_uong' => 'Đồ uống',
        'combo' => 'Combo'
    ];
    
    return $categories[$categoryId] ?? $categoryId;
}

// Helper function để lấy tên phương thức thanh toán
function getPaymentMethodName($method) {
    $methods = [
        'tien_mat' => 'Tiền mặt',
        'the_ngan_hang' => 'Thẻ ngân hàng',
        'vi_dien_tu' => 'Ví điện tử'
    ];
    
    return $methods[$method] ?? $method;
}
?>