<?php
session_start();
include_once '../cauhinh/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_nguoidung'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['id_nguoidung'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($action) {
        case 'update_profile':
            $hoten = $_POST['hoten'] ?? '';
            $email = $_POST['email'] ?? '';
            $sodienthoai = $_POST['sodienthoai'] ?? '';
            $gioitinh = $_POST['gioitinh'] ?? '';
            $ngaysinh = $_POST['ngaysinh'] ?? '';
            
            if (empty($hoten)) {
                echo json_encode(['success' => false, 'message' => 'Họ tên không được để trống']);
                exit;
            }
            
            // Kiểm tra email trùng
            if (!empty($email)) {
                $checkEmailQuery = "SELECT id_nguoidung FROM nguoidung WHERE email = ? AND id_nguoidung != ?";
                $checkEmailStmt = $db->prepare($checkEmailQuery);
                $checkEmailStmt->execute([$email, $user_id]);
                
                if ($checkEmailStmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
                    exit;
                }
            }
            
            // Cập nhật thông tin
            $updateQuery = "UPDATE nguoidung SET 
                            hoten = ?, 
                            email = ?, 
                            sodienthoai = ?, 
                            gioitinh = ?, 
                            ngaysinh = ?,
                            ngay_capnhat = NOW()
                          WHERE id_nguoidung = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$hoten, $email, $sodienthoai, $gioitinh, $ngaysinh, $user_id]);
            
            // Cập nhật session
            $_SESSION['hoten'] = $hoten;
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
                exit;
            }
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
                exit;
            }
            
            // Lấy mật khẩu hiện tại
            $getPasswordQuery = "SELECT matkhau FROM nguoidung WHERE id_nguoidung = ?";
            $getPasswordStmt = $db->prepare($getPasswordQuery);
            $getPasswordStmt->execute([$user_id]);
            $user = $getPasswordStmt->fetch(PDO::FETCH_ASSOC);
            
            // Kiểm tra mật khẩu hiện tại (cho demo, mật khẩu mặc định là 'password')
            if ($current_password !== 'password' && !password_verify($current_password, $user['matkhau'])) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác']);
                exit;
            }
            
            // Hash mật khẩu mới
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Cập nhật mật khẩu
            $updatePasswordQuery = "UPDATE nguoidung SET matkhau = ?, ngay_capnhat = NOW() WHERE id_nguoidung = ?";
            $updatePasswordStmt = $db->prepare($updatePasswordQuery);
            $updatePasswordStmt->execute([$hashedPassword, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>