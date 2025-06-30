<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = '';
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$report_id = intval($_GET['id']);
// Lấy dữ liệu báo cáo
$stmt = $conn->prepare('SELECT title, content, created_at FROM reports WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $report_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}
$report = $result->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $created_at = $_POST['created_at'];
    $stmt = $conn->prepare('UPDATE reports SET title = ?, content = ?, created_at = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->bind_param('sssii', $title, $content, $created_at, $report_id, $user_id);
    if ($stmt->execute()) {
        // Kiểm tra role
        $stmt_role = $conn->prepare('SELECT role FROM users WHERE id = ?');
        $stmt_role->bind_param('i', $user_id);
        $stmt_role->execute();
        $stmt_role->bind_result($role);
        $stmt_role->fetch();
        $stmt_role->close();
        if ($role === 'admin') {
            header('Location: admin_reports.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $message = 'Lỗi khi cập nhật báo cáo!';
    }
    $stmt->close();
}
$created_at_value = date('Y-m-d\TH:i', strtotime($report['created_at']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa báo cáo</title>
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
                <div class="card-header bg-primary text-white fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Sửa báo cáo</div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($report['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">Nội dung</label>
                            <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($report['content']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="created_at" class="form-label fw-semibold">Ngày giờ báo cáo</label>
                            <input type="datetime-local" class="form-control" id="created_at" name="created_at" value="<?php echo $created_at_value; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Cập nhật</button>
                        <a href="admin_reports.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 