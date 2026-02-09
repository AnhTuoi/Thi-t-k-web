<?php
// Fontend/baocao/tongquan.php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Báo cáo tổng quan - FoodGo</title>
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
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
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
        .stat-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-delivering { background: #fef3c7; color: #92400e; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        /* Navigation Cards */
        .nav-card {
            background: linear-gradient(135deg, rgba(244, 140, 37, 0.05) 0%, rgba(244, 140, 37, 0.02) 100%);
            border: 1px solid rgba(244, 140, 37, 0.1);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(244, 140, 37, 0.3), transparent);
        }
        .nav-card:hover {
            transform: translateY(-8px);
            border-color: rgba(244, 140, 37, 0.3);
            box-shadow: 0 20px 40px rgba(244, 140, 37, 0.15);
        }
        .nav-card:hover .nav-card-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .nav-card-icon {
            transition: transform 0.3s ease;
        }
        .dark .nav-card {
            background: linear-gradient(135deg, rgba(244, 140, 37, 0.1) 0%, rgba(244, 140, 37, 0.05) 100%);
            border-color: rgba(244, 140, 37, 0.2);
        }
        
        /* Chatbot Floating Button */
        .chatbot-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f48c25 0%, #ff6b35 100%);
            border-radius: 50%;
            box-shadow: 0 8px 32px rgba(244, 140, 37, 0.4);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }
        .chatbot-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(244, 140, 37, 0.5);
        }
        .chatbot-btn .pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(244, 140, 37, 0.3);
            animation: pulse 2s infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        
        /* Chatbot Modal */
        .chatbot-modal {
            position: fixed;
            bottom: 100px;
            right: 24px;
            width: 380px;
            max-height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            z-index: 1001;
            display: none;
            overflow: hidden;
            border: 1px solid rgba(244, 140, 37, 0.2);
        }
        .dark .chatbot-modal {
            background: #1f1a15;
            border-color: rgba(244, 140, 37, 0.3);
        }
        .chatbot-header {
            background: linear-gradient(135deg, #f48c25 0%, #ff6b35 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chatbot-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #fefaf5;
        }
        .dark .chatbot-messages {
            background: #2a2015;
        }
        .message-bot {
            background: white;
            border: 1px solid #f4ede7;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            padding: 12px 16px;
            max-width: 80%;
            margin-bottom: 12px;
            animation: messageIn 0.3s ease-out;
        }
        .dark .message-bot {
            background: #3d2e1f;
            border-color: rgba(244, 140, 37, 0.2);
        }
        .message-user {
            background: #f48c25;
            color: white;
            border-radius: 18px;
            border-bottom-right-radius: 4px;
            padding: 12px 16px;
            max-width: 80%;
            margin-left: auto;
            margin-bottom: 12px;
            animation: messageIn 0.3s ease-out;
        }
        @keyframes messageIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 12px;
        }
        .quick-btn {
            background: rgba(244, 140, 37, 0.1);
            border: 1px solid rgba(244, 140, 37, 0.2);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 11px;
            color: #f48c25;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .quick-btn:hover {
            background: rgba(244, 140, 37, 0.2);
            transform: translateY(-1px);
        }
        .dark .quick-btn {
            background: rgba(244, 140, 37, 0.15);
            border-color: rgba(244, 140, 37, 0.3);
        }
        
        /* Header Navigation */
        .header-nav {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #f4ede7;
            margin-bottom: 20px;
        }
        .dark .header-nav {
            background: rgba(26, 20, 15, 0.8);
            border-color: #3d2e1f;
        }
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: white;
            border: 1px solid #f4ede7;
            border-radius: 12px;
            color: #1c140d;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .nav-btn:hover {
            background: #f48c25;
            color: white;
            border-color: #f48c25;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(244, 140, 37, 0.2);
        }
        .dark .nav-btn {
            background: #2a2015;
            border-color: #3d2e1f;
            color: #fcfaf8;
        }
        .dark .nav-btn:hover {
            background: #f48c25;
            color: white;
            border-color: #f48c25;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            .chatbot-modal {
                width: calc(100vw - 48px);
                right: 24px;
                left: 24px;
            }
            .header-nav {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
            .nav-btn span:last-child {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#1c140d] dark:text-[#fcfaf8] min-h-screen">
    <div class="layout-container">
        <!-- Header Navigation -->
        <div class="header-nav">
            <a href="tongquan.php" class="nav-btn">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Tổng quan</span>
            </a>
            <a href="doanhthu.php" class="nav-btn">
                <span class="material-symbols-outlined">trending_up</span>
                <span>Doanh thu</span>
            </a>
            <a href="donhang.php" class="nav-btn">
                <span class="material-symbols-outlined">receipt_long</span>
                <span>Đơn hàng</span>
            </a>
            <a href="taikhoan.php" class="nav-btn">
                <span class="material-symbols-outlined">account_circle</span>
                <span>Tài khoản</span>
            </a>
        </div>
        
        <!-- Main Content -->
        <main class="max-w-[1400px] mx-auto px-4 md:px-6 py-4">
            <!-- Summary Cards -->
            <section class="mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Doanh thu hôm nay -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="nav-card-icon h-12 w-12 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">payments</span>
                            </div>
                            <span class="text-xs font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded-full">
                                +12.5%
                            </span>
                        </div>
                        <h3 class="text-sm text-[#9c7349] mb-2">Doanh thu hôm nay</h3>
                        <div class="text-2xl md:text-3xl font-black text-[#1c140d] dark:text-white" id="today-revenue">0đ</div>
                        <div class="text-xs text-[#9c7349] mt-2">
                            <span id="today-orders">0</span> đơn hàng
                        </div>
                    </div>
                    
                    <!-- Tổng doanh thu -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="nav-card-icon h-12 w-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">trending_up</span>
                            </div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-full">
                                Tổng cộng
                            </span>
                        </div>
                        <h3 class="text-sm text-[#9c7349] mb-2">Tổng doanh thu</h3>
                        <div class="text-2xl md:text-3xl font-black text-[#1c140d] dark:text-white" id="total-revenue">0đ</div>
                        <div class="text-xs text-[#9c7349] mt-2">
                            Từ <span id="total-orders">0</span> đơn hàng
                        </div>
                    </div>
                    
                    <!-- Tổng người dùng -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="nav-card-icon h-12 w-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">group</span>
                            </div>
                            <span class="text-xs font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded-full">
                                +<span id="new-users-today">0</span> mới
                            </span>
                        </div>
                        <h3 class="text-sm text-[#9c7349] mb-2">Tổng người dùng</h3>
                        <div class="text-2xl md:text-3xl font-black text-[#1c140d] dark:text-white" id="total-users">0</div>
                        <div class="text-xs text-[#9c7349] mt-2">
                            <span id="active-users">0</span> đang hoạt động
                        </div>
                    </div>
                    
                    <!-- Đơn hàng chờ xử lý -->
                    <div class="stat-card bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="nav-card-icon h-12 w-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">pending_actions</span>
                            </div>
                            <span class="text-xs font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-2 py-1 rounded-full">
                                Cần xử lý
                            </span>
                        </div>
                        <h3 class="text-sm text-[#9c7349] mb-2">Đơn hàng chờ xử lý</h3>
                        <div class="text-2xl md:text-3xl font-black text-[#1c140d] dark:text-white" id="pending-orders">0</div>
                        <div class="text-xs text-[#9c7349] mt-2">
                            <span id="confirmed-orders">0</span> đã xác nhận
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Charts Section -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Doanh thu theo tháng -->
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Doanh thu theo tháng</h3>
                        <select id="revenue-chart-period" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                            <option value="12">12 tháng</option>
                            <option value="6">6 tháng</option>
                            <option value="3">3 tháng</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenue-chart"></canvas>
                    </div>
                </div>
                
                <!-- Trạng thái đơn hàng -->
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Trạng thái đơn hàng</h3>
                        <button onclick="window.open('donhang.php', '_blank')" class="text-sm text-primary font-medium hover:underline">
                            Xem chi tiết
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="order-status-chart"></canvas>
                    </div>
                </div>
            </section>
            
            <!-- Tables Section -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top món ăn bán chạy -->
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Top món ăn bán chạy</h3>
                        <select id="top-foods-limit" class="text-sm bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg px-3 py-1 border-0">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                            <option value="15">Top 15</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                                    <th class="text-left py-3 font-medium">STT</th>
                                    <th class="text-left py-3 font-medium">Món ăn</th>
                                    <th class="text-left py-3 font-medium">Đã bán</th>
                                    <th class="text-left py-3 font-medium">Đánh giá</th>
                                    <th class="text-left py-3 font-medium">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody id="top-foods-table">
                                <!-- Dữ liệu sẽ được load bằng JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Đơn hàng gần đây -->
                <div class="bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Đơn hàng gần đây</h3>
                        <button onclick="window.open('donhang.php', '_blank')" class="text-sm text-primary font-medium hover:underline">
                            Xem tất cả
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[#f4ede7] dark:border-[#3d2e1f]">
                                    <th class="text-left py-3 font-medium">Mã đơn</th>
                                    <th class="text-left py-3 font-medium">Khách hàng</th>
                                    <th class="text-left py-3 font-medium">Ngày đặt</th>
                                    <th class="text-left py-3 font-medium">Tổng tiền</th>
                                    <th class="text-left py-3 font-medium">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="recent-orders-table">
                                <!-- Dữ liệu sẽ được load bằng JS -->
                            </tbody>
                        </table>
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
        
        <!-- Chatbot Floating Button -->
        <div class="chatbot-btn" id="chatbot-toggle">
            <div class="pulse"></div>
            <span class="material-symbols-outlined text-white text-2xl">smart_toy</span>
        </div>
        
        <!-- Chatbot Modal -->
        <div class="chatbot-modal" id="chatbot-modal">
            <div class="chatbot-header">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined">smart_toy</span>
                    </div>
                    <div>
                        <h4 class="font-bold">FoodGo AI Assistant</h4>
                        <p class="text-sm opacity-90">Trợ lý báo cáo thông minh</p>
                    </div>
                </div>
                <button onclick="toggleChatbot()" class="ml-auto text-white/80 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="message-bot">
                    <p class="text-sm">Xin chào! Tôi là trợ lý AI của FoodGo. Tôi có thể giúp bạn phân tích dữ liệu và tạo báo cáo. Bạn cần hỗ trợ gì?</p>
                    <div class="quick-actions">
                        <button onclick="askQuickQuestion('Thống kê doanh thu hôm nay')" class="quick-btn">
                            📊 Doanh thu
                        </button>
                        <button onclick="askQuickQuestion('Đơn hàng đang chờ')" class="quick-btn">
                            📦 Đơn hàng
                        </button>
                        <button onclick="askQuickQuestion('Số lượng người dùng')" class="quick-btn">
                            👥 Người dùng
                        </button>
                        <button onclick="askQuickQuestion('Món ăn bán chạy')" class="quick-btn">
                            🍔 Món ăn
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                <div class="flex gap-2">
                    <input type="text" id="chatbot-input" 
                           class="flex-1 h-10 px-4 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] bg-transparent focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm" 
                           placeholder="Nhập câu hỏi về báo cáo..."
                           onkeyup="if(event.key === 'Enter') sendChatbotMessage()">
                    <button onclick="sendChatbotMessage()" class="h-10 px-4 bg-primary text-white rounded-xl hover:opacity-90 transition-all">
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // API Configuration
        const API_BASE_URL = '../../api';
        
        // Chart instances
        let revenueChart = null;
        let orderStatusChart = null;
        
        // Chatbot state
        let isChatbotOpen = false;
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            setupEventListeners();
            initChatbot();
        });
        
        // Setup event listeners
        function setupEventListeners() {
            // Chatbot toggle
            document.getElementById('chatbot-toggle').addEventListener('click', toggleChatbot);
            
            // Close chatbot when clicking outside
            document.addEventListener('click', function(e) {
                const chatbotModal = document.getElementById('chatbot-modal');
                const chatbotBtn = document.getElementById('chatbot-toggle');
                
                if (isChatbotOpen && 
                    !chatbotModal.contains(e.target) && 
                    !chatbotBtn.contains(e.target)) {
                    toggleChatbot();
                }
            });
        }
        
        // Initialize chatbot
        function initChatbot() {
            // Load initial message
            addChatbotMessage('Xin chào! Tôi là trợ lý AI của FoodGo. Tôi có thể giúp bạn phân tích dữ liệu và tạo báo cáo. Bạn cần hỗ trợ gì?', 'bot');
        }
        
        // Toggle chatbot
        function toggleChatbot() {
            const modal = document.getElementById('chatbot-modal');
            const btn = document.getElementById('chatbot-toggle');
            
            if (!isChatbotOpen) {
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.style.opacity = '1';
                    modal.style.transform = 'translateY(0)';
                }, 10);
                btn.innerHTML = '<div class="pulse"></div><span class="material-symbols-outlined text-white text-2xl">close</span>';
            } else {
                modal.style.opacity = '0';
                modal.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
                btn.innerHTML = '<div class="pulse"></div><span class="material-symbols-outlined text-white text-2xl">smart_toy</span>';
            }
            
            isChatbotOpen = !isChatbotOpen;
        }
        
        // Add chatbot message
        function addChatbotMessage(text, sender) {
            const messagesContainer = document.getElementById('chatbot-messages');
            
            const messageDiv = document.createElement('div');
            messageDiv.className = sender === 'user' ? 'message-user' : 'message-bot';
            messageDiv.innerHTML = `<p class="text-sm">${text}</p>`;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Ask quick question
        function askQuickQuestion(question) {
            document.getElementById('chatbot-input').value = question;
            sendChatbotMessage();
        }
        
        // Send chatbot message
        async function sendChatbotMessage() {
            const input = document.getElementById('chatbot-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addChatbotMessage(message, 'user');
            input.value = '';
            
            try {
                // Show typing indicator
                addTypingIndicator();
                
                // Simulate AI response (replace with actual API call)
                setTimeout(() => {
                    removeTypingIndicator();
                    
                    // Simulate different responses based on question
                    let response = '';
                    if (message.includes('doanh thu')) {
                        response = `Theo dữ liệu mới nhất, doanh thu hôm nay là <strong>${document.getElementById('today-revenue').textContent}</strong> từ <strong>${document.getElementById('today-orders').textContent}</strong> đơn hàng. Doanh thu có xu hướng tăng so với hôm qua.`;
                    } else if (message.includes('đơn hàng')) {
                        response = `Hiện có <strong>${document.getElementById('pending-orders').textContent}</strong> đơn hàng đang chờ xử lý. Tổng số đơn hàng đã xử lý là <strong>${document.getElementById('total-orders').textContent}</strong>.`;
                    } else if (message.includes('người dùng')) {
                        response = `Tổng số người dùng hiện tại là <strong>${document.getElementById('total-users').textContent}</strong>, trong đó có <strong>${document.getElementById('new-users-today').textContent}</strong> người dùng mới hôm nay.`;
                    } else if (message.includes('món ăn')) {
                        response = 'Đang phân tích dữ liệu về món ăn bán chạy... Bạn có thể xem chi tiết trong bảng "Top món ăn bán chạy" ở trên.';
                    } else {
                        response = 'Tôi đã nhận được câu hỏi của bạn. Hiện tại tôi có thể giúp bạn phân tích: doanh thu, đơn hàng, người dùng và món ăn bán chạy.';
                    }
                    
                    addChatbotMessage(response, 'bot');
                }, 1000);
                
            } catch (error) {
                removeTypingIndicator();
                console.error('Chatbot error:', error);
                addChatbotMessage('Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.', 'bot');
            }
        }
        
        // Add typing indicator
        function addTypingIndicator() {
            const messagesContainer = document.getElementById('chatbot-messages');
            
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'message-bot';
            typingDiv.innerHTML = `
                <div class="flex gap-1 items-center">
                    <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="h-2 w-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            `;
            
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Remove typing indicator
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        // Load dashboard data
        async function loadDashboardData() {
            showLoading();
            
            try {
                const [summaryData, revenueData, orderData, topFoodsData, recentOrdersData] = await Promise.all([
                    fetchAPI('laydulieu_tongquan.php', { action: 'get_dashboard_summary' }),
                    fetchAPI('laydulieu_tongquan.php', { action: 'get_revenue_statistics' }),
                    fetchAPI('laydulieu_tongquan.php', { action: 'get_order_statistics' }),
                    fetchAPI('laydulieu_tongquan.php', { action: 'get_top_foods', limit: 10 }),
                    fetchAPI('laydulieu_tongquan.php', { action: 'get_recent_orders', limit: 5 })
                ]);
                
                hideLoading();
                
                if (summaryData.success) {
                    updateSummaryCards(summaryData.data);
                }
                
                if (revenueData.success) {
                    renderRevenueChart(revenueData.data);
                }
                
                if (orderData.success) {
                    renderOrderStatusChart(orderData.data);
                }
                
                if (topFoodsData.success) {
                    renderTopFoodsTable(topFoodsData.data);
                }
                
                if (recentOrdersData.success) {
                    renderRecentOrdersTable(recentOrdersData.data);
                }
                
                showToast('Thành công', 'Đã tải dữ liệu dashboard', 'success');
                
            } catch (error) {
                hideLoading();
                console.error('Error loading dashboard data:', error);
                showToast('Lỗi', 'Không thể tải dữ liệu dashboard', 'error');
            }
        }
        
        // Update summary cards
        function updateSummaryCards(data) {
            document.getElementById('today-revenue').textContent = data.today_revenue_formatted;
            document.getElementById('today-orders').textContent = data.today_orders;
            document.getElementById('total-revenue').textContent = data.total_revenue_formatted;
            document.getElementById('total-orders').textContent = data.total_orders;
            document.getElementById('total-users').textContent = data.total_users.toLocaleString();
            document.getElementById('new-users-today').textContent = data.new_users_today;
            document.getElementById('pending-orders').textContent = data.pending_orders;
        }
        
        // Render revenue chart
        function renderRevenueChart(data) {
            const ctx = document.getElementById('revenue-chart').getContext('2d');
            
            if (revenueChart) {
                revenueChart.destroy();
            }
            
            const months = data.months.map(month => {
                const [year, monthNum] = month.split('-');
                return `${monthNum}/${year}`;
            });
            
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Doanh thu (VND)',
                        data: data.revenues,
                        borderColor: '#f48c25',
                        backgroundColor: 'rgba(244, 140, 37, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#f48c25',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `Doanh thu: ${context.raw.toLocaleString('vi-VN')}đ`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('vi-VN') + 'đ';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Render order status chart
        function renderOrderStatusChart(data) {
            const ctx = document.getElementById('order-status-chart').getContext('2d');
            
            if (orderStatusChart) {
                orderStatusChart.destroy();
            }
            
            const statusColors = {
                'cho_xac_nhan': '#f59e0b',
                'da_xac_nhan': '#3b82f6',
                'dang_giao': '#f59e0b',
                'da_giao': '#10b981',
                'da_huy': '#ef4444'
            };
            
            const backgroundColors = Object.keys(data.status_counts).map(status => 
                statusColors[status] || '#9ca3af'
            );
            
            orderStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.values(data.status_labels),
                    datasets: [{
                        data: Object.values(data.status_counts),
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: window.matchMedia('(prefers-color-scheme: dark)').matches ? '#2a2015' : '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
        
        // Render top foods table
        function renderTopFoodsTable(foods) {
            const tableBody = document.getElementById('top-foods-table');
            
            if (!foods || foods.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-[#9c7349]">Chưa có dữ liệu</td></tr>';
                return;
            }
            
            let html = '';
            foods.forEach((food, index) => {
                const revenue = food.price * food.total_sold;
                html += `
                    <tr class="hover:bg-[#f4ede7]/30 dark:hover:bg-[#3d2e1f]/30">
                        <td class="py-3 font-medium">${index + 1}</td>
                        <td class="py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg overflow-hidden">
                                    <img src="${food.image_url}" alt="${food.name}" class="h-full w-full object-cover">
                                </div>
                                <div>
                                    <div class="font-medium">${food.name}</div>
                                    <div class="text-xs text-[#9c7349]">${food.price_formatted}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="font-medium">${food.total_sold}</span>
                            <span class="text-xs text-[#9c7349]"> suất</span>
                        </td>
                        <td class="py-3">
                            <div class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-yellow-500 text-sm fill">star</span>
                                <span class="font-medium">${food.avg_rating}</span>
                            </div>
                        </td>
                        <td class="py-3 font-medium">
                            ${revenue.toLocaleString('vi-VN')}đ
                        </td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        // Render recent orders table
        function renderRecentOrdersTable(orders) {
            const tableBody = document.getElementById('recent-orders-table');
            
            if (!orders || orders.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-[#9c7349]">Chưa có đơn hàng</td></tr>';
                return;
            }
            
            let html = '';
            orders.forEach(order => {
                let statusClass = 'status-pending';
                let statusIcon = 'pending';
                
                switch (order.status) {
                    case 'da_xac_nhan':
                        statusClass = 'status-confirmed';
                        statusIcon = 'check_circle';
                        break;
                    case 'dang_giao':
                        statusClass = 'status-delivering';
                        statusIcon = 'local_shipping';
                        break;
                    case 'da_giao':
                        statusClass = 'status-delivered';
                        statusIcon = 'done_all';
                        break;
                    case 'da_huy':
                        statusClass = 'status-cancelled';
                        statusIcon = 'cancel';
                        break;
                }
                
                html += `
                    <tr class="hover:bg-[#f4ede7]/30 dark:hover:bg-[#3d2e1f]/30">
                        <td class="py-3">
                            <div class="font-medium">${order.id}</div>
                            <div class="text-xs text-[#9c7349]">${order.item_count} món</div>
                        </td>
                        <td class="py-3">
                            <div class="font-medium">${order.customer_name}</div>
                            <div class="text-xs text-[#9c7349]">${order.customer_id}</div>
                        </td>
                        <td class="py-3 text-[#9c7349]">${order.order_date_formatted}</td>
                        <td class="py-3 font-medium">${order.total_formatted}</td>
                        <td class="py-3">
                            <span class="${statusClass} status-badge">
                                <span class="material-symbols-outlined text-xs">${statusIcon}</span>
                                ${order.status_text}
                            </span>
                        </td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        // Utility functions
        async function fetchAPI(endpoint, params = {}) {
            try {
                const queryString = new URLSearchParams(params).toString();
                const url = `${API_BASE_URL}/${endpoint}${queryString ? '?' + queryString : ''}`;
                
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, message: error.message };
            }
        }
        
        function showLoading() {
            document.getElementById('loading-spinner').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loading-spinner').classList.add('hidden');
        }
        
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toast-icon');
            
            if (type === 'error') {
                toastIcon.className = 'h-10 w-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center';
                toastIcon.innerHTML = '<span class="material-symbols-outlined">error</span>';
            } else {
                toastIcon.className = 'h-10 w-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center';
                toastIcon.innerHTML = '<span class="material-symbols-outlined">check_circle</span>';
            }
            
            document.getElementById('toast-title').textContent = title;
            document.getElementById('toast-message').textContent = message;
            
            toast.classList.remove('hidden');
            setTimeout(hideToast, 3000);
        }
        
        function hideToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('hidden');
        }
    </script>
</body>
</html>