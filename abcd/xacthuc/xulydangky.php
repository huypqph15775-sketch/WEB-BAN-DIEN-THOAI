<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../cauhinh/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hoTen = $_POST['hoTen'] ?? '';
    $tenDangNhap = $_POST['tenDangNhap'] ?? '';
    $email = $_POST['email'] ?? '';
    $soDienThoai = $_POST['soDienThoai'] ?? '';
    $matKhau = $_POST['matKhau'] ?? '';
    $gioiTinh = $_POST['gioiTinh'] ?? '';
    $ngaySinh = $_POST['ngaySinh'] ?? '';
    
    // Kiểm tra dữ liệu
    if (empty($hoTen) || empty($tenDangNhap) || empty($email) || empty($matKhau)) {
        header('Location: dangky.php?error=Vui lòng điền đầy đủ thông tin bắt buộc');
        exit();
    }
    
    if (strlen($matKhau) < 6) {
        header('Location: dangky.php?error=Mật khẩu phải có ít nhất 6 ký tự');
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Kiểm tra tên đăng nhập đã tồn tại
        $checkUserQuery = "SELECT id_nguoidung FROM nguoidung WHERE tendangnhap = ?";
        $checkUserStmt = $db->prepare($checkUserQuery);
        $checkUserStmt->execute([$tenDangNhap]);
        
        if ($checkUserStmt->rowCount() > 0) {
            header('Location: dangky.php?error=Tên đăng nhập đã tồn tại');
            exit();
        }
        
        // Kiểm tra email đã tồn tại
        $checkEmailQuery = "SELECT id_nguoidung FROM nguoidung WHERE email = ?";
        $checkEmailStmt = $db->prepare($checkEmailQuery);
        $checkEmailStmt->execute([$email]);
        
        if ($checkEmailStmt->rowCount() > 0) {
            header('Location: dangky.php?error=Email đã được sử dụng');
            exit();
        }
        
        // Hash mật khẩu
        $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);
        
        // Thêm người dùng mới (mặc định là khách hàng - id_phanquyen = 6)
        $insertQuery = "INSERT INTO nguoidung (id_phanquyen, tendangnhap, matkhau, hoten, email, sodienthoai, gioitinh, ngaysinh) 
                       VALUES (6, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$tenDangNhap, $hashedPassword, $hoTen, $email, $soDienThoai, $gioiTinh, $ngaySinh]);
        
        if ($insertStmt->rowCount() > 0) {
            header('Location: dangnhap.php?success=Đăng ký thành công! Vui lòng đăng nhập');
            exit();
        } else {
            header('Location: dangky.php?error=Có lỗi xảy ra khi đăng ký');
            exit();
        }
        
    } catch (PDOException $e) {
        header('Location: dangky.php?error=Lỗi hệ thống: ' . $e->getMessage());
        exit();
    }
} else {
    header('Location: dangky.php');
    exit();
}
?>