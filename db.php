<?php
// db.php
$host = 'localhost';
$db   = 'baocao'; // Đổi tên DB nếu cần
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}
?> 