<?php
// api/xulychatbot.php
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

// Xử lý action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'chat') {
    $message = $_POST['message'] ?? $_GET['message'] ?? '';
    $context = $_POST['context'] ?? $_GET['context'] ?? 'general';
    
    $response = processChatMessage($message, $context, $conn);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

$conn->close();

// Hàm xử lý tin nhắn chat
function processChatMessage($message, $context, $conn) {
    $lowerMessage = strtolower($message);
    
    // Phân loại câu hỏi
    $intent = classifyIntent($lowerMessage);
    
    // Xử lý theo intent
    switch ($intent) {
        case 'revenue':
            return getRevenueInfo($conn);
            
        case 'orders':
            return getOrdersInfo($conn);
            
        case 'users':
            return getUsersInfo($conn);
            
        case 'foods':
            return getFoodsInfo($conn);
            
        case 'report':
            return getReportInfo();
            
        case 'greeting':
            return getGreetingResponse();
            
        case 'help':
            return getHelpResponse();
            
        default:
            return getGeneralResponse($message, $context, $conn);
    }
}

// Hàm phân loại ý định
function classifyIntent($message) {
    $keywords = [
        'revenue' => ['doanh thu', 'tiền', 'thu nhập', 'lợi nhuận', 'bán được'],
        'orders' => ['đơn hàng', 'order', 'đặt hàng', 'giao hàng', 'trạng thái'],
        'users' => ['người dùng', 'khách hàng', 'tài khoản', 'user', 'customer'],
        'foods' => ['món ăn', 'đồ ăn', 'thức ăn', 'bán chạy', 'phổ biến'],
        'report' => ['báo cáo', 'thống kê', 'tổng hợp', 'phân tích', 'biểu đồ'],
        'greeting' => ['xin chào', 'chào', 'hello', 'hi', 'chào bạn'],
        'help' => ['giúp', 'hỗ trợ', 'hướng dẫn', 'làm sao', 'cách nào']
    ];
    
    foreach ($keywords as $intent => $words) {
        foreach ($words as $word) {
            if (strpos($message, $word) !== false) {
                return $intent;
            }
        }
    }
    
    return 'general';
}

// Hàm lấy thông tin doanh thu
function getRevenueInfo($conn) {
    $today = date('Y-m-d');
    $firstDayOfMonth = date('Y-m-01');
    
    // Doanh thu hôm nay
    $todayRevenueQuery = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue 
                         FROM DONHANG 
                         WHERE DATE(ngay_tao) = '$today' 
                         AND trang_thai_donhang = 'da_giao'";
    
    $todayResult = $conn->query($todayRevenueQuery);
    $todayRevenue = $todayResult ? (float)$todayResult->fetch_assoc()['revenue'] : 0;
    
    // Doanh thu tháng này
    $monthRevenueQuery = "SELECT COALESCE(SUM(tong_cuoi_cung), 0) as revenue 
                         FROM DONHANG 
                         WHERE ngay_tao >= '$firstDayOfMonth' 
                         AND trang_thai_donhang = 'da_giao'";
    
    $monthResult = $conn->query($monthRevenueQuery);
    $monthRevenue = $monthResult ? (float)$monthResult->fetch_assoc()['revenue'] : 0;
    
    // Số đơn hàng hôm nay
    $todayOrdersQuery = "SELECT COUNT(*) as count FROM DONHANG WHERE DATE(ngay_tao) = '$today'";
    $todayOrdersResult = $conn->query($todayOrdersQuery);
    $todayOrders = $todayOrdersResult ? (int)$todayOrdersResult->fetch_assoc()['count'] : 0;
    
    $formattedToday = number_format($todayRevenue, 0, ',', '.');
    $formattedMonth = number_format($monthRevenue, 0, ',', '.');
    
    return "📊 **Thông tin doanh thu:**\n\n" .
           "• Hôm nay: {$formattedToday}đ từ {$todayOrders} đơn hàng\n" .
           "• Tháng này: {$formattedMonth}đ\n" .
           "• Xu hướng: Doanh thu ổn định và tăng trưởng tốt\n\n" .
           "💡 *Mẹo:* Bạn có thể xem chi tiết biểu đồ doanh thu trong phần báo cáo doanh thu.";
}

