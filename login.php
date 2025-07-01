<?php
require 'db.php';
session_start();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare('SELECT id, name, password, is_verified, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hash, $is_verified, $role);
        $stmt->fetch();
        if (!$is_verified) {
            $message = 'Tài khoản của bạn chưa được admin duyệt!';
        } elseif (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            if ($role === 'admin_tong' || $role === 'admin_ban') {
                header('Location: admin_reports.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $message = 'Sai mật khẩu!';
        }
    } else {
        $message = 'Email không tồn tại!';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Đăng nhập</div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Đăng nhập</button>
                        <a href="register.php" class="btn btn-link">Đăng ký</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 