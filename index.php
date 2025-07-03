<?php
require 'db.php';
require 'notification_helper.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
// Kiểm tra quyền và ban của user
$stmt_role = $conn->prepare('SELECT role, department_id FROM users WHERE id = ?');
$stmt_role->bind_param('i', $user_id);
$stmt_role->execute();
$stmt_role->bind_result($role, $user_department_id);
$stmt_role->fetch();
$stmt_role->close();
// Nếu là admin, quản lý, nhóm trưởng thì chuyển sang admin_reports.php
if ($role === 'admin' || $role === 'quanly' || $role === 'nhomtruong') {
    header('Location: admin_reports.php');
    exit;
}
// Xử lý xóa báo cáo
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($role === 'admin') {
        // Admin có thể xóa bất kỳ báo cáo nào
        $stmt = $conn->prepare('DELETE FROM reports WHERE id = ?');
        $stmt->bind_param('i', $delete_id);
    } else if ($role === 'quanly') {
        // Quản lý chỉ xóa báo cáo của ban mình
        $stmt = $conn->prepare('DELETE FROM reports WHERE id = ? AND department_id = ?');
        $stmt->bind_param('ii', $delete_id, $user_department_id);
    } else {
        // Nhóm trưởng và user chỉ xóa báo cáo của mình
        $stmt = $conn->prepare('DELETE FROM reports WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $delete_id, $user_id);
    }
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit;
}
// Xử lý filter
$where = [];
$params = [];
$types = '';
if ($role === 'admin') {
    // Admin xem tất cả báo cáo
    if (!empty($_GET['from_date'])) {
        $where[] = 'DATE(reports.created_at) >= ?';
        $params[] = $_GET['from_date'];
        $types .= 's';
    }
    if (!empty($_GET['to_date'])) {
        $where[] = 'DATE(reports.created_at) <= ?';
        $params[] = $_GET['to_date'];
        $types .= 's';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    // Đếm tổng số báo cáo
    $count_sql = "SELECT COUNT(*) FROM reports JOIN users ON reports.user_id = users.id $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($params) $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_stmt->bind_result($total);
    $count_stmt->fetch();
    $count_stmt->close();
    $total_pages = ceil($total / $limit);
    // Lấy báo cáo trang hiện tại
    $sql = "SELECT reports.id, reports.title, reports.content, users.name, reports.user_id, reports.created_at FROM reports JOIN users ON reports.user_id = users.id $where_sql ORDER BY reports.created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else if ($role === 'quanly') {
    // Quản lý chỉ xem báo cáo của user và nhomtruong trong ban mình
    if (!empty($_GET['from_date'])) {
        $where[] = 'DATE(reports.created_at) >= ?';
        $params[] = $_GET['from_date'];
        $types .= 's';
    }
    if (!empty($_GET['to_date'])) {
        $where[] = 'DATE(reports.created_at) <= ?';
        $params[] = $_GET['to_date'];
        $types .= 's';
    }
    $where[] = 'reports.department_id = ?';
    $params[] = $user_department_id;
    $types .= 'i';
    $where[] = '(users.role = \'user\' OR users.role = \'nhomtruong\')';
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    // Đếm tổng số báo cáo
    $count_sql = "SELECT COUNT(*) FROM reports JOIN users ON reports.user_id = users.id $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($params) $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_stmt->bind_result($total);
    $count_stmt->fetch();
    $count_stmt->close();
    $total_pages = ceil($total / $limit);
    // Lấy báo cáo trang hiện tại
    $sql = "SELECT reports.id, reports.title, reports.content, users.name, reports.user_id, reports.created_at FROM reports JOIN users ON reports.user_id = users.id $where_sql ORDER BY reports.created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else if ($role === 'nhomtruong') {
    // Nhóm trưởng chỉ xem báo cáo của user trong ban mình
    if (!empty($_GET['from_date'])) {
        $where[] = 'DATE(reports.created_at) >= ?';
        $params[] = $_GET['from_date'];
        $types .= 's';
    }
    if (!empty($_GET['to_date'])) {
        $where[] = 'DATE(reports.created_at) <= ?';
        $params[] = $_GET['to_date'];
        $types .= 's';
    }
    $where[] = 'reports.department_id = ?';
    $params[] = $user_department_id;
    $types .= 'i';
    $where[] = 'users.role = \'user\'';
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    // Đếm tổng số báo cáo
    $count_sql = "SELECT COUNT(*) FROM reports JOIN users ON reports.user_id = users.id $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($params) $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_stmt->bind_result($total);
    $count_stmt->fetch();
    $count_stmt->close();
    $total_pages = ceil($total / $limit);
    // Lấy báo cáo trang hiện tại
    $sql = "SELECT reports.id, reports.title, reports.content, users.name, reports.user_id, reports.created_at FROM reports JOIN users ON reports.user_id = users.id $where_sql ORDER BY reports.created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // User chỉ xem báo cáo của mình
    if (!empty($_GET['from_date'])) {
        $where[] = 'DATE(created_at) >= ?';
        $params[] = $_GET['from_date'];
        $types .= 's';
    }
    if (!empty($_GET['to_date'])) {
        $where[] = 'DATE(created_at) <= ?';
        $params[] = $_GET['to_date'];
        $types .= 's';
    }
    $where[] = 'user_id = ?';
    $params[] = $user_id;
    $types .= 'i';
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    // Đếm tổng số báo cáo
    $count_sql = "SELECT COUNT(*) FROM reports $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($params) $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_stmt->bind_result($total);
    $count_stmt->fetch();
    $count_stmt->close();
    $total_pages = ceil($total / $limit);
    // Lấy báo cáo trang hiện tại
    $sql = "SELECT id, title, content, user_id, created_at FROM reports $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chính</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-filter { background: #f8f9fa; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-responsive { border-radius: 1rem; overflow: hidden; }
        .table th, .table td { vertical-align: middle; }
        .pagination .page-link { border-radius: 50% !important; margin: 0 2px; }
        .badge-user { background: #0d6efd; }
        .badge-admin { background: #dc3545; }
        .cell-content-limit { max-width: 260px; max-height: 60px; overflow: auto; white-space: pre-line; text-overflow: ellipsis; }
        @media (max-width: 576px) {
            .card-filter, .card, .table-responsive { border-radius: .5rem; box-shadow: none; }
            .table th, .table td { font-size: 13px; padding: 6px 4px; }
            .badge-user, .badge-admin { font-size: 11px; padding: 3px 7px; }
            .cell-content-limit { max-width: 120px; max-height: 40px; font-size: 12px; }
            .btn-sm { font-size: 12px; padding: 5px 8px; }
            .navbar .navbar-brand { font-size: 15px; }
            .container, .container-fluid { padding-left: 4px; padding-right: 4px; }
        }
    </style>
</head>
<body class="bg-light" 
      data-user-id="<?php echo $user_id; ?>" 
      data-user-name="<?php echo htmlspecialchars($user_name); ?>" 
      data-user-role="<?php echo $role; ?>" 
      data-department-id="<?php echo $user_department_id; ?>">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold"><i class="fa-solid fa-user me-2"></i><?php echo htmlspecialchars($user_name); ?></span>
    <div class="d-flex">
      <a href="report_form.php" class="btn btn-success me-2"><i class="fa-solid fa-plus"></i> Tạo báo cáo</a>
      <a href="logout.php" class="btn btn-outline-light"><i class="fa-solid fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
  </div>
</nav>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card card-filter shadow-sm p-3">
                <form class="row g-3" method="get">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Từ ngày</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đến ngày</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Lọc</button>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom-0 pb-0">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-table-list me-2"></i><?php echo ($role === 'admin') ? 'Tất cả báo cáo' : ($role === 'quanly' ? 'Báo cáo của ban mình' : 'Danh sách báo cáo của bạn'); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php if ($role === 'admin' || $role === 'quanly'): ?><th>Người gửi</th><?php endif; ?>
                            <th>Tiêu đề</th>
                            <th>Nội dung</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($role === 'admin' || $role === 'quanly'): ?><td><?php echo htmlspecialchars($row['name']); ?> 
                                <?php if ($role === 'admin'): ?>
                                    <span class="badge badge-admin ms-1">Admin tổng</span>
                                <?php else: ?>
                                    <span class="badge badge-user ms-1">User</span>
                                <?php endif; ?>
                            </td><?php endif; ?>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="cell-content-limit" title="<?php echo htmlspecialchars($row['content']); ?>"><?php echo htmlspecialchars(mb_strimwidth($row['content'], 0, 100, '...')); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td class="text-center">
                                <a href="view_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info me-1" title="Xem"><i class="fa-solid fa-eye"></i></a>
                                <?php if ($role === 'admin' || $row['user_id'] == $user_id): ?>
                                    <a href="edit_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary me-1" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0">
            <nav>
              <ul class="pagination justify-content-center mb-0">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?<?php 
                        $q = $_GET; $q['page'] = $i; echo http_build_query($q); 
                    ?>"><?php echo $i; ?></a>
                  </li>
                <?php endfor; ?>
              </ul>
            </nav>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="notification.js"></script>
<script>
function confirmDelete(id) {
    if (confirm('Bạn có chắc chắn muốn xóa báo cáo này?')) {
        window.location = 'index.php?delete=' + id + '&<?php echo http_build_query($_GET); ?>';
    }
}

// Test notification (chỉ cho admin)
<?php if ($role === 'admin' || $role === 'quanly'): ?>
function testNotification() {
    if (window.notificationClient) {
        window.notificationClient.sendNotification('Đây là thông báo test từ admin!', 'info');
    }
}
<?php endif; ?>
</script>
</body>
</html> 