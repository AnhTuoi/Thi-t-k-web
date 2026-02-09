<?php
// Fontend/baocao/doanhthu.php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Báo cáo Doanh thu - FoodGo</title>
    <link rel="stylesheet" href="../css/dist.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0"></script>
    
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
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            .stat-card {
                padding: 16px;
            }
        }
        @media (max-width: 640px) {
            .chart-container {
                height: 200px;
            }
            .hide-on-mobile {
                display: none;
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
                        <span class="font-bold text-primary">Doanh thu</span>
                    </div>
                </div>
                
                <!-- Page Title -->
                <div class="flex-1">
                    <h1 class="text-lg md:text-xl font-black text-center">BÁO CÁO DOANH THU</h1>
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
                                <p class="text-xs text-[#9c7349]" id="filter-summary">Hôm nay - Tất cả danh mục</p>
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
                                    <option value="daily">Theo ngày</option>
                                    <option value="monthly">Theo tháng</option>
                                    <option value="yearly">Theo năm</option>
                                    <option value="category">Theo danh mục</option>
                                    <option value="food">Theo món ăn</option>
                                    <option value="payment">Theo phương thức TT</option>
                                    <option value="time">Theo khung giờ</option>
                                </select>
                            </div>
                            
                            <!-- Category Filter -->
                            <div id="category-filter-container">
                                <label class="block text-sm font-medium mb-2">Danh mục</label>
                                <select id="category-filter" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="all">Tất cả danh mục</option>
                                    <option value="mon_chinh">Món chính</option>
                                    <option value="mon_phu">Món phụ</option>
                                    <option value="do_uong">Đồ uống</option>
                                    <option value="trang_mieng">Tráng miệng</option>
                                    <option value="combo">Combo</option>
                                </select>
                            </div>
                            
                            <!-- Limit Results -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Số lượng hiển thị</label>
                                <select id="limit-results" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                                    <option value="10">10 kết quả</option>
                                    <option value="25">25 kết quả</option>
                                    <option value="50">50 kết quả</option>
                                    <option value="100">100 kết quả</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Quick Date Buttons -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button class="quick-date-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-days="1">
                                Hôm nay
                            </button>
                            <button class="quick-date-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-days="7">
                                7 ngày qua
                            </button>
                            <button class="quick-date-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-days="30">
                                30 ngày qua
                            </button>
                            <button class="quick-date-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-days="90">
                                3 tháng qua
                            </button>
                            <button class="quick-date-btn px-3 py-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg text-sm hover:bg-primary/20 transition-colors" data-days="365">
                                1 năm qua
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Revenue -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">payments</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Tổng doanh thu</div>
                                <div class="text-lg font-black" id="total-revenue">0đ</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Khoảng thời gian</span>
                            <span id="period-range" class="font-medium">Hôm nay</span>
                        </div>
                    </div>
                    
                    <!-- Total Orders -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">receipt_long</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Tổng đơn hàng</div>
                                <div class="text-lg font-black" id="total-orders">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Đơn trung bình</span>
                            <span id="avg-order-value" class="font-medium">0đ</span>
                        </div>
                    </div>
                    
                    <!-- Avg Daily Revenue -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">trending_up</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Doanh thu TB/ngày</div>
                                <div class="text-lg font-black" id="avg-daily-revenue">0đ</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Tăng trưởng</span>
                            <span id="revenue-growth" class="font-medium trend-neutral">0%</span>
                        </div>
                    </div>
                    
                    <!-- Unique Customers -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">group</span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#9c7349]">Khách hàng</div>
                                <div class="text-lg font-black" id="unique-customers">0</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#9c7349]">Đơn/Khách</span>
                            <span id="orders-per-customer" class="font-medium">0</span>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Tabs Navigation -->
            <section class="mb-6">
                <div class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                    <div class="flex overflow-x-auto no-scrollbar">
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap active" data-tab="chart">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">bar_chart</span>
                            Biểu đồ
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="table">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">table_chart</span>
                            Bảng dữ liệu
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="analysis">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">analytics</span>
                            Phân tích
                        </button>
                        <button class="tab-button px-4 py-3 text-sm font-medium whitespace-nowrap" data-tab="comparison">
                            <span class="material-symbols-outlined align-middle mr-2 text-base">compare</span>
                            So sánh
                        </button>
                    </div>
                </div>
            </section>
            
            <!-- Chart Tab -->
            <section id="chart-tab" class="tab-content active">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Main Chart -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold" id="chart-title">Doanh thu theo ngày</h3>
                            <div class="flex items-center gap-2">
                                <select id="chart-type" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                                    <option value="line">Đường</option>
                                    <option value="bar">Cột</option>
                                    <option value="area">Miền</option>
                                </select>
                                <button id="download-chart" class="h-8 w-8 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg flex items-center justify-center hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-base">download</span>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenue-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Secondary Chart 1 -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Doanh thu theo danh mục</h3>
                            <button onclick="loadRevenueByCategory()" class="text-sm text-primary font-medium hover:underline">
                                Xem chi tiết
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="category-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Secondary Chart 2 -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">Phương thức thanh toán</h3>
                            <button onclick="loadRevenueByPayment()" class="text-sm text-primary font-medium hover:underline">
                                Xem chi tiết
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="payment-chart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Table Tab -->
            <section id="table-tab" class="tab-content hidden">
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] overflow-hidden">
                    <!-- Table Header -->
                    <div class="p-4 border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold" id="table-title">Chi tiết doanh thu</h3>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-[#9c7349]">Tìm kiếm:</span>
                                    <input type="text" id="table-search" class="h-9 px-3 rounded-lg border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm" placeholder="Nhập từ khóa...">
                                </div>
                                <button id="export-table" class="h-9 px-4 bg-primary text-white rounded-lg text-sm font-medium hover:opacity-90 transition-colors">
                                    Xuất Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead id="table-header">
                                <!-- Header will be loaded by JS -->
                            </thead>
                            <tbody id="table-body">
                                <!-- Data will be loaded by JS -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Table Footer -->
                    <div class="p-4 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-[#9c7349]" id="table-info">
                                Hiển thị 0 đến 0 của 0 bản ghi
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
            </section>
            
            <!-- Analysis Tab -->
            <section id="analysis-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Insights -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">📊 Phân tích chi tiết</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="h-10 w-10 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined">trending_up</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Xu hướng doanh thu</h4>
                                        <p class="text-xs text-[#9c7349]" id="revenue-trend">Đang tính toán...</p>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm" id="revenue-trend-detail"></div>
                            </div>
                            
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined">schedule</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Giờ cao điểm</h4>
                                        <p class="text-xs text-[#9c7349]" id="peak-hours">Đang tính toán...</p>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm" id="peak-hours-detail"></div>
                            </div>
                            
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="h-10 w-10 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined">star</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold">Sản phẩm chủ lực</h4>
                                        <p class="text-xs text-[#9c7349]" id="top-products">Đang tính toán...</p>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm" id="top-products-detail"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommendations -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">💡 Đề xuất kinh doanh</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 bg-primary text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-sm">lightbulb</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-primary mb-1">Tối ưu giờ cao điểm</h4>
                                        <p class="text-sm" id="peak-hour-recommendation">Đang phân tích dữ liệu...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gradient-to-r from-green-100 to-green-50 dark:from-green-900/20 dark:to-green-900/10 border border-green-200 dark:border-green-800/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 bg-green-500 text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-sm">restaurant_menu</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-green-600 dark:text-green-400 mb-1">Phát triển sản phẩm</h4>
                                        <p class="text-sm" id="product-recommendation">Đang phân tích dữ liệu...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-gradient-to-r from-blue-100 to-blue-50 dark:from-blue-900/20 dark:to-blue-900/10 border border-blue-200 dark:border-blue-800/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 bg-blue-500 text-white rounded-lg flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-sm">campaign</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-blue-600 dark:text-blue-400 mb-1">Chiến lược khuyến mãi</h4>
                                        <p class="text-sm" id="promotion-recommendation">Đang phân tích dữ liệu...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Metrics -->
                <div class="mt-6 bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                    <h3 class="text-lg font-bold mb-4">📈 Chỉ số chi tiết</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                            <div class="text-2xl font-black text-primary mb-1" id="metric-conversion-rate">0%</div>
                            <div class="text-sm text-[#9c7349]">Tỷ lệ chuyển đổi</div>
                        </div>
                        <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                            <div class="text-2xl font-black text-blue-600 mb-1" id="metric-customer-value">0đ</div>
                            <div class="text-sm text-[#9c7349]">Giá trị khách hàng</div>
                        </div>
                        <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                            <div class="text-2xl font-black text-green-600 mb-1" id="metric-repeat-rate">0%</div>
                            <div class="text-sm text-[#9c7349]">Tỷ lệ quay lại</div>
                        </div>
                        <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                            <div class="text-2xl font-black text-purple-600 mb-1" id="metric-margin">0%</div>
                            <div class="text-sm text-[#9c7349">Biên lợi nhuận</div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Comparison Tab -->
            <section id="comparison-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Period Comparison -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold">So sánh theo kỳ</h3>
                            <div class="flex items-center gap-2">
                                <select id="comparison-period" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                                    <option value="month">Tháng này vs Tháng trước</option>
                                    <option value="year">Năm nay vs Năm ngoái</option>
                                    <option value="quarter">Quý này vs Quý trước</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="comparison-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Comparison Cards -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">So sánh Doanh thu</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-[#f4ede7]/30 dark:bg-[#3d2e1f]/30 rounded-xl">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium">Doanh thu hiện tại</span>
                                    <span class="font-bold" id="current-revenue-comp">0đ</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium">Doanh thu so sánh</span>
                                    <span class="font-bold" id="comparison-revenue">0đ</span>
                                </div>
                                <div class="mt-3 pt-3 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium">Chênh lệch</span>
                                        <span class="font-bold" id="revenue-difference">0đ</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium">Tỷ lệ tăng trưởng</span>
                                        <span class="font-bold trend-neutral" id="revenue-growth-percent">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparison Metrics -->
                    <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                        <h3 class="text-lg font-bold mb-4">Chỉ số so sánh</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Số đơn hàng</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" id="current-orders">0</span>
                                    <span class="material-symbols-outlined text-base text-[#9c7349]">trending_up</span>
                                    <span class="text-sm" id="order-growth">0%</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Giá trị đơn trung bình</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" id="current-avg-order">0đ</span>
                                    <span class="material-symbols-outlined text-base text-[#9c7349]">trending_up</span>
                                    <span class="text-sm" id="avg-order-growth">0%</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Khách hàng mới</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" id="current-new-customers">0</span>
                                    <span class="material-symbols-outlined text-base text-[#9c7349]">trending_up</span>
                                    <span class="text-sm" id="customer-growth">0%</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Tỷ lệ chuyển đổi</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" id="current-conversion">0%</span>
                                    <span class="material-symbols-outlined text-base text-[#9c7349]">trending_up</span>
                                    <span class="text-sm" id="conversion-growth">0%</span>
                                </div>
                            </div>
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
                                <h3 class="font-bold">Xuất báo cáo doanh thu</h3>
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
                            <option value="daily">Doanh thu theo ngày</option>
                            <option value="monthly">Doanh thu theo tháng</option>
                            <option value="category">Doanh thu theo danh mục</option>
                            <option value="food">Doanh thu theo món ăn</option>
                            <option value="summary">Tổng quan doanh thu</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Khoảng thời gian</label>
                        <div class="flex gap-2">
                            <input type="date" id="export-start-date" class="flex-1 h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                            <span class="flex items-center text-[#9c7349]">đến</span>
                            <input type="date" id="export-end-date" class="flex-1 h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Tên file</label>
                        <input type="text" id="export-filename" class="w-full h-10 px-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent text-sm" value="bao-cao-doanh-thu">
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

    <script src="../js/xulydoanhthu.js"></script>
    <script>
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize revenue report
            if (typeof window.revenueReport !== 'undefined') {
                window.revenueReport.init();
            }
        });
    </script>
</body>
</html>