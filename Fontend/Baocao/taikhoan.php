<?php
// Fontend/baocao/taikhoan.php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Báo cáo Tài khoản - FoodGo</title>
    <link rel="stylesheet" href="../css/dist.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    
    <style type="text/css">
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f48c25;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .table-row-hover:hover {
            background: #f4ede7;
        }
        .dark .table-row-hover:hover {
            background: #3d2e1f;
        }
        .filter-card {
            transition: all 0.3s ease;
            max-height: 1000px;
            overflow: hidden;
        }
        .filter-card.collapsed {
            max-height: 60px;
        }
        .tab-button {
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab-button.active {
            border-bottom-color: #f48c25;
            color: #f48c25;
            font-weight: 700;
        }
        .export-btn {
            background: linear-gradient(135deg, #f48c25, #ff6b35);
            color: white;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 140, 37, 0.3);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background-size: cover;
            background-position: center;
            background-color: #f4ede7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #f48c25;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fef3c7; color: #92400e; }
        .status-pending { background: #dbeafe; color: #1e40af; }
        .status-locked { background: #fee2e2; color: #991b1b; }
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .role-customer { background: #dbeafe; color: #1e40af; }
        .role-staff { background: #fef3c7; color: #92400e; }
        .role-admin { background: #f3e8ff; color: #6b21a8; }
        .segment-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .segment-card:hover {
            transform: translateX(5px);
        }
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            .stat-card {
                padding: 16px;
            }
            .hide-on-mobile {
                display: none;
            }
        }
        @media (max-width: 640px) {
            .chart-container {
                height: 200px;
            }
        }
        .trend-up {
            color: #10b981;
        }
        .trend-down {
            color: #ef4444;
        }
        .trend-neutral {
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#1c140d] dark:text-[#fcfaf8] min-h-screen">
    <div class="layout-container">
        <!-- Header -->
        <header class="w-full sticky top-0 z-50 bg-white/90 dark:bg-background-dark/90 backdrop-blur-md border-b border-[#f4ede7] dark:border-[#3d2e1f] px-4 md:px-6 py-3">
            <div class="max-w-[1400px] mx-auto flex items-center justify-between gap-4">
                <!-- Logo và Breadcrumb -->
                <div class="flex items-center gap-3 md:gap-4">
                    <a class="flex items-center gap-2 text-[#f48c25] shrink-0" href="../../index.php">
                        <span class="material-symbols-outlined text-3xl font-bold">fastfood</span>
                        <h2 class="text-xl font-black tracking-tighter hidden md:block">FoodGo</h2>
                    </a>
                    <div class="hidden md:flex items-center gap-2 text-sm text-[#9c7349]">
                        <span class="material-symbols-outlined text-base">chevron_right</span>
                        <a href="tongquan.php" class="hover:text-primary">Báo cáo</a>
                        <span class="material-symbols-outlined text-base">chevron_right</span>
                        <span class="font-bold text-primary">Tài khoản</span>
                    </div>
                </div>
                
                <!-- Page Title -->
                <div class="flex-1">
                    <h1 class="text-lg md:text-xl font-black text-center">BÁO CÁO TÀI KHOẢN</h1>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <!-- Back to Dashboard -->
                    <a href="tongquan.php" class="p-2 rounded-xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all hidden md:block">
                        <span class="material-symbols-outlined">dashboard</span>
                    </a>
                    
                    <!-- Refresh -->
                    <button id="refresh-btn" class="p-2 rounded-xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all">
                        <span class="material-symbols-outlined">refresh</span>
                    </button>
                    
                    <!-- Export -->
                    <button id="export-btn" class="export-btn hidden md:flex items-center gap-2">
                        <span class="material-symbols-outlined">download</span>
                        Xuất báo cáo
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="max-w-[1400px] mx-auto px-4 md:px-6 py-6">
            <!-- Filter Section -->
            <section class="mb-6">
                <div id="filter-card" class="filter-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-4 transition-all duration-300">
                    <div class="flex items-center justify-between cursor-pointer" onclick="toggleFilterCard()">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined">filter_alt</span>
                            </div>
                            <div>
                                <h3 class="font-bold">Bộ lọc và Tùy chọn</h3>
                                <p class="text-xs text-[#9c7349]" id="filter-summary">Tất cả người dùng - Tất cả vai trò</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined transform transition-transform" id="filter-arrow">
                            expand_more
                        </span>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Date Range -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Khoảng thời gian</label>
                                <div class="flex gap-2">
                                    <input type="date" id="start-date" class="flex-1 h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <span class="flex items-center text-[#9c7349]">đến</span>
                                    <input type="date" id="end-date" class="flex-1 h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                </div>
                            </div>
                            
                            <!-- Report Type -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Loại báo cáo</label>
                                <select id="report-type" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="summary">Tổng quan</option>
                                    <option value="growth">Tăng trưởng</option>
                                    <option value="activity">Hoạt động</option>
                                    <option value="role">Theo vai trò</option>
                                    <option value="status">Theo trạng thái</option>
                                    <option value="segments">Phân khúc</option>
                                    <option value="top">Top người dùng</option>
                                    <option value="recent">Người dùng mới</option>
                                </select>
                            </div>
                            
                            <!-- Role Filter -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Vai trò</label>
                                <select id="role-filter" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="all">Tất cả vai trò</option>
                                    <option value="khach_hang">Khách hàng</option>
                                    <option value="nhan_vien">Nhân viên</option>
                                    <option value="quan_tri">Quản trị viên</option>
                                </select>
                            </div>
                            
                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Trạng thái</label>
                                <select id="status-filter" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="all">Tất cả trạng thái</option>
                                    <option value="hoat_dong">Hoạt động</option>
                                    <option value="vo_hieu_hoa">Vô hiệu hóa</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Quick Period Buttons -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button class="quick-period-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-period="today">
                                Hôm nay
                            </button>
                            <button class="quick-period-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-period="week">
                                Tuần này
                            </button>
                            <button class="quick-period-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-period="month">
                                Tháng này
                            </button>
                            <button class="quick-period-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-period="quarter">
                                Quý này
                            </button>
                            <button class="quick-period-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-period="year">
                                Năm nay
                            </button>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-end gap-3">
                            <button id="reset-filters" class="px-4 py-2 bg-gray-100 dark:bg-[#3d2e1f] rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-[#4a3929] transition-colors">
                                Đặt lại
                            </button>
                            <button id="apply-filters" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition-colors">
                                Áp dụng bộ lọc
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Summary Cards -->
            <section class="mb-8">
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Users -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">group</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Tổng người dùng</div>
                                <div class="text-lg font-black" id="total-users">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Hôm nay</span>
                            <span id="new-users-today" class="font-medium">+0</span>
                        </div>
                    </div>
                    
                    <!-- Active Users -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">check_circle</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Đang hoạt động</div>
                                <div class="text-lg font-black" id="active-users">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Tỷ lệ</span>
                            <span id="active-rate" class="font-medium">0%</span>
                        </div>
                    </div>
                    
                    <!-- New Users This Month -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">trending_up</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Mới trong tháng</div>
                                <div class="text-lg font-black" id="new-users-month">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Tăng trưởng</span>
                            <span id="growth-rate" class="font-medium trend-neutral">0%</span>
                        </div>
                    </div>
                    
                    <!-- VIP Users -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Người dùng VIP</div>
                                <div class="text-lg font-black" id="vip-users">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Tỷ lệ</span>
                            <span id="vip-rate" class="font-medium">0%</span>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Tabs Navigation -->
            <section class="mb-6">
                <div class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                    <div class="flex overflow-x-auto no-scrollbar">
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap active" data-tab="overview">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">dashboard</span>
                            Tổng quan
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="growth">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">trending_up</span>
                            Tăng trưởng
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="segments">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">category</span>
                            Phân khúc
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="activity">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">activity</span>
                            Hoạt động
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="users">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">group</span>
                            Danh sách
                        </button>
                    </div>
                </div>
            </section>
            
            <!-- Overview Tab -->
            <section id="overview-tab" class="tab-content active">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- User Growth Chart -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Tăng trưởng người dùng</h3>
                            <select id="growth-period" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                                <option value="12">12 tháng</option>
                                <option value="6">6 tháng</option>
                                <option value="3">3 tháng</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="user-growth-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Role Distribution -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Phân bố theo vai trò</h3>
                            <button onclick="loadUserByRole()" class="text-sm text-primary font-medium hover:underline">
                                Xem chi tiết
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="role-distribution-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Status Distribution -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Trạng thái tài khoản</h3>
                            <button onclick="loadUserByStatus()" class="text-sm text-primary font-medium hover:underline">
                                Xem chi tiết
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="status-distribution-chart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Growth Tab -->
            <section id="growth-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- New Users Trend -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Người dùng mới theo ngày</h3>
                        <div class="chart-container">
                            <canvas id="new-users-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Active Users Trend -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Người dùng hoạt động theo ngày</h3>
                        <div class="chart-container">
                            <canvas id="active-users-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Growth Metrics -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-4">Chỉ số tăng trưởng</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-2xl font-black text-blue-600 mb-1" id="metric-daily-growth">0</div>
                                <div class="text-sm text-[#9c7349]">Người dùng mới/ngày</div>
                            </div>
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-2xl font-black text-green-600 mb-1" id="metric-weekly-growth">0%</div>
                                <div class="text-sm text-[#9c7349]">Tăng trưởng tuần</div>
                            </div>
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-2xl font-black text-purple-600 mb-1" id="metric-monthly-growth">0%</div>
                                <div class="text-sm text-[#9c7349]">Tăng trưởng tháng</div>
                            </div>
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-2xl font-black text-orange-600 mb-1" id="metric-retention-rate">0%</div>
                                <div class="text-sm text-[#9c7349]">Tỷ lệ giữ chân</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Segments Tab -->
            <section id="segments-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- User Segments Chart -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Phân khúc người dùng</h3>
                        <div class="chart-container">
                            <canvas id="segments-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Segments List -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Chi tiết phân khúc</h3>
                        <div class="space-y-4" id="segments-list">
                            <!-- Segments will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Segment Insights -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-4">Phân tích phân khúc</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-gradient-to-r from-blue-100 to-blue-50 dark:from-blue-900/20 dark:to-blue-900/10 border border-blue-200 dark:border-blue-800/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 bg-blue-500 text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined">person_add</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-blue-600 dark:text-blue-400 mb-1">Người dùng mới</h4>
                                        <p class="text-sm" id="segment-new-insight">Đang phân tích...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gradient-to-r from-green-100 to-green-50 dark:from-green-900/20 dark:to-green-900/10 border border-green-200 dark:border-green-800/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 bg-green-500 text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined">verified_user</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-green-600 dark:text-green-400 mb-1">Người dùng VIP</h4>
                                        <p class="text-sm" id="segment-vip-insight">Đang phân tích...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gradient-to-r from-orange-100 to-orange-50 dark:from-orange-900/20 dark:to-orange-900/10 border border-orange-200 dark:border-orange-800/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 bg-orange-500 text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined">loyalty</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-orange-600 dark:text-orange-400 mb-1">Người dùng trung thành</h4>
                                        <p class="text-sm" id="segment-loyal-insight">Đang phân tích...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Activity Tab -->
            <section id="activity-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Login Activity -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Hoạt động đăng nhập</h3>
                            <select id="login-period" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                                <option value="30">30 ngày</option>
                                <option value="14">14 ngày</option>
                                <option value="7">7 ngày</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="login-activity-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- User Activity Metrics -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Chỉ số hoạt động</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium">Đăng nhập trung bình/ngày</div>
                                        <div class="text-2xl font-black text-primary" id="avg-daily-logins">0</div>
                                    </div>
                                    <div class="h-12 w-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                        <span class="material-symbols-outlined">login</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium">Người dùng hoạt động/ngày</div>
                                        <div class="text-2xl font-black text-green-600" id="avg-daily-active">0</div>
                                    </div>
                                    <div class="h-12 w-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center">
                                        <span class="material-symbols-outlined">activity</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium">Tỷ lệ đăng nhập/người dùng</div>
                                        <div class="text-2xl font-black text-blue-600" id="login-per-user">0</div>
                                    </div>
                                    <div class="h-12 w-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                                        <span class="material-symbols-outlined">trending_up</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Peak Activity Times -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-4">Thời điểm hoạt động cao nhất</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <!-- Peak times will be loaded here -->
                            <div class="text-center p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-lg font-black text-primary">08:00</div>
                                <div class="text-sm text-[#9c7349]">Sáng</div>
                                <div class="text-xs">Cao nhất</div>
                            </div>
                            <div class="text-center p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-lg font-black text-primary">12:00</div>
                                <div class="text-sm text-[#9c7349]">Trưa</div>
                                <div class="text-xs">Bình thường</div>
                            </div>
                            <div class="text-center p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="text-lg font-black text-primary">18:00</div>
                                <div class="text-sm text-[#9c7349]">Tối</div>
                                <div class="text-xs">Cao nhất</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Users Tab -->
            <section id="users-tab" class="tab-content hidden">
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] overflow-hidden">
                    <!-- Table Header -->
                    <div class="p-4 border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold">Danh sách người dùng</h3>
                                <p class="text-sm text-[#9c7349]" id="user-list-summary">Đang tải...</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-[#9c7349]">Tìm kiếm:</span>
                                    <input type="text" id="user-search" class="h-9 px-3 rounded-lg border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm" placeholder="Tên, email, số điện thoại...">
                                </div>
                                <select id="user-sort" class="h-9 px-3 rounded-lg border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="recent">Mới nhất</option>
                                    <option value="name">Tên A-Z</option>
                                    <option value="orders">Nhiều đơn nhất</option>
                                    <option value="revenue">Doanh thu cao</option>
                                </select>
                                <button id="export-users" class="h-9 px-4 bg-primary text-white rounded-lg text-sm font-medium hover:opacity-90 transition-colors">
                                    Xuất Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                                    <th class="text-left py-3 px-4 font-medium">Người dùng</th>
                                    <th class="text-left py-3 px-4 font-medium">Vai trò</th>
                                    <th class="text-left py-3 px-4 font-medium">Trạng thái</th>
                                    <th class="text-left py-3 px-4 font-medium hide-on-mobile">Ngày đăng ký</th>
                                    <th class="text-left py-3 px-4 font-medium hide-on-mobile">Đơn hàng</th>
                                    <th class="text-left py-3 px-4 font-medium hide-on-mobile">Doanh thu</th>
                                    <th class="text-left py-3 px-4 font-medium">Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <!-- Users will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Table Footer -->
                    <div class="p-4 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-[#9c7349]" id="users-table-info">
                                Hiển thị 0 đến 0 của 0 người dùng
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="prev-page" class="h-8 w-8 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="material-symbols-outlined text-base">chevron_left</span>
                                </button>
                                <div class="flex items-center gap-1">
                                    <span class="text-sm">Trang</span>
                                    <input type="number" id="current-page" class="h-8 w-12 text-center border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent rounded-lg text-sm" value="1" min="1">
                                    <span class="text-sm">/</span>
                                    <span class="text-sm" id="total-pages">1</span>
                                </div>
                                <button id="next-page" class="h-8 w-8 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="material-symbols-outlined text-base">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Users Section -->
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Top by Revenue -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Top doanh thu</h3>
                            <button onclick="loadTopUsers('revenue')" class="text-sm text-primary font-medium hover:underline">
                                Xem tất cả
                            </button>
                        </div>
                        <div class="space-y-3" id="top-revenue-users">
                            <!-- Top users by revenue will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Top by Orders -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Top đơn hàng</h3>
                            <button onclick="loadTopUsers('orders')" class="text-sm text-primary font-medium hover:underline">
                                Xem tất cả
                            </button>
                        </div>
                        <div class="space-y-3" id="top-orders-users">
                            <!-- Top users by orders will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Người dùng mới</h3>
                            <button onclick="loadRecentUsers()" class="text-sm text-primary font-medium hover:underline">
                                Xem tất cả
                            </button>
                        </div>
                        <div class="space-y-3" id="recent-users-list">
                            <!-- Recent users will be loaded here -->
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Export Section -->
            <section class="mt-8">
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 bg-primary text-white rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">download</span>
                            </div>
                            <div>
                                <h3 class="font-bold">Xuất báo cáo tài khoản</h3>
                                <p class="text-sm text-[#9c7349]">Tải báo cáo chi tiết ở nhiều định dạng</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button class="export-format-btn px-4 py-2 bg-white dark:bg-[#2a2015] border border-[#f4ede7] dark:border-[#3d2e1f] rounded-lg text-sm font-medium hover:bg-primary/10 transition-colors" data-format="pdf">
                                <span class="material-symbols-outlined align-middle mr-2">picture_as_pdf</span>
                                PDF
                            </button>
                            <button class="export-format-btn px-4 py-2 bg-white dark:bg-[#2a2015] border border-[#f4ede7] dark:border-[#3d2e1f] rounded-lg text-sm font-medium hover:bg-primary/10 transition-colors" data-format="excel">
                                <span class="material-symbols-outlined align-middle mr-2">table_chart</span>
                                Excel
                            </button>
                            <button class="export-format-btn px-4 py-2 bg-white dark:bg-[#2a2015] border border-[#f4ede7] dark:border-[#3d2e1f] rounded-lg text-sm font-medium hover:bg-primary/10 transition-colors" data-format="csv">
                                <span class="material-symbols-outlined align-middle mr-2">dataset</span>
                                CSV
                            </button>
                            <button class="export-format-btn px-4 py-2 bg-white dark:bg-[#2a2015] border border-[#f4ede7] dark:border-[#3d2e1f] rounded-lg text-sm font-medium hover:bg-primary/10 transition-colors" data-format="print">
                                <span class="material-symbols-outlined align-middle mr-2">print</span>
                                In ấn
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <!-- Loading Spinner -->
        <div id="loading-spinner" class="fixed inset-0 bg-black/20 flex items-center justify-center z-[1000] hidden">
            <div class="loading-spinner"></div>
        </div>
        
        <!-- Toast Notification -->
        <div id="toast" class="fixed top-4 right-4 z-[1000] hidden">
            <div class="bg-white dark:bg-[#2a2015] rounded-xl shadow-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-4 max-w-xs flex items-center gap-3 fade-in">
                <div id="toast-icon" class="h-10 w-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm" id="toast-title"></p>
                    <p class="text-xs text-[#9c7349]" id="toast-message"></p>
                </div>
                <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <!-- User Detail Modal -->
        <div id="user-detail-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] p-4">
            <div class="bg-white dark:bg-[#2a2015] rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#3d2e1f]">
                    <h3 class="text-xl font-bold">Chi tiết người dùng</h3>
                    <button onclick="closeUserDetail()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>
                
                <div class="overflow-y-auto max-h-[70vh] p-6">
                    <div id="user-detail-content">
                        <!-- User details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Modal -->
        <div id="export-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] p-4">
            <div class="bg-white dark:bg-[#2a2015] rounded-2xl p-6 max-w-md w-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Xuất báo cáo</h3>
                    <button onclick="closeExportModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Định dạng</label>
                        <select id="export-format" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV File</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Loại báo cáo</label>
                        <select id="export-report-type" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                            <option value="summary">Tổng quan người dùng</option>
                            <option value="growth">Báo cáo tăng trưởng</option>
                            <option value="segments">Phân tích phân khúc</option>
                            <option value="activity">Hoạt động người dùng</option>
                            <option value="list">Danh sách người dùng</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Tên file</label>
                        <input type="text" id="export-filename" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm" value="bao-cao-tai-khoan">
                    </div>
                </div>
                
                <div class="mt-6 flex gap-3">
                    <button onclick="closeExportModal()" class="flex-1 h-10 bg-gray-100 dark:bg-[#3d2e1f] text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-[#4a3929] transition-colors">
                        Hủy
                    </button>
                    <button onclick="processExport()" class="flex-1 h-10 bg-primary text-white rounded-xl font-medium hover:opacity-90 transition-colors">
                        Xuất file
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/xulytaikhoan.js"></script>
    <script>
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize user report
            if (typeof window.userReport !== 'undefined') {
                window.userReport.init();
            }
        });
    </script>
</body>
</html>