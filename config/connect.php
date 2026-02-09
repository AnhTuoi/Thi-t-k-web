<?php
// includes/db_connection.php - Kết nối database MySQLi

function getConnection() {
    $servername = "localhost";
    $username = "root"; // Thay đổi theo cấu hình của bạn
    $password = ""; // Thay đổi theo cấu hình của bạn
    $database = "qlybandoan"; // Tên database
    
    // Tạo kết nối
    $conn = new mysqli($servername, $username, $password, $database);
    
    // Kiểm tra kết nối
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Đặt charset UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Hàm helper để thực thi query an toàn
function executeQuery($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}
?>