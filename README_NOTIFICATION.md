# 🔔 Hệ Thống Thông Báo Real-Time

## 📋 Tổng Quan

Hệ thống thông báo real-time sử dụng **Socket.IO** và **PHP** để gửi thông báo tức thì đến người dùng. Hỗ trợ gửi thông báo theo:
- **Tất cả user** (admin tổng)
- **Theo ban** (admin ban)
- **User cụ thể** (admin tổng)

## 🚀 Cài Đặt

### 1. Cài đặt Node.js dependencies
```bash
npm install express socket.io cors
```

### 2. Khởi động Socket.IO server
```bash
node server.js
```
Server sẽ chạy trên port 3000: `http://192.168.5.183:3000`

### 3. Cấu hình PHP
Đảm bảo PHP có extension `curl` để gửi HTTP requests.

## 📁 Cấu Trúc Files

```
├── server.js                 # Socket.IO server
├── notification_helper.php   # PHP helper class
├── notification.js          # Client-side JavaScript
├── notification_manager.php # Giao diện quản lý thông báo
├── test_notification.php    # Test cơ bản
└── test_notification_system.php # Test toàn diện
```

## 🎯 Tính Năng

### 1. Gửi Thông Báo
- **sendToAll()**: Gửi cho tất cả user
- **sendToDepartment()**: Gửi cho user trong ban cụ thể
- **sendToUser()**: Gửi cho user cụ thể

### 2. Loại Thông Báo
- **info**: Thông tin (màu xanh)
- **success**: Thành công (màu xanh lá)
- **warning**: Cảnh báo (màu cam)
- **error**: Lỗi (màu đỏ)

### 3. Thông Báo Tự Động
- **sendReportReminder()**: Nhắc nhở báo cáo
- **sendNewReportNotification()**: Báo cáo mới
- **sendNewUserNotification()**: User mới đăng ký
- **sendUserApprovedNotification()**: User được duyệt
- **sendSystemError()**: Lỗi hệ thống

### 4. 🔊 Âm Thanh Thông Báo
- **Âm thanh theo loại**: Mỗi loại thông báo có âm thanh riêng
- **Điều khiển âm thanh**: Nút bật/tắt âm thanh ở góc trái
- **Lưu trạng thái**: Nhớ cài đặt âm thanh qua localStorage
- **Âm lượng phù hợp**: 30% âm lượng mặc định
- **Test âm thanh**: Nút test để kiểm tra từng loại âm thanh

#### Các Loại Âm Thanh:
- **info**: Âm thanh thông tin nhẹ nhàng
- **success**: Âm thanh thành công vui vẻ
- **warning**: Âm thanh cảnh báo nghiêm túc
- **error**: Âm thanh lỗi nghiêm trọng
- **report_reminder**: Âm thanh nhắc nhở báo cáo
- **meeting_reminder**: Âm thanh nhắc nhở cuộc họp
- **deadline_reminder**: Âm thanh nhắc nhở deadline
- **welcome**: Âm thanh chào mừng

## 👥 Phân Quyền

### Admin Tổng (`admin_tong`)
- ✅ Gửi thông báo cho tất cả user
- ✅ Gửi thông báo theo ban
- ✅ Gửi thông báo cho user cụ thể
- ✅ Quản lý tất cả ban

### Admin Ban (`admin_ban`)
- ✅ Gửi thông báo cho tất cả user
- ✅ Gửi thông báo cho ban của mình
- ❌ Không thể gửi cho user cụ thể
- ❌ Không thể quản lý ban khác

### User Thường (`user`)
- ✅ Nhận thông báo
- ❌ Không thể gửi thông báo

## 🖥️ Giao Diện Quản Lý

### Trang Quản Lý Thông Báo (`notification_manager.php`)
- **Form gửi thông báo**: Chọn loại, nội dung, đối tượng
- **Thao tác nhanh**: Nút gửi thông báo nhanh
- **Test âm thanh**: Nút test từng loại âm thanh
- **User online**: Hiển thị danh sách user đang online
- **Thống kê**: Số user online, số ban, quyền hiện tại

### Các Nút Thao Tác Nhanh
- 🔔 **Nhắc nhở báo cáo**: "Hôm nay bạn chưa báo cáo công việc!"
- 📅 **Nhắc nhở cuộc họp**: "Có cuộc họp quan trọng sắp diễn ra!"
- ⏰ **Nhắc nhở deadline**: "Deadline báo cáo sắp đến!"
- 👋 **Chào mừng**: "Chào mừng bạn đến với hệ thống báo cáo!"

### 🔊 Điều Khiển Âm Thanh
- **Nút âm thanh**: Ở góc trái trên màn hình
- **Bật/Tắt**: Click để bật/tắt âm thanh
- **Lưu cài đặt**: Tự động nhớ trạng thái
- **Test âm thanh**: Nút test từng loại âm thanh

