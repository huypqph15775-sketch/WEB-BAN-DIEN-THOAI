<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function kiemTraDangNhap() {
    if (!isset($_SESSION['id_nguoidung'])) {
        header('Location: ../xacthuc/dangnhap.php?error=Vui lòng đăng nhập');
        exit();
    }
}

function kiemTraQuyen($capDoToiThieu) {
    kiemTraDangNhap();
    
    if ($_SESSION['capdo'] < $capDoToiThieu) {
        header('Location: ../index.php?error=Bạn không có quyền truy cập');
        exit();
    }
}
?>