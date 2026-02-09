// API Configuration
const API_BASE_URL = '../../api';
let cartItems = [];
let suggestions = [];
let appliedOrderCoupon = null;
let appliedShippingCoupon = null;
let paymentTimerInterval = null;

// Helper to get current user ID
// Helper to get current user ID
function getUserId() {
    try {
        const stored = localStorage.getItem('foodgo_current_user');
        if (!stored) return 'KH001';

        const currentUser = JSON.parse(stored);
        if (!currentUser) return 'KH001';

        return currentUser.id || currentUser.nguoidung_id || 'KH001';
    } catch (e) {
        console.warn('Error parsing user ID, defaulting to KH001:', e);
        return 'KH001';
    }
}

// Load demo data if not exists (Keep for coupons)
function initDemoData() {
    if (!localStorage.getItem('foodgo_coupons')) {
        const demoCoupons = [
            { code: 'WELCOME20', discount: 20000, category: 'order', type: 'fixed', min_order: 0, desc: 'Giảm 20k cho đơn hàng' },
            { code: 'FREESHIP', discount: 15000, category: 'shipping', type: 'fixed', min_order: 0, desc: 'Miễn phí vận chuyển' },
            { code: 'SAVE10', discount: 10, category: 'order', type: 'percent', min_order: 100000, desc: 'Giảm 10% đơn từ 100k' }
        ];
        localStorage.setItem('foodgo_coupons', JSON.stringify(demoCoupons));
    }
}

// Update cart count from Database
async function updateCartCount() {
    const userId = getUserId();
    try {
        const response = await fetch(`${API_BASE_URL}/food.php?action=get_cart&user_id=${userId}`);
        const result = await response.json();

        if (result.success) {
            cartItems = result.data;
            const count = cartItems.reduce((total, item) => total + parseInt(item.quantity), 0);
            document.getElementById('cart-count').textContent = count;
            return cartItems;
        }
    } catch (error) {
        console.error('Error fetching cart count:', error);
    }
    return [];
}

// Load cart items from Database
async function loadCartItems() {
    const cart = await updateCartCount();

    if (cart.length === 0) {
        document.getElementById('empty-cart').classList.remove('hidden');
        document.getElementById('cart-with-items').classList.add('hidden');
        return;
    }

    document.getElementById('empty-cart').classList.add('hidden');
    document.getElementById('cart-with-items').classList.remove('hidden');

    // Show loading state
    document.getElementById('cart-items-container').innerHTML = `
    <div class="text-center py-8">
        <div class="loading-spinner mx-auto"></div>
        <p class="mt-4 text-[#9c7349]">Đang tải giỏ hàng...</p>
    </div>
`;

    renderCartItems(cart);
    loadSuggestions();
    calculateTotals();
}

