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
            if ($role === 'admin' || $role === 'quanly') {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .login-card {
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            background: rgba(255,255,255,0.97);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-header img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 8px rgba(102,126,234,0.15);
        }
        .form-control:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .btn-link {
            color: #764ba2;
        }
        .btn-link:hover {
            color: #5a6fd8;
        }
        @media (max-width: 576px) {
            .login-card { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="login-card">
                <div class="login-header">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User Icon">
                    <h3 class="fw-bold mb-0"><i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập</h3>
                </div>
                    <?php if ($message): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $message; ?></div>
                    <?php endif; ?>
                <form method="post" autocomplete="off">
                        <div class="mb-3">
                        <label for="email" class="form-label fw-semibold"><i class="fa-solid fa-envelope me-1"></i>Email</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        <div class="mb-3">
                        <label for="password" class="form-label fw-semibold"><i class="fa-solid fa-lock me-1"></i>Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-2"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Đăng nhập</button>
                    <a href="register.php" class="btn btn-link w-100">Chưa có tài khoản? Đăng ký ngay</a>
                    </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 