// Hàm lấy thông tin đơn hàng
function getOrdersInfo($conn) {
    $statusCounts = [];
    $statuses = ['cho_xac_nhan', 'da_xac_nhan', 'dang_giao', 'da_giao', 'da_huy'];
    
    foreach ($statuses as $status) {
        $query = "SELECT COUNT(*) as count FROM DONHANG WHERE trang_thai_donhang = '$status'";
        $result = $conn->query($query);
        $statusCounts[$status] = $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
    
    $statusLabels = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'dang_giao' => 'Đang giao',
        'da_giao' => 'Đã giao',
        'da_huy' => 'Đã hủy'
    ];
    
    $response = "📦 **Thông tin đơn hàng:**\n\n";
    foreach ($statusCounts as $status => $count) {
        $response .= "• {$statusLabels[$status]}: {$count} đơn\n";
    }
    
    $totalPending = $statusCounts['cho_xac_nhan'] + $statusCounts['da_xac_nhan'] + $statusCounts['dang_giao'];
    
    $response .= "\n📈 **Tổng quan:**\n";
    $response .= "• Đơn cần xử lý: {$totalPending} đơn\n";
    $response .= "• Tỷ lệ thành công: " . 
                 ($statusCounts['da_giao'] > 0 ? 
                  round(($statusCounts['da_giao'] / array_sum($statusCounts)) * 100, 1) : 0) . "%\n\n";
    $response .= "💡 *Mẹo:* Xem chi tiết đơn hàng trong phần báo cáo đơn hàng.";
    
    return $response;
}

// Hàm lấy thông tin người dùng
function getUsersInfo($conn) {
    // Thống kê theo vai trò
    $roles = ['khach_hang', 'nhan_vien', 'quan_tri'];
    $roleStats = [];
    
    foreach ($roles as $role) {
        $query = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE vai_tro = '$role'";
        $result = $conn->query($query);
        $roleStats[$role] = $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
    
    // Người dùng mới hôm nay
    $today = date('Y-m-d');
    $newUsersQuery = "SELECT COUNT(*) as count FROM NGUOIDUNG WHERE DATE(ngay_tao) = '$today'";
    $newUsersResult = $conn->query($newUsersQuery);
    $newUsersToday = $newUsersResult ? (int)$newUsersResult->fetch_assoc()['count'] : 0;
    
    $roleLabels = [
        'khach_hang' => 'Khách hàng',
        'nhan_vien' => 'Nhân viên',
        'quan_tri' => 'Quản trị'
    ];
    
    $response = "👥 **Thông tin người dùng:**\n\n";
    foreach ($roleStats as $role => $count) {
        $response .= "• {$roleLabels[$role]}: {$count} người\n";
    }
    
    $totalUsers = array_sum($roleStats);
    $response .= "\n📈 **Tổng quan:**\n";
    $response .= "• Tổng người dùng: {$totalUsers} người\n";
    $response .= "• Người dùng mới hôm nay: {$newUsersToday} người\n";
    $response .= "• Tỷ lệ khách hàng: " . 
                 round(($roleStats['khach_hang'] / $totalUsers) * 100, 1) . "%\n\n";
    $response .= "💡 *Mẹo:* Phân tích chi tiết trong phần báo cáo tài khoản.";
    
    return $response;
}

// Hàm lấy thông tin món ăn
function getFoodsInfo($conn) {
    // Top 5 món bán chạy
    $query = "SELECT m.ten_mon, COALESCE(SUM(c.so_luong), 0) as total_sold
              FROM MONAN m
              LEFT JOIN CHITIETDONHANG c ON m.monan_id = c.monan_id
              GROUP BY m.monan_id, m.ten_mon
              ORDER BY total_sold DESC
              LIMIT 5";
    
    $result = $conn->query($query);
    $topFoods = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $topFoods[] = [
                'name' => $row['ten_mon'],
                'sold' => (int)$row['total_sold']
            ];
        }
    }
    
    $response = "🍔 **Top món ăn bán chạy:**\n\n";
    
    if (empty($topFoods)) {
        $response .= "Chưa có dữ liệu món ăn bán chạy.\n";
    } else {
        foreach ($topFoods as $index => $food) {
            $response .= ($index + 1) . ". {$food['name']}: {$food['sold']} suất\n";
        }
    }
    
    // Tổng số món ăn
    $totalFoodsQuery = "SELECT COUNT(*) as count FROM MONAN";
    $totalResult = $conn->query($totalFoodsQuery);
    $totalFoods = $totalResult ? (int)$totalResult->fetch_assoc()['count'] : 0;
    
    $response .= "\n📊 **Tổng quan:**\n";
    $response .= "• Tổng món ăn: {$totalFoods} món\n";
    $response .= "• Món đang bán: " . ($totalFoods - 0) . " món\n\n"; // Có thể tính thêm món hết hàng
    $response .= "💡 *Mẹo:* Quản lý menu trong phần Quản lý thực đơn.";
    
    return $response;
}