// Render cart items
function renderCartItems(cart) {
    const container = document.getElementById('cart-items-container');

    if (cart.length === 0) {
        container.innerHTML = `
        <div class="text-center py-8">
            <div class="h-16 w-16 bg-gray-100 dark:bg-[#3d2e1f] text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-3xl">shopping_cart</span>
            </div>
            <h4 class="text-lg font-bold mb-2">Giỏ hàng trống</h4>
            <p class="text-[#9c7349]">Hãy thêm món ăn vào giỏ hàng</p>
        </div>
    `;
        return;
    }

    let html = '';

    cart.forEach(item => {
        const total = item.price * item.quantity;

        html += `
        <div class="bg-white dark:bg-[#2a2015] rounded-3xl p-4 md:p-6 border border-[#f4ede7] dark:border-[#3d2e1f] shadow-sm flex items-center gap-6 cart-item" data-cart-id="${item.cart_id}" data-food-id="${item.food_id}">
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-2xl overflow-hidden bg-[#f4ede7] dark:bg-[#3d2e1f] shrink-0">
                <img alt="${item.food_name}" class="w-full h-full object-cover" src="${item.image_url || 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop'}"/>
            </div>
            <div class="flex-1 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-extrabold mb-1">${item.food_name}</h3>
                    <p class="text-sm text-[#9c7349] mb-2">${item.description || 'Món ăn hấp dẫn'}</p>
                    <span class="text-lg font-black text-primary">${new Intl.NumberFormat('vi-VN').format(item.price)}đ</span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center bg-[#f4ede7] dark:bg-[#3d2e1f] rounded-xl h-10 px-1">
                        <button class="decrease-qty w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white dark:hover:bg-primary/20 transition-all" data-cart-id="${item.cart_id}" data-food-id="${item.food_id}">
                            <span class="material-symbols-outlined text-sm">remove</span>
                        </button>
                        <input class="quantity-input w-10 bg-transparent border-none text-center font-bold focus:ring-0 text-sm" 
                               readonly type="text" value="${item.quantity}" data-food-id="${item.food_id}"/>
                        <button class="increase-qty w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white dark:hover:bg-primary/20 transition-all" data-cart-id="${item.cart_id}" data-food-id="${item.food_id}">
                            <span class="material-symbols-outlined text-sm">add</span>
                        </button>
                    </div>
                    <div class="text-right min-w-[100px]">
                        <p class="text-xs text-[#9c7349] font-bold uppercase mb-1">Thành tiền</p>
                        <p class="font-black item-total">${new Intl.NumberFormat('vi-VN').format(total)}đ</p>
                    </div>
                    <button class="remove-item p-2 text-[#9c7349] hover:text-red-500 transition-colors" data-cart-id="${item.cart_id}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    });

    container.innerHTML = html;

    // Add event listeners
    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', function () {
            const cartId = this.dataset.cartId;
            const foodId = this.dataset.foodId;
            updateQuantity(cartId, foodId, -1);
        });
    });

    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', function () {
            const cartId = this.dataset.cartId;
            const foodId = this.dataset.foodId;
            updateQuantity(cartId, foodId, 1);
        });
    });

    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function () {
            const cartId = this.dataset.cartId;
            removeFromCart(cartId);
        });
    });
}

// Load saved addresses
// Load default address into form
async function loadDefaultAddress() {
    const userId = getUserId();
    if (!userId) return;

    try {
        const response = await fetch(`${API_BASE_URL}/food.php?action=get_addresses&user_id=${userId}`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            // Find default or take the first one
            const defaultAddr = result.data.find(a => a.mac_dinh == 1) || result.data[0];

            if (defaultAddr) {
                const nameInput = document.getElementById('delivery-name');
                const phoneInput = document.getElementById('delivery-phone');
                const addressInput = document.getElementById('final-delivery-address');

                // Update values if empty
                if (!nameInput.value) nameInput.value = defaultAddr.ten_nguoi_nhan;
                if (!phoneInput.value) phoneInput.value = defaultAddr.sodienthoai;
                if (!addressInput.value) addressInput.value = defaultAddr.diachi_chitiet;

                // Select radio type
                const radio = document.querySelector(`input[name="addr-type"][value="${defaultAddr.loai_diachi}"]`);
                if (radio) radio.checked = true;
            }
        }
    } catch (e) {
        console.error('Error loading default address:', e);
    }
}

// Update quantity in Database
async function updateQuantity(cartId, foodId, change) {
    const userId = getUserId();
    const itemIndex = cartItems.findIndex(item => item.cart_id == cartId);

    if (itemIndex !== -1) {
        let newQuantity = parseInt(cartItems[itemIndex].quantity) + change;

        if (newQuantity < 1) newQuantity = 1;
        if (newQuantity > 99) newQuantity = 99;

        try {
            const response = await fetch(`${API_BASE_URL}/food.php?action=update_cart`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart_id: cartId,
                    user_id: userId,
                    quantity: newQuantity
                })
            });
            const result = await response.json();

            if (result.success) {
                cartItems[itemIndex].quantity = newQuantity;

                // Update the UI
                const quantityInput = document.querySelector(`.quantity-input[data-food-id="${foodId}"]`);
                const itemTotal = document.querySelector(`.cart-item[data-cart-id="${cartId}"] .item-total`);

                if (quantityInput) quantityInput.value = newQuantity;
                if (itemTotal) {
                    const total = cartItems[itemIndex].price * newQuantity;
                    itemTotal.textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
                }

                updateCartCount();
                calculateTotals();
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    }
}

// Remove from cart in Database
async function removeFromCart(cartId) {
    const userId = getUserId();
    try {
        const response = await fetch(`${API_BASE_URL}/food.php?action=remove_from_cart&cart_id=${cartId}&user_id=${userId}`);
        const result = await response.json();

        if (result.success) {
            // Remove item from UI
            const itemElement = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
            if (itemElement) {
                itemElement.classList.add('opacity-0', 'scale-95', 'transition-all', 'duration-300');
                setTimeout(() => {
                    loadCartItems();
                }, 300);
            }
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
    }
}

// Calculate totals
function calculateTotals() {
    const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    const shippingFee = 15000;
    let orderDiscount = 0;
    let shippingDiscount = 0;

    // Apply order discount
    if (appliedOrderCoupon) {
        if (appliedOrderCoupon.type === 'fixed') {
            orderDiscount = appliedOrderCoupon.discount;
        } else if (appliedOrderCoupon.type === 'percent') {
            orderDiscount = Math.round(subtotal * appliedOrderCoupon.discount / 100);
        }
    }

    // Apply shipping discount
    if (appliedShippingCoupon) {
        if (appliedShippingCoupon.type === 'fixed') {
            shippingDiscount = appliedShippingCoupon.discount;
        } else if (appliedShippingCoupon.type === 'percent') {
            shippingDiscount = Math.round(shippingFee * appliedShippingCoupon.discount / 100);
        }
    }

    // Safety check: Discount cannot exceed fee
    if (shippingDiscount > shippingFee) shippingDiscount = shippingFee;

    const total = subtotal + shippingFee - orderDiscount - shippingDiscount;

    // Update UI
    const subtotalEl = document.getElementById('subtotal');
    if (subtotalEl) subtotalEl.textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + 'đ';

    const shippingEl = document.getElementById('shipping-fee');
    if (shippingEl) shippingEl.textContent = new Intl.NumberFormat('vi-VN').format(shippingFee) + 'đ';

    const discountEl = document.getElementById('discount');
    if (discountEl) discountEl.textContent = '-' + new Intl.NumberFormat('vi-VN').format(orderDiscount) + 'đ';

    const shippingDiscountEl = document.getElementById('shipping-discount');
    if (shippingDiscountEl) shippingDiscountEl.textContent = '-' + new Intl.NumberFormat('vi-VN').format(shippingDiscount) + 'đ';

    const totalElements = document.querySelectorAll('#total, #modal-total, .final-total');
    totalElements.forEach(el => el.textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ');

    // Also update modal summary with safety checks
    const mSubtotalEl = document.getElementById('modal-subtotal');
    if (mSubtotalEl) mSubtotalEl.textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + 'đ';

    const mShippingEl = document.getElementById('modal-shipping');
    if (mShippingEl) mShippingEl.textContent = new Intl.NumberFormat('vi-VN').format(shippingFee) + 'đ';

    const mOrderDiscountEl = document.getElementById('modal-discount');
    if (mOrderDiscountEl) mOrderDiscountEl.textContent = '-' + new Intl.NumberFormat('vi-VN').format(orderDiscount) + 'đ';

    const mShippingDiscountEl = document.getElementById('modal-shipping-discount');
    if (mShippingDiscountEl) mShippingDiscountEl.textContent = '-' + new Intl.NumberFormat('vi-VN').format(shippingDiscount) + 'đ';
}

// Load suggestions (from actual database items)
async function loadSuggestions() {
    try {
        const response = await fetch(`${API_BASE_URL}/food.php?action=search_foods&limit=4`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            suggestions = result.data;
        } else {
            // Fallback to known actual IDs if API search fails
            suggestions = [
                {
                    id: 'MA001',
                    name: 'Phở Bò',
                    price: 50000,
                    image_url: 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=800&auto=format&fit=crop'
                },
                {
                    id: 'MA002',
                    name: 'Cơm Gà Xối Mỡ',
                    price: 45000,
                    image_url: 'https://images.unsplash.com/photo-1562607378-07bb79e6726f?w=800&auto=format&fit=crop'
                },
                {
                    id: 'MA003',
                    name: 'Trà Đào',
                    price: 25000,
                    image_url: 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?w=800&auto=format&fit=crop'
                },
                {
                    id: 'MA005',
                    name: 'Gỏi Cuốn',
                    price: 35000,
                    image_url: 'https://images.unsplash.com/photo-1534422298391-e4f8c170db0f?w=800&auto=format&fit=crop'
                }
            ];
        }
    } catch (error) {
        console.error('Error loading suggestions:', error);
    }
    renderSuggestions();
}

// Render suggestions
function renderSuggestions() {
    const container = document.getElementById('suggestions-grid');
    let html = '';

    suggestions.forEach(item => {
        html += `
        <div class="bg-white dark:bg-[#2a2015] p-3 rounded-2xl border border-[#f4ede7] dark:border-[#3d2e1f] hover:shadow-md transition-all cursor-pointer group">
            <div class="aspect-square rounded-xl overflow-hidden mb-3 bg-[#f4ede7] dark:bg-[#3d2e1f]">
                <img alt="${item.name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="${item.image_url}"/>
            </div>
            <h4 class="text-sm font-bold truncate">${item.name}</h4>
            <p class="text-primary font-black text-sm">${new Intl.NumberFormat('vi-VN').format(item.price)}đ</p>
            <button class="add-suggestion w-full mt-2 py-1.5 text-xs font-bold text-primary border border-primary/30 rounded-lg hover:bg-primary hover:text-white transition-all" 
                data-food-id="${item.id}" data-food-name="${item.name}" data-food-price="${item.price}">
                Thêm
            </button>
        </div>
    `;
    });

    container.innerHTML = html;

    document.querySelectorAll('.add-suggestion').forEach(button => {
        button.addEventListener('click', function (event) {
            const foodId = this.dataset.foodId;
            addToCart(foodId, event);
        });
    });
}

// Add to cart via API
async function addToCart(foodId, event) {
    const userId = getUserId();
    try {
        const response = await fetch(`${API_BASE_URL}/food.php?action=add_to_cart`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                food_id: foodId,
                quantity: 1
            })
        });
        const result = await response.json();

        if (result.success) {
            await loadCartItems();

            // Button feedback
            const button = event?.target;
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = 'Đã thêm';
                button.classList.add('bg-green-500', 'text-white');
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-500', 'text-white');
                }, 1000);
            }
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
    }
}

// --- INLINE COUPON SELECTION (Refactored for 2 separate inputs) ---
const orderCouponInput = document.getElementById('order-coupon-input');
const shippingCouponInput = document.getElementById('shipping-coupon-input');
const orderCouponList = document.getElementById('order-coupon-list');
const shippingCouponList = document.getElementById('shipping-coupon-list');

function getDemoCoupons() {
    return [
        { code: 'WELCOME20', discount: 20000, category: 'order', type: 'fixed', min_order: 0, desc: 'Giảm 20k cho đơn hàng' },
        { code: 'FREESHIP', discount: 15000, category: 'shipping', type: 'fixed', min_order: 0, desc: 'Miễn phí vận chuyển' },
        { code: 'SAVE10', discount: 10, category: 'order', type: 'percent', min_order: 100000, desc: 'Giảm 10% đơn từ 100k' }
    ];
}

function showCouponsForCategory(category, listElement) {
    fetch(`${API_BASE_URL}/food.php?action=get_coupons`)
        .then(response => response.json())
        .then(data => {
            const coupons = data.success ? data.coupons : getDemoCoupons();
            displayCouponsForCategory(coupons, category, listElement);
        })
        .catch(error => {
            console.error('Error fetching coupons:', error);
            displayCouponsForCategory(getDemoCoupons(), category, listElement);
        });
}

function displayCouponsForCategory(coupons, category, listElement) {
    listElement.innerHTML = '';
    const filteredCoupons = coupons.filter(c => c.category === category);

    if (filteredCoupons.length === 0) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'px-3 py-4 text-center text-xs text-[#9c7349]';
        emptyMsg.textContent = 'Không có mã khuyến mãi';
        listElement.appendChild(emptyMsg);
    } else {
        filteredCoupons.forEach(coupon => {
            const item = createCouponItemForInput(coupon, category);
            listElement.appendChild(item);
        });
    }

    listElement.classList.remove('hidden');
}

function createCouponItemForInput(coupon, category) {
    const item = document.createElement('div');
    item.className = 'p-3 rounded-xl border border-transparent hover:border-primary hover:bg-primary/5 transition-all cursor-pointer group flex items-start justify-between bg-white dark:bg-[#3d2e1f]/30 mb-1 last:mb-0';

    item.innerHTML = `
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="font-bold text-primary text-xs">${coupon.code}</span>
            </div>
            <p class="text-[10px] font-medium leading-tight">${coupon.desc}</p>
        </div>
        <span class="material-symbols-outlined text-primary text-sm opacity-0 group-hover:opacity-100 transition-opacity">add_circle</span>
    `;

    item.onclick = (e) => {
        e.stopPropagation();
        if (category === 'order') {
            orderCouponInput.value = coupon.code;
            orderCouponList.classList.add('hidden');
            applyOrderCoupon();
        } else {
            shippingCouponInput.value = coupon.code;
            shippingCouponList.classList.add('hidden');
            applyShippingCoupon();
        }
    };

    return item;
}

// Event listeners for order coupon input
if (orderCouponInput) {
    orderCouponInput.addEventListener('focus', () => showCouponsForCategory('order', orderCouponList));
    orderCouponInput.addEventListener('click', (e) => {
        e.stopPropagation();
        showCouponsForCategory('order', orderCouponList);
    });
}

// Event listeners for shipping coupon input
if (shippingCouponInput) {
    shippingCouponInput.addEventListener('focus', () => showCouponsForCategory('shipping', shippingCouponList));
    shippingCouponInput.addEventListener('click', (e) => {
        e.stopPropagation();
        showCouponsForCategory('shipping', shippingCouponList);
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.group')) {
        if (orderCouponList) orderCouponList.classList.add('hidden');
        if (shippingCouponList) shippingCouponList.classList.add('hidden');
    }
});

// Apply order coupon
function applyOrderCoupon() {
    const couponCode = orderCouponInput.value.trim().toUpperCase();
    const messageElement = document.getElementById('order-coupon-message');

    if (!couponCode) {
        messageElement.textContent = 'Vui lòng nhập mã giảm giá';
        messageElement.className = 'text-xs mt-2 text-red-500';
        messageElement.classList.remove('hidden');
        return;
    }

    fetch(`${API_BASE_URL}/food.php?action=get_coupons`)
        .then(response => response.json())
        .then(data => {
            let coupons = data.success ? data.coupons : getDemoCoupons();
            const coupon = coupons.find(c => c.code === couponCode && c.category === 'order');

            if (!coupon) {
                messageElement.textContent = 'Mã giảm giá không hợp lệ';
                messageElement.className = 'text-xs mt-2 text-red-500';
                messageElement.classList.remove('hidden');
                return;
            }

            const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);

            if (subtotal < coupon.min_order) {
                messageElement.textContent = `Đơn hàng tối thiểu ${new Intl.NumberFormat('vi-VN').format(coupon.min_order)}đ`;
                messageElement.className = 'text-xs mt-2 text-red-500';
                messageElement.classList.remove('hidden');
                return;
            }

            appliedOrderCoupon = coupon;
            messageElement.textContent = `✅ Áp dụng thành công mã ${couponCode}`;
            messageElement.className = 'text-xs mt-2 text-green-500';
            messageElement.classList.remove('hidden');

            calculateTotals();
            if (typeof updatePaymentQR === 'function') updatePaymentQR();
        })
        .catch(error => {
            console.error('Error validating coupon:', error);
            messageElement.textContent = 'Lỗi khi kiểm tra mã giảm giá';
            messageElement.className = 'text-xs mt-2 text-red-500';
            messageElement.classList.remove('hidden');
        });
}

// Apply shipping coupon
function applyShippingCoupon() {
    const couponCode = shippingCouponInput.value.trim().toUpperCase();
    const messageElement = document.getElementById('shipping-coupon-message');

    if (!couponCode) {
        messageElement.textContent = 'Vui lòng nhập mã free ship';
        messageElement.className = 'text-xs mt-2 text-red-500';
        messageElement.classList.remove('hidden');
        return;
    }

    fetch(`${API_BASE_URL}/food.php?action=get_coupons`)
        .then(response => response.json())
        .then(data => {
            let coupons = data.success ? data.coupons : getDemoCoupons();
            const coupon = coupons.find(c => c.code === couponCode && c.category === 'shipping');

            if (!coupon) {
                messageElement.textContent = 'Mã free ship không hợp lệ';
                messageElement.className = 'text-xs mt-2 text-red-500';
                messageElement.classList.remove('hidden');
                return;
            }

            const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);

            if (subtotal < coupon.min_order) {
                messageElement.textContent = `Đơn hàng tối thiểu ${new Intl.NumberFormat('vi-VN').format(coupon.min_order)}đ`;
                messageElement.className = 'text-xs mt-2 text-red-500';
                messageElement.classList.remove('hidden');
                return;
            }

            appliedShippingCoupon = coupon;
            messageElement.textContent = `✅ Áp dụng thành công mã ${couponCode}`;
            messageElement.className = 'text-xs mt-2 text-green-500';
            messageElement.classList.remove('hidden');

            calculateTotals();
            if (typeof updatePaymentQR === 'function') updatePaymentQR();
        })
        .catch(error => {
            console.error('Error validating coupon:', error);
            messageElement.textContent = 'Lỗi khi kiểm tra mã free ship';
            messageElement.className = 'text-xs mt-2 text-red-500';
            messageElement.classList.remove('hidden');
        });
}

// --- ADDRESS MANAGEMENT ---
let savedAddresses = [];

function loadSavedAddresses() {
    const userId = getUserId();
    const listElement = document.getElementById('saved-addresses-list');
    const noAddressMsg = document.getElementById('no-saved-addresses');

    fetch(`${API_BASE_URL}/food.php?action=get_addresses&user_id=${userId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                savedAddresses = result.data;
                if (savedAddresses.length > 0) {
                    noAddressMsg.classList.add('hidden');
                    renderSavedAddresses(savedAddresses);

                    // Auto-select default address if fields are empty
                    const defaultAddr = savedAddresses.find(a => a.mac_dinh == 1);
                    const currentName = document.getElementById('delivery-name').value;
                    if (defaultAddr && !currentName) {
                        selectAddress(defaultAddr);
                    }
                } else {
                    noAddressMsg.classList.remove('hidden');
                    listElement.innerHTML = '';
                    listElement.appendChild(noAddressMsg);
                }
            }
        })
        .catch(error => console.error('Error loading addresses:', error));
}

