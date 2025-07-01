<?php
require 'notification_helper.php';

echo "<h2>Test Notification System</h2>";

// Test 1: Kiểm tra server status
echo "<h3>1. Kiểm tra server status:</h3>";
if ($notification->checkServerStatus()) {
    echo "✅ Server đang hoạt động<br>";
} else {
    echo "❌ Server không hoạt động<br>";
}

// Test 2: Lấy danh sách user online
echo "<h3>2. User online:</h3>";
$onlineUsers = $notification->getOnlineUsers();
if (isset($onlineUsers['users'])) {
    echo "Số user online: " . $onlineUsers['count'] . "<br>";
    foreach ($onlineUsers['users'] as $user) {
        echo "- {$user['userName']} (Role: {$user['role']}, Dept: {$user['departmentId']})<br>";
    }
} else {
    echo "Không có user online<br>";
}

// Test 3: Gửi thông báo test
echo "<h3>3. Gửi thông báo test:</h3>";
if ($notification->sendToAll("🧪 Test notification từ PHP - " . date('H:i:s'), "info")) {
    echo "✅ Thông báo đã gửi thành công!<br>";
} else {
    echo "❌ Lỗi khi gửi thông báo<br>";
}

// Test 4: Gửi thông báo cho ban IT
echo "<h3>4. Gửi thông báo cho ban IT:</h3>";
if ($notification->sendToDepartment("📢 Nhắc nhở báo cáo cho ban IT!", 1, "warning")) {
    echo "✅ Thông báo cho ban IT đã gửi!<br>";
} else {
    echo "❌ Lỗi khi gửi thông báo cho ban IT<br>";
}

// Test 5: Gửi thông báo cho user cụ thể
echo "<h3>5. Gửi thông báo cho user ID 10:</h3>";
if ($notification->sendToUser("👋 Chào bạn tran văn dui!", 10, "success")) {
    echo "✅ Thông báo cho user đã gửi!<br>";
} else {
    echo "❌ Lỗi khi gửi thông báo cho user<br>";
}

echo "<br><strong>Kiểm tra thông báo trên browser!</strong>";
?> 