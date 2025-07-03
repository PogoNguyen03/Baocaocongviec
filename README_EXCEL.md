# Hướng dẫn cài đặt và sử dụng tính năng Xuất Excel

## 🧰 CÔNG CỤ DÙNG:
Sử dụng thư viện PHP PhpSpreadsheet của Microsoft, thay thế cho PHPExcel.

## 🔧 CÁC BƯỚC TRIỂN KHAI

### 1. ✅ CÀI PhpSpreadsheet

#### Cách 1: Dùng Composer (Khuyến nghị)
```bash
composer require phpoffice/phpspreadsheet
```

#### Cách 2: Nếu bạn không dùng Composer (Laragon thuần)
1. Tải PhpSpreadsheet từ: https://github.com/PHPOffice/PhpSpreadsheet/releases
2. Giải nén vào thư mục `vendor/phpoffice/phpspreadsheet/`
3. Tạo file `vendor/autoload.php` với nội dung:
```php
<?php
require_once __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require_once __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';
require_once __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Style/Alignment.php';
require_once __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Style/Border.php';
require_once __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Style/Fill.php';
```

### 2. 📁 FILE export_excel.php
File đã được tạo với các tính năng:

#### 🔐 Bảo mật:
- Chỉ cho đăng nhập rồi mới export
- Phân quyền theo role người dùng

#### 📊 Định dạng Excel theo vai trò:

**👤 User:**
- Tiêu đề: "BÁO CÁO CÔNG VIỆC - [Tên] - [Ban]"
- Cột: Tiêu đề, Nội dung, Ngày giờ báo cáo
- Dữ liệu: Chỉ báo cáo của user đó

**👥 Nhóm trưởng/Quản lý:**
- Tiêu đề: "BÁO CÁO CÔNG VIỆC - [Tên] - [Chức vụ] - [Ban]"
- Cột: Tên người gửi, Tiêu đề, Nội dung, Ngày tạo
- Dữ liệu: Báo cáo của user trong ban mình

**👑 Admin:**
- Tiêu đề: "BÁO CÁO CÔNG VIỆC - [Tên] - Admin - Tất cả ban"
- Cột: Tên người gửi, Chức vụ, Ban, Tiêu đề, Nội dung, Ngày tạo
- Dữ liệu: Tất cả báo cáo trong hệ thống

#### 🎨 Tính năng Excel:
- Tiêu đề đẹp với màu nền
- Header có màu nền khác biệt
- Border cho toàn bộ bảng
- Tự động điều chỉnh độ rộng cột
- Wrap text cho nội dung dài
- Tên file: `baocao_[role]_[timestamp].xlsx`

### 3. 📌 NÚT "Xuất Excel" ĐÃ THÊM VÀO:
- ✅ Trang chính (index.php)
- ✅ Trang quản trị (admin_reports.php)

### 4. 🛡️ Bảo mật bổ sung:
- Kiểm tra session trước khi export
- Phân quyền theo role
- Chỉ export dữ liệu được phép

### 5. 🔄 Có thể mở rộng thêm:
- Filter theo ngày/tháng
- Export theo ban cụ thể
- Thêm biểu đồ thống kê
- Export PDF

## 🚀 Sử dụng:
1. Đăng nhập vào hệ thống
2. Nhấn nút "📥 Xuất Excel" 
3. File Excel sẽ tự động tải về
4. Mở file để xem báo cáo

## ⚠️ Lưu ý:
- Cần cài đặt PhpSpreadsheet trước khi sử dụng
- Đảm bảo PHP có đủ quyền ghi file
- Kiểm tra memory limit nếu export nhiều dữ liệu 