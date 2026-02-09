<?php
$conn = new mysqli("localhost", "root", "", "qlybandoan");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Lỗi kết nối CSDL");
}