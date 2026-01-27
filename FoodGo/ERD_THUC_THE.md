# HỆ THỐNG FOODGO - BẢNG THỰC THỂ, THUỘC TÍNH VÀ MỐI QUAN HỆ

## 1. DANH SÁCH CÁC THỰC THỂ (ENTITIES)

### 1.1 NGƯỜI DÙNG (User)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | user_id | INT (PK) | Mã người dùng (Khóa chính) |
| 2 | username | VARCHAR(100) | Tên đăng nhập |
| 3 | email | VARCHAR(150) | Email |
| 4 | password_hash | VARCHAR(255) | Mật khẩu được mã hóa |
| 5 | full_name | VARCHAR(150) | Tên đầy đủ |
| 6 | phone | VARCHAR(15) | Số điện thoại |
| 7 | address | TEXT | Địa chỉ |
| 8 | avatar | VARCHAR(255) | URL ảnh đại diện |
| 9 | role | ENUM('customer', 'seller', 'admin') | Vai trò người dùng |
| 10 | status | ENUM('active', 'inactive', 'blocked') | Trạng thái tài khoản |
| 11 | created_at | DATETIME | Ngày tạo |
| 12 | updated_at | DATETIME | Ngày cập nhật |

---

### 1.2 DANH MỤC (Category)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | category_id | INT (PK) | Mã danh mục (Khóa chính) |
| 2 | category_name | VARCHAR(100) | Tên danh mục |
| 3 | description | TEXT | Mô tả danh mục |
| 4 | icon | VARCHAR(255) | URL hình ảnh danh mục |
| 5 | status | ENUM('active', 'inactive') | Trạng thái |
| 6 | created_at | DATETIME | Ngày tạo |

---

### 1.3 MÓN ĂN (Food/Dish)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | food_id | INT (PK) | Mã món ăn (Khóa chính) |
| 2 | category_id | INT (FK) | Mã danh mục (Khóa ngoài) |
| 3 | seller_id | INT (FK) | Mã cửa hàng (Khóa ngoài) |
| 4 | food_name | VARCHAR(150) | Tên món ăn |
| 5 | description | TEXT | Mô tả chi tiết |
| 6 | price | DECIMAL(10, 2) | Giá bán |
| 7 | image | VARCHAR(255) | URL hình ảnh |
| 8 | rating | DECIMAL(3, 2) | Đánh giá trung bình |
| 9 | quantity_available | INT | Số lượng còn sẵn |
| 10 | status | ENUM('available', 'out_of_stock', 'discontinued') | Trạng thái |
| 11 | created_at | DATETIME | Ngày tạo |
| 12 | updated_at | DATETIME | Ngày cập nhật |

---

### 1.4 CỬA HÀNG/NHÀ BÁN (Seller/Store)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | seller_id | INT (PK, FK) | Mã cửa hàng = user_id (Khóa chính & ngoài) |
| 2 | store_name | VARCHAR(150) | Tên cửa hàng |
| 3 | store_address | TEXT | Địa chỉ cửa hàng |
| 4 | store_phone | VARCHAR(15) | Số điện thoại cửa hàng |
| 5 | store_image | VARCHAR(255) | Hình ảnh cửa hàng |
| 6 | opening_time | TIME | Giờ mở cửa |
| 7 | closing_time | TIME | Giờ đóng cửa |
| 8 | rating | DECIMAL(3, 2) | Đánh giá cửa hàng |
| 9 | status | ENUM('active', 'closed', 'suspended') | Trạng thái hoạt động |
| 10 | verified | BOOLEAN | Xác minh cửa hàng |
| 11 | created_at | DATETIME | Ngày tạo |

---

### 1.5 GIỎ HÀNG (Shopping Cart)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | cart_id | INT (PK) | Mã giỏ hàng (Khóa chính) |
| 2 | user_id | INT (FK) | Mã người dùng (Khóa ngoài) |
| 3 | created_at | DATETIME | Ngày tạo |
| 4 | updated_at | DATETIME | Ngày cập nhật |

