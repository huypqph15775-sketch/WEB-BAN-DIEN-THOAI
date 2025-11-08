<?php
include '../../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(4); // Chỉ admin

include '../../thanhphan/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lấy thông tin người dùng
    $userQuery = "SELECT 
                    nd.*,
                    pq.ten_phanquyen,
                    pq.capdo
                 FROM nguoidung nd
                 INNER JOIN phanquyen pq ON nd.id_phanquyen = pq.id_phanquyen
                 WHERE nd.id_nguoidung = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$user_id]);
    $nguoidung = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$nguoidung) {
        header('Location: index.php');
        exit;
    }
    
    // Lấy danh sách phân quyền
    $rolesQuery = "SELECT * FROM phanquyen ORDER BY capdo DESC";
    $rolesStmt = $db->prepare($rolesQuery);
    $rolesStmt->execute();
    $phanquyen = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../thanhphan/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Sửa người dùng</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form id="editUserForm" action="../../xuly/nguoidung_ajax.php" method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?= $nguoidung['id_nguoidung'] ?>">
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Thông tin cơ bản</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" class="form-control" name="hoten" 
                                       value="<?= htmlspecialchars($nguoidung['hoten']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên đăng nhập *</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($nguoidung['tendangnhap']) ?>" readonly>
                                <small class="form-text text-muted">Tên đăng nhập không thể thay đổi</small>
                            </div>
                            
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
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giới tính</label>
                                <select class="form-control" name="gioitinh">
                                    <option value="">Chọn giới tính</option>
                                    <option value="nam" <?= $nguoidung['gioitinh'] == 'nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="nu" <?= $nguoidung['gioitinh'] == 'nu' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="khac" <?= $nguoidung['gioitinh'] == 'khac' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" class="form-control" name="ngaysinh" 
                                       value="<?= $nguoidung['ngaysinh'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Cài đặt tài khoản</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phân quyền *</label>
                                <select class="form-control" name="id_phanquyen" required>
                                    <option value="">Chọn phân quyền</option>
                                    <?php foreach ($phanquyen as $pq): ?>
                                        <option value="<?= $pq['id_phanquyen'] ?>" 
                                                <?= $pq['id_phanquyen'] == $nguoidung['id_phanquyen'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pq['ten_phanquyen']) ?> (Cấp độ: <?= $pq['capdo'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trạng thái *</label>
                                <select class="form-control" name="trangthai" required>
                                    <option value="active" <?= $nguoidung['trangthai'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $nguoidung['trangthai'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="locked" <?= $nguoidung['trangthai'] == 'locked' ? 'selected' : '' ?>>Locked</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mã nhân viên</label>
                                <input type="text" class="form-control" name="ma_nhanvien" 
                                       value="<?= htmlspecialchars($nguoidung['ma_nhanvien'] ?? '') ?>"
                                       placeholder="Chỉ dành cho nhân viên">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày bắt đầu làm việc</label>
                                <input type="date" class="form-control" name="ngay_batdau_lamviec" 
                                       value="<?= $nguoidung['ngay_batdau_lamviec'] ?? '' ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hạng thành viên</label>
                                <select class="form-control" name="hang_thanhvien">
                                    <option value="standard" <?= $nguoidung['hang_thanhvien'] == 'standard' ? 'selected' : '' ?>>Standard</option>
                                    <option value="silver" <?= $nguoidung['hang_thanhvien'] == 'silver' ? 'selected' : '' ?>>Silver</option>
                                    <option value="gold" <?= $nguoidung['hang_thanhvien'] == 'gold' ? 'selected' : '' ?>>Gold</option>
                                    <option value="diamond" <?= $nguoidung['hang_thanhvien'] == 'diamond' ? 'selected' : '' ?>>Diamond</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Điểm tích lũy</label>
                                <input type="number" class="form-control" name="diem_tichluy" 
                                       value="<?= $nguoidung['diem_tichluy'] ?>" min="0">
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Đổi mật khẩu</h5>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-2"></i>
                                        Chỉ điền mật khẩu mới nếu muốn thay đổi. Để trống nếu không muốn đổi mật khẩu.
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" name="matkhau_moi" 
                                       placeholder="Để trống nếu không đổi">
                                <small class="form-text text-muted">Mật khẩu ít nhất 6 ký tự</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" name="xacnhan_matkhau" 
                                       placeholder="Xác nhận mật khẩu mới">
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Cập nhật người dùng
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">Hủy</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const newPassword = formData.get('matkhau_moi');
    const confirmPassword = formData.get('xacnhan_matkhau');
    
    // Validate password
    if (newPassword && newPassword.length < 6) {
        showAlert('Mật khẩu phải có ít nhất 6 ký tự', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('Mật khẩu xác nhận không khớp', 'error');
        return;
    }
    
    fetch('../../xuly/nguoidung_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật người dùng thành công!', 'success');
            setTimeout(() => {
                window.location.href = 'index.php?success=Cập nhật người dùng thành công';
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật người dùng', 'error');
    });
});

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

<?php include '../../thanhphan/footer.php'; ?>