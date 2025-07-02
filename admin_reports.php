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
if ($role !== 'admin' && $role !== 'quanly') {
    echo '<div style="margin:40px auto;max-width:500px;" class="alert alert-danger">Bạn không có quyền truy cập trang này!</div>';
    exit;
}
// Lấy danh sách user cho bộ lọc (chỉ user của ban mình nếu là quanly)
if ($role === 'quanly') {
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
if ($role === 'admin') {
    // Admin xem tất cả báo cáo
    // Không cần lọc theo department hay role
} else if ($role === 'quanly') {
    // Quản lý chỉ xem báo cáo của user và nhomtruong trong ban mình
    $where[] = 'reports.department_id = ?';
    $params[] = $admin_department_id;
    $types .= 'i';
    $where[] = '(users.role = \'user\' OR users.role = \'nhomtruong\')';
} else if ($role === 'nhomtruong') {
    // Nhóm trưởng chỉ xem báo cáo của user trong ban mình
    $where[] = 'reports.department_id = ?';
    $params[] = $admin_department_id;
    $types .= 'i';
    $where[] = 'users.role = \'user\'';
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
$sql = "SELECT reports.id, users.name, users.email, users.role, reports.title, reports.content, reports.user_id, reports.created_at, departments.name AS department_name FROM reports JOIN users ON reports.user_id = users.id LEFT JOIN departments ON users.department_id = departments.id $where_sql ORDER BY reports.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách người dùng chờ duyệt
$pending_users = null;
// Xác định tab hiện tại
$tab = 'reports';
if (isset($_GET['tab']) && in_array($_GET['tab'], ['users', 'roles'])) {
    $tab = $_GET['tab'];
}

// Xử lý cập nhật ban (chỉ admin, phải đặt trước khi xuất HTML)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_department_user_id'], $_POST['new_department_id']) && $role === 'admin'
) {
    $target_id = intval($_POST['change_department_user_id']);
    $new_department_id = intval($_POST['new_department_id']);
    $stmt = $conn->prepare('UPDATE users SET department_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $new_department_id, $target_id);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_reports.php?tab=roles');
    exit;
}

// Xử lý cập nhật role (phải đặt trước khi xuất HTML)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role_user_id'], $_POST['new_role'])
) {
    $target_id = intval($_POST['change_role_user_id']);
    $new_role = $_POST['new_role'];
    $can_change = false;
    if ($role === 'admin' && $target_id != $_SESSION['user_id']) {
        $can_change = true;
    } else if ($role === 'quanly' && $new_role !== 'admin' && $target_id != $_SESSION['user_id']) {
        // Chỉ đổi cho user/nhomtruong trong ban mình
        $stmt = $conn->prepare('SELECT department_id FROM users WHERE id = ?');
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        $stmt->bind_result($target_dept);
        $stmt->fetch();
        $stmt->close();
        if ($target_dept == $admin_department_id) {
            $can_change = true;
        }
    }
    if ($can_change) {
        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $new_role, $target_id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_reports.php?tab=roles');
        exit;
    }
}
// Xử lý duyệt user và xóa user (phải đặt trước khi xuất HTML)
if (isset($_GET['approve_user']) && ($role === 'admin' || $role === 'quanly' || $role === 'nhomtruong')) {
    $uid = intval($_GET['approve_user']);
    $stmt = $conn->prepare('SELECT role, department_id FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($pending_role, $pending_dept);
    $stmt->fetch();
    $stmt->close();
    $can_approve = false;
    if ($role === 'admin') {
        $can_approve = true;
    } else if ($role === 'quanly' && $pending_dept == $admin_department_id && ($pending_role === 'user' || $pending_role === 'nhomtruong')) {
        $can_approve = true;
    } else if ($role === 'nhomtruong' && $pending_dept == $admin_department_id && $pending_role === 'user') {
        $can_approve = true;
    }
    if ($can_approve) {
        $stmt = $conn->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: admin_reports.php?tab=users');
    exit;
}
if (isset($_GET['delete_user']) && ($role === 'admin' || $role === 'quanly' || $role === 'nhomtruong')) {
    $uid = intval($_GET['delete_user']);
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_reports.php?tab=users');
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
<body class="bg-light"
      data-user-id="<?php echo $_SESSION['user_id']; ?>"
      data-user-name="<?php echo htmlspecialchars($admin_name); ?>"
      data-user-role="<?php echo $role; ?>"
      data-department-id="<?php echo $admin_department_id; ?>">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold">
        <i class="fa-solid fa-user-shield me-2"></i>
        <?php
            $role_display = [
                'admin' => 'Admin',
                'quanly' => 'Quản lý',
                'nhomtruong' => 'Nhóm trưởng',
            ];
            echo isset($role_display[$role]) ? $role_display[$role] : 'Admin ban';
        ?>: <?php echo htmlspecialchars($admin_name); ?>
    </span>
    <div class="d-flex">
      <a href="notification_manager.php" class="btn btn-warning me-2">
        <i class="fa-solid fa-bell"></i> Quản lý thông báo
      </a>
      <a href="admin_report_form.php" class="btn btn-success me-2"><i class="fa-solid fa-plus"></i> Tạo báo cáo</a>
      <a href="logout.php" class="btn btn-outline-light"><i class="fa-solid fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
  </div>
</nav>
<div class="container py-2">
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php if ($tab === 'reports') echo 'active'; ?>" href="admin_reports.php">Báo cáo</a>
  </li>
  <?php if ($role === 'admin' || $role === 'quanly' || $role === 'nhomtruong'): ?>
  <li class="nav-item">
    <a class="nav-link <?php if ($tab === 'users') echo 'active'; ?>" href="admin_reports.php?tab=users">Duyệt người dùng mới</a>
  </li>
  <?php endif; ?>
  <?php if ($role === 'admin' || $role === 'quanly' || $role === 'nhomtruong'): ?>
  <li class="nav-item">
    <a class="nav-link <?php if ($tab === 'roles') echo 'active'; ?>" href="admin_reports.php?tab=roles">Người dùng</a>
  </li>
  <?php endif; ?>
</ul>
<?php if ($tab === 'users'): ?>
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
        <?php if ($pending_users): while ($u = $pending_users->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['created_at']; ?></td>
            <td class="text-center">
              <?php
              $can_approve = false;
              if ($role === 'admin') {
                  $can_approve = true;
              } else if ($role === 'quanly' && $u['department_id'] == $admin_department_id && ($u['role'] === 'user' || $u['role'] === 'nhomtruong')) {
                  $can_approve = true;
              } else if ($role === 'nhomtruong' && $u['department_id'] == $admin_department_id && $u['role'] === 'user') {
                  $can_approve = true;
              }
              ?>
              <?php if ($can_approve): ?>
              <a href="admin_reports.php?tab=users&approve_user=<?php echo $u['id']; ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Duyệt</a>
              <a href="admin_reports.php?tab=users&delete_user=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa người dùng này?');"><i class="fa-solid fa-trash"></i> Xóa</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php elseif ($tab === 'roles'): ?>
<!-- NGƯỜI DÙNG (quản lý role và ban) -->
<div class="card shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="fa-solid fa-users-gear me-2"></i>Người dùng</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Ban</th>
            <th>Quyền hiện tại</th>
            <th class="text-center">Thay đổi quyền</th>
            <!-- <?php if ($role === 'admin'): ?><th class="text-center">Thay đổi ban</th><?php endif; ?> -->
          </tr>
        </thead>
        <tbody>
        <?php
        // Lấy danh sách ban
        $all_departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
        $departments_arr = [];
        while ($d = $all_departments->fetch_assoc()) {
            $departments_arr[$d['id']] = $d['name'];
        }
        // Lấy danh sách user theo quyền
        $role_users = null;
        if ($role === 'admin') {
            $role_users = $conn->query("SELECT u.id, u.name, u.email, u.role, u.department_id, d.name AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.name");
        } else if ($role === 'quanly') {
            $role_users = $conn->prepare("SELECT u.id, u.name, u.email, u.role, u.department_id, d.name AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.department_id = ? AND u.id != ? AND u.role != 'admin' ORDER BY u.name");
            $role_users->bind_param('ii', $admin_department_id, $_SESSION['user_id']);
            $role_users->execute();
            $role_users = $role_users->get_result();
        } else if ($role === 'nhomtruong') {
            $role_users = $conn->prepare("SELECT u.id, u.name, u.email, u.role, u.department_id, d.name AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.department_id = ? AND u.role = 'user' ORDER BY u.name");
            $role_users->bind_param('i', $admin_department_id);
            $role_users->execute();
            $role_users = $role_users->get_result();
        }
        $role_options = [
            'admin' => 'Admin',
            'quanly' => 'Quản lý',
            'nhomtruong' => 'Nhóm trưởng',
            'user' => 'Người dùng'
        ];
        ?>
        <?php if ($role_users): while ($u = $role_users->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
              <?php if ($role === 'admin'): ?>
                <form method="post" style="display:inline-block;min-width:120px;">
                  <input type="hidden" name="change_department_user_id" value="<?php echo $u['id']; ?>">
                  <select name="new_department_id" class="form-select form-select-sm d-inline w-auto" style="min-width:100px;display:inline-block;">
                    <?php foreach ($departments_arr as $dept_id => $dept_name): ?>
                      <option value="<?php echo $dept_id; ?>" <?php if ($u['department_id'] == $dept_id) echo 'selected'; ?>><?php echo htmlspecialchars($dept_name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-secondary btn-sm ms-1">Cập nhật</button>
                </form>
              <?php else: ?>
                <?php echo htmlspecialchars($u['department_name']); ?>
              <?php endif; ?>
            </td>
            <td><?php echo $role_options[$u['role']]; ?></td>
            <td class="text-center">
              <?php
              $can_change = false;
              if ($role === 'admin' && $u['id'] != $_SESSION['user_id']) {
                  $can_change = true;
              } else if ($role === 'quanly' && $u['role'] !== 'admin' && $u['id'] != $_SESSION['user_id']) {
                  $can_change = true;
              }
              ?>
              <?php if ($can_change): ?>
              <form method="post" style="display:inline-block;min-width:120px;">
                <input type="hidden" name="change_role_user_id" value="<?php echo $u['id']; ?>">
                <select name="new_role" class="form-select form-select-sm d-inline w-auto" style="min-width:100px;display:inline-block;">
                  <?php foreach ($role_options as $k => $v): ?>
                    <?php
                      if ($role === 'admin') {
                          // admin cấp mọi quyền
                          echo '<option value="'.$k.'"'.($u['role'] === $k ? ' selected' : '').'>'.$v.'</option>';
                      } elseif ($role === 'quanly' && in_array($k, ['nhomtruong', 'user'])) {
                          // quản lý chỉ cấp nhóm trưởng hoặc user
                          echo '<option value="'.$k.'"'.($u['role'] === $k ? ' selected' : '').'>'.$v.'</option>';
                      } elseif ($role === 'nhomtruong' && $k === 'user') {
                          // nhóm trưởng chỉ cấp user
                          echo '<option value="'.$k.'"'.($u['role'] === $k ? ' selected' : '').'>'.$v.'</option>';
                      }
                    ?>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm ms-1">Cập nhật</button>
              </form>
              <?php else: ?>
                <span class="text-muted">Không thể đổi</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php elseif ($tab === 'reports'): ?>
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
                <?php echo ($role === 'admin') ? 'Danh sách tất cả báo cáo' : 'Danh sách báo cáo của ban mình'; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Người gửi</th>
                            <th>Ban</th>
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
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="badge badge-admin ms-1">Admin</span>
                                <?php elseif ($row['role'] === 'quanly'): ?>
                                    <span class="badge badge-admin ms-1">Quản lý</span>
                                <?php elseif ($row['role'] === 'nhomtruong'): ?>
                                    <span class="badge badge-admin ms-1">Nhóm trưởng</span>
                                <?php else: ?>
                                    <span class="badge badge-user ms-1">Người dùng</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="cell-content-limit" title="<?php echo htmlspecialchars($row['content']); ?>"><?php echo htmlspecialchars(mb_strimwidth($row['content'], 0, 100, '...')); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="view_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="Xem"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($role === 'admin' || $row['user_id'] == $_SESSION['user_id']): ?>
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
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
function confirmDelete(id) {
    if (confirm('Bạn có chắc chắn muốn xóa báo cáo này?')) {
        window.location = 'admin_reports.php?delete=' + id + '&<?php echo http_build_query($_GET); ?>';
    }
}
</script>
<!-- Notification system -->
<script src="sounds/notification_sounds.js"></script>
<script src="notification.js"></script>
</body>
</html>