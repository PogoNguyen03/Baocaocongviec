<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Kiểm tra quyền admin và ban
$stmt = $conn->prepare('SELECT role, name, department_id FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role, $admin_name, $admin_department_id);
$stmt->fetch();
$stmt->close();
if ($role !== 'admin_ban' && $role !== 'admin_tong') {
    echo '<div style="margin:40px auto;max-width:500px;" class="alert alert-danger">Bạn không có quyền truy cập trang này!</div>';
    exit;
}
// Lấy danh sách user cho bộ lọc (chỉ user của ban mình nếu là admin_ban)
if ($role === 'admin_ban') {
    $users = $conn->prepare('SELECT id, name FROM users WHERE department_id = ? ORDER BY name');
    $users->bind_param('i', $admin_department_id);
    $users->execute();
    $users = $users->get_result();
} else {
    $users = $conn->query('SELECT id, name FROM users ORDER BY name');
}
// Xử lý filter
$where = [];
$params = [];
$types = '';
if ($role === 'admin_ban') {
    // Admin ban chỉ xem báo cáo của ban mình
    $where[] = 'reports.department_id = ?';
    $params[] = $admin_department_id;
    $types .= 'i';
}
if (!empty($_GET['user_id'])) {
    $where[] = 'reports.user_id = ?';
    $params[] = $_GET['user_id'];
    $types .= 'i';
}
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
// Phân trang
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
// Lấy báo cáo trang hiện tại (thêm users.role)
$sql = "SELECT reports.id, users.name, users.email, users.role, reports.title, reports.content, reports.user_id, reports.created_at FROM reports JOIN users ON reports.user_id = users.id $where_sql ORDER BY reports.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
// Xử lý duyệt user (chỉ admin_tong mới có quyền)
if ($role === 'admin_tong') {
    if (isset($_GET['approve_user'])) {
        $uid = intval($_GET['approve_user']);
        $stmt = $conn->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_reports.php?tab=users');
        exit;
    }
    if (isset($_GET['delete_user'])) {
        $uid = intval($_GET['delete_user']);
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_reports.php?tab=users');
        exit;
    }
    // Lấy user chờ duyệt
    $pending_users = $conn->query("SELECT id, name, email, created_at FROM users WHERE is_verified = 0 ORDER BY created_at DESC");
} else {
    $pending_users = null;
}
// Xử lý xóa báo cáo
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($role === 'admin_tong') {
        // Admin tổng có thể xóa bất kỳ báo cáo nào
        $stmt = $conn->prepare('DELETE FROM reports WHERE id = ?');
        $stmt->bind_param('i', $delete_id);
    } else {
        // Admin ban chỉ xóa báo cáo của ban mình
        $stmt = $conn->prepare('DELETE FROM reports WHERE id = ? AND department_id = ?');
        $stmt->bind_param('ii', $delete_id, $admin_department_id);
    }
    $stmt->execute();
    $stmt->close();
    // Giữ lại filter khi reload
    $q = $_GET;
    unset($q['delete']);
    $redirect = 'admin_reports.php';
    if (!empty($q)) $redirect .= '?' . http_build_query($q);
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản trị báo cáo</title>
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
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold">
        <i class="fa-solid fa-user-shield me-2"></i>
        <?php echo ($role === 'admin_tong') ? 'Admin tổng' : 'Admin ban'; ?>: <?php echo htmlspecialchars($admin_name); ?>
    </span>
    <div class="d-flex">
      <a href="admin_report_form.php" class="btn btn-success me-2"><i class="fa-solid fa-plus"></i> Tạo báo cáo</a>
      <a href="logout.php" class="btn btn-outline-light"><i class="fa-solid fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
  </div>
</nav>
<div class="container py-2">
<?php if ($role === 'admin_tong'): ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php if (!isset($_GET['tab']) || $_GET['tab']!=='users') echo 'active'; ?>" href="admin_reports.php">Báo cáo</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php if (isset($_GET['tab']) && $_GET['tab']==='users') echo 'active'; ?>" href="admin_reports.php?tab=users">Duyệt người dùng mới</a>
  </li>
</ul>
<?php endif; ?>
<?php if (isset($_GET['tab']) && $_GET['tab']==='users'): ?>
<div class="card shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="fa-solid fa-user-clock me-2"></i>Người dùng chờ duyệt</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Ngày đăng ký</th>
            <th class="text-center">Hành động</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($u = $pending_users->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['created_at']; ?></td>
            <td class="text-center">
              <a href="admin_reports.php?tab=users&approve_user=<?php echo $u['id']; ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Duyệt</a>
              <a href="admin_reports.php?tab=users&delete_user=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa người dùng này?');"><i class="fa-solid fa-trash"></i> Xóa</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card card-filter shadow-sm p-3">
                <form class="row g-3" method="get">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Người gửi</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <?php $users->data_seek(0); while ($u = $users->fetch_assoc()): ?>
                                <option value="<?php echo $u['id']; ?>" <?php if (!empty($_GET['user_id']) && $_GET['user_id'] == $u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
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
                        <a href="admin_reports.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom-0 pb-0">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-table-list me-2"></i>
                <?php echo ($role === 'admin_tong') ? 'Danh sách tất cả báo cáo' : 'Danh sách báo cáo của ban mình'; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Người gửi</th>
                            <th>Email</th>
                            <th>Tiêu đề</th>
                            <th>Nội dung</th>
                            <th>Ngày tạo</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['name']); ?>
                                <?php if ($row['role'] === 'admin_tong'): ?>
                                    <span class="badge badge-admin ms-1">Admin tổng</span>
                                <?php elseif ($row['role'] === 'admin_ban'): ?>
                                    <span class="badge badge-admin ms-1">Admin ban</span>
                                <?php else: ?>
                                    <span class="badge badge-user ms-1">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="cell-content-limit" title="<?php echo htmlspecialchars($row['content']); ?>"><?php echo htmlspecialchars(mb_strimwidth($row['content'], 0, 100, '...')); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="view_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Xem"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($role === 'admin_tong' || ($role === 'admin_ban' && $row['user_id'] != $_SESSION['user_id']) || ($row['user_id'] == $_SESSION['user_id'])): ?>
                                        <a href="edit_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-danger" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                                    <?php endif; ?>
                                </div>
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
<script>
function confirmDelete(id) {
    if (confirm('Bạn có chắc chắn muốn xóa báo cáo này?')) {
        window.location = 'admin_reports.php?delete=' + id + '&<?php echo http_build_query($_GET); ?>';
    }
}
</script>
</body>
</html>