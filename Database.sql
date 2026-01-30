-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS qlybandoan;
USE qlybandoan;

-- 1. Bảng NGUOIDUNG (Thực thể mạnh)
CREATE TABLE NGUOIDUNG (
    nguoidung_id VARCHAR(10) PRIMARY KEY,
    email VARCHAR(50) UNIQUE NOT NULL,
    hoten VARCHAR(150) NOT NULL,
    sodienthoai VARCHAR(15),
    diachi TEXT,
    avatar VARCHAR(255),
    vai_tro ENUM('khach_hang', 'nhan_vien', 'quan_tri') NOT NULL DEFAULT 'khach_hang',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    trang_thai ENUM('hoat_dong', 'vo_hieu_hoa') DEFAULT 'hoat_dong',
    INDEX idx_email (email),
    INDEX idx_vaitro (vai_tro),
    INDEX idx_trangthai (trang_thai)
);

-- 2. Bảng TAIKHOAN (1-1 với NGUOIDUNG)
CREATE TABLE TAIKHOAN (
    taikhoan_id VARCHAR(10) PRIMARY KEY,
    nguoidung_id VARCHAR(10) UNIQUE NOT NULL,
    ten_dang_nhap VARCHAR(50) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    loai_xac_thuc ENUM('email', 'google', 'facebook') DEFAULT 'email',
    trang_thai ENUM('kich_hoat', 'chua_kich_hoat', 'khoa') DEFAULT 'chua_kich_hoat',
    lan_dang_nhap_cuoi DATETIME,
    FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
    INDEX idx_tendangnhap (ten_dang_nhap),
    INDEX idx_trangthai (trang_thai)
);

-- 3. Bảng MONAN (Thực thể mạnh)
CREATE TABLE MONAN (
    monan_id VARCHAR(10) PRIMARY KEY,
    danh_muc ENUM('khai_vi', 'mon_chinh', 'mon_phu', 'trang_mieng', 'do_uong', 'combo') NOT NULL,
    ten_mon VARCHAR(100) NOT NULL,
    mo_ta VARCHAR(255),
    gia DECIMAL(10,2) NOT NULL CHECK (gia >= 0),
    hinh_anh VARCHAR(255),
    danh_gia_tb DECIMAL(3,2) DEFAULT 0.00 CHECK (danh_gia_tb BETWEEN 0 AND 5),
    trang_thai ENUM('dang_ban', 'het_hang', 'ngung_ban') DEFAULT 'dang_ban',
    so_luong_da_ban INT DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_danhmuc (danh_muc),
    INDEX idx_gia (gia),
    INDEX idx_trangthai (trang_thai),
    INDEX idx_danhgia (danh_gia_tb)
);

-- 6. Bảng KHUYENMAI (Thực thể mạnh) - ĐƯỢC CHUYỂN LÊN TRƯỚC
CREATE TABLE KHUYENMAI (
    khuyenmai_id VARCHAR(10) PRIMARY KEY,
    ma_khuyenmai VARCHAR(50) UNIQUE NOT NULL,
    mo_ta TEXT,
    loai_giam_gia ENUM('phan_tram', 'so_tien_co_dinh') NOT NULL,
    gia_tri_giam DECIMAL(10,2) NOT NULL CHECK (gia_tri_giam >= 0),
    don_hang_toi_thieu DECIMAL(10,2) DEFAULT 0 CHECK (don_hang_toi_thieu >= 0),
    giam_toi_da DECIMAL(10,2),
    gioi_han_su_dung INT,
    so_lan_da_su_dung INT DEFAULT 0 CHECK (so_lan_da_su_dung >= 0),
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL,
    trang_thai ENUM('dang_ap_dung', 'khong_ap_dung', 'het_han') DEFAULT 'dang_ap_dung',
    CHECK (ngay_ket_thuc >= ngay_bat_dau),
    CHECK ((loai_giam_gia = 'phan_tram' AND gia_tri_giam <= 100) OR loai_giam_gia = 'so_tien_co_dinh'),
    INDEX idx_makhuyenmai (ma_khuyenmai),
    INDEX idx_trangthai (trang_thai),
    INDEX idx_ngaybatdau (ngay_bat_dau),
    INDEX idx_ngayketthuc (ngay_ket_thuc)
);

