<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điện Thoại Store - Bán điện thoại chính hãng</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS tùy chỉnh -->
    <link rel="stylesheet" href="../thuvien/css/styletrangchu.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>ĐiệnThoạiStore
            </a>

            <!-- Search -->
            <div class="d-none d-lg-flex mx-4 flex-grow-1">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Tìm kiếm sản phẩm...">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Right buttons -->
            <div class="d-flex align-items-center">
                <!-- Cart -->
                <a href="giohang.php" class="btn btn-outline-dark position-relative me-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php
                        if (isset($_SESSION['id_nguoidung'])) {
                            include '../cauhinh/database.php';
                            $database = new Database();
                            $db = $database->getConnection();
                            $query = "SELECT SUM(soluong) as total FROM giohang WHERE id_nguoidung = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$_SESSION['id_nguoidung']]);
                            $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                            echo $cart_count;
                        } else {
                            echo '0';
                        }
                        ?>
                    </span>
                </a>

                <!-- User dropdown -->
                <?php if (isset($_SESSION['id_nguoidung'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['hoten']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../taikhoan/index.php">
                                <i class="fas fa-user me-2"></i>Tài khoản
                            </a></li>
                            <li><a class="dropdown-item" href="../taikhoan/donhang.php">
                                <i class="fas fa-shopping-bag me-2"></i>Đơn hàng
                            </a></li>
                            <li><a class="dropdown-item" href="../taikhoan/yeuthich.php">
                                <i class="fas fa-heart me-2"></i>Yêu thích
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($_SESSION['capdo'] >= 3): ?>
                                <li><a class="dropdown-item text-success" href="../admin/index.php">
                                    <i class="fas fa-cog me-2"></i>Quản trị
                                </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="../xacthuc/dangxuat.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="../xacthuc/dangnhap.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                        </a>
                        <a href="../xacthuc/dangky.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Đăng ký
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile search -->
    <div class="container d-lg-none mt-3">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Tìm kiếm sản phẩm...">
            <button class="btn btn-primary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>