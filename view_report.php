<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Thiếu ID báo cáo!</div>';
    exit;
}
$report_id = intval($_GET['id']);
// Lấy thông tin báo cáo và user
$stmt = $conn->prepare('SELECT reports.title, reports.content, reports.created_at, users.name, users.email FROM reports JOIN users ON reports.user_id = users.id WHERE reports.id = ?');
$stmt->bind_param('i', $report_id);
$stmt->execute();
$stmt->bind_result($title, $content, $created_at, $user_name, $user_email);
if ($stmt->fetch()) {
    // Hiển thị bên dưới
} else {
    echo '<div class="alert alert-danger">Không tìm thấy báo cáo!</div>';
    exit;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xem báo cáo</title>
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
                <div class="card-header bg-info text-white fw-bold"><i class="fa-solid fa-eye me-2"></i>Chi tiết báo cáo</div>
                <div class="card-body">
                    <h5 class="card-title mb-3">Tiêu đề: <span class="fw-semibold"><?php echo htmlspecialchars($title); ?></span></h5>
                    <p class="mb-1"><strong>Người gửi:</strong> <?php echo htmlspecialchars($user_name); ?> <span class="badge bg-primary ms-1">User</span> (<?php echo htmlspecialchars($user_email); ?>)</p>
                    <p class="mb-1"><strong>Ngày giờ báo cáo:</strong> <?php echo $created_at; ?></p>
                    <hr>
                    <div class="mb-3" style="white-space:pre-line;"><strong>Nội dung:</strong><br><?php echo nl2br(htmlspecialchars($content)); ?></div>
                    <a href="javascript:history.back()" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 