## 🔧 API Endpoints

### Socket.IO Server (`server.js`)
- `POST /notify`: Gửi thông báo từ PHP
- `GET /online-users`: Lấy danh sách user online
- `GET /health`: Kiểm tra trạng thái server

### Socket Events
- `join`: User tham gia room
- `disconnect`: User rời khỏi
- `admin_notification`: Admin gửi thông báo
- `notification`: Nhận thông báo

## 📱 Hiển Thị Thông Báo

### Vị Trí
- Góc phải trên của browser
- Fixed position, không ảnh hưởng layout

### Animation
- Slide in từ phải
- Auto hide sau 5 giây
- Click để đóng sớm

### Styling
- Màu sắc theo loại thông báo
- Icon emoji tương ứng
- Border left accent color
- Box shadow đẹp mắt

## 🧪 Testing

### 1. Test Cơ Bản
```bash
# Truy cập file test
http://your-domain/test_notification.php
```

### 2. Test Toàn Diện
```bash
# Truy cập file test đầy đủ
http://your-domain/test_notification_system.php
```

### 3. Test Manual
1. Mở browser và truy cập `index.php`
2. Mở trang quản lý thông báo `notification_manager.php`
3. Gửi thông báo test
4. Kiểm tra hiển thị trên browser
5. Test âm thanh bằng nút test âm thanh

### 4. Test Âm Thanh
- Click nút test âm thanh trong trang quản lý
- Sử dụng nút điều khiển âm thanh ở góc trái
- Kiểm tra âm thanh cho từng loại thông báo
- Test bật/tắt âm thanh

## 🔍 Debug & Troubleshooting

### 1. Kiểm Tra Server Status
```php
if ($notification->checkServerStatus()) {
    echo "✅ Server đang hoạt động";
} else {
    echo "❌ Server không hoạt động";
}
```

### 2. Kiểm Tra User Online
```php
$onlineUsers = $notification->getOnlineUsers();
echo "Số user online: " . $onlineUsers['count'];
```

### 3. Log Browser Console
- Mở Developer Tools (F12)
- Xem tab Console
- Kiểm tra Socket.IO connection logs

### 4. Lỗi Thường Gặp

#### Server không khởi động
```bash
# Kiểm tra port 3000 có bị chiếm không
netstat -an | grep 3000
# Kill process nếu cần
kill -9 <PID>
```

#### Thông báo không hiển thị
- Kiểm tra Socket.IO server đang chạy
- Kiểm tra IP address trong `notification.js`
- Kiểm tra CORS settings trong `server.js`

#### PHP không gửi được thông báo
- Kiểm tra extension curl
- Kiểm tra firewall
- Kiểm tra network connectivity

## 📊 Monitoring

### Server Logs
```bash
# Xem logs real-time
tail -f server.log

# Kiểm tra process
ps aux | grep node
```

### Database Monitoring
```sql
-- Kiểm tra user online
SELECT COUNT(*) FROM users WHERE last_activity > NOW() - INTERVAL 5 MINUTE;

-- Kiểm tra thông báo gần đây
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

## 🔒 Security

### CORS Configuration
```javascript
cors: { 
  origin: ["http://192.168.5.183", "http://192.168.5.183:80", "http://localhost"],
  methods: ["GET", "POST"]
}
```

### Authentication
- Kiểm tra session trước khi gửi thông báo
- Validate user permissions
- Sanitize input data

### Rate Limiting
- Giới hạn số thông báo gửi/phút
- Prevent spam notifications
- Monitor abuse patterns

## 🚀 Deployment

### Production Setup
1. **PM2 Process Manager**
```bash
npm install -g pm2
pm2 start server.js --name "notification-server"
pm2 startup
pm2 save
```

2. **Nginx Reverse Proxy**
```nginx
location /socket.io/ {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

3. **SSL Certificate**
```bash
# Let's Encrypt
certbot --nginx -d your-domain.com
```

### Environment Variables
```bash
# .env file
NODE_ENV=production
PORT=3000
SOCKET_URL=https://your-domain.com
```

## 📈 Performance

### Optimization Tips
- Sử dụng Redis cho session storage
- Implement connection pooling
- Monitor memory usage
- Use CDN cho static files

### Scaling
- Load balancing với multiple Socket.IO servers
- Redis adapter cho Socket.IO clustering
- Database connection pooling
- Caching strategies

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

MIT License - Xem file LICENSE để biết thêm chi tiết.

---

## 🎉 Kết Luận

Hệ thống thông báo real-time đã được hoàn thiện với đầy đủ tính năng:
- ✅ Gửi thông báo real-time
- ✅ Phân quyền theo role
- ✅ Giao diện quản lý đẹp mắt
- ✅ Testing tools đầy đủ
- ✅ Documentation chi tiết

Bây giờ admin có thể dễ dàng gửi nhắc nhở và thông báo cho user một cách hiệu quả! 🚀 