-- 4. Bảng DONHANG (Thực thể mạnh) - Quan hệ 1-N với NGUOIDUNG
CREATE TABLE DONHANG (
    donhang_id VARCHAR(10) PRIMARY KEY,
    nguoidung_id VARCHAR(10) NOT NULL,
    khuyenmai_id VARCHAR(10),
    tong_tien DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (tong_tien >= 0),
    diachi_giaohang TEXT NOT NULL,
    phi_vanchuyen DECIMAL(10,2) DEFAULT 0 CHECK (phi_vanchuyen >= 0),
    giam_gia DECIMAL(10,2) DEFAULT 0 CHECK (giam_gia >= 0),
    tong_cuoi_cung DECIMAL(10,2) NOT NULL CHECK (tong_cuoi_cung >= 0),
    phuong_thuc_thanhtoan ENUM('tien_mat', 'the_ngan_hang', 'vi_dien_tu') NOT NULL,
    trang_thai_thanhtoan ENUM('cho_thanh_toan', 'da_thanh_toan', 'that_bai') DEFAULT 'cho_thanh_toan',
    trang_thai_donhang ENUM('cho_xac_nhan', 'da_xac_nhan', 'dang_chuan_bi', 'san_sang', 'dang_giao', 'da_giao', 'da_huy') DEFAULT 'cho_xac_nhan',
    ghi_chu TEXT,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME ON UPDATE CURRENT_TIMESTAMP,
    -- Quan hệ 1-N với NGUOIDUNG
    FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
    FOREIGN KEY (khuyenmai_id) REFERENCES KHUYENMAI(khuyenmai_id) ON DELETE SET NULL,
    INDEX idx_nguoidung (nguoidung_id),
    INDEX idx_ngaytao (ngay_tao),
    INDEX idx_trangthai (trang_thai_donhang),
    INDEX idx_trangthaithanhtoan (trang_thai_thanhtoan),
    INDEX idx_khuyenmai (khuyenmai_id)
);

-- 5. Bảng CHITIETDONHANG (Bảng trung gian cho quan hệ N-N)
CREATE TABLE CHITIETDONHANG (
    donhang_id VARCHAR(10) NOT NULL,
    monan_id VARCHAR(10) NOT NULL,
    so_luong INT NOT NULL CHECK (so_luong > 0),
    don_gia DECIMAL(10,2) NOT NULL CHECK (don_gia >= 0),
    thanh_tien DECIMAL(10,2) NOT NULL CHECK (thanh_tien >= 0),
    PRIMARY KEY (donhang_id, monan_id),
    FOREIGN KEY (donhang_id) REFERENCES DONHANG(donhang_id) ON DELETE CASCADE,
    FOREIGN KEY (monan_id) REFERENCES MONAN(monan_id) ON DELETE CASCADE,
    INDEX idx_donhang (donhang_id),
    INDEX idx_monan (monan_id),
    INDEX idx_soluong (so_luong)
);

-- 7. Bảng DANHGIA (THỰC THỂ YẾU - phụ thuộc vào NGUOIDUNG và DONHANG)
CREATE TABLE DANHGIA (
    -- Khóa chính phức hợp (chứa khóa ngoại từ thực thể mạnh) - ĐẶC ĐIỂM THỰC THỂ YẾU
    donhang_id VARCHAR(10) NOT NULL,
    nguoidung_id VARCHAR(10) NOT NULL,
    monan_id VARCHAR(10) NOT NULL,
    
    -- Thuộc tính của thực thể yếu
    diem_danhgia INT NOT NULL CHECK (diem_danhgia BETWEEN 1 AND 5),
    binh_luan TEXT,
    hinh_anh VARCHAR(255),
    trang_thai ENUM('cho_duyet', 'da_duyet', 'tu_choi') DEFAULT 'cho_duyet',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_duyet DATETIME,
    
    -- Khóa chính phức hợp (IDENTIFYING RELATIONSHIP)
    PRIMARY KEY (donhang_id, nguoidung_id, monan_id),
    
    -- FOREIGN KEY đến các thực thể mạnh (phụ thuộc tồn tại)
    -- Quan hệ identifying với NGUOIDUNG và DONHANG
    FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
    FOREIGN KEY (donhang_id) REFERENCES DONHANG(donhang_id) ON DELETE CASCADE,
    
    -- FOREIGN KEY đến MONAN (không phải identifying nhưng cần thiết)
    FOREIGN KEY (monan_id) REFERENCES MONAN(monan_id) ON DELETE CASCADE,
    
    -- Ràng buộc đặc biệt: Composite FK đến CHITIETDONHANG
    -- Đảm bảo chỉ đánh giá món đã mua trong đơn hàng
    FOREIGN KEY (donhang_id, monan_id) REFERENCES CHITIETDONHANG(donhang_id, monan_id) ON DELETE CASCADE,
    
    -- Index cho hiệu năng
    INDEX idx_monan (monan_id),
    INDEX idx_nguoidung (nguoidung_id),
    INDEX idx_trangthai (trang_thai),
    INDEX idx_diem (diem_danhgia),
    INDEX idx_ngaytao (ngay_tao)
);

