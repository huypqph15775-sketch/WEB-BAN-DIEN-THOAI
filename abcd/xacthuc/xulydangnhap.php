<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../cauhinh/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tenDangNhap = $_POST['tenDangNhap'] ?? '';
    $matKhau = $_POST['matKhau'] ?? '';
    
    if (empty($tenDangNhap) || empty($matKhau)) {
        header('Location: dangnhap.php?error=Vui lòng điền đầy đủ thông tin');
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Truy vấn người dùng
        $query = "SELECT nd.*, pq.ten_phanquyen, pq.capdo 
                  FROM nguoidung nd 
                  JOIN phanquyen pq ON nd.id_phanquyen = pq.id_phanquyen 
                  WHERE nd.tendangnhap = :tendangnhap AND nd.trangthai = 'active'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':tendangnhap', $tenDangNhap);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $nguoiDung = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vì mật khẩu trong database đã được hash, nhưng để test tạm thời
            // Chúng ta sẽ so sánh trực tiếp với 'password' cho demo
            if ($matKhau === 'password' || password_verify($matKhau, $nguoiDung['matkhau'])) {
                // Sau khi xác thực mật khẩu đúng
                session_regenerate_id(true); // chống tấn công session fixation
                // Đăng nhập thành công
                $_SESSION['id_nguoidung'] = $nguoiDung['id_nguoidung'];
                $_SESSION['tendangnhap'] = $nguoiDung['tendangnhap'];
                $_SESSION['hoten'] = $nguoiDung['hoten'];
                $_SESSION['id_phanquyen'] = $nguoiDung['id_phanquyen'];
                $_SESSION['ten_phanquyen'] = $nguoiDung['ten_phanquyen'];
                $_SESSION['capdo'] = $nguoiDung['capdo'];
                
                // Chuyển hướng dựa trên phân quyền
                if ($nguoiDung['capdo'] >= 3) {
                    header('Location: ../admin/index.php?success=Đăng nhập quản trị thành công');
                } else {
                    header('Location: ../trangchu/index.php?success=Đăng nhập thành công');
                }
                exit();
            } else {
                header('Location: dangnhap.php?error=Mật khẩu không chính xác');
                exit();
            }
        } else {
            header('Location: dangnhap.php?error=Tên đăng nhập không tồn tại');
            exit();
        }
        
    } catch (PDOException $e) {
        header('Location: dangnhap.php?error=Lỗi hệ thống: ' . $e->getMessage());
        exit();
    }
} else {
    header('Location: dangnhap.php');
    exit();
}
?>