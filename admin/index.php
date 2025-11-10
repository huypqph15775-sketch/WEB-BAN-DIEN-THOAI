<?php
include '../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(3); // Chỉ admin và quản lý mới được truy cập

include '../thanhphan/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sanpham/index.php">
                            <i class="fas fa-box me-2"></i>
                            Quản lý sản phẩm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="donhang/index.php">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Quản lý đơn hàng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ngoidung/index.php">
                            <i class="fas fa-users me-2"></i>
                            Quản lý người dùng
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Tổng đơn hàng</h5>
                                    <?php
                                    $database = new Database();
                                    $db = $database->getConnection();
                                    $query = "SELECT COUNT(*) as total FROM donhang";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                    <h2><?php echo $total_orders; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Tổng sản phẩm</h5>
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM sanpham WHERE trangthai = 'active'";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                    <h2><?php echo $total_products; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-box fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Tổng người dùng</h5>
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM nguoidung WHERE trangthai = 'active'";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                    <h2><?php echo $total_users; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Doanh thu</h5>
                                    <?php
                                    $query = "SELECT SUM(thanhtien) as total FROM donhang WHERE trangthai = 'hoanthanh'";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                                    ?>
                                    <h2><?php echo number_format($revenue, 0, ',', '.'); ?>₫</h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent orders -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Đơn hàng gần đây</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Khách hàng</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đặt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT dh.ma_donhang, nd.hoten, dh.thanhtien, dh.trangthai, dh.ngaydathang 
                                                 FROM donhang dh 
                                                 INNER JOIN nguoidung nd ON dh.id_nguoidung = nd.id_nguoidung 
                                                 ORDER BY dh.ngaydathang DESC 
                                                 LIMIT 5";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($recent_orders as $order) {
                                            $status_badge = '';
                                            switch ($order['trangthai']) {
                                                case 'choduyet':
                                                    $status_badge = '<span class="badge bg-warning">Chờ duyệt</span>';
                                                    break;
                                                case 'daxacnhan':
                                                    $status_badge = '<span class="badge bg-info">Đã xác nhận</span>';
                                                    break;
                                                case 'danggiaohang':
                                                    $status_badge = '<span class="badge bg-primary">Đang giao</span>';
                                                    break;
                                                case 'hoanthanh':
                                                    $status_badge = '<span class="badge bg-success">Hoàn thành</span>';
                                                    break;
                                                case 'huy':
                                                    $status_badge = '<span class="badge bg-danger">Hủy</span>';
                                                    break;
                                            }
                                            
                                            echo '
                                            <tr>
                                                <td>' . $order['ma_donhang'] . '</td>
                                                <td>' . htmlspecialchars($order['hoten']) . '</td>
                                                <td>' . number_format($order['thanhtien'], 0, ',', '.') . '₫</td>
                                                <td>' . $status_badge . '</td>
                                                <td>' . date('d/m/Y H:i', strtotime($order['ngaydathang'])) . '</td>
                                            </tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.75rem 1rem;
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: #e9ecef;
}

main {
    margin-top: 60px;
}
</style>

<?php include '../thanhphan/footer.php'; ?>