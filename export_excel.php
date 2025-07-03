<?php
ob_clean();
require 'vendor/autoload.php';
require 'db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Sử dụng PhpSpreadsheet (cần cài đặt trước)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = $conn->prepare("SELECT role, name, department_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Không tìm thấy thông tin người dùng!");
}

$role = $user['role'];
$name = $user['name'];
$department_id = $user['department_id'];

// Lấy tên ban
$stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();
$department_name = $department ? $department['name'] : '';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thiết lập tiêu đề bảng tuỳ vai trò
switch ($role) {
    case 'user':
        $headers = ['Tiêu đề', 'Nội dung', 'Ngày giờ báo cáo'];
        $title = "BÁO CÁO CÔNG VIỆC - $name - $department_name";
        break;
    case 'nhomtruong':
    case 'quanly':
        $headers = ['Tên người gửi', 'Tiêu đề', 'Nội dung', 'Ngày tạo'];
        $title = "BÁO CÁO CÔNG VIỆC - $name - " . ($role === 'quanly' ? 'Quản lý' : 'Nhóm trưởng') . " - $department_name";
        break;
    case 'admin':
        $headers = ['Tên người gửi', 'Chức vụ', 'Ban', 'Tiêu đề', 'Nội dung', 'Ngày tạo'];
        $title = "BÁO CÁO CÔNG VIỆC - $name - Admin - Tất cả ban";
        break;
    default:
        die("Không rõ vai trò người dùng.");
}

// Thiết lập tiêu đề
$sheet->setCellValue('A1', $title);
$sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');

// Style cho tiêu đề
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
$sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');

// Ghi tiêu đề cột
$col = 1;
$row = 3;
foreach ($headers as $header) {
    $sheet->setCellValue(chr(64 + $col) . $row, $header);
    $col++;
}

// Style cho header
$headerRange = 'A' . $row . ':' . chr(64 + count($headers)) . $row;
$sheet->getStyle($headerRange)->getFont()->setBold(true);
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Lấy dữ liệu tuỳ theo role
$data = [];
if ($role === 'user') {
    // User chỉ xem báo cáo của mình
    $stmt = $conn->prepare("SELECT title, content, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
} else if ($role === 'nhomtruong' || $role === 'quanly') {
    // Nhóm trưởng/Quản lý xem báo cáo của user trong ban mình
    $stmt = $conn->prepare("
        SELECT u.name AS sender, r.title, r.content, r.created_at
        FROM reports r
        JOIN users u ON u.id = r.user_id
        WHERE r.department_id = ? AND u.role = 'user'
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $department_id);
} else if ($role === 'admin') {
    // Admin xem tất cả báo cáo
    $stmt = $conn->prepare("
        SELECT u.name AS sender, 
               CASE u.role 
                   WHEN 'admin' THEN 'Admin'
                   WHEN 'quanly' THEN 'Quản lý'
                   WHEN 'nhomtruong' THEN 'Nhóm trưởng'
                   ELSE 'User'
               END AS position,
               d.name AS department,
               r.title, r.content, r.created_at
        FROM reports r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN departments d ON u.department_id = d.id
        ORDER BY r.created_at DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

$row = 4;
while ($data = $result->fetch_assoc()) {
    $col = 1; // Start from column 1 (A)
    
    if ($role === 'user') {
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['title']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['content']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['created_at']);
    } else if ($role === 'nhomtruong' || $role === 'quanly') {
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['sender']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['title']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['content']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['created_at']);
    } else if ($role === 'admin') {
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['sender']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['position']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['department']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['title']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['content']);
        $sheet->setCellValue(chr(64 + $col++) . $row, $data['created_at']);
    }
    
    $row++;
}

// Style cho dữ liệu
$dataRange = 'A4:' . chr(64 + count($headers)) . ($row - 1);
$sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
$sheet->getStyle($dataRange)->getAlignment()->setWrapText(true);

// Border cho toàn bộ bảng
$tableRange = 'A3:' . chr(64 + count($headers)) . ($row - 1);
$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Tự động điều chỉnh độ rộng cột
foreach (range('A', chr(64 + count($headers))) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Tên file
$filename = "baocao_" . strtolower($role) . "_" . date('Ymd_His') . ".xlsx";

// Đảm bảo không có output nào trước khi gửi file
if (ob_get_length()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?> 