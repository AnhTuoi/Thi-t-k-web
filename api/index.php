<?php
// Không cần fetch từ PHP nữa, sẽ để JavaScript xử lý
// Điều này giúp trang load nhanh hơn và dễ quản lý hơn
?>
<!DOCTYPE html>
<html class="light" lang="vi"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quản lý Khuyến mãi Admin FoodGo</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f48c25",
                        "background-light": "#f8f7f5",
                        "sidebar-bg": "#ffffff",
                        "card-bg": "#ffffff",
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
                    },
                },
            },
        }
    </script>
<style type="text/tailwindcss">
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .active-menu {
            @apply bg-primary/10 text-primary border-r-4 border-primary;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        #chatbot-window {
            display: none;
        }
        #chatbot-toggle:focus-within + #chatbot-window,
        #chatbot-window:hover,
        #chatbot-window:focus-within {
            display: flex;
        }
    </style>
</head>
<body class="bg-background-light text-[#1c140d] min-h-screen flex">
<aside class="w-64 bg-sidebar-bg border-r border-[#f4ede7] flex flex-col fixed h-full z-50 left-0 top-0">
<div class="p-6 flex items-center gap-2 text-primary shrink-0">
<span class="material-symbols-outlined text-4xl font-bold">fastfood</span>
<h2 class="text-xl font-black tracking-tighter">FoodGo <span class="text-[10px] bg-primary text-white px-1.5 py-0.5 rounded ml-1 uppercase">Admin</span></h2>
</div>
<nav class="flex-1 px-4 space-y-1 overflow-y-auto no-scrollbar">
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="Tongquan.html">
<span class="material-symbols-outlined">dashboard</span>
            Tổng quan
        </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="QLthucdon.html">
<span class="material-symbols-outlined">restaurant_menu</span>
            Món ăn
        </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="QLdon.html">
<span class="material-symbols-outlined">receipt_long</span>
            Đơn hàng
        </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="QLnguoidung.html">
<span class="material-symbols-outlined">group</span>
            Người dùng
        </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="QLtaikhoan.html">
<span class="material-symbols-outlined">key</span>
                Tài khoản
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold active-menu rounded-lg transition-all" href="QLkhuyenmai.html">
<span class="material-symbols-outlined">redeem</span>
                Khuyến mãi
            </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="QLdanhgia.html">
<span class="material-symbols-outlined">star</span>
                Đánh giá
            </a>
<div class="pt-4 mt-4 border-t border-[#f4ede7]">
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="TKrieng.html">
<span class="material-symbols-outlined">person</span>
                    Tài khoản của tôi
                </a>
