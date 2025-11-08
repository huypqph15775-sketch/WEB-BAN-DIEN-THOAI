<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Điều hướng thông minh
if (isset($_SESSION['id_nguoidung']) && $_SESSION['capdo'] >= 3) {
    // Admin -> trang quản trị
    header('Location: admin/index.php');
} else {
    // Khách hàng -> trang chủ bán hàng
    header('Location: trangchu/index.php');
}
exit();
?>