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
$stmt = $conn->prepare('SELECT reports.title, reports.content, reports.created_at, users.name, users.email, users.role FROM reports JOIN users ON reports.user_id = users.id WHERE reports.id = ?');
$stmt->bind_param('i', $report_id);
$stmt->execute();
$stmt->bind_result($title, $content, $created_at, $user_name, $user_email, $user_role);
if ($stmt->fetch()) {
    // Hiển thị bên dưới
} else {
    echo '<div class="alert alert-danger">Không tìm thấy báo cáo!</div>';
    exit;
}
$stmt->close();

// Xử lý nội dung để loại bỏ khoảng trắng thừa nhưng giữ nguyên định dạng
$content = trim($content);
// Loại bỏ khoảng trắng thừa ở đầu mỗi dòng nhưng giữ nguyên thụt lề có ý nghĩa
$lines = explode("\n", $content);
$cleaned_lines = [];

// Tìm số khoảng trắng thừa tối thiểu ở đầu các dòng
$min_leading_spaces = PHP_INT_MAX;
foreach ($lines as $line) {
    if (trim($line) !== '') {
        $leading_spaces = strlen($line) - strlen(ltrim($line));
        if ($leading_spaces < $min_leading_spaces) {
            $min_leading_spaces = $leading_spaces;
        }
    }
}

// Nếu có khoảng trắng thừa chung, loại bỏ chúng
if ($min_leading_spaces > 0 && $min_leading_spaces < PHP_INT_MAX) {
    foreach ($lines as $line) {
        if (trim($line) === '') {
            $cleaned_lines[] = '';
        } else {
            // Loại bỏ khoảng trắng thừa chung nhưng giữ nguyên thụt lề có ý nghĩa
            $cleaned_lines[] = substr($line, $min_leading_spaces);
        }
    }
} else {
    // Không có khoảng trắng thừa chung, giữ nguyên
    $cleaned_lines = $lines;
}

$content = implode("\n", $cleaned_lines);
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
                    <p class="mb-1"><strong>Người gửi:</strong> <?php echo htmlspecialchars($user_name); ?> 
                        <?php if ($user_role === 'admin'): ?>
                            <span class="badge bg-danger ms-1">Admin</span>
                        <?php elseif ($user_role === 'quanly'): ?>
                            <span class="badge bg-warning text-dark ms-1">Quản lý</span>
                        <?php elseif ($user_role === 'nhomtruong'): ?>
                            <span class="badge bg-success ms-1">Nhóm trưởng</span>
                        <?php else: ?>
                            <span class="badge bg-primary ms-1">User</span>
                        <?php endif; ?>
                        (<?php echo htmlspecialchars($user_email); ?>)</p>
                    <p class="mb-1"><strong>Ngày giờ báo cáo:</strong> <?php echo $created_at; ?></p>
                    <hr>
                    <div class="mb-3">
                        <strong>Nội dung:</strong>
                        <div class="mt-2 p-3 bg-light border rounded" style="white-space: pre-wrap; font-family: inherit; line-height: 1.6; text-align: left;"><?php echo htmlspecialchars($content); ?></div>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 