<?php
session_start();

// Xóa tất cả session
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: dangnhap.php?success=Đăng xuất thành công');
exit();
?>