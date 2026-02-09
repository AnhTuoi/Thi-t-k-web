<?php
session_start();
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Dangnhap.html');
    exit();
}

// Kiểm tra quyền truy cập (chỉ admin và nhân viên)
if ($_SESSION['vai_tro'] == 'khach_hang') {
    header('Location: ../Shop/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo đơn hàng - FoodGo</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../css/dist.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body class="bg-[#f4ede7] dark:bg-[#1f1811] min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white dark:bg-[#2a2015] border-b border-[#f4ede7] dark:border-[#3d2e1f] shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <a href="../ADpage/Tongquan.html" class="text-2xl font-bold text-[#1c140d] dark:text-white">
                        <i class="fas fa-utensils text-[#e8956f]"></i> FoodGo
                    </a>
                    <span class="text-sm text-gray-500 dark:text-gray-400">/ Báo cáo đơn hàng</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="tongquan.php" class="text-sm text-gray-600 dark:text-gray-300 hover:text-[#e8956f] transition-colors">
                        <i class="fas fa-chart-line mr-1"></i> Tổng quan
                    </a>
                    <a href="doanhthu.php" class="text-sm text-gray-600 dark:text-gray-300 hover:text-[#e8956f] transition-colors">
                        <i class="fas fa-money-bill-wave mr-1"></i> Doanh thu
                    </a>
                    <a href="taikhoan.php" class="text-sm text-gray-600 dark:text-gray-300 hover:text-[#e8956f] transition-colors">
                        <i class="fas fa-users mr-1"></i> Tài khoản
                    </a>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                    <a href="../ADpage/Tongquan.html" class="text-sm text-gray-600 dark:text-gray-300 hover:text-[#e8956f] transition-colors">
                        <i class="fas fa-home mr-1"></i> Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-[#1c140d] dark:text-white mb-2">
                <i class="fas fa-shopping-cart text-[#e8956f] mr-3"></i>Báo cáo đơn hàng
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Phân tích và thống kê chi tiết về đơn hàng</p>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-[#1c140d] dark:text-white mb-4">
                    <i class="fas fa-filter text-[#e8956f] mr-2"></i>Bộ lọc thời gian
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loại thống kê</label>
                        <select id="loaiThongKe" class="input-field">
                            <option value="theo_thang">Theo tháng</option>
                            <option value="theo_nam">Theo năm</option>
                            <option value="chi_tiet_thang">Chi tiết theo ngày</option>
                            <option value="trang_thai">Theo trạng thái</option>
                            <option value="phuong_thuc_thanh_toan">Phương thức thanh toán</option>
                        </select>
                    </div>
                    <div id="thangNamGroup">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Năm</label>
                        <select id="selectNam" class="input-field">
                            <?php
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                echo "<option value='$year'" . ($year == $currentYear ? ' selected' : '') . ">$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="thangGroup">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tháng</label>
                        <select id="selectThang" class="input-field">
                            <?php
                            $months = [
                                1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3',
                                4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6',
                                7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9',
                                10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
                            ];
                            $currentMonth = date('n');
                            foreach ($months as $num => $name) {
                                echo "<option value='$num'" . ($num == $currentMonth ? ' selected' : '') . ">$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="namRangeGroup" class="hidden">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Từ năm</label>
                                <input type="number" id="namBatDau" value="<?php echo date('Y') - 2; ?>" class="input-field">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Đến năm</label>
                                <input type="number" id="namKetThuc" value="<?php echo date('Y'); ?>" class="input-field">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button id="btnTaiDuLieu" class="btn-primary">
                        <i class="fas fa-sync-alt mr-2"></i>Tải dữ liệu
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="summaryCards">
            <!-- Cards sẽ được cập nhật bằng JavaScript -->
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Chart 1: Biểu đồ đơn hàng -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-[#1c140d] dark:text-white mb-4">
                        <i class="fas fa-chart-bar text-[#e8956f] mr-2"></i>Biểu đồ đơn hàng
                    </h2>
                    <div class="h-80">
                        <canvas id="donHangChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Trạng thái đơn hàng -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-[#1c140d] dark:text-white mb-4">
                        <i class="fas fa-pie-chart text-[#e8956f] mr-2"></i>Phân phối trạng thái
                    </h2>
                    <div class="h-80">
                        <canvas id="trangThaiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card mb-8">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-[#1c140d] dark:text-white">
                        <i class="fas fa-table text-[#e8956f] mr-2"></i>Danh sách đơn hàng
                    </h2>
                    <div class="flex space-x-2">
                        <button id="btnXuatExcel" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                        </button>
                        <button id="btnXuatPDF" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-[#3d2e1f]" id="donHangTable">
                        <thead class="bg-gray-50 dark:bg-[#3d2e1f]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mã đơn</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ngày đặt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Khách hàng</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Số món</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tổng tiền</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PTTT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#2a2015] divide-y divide-gray-200 dark:divide-[#3d2e1f]" id="tableBody">
                            <!-- Dữ liệu sẽ được thêm bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Hiển thị <span id="currentCount">0</span> trong tổng số <span id="totalCount">0</span> đơn hàng
                    </div>
                    <div class="flex space-x-2" id="pagination">
                        <!-- Phân trang sẽ được thêm bằng JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Insights -->
        <div class="card">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-[#1c140d] dark:text-white mb-4">
                    <i class="fas fa-lightbulb text-[#e8956f] mr-2"></i>Nhận xét và đề xuất
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="insightsContainer">
                    <!-- Insights sẽ được thêm bằng JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-[#2a2015] rounded-xl p-8 max-w-md w-full mx-4">
            <div class="flex flex-col items-center">
                <div class="loading mb-4"></div>
                <p class="text-lg font-medium text-[#1c140d] dark:text-white">Đang tải dữ liệu...</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Vui lòng chờ trong giây lát</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/xulydonhang.js"></script>
    <script>
        // Khởi tạo khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            // Gắn sự kiện cho bộ lọc
            document.getElementById('loaiThongKe').addEventListener('change', function() {
                toggleFilterGroups(this.value);
            });
            
            // Gắn sự kiện cho nút tải dữ liệu
            document.getElementById('btnTaiDuLieu').addEventListener('click', loadDonHangData);
            
            // Tải dữ liệu ban đầu
            loadDonHangData();
            
            // Khởi tạo các biểu đồ rỗng
            initCharts();
        });
        
        function toggleFilterGroups(loai) {
            const thangNamGroup = document.getElementById('thangNamGroup');
            const thangGroup = document.getElementById('thangGroup');
            const namRangeGroup = document.getElementById('namRangeGroup');
            
            switch(loai) {
                case 'theo_thang':
                case 'chi_tiet_thang':
                case 'trang_thai':
                    thangNamGroup.classList.remove('hidden');
                    thangGroup.classList.remove('hidden');
                    namRangeGroup.classList.add('hidden');
                    break;
                case 'theo_nam':
                    thangNamGroup.classList.add('hidden');
                    thangGroup.classList.add('hidden');
                    namRangeGroup.classList.remove('hidden');
                    break;
                default:
                    thangNamGroup.classList.remove('hidden');
                    thangGroup.classList.add('hidden');
                    namRangeGroup.classList.add('hidden');
            }
        }
    </script>
</body>
</html>