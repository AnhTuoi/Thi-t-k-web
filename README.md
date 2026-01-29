# Thiết kế web
Thiết kế website bán đồ ăn
# FoodGo - Hệ Thống Quản Lý Bán Đồ Ăn

## Hướng dẫn Cài Đặt và Chạy Dự Án

### 1. Yêu Cầu Hệ Thống
- **XAMPP** (hoặc Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- **Trình duyệt web hiện đại**
- Dùng MySQLi không dùng PDO
### 2. Các Bước Cài Đặt

#### 2.1 Khởi động XAMPP
1. Mở XAMPP Control Panel
2. Nhấp **Start** cho Apache
3. Nhấp **Start** cho MySQL

#### 2.2 Import Database
1. Mở phần mềm **phpMyAdmin** (http://localhost/phpmyadmin)
2. Tạo cơ sở dữ liệu mới tên **qlybandoan** hoặc:
   - Đăng nhập MySQL qua Command Prompt
   - Chạy lệnh:
     ```sql
     mysql -u root -p < Database.sql
     ```
   - Bỏ qua password (chỉ Enter) nếu không có mật khẩu root

#### 2.3 Kiểm Tra Cấu Hình
- File `config/database.php` đã được thiết lập sẵn:
  - Host: `localhost`
  - Username: `root`
  - Password: (trống)
  - Database: `qlybandoan`
  - Nếu cần thay đổi, sửa trong file này

### 3. Chạy Dự Án

#### Cách 1: Sử dụng XAMPP (Khuyến Nghị)
1. Đặt thư mục `FoodGo` trong: `C:\xampp\htdocs\FoodGo`
2. Mở trình duyệt và truy cập: http://localhost/FoodGo

#### Cách 2: Sử dụng PHP Built-in Server
```bash
cd C:\xampp\htdocs\FoodGo
php -S localhost:8000
```
Sau đó truy cập: **http://localhost:8000**

### 6. Tài Khoản Demo (Sau khi import DB)
- Sẽ được cung cấp sau khi chạy script Database.sql
- Hoặc tạo tài khoản mới qua giao diện đăng ký

### 7. Khắc Phục Sự Cố

#### Lỗi: "Kết nối CSDL thất bại"
- Kiểm tra MySQL đang chạy (bật XAMPP MySQL)
- Kiểm tra tên database là `qlybandoan`
- Kiểm tra username/password trong `config/database.php`

#### Lỗi: "404 Not Found"
- Kiểm tra FoodGo ở đúng vị trí `C:\xampp\htdocs\FoodGo`
- Kiểm tra Apache đang chạy
- Xóa file `.htaccess` nếu mod_rewrite không hoạt động

#### Lỗi: "PDO not found"
- Kích hoạt extension PDO trong `php.ini`:
  - Mở `C:\xampp\php\php.ini`
  - Tìm `;extension=pdo_mysql`
  - Bỏ dấu `;` ở đầu
  - Khởi động lại Apache

### 8. Liên Hệ & Hỗ Trợ
- Kiểm tra file Database.sql có chứa tất cả bảng cần thiết
- Xem log lỗi trong `C:\xampp\apache\logs\error.log`
- Kiểm tra console developer (F12) để xem lỗi client-side

---
**Lưu ý**: Dự án đang ở giai đoạn phát triển. Vui lòng kiểm tra các tệp trước khi deploy lên production.