-- 8. Bảng HOADON (Thực thể mạnh - Quan hệ 1-1 với DONHANG)
CREATE TABLE HOADON (
    ma_giaodich VARCHAR(100) PRIMARY KEY,
    donhang_id VARCHAR(10) UNIQUE NOT NULL,
    nguoidung_id VARCHAR(10) NOT NULL,
    so_tien DECIMAL(10,2) NOT NULL CHECK (so_tien >= 0),
    phuong_thuc_thanhtoan ENUM('tien_mat', 'the_ngan_hang', 'vi_dien_tu') NOT NULL,
    trang_thai ENUM('cho_thanh_toan', 'hoan_thanh', 'that_bai', 'hoan_tien') DEFAULT 'cho_thanh_toan',
    thongtin_the VARCHAR(255),
    ma_thanhtoan_bank VARCHAR(100),
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donhang_id) REFERENCES DONHANG(donhang_id) ON DELETE CASCADE,
    FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
    INDEX idx_donhang (donhang_id),
    INDEX idx_nguoidung (nguoidung_id),
    INDEX idx_trangthai (trang_thai),
    INDEX idx_ngaytao (ngay_tao)
);

-- 9. Bảng GIOHANG (Để lưu giỏ hàng của người dùng)
CREATE TABLE GIOHANG (
    giohang_id INT AUTO_INCREMENT PRIMARY KEY,
    nguoidung_id VARCHAR(10) NOT NULL,
    monan_id VARCHAR(10) NOT NULL,
    so_luong INT NOT NULL DEFAULT 1 CHECK (so_luong > 0),
    ngay_them DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoidung_id) REFERENCES NGUOIDUNG(nguoidung_id) ON DELETE CASCADE,
    FOREIGN KEY (monan_id) REFERENCES MONAN(monan_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (nguoidung_id, monan_id),
    INDEX idx_nguoidung (nguoidung_id),
    INDEX idx_monan (monan_id)
);
-- ============================================
-- INSERT DỮ LIỆU MẪU
-- ============================================

-- Thêm dữ liệu mẫu cho NGUOIDUNG
INSERT INTO NGUOIDUNG (nguoidung_id, email, hoten, sodienthoai, diachi, vai_tro) VALUES
('KH001', 'nguyenvana@email.com', 'Nguyễn Văn A', '0901234567', '123 Đường ABC, Quận 1, TP.HCM', 'khach_hang'),
('KH002', 'tranthib@email.com', 'Trần Thị B', '0912345678', '456 Đường XYZ, Quận 2, TP.HCM', 'khach_hang'),
('NV001', 'staff@nhahang.com', 'Nhân Viên 1', '0923456789', '789 Đường LMN, Quận 3, TP.HCM', 'nhan_vien'),
('AD001', 'admin@nhahang.com', 'Quản Trị Viên', '0934567890', '321 Đường DEF, Quận 4, TP.HCM', 'quan_tri');

-- Thêm dữ liệu mẫu cho TAIKHOAN
INSERT INTO TAIKHOAN (taikhoan_id, nguoidung_id, ten_dang_nhap, mat_khau, trang_thai) VALUES
('TK001', 'KH001', 'nguyenvana', 'hashed_password_1', 'kich_hoat'),
('TK002', 'KH002', 'tranthib', 'hashed_password_2', 'kich_hoat'),
('TK003', 'NV001', 'nhanvien1', 'hashed_password_3', 'kich_hoat'),
('TK004', 'AD001', 'admin', 'hashed_password_4', 'kich_hoat');

