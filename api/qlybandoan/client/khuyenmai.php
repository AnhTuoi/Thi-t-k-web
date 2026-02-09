<?php
// Load dữ liệu khuyến mãi từ API
$api_url = '/qlybandoan/api/khuyenmai/get_all.php';
$promotions = [];

try {
    // Kiểm tra xem API có thể truy cập được không
    $api_full_path = $_SERVER['DOCUMENT_ROOT'] . $api_url;
    
    // Gọi API để lấy dữ liệu
    $json_data = @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . $api_url);
    
    if ($json_data) {
        $promotions = json_decode($json_data, true);
        if (!is_array($promotions)) {
            $promotions = [];
        }
    }
} catch (Exception $e) {
    // Nếu có lỗi, để mảng rỗng
    $promotions = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Khuyến mãi - FoodGo</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f48c25",
                        "background-light": "#f8f7f5",
                        "background-dark": "#221910",
                        "secondary": "#10b981",
                        "danger": "#ef4444"
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "3xl": "1.5rem",
                        "full": "9999px"
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .promo-card {
            transition: all 0.3s ease;
        }
        .promo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .sidebar-icon {
            transition: all 0.3s ease;
        }
        .sidebar-icon:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#1c140d] dark:text-[#fcfaf8] min-h-screen">
    <div class="layout-container flex flex-col items-center">
        <!-- Header -->
        <header class="w-full sticky top-0 z-50 bg-white/90 dark:bg-background-dark/90 backdrop-blur-md border-b border-[#f4ede7] dark:border-[#3d2e1f] px-4 md:px-6 lg:px-8 xl:px-20 py-3">
            <div class="max-w-[1200px] mx-auto flex items-center justify-between gap-4 md:gap-8">
                <!-- Logo -->
                <a class="flex items-center gap-2 text-primary shrink-0" href="Trangchu.html">
                    <span class="material-symbols-outlined text-3xl md:text-4xl font-bold">fastfood</span>
                    <h2 class="text-xl md:text-2xl font-black tracking-tighter">FoodGo</h2>
                </a>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="lg:hidden p-2 rounded-xl bg-[#f4ede7] dark:bg-[#3d2e1f]">
                    <span class="material-symbols-outlined text-2xl">menu</span>
                </button>
                
                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-6 xl:gap-8 shrink-0">
                    <a class="text-sm font-bold text-[#9c7349] dark:text-gray-300 hover:text-primary transition-colors" href="Trangchu.html">Trang chủ</a>
                    <a class="text-sm font-bold text-[#9c7349] dark:text-gray-300 hover:text-primary transition-colors" href="Thucdon.html">Thực đơn</a>
                    <a class="text-sm font-bold text-primary transition-colors" href="Khuyenmai.html">Khuyến mãi</a>
                    <a class="text-sm font-bold text-[#9c7349] dark:text-gray-300 hover:text-primary transition-colors" href="Donhang.html">Đơn hàng</a>
                </nav>
                
                <!-- Search Bar (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-md mx-4">
                    <label class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-[#9c7349]">
                            <span class="material-symbols-outlined text-xl">search</span>
                        </span>
                        <input id="search-input" class="w-full h-10 md:h-11 pl-10 pr-4 rounded-2xl border-none bg-[#f4ede7] dark:bg-[#3d2e1f] text-[#1c140d] dark:text-white placeholder:text-[#9c7349] focus:ring-2 focus:ring-primary/50 transition-all text-sm" 
                               placeholder="Tìm kiếm mã giảm giá..." type="text"/>
                    </label>
                </div>
                
                <!-- User Actions -->
                <div class="flex items-center gap-3 md:gap-4 shrink-0">
                    <!-- Search Button (Mobile) -->
                    <button id="mobile-search-button" class="md:hidden p-2.5 rounded-2xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all">
                        <span class="material-symbols-outlined text-2xl">search</span>
                    </button>
                    
                    <!-- Auth Section -->
                    <div id="auth-section">
                        <!-- Not logged in -->
                        <div id="not-logged-in" style="display: none;">
                            <div class="flex items-center gap-2">
                                <a href="Dangnhap.html" class="px-4 md:px-6 py-2 md:py-2.5 bg-primary text-white font-bold rounded-2xl hover:opacity-90 transition-all text-sm whitespace-nowrap">
                                    Đăng nhập
                                </a>
                            </div>
                        </div>
                        
                        <!-- Logged in -->
                        <div id="logged-in" style="display: none;">
                            <div class="flex items-center gap-2 md:gap-3">
                                <!-- User Info (Desktop) -->
                                <div class="hidden md:block text-right">
                                    <p class="text-sm font-bold truncate max-w-[120px]" id="user-name"></p>
                                    <p class="text-[10px] text-[#9c7349] truncate max-w-[120px]" id="user-role"></p>
                                </div>
                                
                                <!-- User Avatar -->
                                <div class="h-9 md:h-10 w-9 md:w-10 rounded-2xl bg-cover bg-center border-2 border-primary cursor-pointer hover:scale-105 transition-transform" 
                                     style='background-image: url("https://lh3.googleusercontent.com/a/ACg8ocKx4vBv3k7QY2nHwW6tQ7Lm9J1pRfL6s3V2b5d8h0j");'>
                                </div>
                                
                                <!-- Logout Button -->
                                <button id="logout-button" class="p-2 md:p-2.5 rounded-2xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all">
                                    <span class="material-symbols-outlined text-xl">logout</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cart & Notifications -->
                    <div class="flex items-center gap-1 md:gap-2">
                        <a href="Giohang&TT.html" class="relative p-2 md:p-2.5 rounded-2xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all group">
                            <span class="material-symbols-outlined text-2xl group-hover:text-primary transition-colors">shopping_cart</span>
                            <span id="cart-count" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-extrabold text-white border-2 border-white dark:border-[#221910]">0</span>
                        </a>
                        <a href="Thongbao.html" class="p-2 md:p-2.5 rounded-2xl bg-[#f4ede7] dark:bg-[#3d2e1f] hover:bg-primary/20 transition-all group">
                            <span class="material-symbols-outlined text-2xl group-hover:text-primary transition-colors">notifications</span>
                            <span id="notification-count" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-extrabold text-white border-2 border-white dark:border-[#221910]">3</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Search Bar -->
            <div id="mobile-search-bar" class="md:hidden mt-3 hidden">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-[#9c7349]">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </span>
                    <input id="mobile-search-input" class="w-full h-11 pl-10 pr-4 rounded-2xl border-none bg-[#f4ede7] dark:bg-[#3d2e1f] text-[#1c140d] dark:text-white placeholder:text-[#9c7349] focus:ring-2 focus:ring-primary/50 transition-all text-sm" 
                           placeholder="Tìm kiếm mã giảm giá..." type="text"/>
                </div>
            </div>
            
            <!-- Mobile Navigation Menu -->
            <div id="mobile-menu" class="lg:hidden mt-3 hidden bg-white dark:bg-[#2a2015] rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] p-4">
                <nav class="space-y-3">
                    <a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:text-primary hover:bg-gray-50 dark:hover:bg-[#3d2e1f] rounded-xl transition-colors" href="Trangchu.html">
                        <span class="material-symbols-outlined">home</span>
                        Trang chủ
                    </a>
                    <a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:text-primary hover:bg-gray-50 dark:hover:bg-[#3d2e1f] rounded-xl transition-colors" href="Thucdon.html">
                        <span class="material-symbols-outlined">restaurant_menu</span>
                        Thực đơn
                    </a>
                    <a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-primary bg-primary/10 rounded-xl" href="Khuyenmai.html">
                        <span class="material-symbols-outlined">redeem</span>
                        Khuyến mãi
                    </a>
                    <a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:text-primary hover:bg-gray-50 dark:hover:bg-[#3d2e1f] rounded-xl transition-colors" href="Donhang.html">
                        <span class="material-symbols-outlined">receipt_long</span>
                        Đơn hàng
                    </a>
                    
                    <!-- Mobile User Info -->
                    <div id="mobile-user-info" class="pt-4 border-t border-[#f4ede7] dark:border-[#3d2e1f] hidden">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="h-10 w-10 rounded-xl bg-cover bg-center border-2 border-primary" 
                                 style='background-image: url("https://lh3.googleusercontent.com/a/ACg8ocKx4vBv3k7QY2nHwW6tQ7Lm9J1pRfL6s3V2b5d8h0j");'></div>
                            <div>
                                <p class="text-sm font-bold" id="mobile-user-name"></p>
                                <p class="text-xs text-[#9c7349]" id="mobile-user-role"></p>
                            </div>
                        </div>
                        <button id="mobile-logout-button" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors">
                            <span class="material-symbols-outlined">logout</span>
                            Đăng xuất
                        </button>
                    </div>
                    
                    <!-- Mobile Login Button -->
                    <div id="mobile-login-button" class="pt-4 border-t border-[#f4ede7] dark:border-[#3d2e1f]">
                        <a href="Dangnhap.html" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-white font-bold rounded-xl hover:opacity-90 transition-all">
                            <span class="material-symbols-outlined">login</span>
                            Đăng nhập
                        </a>
                    </div>
                </nav>
            </div>
        </header>

        
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

        <!-- Main Content -->
        <main id="khuyenMai" class="w-full max-w-[1200px] px-4 md:px-6 lg:px-8 xl:px-10 py-6 md:py-8">
            <div class="flex flex-col lg:flex-row gap-6 md:gap-8">
                <!-- Sidebar Filter -->
                <aside class="w-full lg:w-1/4 shrink-0">
                    <div class="sticky top-24 bg-white dark:bg-[#2a2015] p-5 md:p-6 rounded-2xl md:rounded-3xl border border-[#f4ede7] dark:border-[#3d2e1f] shadow-sm">
                        <div class="flex items-center justify-between mb-5 md:mb-6">
                            <h3 class="text-base md:text-lg font-bold">Bộ lọc khuyến mãi</h3>
                            <button id="clear-filters" class="text-xs text-primary font-bold hover:underline">Xóa tất cả</button>
                        </div>
                        
                        <!-- Loại khuyến mãi -->
                        <div class="mb-6 md:mb-8">
                            <h4 class="text-sm font-bold text-[#1c140d] dark:text-white mb-3 md:mb-4 uppercase tracking-wider">Loại khuyến mãi</h4>
                            <div class="flex flex-col gap-2 md:gap-3">
                                <label class="flex items-center gap-3 cursor-pointer group" data-type="all">
                                    <input type="checkbox" class="type-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary" checked>
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Tất cả</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group" data-type="percent">
                                    <input type="checkbox" class="type-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Giảm theo %</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group" data-type="cash">
                                    <input type="checkbox" class="type-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Giảm tiền mặt</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group" data-type="freeship">
                                    <input type="checkbox" class="type-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Miễn phí ship</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group" data-type="combo">
                                    <input type="checkbox" class="type-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Combo tiết kiệm</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Trạng thái -->
                        <div class="mb-6 md:mb-8">
                            <h4 class="text-sm font-bold text-[#1c140d] dark:text-white mb-3 md:mb-4 uppercase tracking-wider">Trạng thái</h4>
                            <div class="flex flex-col gap-2 md:gap-3">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="status-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary" data-status="active" checked>
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Đang hoạt động</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="status-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary" data-status="expiring">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Sắp hết hạn</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="status-checkbox w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary" data-status="popular">
                                    <span class="text-sm font-medium text-[#9c7349] dark:text-gray-300 group-hover:text-primary transition-colors">Phổ biến nhất</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Giá trị tối thiểu -->
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-[#1c140d] dark:text-white mb-3 md:mb-4 uppercase tracking-wider">Giá trị tối thiểu</h4>
                            <div class="px-2">
                                <input id="min-value-range" class="w-full h-1.5 bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-lg appearance-none cursor-pointer accent-primary" 
                                       max="500000" min="0" step="50000" type="range" value="100000"/>
                                <div class="flex justify-between mt-3">
                                    <span class="text-xs font-bold text-[#9c7349]">0đ</span>
                                    <span id="current-min-value" class="text-xs font-bold text-primary">100.000đ</span>
                                    <span class="text-xs font-bold text-[#9c7349]">500.000đ</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Áp dụng bộ lọc -->
                        <button id="apply-filters" class="w-full h-12 bg-primary text-white font-bold rounded-xl hover:opacity-90 transition-all">
                            Áp dụng bộ lọc
                        </button>
                    </div>
                </aside>
                
                <!-- Main Content -->
                <div class="flex-1">
                    <!-- Header với sắp xếp -->
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-6 md:mb-8 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-black tracking-tight">Khuyến mãi & Ưu đãi</h2>
                            
                        </div>
                    </div>
                    
            
                        <!-- Right Content - Promo Cards -->
                        <div class="flex-1">
                            <!-- Hot Deals Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold mb-4">Mã hot trong ngày</h3>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Promo Cards (keeping existing cards) -->
                                    <!-- Card 1 -->
                                    <div class="promo-card bg-white rounded-2xl overflow-hidden flex cursor-pointer border border-[#f4ede7]">
                                        <div class="w-32 h-32 shrink-0 bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                            <img src="https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=200&h=200&fit=crop" alt="Noodles" class="w-full h-full object-cover"/>
                                        </div>
                                        <div class="flex-1 p-4 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h4 class="font-bold text-lg mb-1">GIẢM 20K</h4>
                                                        <p class="text-xs text-[#9c7349]">Đơn từ 119.000đ</p>
                                                    </div>
                                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-semibold">Tận dùng!</span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">HSD: 30/6/2024</p>
                                            </div>
                                            <button class="self-start px-4 py-1.5 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all">
                                                Dùng ngay
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Card 2 -->
                                    <div class="promo-card bg-white rounded-2xl overflow-hidden flex cursor-pointer border border-[#f4ede7]">
                                        <div class="w-32 h-32 shrink-0 bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                                            <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=200&h=200&fit=crop" alt="Drink" class="w-full h-full object-cover"/>
                                        </div>
                                        <div class="flex-1 p-4 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h4 class="font-bold text-lg mb-1">GIẢM 15% TỔNG ĐƠN</h4>
                                                        <p class="text-xs text-[#9c7349]">Cho đơn từ 150k</p>
                                                    </div>
                                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-semibold">Tận dùng!</span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">HSD: 30/6/2024</p>
                                            </div>
                                            <button class="self-start px-4 py-1.5 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all">
                                                Dùng ngay
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Card 3 -->
                                    <div class="promo-card bg-white rounded-2xl overflow-hidden flex cursor-pointer border border-[#f4ede7]">
                                        <div class="w-32 h-32 shrink-0 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                                            <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=200&h=200&fit=crop" alt="Pizza" class="w-full h-full object-cover"/>
                                        </div>
                                        <div class="flex-1 p-4 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h4 class="font-bold text-lg mb-1">MIỄN PHÍ SHIP MAX</h4>
                                                        <p class="text-xs text-[#9c7349]">Đơn từ 50k - Tối đa 25k</p>
                                                    </div>
                                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Tận dùng!</span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">HSD: 28/6/2024</p>
                                            </div>
                                            <button class="self-start px-4 py-1.5 bg-secondary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all">
                                                Lưu mã
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Card 4 -->
                                    <div class="promo-card bg-white rounded-2xl overflow-hidden flex cursor-pointer border border-[#f4ede7]">
                                        <div class="w-32 h-32 shrink-0 bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                            <img src="https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=200&h=200&fit=crop" alt="Sushi" class="w-full h-full object-cover"/>
                                        </div>
                                        <div class="flex-1 p-4 flex flex-col justify-between">
                                            <div>
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <h4 class="font-bold text-lg mb-1">FREESHIP SUSHI</h4>
                                                        <p class="text-xs text-[#9c7349]">Đơn từ 50k - Tối đa 30%</p>
                                                    </div>
                                                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded font-semibold">Tận dùng!</span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">HSD: 11/7/2024</p>
                                            </div>
                                            <button class="self-start px-4 py-1.5 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all">
                                                Dùng ngay
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <!-- Pagination -->
                    <!-- Pagination Controls -->
                            <div class="pagination-controls"></div>
                        </div>
                    </div>
                </div>
            </main>


            <!-- Footer -->
            <footer class="bg-[#3d2314] text-white py-12">
                <div class="max-w-[1400px] mx-auto px-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8">
                        <!-- Column 1 - Logo & Description -->
                        <div class="lg:col-span-1">
                            <div class="flex items-center gap-2 text-primary mb-4">
                                <span class="material-symbols-outlined text-3xl font-bold">fastfood</span>
                                <h2 class="text-xl font-black tracking-tighter">FoodGo</h2>
                            </div>
                            <p class="text-sm text-gray-300 leading-relaxed">
                                FoodGo mang đến trải nghiệm ẩm thực tuyệt vời ngay tại nhà. Chúng tôi cam kết chất lượng món ăn và tốc độ giao hàng nhanh nhất.
                            </p>
                        </div>

                        <!-- Column 2 - Về FoodGo -->
                        <div>
                            <h3 class="font-bold text-base mb-4">Về FoodGo</h3>
                            <ul class="space-y-2 text-sm text-gray-300">
                                <li><a href="#" class="hover:text-primary transition-colors">Giới thiệu</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Cơ hội nghiệp</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Quan hệ Cổ đông</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Chính sách</a></li>
                            </ul>
                        </div>

                        <!-- Column 3 - Hỗ trợ khách hàng -->
                        <div>
                            <h3 class="font-bold text-base mb-4">Hỗ trợ khách hàng</h3>
                            <ul class="space-y-2 text-sm text-gray-300">
                                <li><a href="#" class="hover:text-primary transition-colors">Trung tâm hỗ trợ</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">An toàn vệ sinh thực phẩm</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Điều khoản dịch vụ</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Chính sách bảo mật</a></li>
                            </ul>
                        </div>

                        <!-- Column 4 - Liên kết khác -->
                        <div>
                            <h3 class="font-bold text-base mb-4">Liên kết khác</h3>
                            <ul class="space-y-2 text-sm text-gray-300">
                                <li><a href="#" class="hover:text-primary transition-colors">Đăng ký</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Khuyến mãi</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Thực đơn</a></li>
                                <li><a href="#" class="hover:text-primary transition-colors">Đơn hàng</a></li>
                            </ul>
                        </div>

                        <!-- Column 5 - Tải ứng dụng -->
                        <div>
                            <h3 class="font-bold text-base mb-4">Tải ứng dụng</h3>
                            <div class="space-y-3">
                                <a href="#" class="flex items-center gap-3 bg-black rounded-lg px-4 py-2 hover:opacity-80 transition-all">
                                    <i class="fab fa-google-play text-2xl"></i>
                                    <div>
                                        <p class="text-xs">Get it on</p>
                                        <p class="font-semibold">Google Play</p>
                                    </div>
                                </a>
                                <a href="#" class="flex items-center gap-3 bg-black rounded-lg px-4 py-2 hover:opacity-80 transition-all">
                                    <i class="fab fa-apple text-2xl"></i>
                                    <div>
                                        <p class="text-xs">Download on the</p>
                                        <p class="font-semibold">App Store</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Copyright -->
                    <div class="pt-6 border-t border-gray-600 flex flex-col md:flex-row items-center text-xs justify-between gap-4 text-gray-500">
                        
                    		<p class="text-center md:text-left mb-3 md:mb-0">© 2025 FoodGo. Bản quyền thuộc về FoodGo Team.</p>


                        <!-- Social Media Icons -->
                        <div class="flex items-center gap-4">
                            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-all">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-all">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-all">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-primary transition-all">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Data structure for vouchers - Load từ database qua PHP
        let allVouchers = <?php 
            // Chuyển đổi dữ liệu từ database sang format JavaScript
            $js_vouchers = [];
            $id_counter = 1;
            
            foreach ($promotions as $promo) {
                $js_vouchers[] = [
                    'id' => $id_counter++,
                    'title' => $promo['ma_khuyenmai'] ?? '',
                    'description' => $promo['mo_ta'] ?? '',
                    'expiry' => isset($promo['ngay_ket_thuc']) ? date('d/m/Y', strtotime($promo['ngay_ket_thuc'])) : '',
                    'type' => strtolower(str_replace(' ', '_', $promo['loai_giam_gia'] ?? 'discount_amount')),
                    'minValue' => floatval($promo['don_hang_toi_thieu'] ?? 0),
                    'image' => !empty($promo['hinh_anh']) ? $promo['hinh_anh'] : 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=200&h=200&fit=crop',
                    'saved' => false,
                    'discount_value' => floatval($promo['gia_tri_giam'] ?? 0),
                    'status' => $promo['trang_thai'] ?? 'dang_ap_dung',
                    'start_date' => $promo['ngay_bat_dau'] ?? '',
                    'end_date' => $promo['ngay_ket_thuc'] ?? ''
                ];
            }
            
            echo json_encode($js_vouchers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>;

        // Pagination state
        let currentPage = 1;
        let itemsPerPage = 6;
        let filteredVouchers = [...allVouchers];

        // Filter state
        let filters = {
            types: ['all'],
            status: ['active'],
            minValue: 0
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeSaveButtons();
            initializePagination();
            renderVouchers();
        });

        // Initialize pagination
        function initializePagination() {
            // Event listeners cho các nút phân trang sẽ được thêm khi render
        }

        // Get paginated vouchers
        function getPaginatedVouchers() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            return filteredVouchers.slice(startIndex, endIndex);
        }

        // Get total pages
        function getTotalPages() {
            return Math.ceil(filteredVouchers.length / itemsPerPage);
        }

        // Go to page
        function goToPage(page) {
            const totalPages = getTotalPages();
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            renderVouchers();
            
            // Scroll to top of content
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Render pagination controls
        function renderPagination() {
            const totalPages = getTotalPages();
            const paginationContainer = document.querySelector('.pagination-controls');
            
            if (!paginationContainer || totalPages <= 1) {
                if (paginationContainer) paginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <div class="flex items-center justify-center gap-2 mt-8">
                    <button onclick="goToPage(${currentPage - 1})" 
                            class="px-4 py-2 rounded-lg ${currentPage === 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-white border border-[#f4ede7] hover:bg-[#f4ede7]'} text-sm font-semibold transition-all"
                            ${currentPage === 1 ? 'disabled' : ''}>
                        <span class="material-symbols-outlined text-lg">chevron_left</span>
                    </button>
            `;

            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            // First page
            if (startPage > 1) {
                paginationHTML += `
                    <button onclick="goToPage(1)" class="w-10 h-10 rounded-lg bg-white border border-[#f4ede7] font-bold hover:bg-[#f4ede7] transition-all">
                        1
                    </button>
                `;
                if (startPage > 2) {
                    paginationHTML += `<span class="px-2">...</span>`;
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button onclick="goToPage(${i})" 
                            class="w-10 h-10 rounded-lg ${i === currentPage ? 'bg-primary text-white' : 'bg-white border border-[#f4ede7] hover:bg-[#f4ede7]'} font-bold transition-all">
                        ${i}
                    </button>
                `;
            }

            // Last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += `<span class="px-2">...</span>`;
                }
                paginationHTML += `
                    <button onclick="goToPage(${totalPages})" class="w-10 h-10 rounded-lg bg-white border border-[#f4ede7] font-bold hover:bg-[#f4ede7] transition-all">
                        ${totalPages}
                    </button>
                `;
            }

            paginationHTML += `
                    <button onclick="goToPage(${currentPage + 1})" 
                            class="px-4 py-2 rounded-lg ${currentPage === totalPages ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-white border border-[#f4ede7] hover:bg-[#f4ede7]'} text-sm font-semibold transition-all"
                            ${currentPage === totalPages ? 'disabled' : ''}>
                        <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
            `;

            // Result count
            const startItem = (currentPage - 1) * itemsPerPage + 1;
            const endItem = Math.min(currentPage * itemsPerPage, filteredVouchers.length);
            paginationHTML = `
                <div class="text-center text-sm text-gray-600 mb-4">
                    Hiển thị <span class="font-bold">${startItem}-${endItem}</span> trong tổng số <span class="font-bold">${filteredVouchers.length}</span> khuyến mãi
                </div>
            ` + paginationHTML;

            paginationContainer.innerHTML = paginationHTML;
        }

        // Initialize filter checkboxes
        function initializeFilters() {
            // Type filters
            const typeCheckboxes = document.querySelectorAll('input[type="checkbox"]');
            typeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleFilterChange);
            });

            // Price range slider
            const priceRange = document.querySelector('input[type="range"]');
            const priceDisplay = document.createElement('div');
            priceDisplay.className = 'text-sm font-bold text-primary text-center mb-2';
            priceDisplay.textContent = '0đ';
            priceRange.parentElement.insertBefore(priceDisplay, priceRange);

            priceRange.addEventListener('input', function() {
                const value = parseInt(this.value);
                priceDisplay.textContent = new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                filters.minValue = value;
            });

            // Apply filters button
            const applyButton = document.querySelector('button.bg-primary.w-full');
            if (applyButton) {
                applyButton.addEventListener('click', function() {
                    applyFilters();
                    showNotification('Đã áp dụng bộ lọc', 'success');
                });
            }

            // Clear all filters button
            const clearButton = document.querySelector('.text-primary.ml-auto');
            if (clearButton) {
                clearButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllFilters();
                    showNotification('Đã xóa tất cả bộ lọc', 'info');
                });
            }
        }

        // Handle filter checkbox change
        function handleFilterChange(e) {
            const checkbox = e.target;
            const filterSection = checkbox.closest('div[class*="space-y-2"]');
            const label = checkbox.parentElement.textContent.trim().toLowerCase();

            // Determine filter type
            if (label.includes('tất cả')) {
                // If "Tất cả" is checked, uncheck others
                if (checkbox.checked) {
                    filterSection.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        if (cb !== checkbox) cb.checked = false;
                    });
                }
            } else {
                // If any other is checked, uncheck "Tất cả"
                const allCheckbox = filterSection.querySelector('input[type="checkbox"]');
                if (checkbox.checked && allCheckbox) {
                    allCheckbox.checked = false;
                }
            }
        }

        // Apply filters
        function applyFilters() {
            filteredVouchers = allVouchers.filter(voucher => {
                // Filter by minimum value
                if (voucher.minValue < filters.minValue) {
                    return false;
                }

                // Filter by type
                const typeCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked');
                const selectedTypes = Array.from(typeCheckboxes)
                    .map(cb => cb.parentElement.textContent.trim().toLowerCase());

                if (!selectedTypes.some(type => type.includes('tất cả'))) {
                    let matchesType = false;
                    
                    if (selectedTypes.some(type => type.includes('giảm theo %')) && voucher.type === 'discount_percent') {
                        matchesType = true;
                    }
                    if (selectedTypes.some(type => type.includes('giảm tiền mặt')) && voucher.type === 'discount_amount') {
                        matchesType = true;
                    }
                    if (selectedTypes.some(type => type.includes('miễn phí ship')) && voucher.type === 'freeship') {
                        matchesType = true;
                    }
                    if (selectedTypes.some(type => type.includes('combo')) && voucher.type === 'combo') {
                        matchesType = true;
                    }

                    if (!matchesType && !selectedTypes.some(type => type.includes('tất cả'))) {
                        return false;
                    }
                }

                return true;
            });

            currentPage = 1; // Reset về trang 1
            renderVouchers();
        }

        // Clear all filters
        function clearAllFilters() {
            // Reset checkboxes
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
            allCheckboxes.forEach((cb, index) => {
                cb.checked = index === 0 || index === 6; // First checkbox in each section
            });

            // Reset price range
            const priceRange = document.querySelector('input[type="range"]');
            if (priceRange) {
                priceRange.value = 0;
                const priceDisplay = priceRange.previousElementSibling;
                if (priceDisplay) priceDisplay.textContent = '0đ';
            }

            filters.minValue = 0;
            filteredVouchers = [...allVouchers];
            currentPage = 1;
            renderVouchers();
        }

        // Initialize save buttons
        function initializeSaveButtons() {
            updateAllButtons();
        }

        // Update all save/use buttons based on saved state
        function updateAllButtons() {
            const cards = document.querySelectorAll('.promo-card');
            cards.forEach(card => {
                const voucherId = parseInt(card.dataset.id);
                const button = card.querySelector('button[class*="bg-"]');
                const voucher = allVouchers.find(v => v.id === voucherId);
                
                if (button && voucher) {
                    updateButtonState(button, voucher.saved);
                    
                    // Add click event based on saved state
                    if (voucher.saved) {
                        button.onclick = function(e) {
                            e.stopPropagation();
                            useVoucher(voucher.id, button);
                        };
                    } else {
                        button.onclick = function(e) {
                            e.stopPropagation();
                            toggleSaveVoucher(voucher.id, button);
                        };
                    }
                }
            });
        }

        // Toggle save voucher
        function toggleSaveVoucher(voucherId, button) {
            const voucher = allVouchers.find(v => v.id === voucherId);
            if (!voucher) return;

            // Nếu đang ở trạng thái "Lưu mã" (chưa lưu)
            if (!voucher.saved) {
                voucher.saved = true;
                
                // Chuyển sang màu xanh "Đã lưu" tạm thời
                button.className = 'self-start px-4 py-1.5 bg-secondary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all';
                button.innerHTML = '<i class="fas fa-check mr-1"></i> Đã lưu';
                
                // Hiển thị thông báo
                showNotification(`Lưu mã thành công: ${voucher.title}`, 'success');
                
                // Add animation
                button.classList.add('scale-95');
                setTimeout(() => button.classList.remove('scale-95'), 200);
                
                // Sau 1.5 giây chuyển sang "Dùng ngay" màu cam
                setTimeout(() => {
                    button.className = 'self-start px-4 py-1.5 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all';
                    button.innerHTML = 'Dùng ngay';
                    
                    // Thay đổi sự kiện click
                    button.onclick = function(e) {
                        e.stopPropagation();
                        useVoucher(voucher.id, button);
                    };
                }, 1500);
            }
        }

        // Use voucher (khi đã lưu rồi và click "Dùng ngay")
        function useVoucher(voucherId, button) {
            const voucher = allVouchers.find(v => v.id === voucherId);
            if (!voucher) return;

            showNotification(`Đang áp dụng mã: ${voucher.title}`, 'info');
            
            // Animation
            button.classList.add('scale-95');
            setTimeout(() => button.classList.remove('scale-95'), 200);
            
            // Có thể thêm logic chuyển trang hoặc mở modal ở đây
            setTimeout(() => {
                showNotification('Mã đã được áp dụng vào đơn hàng!', 'success');
            }, 800);
        }

        // Update button state
        function updateButtonState(button, isSaved) {
            if (isSaved) {
                // Nếu đã lưu rồi, hiển thị "Dùng ngay" màu cam
                button.className = 'self-start px-4 py-1.5 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all';
                button.innerHTML = 'Dùng ngay';
            } else {
                // Chưa lưu, kiểm tra text gốc
                const originalText = button.dataset.originalText || 'Lưu mã';
                if (originalText.includes('Lưu mã')) {
                    button.className = 'self-start px-4 py-1.5 bg-secondary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all';
                    button.innerHTML = 'Lưu mã';
                } else {
                    button.className = 'self-start px-4 py-1.5 bg-secondary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all';
                    button.innerHTML = 'Lưu mã';
                }
            }
        }

        // Render vouchers
        function renderVouchers() {
            const container = document.querySelector('.grid.grid-cols-1.lg\\:grid-cols-2');
            if (!container) return;

            const paginatedVouchers = getPaginatedVouchers();

            if (filteredVouchers.length === 0) {
                container.innerHTML = `
                    <div class="col-span-2 text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-gray-300">search_off</span>
                        <p class="text-gray-500 mt-4 text-lg">Không tìm thấy khuyến mãi phù hợp</p>
                        <button onclick="clearAllFilters()" class="mt-4 px-6 py-2 bg-primary text-white font-bold rounded-xl hover:opacity-90 transition-all">
                            Xóa bộ lọc
                        </button>
                    </div>
                `;
                renderPagination();
                return;
            }

            container.innerHTML = paginatedVouchers.map(voucher => `
                <div class="promo-card bg-white rounded-2xl overflow-hidden flex cursor-pointer border border-[#f4ede7]" data-id="${voucher.id}">
                    <div class="w-32 h-32 shrink-0 bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                        <img src="${voucher.image}" alt="${voucher.title}" class="w-full h-full object-cover"/>
                    </div>
                    <div class="flex-1 p-4 flex flex-col justify-between">
                        <div>
                            <div class="mb-2">
                                <h4 class="font-bold text-lg mb-1">${voucher.title}</h4>
                                <p class="text-xs text-[#9c7349]">${voucher.description}</p>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">HSD: ${voucher.expiry}</p>
                        </div>
                        <button class="${voucher.saved ? 'bg-primary' : 'bg-secondary'} self-start px-4 py-1.5 text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all" data-original-text="${voucher.saved ? 'Dùng ngay' : 'Lưu mã'}">
                            ${voucher.saved ? 'Dùng ngay' : 'Lưu mã'}
                        </button>
                    </div>
                </div>
            `).join('');

            // Re-attach event listeners
            updateAllButtons();
            
            // Render pagination
            renderPagination();
        }

        // Show notification
        function showNotification(message, type = 'info') {
            // Remove existing notification
            const existing = document.querySelector('.notification-toast');
            if (existing) existing.remove();

            // Create notification
            const notification = document.createElement('div');
            notification.className = 'notification-toast fixed top-24 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 animate-slide-in';
            
            const colors = {
                success: 'bg-secondary text-white',
                error: 'bg-danger text-white',
                info: 'bg-primary text-white'
            };
            
            const icons = {
                success: 'check_circle',
                error: 'error',
                info: 'info'
            };

            notification.classList.add(...colors[type].split(' '));
            notification.innerHTML = `
                <span class="material-symbols-outlined">${icons[type]}</span>
                <span class="font-semibold">${message}</span>
            `;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slide-in {
                from {
                    opacity: 0;
                    transform: translate(-50%, -20px);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, 0);
                }
            }
            .animate-slide-in {
                animation: slide-in 0.3s ease-out;
            }
            .notification-toast {
                transition: all 0.3s ease-out;
            }
            button {
                transition: all 0.2s ease;
            }
            button:active {
                transform: scale(0.95);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>