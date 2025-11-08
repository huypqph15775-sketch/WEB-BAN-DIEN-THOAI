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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lấy tổng số đơn hàng
    $countQuery = "SELECT COUNT(*) as total FROM donhang WHERE id_nguoidung = ?";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute([$_SESSION['id_nguoidung']]);
    $total_orders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
    
    // Lấy danh sách đơn hàng
    $ordersQuery = "SELECT * FROM donhang 
                   WHERE id_nguoidung = ? 
                   ORDER BY ngaydathang DESC 
                   LIMIT ? OFFSET ?";
    $ordersStmt = $db->prepare($ordersQuery);
    $ordersStmt->bindValue(1, $_SESSION['id_nguoidung'], PDO::PARAM_INT);
    $ordersStmt->bindValue(2, $limit, PDO::PARAM_INT);
    $ordersStmt->bindValue(3, $offset, PDO::PARAM_INT);
    $ordersStmt->execute();
    $donhang = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            <li class="breadcrumb-item active">Đơn hàng</li>
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
                    <a href="donhang.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-shopping-bag me-2"></i>Đơn hàng của tôi
                    </a>
                    <a href="yeuthich.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Sản phẩm yêu thích
                    </a>
                    <a href="diachi.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ
                    </a>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Đơn hàng của tôi</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($donhang)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có đơn hàng nào</h5>
                            <p class="text-muted mb-4">Hãy mua sắm và quay lại xem đơn hàng của bạn tại đây</p>
                            <a href="../trangchu/sanpham.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thanh toán</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donhang as $order): 
                                        $status_badge = '';
                                        $status_text = '';
                                        switch ($order['trangthai']) {
                                            case 'choduyet': 
                                                $status_badge = 'bg-warning';
                                                $status_text = 'Chờ duyệt';
                                                break;
                                            case 'daxacnhan': 
                                                $status_badge = 'bg-info';
                                                $status_text = 'Đã xác nhận';
                                                break;
                                            case 'danggiaohang': 
                                                $status_badge = 'bg-primary';
                                                $status_text = 'Đang giao hàng';
                                                break;
                                            case 'hoanthanh': 
                                                $status_badge = 'bg-success';
                                                $status_text = 'Hoàn thành';
                                                break;
                                            case 'huy': 
                                                $status_badge = 'bg-danger';
                                                $status_text = 'Đã hủy';
                                                break;
                                        }
                                        
                                        $payment_text = '';
                                        switch ($order['hinhthuc_thanhtoan']) {
                                            case 'cod': $payment_text = 'COD'; break;
                                            case 'momo': $payment_text = 'MoMo'; break;
                                            case 'banking': $payment_text = 'Chuyển khoản'; break;
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= $order['ma_donhang'] ?></strong>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['ngaydathang'])) ?></td>
                                        <td><?= number_format($order['thanhtien'], 0, ',', '.') ?>₫</td>
                                        <td>
                                            <span class="badge <?= $status_badge ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= $payment_text ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewOrderDetails(<?= $order['id_donhang'] ?>)">
                                                <i class="fas fa-eye me-1"></i>Chi tiết
                                            </button>
                                            <?php if ($order['trangthai'] === 'choduyet'): ?>
                                                <button class="btn btn-sm btn-outline-danger ms-1" 
                                                        onclick="cancelOrder(<?= $order['id_donhang'] ?>)">
                                                    <i class="fas fa-times me-1"></i>Hủy
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Tiếp</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    fetch('../xuly/donhang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_order_details&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('orderDetailsContent').innerHTML = data.html;
            $('#orderDetailsModal').modal('show');
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi tải chi tiết đơn hàng', 'error');
    });
}

function cancelOrder(orderId) {
    if (!confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
        return;
    }
    
    fetch('../xuly/donhang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=cancel_order&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Hủy đơn hàng thành công!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi hủy đơn hàng', 'error');
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
.table th {
    border-top: none;
    font-weight: 600;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}
</style>

<?php include '../thanhphan/footer.php'; ?>