-- Thêm dữ liệu mẫu cho MONAN
INSERT INTO MONAN (monan_id, danh_muc, ten_mon, mo_ta, gia, trang_thai) VALUES
('MA001', 'mon_chinh', 'Phở Bò', 'Phở bò truyền thống', 50000, 'dang_ban'),
('MA002', 'mon_chinh', 'Cơm Gà Xối Mỡ', 'Cơm gà giòn', 45000, 'dang_ban'),
('MA003', 'do_uong', 'Trà Đào', 'Trà đào cam sả', 25000, 'dang_ban'),
('MA004', 'trang_mieng', 'Chè Khúc Bạch', 'Chè khúc bạch truyền thống', 30000, 'dang_ban'),
('MA005', 'khai_vi', 'Gỏi Cuốn', 'Gỏi cuốn tôm thịt', 35000, 'dang_ban');

-- Thêm dữ liệu mẫu cho KHUYENMAI
INSERT INTO KHUYENMAI (khuyenmai_id, ma_khuyenmai, mo_ta, loai_giam_gia, gia_tri_giam, don_hang_toi_thieu, giam_toi_da, gioi_han_su_dung, ngay_bat_dau, ngay_ket_thuc) VALUES
('KM001', 'GIAM10', 'Giảm 10% cho đơn từ 100k', 'phan_tram', 10, 100000, 20000, 100, '2024-01-01', '2024-12-31'),
('KM002', 'GIAM20K', 'Giảm thẳng 20k cho đơn từ 150k', 'so_tien_co_dinh', 20000, 150000, NULL, 50, '2024-01-01', '2024-06-30'),
('KM003', 'FREESHIP', 'Miễn phí vận chuyển', 'so_tien_co_dinh', 15000, 0, NULL, NULL, '2024-01-01', '2024-12-31');

-- Thêm dữ liệu mẫu cho DONHANG
INSERT INTO DONHANG (donhang_id, nguoidung_id, khuyenmai_id, tong_tien, diachi_giaohang, phi_vanchuyen, phuong_thuc_thanhtoan, trang_thai_donhang) VALUES
('DH001', 'KH001', 'KM001', 120000, '123 Đường ABC, Quận 1, TP.HCM', 15000, 'tien_mat', 'da_giao'),
('DH002', 'KH002', 'KM002', 180000, '456 Đường XYZ, Quận 2, TP.HCM', 15000, 'vi_dien_tu', 'da_giao'),
('DH003', 'KH001', NULL, 80000, '123 Đường ABC, Quận 1, TP.HCM', 15000, 'the_ngan_hang', 'dang_giao');

-- Thêm dữ liệu mẫu cho CHITIETDONHANG
INSERT INTO CHITIETDONHANG (donhang_id, monan_id, so_luong, don_gia, thanh_tien) VALUES
('DH001', 'MA001', 2, 50000, 100000),
('DH001', 'MA003', 1, 25000, 25000),
('DH001', 'MA005', 1, 35000, 35000),
('DH002', 'MA002', 2, 45000, 90000),
('DH002', 'MA004', 2, 30000, 60000),
('DH002', 'MA003', 2, 25000, 50000),
('DH003', 'MA001', 1, 50000, 50000),
('DH003', 'MA003', 1, 25000, 25000);

-- Thêm dữ liệu mẫu cho DANHGIA (thực thể yếu)
INSERT INTO DANHGIA (donhang_id, nguoidung_id, monan_id, diem_danhgia, binh_luan, trang_thai) VALUES
('DH001', 'KH001', 'MA001', 5, 'Phở rất ngon, nước dùng đậm đà', 'da_duyet'),
('DH001', 'KH001', 'MA003', 4, 'Trà đào vừa miệng', 'da_duyet'),
('DH001', 'KH001', 'MA005', 3, 'Gỏi cuốn bình thường', 'cho_duyet'),
('DH002', 'KH002', 'MA002', 5, 'Cơm gà giòn rụm, rất thơm', 'da_duyet'),
('DH002', 'KH002', 'MA004', 4, 'Chè ngon nhưng hơi ngọt', 'da_duyet');

-- Thêm dữ liệu mẫu cho HOADON
INSERT INTO HOADON (ma_giaodich, donhang_id, nguoidung_id, so_tien, phuong_thuc_thanhtoan, trang_thai) VALUES
('GD001', 'DH001', 'KH001', 130000, 'tien_mat', 'hoan_thanh'),
('GD002', 'DH002', 'KH002', 195000, 'vi_dien_tu', 'hoan_thanh'),
('GD003', 'DH003', 'KH001', 95000, 'the_ngan_hang', 'cho_thanh_toan');

INSERT INTO GIOHANG (nguoidung_id, monan_id, so_luong) VALUES
('KH001', 'MA001', 2),
('KH001', 'MA003', 1),
('KH002', 'MA002', 1);
