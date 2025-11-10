<?php
include '../../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(3); // Chỉ admin và quản lý mới được truy cập

include '../../thanhphan/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../thanhphan/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quản lý sản phẩm</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="them.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm sản phẩm
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" class="form-control" placeholder="Tên sản phẩm...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-control">
                                <option value="">Tất cả danh mục</option>
                                <?php
                                $database = new Database();
                                $db = $database->getConnection();
                                $query = "SELECT * FROM danhmuc WHERE trangthai = 'active'";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                while ($danhmuc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $danhmuc['id_danhmuc'] . '">' . htmlspecialchars($danhmuc['ten_danhmuc']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-control">
                                <option value="">Tất cả</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Lọc</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $database = new Database();
                                    $db = $database->getConnection();
                                    
                                    $query = "SELECT 
                                                sp.id_sanpham,
                                                sp.ten_sanpham,
                                                sp.ma_sanpham,
                                                dm.ten_danhmuc,
                                                asp.url_anh,
                                                asp.gia,
                                                asp.gia_giam,
                                                asp.soluong_ton,
                                                sp.trangthai,
                                                sp.ngay_tao
                                             FROM sanpham sp
                                             INNER JOIN danhmuc dm ON sp.id_danhmuc = dm.id_danhmuc
                                             INNER JOIN anh_sanpham asp ON sp.id_sanpham = asp.id_sanpham AND asp.is_anhchinh = 1
                                             ORDER BY sp.ngay_tao DESC
                                             LIMIT 20";
                                    
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($products as $product) {
                                        $gia_ban = $product['gia_giam'] > 0 ? $product['gia_giam'] : $product['gia'];
                                        $status_badge = $product['trangthai'] == 'active' ? 'bg-success' : 'bg-secondary';
                                        $status_text = $product['trangthai'] == 'active' ? 'Active' : 'Inactive';
                                        
                                        echo '
                                        <tr>
                                            <td>' . $product['id_sanpham'] . '</td>
                                            <td>
                                                <img src="' . $product['url_anh'] . '" 
                                                     alt="' . htmlspecialchars($product['ten_sanpham']) . '" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            </td>
                                            <td>
                                                <strong>' . htmlspecialchars($product['ten_sanpham']) . '</strong><br>
                                                <small class="text-muted">' . $product['ma_sanpham'] . '</small>
                                            </td>
                                            <td>' . htmlspecialchars($product['ten_danhmuc']) . '</td>
                                            <td>
                                                <strong class="text-danger">' . number_format($gia_ban, 0, ',', '.') . '₫</strong>';
                                        
                                        if ($product['gia_giam'] > 0) {
                                            echo '<br><small class="text-muted text-decoration-line-through">' . 
                                                 number_format($product['gia'], 0, ',', '.') . '₫</small>';
                                        }
                                        
                                        echo '</td>
                                            <td>' . $product['soluong_ton'] . '</td>
                                            <td>
                                                <span class="badge ' . $status_badge . '">' . $status_text . '</span>
                                            </td>
                                            <td>' . date('d/m/Y', strtotime($product['ngay_tao'])) . '</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="sua.php?id=' . $product['id_sanpham'] . '" 
                                                       class="btn btn-outline-primary" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteProduct(' . $product['id_sanpham'] . ')" 
                                                            title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="9" class="text-center text-danger">Lỗi: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Trước</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Tiếp</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function deleteProduct(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }
    
    fetch('../../xuly/sanpham_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Xóa sản phẩm thành công!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi xóa sản phẩm', 'error');
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
.table img {
    border: 1px solid #dee2e6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php include '../../thanhphan/footer.php'; ?>