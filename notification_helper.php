<?php
/**
 * Notification Helper - Gửi thông báo real-time qua Socket.IO
 */

class NotificationHelper {
    private $socketUrl = 'http://192.168.5.183:3000';
    
    /**
     * Gửi thông báo tới tất cả user
     */
    public function sendToAll($message, $type = 'info') {
        return $this->sendNotification([
            'message' => $message,
            'type' => $type,
            'target' => 'all'
        ]);
    }
    
    /**
     * Gửi thông báo tới user trong ban cụ thể
     */
    public function sendToDepartment($message, $departmentId, $type = 'info') {
        return $this->sendNotification([
            'message' => $message,
            'type' => $type,
            'target' => 'department',
            'departmentId' => $departmentId
        ]);
    }
    
    /**
     * Gửi thông báo tới user cụ thể
     */
    public function sendToUser($message, $userId, $type = 'info') {
        return $this->sendNotification([
            'message' => $message,
            'type' => $type,
            'target' => 'user',
            'userId' => $userId
        ]);
    }
    
    /**
     * Gửi thông báo nhắc nhở báo cáo
     */
    public function sendReportReminder($departmentId = null) {
        $message = "📢 Nhắc nhở: Hôm nay bạn chưa báo cáo công việc!";
        
        if ($departmentId) {
            return $this->sendToDepartment($message, $departmentId, 'warning');
        } else {
            return $this->sendToAll($message, 'warning');
        }
    }
    
    /**
     * Gửi thông báo báo cáo mới
     */
    public function sendNewReportNotification($userName, $reportTitle, $departmentId = null) {
        $message = "📝 {$userName} vừa tạo báo cáo mới: {$reportTitle}";
        
        if ($departmentId) {
            return $this->sendToDepartment($message, $departmentId, 'info');
        } else {
            return $this->sendToAll($message, 'info');
        }
    }
    
    /**
     * Gửi thông báo user mới đăng ký
     */
    public function sendNewUserNotification($userName, $departmentName) {
        $message = "👤 {$userName} vừa đăng ký tài khoản mới (Ban: {$departmentName})";
        return $this->sendToAll($message, 'info');
    }
    
    /**
     * Gửi thông báo user được duyệt
     */
    public function sendUserApprovedNotification($userName) {
        $message = "✅ Tài khoản của {$userName} đã được admin duyệt!";
        return $this->sendToAll($message, 'success');
    }
    
    /**
     * Gửi thông báo lỗi hệ thống
     */
    public function sendSystemError($error) {
        $message = "❌ Lỗi hệ thống: {$error}";
        return $this->sendToAll($message, 'error');
    }
    
    /**
     * Gửi thông báo tùy chỉnh
     */
    private function sendNotification($data) {
        $url = $this->socketUrl . '/notify';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Notification error: " . $error);
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("Notification HTTP error: " . $httpCode);
            return false;
        }
        
        $result = json_decode($response, true);
        return $result && isset($result['status']) && $result['status'] === 'ok';
    }
    
    /**
     * Kiểm tra Socket.IO server có hoạt động không
     */
    public function checkServerStatus() {
        $url = $this->socketUrl . '/health';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result && isset($result['status']) && $result['status'] === 'ok';
        }
        
        return false;
    }
    
    /**
     * Lấy danh sách user online
     */
    public function getOnlineUsers() {
        $url = $this->socketUrl . '/online-users';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result ? $result : [];
        }
        
        return [];
    }
}

// Tạo instance global
$notification = new NotificationHelper();
?> 