// Hàm lấy thông tin báo cáo
function getReportInfo() {
    return "📈 **Các loại báo cáo có sẵn:**\n\n" .
           "1. **Báo cáo doanh thu**\n" .
           "   - Doanh thu theo thời gian\n" .
           "   - Phân tích theo sản phẩm\n" .
           "   - So sánh theo kênh bán\n\n" .
           "2. **Báo cáo đơn hàng**\n" .
           "   - Trạng thái đơn hàng\n" .
           "   - Tỷ lệ hoàn thành\n" .
           "   - Thời gian xử lý\n\n" .
           "3. **Báo cáo tài khoản**\n" .
           "   - Phân tích người dùng\n" .
           "   - Hoạt động tài khoản\n" .
           "   - Tăng trưởng người dùng\n\n" .
           "4. **Dashboard tổng quan**\n" .
           "   - Tổng hợp tất cả chỉ số\n" .
           "   - Biểu đồ trực quan\n" .
           "   - Cảnh báo tự động\n\n" .
           "🔗 *Truy cập:* Menu Báo cáo để xem chi tiết từng loại.";
}

// Hàm chào hỏi
function getGreetingResponse() {
    $greetings = [
        "Xin chào! Tôi là trợ lý AI của FoodGo. Tôi có thể giúp bạn phân tích dữ liệu và tạo báo cáo. 😊",
        "Chào bạn! Tôi sẵn sàng hỗ trợ bạn phân tích doanh thu, đơn hàng và người dùng. Cần tôi giúp gì?",
        "Hello! FoodGo AI Assistant đây. Tôi có thể giúp bạn hiểu rõ hơn về hoạt động kinh doanh của mình."
    ];
    
    return $greetings[array_rand($greetings)] . "\n\n" .
           "💡 *Gợi ý:* Bạn có thể hỏi về:\n" .
           "• Doanh thu hôm nay\n" .
           "• Tình trạng đơn hàng\n" .
           "• Thống kê người dùng\n" .
           "• Top món bán chạy";
}

// Hàm trợ giúp
function getHelpResponse() {
    return "🆘 **Hướng dẫn sử dụng Chatbot:**\n\n" .
           "**Các chức năng chính:**\n" .
           "• 📊 **Phân tích doanh thu**\n" .
           "  Hỏi: 'Doanh thu hôm nay thế nào?'\n" .
           "  Hỏi: 'Tổng doanh thu tháng này?'\n\n" .
           "• 📦 **Kiểm tra đơn hàng**\n" .
           "  Hỏi: 'Có bao nhiêu đơn đang chờ?'\n" .
           "  Hỏi: 'Tỷ lệ đơn thành công?'\n\n" .
           "• 👥 **Thống kê người dùng**\n" .
           "  Hỏi: 'Có bao nhiêu người dùng?'\n" .
           "  Hỏi: 'Người dùng mới hôm nay?'\n\n" .
           "• 🍔 **Phân tích món ăn**\n" .
           "  Hỏi: 'Món nào bán chạy nhất?'\n" .
           "  Hỏi: 'Tổng số món đang bán?'\n\n" .
           "• 📈 **Tạo báo cáo**\n" .
           "  Hỏi: 'Có những loại báo cáo nào?'\n" .
           "  Hỏi: 'Làm sao để tạo báo cáo?'\n\n" .
           "💡 *Mẹo:* Bạn cũng có thể click vào các nút câu hỏi nhanh để được hỗ trợ tức thì.";
}

// Hàm phản hồi chung
function getGeneralResponse($message, $context, $conn) {
    $responses = [
        "Tôi hiểu bạn đang hỏi về '{$message}'. Hiện tại tôi chưa được huấn luyện để trả lời câu hỏi này một cách chi tiết.",
        "Câu hỏi của bạn rất thú vị! Tuy nhiên, tôi chỉ có thể hỗ trợ về các vấn đề phân tích dữ liệu và báo cáo tại thời điểm này.",
        "Tôi chưa hoàn toàn hiểu câu hỏi của bạn. Bạn có thể hỏi về doanh thu, đơn hàng, người dùng hoặc món ăn được không?"
    ];
    
    $response = $responses[array_rand($responses)];
    
    // Thêm gợi ý
    $response .= "\n\n💡 *Gợi ý:* Bạn có thể thử hỏi:\n" .
                 "• 'Doanh thu hôm nay bao nhiêu?'\n" .
                 "• 'Có bao nhiêu đơn hàng đang chờ?'\n" .
                 "• 'Top món ăn bán chạy nhất?'";
    
    return $response;
}
?>