function renderSavedAddresses(addresses) {
    const listElement = document.getElementById('saved-addresses-list');
    // Clear list but keep the "no address" message element (which is hidden)
    const noAddressMsg = document.getElementById('no-saved-addresses');
    listElement.innerHTML = '';
    listElement.appendChild(noAddressMsg);

    addresses.forEach(addr => {
        const item = document.createElement('label');
        item.className = 'flex items-start gap-3 p-3 rounded-xl border border-[#f4ede7] dark:border-[#3d2e1f] cursor-pointer hover:bg-primary/5 transition-all group relative';

        const typeIcon = {
            'nha': 'home',
            'cong_ty': 'business',
            'khac': 'location_on'
        }[addr.loai_diachi] || 'location_on';

        const typeLabel = {
            'nha': 'Nhà riêng',
            'cong_ty': 'Công ty',
            'khac': 'Khác'
        }[addr.loai_diachi] || 'Địa chỉ';

        item.innerHTML = `
            <input type="radio" name="selected_address" value="${addr.diachi_id}" class="mt-1 peer accent-primary" ${addr.mac_dinh == 1 ? 'checked' : ''}>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-bold text-sm text-[#1c140d] dark:text-white">${addr.ten_nguoi_nhan}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-[#3d2e1f] text-[#9c7349] flex items-center gap-1">
                        <span class="material-symbols-outlined text-[10px]">${typeIcon}</span>
                        ${typeLabel}
                    </span>
                    ${addr.mac_dinh == 1 ? '<span class="text-[10px] text-primary font-bold border border-primary/30 px-1.5 py-0.5 rounded">Mặc định</span>' : ''}
                </div>
                <p class="text-xs text-[#9c7349] mb-0.5">${addr.sodienthoai}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">${addr.diachi_chitiet}</p>
            </div>
            <button class="delete-address absolute top-2 right-2 p-1.5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all" data-id="${addr.diachi_id}" title="Xóa">
                <span class="material-symbols-outlined text-sm">delete</span>
            </button>
        `;

        // Handle selection
        item.querySelector('input').addEventListener('change', () => selectAddress(addr));

        // Handle delete
        item.querySelector('.delete-address').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (confirm('Bạn có chắc muốn xóa địa chỉ này?')) {
                deleteAddress(addr.diachi_id);
            }
        });

        listElement.appendChild(item);
    });
}

