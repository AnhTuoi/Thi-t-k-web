# Hướng Dẫn Cài Đặt Database FoodGo

## Phương Pháp 1: Sử dụng phpMyAdmin (Dễ nhất)

### Bước 1: Mở phpMyAdmin
1. Khởi động XAMPP
2. Nhấp **Start** trên Apache và MySQL
3. Mở trình duyệt: **http://localhost/phpmyadmin**

### Bước 2: Tạo Database
1. Nhấp **New** ở panel bên trái
2. Nhập tên database: **qlybandoan**
3. Chọn Collation: **utf8mb4_unicode_ci**
4. Nhấp **Create**

### Bước 3: Import SQL
1. Mở tab **Import** (thanh công cụ trên cùng)
2. Nhấp **Choose File** 
3. Chọn file: **Database.sql** từ thư mục FoodGo
4. Nhấp **Import** (có thể mất vài giây)
5. Nếu thành công, bạn sẽ thấy tất cả các bảng được tạo

## Phương Pháp 2: Sử dụng Command Line (Nhanh hơn)

### Windows:
```cmd
cd C:\xampp\mysql\bin
mysql -u root < "C:\xampp\htdocs\FoodGo\Database.sql"
```

### Nếu có password:
```cmd
mysql -u root -p < "C:\xampp\htdocs\FoodGo\Database.sql"
```
Sau đó nhập password khi được hỏi

### Linux/Mac:
```bash
mysql -u root < ~/FoodGo/Database.sql
```

## Kiểm Tra Installation Thành Công

1. Mở phpMyAdmin
2. Nhấp vào database **qlybandoan**
3. Bạn sẽ thấy danh sách bảng:
   - ✓ NGUOIDUNG
   - ✓ TAIKHOAN
   - ✓ MONAN
   - ✓ DANH_MUC_MON_AN (nếu có)
   - ✓ KHUYENMAI
   - ✓ DONHANG
   - ✓ CHITIETDONHANG
   - ✓ DANHGIA
   - và các bảng khác

Nếu tất cả bảng đã xuất hiện → **Cài đặt thành công!** ✓

## Ghi Chú Quan Trọng

- **Không xóa database** khi dự án đang chạy
- **Backup database** trước khi làm việc quan trọng
- **Reset password MySQL**: Nếu quên password, sử dụng XAMPP Control Panel → Admin trên MySQL để reset

## Troubleshooting

### Lỗi: "Access denied for user 'root'@'localhost'"
- Kiểm tra MySQL đang chạy
- Kiểm tra username/password trong config/database.php
- Reset password MySQL

### Lỗi: "Unknown database 'qlybandoan'"
- Kiểm tra database đã được tạo
- Chạy lại script SQL

### Lỗi: "Syntax error near ..."
- Database.sql có thể bị lỗi
- Thử import từng bảng một
- Kiểm tra version MySQL (phải >= 5.7)
