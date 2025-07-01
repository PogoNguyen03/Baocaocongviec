<?php
require 'db.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $department_id = intval($_POST['department_id'] ?? 0);
    if ($password !== $confirm) {
        $message = 'Mật khẩu xác nhận không khớp!';
    } else if ($department_id <= 0) {
        $message = 'Vui lòng chọn ban/phòng!';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, department_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $name, $email, $password_hash, $department_id);
        if ($stmt->execute()) {
            $message = 'Tài khoản của bạn đã được gửi lên admin duyệt. Vui lòng chờ xác nhận.';
        } else {
            $message = 'Email đã tồn tại hoặc lỗi hệ thống!';
        }
        $stmt->close();
    }
}
// Lấy danh sách ban/phòng
$departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Đăng ký tài khoản</div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Họ tên</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Ban/Phòng</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">-- Chọn ban/phòng --</option>
                                <?php if ($departments) { while ($d = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" id="confirm" name="confirm" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Đăng ký</button>
                        <a href="login.php" class="btn btn-link">Đã có tài khoản?</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 