function selectAddress(addr) {
    document.getElementById('delivery-name').value = addr.ten_nguoi_nhan;
    document.getElementById('delivery-phone').value = addr.sodienthoai;
    document.getElementById('final-delivery-address').value = addr.diachi_chitiet;

    // Trigger map update if available
    if (typeof flyToLocation === 'function') {
        // Debounce might delay it, so we call it directly or trigger input event
        // But for safe side, just update previewText
        const previewText = document.getElementById('preview-text');
        if (previewText) previewText.textContent = "Đã chọn từ danh sách";

        // Try to update map view
        // flyToLocation(); // This uses getFullAddressText() which parses dropdowns
        // Since we are filling 'final-delivery-address' directly, flyToLocation might prefer 'delivery-address'
        // Let's populate detail-address too just in case
        const detailInput = document.getElementById('delivery-address');
        if (detailInput) detailInput.value = addr.diachi_chitiet;
    }
}

function deleteAddress(id) {
    const userId = getUserId();
    fetch(`${API_BASE_URL}/food.php?action=delete_address&id=${id}&user_id=${userId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                loadSavedAddresses();
            } else {
                alert(res.message);
            }
        })
        .catch(err => console.error(err));
}

// Modal Logic
const addAddressModal = document.getElementById('add-address-modal');
const btnAddAddress = document.getElementById('btn-add-address');
const btnCancelAddAddress = document.getElementById('cancel-add-address');
const addAddressForm = document.getElementById('add-address-form');

if (btnAddAddress) {
    btnAddAddress.addEventListener('click', () => {
        addAddressModal.classList.remove('hidden');
        addAddressModal.classList.add('flex');
    });
}

if (btnCancelAddAddress) {
    btnCancelAddAddress.addEventListener('click', () => {
        addAddressModal.classList.add('hidden');
        addAddressModal.classList.remove('flex');
    });
}

if (addAddressForm) {
    addAddressForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(addAddressForm);
        const data = {
            user_id: getUserId(),
            name: formData.get('name'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            type: formData.get('type'),
            is_default: formData.get('is_default') === 'on'
        };

        fetch(`${API_BASE_URL}/food.php?action=add_address`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    addAddressModal.classList.add('hidden');
                    addAddressModal.classList.remove('flex');
                    addAddressForm.reset();
                    loadSavedAddresses();
                } else {
                    alert(res.message);
                }
            })
            .catch(err => console.error(err));
    });
}

// Initialize addresses and user info
document.addEventListener('DOMContentLoaded', () => {
    loadDefaultAddress();
    loadUserInfo();
});

function loadUserInfo() {
    const userId = getUserId();
    // Only fetch if inputs are empty
    const nameInput = document.getElementById('delivery-name');
    const phoneInput = document.getElementById('delivery-phone');

    if (nameInput.value || phoneInput.value) return;

    fetch(`${API_BASE_URL}/food.php?action=get_user_info&user_id=${userId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                // Only fill if still empty (in case user started typing)
                if (!nameInput.value && res.data.hoten) {
                    nameInput.value = res.data.hoten;
                }
                if (!phoneInput.value && res.data.sodienthoai) {
                    phoneInput.value = res.data.sodienthoai;
                }
            }
        })
        .catch(err => console.error('Error loading user info:', err));
}


