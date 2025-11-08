<?php
include_once '../../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(2); // Nhân viên bán hàng trở lên

include_once '../../thanhphan/header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? '';
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Xây dựng query với filter
    $whereConditions = [];
    $params = [];
    
    if ($status && $status !== 'all') {
        $whereConditions[] = "dh.trangthai = ?";
        $params[] = $status;
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Lấy tổng số đơn hàng
    $countQuery = "SELECT COUNT(*) as total FROM donhang dh $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total_orders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
    
    // Lấy danh sách đơn hàng
    $ordersQuery = "SELECT 
                        dh.*,
                        nd.hoten as ten_khachhang,
                        nd.sodienthoai,
                        dc.diachi_chitiet
                    FROM donhang dh
                    LEFT JOIN nguoidung nd ON dh.id_nguoidung = nd.id_nguoidung
                    LEFT JOIN dia_chi dc ON dh.id_diachi = dc.id_diachi
                    $whereClause
                    ORDER BY dh.ngaydathang DESC 
                    LIMIT ? OFFSET ?";
    
    $ordersStmt = $db->prepare($ordersQuery);
    $params[] = $limit;
    $params[] = $offset;
    $ordersStmt->execute($params);
    $donhang = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Thống kê trạng thái đơn hàng
$statsQuery = "SELECT 
                trangthai,
                COUNT(*) as count 
               FROM donhang 
               GROUP BY trangthai";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$status_stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../thanhphan/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quản lý đơn hàng</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>

            <!-- Status Stats -->
            <div class="row mb-4">
                <?php
                $status_colors = [
                    'choduyet' => 'warning',
                    'daxacnhan' => 'info', 
                    'danggiaohang' => 'primary',
                    'hoanthanh' => 'success',
                    'huy' => 'danger'
                ];
                
                $status_names = [
                    'choduyet' => 'Chờ duyệt',
                    'daxacnhan' => 'Đã xác nhận',
                    'danggiaohang' => 'Đang giao',
                    'hoanthanh' => 'Hoàn thành',
                    'huy' => 'Đã hủy'
                ];
                
                foreach ($status_stats as $stat) {
                    $color = $status_colors[$stat['trangthai']] ?? 'secondary';
                    $name = $status_names[$stat['trangthai']] ?? $stat['trangthai'];
                    echo '
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card border-' . $color . '">
                            <div class="card-body text-center p-3">
                                <h3 class="text-' . $color . '">' . $stat['count'] . '</h3>
                                <small class="text-muted">' . $name . '</small>
                            </div>
                        </div>
                    </div>';
                }
                ?>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?= $status === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                                <option value="choduyet" <?= $status === 'choduyet' ? 'selected' : '' ?>>Chờ duyệt</option>
                                <option value="daxacnhan" <?= $status === 'daxacnhan' ? 'selected' : '' ?>>Đã xác nhận</option>
                                <option value="danggiaohang" <?= $status === 'danggiaohang' ? 'selected' : '' ?>>Đang giao hàng</option>
                                <option value="hoanthanh" <?= $status === 'hoanthanh' ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="huy" <?= $status === 'huy' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" class="form-control" name="from_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" class="form-control" name="to_date">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Lọc</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Địa chỉ</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thanh toán</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($donhang)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-shopping-cart fa-2x text-muted mb-3"></i>
                                            <p class="text-muted">Không có đơn hàng nào</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($donhang as $order): 
                                        $status_color = $status_colors[$order['trangthai']] ?? 'secondary';
                                        $status_name = $status_names[$order['trangthai']] ?? $order['trangthai'];
                                        
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
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($order['ten_khachhang']) ?></strong>
                                                <?php if ($order['sodienthoai']): ?>
                                                    <br><small class="text-muted"><?= $order['sodienthoai'] ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($order['diachi_chitiet'] ?? 'Chưa có địa chỉ') ?></small>
                                        </td>
                                        <td>
                                            <strong class="text-danger"><?= number_format($order['thanhtien'], 0, ',', '.') ?>₫</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $status_color ?>"><?= $status_name ?></span>
                                        </td>
                                        <td><?= $payment_text ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['ngaydathang'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewOrderDetails(<?= $order['id_donhang'] ?>)"
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($order['trangthai'] === 'choduyet'): ?>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="updateOrderStatus(<?= $order['id_donhang'] ?>, 'daxacnhan')"
                                                            title="Xác nhận đơn">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="updateOrderStatus(<?= $order['id_donhang'] ?>, 'huy')"
                                                            title="Hủy đơn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif ($order['trangthai'] === 'daxacnhan'): ?>
                                                    <button class="btn btn-outline-info" 
                                                            onclick="updateOrderStatus(<?= $order['id_donhang'] ?>, 'danggiaohang')"
                                                            title="Bắt đầu giao hàng">
                                                        <i class="fas fa-shipping-fast"></i>
                                                    </button>
                                                <?php elseif ($order['trangthai'] === 'danggiaohang'): ?>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="updateOrderStatus(<?= $order['id_donhang'] ?>, 'hoanthanh')"
                                                            title="Hoàn thành">
                                                        <i class="fas fa-flag-checkered"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>">Trước</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>">Tiếp</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
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
    fetch('../../xuly/donhang_admin_ajax.php', {
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

function updateOrderStatus(orderId, newStatus) {
    const statusNames = {
        'daxacnhan': 'xác nhận',
        'danggiaohang': 'bắt đầu giao hàng', 
        'hoanthanh': 'hoàn thành',
        'huy': 'hủy'
    };
    
    if (!confirm(`Bạn có chắc muốn ${statusNames[newStatus]} đơn hàng này?`)) {
        return;
    }
    
    fetch('../../xuly/donhang_admin_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&order_id=${orderId}&new_status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật trạng thái thành công!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật trạng thái', 'error');
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
    background-color: #f8f9fa;
}

.card.border-warning { border-left: 4px solid #ffc107 !important; }
.card.border-info { border-left: 4px solid #0dcaf0 !important; }
.card.border-primary { border-left: 4px solid #0d6efd !important; }
.card.border-success { border-left: 4px solid #198754 !important; }
.card.border-danger { border-left: 4px solid #dc3545 !important; }

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php include '../../thanhphan/footer.php'; ?>