<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
</head>
<body>

<h1>Giỏ hàng (demo)</h1>

<button onclick="xacNhanThanhToan()">Xác nhận thanh toán</button>

<div id="result" style="margin-top:20px;color:red;"></div>

<script>
function xacNhanThanhToan() {
    fetch("../order-module/index.php")
        .then(res => res.json())
        .then(data => {
            if (data.login === false) {
                document.getElementById("result").innerHTML =
                    "❌ Chưa đăng nhập – hiển thị Login Modal (demo)";
            } else {
                document.getElementById("result").innerHTML =
                    "✅ Đã đăng nhập – hiển thị Payment Modal (demo)";
            }
        })
        .catch(err => {
            document.getElementById("result").innerHTML = "❌ Lỗi kết nối backend";
        });
}
</script>

</body>
</html>