---

### 1.6 CHI TIẾT GIỎ HÀNG (Cart Item)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | cart_item_id | INT (PK) | Mã mục giỏ (Khóa chính) |
| 2 | cart_id | INT (FK) | Mã giỏ hàng (Khóa ngoài) |
| 3 | food_id | INT (FK) | Mã món ăn (Khóa ngoài) |
| 4 | quantity | INT | Số lượng |
| 5 | unit_price | DECIMAL(10, 2) | Giá đơn vị |
| 6 | subtotal | DECIMAL(10, 2) | Tổng cộng (quantity * unit_price) |
| 7 | added_at | DATETIME | Thời gian thêm vào |

---

### 1.7 ĐƠN HÀNG (Order)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | order_id | INT (PK) | Mã đơn hàng (Khóa chính) |
| 2 | user_id | INT (FK) | Mã khách hàng (Khóa ngoài) |
| 3 | seller_id | INT (FK) | Mã cửa hàng (Khóa ngoài) |
| 4 | order_number | VARCHAR(50) | Số đơn hàng (duy nhất) |
| 5 | total_amount | DECIMAL(10, 2) | Tổng tiền |
| 6 | delivery_address | TEXT | Địa chỉ giao hàng |
| 7 | delivery_fee | DECIMAL(10, 2) | Phí vận chuyển |
| 8 | discount_amount | DECIMAL(10, 2) | Số tiền giảm giá |
| 9 | final_amount | DECIMAL(10, 2) | Tổng tiền cuối cùng |
| 10 | payment_method | ENUM('cash', 'card', 'e-wallet') | Phương thức thanh toán |
| 11 | payment_status | ENUM('pending', 'paid', 'failed') | Trạng thái thanh toán |
| 12 | order_status | ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled') | Trạng thái đơn hàng |
| 13 | notes | TEXT | Ghi chú |
| 14 | created_at | DATETIME | Ngày tạo đơn |
| 15 | updated_at | DATETIME | Ngày cập nhật |

---

### 1.8 CHI TIẾT ĐƠN HÀNG (Order Item)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | order_item_id | INT (PK) | Mã chi tiết đơn (Khóa chính) |
| 2 | order_id | INT (FK) | Mã đơn hàng (Khóa ngoài) |
| 3 | food_id | INT (FK) | Mã món ăn (Khóa ngoài) |
| 4 | quantity | INT | Số lượng |
| 5 | unit_price | DECIMAL(10, 2) | Giá đơn vị |
| 6 | subtotal | DECIMAL(10, 2) | Tổng cộng |

---

### 1.9 KHUYẾN MÃI (Promotion/Coupon)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | promotion_id | INT (PK) | Mã khuyến mãi (Khóa chính) |
| 2 | seller_id | INT (FK) | Mã cửa hàng (Khóa ngoài) |
| 3 | code | VARCHAR(50) | Mã coupon |
| 4 | description | TEXT | Mô tả khuyến mãi |
| 5 | discount_type | ENUM('percentage', 'fixed_amount') | Loại giảm giá |
| 6 | discount_value | DECIMAL(10, 2) | Giá trị giảm giá |
| 7 | min_order_amount | DECIMAL(10, 2) | Số tiền đơn hàng tối thiểu |
| 8 | max_discount | DECIMAL(10, 2) | Giảm giá tối đa |
| 9 | usage_limit | INT | Lượt sử dụng tối đa |
| 10 | used_count | INT | Số lần đã sử dụng |
| 11 | start_date | DATE | Ngày bắt đầu |
| 12 | end_date | DATE | Ngày kết thúc |
| 13 | status | ENUM('active', 'inactive', 'expired') | Trạng thái |

---

### 1.10 ĐƠN HÀNG - KHUYẾN MÃI (Order - Promotion)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | order_promotion_id | INT (PK) | Mã (Khóa chính) |
| 2 | order_id | INT (FK) | Mã đơn hàng (Khóa ngoài) |
| 3 | promotion_id | INT (FK) | Mã khuyến mãi (Khóa ngoài) |
| 4 | discount_applied | DECIMAL(10, 2) | Số tiền giảm áp dụng |

