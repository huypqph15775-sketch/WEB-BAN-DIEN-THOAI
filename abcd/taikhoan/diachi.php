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
    
    // Lấy danh sách địa chỉ
    $addressQuery = "SELECT * FROM dia_chi 
                     WHERE id_nguoidung = ? 
                     ORDER BY mac_dinh DESC, ngay_tao DESC";
    $addressStmt = $db->prepare($addressQuery);
    $addressStmt->execute([$_SESSION['id_nguoidung']]);
    $diachi = $addressStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../trangchu/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php">Tài khoản</a></li>
            <li class="breadcrumb-item active">Địa chỉ</li>
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
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i>Thông tin tài khoản
                    </a>
                    <a href="donhang.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i>Đơn hàng của tôi
                    </a>
                    <a href="yeuthich.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Sản phẩm yêu thích
                    </a>
                    <a href="diachi.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ
                    </a>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Địa chỉ của tôi</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-2"></i>Thêm địa chỉ mới
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($diachi)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có địa chỉ nào</h5>
                            <p class="text-muted mb-4">Thêm địa chỉ để nhận hàng nhanh chóng hơn</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fas fa-plus me-2"></i>Thêm địa chỉ đầu tiên
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($diachi as $dc): ?>
                            <div class="col-md-6">
                                <div class="card h-100 border <?= $dc['mac_dinh'] ? 'border-primary' : '' ?>">
                                    <div class="card-body">
                                        <?php if ($dc['mac_dinh']): ?>
                                            <span class="badge bg-primary mb-2">Mặc định</span>
                                        <?php endif; ?>
                                        
                                        <h6 class="card-title"><?= htmlspecialchars($dc['ten_nguoinhan']) ?></h6>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-phone me-2 text-muted"></i>
                                            <?= $dc['sdt_nguoinhan'] ?>
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                            <?= htmlspecialchars($dc['diachi_chitiet']) ?>
                                        </p>
                                        
                                        <?php if ($dc['tinh_thanh'] || $dc['quan_huyen'] || $dc['phuong_xa']): ?>
                                        <p class="card-text small text-muted mb-3">
                                            <?php
                                            $address_parts = [];
                                            if ($dc['phuong_xa']) $address_parts[] = $dc['phuong_xa'];
                                            if ($dc['quan_huyen']) $address_parts[] = $dc['quan_huyen'];
                                            if ($dc['tinh_thanh']) $address_parts[] = $dc['tinh_thanh'];
                                            echo implode(', ', $address_parts);
                                            ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$dc['mac_dinh']): ?>
                                                <button class="btn btn-outline-primary" 
                                                        onclick="setDefaultAddress(<?= $dc['id_diachi'] ?>)">
                                                    Đặt mặc định
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="editAddress(<?= $dc['id_diachi'] ?>)">
                                                Sửa
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteAddress(<?= $dc['id_diachi'] ?>)">
                                                Xóa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm địa chỉ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAddressForm">
                    <div class="mb-3">
                        <label class="form-label">Họ tên người nhận *</label>
                        <input type="text" class="form-control" name="ten_nguoinhan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" class="form-control" name="sdt_nguoinhan" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tỉnh/Thành phố</label>
                            <select class="form-control" name="tinh_thanh">
                                <option value="">Chọn tỉnh/thành</option>
                                <option value="Hà Nội">Hà Nội</option>
                                <option value="Hồ Chí Minh">Hồ Chí Minh</option>
                                <option value="Đà Nẵng">Đà Nẵng</option>
                                <!-- Thêm các tỉnh thành khác -->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quận/Huyện</label>
                            <select class="form-control" name="quan_huyen">
                                <option value="">Chọn quận/huyện</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phường/Xã</label>
                            <select class="form-control" name="phuong_xa">
                                <option value="">Chọn phường/xã</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết *</label>
                        <textarea class="form-control" name="diachi_chitiet" rows="3" required 
                                  placeholder="Số nhà, tên đường, thôn/xóm..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Loại địa chỉ</label>
                        <select class="form-control" name="loai_diachi">
                            <option value="nha_rieng">Nhà riêng</option>
                            <option value="van_phong">Văn phòng</option>
                            <option value="khac">Khác</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="mac_dinh" id="setDefault">
                        <label class="form-check-label" for="setDefault">
                            Đặt làm địa chỉ mặc định
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="addAddress()">Thêm địa chỉ</button>
            </div>
        </div>
    </div>
</div>

<script>
function addAddress() {
    const form = document.getElementById('addAddressForm');
    const formData = new FormData(form);
    
    // Validate
    const requiredFields = ['ten_nguoinhan', 'sdt_nguoinhan', 'diachi_chitiet'];
    for (let field of requiredFields) {
        if (!formData.get(field)) {
            showAlert('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
            return;
        }
    }
    
    formData.append('action', 'add_address');
    
    fetch('../xuly/diachi_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Thêm địa chỉ thành công!', 'success');
            $('#addAddressModal').modal('hide');
            form.reset();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi thêm địa chỉ', 'error');
    });
}

function setDefaultAddress(addressId) {
    fetch('../xuly/diachi_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=set_default&address_id=${addressId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Đã đặt làm địa chỉ mặc định!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật địa chỉ', 'error');
    });
}

function editAddress(addressId) {
    // Implement edit functionality
    showAlert('Tính năng đang được phát triển', 'info');
}

function deleteAddress(addressId) {
    if (!confirm('Bạn có chắc muốn xóa địa chỉ này?')) {
        return;
    }
    
    fetch('../xuly/diachi_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&address_id=${addressId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Xóa địa chỉ thành công!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi xóa địa chỉ', 'error');
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
.card.border-primary {
    border-width: 2px !important;
}

.btn-group-sm .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}
</style>

<?php include '../thanhphan/footer.php'; ?>