// Show payment modal
function showPaymentModal() {
    const modal = document.getElementById('payment-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Create order
async function createOrder() {
    let paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
    let paymentDisplayName = paymentMethod;

    if (paymentMethod === 'wallet') {
        paymentMethod = document.getElementById('wallet-provider').value;
    } else if (paymentMethod === 'banking') {
        paymentMethod = document.getElementById('bank-provider').value;
    }

    const name = document.getElementById('delivery-name').value.trim();
    const phone = document.getElementById('delivery-phone').value.trim();
    const address = document.getElementById('final-delivery-address').value.trim();

    // Validation matching database constraints
    if (!name || !phone || !address) {
        alert('Vui lòng nhập đầy đủ thông tin giao hàng');
        return;
    }

    if (name.length > 150) {
        alert('Họ và tên không được vượt quá 150 ký tự');
        return;
    }

    if (!/^\d{10}$/.test(phone)) {
        alert('Số điện thoại phải bao gồm đúng 10 chữ số');
        return;
    }

    const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    const shippingFee = 15000;

    let orderDiscount = 0;
    let shippingDiscount = 0;
    let couponId = null; // Store primary coupon ID

    // Apply order discount
    if (appliedOrderCoupon) {
        if (appliedOrderCoupon.type === 'fixed') {
            orderDiscount = appliedOrderCoupon.discount;
        } else if (appliedOrderCoupon.type === 'percent') {
            orderDiscount = Math.round(subtotal * appliedOrderCoupon.discount / 100);
        }
        couponId = appliedOrderCoupon.id || null; // Prefer order coupon ID
    }

    // Apply shipping discount
    if (appliedShippingCoupon) {
        if (appliedShippingCoupon.type === 'fixed') {
            shippingDiscount = appliedShippingCoupon.discount;
        } else if (appliedShippingCoupon.type === 'percent') {
            shippingDiscount = Math.round(shippingFee * appliedShippingCoupon.discount / 100);
        }
        if (!couponId) couponId = appliedShippingCoupon.id || null; // Use shipping ID if no order coupon
    }

    // Safety check
    if (shippingDiscount > shippingFee) shippingDiscount = shippingFee;

    const totalDiscount = orderDiscount + shippingDiscount;
    const total = subtotal + shippingFee - totalDiscount;
    const userId = getUserId();

    // Check if save as default is checked
    const saveDefault = document.getElementById('save-address-default').checked;
    if (saveDefault) {
        try {
            const addressType = document.querySelector('input[name="addr-type"]:checked').value;
            const addressData = {
                user_id: userId,
                name: name,
                phone: phone,
                address: address,
                type: addressType,
                is_default: true
            };

            // Save address silently
            fetch(`${API_BASE_URL}/food.php?action=add_address`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(addressData)
            }).catch(e => console.error('Error saving default address:', e));

        } catch (e) {
            console.error('Error preparing address save:', e);
        }
    }

    const order = {
        id: 'FG' + Date.now().toString().slice(-6),
        user_id: userId,
        items: cartItems,
        customer_name: name,
        customer_phone: phone,
        customer_address: address,
        subtotal: subtotal,
        shipping_fee: shippingFee,
        discount: totalDiscount,
        khuyenmai_id: couponId, // Send coupon ID if available
        total: total,
        payment_method: paymentMethod,
        status: 'pending',
        created_at: new Date().toISOString()
    };

    // For demo, save to localStorage as we don't have order API yet
    let orders = JSON.parse(localStorage.getItem('foodgo_orders') || '[]');
    orders.push(order);
    localStorage.setItem('foodgo_orders', JSON.stringify(orders));

    // Clear cart in Database (by removing items)
    for (const item of cartItems) {
        await fetch(`${API_BASE_URL}/food.php?action=remove_from_cart&cart_id=${item.cart_id}&user_id=${userId}`);
    }

    document.getElementById('payment-modal').classList.add('hidden');
    showSuccessModal(order);
}

// Show success modal
function showSuccessModal(order) {
    document.getElementById('order-id').textContent = '#' + order.id;
    document.getElementById('estimated-time').textContent = '45 phút';

    const paymentMethods = {
        'cash': 'Thanh toán khi nhận hàng',
        'wallet': 'Ví điện tử',
        'banking': 'Chuyển khoản ngân hàng',
        'momo': 'Ví MoMo',
        'zalopay': 'ZaloPay',
        'shopeepay': 'ShopeePay',
        'vcb': 'Vietcombank',
        'tcb': 'Techcombank',
        'icb': 'VietinBank',
        'bidv': 'BIDV',
        'vba': 'Agribank',
        'mbb': 'MB Bank',
        'acb': 'ACB',
        'vpb': 'VPBank',
        'tpb': 'TPBank',
        'hdb': 'HDBank',
        'ocb': 'OCB',
        'shb': 'SHB'
    };
    document.getElementById('payment-method-used').textContent = paymentMethods[order.payment_method] || order.payment_method;

    const modal = document.getElementById('success-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    initDemoData();
    loadCartItems();

    // Apply coupon button event listeners
    const applyOrderBtn = document.getElementById('apply-order-coupon');
    const applyShippingBtn = document.getElementById('apply-shipping-coupon');

    if (applyOrderBtn) applyOrderBtn.addEventListener('click', applyOrderCoupon);
    if (applyShippingBtn) applyShippingBtn.addEventListener('click', applyShippingCoupon);
    document.getElementById('checkout-button').addEventListener('click', function () {
        if (cartItems.length === 0) {
            alert('Giỏ hàng của bạn đang trống');
            return;
        }
        showPaymentModal();
    });

    document.getElementById('close-payment-modal').addEventListener('click', () => {
        document.getElementById('payment-modal').classList.add('hidden');
    });

    document.getElementById('confirm-payment').addEventListener('click', createOrder);

    // Toggle selections
    const paymentRadios = document.querySelectorAll('input[name="payment-method"]');
    const walletSelection = document.getElementById('wallet-selection');
    const bankSelection = document.getElementById('bank-selection');
    const bankProvider = document.getElementById('bank-provider');
    const qrContainer = document.getElementById('qr-container');
    const timerDisplay = document.getElementById('payment-timer');

    function startPaymentTimer(durationInSeconds) {
        stopPaymentTimer();
        let timer = durationInSeconds;

        function updateTimer() {
            const minutes = Math.floor(timer / 60);
            const seconds = timer % 60;
            timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (--timer < 0) {
                stopPaymentTimer();
                alert('Thời gian thanh toán đã hết. Vui lòng thử lại.');
                document.getElementById('payment-modal').classList.add('hidden');
            }
        }

        updateTimer();
        paymentTimerInterval = setInterval(updateTimer, 1000);
    }

    function stopPaymentTimer() {
        if (paymentTimerInterval) {
            clearInterval(paymentTimerInterval);
            paymentTimerInterval = null;
        }
    }

    function updateQRDisplay() {
        // Determine type: banking or wallet
        const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
        let providerCode = '';
        let providerName = '';
        let type = ''; // 'bank' or 'wallet'

        if (paymentMethod === 'banking') {
            providerCode = bankProvider.value;
            type = 'bank';
            // Move container to bank section
            const bankSelection = document.getElementById('bank-selection');
            bankSelection.parentNode.insertBefore(qrContainer, bankSelection.nextSibling);
        } else if (paymentMethod === 'wallet') {
            providerCode = document.getElementById('wallet-provider').value;
            type = 'wallet';
            // Move container to wallet section
            const walletSelection = document.getElementById('wallet-selection');
            walletSelection.parentNode.insertBefore(qrContainer, walletSelection.nextSibling);
        }

        if (!providerCode) {
            qrContainer.classList.add('hidden');
            stopPaymentTimer();
            return;
        }

        const subtotal = cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
        const shippingFee = 15000;
        let orderDiscount = 0;
        let shippingDiscount = 0;

        // Apply order discount
        if (appliedOrderCoupon) {
            if (appliedOrderCoupon.type === 'fixed') {
                orderDiscount = appliedOrderCoupon.discount;
            } else if (appliedOrderCoupon.type === 'percent') {
                orderDiscount = Math.round(subtotal * appliedOrderCoupon.discount / 100);
            }
        }

        // Apply shipping discount
        if (appliedShippingCoupon) {
            if (appliedShippingCoupon.type === 'fixed') {
                shippingDiscount = appliedShippingCoupon.discount;
            } else if (appliedShippingCoupon.type === 'percent') {
                shippingDiscount = Math.round(shippingFee * appliedShippingCoupon.discount / 100);
            }
        }

        // Safety check
        if (shippingDiscount > shippingFee) shippingDiscount = shippingFee;

        const total = subtotal + shippingFee - orderDiscount - shippingDiscount;

        const orderId = 'FG' + Date.now().toString().slice(-6);
        const accountNo = '0987654321'; // Default for wallets
        const accountName = 'FOODGO TEAM';

        let qrUrl = '';

        // Update Labels based on type
        const bankParams = document.querySelectorAll('#qr-bank-name, #qr-account-no');
        const bankLabels = document.querySelectorAll('#qr-container p.text-xs');

        // Reset labels logic
        // This relies on fixed indices which is brittle, but for now we adapt content
        const row1 = bankLabels[0]; // Ngan hang
        const row2 = bankLabels[1]; // So tai khoan

        if (type === 'bank') {
            const bankNames = {
                'vcb': 'Vietcombank',
                'tcb': 'Techcombank',
                'icb': 'VietinBank',
                'bidv': 'BIDV',
                'vba': 'Agribank',
                'mbb': 'MB Bank',
                'acb': 'ACB',
                'vpb': 'VPBank',
                'tpb': 'TPBank',
                'hdb': 'HDBank',
                'ocb': 'OCB',
                'shb': 'SHB'
            };
            providerName = bankNames[providerCode] || providerCode.toUpperCase();

            // Restore labels
            row1.innerHTML = `Ngân hàng: <span id="qr-bank-name" class="font-medium text-sm text-[#1c140d] dark:text-white text-right">${providerName}</span>`;
            row2.innerHTML = `Số tài khoản: <span id="qr-account-no" class="font-medium text-sm text-[#1c140d] dark:text-white text-right">123456789</span>`;

            // VietQR
            qrUrl = `https://img.vietqr.io/image/${providerCode}-123456789-compact.png?amount=${total}&addInfo=${orderId}&accountName=${encodeURIComponent(accountName)}`;

        } else {
            const walletNames = {
                'momo': 'Ví MoMo',
                'zalopay': 'ZaloPay',
                'shopeepay': 'ShopeePay'
            };
            providerName = walletNames[providerCode] || providerCode;

            // Change labels
            row1.innerHTML = `Ví điện tử: <span id="qr-bank-name" class="font-medium text-sm text-[#1c140d] dark:text-white text-right">${providerName}</span>`;
            row2.innerHTML = `Số điện thoại: <span id="qr-account-no" class="font-medium text-sm text-[#1c140d] dark:text-white text-right">${accountNo}</span>`;

            // Generic QR for wallets (using qrserver for demo)
            // Content: Payment for order [ID] amount [Total] via [Wallet]
            const qrContent = `Thanh toan don hang ${orderId} so tien ${total} qua ${providerName}`;
            qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrContent)}`;
        }

        document.getElementById('qr-image').src = qrUrl;
        document.getElementById('qr-amount').textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
        document.getElementById('qr-content').textContent = orderId;
        qrContainer.classList.remove('hidden');

        // Start 5-minute countdown
        startPaymentTimer(300);
    }

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            walletSelection.classList.add('hidden');
            bankSelection.classList.add('hidden');
            qrContainer.classList.add('hidden');
            stopPaymentTimer();

            if (this.value === 'wallet') {
                walletSelection.classList.remove('hidden');
                const wallet = document.getElementById('wallet-provider').value;
                if (wallet) updateQRDisplay();
            } else if (this.value === 'banking') {
                bankSelection.classList.remove('hidden');
                if (bankProvider.value) updateQRDisplay();
            }
        });
    });

    bankProvider.addEventListener('change', updateQRDisplay);
    document.getElementById('wallet-provider').addEventListener('change', updateQRDisplay);

    document.getElementById('cancel-payment').addEventListener('click', () => {
        if (confirm('Bạn có chắc chắn muốn hủy thanh toán này?')) {
            qrContainer.classList.add('hidden');
            stopPaymentTimer();
            bankProvider.value = "";
        }
    });

    document.getElementById('confirm-payment').addEventListener('click', () => {
        stopPaymentTimer();
        createOrder();
    });

    // --- ADD ADDRESS MODAL ---
    const addAddressModal = document.getElementById('add-address-modal');
    const btnAddAddress = document.getElementById('btn-add-address');
    const btnCancelAddress = document.getElementById('btn-cancel-address');
    const formAddAddress = document.getElementById('form-add-address');

    if (btnAddAddress && addAddressModal) {
        btnAddAddress.addEventListener('click', () => {
            addAddressModal.classList.remove('hidden');
            addAddressModal.classList.add('flex');
        });

        btnCancelAddress.addEventListener('click', () => {
            addAddressModal.classList.add('hidden');
            addAddressModal.classList.remove('flex');
        });

        formAddAddress.addEventListener('submit', async (e) => {
            e.preventDefault();
            const userId = getUserId();
            const name = document.getElementById('new-addr-name').value;
            const phone = document.getElementById('new-addr-phone').value;
            const address = document.getElementById('new-addr-detail').value;
            const type = document.querySelector('input[name="addr-type"]:checked').value;
            const isDefault = document.getElementById('new-addr-default').checked;

            try {
                const response = await fetch(`${API_BASE_URL}/food.php?action=add_address`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        name, phone, address, type, is_default: isDefault
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert('Thêm địa chỉ thành công!');
                    addAddressModal.classList.add('hidden');
                    addAddressModal.classList.remove('flex');
                    formAddAddress.reset();
                    loadSavedAddresses();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error adding address:', error);
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            }
        });
    }

    // --- CASCADING ADDRESS SELECTION (In Modal) ---
    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('district');
    const wardSelect = document.getElementById('ward');
    const detailAddressInput = document.getElementById('delivery-address');
    const finalAddressTextarea = document.getElementById('final-delivery-address');

    async function fetchProvinces() {
        try {
            const response = await fetch('https://provinces.open-api.vn/api/p/');
            const provinces = await response.json();
            provinces.forEach(p => {
                const opt = new Option(p.name, p.code);
                provinceSelect.add(opt);
            });
        } catch (error) {
            console.error('Error fetching provinces:', error);
        }
    }

    async function fetchDistricts(provinceCode) {
        districtSelect.innerHTML = '<option value="">Quận/Huyện</option>';
        districtSelect.disabled = true;
        wardSelect.innerHTML = '<option value="">Phường/Xã</option>';
        wardSelect.disabled = true;

        if (!provinceCode) return;

        try {
            const response = await fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`);
            const data = await response.json();
            data.districts.forEach(d => {
                const opt = new Option(d.name, d.code);
                districtSelect.add(opt);
            });
            districtSelect.disabled = false;
        } catch (error) {
            console.error('Error fetching districts:', error);
        }
    }

    async function fetchWards(districtCode) {
        wardSelect.innerHTML = '<option value="">Phường/Xã</option>';
        wardSelect.disabled = true;

        if (!districtCode) return;

        try {
            const response = await fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`);
            const data = await response.json();
            data.wards.forEach(w => {
                const opt = new Option(w.name, w.code);
                wardSelect.add(opt);
            });
            wardSelect.disabled = false;
        } catch (error) {
            console.error('Error fetching wards:', error);
        }
    }

    function getFullAddressText() {
        const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
        const district = districtSelect.options[districtSelect.selectedIndex]?.text || '';
        const ward = wardSelect.options[wardSelect.selectedIndex]?.text || '';
        const detail = detailAddressInput.value.trim();

        let addressParts = [];
        if (detail) addressParts.push(detail);
        if (ward && ward !== 'Phường/Xã') addressParts.push(ward);
        if (district && district !== 'Quận/Huyện') addressParts.push(district);
        if (province && province !== 'Tỉnh/Thành phố') addressParts.push(province);

        return addressParts.join(', ');
    }

    async function flyToLocation() {
        const query = getFullAddressText();
        if (!query || query.length < 5) return;

        previewText.textContent = "Đang tìm kiếm vị trí...";

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=vn`);
            const results = await response.json();
            if (results.length > 0) {
                const lat = parseFloat(results[0].lat);
                const lon = parseFloat(results[0].lon);

                // Initialize map if not already done
                if (!map) initMap();

                map.setView([lat, lon], 16);
                marker.setLatLng([lat, lon]);
                previewText.textContent = results[0].display_name;
            } else {
                previewText.textContent = "Không tìm thấy vị trí tự động. Vui lòng ghim thủ công.";
            }
        } catch (error) {
            console.error('Error flying to location:', error);
            previewText.textContent = "Lỗi kết nối bản đồ.";
        }
    }

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    const debouncedFlyToLocation = debounce(flyToLocation, 1500);

    provinceSelect.addEventListener('change', (e) => {
        fetchDistricts(e.target.value);
        debouncedFlyToLocation();
    });

    districtSelect.addEventListener('change', (e) => {
        fetchWards(e.target.value);
        debouncedFlyToLocation();
    });

    wardSelect.addEventListener('change', debouncedFlyToLocation);

    // Add input event for real-time (debounced) updates
    detailAddressInput.addEventListener('input', debouncedFlyToLocation);
    detailAddressInput.addEventListener('blur', flyToLocation);
    detailAddressInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') flyToLocation();
    });

    fetchProvinces();

    // --- LEAFLET MAP PICKER ---
    let map = null;
    let marker = null;
    let searchTimeout = null;
    const mapModal = document.getElementById('map-modal');
    const previewText = document.getElementById('selected-address-preview');

    function initMap() {
        if (map) return;

        // Default: Ho Chi Minh City
        const defaultLat = 10.762622;
        const defaultLng = 106.660172;

        map = L.map('map').setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map);

        marker.on('dragend', function (event) {
            const position = marker.getLatLng();
            reverseGeocode(position.lat, position.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
    }

    async function reverseGeocode(lat, lng) {
        previewText.textContent = "Đang xác định vị trí...";
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                headers: { 'Accept-Language': 'vi' }
            });
            const data = await response.json();
            if (data && data.display_name) {
                previewText.textContent = data.display_name;
            } else {
                previewText.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            previewText.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    async function searchAddress(query) {
        if (!query || query.length < 3) return;

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=vn`, {
                headers: { 'Accept-Language': 'vi' }
            });
            const results = await response.json();
            displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    function displaySearchResults(results) {
        const resultsContainer = document.getElementById('search-results');
        resultsContainer.innerHTML = '';

        if (results.length === 0) {
            resultsContainer.classList.add('hidden');
            return;
        }

        results.forEach(result => {
            const div = document.createElement('div');
            div.className = 'px-4 py-3 hover:bg-gray-50 dark:hover:bg-[#3d2e1f] cursor-pointer text-sm border-b border-gray-50 dark:border-[#3d2e1f] last:border-0';
            div.textContent = result.display_name;
            div.addEventListener('click', () => {
                const lat = parseFloat(result.lat);
                const lon = parseFloat(result.lon);
                map.setView([lat, lon], 16);
                marker.setLatLng([lat, lon]);
                previewText.textContent = result.display_name;
                resultsContainer.classList.add('hidden');
                document.getElementById('map-search-input').value = '';
            });
            resultsContainer.appendChild(div);
        });

        resultsContainer.classList.remove('hidden');
    }

    document.getElementById('btn-open-map').addEventListener('click', () => {
        mapModal.classList.remove('hidden');
        mapModal.classList.add('flex');

        // Timeout to ensure modal is visible before initializing map
        setTimeout(() => {
            initMap();
            map.invalidateSize(); // Fix gray tiles issue in modals

            // If address already exists, try to find it or just center
            const currentAddr = getFullAddressText();
            if (currentAddr.length > 5) {
                previewText.textContent = currentAddr;
                flyToLocation();
            }
        }, 100);
    });

    document.getElementById('close-map-modal').addEventListener('click', () => {
        mapModal.classList.add('hidden');
        mapModal.classList.remove('flex');
    });

    document.getElementById('map-search-input').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value;
        searchTimeout = setTimeout(() => searchAddress(query), 500);
    });

    document.getElementById('confirm-map-location').addEventListener('click', () => {
        // Preference for constructed address from selects if available, 
        // fallback to reverse geocoding result if it's more descriptive
        const constructed = getFullAddressText();
        const detected = previewText.textContent;

        // Use the most complete looking address
        finalAddressTextarea.value = (detected.length > constructed.length) ? detected : constructed;

        mapModal.classList.add('hidden');
        mapModal.classList.remove('flex');
    });

    // Close results when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.group')) {
            const results = document.getElementById('search-results');
            if (results) results.classList.add('hidden');
        }
    });

    loadSavedAddresses();
});