<a class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-[#9c7349] hover:bg-gray-50 hover:text-primary rounded-lg transition-all" href="Caidat.html">
<span class="material-symbols-outlined">settings</span>
                Cài đặt
            </a>
</div>
</nav>
<div class="p-4 border-t border-[#f4ede7]">
<div class="flex items-center gap-3 p-2 bg-[#f4ede7] rounded-2xl">
<div class="h-10 w-10 rounded-xl bg-cover bg-center border border-white" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCa-A_xiobVGkBwC98jkh3MfvIrJEVa75kurLiQOhyxLHiqGDmWSsVr_3YTAoQYZRPXRahwTNzZ-dbYqIqOHu7aoq0os0D0gWI2Hs1R73wlAgMKuVw6wQ-tOxile4ilTWsYrJeyspkP0VAn11Fjld90gLhccOvG5qah2fn0VZ8gg3bqja4zkLKIb04lGzuBtFVdm9JuODdXB16H5j2gDoRiGFOEUVv2Qa8kEbuzaZGy4kqLSkOeL060s65bgJBfG73sSlq7ro4ydPw");'></div>
<div class="overflow-hidden">
<p class="text-xs font-bold truncate">Admin FoodGo</p>
<p class="text-[10px] text-[#9c7349]">Quản trị viên</p>
</div>
</div>
</div>
</aside>
<main class="flex-1 ml-64 p-8 flex justify-center">
<div class="w-full max-w-[1400px]">
<header class="mb-8">
<div class="flex items-center justify-between mb-6">
<div>
<h1 class="text-2xl font-black tracking-tight text-[#1c140d]">Quản lý khuyến mãi</h1>
<p class="text-sm text-[#9c7349]">Tạo và quản lý các chương trình ưu đãi cho khách hàng</p>
</div>
<div class="flex items-center gap-3">
<button id="btn-open-promo-modal" class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all">
<span class="material-symbols-outlined text-xl">add_circle</span>
                        Thêm khuyến mãi mới
                    </button>
</div>
</div>
<div class="flex items-center justify-between border-b border-[#f4ede7] pb-4">
<div class="flex gap-8">
<button class="filter-tab text-sm font-bold text-primary border-b-2 border-primary pb-4" data-filter="all">Tất cả</button>
<button class="filter-tab text-sm font-bold text-[#9c7349] hover:text-primary transition-all pb-4" data-filter="dang_ap_dung">Đang áp dụng</button>
<button class="filter-tab text-sm font-bold text-[#9c7349] hover:text-primary transition-all pb-4" data-filter="khong_ap_dung">Tạm dừng</button>
<button class="filter-tab text-sm font-bold text-[#9c7349] hover:text-primary transition-all pb-4" data-filter="het_han">Hết hạn</button>
</div>
<div class="flex items-center gap-4">
<div class="relative">
<span class="absolute inset-y-0 left-0 flex items-center pl-3 text-[#9c7349]">
<span class="material-symbols-outlined text-xl">search</span>
</span>
<input id="promo-search" class="h-10 pl-10 pr-4 rounded-xl border-none bg-white shadow-sm text-sm w-64 focus:ring-2 focus:ring-primary/50 transition-all" placeholder="Tìm kiếm mã, mô tả..." type="text"/>
</div>
</div>
</div>
</header>
<section class="bg-white rounded-3xl border border-[#f4ede7] shadow-sm overflow-hidden mb-12">
<div class="overflow-x-auto">
<table class="w-full text-left text-sm" id="promo-table">
<thead class="bg-[#fcfaf8] text-[#9c7349] text-[11px] font-bold uppercase tracking-wider">
<tr>
<th class="px-4 py-3 w-[8%]">Hình ảnh</th>
<th class="px-4 py-3 w-[10%]">Mã KM</th>
<th class="px-4 py-3 w-[18%]">Tên KM</th>
<th class="px-4 py-3 w-[10%]">Loại</th>
<th class="px-4 py-3 w-[9%]">Giảm</th>
<th class="px-4 py-3 w-[10%]">Ngày bắt đầu</th>
<th class="px-4 py-3 w-[10%]">Ngày kết thúc</th>
<th class="px-4 py-3 w-[10%]">Trạng thái</th>
<th class="px-4 py-3 w-[10%] text-center">Thao tác</th>
</tr>
</thead>
<tbody id="promo-table-body" class="divide-y divide-[#f4ede7]">
<!-- Loading state -->
<tr>
<td colspan="9" class="px-4 py-12 text-center">
<div class="flex flex-col items-center gap-3">
<div class="animate-spin rounded-full h-8 w-8 border-4 border-primary border-t-transparent"></div>
<p class="text-sm text-[#9c7349]">Đang tải dữ liệu...</p>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<div class="p-6 border-t border-[#f4ede7] flex items-center justify-between">
<p class="text-sm text-[#9c7349]">
Hiển thị <span id="promo-count-range">0-0</span> trong tổng số <span id="promo-count-total">0</span> chương trình
</p>
<div id="pagination-container" class="flex items-center gap-2">
<!-- Pagination buttons will be rendered here by JavaScript -->
</div>
</div>
</section>
</div>
</main>

<!-- Modal thêm/sửa khuyến mãi -->
<div id="promo-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
<div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
<div class="sticky top-0 bg-white border-b border-[#f4ede7] p-6 flex items-center justify-between rounded-t-3xl z-10">
<h3 id="modal-title" class="text-xl font-black text-[#1c140d]">Thêm khuyến mãi mới</h3>
<button id="btn-close-promo-modal" class="text-[#9c7349] hover:text-primary">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<form id="promo-form" class="p-6 space-y-5">
<!-- Hình ảnh -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Hình ảnh khuyến mãi</label>
<div class="space-y-3">
<input id="input-image" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" placeholder="Nhập URL hình ảnh..." type="text"/>
<div class="flex items-center gap-2">
<span class="text-xs text-[#9c7349]">hoặc</span>
<label class="flex items-center gap-2 px-4 py-2 bg-[#fcfaf8] hover:bg-[#f4ede7] rounded-lg cursor-pointer text-sm font-medium text-[#9c7349] transition-all">
<span class="material-symbols-outlined text-lg">upload</span>
                                Tải lên hình ảnh
                                <input id="input-image-file" type="file" accept="image/*" class="hidden"/>
</label>
</div>
<div id="image-preview" class="hidden mt-3">
<img id="image-preview-img" class="w-full h-48 object-cover rounded-xl border border-[#f4ede7]" src="" alt="Preview"/>
</div>
</div>
</div>

<!-- Mã khuyến mãi -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Mã khuyến mãi <span class="text-red-500">*</span></label>
<input id="input-code" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" placeholder="VD: FREESHIP50" required type="text"/>
</div>

<!-- Tên khuyến mãi -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Tên khuyến mãi <span class="text-red-500">*</span></label>
<input id="input-name" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" placeholder="VD: Miễn phí vận chuyển đơn từ 50k" required type="text"/>
</div>

<!-- Loại giảm giá -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Loại giảm giá <span class="text-red-500">*</span></label>
<div class="flex gap-2">
<select id="input-type" class="flex-1 px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" required>
<!-- Options will be rendered by JavaScript -->
</select>
<button id="add-promo-type" type="button" class="px-4 py-2.5 bg-[#fcfaf8] hover:bg-[#f4ede7] rounded-xl text-sm font-medium text-[#9c7349] transition-all">
<span class="material-symbols-outlined">add</span>
</button>
</div>
</div>

<!-- Giá trị giảm -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Giá trị giảm <span class="text-red-500">*</span></label>
<input id="input-value" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" min="0" placeholder="VD: 20000" required type="number"/>
<p class="text-xs text-[#9c7349] mt-1">Nhập số tiền (VNĐ) hoặc % giảm giá</p>
</div>

<!-- Đơn hàng tối thiểu -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Giá trị đơn hàng tối thiểu</label>
<input id="input-min-order" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" min="0" placeholder="VD: 50000" type="number" value="0"/>
</div>

<!-- Ngày bắt đầu và kết thúc -->
<div class="grid grid-cols-2 gap-4">
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Ngày bắt đầu <span class="text-red-500">*</span></label>
<input id="input-start-date" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" required type="date"/>
</div>
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Ngày kết thúc <span class="text-red-500">*</span></label>
<input id="input-end-date" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" required type="date"/>
</div>
</div>

<!-- Trạng thái -->
<div>
<label class="block text-sm font-bold text-[#1c140d] mb-2">Trạng thái <span class="text-red-500">*</span></label>
<select id="input-status" class="w-full px-4 py-2.5 border border-[#f4ede7] rounded-xl focus:ring-2 focus:ring-primary/50 text-sm" required>
<option value="dang_ap_dung">Đang áp dụng</option>
<option value="khong_ap_dung">Tạm dừng</option>
<option value="het_han">Hết hạn</option>
</select>
</div>

<div class="flex items-center gap-3 pt-4">
<button type="submit" class="flex-1 bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primary/90 transition-all">
Lưu khuyến mãi
                    </button>
<button id="btn-cancel-promo" type="button" class="px-6 py-3 bg-[#fcfaf8] text-[#9c7349] rounded-xl font-bold hover:bg-[#f4ede7] transition-all">
Hủy
                    </button>
</div>
</form>
</div>
</div>

<!-- Toast notification -->
<div id="toast-notification" class="fixed top-8 left-1/2 transform -translate-x-1/2 z-50 hidden">
<div class="bg-white border border-[#f4ede7] rounded-2xl shadow-xl px-6 py-4 flex items-center gap-3 min-w-[300px]">
<div class="flex-shrink-0 w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
<span class="material-symbols-outlined text-green-600">check_circle</span>
</div>
<div class="flex-1">
<p id="toast-message" class="text-sm font-bold text-[#1c140d]">Thao tác thành công!</p>
</div>
</div>
</div>

<script>
    // ============================================
    // CẤU HÌNH API
    // ============================================
    const API_BASE_URL = '/qlybandoan/api/khuyenmai/';
    
    const API_ENDPOINTS = {
        getAll: API_BASE_URL + 'get_all.php',
        create: API_BASE_URL + 'create.php',
        update: API_BASE_URL + 'update.php',
        delete: API_BASE_URL + 'delete.php'
    };
    
    console.log('🔧 API Base URL:', API_BASE_URL);
    console.log('📍 Endpoints:', API_ENDPOINTS);

    // ============================================
    // STATE & VARIABLES
    // ============================================
    let promotions = [];
    let filteredPromotions = [];
    let currentFilter = "all";
    let currentSearch = "";
    let currentPage = 1;
    const itemsPerPage = 10;
    let editingIndex = null;

    let discountTypes = ["Phần trăm", "Số tiền cố định", "Miễn phí vận chuyển"];
    const savedTypes = localStorage.getItem('discountTypes');
    if (savedTypes) {
        discountTypes = JSON.parse(savedTypes);
    }

    // ============================================
    // DOM ELEMENTS
    // ============================================
    const promoTableBody = document.getElementById("promo-table-body");
    const promoModal = document.getElementById("promo-modal");
    const btnOpenPromoModal = document.getElementById("btn-open-promo-modal");
    const btnClosePromoModal = document.getElementById("btn-close-promo-modal");
    const btnCancelPromo = document.getElementById("btn-cancel-promo");
    const promoForm = document.getElementById("promo-form");
    const modalTitle = document.getElementById("modal-title");
    const filterTabs = document.querySelectorAll(".filter-tab");
    const promoSearchInput = document.getElementById("promo-search");
    const paginationContainer = document.getElementById("pagination-container");
    const promoCountRange = document.getElementById("promo-count-range");
    const promoCountTotal = document.getElementById("promo-count-total");

    const inputImage = document.getElementById("input-image");
    const inputImageFile = document.getElementById("input-image-file");
    const imagePreview = document.getElementById("image-preview");
    const imagePreviewImg = document.getElementById("image-preview-img");
    const inputCode = document.getElementById("input-code");
    const inputName = document.getElementById("input-name");
    const inputType = document.getElementById("input-type");
    const inputValue = document.getElementById("input-value");
    const inputMinOrder = document.getElementById("input-min-order");
    const inputStartDate = document.getElementById("input-start-date");
    const inputEndDate = document.getElementById("input-end-date");
    const inputStatus = document.getElementById("input-status");

    const toastNotification = document.getElementById("toast-notification");
    const toastMessage = document.getElementById("toast-message");

    // ============================================
    // API FUNCTIONS
    // ============================================
    
        async function loadPromotions() {
        try {
            const response = await fetch(API_ENDPOINTS.getAll);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // 🔥 Kiểm tra data có phải array không
            if (!Array.isArray(data)) {
                console.error('❌ Data không phải array:', data);
                promotions = [];
                renderEmptyState('Dữ liệu không hợp lệ');
                return;
            }
            
            promotions = data.filter(item => item !== null && item !== undefined);
            console.log('✅ Đã load', promotions.length, 'khuyến mãi');
            renderTable();
            
        } catch (error) {
            console.error('❌ Lỗi khi load:', error);
            promotions = [];
            renderEmptyState('Không thể kết nối đến server.');
        }
    }
    async function createPromotion(data) {
    try {
        // ✅ FIX: Validate dữ liệu trước khi gửi
        console.log('📝 Dữ liệu trước khi gửi:');
        console.log('  ma_khuyenmai:', data.ma_khuyenmai);
        console.log('  mo_ta:', data.mo_ta);
        console.log('  loai_giam_gia:', data.loai_giam_gia);
        console.log('  gia_tri_giam:', data.gia_tri_giam);
        console.log('  ngay_bat_dau:', data.ngay_bat_dau);
        console.log('  ngay_ket_thuc:', data.ngay_ket_thuc);

        // ✅ FIX: Kiểm tra các field bắt buộc
        if (!data.ma_khuyenmai || data.ma_khuyenmai.length < 2) {
            showToast("❌ Mã khuyến mãi phải có ít nhất 2 ký tự");
            return false;
        }
        if (!data.mo_ta || data.mo_ta.length < 5) {
            showToast("❌ Tên khuyến mãi phải có ít nhất 5 ký tự");
            return false;
        }
        if (!data.loai_giam_gia) {
            showToast("❌ Loại giảm giá không được để trống");
            return false;
        }
        if (data.gia_tri_giam <= 0) {
            showToast("❌ Giá trị giảm phải lớn hơn 0");
            return false;
        }
        if (!data.ngay_bat_dau || !data.ngay_ket_thuc) {
            showToast("❌ Vui lòng chọn đầy đủ ngày bắt đầu và kết thúc");
            return false;
        }

        console.log('✅ Dữ liệu hợp lệ, bắt đầu gửi...');
        console.log('📤 Gửi CREATE tới:', API_ENDPOINTS.create);
        
        const response = await fetch(API_ENDPOINTS.create, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        console.log('📊 Response status:', response.status);
        console.log('📨 Response headers:', response.headers);

        const responseText = await response.text();
        console.log('📨 Raw response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ JSON parse error:', e);
            console.error('❌ Raw text:', responseText);
            showToast("❌ Server trả về dữ liệu không phải JSON");
            return false;
        }

        console.log('✅ Parsed result:', result);

        if (response.status === 400) {
            showToast("❌ Lỗi 400: " + (result.message || "Bad Request"));
            return false;
        }

        if (!response.ok) {
            showToast("❌ HTTP " + response.status + ": " + (result.message || "Lỗi server"));
            return false;
        }

        if (!result.success) {
            showToast("❌ " + (result.message || "Có lỗi xảy ra"));
            return false;
        }

        console.log('✅ Thêm thành công!');
        await loadPromotions();
        showToast("✅ Thêm khuyến mãi thành công");
        return true;

    } catch (error) {
        console.error('❌ Fetch error:', error);
        showToast("❌ Lỗi: " + error.message);
        return false;
    }
}

    async function updatePromotion(data) {
        try {
            console.log('📤 UPDATE - Data gửi:', data);
            console.log('📤 UPDATE - khuyenmai_id:', data.khuyenmai_id);
            
            const response = await fetch(API_ENDPOINTS.update, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('📥 UPDATE Response:', result);
            
            if (result.success) {
                await loadPromotions();
                showToast("✅ Cập nhật mã khuyến mãi thành công");
                return true;
            } else {
                showToast("❌ " + (result.message || "Có lỗi xảy ra"));
                return false;
            }
        } catch (error) {
            console.error('❌ Lỗi khi cập nhật:', error);
            showToast("❌ " + error.message);
            return false;
        }
    }

    async function deletePromotion(index) {
        try {
            const promotion = promotions[index];
            
            if (!promotion || !promotion.khuyenmai_id) {
                showToast("❌ Lỗi: Không tìm thấy mã khuyến mãi");
                console.error('❌ Promotion không hợp lệ:', promotion);
                return false;
            }

            console.log('📤 DELETE - ID:', promotion.khuyenmai_id);
            
            const response = await fetch(API_ENDPOINTS.delete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    khuyenmai_id: promotion.khuyenmai_id
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('📥 DELETE Response:', result);
            
            if (result.success) {
                await loadPromotions();
                showToast("✅ Xóa mã khuyến mãi thành công");
                return true;
            } else {
                showToast("❌ " + (result.message || "Có lỗi xảy ra khi xóa"));
                return false;
            }
        } catch (error) {
            console.error('❌ Lỗi xóa:', error);
            showToast("❌ " + error.message);
            return false;
        }
    }

    // ============================================
    // UI FUNCTIONS
    // ============================================
    
    function showToast(message) {
        toastMessage.textContent = message;
        toastNotification.classList.remove("hidden");
        setTimeout(() => {
            toastNotification.classList.add("hidden");
        }, 3000);
    }

    function renderDiscountTypes() {
        inputType.innerHTML = discountTypes.map(type => 
            `<option value="${type}">${type}</option>`
        ).join('');
    }

    function getStatusBadge(status) {
        const badges = {
            dang_ap_dung: '<span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">Đang áp dụng</span>',
            khong_ap_dung: '<span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-700">Tạm dừng</span>',
            het_han: '<span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700">Hết hạn</span>'
        };
        return badges[status] || badges.khong_ap_dung;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(value);
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    function renderEmptyState(message = 'Không có dữ liệu') {
        promoTableBody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <span class="material-symbols-outlined text-6xl text-[#f4ede7]">inbox</span>
                        <p class="text-sm text-[#9c7349]">${message}</p>
                    </div>
                </td>
            </tr>
        `;
        promoCountRange.textContent = "0-0";
        promoCountTotal.textContent = "0";
        paginationContainer.innerHTML = "";
    }

    function renderTable() {
        filteredPromotions = promotions.filter(p => {
            const matchesFilter = currentFilter === "all" || p.trang_thai === currentFilter;
            const matchesSearch = currentSearch === "" || 
                p.ma_khuyenmai.toLowerCase().includes(currentSearch.toLowerCase()) ||
                p.mo_ta.toLowerCase().includes(currentSearch.toLowerCase());
            return matchesFilter && matchesSearch;
        });

        if (filteredPromotions.length === 0) {
            renderEmptyState(currentSearch ? 'Không tìm thấy kết quả' : 'Chưa có khuyến mãi nào');
            return;
        }

        const totalPages = Math.ceil(filteredPromotions.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredPromotions.length);
        const currentPromotions = filteredPromotions.slice(startIndex, endIndex);

        promoTableBody.innerHTML = currentPromotions.map((p) => {
            const globalIndex = promotions.findIndex(promo => promo.khuyenmai_id === p.khuyenmai_id);
            // Mới (sửa)
            const imageUrl = p.hinh_anh && p.hinh_anh.trim() ? p.hinh_anh.trim() : '';

            return `
                <tr>
                    <td 
                    ${imageUrl ? `<img src="${imageUrl}" alt="Khuyến mãi" class="w-16 h-16 rounded-lg object-cover border border-[#f4ede7]" onerror="this.style.display='none'" />` : '<div class="w-16 h-16 rounded-lg bg-gray-200 border border-[#f4ede7] flex items-center justify-center"><span class="material-symbols-outlined text-gray-400">image</span></div>'}
                    </td>
                    <td class="px-4 py-4">
                        <span class="font-bold text-primary">${p.ma_khuyenmai}</span>
                    </td>
                    <td class="px-4 py-4">
                        <p class="font-medium text-[#1c140d]">${p.mo_ta}</p>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-sm text-[#9c7349]">${p.loai_giam_gia}</span>
                    </td>
                    <td class="px-4 py-4">
                        <span class="font-bold text-[#1c140d]">${formatCurrency(p.gia_tri_giam)}</span>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-sm text-[#9c7349]">${formatDate(p.ngay_bat_dau)}</span>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-sm text-[#9c7349]">${formatDate(p.ngay_ket_thuc)}</span>
                    </td>
                    <td class="px-4 py-4">
                        ${getStatusBadge(p.trang_thai)}
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center justify-center gap-2">
                            <button data-action="edit" data-index="${globalIndex}" class="p-2 text-[#9c7349] hover:text-primary hover:bg-[#fcfaf8] rounded-lg transition-all" title="Chỉnh sửa">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            <button data-action="delete" data-index="${globalIndex}" class="p-2 text-[#9c7349] hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Xóa">
                                <span class="material-symbols-outlined text-xl">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        promoCountRange.textContent = `${startIndex + 1}-${endIndex}`;
        promoCountTotal.textContent = filteredPromotions.length;

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) {
            paginationContainer.innerHTML = "";
            return;
        }

        let html = "";

        html += `
            <button ${currentPage === 1 ? 'disabled' : ''} 
                    class="px-3 py-1.5 text-sm font-medium rounded-lg ${currentPage === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-[#9c7349] hover:bg-[#fcfaf8]'} transition-all"
                    onclick="changePage(${currentPage - 1})">
                <span class="material-symbols-outlined text-lg">chevron_left</span>
            </button>
        `;

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <button class="px-3 py-1.5 text-sm font-bold rounded-lg transition-all ${i === currentPage ? 'bg-primary text-white' : 'text-[#9c7349] hover:bg-[#fcfaf8]'}"
                        onclick="changePage(${i})">
                    ${i}
                </button>
            `;
        }

        html += `
            <button ${currentPage === totalPages ? 'disabled' : ''} 
                    class="px-3 py-1.5 text-sm font-medium rounded-lg ${currentPage === totalPages ? 'text-gray-400 cursor-not-allowed' : 'text-[#9c7349] hover:bg-[#fcfaf8]'} transition-all"
                    onclick="changePage(${currentPage + 1})">
                <span class="material-symbols-outlined text-lg">chevron_right</span>
            </button>
        `;

        paginationContainer.innerHTML = html;
    }

    function changePage(page) {
        currentPage = page;
        renderTable();
    }

    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    
    function openModal(isEdit, index) {
        if (isEdit && index !== null) {
            // CHỈNH SỬA
            editingIndex = index;
            modalTitle.textContent = "Chỉnh sửa khuyến mãi";
            const p = promotions[index];
            
            inputImage.value = p.hinh_anh || "";
            inputCode.value = p.ma_khuyenmai;
            inputName.value = p.mo_ta;
            inputType.value = p.loai_giam_gia;
            inputValue.value = p.gia_tri_giam;
            inputMinOrder.value = p.don_hang_toi_thieu || 0;
            inputStartDate.value = p.ngay_bat_dau;
            inputEndDate.value = p.ngay_ket_thuc;
            inputStatus.value = p.trang_thai;
            
            if (p.hinh_anh) {
                imagePreviewImg.src = p.hinh_anh;
                imagePreview.classList.remove("hidden");
            } else {
                imagePreview.classList.add("hidden");
            }
        } else {
            // THÊM MỚI - RESET HOÀN TOÀN
            editingIndex = null;
            modalTitle.textContent = "Thêm khuyến mãi mới";
            promoForm.reset();
            
            inputImage.value = "";
            inputCode.value = "";
            inputName.value = "";
            inputType.value = discountTypes[0] || "";
            inputValue.value = "";
            inputMinOrder.value = "0";
            inputStartDate.value = "";
            inputEndDate.value = "";
            inputStatus.value = "dang_ap_dung";
            
            imagePreview.classList.add("hidden");
        }
        
        promoModal.classList.remove("hidden");
        promoModal.classList.add("flex");
    }

    function closeModal() {
        promoModal.classList.add("hidden");
        promoModal.classList.remove("flex");
        
        // RESET HOÀN TOÀN
        editingIndex = null;
        promoForm.reset();
        
        inputImage.value = "";
        inputCode.value = "";
        inputName.value = "";
        inputType.value = discountTypes[0] || "";
        inputValue.value = "";
        inputMinOrder.value = "0";
        inputStartDate.value = "";
        inputEndDate.value = "";
        inputStatus.value = "dang_ap_dung";
        
        imagePreview.classList.add("hidden");
    }

    // ============================================
    // ============================================
    // HÀM CHUYỂN ĐỔI LOẠI GIẢM GIÁ
    // ============================================
    
    function convertLoaiGiamGia(value) {
        // Chuyển đổi từ tiếng Việt có dấu sang không dấu
        const normalized = value.toLowerCase().trim();
        
        if (normalized.includes('phần trăm') || normalized.includes('%') || normalized === 'phan_tram') {
            return 'phan_tram';
        }
        
        if (normalized.includes('cố định') || normalized.includes('co dinh') || normalized === 'co_dinh') {
            return 'co_dinh';
        }
        
        // Mặc định trả về giá trị gốc (nếu đã đúng format)
        return value;
    }

    // ============================================
    // FORM SUBMIT - 🔥 FIX CHÍNH
    // ============================================
    // ============================================
   async function handleSubmit(e) {
    e.preventDefault();

    // ✅ FIX: Trim tất cả dữ liệu
    const ma_khuyenmai = inputCode.value.trim();
    const mo_ta = inputName.value.trim();
    const loai_giam_gia = inputType.value.trim();
    const gia_tri_giam = parseFloat(inputValue.value);
    const don_hang_toi_thieu = parseFloat(inputMinOrder.value);
    const ngay_bat_dau = inputStartDate.value.trim();
    const ngay_ket_thuc = inputEndDate.value.trim();
    const trang_thai = inputStatus.value.trim();

    console.log('🔍 VALIDATE FORM');
    console.log('  ma_khuyenmai:', ma_khuyenmai);
    console.log('  mo_ta:', mo_ta);
    console.log('  loai_giam_gia:', loai_giam_gia);
    console.log('  gia_tri_giam:', gia_tri_giam);
    console.log('  ngay_bat_dau:', ngay_bat_dau);
    console.log('  ngay_ket_thuc:', ngay_ket_thuc);

    // Validate từng field
    if (!ma_khuyenmai) {
        showToast("❌ Mã khuyến mãi không được để trống");
        inputCode.focus();
        return;
    }

    if (!mo_ta) {
        showToast("❌ Tên khuyến mãi không được để trống");
        inputName.focus();
        return;
    }

    if (!loai_giam_gia) {
        showToast("❌ Loại giảm giá không được để trống");
        inputType.focus();
        return;
    }

    if (isNaN(gia_tri_giam) || gia_tri_giam <= 0) {
        showToast("❌ Giá trị giảm phải là số dương");
        inputValue.focus();
        return;
    }

    if (!ngay_bat_dau) {
        showToast("❌ Ngày bắt đầu không được để trống");
        inputStartDate.focus();
        return;
    }

    if (!ngay_ket_thuc) {
        showToast("❌ Ngày kết thúc không được để trống");
        inputEndDate.focus();
        return;
    }

    // Kiểm tra ngày hợp lệ
    const startDate = new Date(ngay_bat_dau);
    const endDate = new Date(ngay_ket_thuc);
    
    if (isNaN(startDate.getTime())) {
        showToast("❌ Ngày bắt đầu không hợp lệ");
        return;
    }

    if (isNaN(endDate.getTime())) {
        showToast("❌ Ngày kết thúc không hợp lệ");
        return;
    }
    
    if (endDate <= startDate) {
        showToast("❌ Ngày kết thúc phải sau ngày bắt đầu");
        return;
    }

    // ✅ Tạo object dữ liệu
    const data = {
        hinh_anh: inputImage.value.trim(),
        ma_khuyenmai: ma_khuyenmai,
        mo_ta: mo_ta,
        loai_giam_gia: loai_giam_gia,
        gia_tri_giam: gia_tri_giam,
        don_hang_toi_thieu: isNaN(don_hang_toi_thieu) ? 0 : don_hang_toi_thieu,
        ngay_bat_dau: ngay_bat_dau,
        ngay_ket_thuc: ngay_ket_thuc,
        trang_thai: trang_thai
    };

    console.log('✅ Form validate thành công');
    console.log('📦 Data sẽ gửi:', JSON.stringify(data, null, 2));

    let success = false;
    
    if (editingIndex === null) {
        console.log('➕ Mode: THÊM MỚI');
        success = await createPromotion(data);
    } else {
        console.log('✏️ Mode: CẬP NHẬT');
        data.khuyenmai_id = promotions[editingIndex].khuyenmai_id;
        success = await updatePromotion(data);
    }
    
    if (success) {
        closeModal();
    }
}
    // ============================================
    // EVENT HANDLERS
    // ============================================
    
    async function handleTableClick(e) {
        const btn = e.target.closest("button[data-action]");
        if (!btn) return;
        
        const index = parseInt(btn.getAttribute("data-index"), 10);
        const action = btn.getAttribute("data-action");
        
        if (Number.isNaN(index)) {
            console.error('❌ Index không hợp lệ:', index);
            return;
        }

        if (action === "edit") {
            openModal(true, index);
        } else if (action === "delete") {
            const p = promotions[index];
            
            if (!p || !p.khuyenmai_id) {
                showToast("❌ Lỗi: Không tìm thấy ID khuyến mãi");
                return;
            }
            
            const confirmed = confirm(`Bạn có chắc chắn muốn xóa khuyến mãi "${p.ma_khuyenmai}" không?`);
            
            if (confirmed) {
                await deletePromotion(index);
            }
        }
    }

    // ============================================
    // EVENT LISTENERS
    // ============================================
    
    btnOpenPromoModal.addEventListener("click", () => {
        editingIndex = null;
        openModal(false, null);
    });

    btnClosePromoModal.addEventListener("click", closeModal);
    btnCancelPromo.addEventListener("click", closeModal);

    promoModal.addEventListener("click", (e) => {
        if (e.target === promoModal) {
            closeModal();
        }
    });

    promoForm.addEventListener("submit", handleSubmit);

    if (promoSearchInput) {
        promoSearchInput.addEventListener("input", (e) => {
            currentSearch = e.target.value;
            currentPage = 1;
            renderTable();
        });
    }

    if (filterTabs && filterTabs.length > 0) {
        filterTabs.forEach(tab => {
            tab.addEventListener("click", () => {
                filterTabs.forEach(t => {
                    t.classList.remove("text-primary", "border-b-2", "border-primary");
                    t.classList.add("text-[#9c7349]");
                });
                tab.classList.add("text-primary", "border-b-2", "border-primary");
                currentFilter = tab.getAttribute("data-filter") || "all";
                currentPage = 1;
                renderTable();
            });
        });
    }

    promoTableBody.addEventListener("click", handleTableClick);

    document.getElementById("add-promo-type").addEventListener("click", () => {
        const newType = prompt("Nhập tên loại giảm giá mới:");
        
        if (!newType || newType.trim() === "") {
            return;
        }
        
        const trimmedType = newType.trim();
        
        if (discountTypes.includes(trimmedType)) {
            alert("❌ Loại giảm giá này đã tồn tại!");
            return;
        }
        
        discountTypes.push(trimmedType);
        localStorage.setItem('discountTypes', JSON.stringify(discountTypes));
        renderDiscountTypes();
        inputType.value = trimmedType;
        alert(`✅ Đã thêm loại "${trimmedType}" vào danh sách!`);
    });

    inputImage.addEventListener("input", (e) => {
        const url = e.target.value.trim();
        if (url) {
            imagePreviewImg.src = url;
            imagePreview.classList.remove("hidden");
        } else {
            imagePreview.classList.add("hidden");
        }
    });

    inputImageFile.addEventListener("change", (e) => {
        const file = e.target.files[0];
        
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            alert("❌ Vui lòng chọn file hình ảnh!");
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert("❌ Kích thước file không được vượt quá 5MB!");
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            const base64Image = event.target.result;
            inputImage.value = base64Image;
            imagePreviewImg.src = base64Image;
            imagePreview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    });

    // ============================================
    // INITIALIZATION
    // ============================================
    
    renderDiscountTypes();
    loadPromotions();

    window.changePage = changePage;
</script>
<script>
    // Block placeholder request
    window.addEventListener('error', (e) => {
        if (e.message.includes('placeholder')) {
            e.preventDefault();
        }
    }, true);
</script>

</body>
</html>
