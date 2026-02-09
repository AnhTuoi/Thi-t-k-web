<?php
/**
 * API LẤY DỮ LIỆU TỔNG QUAN - DASHBOARD BÁO CÁO
 * File: api/laydulieu_tongquan.php
 * Mục đích: Trả về dữ liệu tổng quan cho trang dashboard báo cáo
 */

// Bật hiển thị lỗi cho development (tắt khi deploy production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header JSON
header('Content-Type: application/json; charset=utf-8');

// Include file kết nối database
require_once '../connect.php';

// Khởi tạo kết nối
$conn = getConnection();

// Kiểm tra kết nối
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối database'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ============================================
    // 1. TỔNG DOANH THU
    // ============================================
    $sql_doanhthu = "
        SELECT 
            COALESCE(SUM(tong_cuoi_cung), 0) as tong_doanhthu,
            COUNT(*) as so_don_thanh_cong
        FROM DONHANG 
        WHERE trang_thai_thanhtoan = 'da_thanh_toan'
        AND trang_thai_donhang != 'da_huy'
    ";
    $result_doanhthu = $conn->query($sql_doanhthu);
    $data_doanhthu = $result_doanhthu->fetch_assoc();

    // ============================================
    // 2. TỔNG SỐ ĐơN HÀNG THEO TRẠNG THÁI
    // ============================================
    $sql_donhang = "
        SELECT 
            COUNT(*) as tong_don,
            SUM(CASE WHEN trang_thai_donhang = 'cho_xac_nhan' THEN 1 ELSE 0 END) as cho_xac_nhan,
            SUM(CASE WHEN trang_thai_donhang = 'da_xac_nhan' THEN 1 ELSE 0 END) as da_xac_nhan,
            SUM(CASE WHEN trang_thai_donhang = 'dang_giao' THEN 1 ELSE 0 END) as dang_giao,
            SUM(CASE WHEN trang_thai_donhang = 'da_giao' THEN 1 ELSE 0 END) as da_giao,
            SUM(CASE WHEN trang_thai_donhang = 'da_huy' THEN 1 ELSE 0 END) as da_huy
        FROM DONHANG
    ";
    $result_donhang = $conn->query($sql_donhang);
    $data_donhang = $result_donhang->fetch_assoc();

    // ============================================
    // 3. THỐNG KÊ TÀI KHOẢN
    // ============================================
    $sql_taikhoan = "
        SELECT 
            COUNT(*) as tong_nguoidung,
            SUM(CASE WHEN vai_tro = 'khach_hang' THEN 1 ELSE 0 END) as khach_hang,
            SUM(CASE WHEN vai_tro = 'nhan_vien' THEN 1 ELSE 0 END) as nhan_vien,
            SUM(CASE WHEN vai_tro = 'quan_tri' THEN 1 ELSE 0 END) as quan_tri,
            SUM(CASE WHEN trang_thai = 'hoat_dong' THEN 1 ELSE 0 END) as dang_hoat_dong,
            SUM(CASE WHEN trang_thai = 'vo_hieu_hoa' THEN 1 ELSE 0 END) as vo_hieu_hoa
        FROM NGUOIDUNG
    ";
    $result_taikhoan = $conn->query($sql_taikhoan);
    $data_taikhoan = $result_taikhoan->fetch_assoc();

    // ============================================
    // 4. SỐ LƯỢNG TÀI KHOẢN KÍCH HOẠT
    // ============================================
    $sql_tk_kichhoat = "
        SELECT 
            COUNT(*) as tong_taikhoan,
            SUM(CASE WHEN trang_thai = 'kich_hoat' THEN 1 ELSE 0 END) as da_kich_hoat,
            SUM(CASE WHEN trang_thai = 'chua_kich_hoat' THEN 1 ELSE 0 END) as chua_kich_hoat,
            SUM(CASE WHEN trang_thai = 'khoa' THEN 1 ELSE 0 END) as bi_khoa
        FROM TAIKHOAN
    ";
    $result_tk_kichhoat = $conn->query($sql_tk_kichhoat);
    $data_tk_kichhoat = $result_tk_kichhoat->fetch_assoc();

    // ============================================
    // 5. MÓN ĂN BÁN CHẠY NHẤT (TOP 5)
    // ============================================
    $sql_monan_banchay = "
        SELECT 
            m.ten_mon,
            m.danh_muc,
            m.gia,
            COALESCE(SUM(ct.so_luong), 0) as tong_ban
        FROM MONAN m
        LEFT JOIN CHITIETDONHANG ct ON m.monan_id = ct.monan_id
        LEFT JOIN DONHANG d ON ct.donhang_id = d.donhang_id
        WHERE d.trang_thai_donhang != 'da_huy' OR d.trang_thai_donhang IS NULL
        GROUP BY m.monan_id, m.ten_mon, m.danh_muc, m.gia
        ORDER BY tong_ban DESC
        LIMIT 5
    ";
    $result_monan = $conn->query($sql_monan_banchay);
    $monan_banchay = [];
    while ($row = $result_monan->fetch_assoc()) {
        $monan_banchay[] = $row;
    }

    // ============================================
    // 6. DOANH THU 7 NGÀY GẦN NHẤT
    // ============================================
    $sql_doanhthu_7ngay = "
        SELECT 
            DATE(ngay_tao) as ngay,
            COALESCE(SUM(tong_cuoi_cung), 0) as doanhthu,
            COUNT(*) as so_don
        FROM DONHANG
        WHERE trang_thai_thanhtoan = 'da_thanh_toan'
        AND trang_thai_donhang != 'da_huy'
        AND ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(ngay_tao)
        ORDER BY ngay ASC
    ";
    $result_7ngay = $conn->query($sql_doanhthu_7ngay);
    $doanhthu_7ngay = [];
    while ($row = $result_7ngay->fetch_assoc()) {
        $doanhthu_7ngay[] = $row;
    }

    // ============================================
    // 7. KHUYẾN MÃI ĐANG ÁP DỤNG
    // ============================================
    $sql_khuyenmai = "
        SELECT 
            COUNT(*) as tong_km,
            SUM(CASE WHEN trang_thai = 'dang_ap_dung' THEN 1 ELSE 0 END) as dang_ap_dung,
            SUM(so_lan_da_su_dung) as tong_luot_su_dung
        FROM KHUYENMAI
        WHERE ngay_bat_dau <= CURDATE() 
        AND ngay_ket_thuc >= CURDATE()
    ";
    $result_km = $conn->query($sql_khuyenmai);
    $data_khuyenmai = $result_km->fetch_assoc();

    // ============================================
    // TRẢ VỀ DỮ LIỆU JSON
    // ============================================
    $response = [
        'success' => true,
        'message' => 'Lấy dữ liệu thành công',
        'data' => [
            'doanh_thu' => [
                'tong_doanhthu' => floatval($data_doanhthu['tong_doanhthu']),
                'so_don_thanh_cong' => intval($data_doanhthu['so_don_thanh_cong']),
                'doanhthu_trung_binh' => $data_doanhthu['so_don_thanh_cong'] > 0 
                    ? floatval($data_doanhthu['tong_doanhthu'] / $data_doanhthu['so_don_thanh_cong']) 
                    : 0
            ],
            'don_hang' => [
                'tong_don' => intval($data_donhang['tong_don']),
                'cho_xac_nhan' => intval($data_donhang['cho_xac_nhan']),
                'da_xac_nhan' => intval($data_donhang['da_xac_nhan']),
                'dang_giao' => intval($data_donhang['dang_giao']),
                'da_giao' => intval($data_donhang['da_giao']),
                'da_huy' => intval($data_donhang['da_huy']),
                'ty_le_huy' => $data_donhang['tong_don'] > 0 
                    ? round(($data_donhang['da_huy'] / $data_donhang['tong_don']) * 100, 2) 
                    : 0
            ],
            'tai_khoan' => [
                'nguoi_dung' => [
                    'tong' => intval($data_taikhoan['tong_nguoidung']),
                    'khach_hang' => intval($data_taikhoan['khach_hang']),
                    'nhan_vien' => intval($data_taikhoan['nhan_vien']),
                    'quan_tri' => intval($data_taikhoan['quan_tri']),
                    'hoat_dong' => intval($data_taikhoan['dang_hoat_dong']),
                    'vo_hieu_hoa' => intval($data_taikhoan['vo_hieu_hoa'])
                ],
                'tai_khoan' => [
                    'tong' => intval($data_tk_kichhoat['tong_taikhoan']),
                    'da_kich_hoat' => intval($data_tk_kichhoat['da_kich_hoat']),
                    'chua_kich_hoat' => intval($data_tk_kichhoat['chua_kich_hoat']),
                    'bi_khoa' => intval($data_tk_kichhoat['bi_khoa'])
                ]
            ],
            'mon_an_ban_chay' => $monan_banchay,
            'doanhthu_7ngay' => $doanhthu_7ngay,
            'khuyen_mai' => [
                'tong' => intval($data_khuyenmai['tong_km']),
                'dang_ap_dung' => intval($data_khuyenmai['dang_ap_dung']),
                'tong_luot_su_dung' => intval($data_khuyenmai['tong_luot_su_dung'] ?? 0)
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Đóng kết nối
    if ($conn) {
        $conn->close();
    }
}
?>