---

### 1.11 ĐÁNH GIÁ/NHẬN XÉT (Review/Rating)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | review_id | INT (PK) | Mã đánh giá (Khóa chính) |
| 2 | order_id | INT (FK) | Mã đơn hàng (Khóa ngoài) |
| 3 | user_id | INT (FK) | Mã người dùng (Khóa ngoài) |
| 4 | food_id | INT (FK) | Mã món ăn (Khóa ngoài) |
| 5 | seller_id | INT (FK) | Mã cửa hàng (Khóa ngoài) |
| 6 | rating_value | INT(1-5) | Điểm đánh giá (1-5 sao) |
| 7 | comment | TEXT | Bình luận |
| 8 | image | VARCHAR(255) | Ảnh đi kèm |
| 9 | status | ENUM('pending', 'approved', 'rejected', 'hidden') | Trạng thái |
| 10 | created_at | DATETIME | Ngày tạo |
| 11 | updated_at | DATETIME | Ngày cập nhật |

---

### 1.12 HÓA ĐƠN/THANH TOÁN (Invoice/Payment)
**Thuộc tính:**
| STT | Thuộc tính | Kiểu dữ liệu | Mô tả |
|-----|-----------|-------------|-------|
| 1 | payment_id | INT (PK) | Mã thanh toán (Khóa chính) |
| 2 | order_id | INT (FK) | Mã đơn hàng (Khóa ngoài) |
| 3 | user_id | INT (FK) | Mã người dùng (Khóa ngoài) |
| 4 | amount | DECIMAL(10, 2) | Số tiền |
| 5 | payment_method | ENUM('cash', 'card', 'e-wallet') | Phương thức thanh toán |
| 6 | transaction_id | VARCHAR(100) | Mã giao dịch |
| 7 | status | ENUM('pending', 'completed', 'failed', 'refunded') | Trạng thái |
| 8 | created_at | DATETIME | Ngày tạo |
| 9 | updated_at | DATETIME | Ngày cập nhật |

---

## 2. MỐI QUAN HỆ GIỮA CÁC THỰC THỂ

### Bảng Quan Hệ:
| Thực thể 1 | Mối quan hệ | Thực thể 2 | Cardinality | Mô tả |
|-----------|-----------|-----------|------------|-------|
| User | tạo ra | Order | 1:N | Một người dùng tạo nhiều đơn hàng |
| Seller (User) | quản lý | Food | 1:N | Một cửa hàng quản lý nhiều món ăn |
| Category | chứa | Food | 1:N | Một danh mục chứa nhiều món ăn |
| User | sở hữu | Cart | 1:1 | Một người dùng có một giỏ hàng |
| Cart | chứa | CartItem | 1:N | Một giỏ hàng chứa nhiều mục |
| Food | có trong | CartItem | 1:N | Một món ăn có thể trong nhiều giỏ hàng |
| Order | bao gồm | OrderItem | 1:N | Một đơn hàng bao gồm nhiều mục |
| Food | có trong | OrderItem | 1:N | Một món ăn có thể trong nhiều đơn hàng |
| User (Customer) | đặt | Order | 1:N | Một khách hàng đặt nhiều đơn hàng |
| Seller | nhận | Order | 1:N | Một cửa hàng nhận nhiều đơn hàng |
| Promotion | áp dụng cho | Order | M:N | Nhiều khuyến mãi cho nhiều đơn hàng |
| User | viết | Review | 1:N | Một người dùng viết nhiều đánh giá |
| Order | có | Review | 1:1 | Một đơn hàng có một đánh giá |
| Food | được đánh giá | Review | 1:N | Một món ăn được đánh giá bởi nhiều người |
| Seller | được đánh giá | Review | 1:N | Một cửa hàng được đánh giá bởi nhiều người |
| Order | có | Payment | 1:1 | Một đơn hàng có một thanh toán |
| User (Seller) | tạo | Promotion | 1:N | Một cửa hàng tạo nhiều khuyến mãi |

