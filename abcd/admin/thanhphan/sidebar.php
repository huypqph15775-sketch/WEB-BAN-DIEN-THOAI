<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="sidebar-header p-3 border-bottom">
            <h5 class="text-primary">
                <i class="fas fa-cog me-2"></i>
                Admin Panel
            </h5>
            <small class="text-muted">Xin chào, <?php echo htmlspecialchars($_SESSION['hoten'] ?? 'Admin'); ?></small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Products Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'sanpham') !== false ? 'active' : ''; ?>" 
                   href="sanpham/index.php">
                    <i class="fas fa-box me-2"></i>
                    Quản lý sản phẩm
                </a>
            </li>
            
            <!-- Orders Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'donhang') !== false ? 'active' : ''; ?>" 
                   href="donhang/index.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Quản lý đơn hàng
                </a>
            </li>
            
            <!-- Users Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'ngoidung') !== false ? 'active' : ''; ?>" 
                   href="ngoidung/index.php">
                    <i class="fas fa-users me-2"></i>
                    Quản lý người dùng
                </a>
            </li>
            
            <!-- Categories Management -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-tags me-2"></i>
                    Quản lý danh mục
                </a>
            </li>
            
            <!-- Promotions Management -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-percent me-2"></i>
                    Khuyến mãi
                </a>
            </li>
            
            <!-- Inventory Management -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-warehouse me-2"></i>
                    Quản lý kho
                </a>
            </li>
            
            <!-- Reports -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar me-2"></i>
                    Báo cáo
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer p-3 border-top">
            <div class="d-grid gap-2">
                <a href="../../trangchu/index.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-store me-2"></i>Về trang bán hàng
                </a>
                <a href="../../xacthuc/dangxuat.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    width: 250px;
    transition: all 0.3s;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin: 0.125rem 0.5rem;
}

.sidebar .nav-link:hover {
    background-color: #e9ecef;
    color: #007bff;
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: #e9ecef;
    border-left: 3px solid #007bff;
}

.sidebar-header {
    background-color: #f8f9fa;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #f8f9fa;
}

@media (max-width: 767.98px) {
    .sidebar {
        width: 100%;
    }
}
</style>