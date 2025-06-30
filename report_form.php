<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO reports (user_id, title, content, created_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $user_id, $title, $content, $created_at);
    if ($stmt->execute()) {
        header('Location: index.php');
        exit;
    } else {
        $message = 'Lỗi khi tạo báo cáo!';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo báo cáo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white fw-bold"><i class="fa-solid fa-plus me-2"></i>Tạo báo cáo mới</div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">Nội dung</label>
                            <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i> Tạo báo cáo</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 