-- Cập nhật database từ cấu trúc cũ sang cấu trúc mới
-- Chạy từng lệnh một để đảm bảo an toàn

-- 1. Tạo bảng departments nếu chưa có
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Thêm dữ liệu mẫu cho departments
INSERT IGNORE INTO `departments` (`name`) VALUES 
('ban hr'), 
('ban SEO'), 
('ban IT');

-- 3. Cập nhật dữ liệu role cũ trước khi thay đổi cấu trúc
-- Chuyển 2 tài khoản admin cụ thể
UPDATE `users` SET `role` = 'admin' WHERE `email` = 'arkhip04122003@gmail.com';
UPDATE `users` SET `role` = 'admin' WHERE `email` = 'nguyencanhphong135@gmail.com';
-- Chuyển các tài khoản admin khác thành user
UPDATE `users` SET `role` = 'user' WHERE `role` NOT IN ('admin', 'quanly', 'nhomtruong', 'user');

-- 5. Cập nhật trường role trong bảng users
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('admin', 'quanly', 'nhomtruong', 'user') NOT NULL DEFAULT 'user';

-- 6. Thêm trường department_id vào bảng reports
ALTER TABLE `reports` 
ADD COLUMN `department_id` INT DEFAULT NULL AFTER `updated_at`,
ADD CONSTRAINT `fk_reports_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`);

-- 7. Gán tất cả user hiện tại vào ban IT (id = 3)
UPDATE `users` SET `department_id` = (SELECT `id` FROM `departments` WHERE `name` = 'ban IT') WHERE `department_id` IS NULL;

-- 8. Cập nhật department_id cho các báo cáo hiện tại
UPDATE `reports` r 
JOIN `users` u ON r.user_id = u.id 
SET r.department_id = u.department_id 
WHERE r.department_id IS NULL;

-- 9. Kiểm tra kết quả
SELECT 'Cập nhật hoàn tất!' as status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_reports FROM reports;
SELECT COUNT(*) as total_departments FROM departments;

-- 10. Kiểm tra quyền của các tài khoản admin
SELECT email, role, department_id FROM users WHERE role IN ('admin', 'quanly');

-- Ví dụ: Gán user có id = 5 làm nhóm trưởng
UPDATE users SET role = 'nhomtruong' WHERE id = 5; 