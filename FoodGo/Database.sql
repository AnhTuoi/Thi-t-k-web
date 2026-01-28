-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS quanlynhahang;
USE quanlynhahang;

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

-- 6. Bảng KHUYENMAI (Thực thể mạnh)
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

-- ============================================
-- TRIGGERS và STORED PROCEDURES
-- ============================================

DELIMITER //

-- Trigger 1: Cập nhật thời gian sửa đổi
CREATE TRIGGER before_donhang_update
BEFORE UPDATE ON DONHANG
FOR EACH ROW
BEGIN
    SET NEW.ngay_cap_nhat = CURRENT_TIMESTAMP;
END//

-- Trigger 2: Cập nhật đánh giá trung bình cho món ăn
CREATE TRIGGER after_danhgia_update
AFTER UPDATE ON DANHGIA
FOR EACH ROW
BEGIN
    -- Chỉ cập nhật khi trạng thái thay đổi thành 'da_duyet'
    IF NEW.trang_thai = 'da_duyet' AND OLD.trang_thai != 'da_duyet' THEN
        UPDATE MONAN m
        SET m.danh_gia_tb = (
            SELECT COALESCE(AVG(d.diem_danhgia), 0)
            FROM DANHGIA d
            WHERE d.monan_id = NEW.monan_id 
            AND d.trang_thai = 'da_duyet'
            AND d.diem_danhgia IS NOT NULL
        )
        WHERE m.monan_id = NEW.monan_id;
    END IF;
END//

-- Trigger 3: Cập nhật số lượng đã bán
CREATE TRIGGER after_chitietdonhang_insert
AFTER INSERT ON CHITIETDONHANG
FOR EACH ROW
BEGIN
    UPDATE MONAN m
    SET m.so_luong_da_ban = m.so_luong_da_ban + NEW.so_luong
    WHERE m.monan_id = NEW.monan_id;
END//

-- Trigger 4: Giảm số lượng đã bán khi xóa chi tiết đơn hàng
CREATE TRIGGER after_chitietdonhang_delete
AFTER DELETE ON CHITIETDONHANG
FOR EACH ROW
BEGIN
    UPDATE MONAN m
    SET m.so_luong_da_ban = GREATEST(m.so_luong_da_ban - OLD.so_luong, 0)
    WHERE m.monan_id = OLD.monan_id;
END//

-- Trigger 5: Cập nhật số lần sử dụng khuyến mãi
CREATE TRIGGER after_donhang_insert_khuyenmai
AFTER INSERT ON DONHANG
FOR EACH ROW
BEGIN
    IF NEW.khuyenmai_id IS NOT NULL THEN
        UPDATE KHUYENMAI k
        SET k.so_lan_da_su_dung = k.so_lan_da_su_dung + 1
        WHERE k.khuyenmai_id = NEW.khuyenmai_id;
    END IF;
END//

-- Trigger 6: Tính toán tự động giảm giá và tổng cuối cùng
CREATE TRIGGER before_donhang_insert_calculate
BEFORE INSERT ON DONHANG
FOR EACH ROW
BEGIN
    DECLARE discount_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE max_discount DECIMAL(10,2);
    DECLARE discount_value DECIMAL(10,2);
    DECLARE v_discount_type ENUM('phan_tram', 'so_tien_co_dinh');
    DECLARE v_valid_promo BOOLEAN DEFAULT FALSE;
    
    -- Kiểm tra và tính toán khuyến mãi nếu có
    IF NEW.khuyenmai_id IS NOT NULL THEN
        -- Kiểm tra khuyến mãi có hợp lệ không
        SELECT 
            k.gia_tri_giam,
            k.giam_toi_da,
            k.loai_giam_gia,
            CASE 
                WHEN k.trang_thai = 'dang_ap_dung' 
                AND CURDATE() BETWEEN k.ngay_bat_dau AND k.ngay_ket_thuc
                AND (k.gioi_han_su_dung IS NULL OR k.so_lan_da_su_dung < k.gioi_han_su_dung)
                AND NEW.tong_tien >= k.don_hang_toi_thieu
                THEN TRUE
                ELSE FALSE
            END
        INTO 
            discount_value,
            max_discount,
            v_discount_type,
            v_valid_promo
        FROM KHUYENMAI k
        WHERE k.khuyenmai_id = NEW.khuyenmai_id;
        
        -- Nếu khuyến mãi hợp lệ, tính toán giảm giá
        IF v_valid_promo THEN
            IF v_discount_type = 'phan_tram' THEN
                -- Giảm giá theo phần trăm
                SET discount_amount = NEW.tong_tien * (discount_value / 100);
                IF max_discount IS NOT NULL AND discount_amount > max_discount THEN
                    SET discount_amount = max_discount;
                END IF;
            ELSE
                -- Giảm giá theo số tiền cố định
                SET discount_amount = discount_value;
            END IF;
        END IF;
    END IF;
    
    -- Đảm bảo số tiền giảm không vượt quá tổng tiền
    IF discount_amount > NEW.tong_tien THEN
        SET discount_amount = NEW.tong_tien;
    END IF;
    
    -- Cập nhật giá trị
    SET NEW.giam_gia = discount_amount;
    SET NEW.tong_cuoi_cung = NEW.tong_tien + NEW.phi_vanchuyen - discount_amount;
