<?php
require 'db.php';
require 'notification_helper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền admin
$stmt = $conn->prepare('SELECT role, name, department_id FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role, $admin_name, $admin_department_id);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin' && $role !== 'quanly' && $role !== 'nhomtruong') {
    echo '<div style="margin:40px auto;max-width:500px;" class="alert alert-danger">Bạn không có quyền truy cập trang này!</div>';
    exit;
}

// Xử lý gửi thông báo
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_type = $_POST['notification_type'];
    $custom_message = trim($_POST['custom_message']);
    $target_type = $_POST['target_type'];
    $department_id = intval($_POST['department_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $excludeUserId = $_SESSION['user_id'];
    
    switch ($notification_type) {
        case 'custom':
            if (empty($custom_message)) {
                $message = 'Vui lòng nhập nội dung thông báo!';
            } else {
                if ($target_type === 'all') {
                    if ($role === 'admin') {
                        $notification->sendToAll($custom_message, 'info', $excludeUserId, ['quanly','nhomtruong','user']);
                    } else {
                        $notification->sendToAll($custom_message, 'info', $excludeUserId);
                    }
                } elseif ($target_type === 'department') {
                    if ($role === 'quanly') {
                        $notification->sendToDepartment($custom_message, $department_id, 'info', $excludeUserId, ['nhomtruong','user']);
                    } else {
                        $notification->sendToDepartment($custom_message, $department_id, 'info', $excludeUserId);
                    }
                } elseif ($target_type === 'user') {
                    $notification->sendToUser($custom_message, $user_id, 'info', $excludeUserId);
                }
                $message = 'Thông báo đã được gửi thành công!';
            }
            break;
            
        case 'report_reminder':
            if ($target_type === 'all') {
                if ($role === 'admin') {
                    $notification->sendToAll('📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!', 'warning', $excludeUserId, ['quanly','nhomtruong','user']);
                } else {
                    $notification->sendToAll('📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!', 'warning', $excludeUserId);
                }
            } elseif ($target_type === 'department') {
                if ($role === 'quanly') {
                    $notification->sendToDepartment('📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!', $department_id, 'warning', $excludeUserId, ['nhomtruong','user']);
                } else {
                    $notification->sendToDepartment('📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!', $department_id, 'warning', $excludeUserId);
                }
            }
            $message = 'Nhắc nhở báo cáo đã được gửi!';
            break;
            
        case 'meeting_reminder':
            $meeting_msg = "📅 Nhắc nhở: Có cuộc họp quan trọng sắp diễn ra!";
            if ($target_type === 'all') {
                if ($role === 'admin') {
                    $notification->sendToAll($meeting_msg, 'warning', $excludeUserId, ['quanly','nhomtruong','user']);
                } else {
                    $notification->sendToAll($meeting_msg, 'warning', $excludeUserId);
                }
            } elseif ($target_type === 'department') {
                if ($role === 'quanly') {
                    $notification->sendToDepartment($meeting_msg, $department_id, 'warning', $excludeUserId, ['nhomtruong','user']);
                } else {
                    $notification->sendToDepartment($meeting_msg, $department_id, 'warning', $excludeUserId);
                }
            }
            $message = 'Nhắc nhở cuộc họp đã được gửi!';
            break;
            
        case 'deadline_reminder':
            $deadline_msg = "⏰ Nhắc nhở: Deadline báo cáo sắp đến!";
            if ($target_type === 'all') {
                if ($role === 'admin') {
                    $notification->sendToAll($deadline_msg, 'warning', $excludeUserId, ['quanly','nhomtruong','user']);
                } else {
                    $notification->sendToAll($deadline_msg, 'warning', $excludeUserId);
                }
            } elseif ($target_type === 'department') {
                if ($role === 'quanly') {
                    $notification->sendToDepartment($deadline_msg, $department_id, 'warning', $excludeUserId, ['nhomtruong','user']);
                } else {
                    $notification->sendToDepartment($deadline_msg, $department_id, 'warning', $excludeUserId);
                }
            }
            $message = 'Nhắc nhở deadline đã được gửi!';
            break;
    }
}

// Lấy danh sách ban
$departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
// Tạo mảng ánh xạ id => tên ban
$departments_arr = [];
if ($departments) {
    $departments->data_seek(0);
    while ($d = $departments->fetch_assoc()) {
        $departments_arr[$d['id']] = $d['name'];
    }
    // Reset lại con trỏ để dùng cho select phía dưới
    $departments->data_seek(0);
}

// Lấy danh sách user (theo quyền)
if ($role === 'admin') {
    $users = $conn->query('SELECT id, name, email FROM users ORDER BY name');
} else if ($role === 'quanly') {
    $users = $conn->prepare('SELECT id, name, email FROM users WHERE department_id = ? ORDER BY name');
    $users->bind_param('i', $admin_department_id);
    $users->execute();
    $users = $users->get_result();
} else if ($role === 'nhomtruong') {
    $users = $conn->prepare("SELECT id, name, email FROM users WHERE department_id = ? AND role = 'user' ORDER BY name");
    $users->bind_param('i', $admin_department_id);
    $users->execute();
    $users = $users->get_result();
}

// Lấy thống kê user online
$onlineUsers = $notification->getOnlineUsers();
$online_count = isset($onlineUsers['count']) ? $onlineUsers['count'] : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card { border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .notification-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .btn-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-custom:hover { background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%); }
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
            <i class="fa-solid fa-bell me-2"></i>
            Quản lý thông báo - <?php echo ($role === 'admin') ? 'Admin tổng' : ($role === 'quanly' ? 'Admin ban' : 'Nhóm trưởng'); ?>
        </span>
        <div class="d-flex">
            <a href="<?php echo ($role === 'admin' || $role === 'quanly') ? 'admin_reports.php' : 'index.php'; ?>" class="btn btn-outline-light me-2">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </a>
            <a href="logout.php" class="btn btn-outline-light">
                <i class="fa-solid fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Thống kê -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fa-solid fa-users fa-2x mb-2"></i>
                    <h4><?php echo $online_count; ?></h4>
                    <p class="mb-0">User đang online</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fa-solid fa-building fa-2x mb-2"></i>
                    <h4><?php echo $departments->num_rows; ?></h4>
                    <p class="mb-0">Tổng số ban</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fa-solid fa-user-shield fa-2x mb-2"></i>
                    <h4><?php echo $role === 'admin' ? 'Admin tổng' : ($role === 'quanly' ? 'Admin ban' : 'Nhóm trưởng'); ?></h4>
                    <p class="mb-0">Quyền hiện tại</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form gửi thông báo -->
        <div class="col-lg-8">
            <div class="card notification-card">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0"><i class="fa-solid fa-paper-plane me-2"></i>Gửi thông báo</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="notificationForm">
                        <!-- Loại thông báo -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Loại thông báo</label>
                            <select name="notification_type" class="form-select" id="notificationType">
                                <option value="custom">Thông báo tùy chỉnh</option>
                                <option value="report_reminder">Nhắc nhở báo cáo</option>
                                <option value="meeting_reminder">Nhắc nhở cuộc họp</option>
                                <option value="deadline_reminder">Nhắc nhở deadline</option>
                            </select>
                        </div>

                        <!-- Nội dung tùy chỉnh -->
                        <div class="mb-3" id="customMessageDiv">
                            <label class="form-label fw-semibold">Nội dung thông báo</label>
                            <textarea name="custom_message" class="form-control" rows="3" placeholder="Nhập nội dung thông báo..."></textarea>
                        </div>

                        <!-- Đối tượng nhận -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Đối tượng nhận</label>
                            <select name="target_type" class="form-select" id="targetType">
                                <?php if ($role === 'admin'): ?>
                                    <option value="all">Tất cả user</option>
                                    <option value="department">Theo ban</option>
                                    <option value="user">User cụ thể</option>
                                <?php elseif ($role === 'quanly'): ?>
                                    <option value="department">Ban của tôi</option>
                                    <option value="user">User cụ thể</option>
                                <?php elseif ($role === 'nhomtruong'): ?>
                                    <option value="user">User cụ thể</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Chọn ban -->
                        <div class="mb-3" id="departmentDiv" style="display: none;">
                            <label class="form-label fw-semibold">Chọn ban</label>
                            <select name="department_id" class="form-select">
                                <?php $departments->data_seek(0); while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php if ($role === 'quanly' && $dept['id'] == $admin_department_id) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Chọn user -->
                        <div class="mb-3" id="userDiv" style="display: none;">
                            <label class="form-label fw-semibold">Chọn user</label>
                            <select name="user_id" class="form-select">
                                <?php $users->data_seek(0); while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-light btn-custom">
                            <i class="fa-solid fa-paper-plane me-2"></i>Gửi thông báo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel điều khiển nhanh -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa-solid fa-bolt me-2"></i>Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" onclick="sendQuickNotification('report_reminder')">
                            <i class="fa-solid fa-bell me-2"></i>Nhắc nhở báo cáo
                        </button>
                        <button class="btn btn-info" onclick="sendQuickNotification('meeting_reminder')">
                            <i class="fa-solid fa-calendar me-2"></i>Nhắc nhở cuộc họp
                        </button>
                        <button class="btn btn-danger" onclick="sendQuickNotification('deadline_reminder')">
                            <i class="fa-solid fa-clock me-2"></i>Nhắc nhở deadline
                        </button>
                        <button class="btn btn-success" onclick="sendQuickNotification('welcome')">
                            <i class="fa-solid fa-hand-wave me-2"></i>Chào mừng
                        </button>
                    </div>
                    
                    <!-- Test âm thanh -->
                    <!-- <hr>
                    <h6 class="mb-2"><i class="fa-solid fa-volume-high me-2"></i>Test âm thanh</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="testSound('info')">
                            <i class="fa-solid fa-play me-2"></i>Test Info
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="testSound('success')">
                            <i class="fa-solid fa-play me-2"></i>Test Success
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="testSound('warning')">
                            <i class="fa-solid fa-play me-2"></i>Test Warning
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="testSound('error')">
                            <i class="fa-solid fa-play me-2"></i>Test Error
                        </button>
                    </div> -->
                </div>
            </div>

            <!-- User online -->
            <div class="card mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fa-solid fa-circle-dot me-2"></i>Người dùng online (<?php echo $online_count; ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($onlineUsers['users']) && count($onlineUsers['users']) > 0): ?>
                        <?php foreach ($onlineUsers['users'] as $user): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success rounded-circle" style="width: 8px; height: 8px; margin-right: 8px;"></div>
                                <div>
                                    <small class="fw-semibold"><?php echo htmlspecialchars($user['userName']); ?></small>
                                    <br><small class="text-muted">
                                    <?php
                                        $role_map = [
                                            'admin' => 'Admin',
                                            'quanly' => 'Quản lý',
                                            'nhomtruong' => 'Nhóm trưởng',
                                            'user' => 'Người dùng'
                                        ];
                                        echo isset($role_map[$user['role']]) ? $role_map[$user['role']] : $user['role'];
                                    ?> - Ban: <?php echo isset($departments_arr[$user['departmentId']]) ? htmlspecialchars($departments_arr[$user['departmentId']]) : 'Không xác định'; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Không có người dùng online</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="sounds/notification_sounds.js"></script>
<script src="notification.js"></script>
<script>
// Xử lý hiển thị/ẩn các trường theo loại thông báo
document.getElementById('notificationType').addEventListener('change', function() {
    const customDiv = document.getElementById('customMessageDiv');
    if (this.value === 'custom') {
        customDiv.style.display = 'block';
    } else {
        customDiv.style.display = 'none';
    }
});

// Xử lý hiển thị/ẩn các trường theo đối tượng nhận
document.getElementById('targetType').addEventListener('change', function() {
    const departmentDiv = document.getElementById('departmentDiv');
    const userDiv = document.getElementById('userDiv');
    
    departmentDiv.style.display = 'none';
    userDiv.style.display = 'none';
    
    if (this.value === 'department') {
        departmentDiv.style.display = 'block';
    } else if (this.value === 'user') {
        userDiv.style.display = 'block';
    }
});

// Gửi thông báo nhanh
function sendQuickNotification(type) {
    let message = '';
    let notificationType = 'info';
    switch(type) {
        case 'report_reminder':
            message = '📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!';
            notificationType = 'warning';
            break;
        case 'meeting_reminder':
            message = '📅 Nhắc nhở: Có cuộc họp quan trọng sắp diễn ra!';
            notificationType = 'warning';
            break;
        case 'deadline_reminder':
            message = '⏰ Nhắc nhở: Deadline báo cáo sắp đến!';
            notificationType = 'warning';
            break;
        case 'welcome':
            message = '👋 Chào mừng bạn đến với hệ thống báo cáo!';
            notificationType = 'success';
            break;
    }
    if (window.notificationClient && message) {
        window.notificationClient.sendNotification(message, notificationType);
        showSuccessMessage('Thông báo đã được gửi thành công!');
    }
}

// Hiển thị thông báo thành công
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = `
        top: 80px;
        right: 20px;
        z-index: 10001;
        min-width: 300px;
    `;
    alertDiv.innerHTML = `
        <i class="fa-solid fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Test âm thanh
// function testSound(type) {
//     if (window.notificationClient) {
//         window.notificationClient.playSound(type);
//         showSuccessMessage(`Đã phát âm thanh ${type}!`);
//     }
// }

// Auto refresh user online list
// setInterval(function() {
//     location.reload();
// }, 30000); // Refresh mỗi 30 giây
</script>
</body>
</html> 