---

## 3. SƠ ĐỒ ERD (Entity-Relationship Diagram)

```
┌─────────────────────────────────────────────────────────────────┐
│                      HỆ THỐNG QUẢN LÝ BÁN ĐỒ ĂN                  │
│                          (FoodGo)                               │
└─────────────────────────────────────────────────────────────────┘

                          ┌──────────────┐
                          │     USER     │
                          ├──────────────┤
                          │ user_id (PK) │
                          │ username     │
                          │ email        │
                          │ role         │
                          │ status       │
                          └──────────────┘
                                ▲
                  ┌─────────────┼─────────────┐
                  │             │             │
            (Seller)      (Customer)      (Admin)
                  │             │             │
            ┌─────────┐    ┌────────┐   ┌────────┐
            │ SELLER  │    │ CART   │   │ ADMIN  │
            └─────────┘    └────────┘   └────────┘
                  │             │
                  │        ┌────────────┐
                  │        │ CART_ITEM  │
                  │        └────────────┘
                  │             ▲
                  │             │
            ┌─────────────┐     │
            │   FOOD      │◄────┘
            ├─────────────┤
            │ food_id (PK)│
            │ category_id │
            │ seller_id   │
            │ price       │
            │ rating      │
            └─────────────┘
                  ▲
                  │
            ┌──────────────┐
            │  CATEGORY    │
            ├──────────────┤
            │category_id(PK)
            │category_name │
            └──────────────┘


            ┌────────────┐      ┌─────────────┐      ┌──────────────┐
            │   ORDER    │◄─────┤ ORDER_ITEM  │─────►│    FOOD      │
            ├────────────┤      └─────────────┘      └──────────────┘
            │ order_id   │
            │ user_id    │
            │ seller_id  │
            │ status     │
            │ payment_   │
            │ status     │
            └────────────┘
                  │
          ┌───────┴───────┐
          │               │
      ┌───────────┐  ┌──────────────┐
      │ PROMOTION │  │   PAYMENT    │
      └───────────┘  ├──────────────┤
                     │ payment_id   │
                     │ order_id     │
                     │ amount       │
                     │ status       │
                     └──────────────┘

            ┌──────────────┐
            │    REVIEW    │
            ├──────────────┤
            │ review_id    │
            │ order_id     │
            │ user_id      │
            │ food_id      │
            │ seller_id    │
            │ rating_value │
            │ status       │
            └──────────────┘
```

---

## 4. QUY TẮC ÁP DỤNG

### 4.1 Ràng buộc Toàn vẹn (Integrity Constraints)
- **Khóa chính (PK)**: Không được NULL, duy nhất
- **Khóa ngoài (FK)**: Phải tham chiếu đến khóa chính của bảng khác
- **ENUM**: Chỉ nhận giá trị trong danh sách được xác định
- **UNIQUE**: Các trường như `username`, `email`, `code` (coupon), `order_number` phải duy nhất

### 4.2 Các Bảng Giao Diện (Junction Tables)
- **Order_Promotion**: Quản lý quan hệ M:N giữa Order và Promotion

### 4.3 Trạng Thái & Luồng Xử Lý
- **Đơn hàng**: pending → confirmed → preparing → ready → delivering → delivered/cancelled
- **Thanh toán**: pending → completed/failed/refunded
- **Khuyến mãi**: active → inactive → expired
- **Người dùng**: active → inactive → blocked

---

## 5. CÁC CHỈNH SỬA CÓ THỂ CẦN THIẾT

- Thêm trường `delivery_date` và `estimated_delivery_time` cho Order
- Thêm `rating_count` cho Food và Seller (để tối ưu hiệu suất)
- Thêm bảng `Notification` cho thông báo hệ thống
- Thêm bảng `Transaction_History` để theo dõi chi tiết các giao dịch
- Thêm `discount_reason` hoặc `discount_code` vào Order để theo dõi mã giảm giá