END//

-- Trigger 7: Kiểm tra khi thêm đánh giá
CREATE TRIGGER before_danhgia_insert_check
BEFORE INSERT ON DANHGIA
FOR EACH ROW
BEGIN
    DECLARE order_exists INT;
    DECLARE item_exists INT;
    
    -- Kiểm tra đơn hàng thuộc về người dùng
    SELECT COUNT(*) INTO order_exists
    FROM DONHANG d
    WHERE d.donhang_id = NEW.donhang_id
    AND d.nguoidung_id = NEW.nguoidung_id;
    
    IF order_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Đơn hàng không thuộc về người dùng này';
    END IF;
    
    -- Kiểm tra món ăn có trong đơn hàng (ràng buộc FK sẽ tự động kiểm tra)
    -- Nhưng có thể thêm kiểm tra bổ sung
    SELECT COUNT(*) INTO item_exists
    FROM CHITIETDONHANG cd
    WHERE cd.donhang_id = NEW.donhang_id
    AND cd.monan_id = NEW.monan_id;
    
    IF item_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Món ăn không có trong đơn hàng';
    END IF;
    
    -- Đảm bảo chỉ đánh giá đơn hàng đã giao
    IF NOT EXISTS (
        SELECT 1 FROM DONHANG d 
        WHERE d.donhang_id = NEW.donhang_id 
        AND d.trang_thai_donhang = 'da_giao'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Chỉ có thể đánh giá đơn hàng đã giao';
    END IF;
END//

DELIMITER ;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Procedure 1: Tính toán giảm giá cho đơn hàng
CREATE PROCEDURE CalculateOrderDiscount(
    IN p_donhang_id VARCHAR(10),
    OUT p_giam_gia DECIMAL(10,2),
    OUT p_tong_cuoi_cung DECIMAL(10,2)
)
BEGIN
    DECLARE v_tong_tien DECIMAL(10,2);
    DECLARE v_phi_vanchuyen DECIMAL(10,2);
    DECLARE v_khuyenmai_id VARCHAR(10);
    DECLARE discount_amount DECIMAL(10,2) DEFAULT 0;
    
    -- Lấy thông tin đơn hàng
    SELECT tong_tien, phi_vanchuyen, khuyenmai_id
    INTO v_tong_tien, v_phi_vanchuyen, v_khuyenmai_id
    FROM DONHANG
    WHERE donhang_id = p_donhang_id;
    
    -- Tính toán giảm giá nếu có khuyến mãi
    IF v_khuyenmai_id IS NOT NULL THEN
        SELECT CalculatePromotionDiscount(v_khuyenmai_id, v_tong_tien)
        INTO discount_amount;
    END IF;
    
    -- Đảm bảo số tiền giảm không vượt quá tổng tiền
    IF discount_amount > v_tong_tien THEN
        SET discount_amount = v_tong_tien;
    END IF;
    
    SET p_giam_gia = discount_amount;
    SET p_tong_cuoi_cung = v_tong_tien + v_phi_vanchuyen - discount_amount;
END//

-- Procedure 2: Tính giá trị giảm giá từ khuyến mãi
CREATE FUNCTION CalculatePromotionDiscount(
    p_khuyenmai_id VARCHAR(10),
    p_tong_tien DECIMAL(10,2)
) RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE discount_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE discount_value DECIMAL(10,2);
    DECLARE max_discount DECIMAL(10,2);
    DECLARE v_discount_type ENUM('phan_tram', 'so_tien_co_dinh');
    DECLARE min_order DECIMAL(10,2);
    
    -- Lấy thông tin khuyến mãi
    SELECT 
        k.gia_tri_giam,
        k.giam_toi_da,
        k.loai_giam_gia,
        k.don_hang_toi_thieu
    INTO 
        discount_value,
        max_discount,
        v_discount_type,
        min_order
    FROM KHUYENMAI k
    WHERE k.khuyenmai_id = p_khuyenmai_id
    AND k.trang_thai = 'dang_ap_dung'
    AND CURDATE() BETWEEN k.ngay_bat_dau AND k.ngay_ket_thuc
    AND (k.gioi_han_su_dung IS NULL OR k.so_lan_da_su_dung < k.gioi_han_su_dung)
    AND p_tong_tien >= k.don_hang_toi_thieu;
    
    -- Nếu tìm thấy khuyến mãi hợp lệ
    IF discount_value IS NOT NULL THEN
        IF v_discount_type = 'phan_tram' THEN
            SET discount_amount = p_tong_tien * (discount_value / 100);
            IF max_discount IS NOT NULL AND discount_amount > max_discount THEN
                SET discount_amount = max_discount;
            END IF;
        ELSE
            SET discount_amount = discount_value;
        END IF;
    END IF;
    
    RETURN discount_amount;
END//

DELIMITER ;

-- ============================================
-- VIEWS
-- ============================================

-- View 1: Thống kê doanh thu
CREATE VIEW THONGKE_DOANHTHU AS
SELECT 
    DATE(d.ngay_tao) AS ngay,
    COUNT(d.donhang_id) AS so_don_hang,
    SUM(d.tong_cuoi_cung) AS doanh_thu,
    AVG(d.tong_cuoi_cung) AS don_gia_trung_binh,
    SUM(d.giam_gia) AS tong_giam_gia,
    SUM(d.phi_vanchuyen) AS tong_phi_vanchuyen
FROM DONHANG d
WHERE d.trang_thai_donhang = 'da_giao'
GROUP BY DATE(d.ngay_tao);

-- View 2: Xếp hạng món ăn
CREATE VIEW XEPHANG_MONAN AS
SELECT 
    m.monan_id,
    m.ten_mon,
    m.danh_gia_tb,
    m.so_luong_da_ban,
    COUNT(DISTINCT d.donhang_id) AS so_don_da_ban,
    COUNT(DISTINCT r.nguoidung_id) AS so_luot_danh_gia,
    SUM(cd.so_luong) AS tong_so_luong_ban
FROM MONAN m
LEFT JOIN CHITIETDONHANG cd ON m.monan_id = cd.monan_id
LEFT JOIN DONHANG dh ON cd.donhang_id = dh.donhang_id AND dh.trang_thai_donhang = 'da_giao'
LEFT JOIN DANHGIA r ON m.monan_id = r.monan_id AND r.trang_thai = 'da_duyet'
GROUP BY m.monan_id, m.ten_mon, m.danh_gia_tb, m.so_luong_da_ban;

-- View 3: Khách hàng thân thiết
CREATE VIEW KHACHHANG_THANTHIET AS
SELECT 
    n.nguoidung_id,
    n.hoten,
    n.email,
    n.sodienthoai,
    COUNT(d.donhang_id) AS so_don_hang,
    SUM(d.tong_cuoi_cung) AS tong_chi_tieu,
    MAX(d.ngay_tao) AS don_hang_gan_nhat,
    AVG(d.tong_cuoi_cung) AS don_gia_trung_binh,
    DATEDIFF(CURDATE(), MAX(d.ngay_tao)) AS ngay_khong_mua
FROM NGUOIDUNG n
JOIN DONHANG d ON n.nguoidung_id = d.nguoidung_id
WHERE n.vai_tro = 'khach_hang' 
AND d.trang_thai_donhang = 'da_giao'
GROUP BY n.nguoidung_id, n.hoten, n.email, n.sodienthoai;

-- View 4: Hiệu quả khuyến mãi
CREATE VIEW HIEUQUA_KHUYENMAI AS
SELECT 
    k.khuyenmai_id,
    k.ma_khuyenmai,
    k.loai_giam_gia,
    k.gia_tri_giam,
    k.so_lan_da_su_dung,
    k.gioi_han_su_dung,
    COUNT(d.donhang_id) AS so_don_ap_dung,
    SUM(d.giam_gia) AS tong_tien_giam,
    AVG(d.tong_cuoi_cung) AS don_gia_trung_binh,
    MIN(d.ngay_tao) AS ngay_dau_ap_dung,
    MAX(d.ngay_tao) AS ngay_cuoi_ap_dung
FROM KHUYENMAI k
LEFT JOIN DONHANG d ON k.khuyenmai_id = d.khuyenmai_id AND d.trang_thai_donhang = 'da_giao'
GROUP BY k.khuyenmai_id, k.ma_khuyenmai, k.loai_giam_gia, k.gia_tri_giam, 
         k.so_lan_da_su_dung, k.gioi_han_su_dung;

-- View 5: Đánh giá chi tiết (thể hiện tính chất thực thể yếu)
CREATE VIEW DANHGIA_CHITIET AS
SELECT 
    -- Thông tin từ thực thể yếu DANHGIA
    r.donhang_id,
    r.nguoidung_id,
    r.monan_id,
    r.diem_danhgia,
    r.binh_luan,
    r.trang_thai AS trang_thai_danhgia,
    r.ngay_tao AS ngay_danhgia,
    
    -- Thông tin từ thực thể mạnh NGUOIDUNG
    n.hoten AS ten_nguoi_danhgia,
    n.email,
    
    -- Thông tin từ thực thể mạnh DONHANG
    d.ngay_tao AS ngay_dat_hang,
    d.trang_thai_donhang,
    
    -- Thông tin từ thực thể mạnh MONAN
    m.ten_mon,
    m.gia,
    
    -- Thông tin từ CHITIETDONHANG (để chứng minh đã mua)
    cd.so_luong,
    cd.don_gia,
    cd.thanh_tien
FROM DANHGIA r
-- JOIN với các thực thể mạnh mà nó phụ thuộc
JOIN NGUOIDUNG n ON r.nguoidung_id = n.nguoidung_id
JOIN DONHANG d ON r.donhang_id = d.donhang_id
JOIN MONAN m ON r.monan_id = m.monan_id
JOIN CHITIETDONHANG cd ON r.donhang_id = cd.donhang_id AND r.monan_id = cd.monan_id
WHERE r.trang_thai = 'da_duyet';

-- View 6: Tổng quan đơn hàng
CREATE VIEW DONHANG_TONGQUAN AS
SELECT 
    dh.donhang_id,
    dh.ngay_tao,
    n.hoten AS ten_khach_hang,
    n.sodienthoai,
    dh.diachi_giaohang,
    dh.tong_tien,
    dh.phi_vanchuyen,
    dh.giam_gia,
    dh.tong_cuoi_cung,
    dh.trang_thai_donhang,
    dh.trang_thai_thanhtoan,
    k.ma_khuyenmai,
    COUNT(cd.monan_id) AS so_mon,
    SUM(cd.so_luong) AS tong_so_luong,
    GROUP_CONCAT(CONCAT(m.ten_mon, ' (x', cd.so_luong, ')') SEPARATOR ', ') AS danh_sach_mon
FROM DONHANG dh
JOIN NGUOIDUNG n ON dh.nguoidung_id = n.nguoidung_id
LEFT JOIN KHUYENMAI k ON dh.khuyenmai_id = k.khuyenmai_id
JOIN CHITIETDONHANG cd ON dh.donhang_id = cd.donhang_id
JOIN MONAN m ON cd.monan_id = m.monan_id
GROUP BY dh.donhang_id, dh.ngay_tao, n.hoten, n.sodienthoai, dh.diachi_giaohang, 
         dh.tong_tien, dh.phi_vanchuyen, dh.giam_gia, dh.tong_cuoi_cung, 
         dh.trang_thai_donhang, dh.trang_thai_thanhtoan, k.ma_khuyenmai;

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
