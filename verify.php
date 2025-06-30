<?php
require 'db.php';
$message = '';
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $stmt = $conn->prepare('SELECT id FROM users WHERE verification_code = ? AND is_verified = 0');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare('UPDATE users SET is_verified = 1, verification_code = NULL WHERE verification_code = ?');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $message = 'Xác thực thành công! Bạn có thể đăng nhập.';
    } else {
        $message = 'Mã xác thực không hợp lệ hoặc tài khoản đã được xác thực.';
    }
    $stmt->close();
} else {
    $message = 'Thiếu mã xác thực.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác thực email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Xác thực email</div>
                <div class="card-body">
                    <div class="alert alert-info"><?php echo $message; ?></div>
                    <a href="login.php" class="btn btn-primary">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 