<?php
include '../../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(4); // Chỉ admin mới được quản lý người dùng

include '../../thanhphan/header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Xây dựng query với filter
    $whereConditions = [];
    $params = [];
    
    if ($role && $role !== 'all') {
        $whereConditions[] = "nd.id_phanquyen = ?";
        $params[] = $role;
    }
    
    if ($status && $status !== 'all') {
        $whereConditions[] = "nd.trangthai = ?";
        $params[] = $status;
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Lấy tổng số người dùng
    $countQuery = "SELECT COUNT(*) as total FROM nguoidung nd $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total_users = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $limit);
    
    // Lấy danh sách người dùng
    $usersQuery = "SELECT 
                        nd.*,
                        pq.ten_phanquyen,
                        pq.capdo
                    FROM nguoidung nd
                    INNER JOIN phanquyen pq ON nd.id_phanquyen = pq.id_phanquyen
                    $whereClause
                    ORDER BY nd.ngay_tao DESC 
                    LIMIT ? OFFSET ?";
    
    $usersStmt = $db->prepare($usersQuery);
    $params[] = $limit;
    $params[] = $offset;
    $usersStmt->execute($params);
    $nguoidung = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                <h1 class="h2">Quản lý người dùng</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="them.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Thêm người dùng
                    </a>
                </div>
            </div>

            <!-- User Stats -->
            <div class="row mb-4">
                <?php
                // Thống kê người dùng theo vai trò
                $statsQuery = "SELECT 
                                pq.ten_phanquyen,
                                COUNT(*) as count 
                               FROM nguoidung nd
                               INNER JOIN phanquyen pq ON nd.id_phanquyen = pq.id_phanquyen
                               WHERE nd.trangthai = 'active'
                               GROUP BY pq.ten_phanquyen";
                $statsStmt = $db->prepare($statsQuery);
                $statsStmt->execute();
                $role_stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $role_colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                $color_index = 0;
                
                foreach ($role_stats as $stat) {
                    $color = $role_colors[$color_index % count($role_colors)];
                    $color_index++;
                    echo '
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card border-' . $color . '">
                            <div class="card-body text-center p-3">
                                <h3 class="text-' . $color . '">' . $stat['count'] . '</h3>
                                <small class="text-muted">' . $stat['ten_phanquyen'] . '</small>
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
                        <div class="col-md-4">
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?= $role === '' ? 'selected' : '' ?>>Tất cả vai trò</option>
                                <?php foreach ($phanquyen as $pq): ?>
                                    <option value="<?= $pq['id_phanquyen'] ?>" <?= $role == $pq['id_phanquyen'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pq['ten_phanquyen']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?= $status === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Lọc</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Thông tin</th>
                                    <th>Vai trò</th>
                                    <th>Liên hệ</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($nguoidung)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                            <p class="text-muted">Không có người dùng nào</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($nguoidung as $user): 
                                        $status_badge = '';
                                        $status_text = '';
                                        switch ($user['trangthai']) {
                                            case 'active': 
                                                $status_badge = 'bg-success';
                                                $status_text = 'Active';
                                                break;
                                            case 'inactive': 
                                                $status_badge = 'bg-secondary';
                                                $status_text = 'Inactive';
                                                break;
                                            case 'locked': 
                                                $status_badge = 'bg-danger';
                                                $status_text = 'Locked';
                                                break;
                                        }
                                        
                                        $role_badge = '';
                                        switch ($user['capdo']) {
                                            case 5: $role_badge = 'bg-danger'; break;
                                            case 4: $role_badge = 'bg-warning'; break;
                                            case 3: $role_badge = 'bg-info'; break;
                                            case 2: $role_badge = 'bg-primary'; break;
                                            case 1: $role_badge = 'bg-secondary'; break;
                                            default: $role_badge = 'bg-light text-dark';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $user['id_nguoidung'] ?></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($user['hoten']) ?></strong>
                                                <br>
                                                <small class="text-muted">@<?= $user['tendangnhap'] ?></small>
                                                <?php if ($user['ma_nhanvien']): ?>
                                                    <br><small class="text-muted">Mã NV: <?= $user['ma_nhanvien'] ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $role_badge ?>">
                                                <?= htmlspecialchars($user['ten_phanquyen']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['email']): ?>
                                                <div><small><?= htmlspecialchars($user['email']) ?></small></div>
                                            <?php endif; ?>
                                            <?php if ($user['sodienthoai']): ?>
                                                <div><small><?= $user['sodienthoai'] ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $status_badge ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['ngay_tao'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="sua.php?id=<?= $user['id_nguoidung'] ?>" 
                                                   class="btn btn-outline-primary" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id_nguoidung'] != $_SESSION['id_nguoidung']): ?>
                                                    <?php if ($user['trangthai'] == 'active'): ?>
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="updateUserStatus(<?= $user['id_nguoidung'] ?>, 'inactive')"
                                                                title="Vô hiệu hóa">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="updateUserStatus(<?= $user['id_nguoidung'] ?>, 'active')"
                                                                title="Kích hoạt">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteUser(<?= $user['id_nguoidung'] ?>)" 
                                                            title="Xóa">
                                                        <i class="fas fa-trash"></i>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&role=<?= $role ?>&status=<?= $status ?>">Trước</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&role=<?= $role ?>&status=<?= $status ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&role=<?= $role ?>&status=<?= $status ?>">Tiếp</a>
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

<script>
function updateUserStatus(userId, newStatus) {
    const action = newStatus === 'active' ? 'kích hoạt' : 'vô hiệu hóa';
    
    if (!confirm(`Bạn có chắc muốn ${action} người dùng này?`)) {
        return;
    }
    
    fetch('../../xuly/nguoidung_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&user_id=${userId}&new_status=${newStatus}`
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

function deleteUser(userId) {
    if (!confirm('Bạn có chắc muốn xóa người dùng này? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    fetch('../../xuly/nguoidung_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Xóa người dùng thành công!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi xóa người dùng', 'error');
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

.card.border-primary { border-left: 4px solid #0d6efd !important; }
.card.border-success { border-left: 4px solid #198754 !important; }
.card.border-info { border-left: 4px solid #0dcaf0 !important; }
.card.border-warning { border-left: 4px solid #ffc107 !important; }
.card.border-danger { border-left: 4px solid #dc3545 !important; }
.card.border-secondary { border-left: 4px solid #6c757d !important; }

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php include '../../thanhphan/footer.php'; ?>