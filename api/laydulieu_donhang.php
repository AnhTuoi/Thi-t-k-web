<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../connect.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $conn = connectDB();
    
    // Kiểm tra kết nối
    if (!$conn) {
        throw new Exception('Không thể kết nối đến cơ sở dữ liệu');
    }
    
    // Lấy tham số từ request
    $loaiThongKe = $_GET['loai'] ?? 'theo_thang';
    $thang = $_GET['thang'] ?? date('m');
    $nam = $_GET['nam'] ?? date('Y');
    $namBatDau = $_GET['nam_bat_dau'] ?? date('Y');
    $namKetThuc = $_GET['nam_ket_thuc'] ?? date('Y');
    
    switch ($loaiThongKe) {
        case 'theo_thang':
            // Thống kê đơn hàng theo tháng
            $sql = "SELECT 
                        MONTH(d.ngay_tao) as thang,
                        YEAR(d.ngay_tao) as nam,
                        COUNT(*) as tong_donhang,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_giao' THEN 1 ELSE 0 END) as donhang_thanhcong,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_huy' THEN 1 ELSE 0 END) as donhang_huy,
                        SUM(d.tong_cuoi_cung) as tong_doanhthu,
                        AVG(d.tong_cuoi_cung) as avg_gia_tri_donhang
                    FROM DONHANG d
                    WHERE YEAR(d.ngay_tao) = ?
                    GROUP BY YEAR(d.ngay_tao), MONTH(d.ngay_tao)
                    ORDER BY nam, thang";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $nam);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            $tongDonHang = 0;
            $tongDoanhThu = 0;
            $tongDonThanhCong = 0;
            $tongDonHuy = 0;
            
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'thang' => $row['thang'],
                    'nam' => $row['nam'],
                    'tong_donhang' => (int)$row['tong_donhang'],
                    'donhang_thanhcong' => (int)$row['donhang_thanhcong'],
                    'donhang_huy' => (int)$row['donhang_huy'],
                    'tong_doanhthu' => (float)$row['tong_doanhthu'],
                    'avg_gia_tri_donhang' => (float)$row['avg_gia_tri_donhang']
                ];
                
                $tongDonHang += (int)$row['tong_donhang'];
                $tongDoanhThu += (float)$row['tong_doanhthu'];
                $tongDonThanhCong += (int)$row['donhang_thanhcong'];
                $tongDonHuy += (int)$row['donhang_huy'];
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'summary' => [
                    'tong_donhang' => $tongDonHang,
                    'tong_doanhthu' => $tongDoanhThu,
                    'tong_donhang_thanhcong' => $tongDonThanhCong,
                    'tong_donhang_huy' => $tongDonHuy,
                    'ty_le_thanhcong' => $tongDonHang > 0 ? round(($tongDonThanhCong / $tongDonHang) * 100, 2) : 0
                ],
                'message' => 'Lấy dữ liệu đơn hàng theo tháng thành công'
            ];
            break;
            
        case 'theo_nam':
            // Thống kê đơn hàng theo năm
            $sql = "SELECT 
                        YEAR(d.ngay_tao) as nam,
                        COUNT(*) as tong_donhang,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_giao' THEN 1 ELSE 0 END) as donhang_thanhcong,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_huy' THEN 1 ELSE 0 END) as donhang_huy,
                        SUM(d.tong_cuoi_cung) as tong_doanhthu,
                        AVG(d.tong_cuoi_cung) as avg_gia_tri_donhang
                    FROM DONHANG d
                    WHERE YEAR(d.ngay_tao) BETWEEN ? AND ?
                    GROUP BY YEAR(d.ngay_tao)
                    ORDER BY nam";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $namBatDau, $namKetThuc);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'nam' => $row['nam'],
                    'tong_donhang' => (int)$row['tong_donhang'],
                    'donhang_thanhcong' => (int)$row['donhang_thanhcong'],
                    'donhang_huy' => (int)$row['donhang_huy'],
                    'tong_doanhthu' => (float)$row['tong_doanhthu'],
                    'avg_gia_tri_donhang' => (float)$row['avg_gia_tri_donhang']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy dữ liệu đơn hàng theo năm thành công'
            ];
            break;
            
        case 'chi_tiet_thang':
            // Chi tiết đơn hàng trong tháng cụ thể
            $sql = "SELECT 
                        DATE(d.ngay_tao) as ngay,
                        COUNT(*) as tong_donhang,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_giao' THEN 1 ELSE 0 END) as donhang_thanhcong,
                        SUM(CASE WHEN d.trang_thai_donhang = 'da_huy' THEN 1 ELSE 0 END) as donhang_huy,
                        SUM(d.tong_cuoi_cung) as tong_doanhthu
                    FROM DONHANG d
                    WHERE YEAR(d.ngay_tao) = ? AND MONTH(d.ngay_tao) = ?
                    GROUP BY DATE(d.ngay_tao)
                    ORDER BY ngay";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nam, $thang);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'ngay' => $row['ngay'],
                    'tong_donhang' => (int)$row['tong_donhang'],
                    'donhang_thanhcong' => (int)$row['donhang_thanhcong'],
                    'donhang_huy' => (int)$row['donhang_huy'],
                    'tong_doanhthu' => (float)$row['tong_doanhthu']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy chi tiết đơn hàng theo ngày thành công'
            ];
            break;
            
        case 'trang_thai':
            // Thống kê theo trạng thái đơn hàng
            $sql = "SELECT 
                        d.trang_thai_donhang,
                        COUNT(*) as so_luong,
                        SUM(d.tong_cuoi_cung) as tong_doanhthu,
                        AVG(d.tong_cuoi_cung) as avg_gia_tri
                    FROM DONHANG d
                    WHERE YEAR(d.ngay_tao) = ? AND MONTH(d.ngay_tao) = ?
                    GROUP BY d.trang_thai_donhang
                    ORDER BY so_luong DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nam, $thang);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'trang_thai' => $row['trang_thai_donhang'],
                    'so_luong' => (int)$row['so_luong'],
                    'tong_doanhthu' => (float)$row['tong_doanhthu'],
                    'avg_gia_tri' => (float)$row['avg_gia_tri'],
                    'phan_tram' => 0 // Sẽ tính sau
                ];
            }
            
            // Tính phần trăm
            $tong = array_sum(array_column($data, 'so_luong'));
            foreach ($data as &$item) {
                $item['phan_tram'] = $tong > 0 ? round(($item['so_luong'] / $tong) * 100, 2) : 0;
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy thống kê trạng thái đơn hàng thành công'
            ];
            break;
            
        case 'phuong_thuc_thanh_toan':
            // Thống kê theo phương thức thanh toán
            $sql = "SELECT 
                        d.phuong_thuc_thanhtoan,
                        COUNT(*) as so_luong,
                        SUM(d.tong_cuoi_cung) as tong_doanhthu
                    FROM DONHANG d
                    WHERE YEAR(d.ngay_tao) = ?
                    GROUP BY d.phuong_thuc_thanhtoan
                    ORDER BY so_luong DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $nam);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'phuong_thuc' => $row['phuong_thuc_thanhtoan'],
                    'so_luong' => (int)$row['so_luong'],
                    'tong_doanhthu' => (float)$row['tong_doanhthu']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy thống kê phương thức thanh toán thành công'
            ];
            break;
            
        case 'donhang_gan_day':
            // Lấy danh sách đơn hàng gần đây
            $limit = $_GET['limit'] ?? 10;
            $sql = "SELECT 
                        d.donhang_id,
                        d.ngay_tao,
                        n.hoten,
                        n.sodienthoai,
                        d.tong_cuoi_cung,
                        d.trang_thai_donhang,
                        d.phuong_thuc_thanhtoan,
                        COUNT(ct.monan_id) as so_mon
                    FROM DONHANG d
                    JOIN NGUOIDUNG n ON d.nguoidung_id = n.nguoidung_id
                    LEFT JOIN CHITIETDONHANG ct ON d.donhang_id = ct.donhang_id
                    GROUP BY d.donhang_id
                    ORDER BY d.ngay_tao DESC
                    LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'donhang_id' => $row['donhang_id'],
                    'ngay_tao' => $row['ngay_tao'],
                    'hoten' => $row['hoten'],
                    'sodienthoai' => $row['sodienthoai'],
                    'tong_cuoi_cung' => (float)$row['tong_cuoi_cung'],
                    'trang_thai_donhang' => $row['trang_thai_donhang'],
                    'phuong_thuc_thanhtoan' => $row['phuong_thuc_thanhtoan'],
                    'so_mon' => (int)$row['so_mon']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $data,
                'message' => 'Lấy danh sách đơn hàng gần đây thành công'
            ];
            break;
            
        default:
            $response['message'] = 'Loại thống kê không hợp lệ';
            break;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = 'Lỗi: ' . $e->getMessage();
    error_log('Lỗi laydulieu_donhang.php: ' . $e->getMessage());
}

echo json_encode($response);
?>