<?php
require 'notification_helper.php';
require 'db.php';

echo "<h1>🧪 Test Hệ Thống Thông Báo Toàn Diện</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
    .btn-info { background: #17a2b8; color: white; }
    .btn-danger { background: #dc3545; color: white; }
</style>";

// Test 1: Kiểm tra server status
echo "<div class='test-section'>";
echo "<h3>1. 🔍 Kiểm tra server status:</h3>";
if ($notification->checkServerStatus()) {
    echo "<div class='success'>✅ Server đang hoạt động</div>";
} else {
    echo "<div class='error'>❌ Server không hoạt động</div>";
}
echo "</div>";

// Test 2: Lấy danh sách user online
echo "<div class='test-section'>";
echo "<h3>2. 👥 User online:</h3>";
$onlineUsers = $notification->getOnlineUsers();
if (isset($onlineUsers['users'])) {
    echo "<div class='info'>Số user online: " . $onlineUsers['count'] . "</div>";
    foreach ($onlineUsers['users'] as $user) {
        echo "- {$user['userName']} (Role: {$user['role']}, Dept: {$user['departmentId']})<br>";
    }
} else {
    echo "<div class='info'>Không có user online</div>";
}
echo "</div>";

// Test 3: Gửi thông báo test cơ bản
echo "<div class='test-section'>";
echo "<h3>3. 📢 Gửi thông báo test cơ bản:</h3>";
if ($notification->sendToAll("🧪 Test notification từ PHP - " . date('H:i:s'), "info")) {
    echo "<div class='success'>✅ Thông báo đã gửi thành công!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo</div>";
}
echo "</div>";

// Test 4: Gửi thông báo cho ban IT
echo "<div class='test-section'>";
echo "<h3>4. 🏢 Gửi thông báo cho ban IT:</h3>";
if ($notification->sendToDepartment("📢 Nhắc nhở báo cáo cho ban IT!", 1, "warning")) {
    echo "<div class='success'>✅ Thông báo cho ban IT đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo cho ban IT</div>";
}
echo "</div>";

// Test 5: Gửi thông báo cho user cụ thể
echo "<div class='test-section'>";
echo "<h3>5. 👤 Gửi thông báo cho user ID 10:</h3>";
if ($notification->sendToUser("👋 Chào bạn tran văn dui!", 10, "success")) {
    echo "<div class='success'>✅ Thông báo cho user đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo cho user</div>";
}
echo "</div>";

// Test 6: Gửi nhắc nhở báo cáo
echo "<div class='test-section'>";
echo "<h3>6. 📝 Gửi nhắc nhở báo cáo:</h3>";
if ($notification->sendReportReminder()) {
    echo "<div class='success'>✅ Nhắc nhở báo cáo đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi nhắc nhở báo cáo</div>";
}
echo "</div>";

// Test 7: Gửi nhắc nhở báo cáo cho ban cụ thể
echo "<div class='test-section'>";
echo "<h3>7. 📝 Gửi nhắc nhở báo cáo cho ban IT:</h3>";
if ($notification->sendReportReminder(1)) {
    echo "<div class='success'>✅ Nhắc nhở báo cáo cho ban IT đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi nhắc nhở báo cáo cho ban IT</div>";
}
echo "</div>";

// Test 8: Gửi thông báo báo cáo mới
echo "<div class='test-section'>";
echo "<h3>8. 📄 Gửi thông báo báo cáo mới:</h3>";
if ($notification->sendNewReportNotification("tran văn dui", "Báo cáo tuần mới", 1)) {
    echo "<div class='success'>✅ Thông báo báo cáo mới đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo báo cáo mới</div>";
}
echo "</div>";

// Test 9: Gửi thông báo user mới
echo "<div class='test-section'>";
echo "<h3>9. 👤 Gửi thông báo user mới đăng ký:</h3>";
if ($notification->sendNewUserNotification("Nguyễn Văn A", "Ban IT")) {
    echo "<div class='success'>✅ Thông báo user mới đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo user mới</div>";
}
echo "</div>";

// Test 10: Gửi thông báo user được duyệt
echo "<div class='test-section'>";
echo "<h3>10. ✅ Gửi thông báo user được duyệt:</h3>";
if ($notification->sendUserApprovedNotification("Nguyễn Văn A")) {
    echo "<div class='success'>✅ Thông báo user được duyệt đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo user được duyệt</div>";
}
echo "</div>";

// Test 11: Gửi thông báo lỗi hệ thống
echo "<div class='test-section'>";
echo "<h3>11. ❌ Gửi thông báo lỗi hệ thống:</h3>";
if ($notification->sendSystemError("Lỗi kết nối database")) {
    echo "<div class='success'>✅ Thông báo lỗi hệ thống đã gửi!</div>";
} else {
    echo "<div class='error'>❌ Lỗi khi gửi thông báo lỗi hệ thống</div>";
}
echo "</div>";

// Test 12: Kiểm tra database
echo "<div class='test-section'>";
echo "<h3>12. 🗄️ Kiểm tra database:</h3>";

// Lấy danh sách ban
$departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
echo "<strong>Danh sách ban:</strong><br>";
while ($dept = $departments->fetch_assoc()) {
    echo "- {$dept['name']} (ID: {$dept['id']})<br>";
}

// Lấy danh sách user
$users = $conn->query('SELECT id, name, email, role, department_id FROM users ORDER BY name LIMIT 5');
echo "<br><strong>Danh sách user (5 đầu):</strong><br>";
while ($user = $users->fetch_assoc()) {
    echo "- {$user['name']} ({$user['email']}) - Role: {$user['role']} - Dept: {$user['department_id']}<br>";
}

echo "</div>";

// Test 13: Hướng dẫn sử dụng
echo "<div class='test-section'>";
echo "<h3>13. 📚 Hướng dẫn sử dụng:</h3>";
echo "<div class='info'>";
echo "<strong>Để test hệ thống thông báo:</strong><br>";
echo "1. Mở browser và truy cập: <a href='index.php' target='_blank'>index.php</a><br>";
echo "2. Mở trang quản lý thông báo: <a href='notification_manager.php' target='_blank'>notification_manager.php</a><br>";
echo "3. Mở trang admin: <a href='admin_reports.php' target='_blank'>admin_reports.php</a><br>";
echo "4. Kiểm tra console browser để xem log thông báo<br>";
echo "5. Thông báo sẽ hiển thị ở góc phải trên của browser<br>";
echo "</div>";
echo "</div>";

// Test 14: Nút test nhanh
echo "<div class='test-section'>";
echo "<h3>14. ⚡ Test nhanh:</h3>";
echo "<button class='btn btn-success' onclick='sendTestNotification(\"success\")'>Test Success</button>";
echo "<button class='btn btn-warning' onclick='sendTestNotification(\"warning\")'>Test Warning</button>";
echo "<button class='btn btn-info' onclick='sendTestNotification(\"info\")'>Test Info</button>";
echo "<button class='btn btn-danger' onclick='sendTestNotification(\"error\")'>Test Error</button>";
echo "</div>";

// Test 15: Test âm thanh
echo "<div class='test-section'>";
echo "<h3>15. 🔊 Test âm thanh:</h3>";
echo "<button class='btn btn-outline-primary' onclick='testSound(\"info\")'>Test Info Sound</button>";
echo "<button class='btn btn-outline-success' onclick='testSound(\"success\")'>Test Success Sound</button>";
echo "<button class='btn btn-outline-warning' onclick='testSound(\"warning\")'>Test Warning Sound</button>";
echo "<button class='btn btn-outline-danger' onclick='testSound(\"error\")'>Test Error Sound</button>";
echo "<button class='btn btn-outline-info' onclick='testSound(\"report_reminder\")'>Test Report Reminder</button>";
echo "<button class='btn btn-outline-secondary' onclick='testSound(\"meeting_reminder\")'>Test Meeting Reminder</button>";
echo "<button class='btn btn-outline-dark' onclick='testSound(\"deadline_reminder\")'>Test Deadline Reminder</button>";
echo "<button class='btn btn-outline-light' onclick='testSound(\"welcome\")'>Test Welcome Sound</button>";
echo "</div>";

echo "<br><div class='info'>";
echo "<strong>💡 Lưu ý:</strong> Kiểm tra thông báo trên browser! Mở console để xem log chi tiết.";
echo "</div>";
?>

<script src="sounds/notification_sounds.js"></script>
<script src="notification.js"></script>
<script>
function sendTestNotification(type) {
    if (window.notificationClient) {
        const messages = {
            'success': '✅ Test thông báo thành công!',
            'warning': '⚠️ Test thông báo cảnh báo!',
            'info': 'ℹ️ Test thông báo thông tin!',
            'error': '❌ Test thông báo lỗi!'
        };
        
        window.notificationClient.sendNotification(messages[type], type);
        alert('Thông báo đã được gửi!');
    } else {
        alert('Notification client chưa sẵn sàng!');
    }
}

function testSound(type) {
    if (window.notificationClient) {
        window.notificationClient.playSound(type);
        alert(`Đã phát âm thanh ${type}!`);
    } else {
        alert('Notification client chưa sẵn sàng!');
    }
}
</script> 