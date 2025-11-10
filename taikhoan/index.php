<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_nguoidung'])) {
    header('Location: ../xacthuc/dangnhap.php');
    exit;
}

include '../thanhphan/headertrangchu.php';
include_once '../cauhinh/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lấy thông tin người dùng
    $userQuery = "SELECT * FROM nguoidung WHERE id_nguoidung = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$_SESSION['id_nguoidung']]);
    $nguoidung = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Thống kê đơn hàng
    $orderStatsQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN trangthai = 'hoanthanh' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(CASE WHEN trangthai = 'danggiaohang' THEN 1 ELSE 0 END) as shipping_orders
                       FROM donhang 
                       WHERE id_nguoidung = ?";
    $orderStatsStmt = $db->prepare($orderStatsQuery);
    $orderStatsStmt->execute([$_SESSION['id_nguoidung']]);
    $order_stats = $orderStatsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../trangchu/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Tài khoản</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Tài khoản</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i>Thông tin tài khoản
                    </a>
                    <a href="donhang.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i>Đơn hàng của tôi
                    </a>
                    <a href="yeuthich.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Sản phẩm yêu thích
                    </a>
                    <a href="diachi.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ
                    </a>
                    <a href="../xacthuc/dangxuat.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                    </a>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">Thống kê</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary"><?= $order_stats['total_orders'] ?? 0 ?></h4>
                                <small class="text-muted">Tổng đơn</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?= $order_stats['completed_orders'] ?? 0 ?></h4>
                            <small class="text-muted">Đã hoàn thành</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Thông tin tài khoản</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                 style="width: 100px; height: 100px; font-size: 2rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <button class="btn btn-outline-primary btn-sm">Thay đổi ảnh</button>
                        </div>
                        <div class="col-md-9">
                            <form id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" name="hoten" 
                                               value="<?= htmlspecialchars($nguoidung['hoten'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($nguoidung['tendangnhap'] ?? '') ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= htmlspecialchars($nguoidung['email'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="sodienthoai" 
                                               value="<?= htmlspecialchars($nguoidung['sodienthoai'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Giới tính</label>
                                        <select class="form-control" name="gioitinh">
                                            <option value="">Chọn giới tính</option>
                                            <option value="nam" <?= ($nguoidung['gioitinh'] ?? '') == 'nam' ? 'selected' : '' ?>>Nam</option>
                                            <option value="nu" <?= ($nguoidung['gioitinh'] ?? '') == 'nu' ? 'selected' : '' ?>>Nữ</option>
                                            <option value="khac" <?= ($nguoidung['gioitinh'] ?? '') == 'khac' ? 'selected' : '' ?>>Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="date" class="form-control" name="ngaysinh" 
                                               value="<?= $nguoidung['ngaysinh'] ?? '' ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Hạng thành viên</label>
                                    <div class="form-control" readonly>
                                        <?php
                                        $hang_map = [
                                            'standard' => 'Thành viên',
                                            'silver' => 'Bạc',
                                            'gold' => 'Vàng', 
                                            'diamond' => 'Kim cương'
                                        ];
                                        echo $hang_map[$nguoidung['hang_thanhvien'] ?? 'standard'] ?? 'Thành viên';
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        Đổi mật khẩu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Đơn hàng gần đây</h5>
                    <a href="donhang.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                </div>
                <div class="card-body">
                    <?php
                    $recentOrdersQuery = "SELECT * FROM donhang 
                                         WHERE id_nguoidung = ? 
                                         ORDER BY ngaydathang DESC 
                                         LIMIT 3";
                    $recentOrdersStmt = $db->prepare($recentOrdersQuery);
                    $recentOrdersStmt->execute([$_SESSION['id_nguoidung']]);
                    $recent_orders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($recent_orders)): ?>
                        <p class="text-muted text-center py-3">Chưa có đơn hàng nào</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): 
                                        $status_badge = '';
                                        switch ($order['trangthai']) {
                                            case 'choduyet': $status_badge = 'bg-warning'; break;
                                            case 'daxacnhan': $status_badge = 'bg-info'; break;
                                            case 'danggiaohang': $status_badge = 'bg-primary'; break;
                                            case 'hoanthanh': $status_badge = 'bg-success'; break;
                                            case 'huy': $status_badge = 'bg-danger'; break;
                                            default: $status_badge = 'bg-secondary';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $order['ma_donhang'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($order['ngaydathang'])) ?></td>
                                        <td><?= number_format($order['thanhtien'], 0, ',', '.') ?>₫</td>
                                        <td>
                                            <span class="badge <?= $status_badge ?>">
                                                <?= ucfirst($order['trangthai']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="donhang_chitiet.php?id=<?= $order['id_donhang'] ?>" class="btn btn-sm btn-outline-primary">
                                                Xem chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đổi mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="changePassword()">Đổi mật khẩu</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_profile');
    
    fetch('../xuly/taikhoan_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật thông tin thành công!', 'success');
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật thông tin', 'error');
    });
});

function changePassword() {
    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showAlert('Mật khẩu xác nhận không khớp!', 'error');
        return;
    }
    
    formData.append('action', 'change_password');
    
    fetch('../xuly/taikhoan_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Đổi mật khẩu thành công!', 'success');
            $('#changePasswordModal').modal('hide');
            form.reset();
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi đổi mật khẩu', 'error');
    });
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}
</script>

<style>
.avatar-placeholder {
    background: linear-gradient(45deg, #007bff, #0056b3);
}

.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
</style>

<?php include '../thanhphan/footer.php'; ?>