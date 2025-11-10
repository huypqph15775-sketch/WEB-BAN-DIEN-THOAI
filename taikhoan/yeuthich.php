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
    
    // Lấy sản phẩm yêu thích
    $favoritesQuery = "SELECT 
                        sp.id_sanpham,
                        sp.ten_sanpham,
                        sp.id_danhmuc,
                        dm.ten_danhmuc,
                        asp.url_anh,
                        asp.gia,
                        asp.gia_giam,
                        ms.ten_mausac,
                        yt.ngay_them
                      FROM sanpham_yeuthich yt
                      INNER JOIN sanpham sp ON yt.id_sanpham = sp.id_sanpham
                      INNER JOIN danhmuc dm ON sp.id_danhmuc = dm.id_danhmuc
                      INNER JOIN anh_sanpham asp ON sp.id_sanpham = asp.id_sanpham AND asp.is_anhchinh = 1
                      INNER JOIN mausac_sanpham ms ON asp.id_mausac = ms.id_mausac
                      WHERE yt.id_nguoidung = ?
                      ORDER BY yt.ngay_them DESC";
    
    $favoritesStmt = $db->prepare($favoritesQuery);
    $favoritesStmt->execute([$_SESSION['id_nguoidung']]);
    $yeuthich = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            <li class="breadcrumb-item active">Yêu thích</li>
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
                    <a href="yeuthich.php" class="list-group-item list-group-item-action active">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Sản phẩm yêu thích</h4>
                    <span class="badge bg-primary"><?= count($yeuthich) ?> sản phẩm</span>
                </div>
                <div class="card-body">
                    <?php if (empty($yeuthich)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có sản phẩm yêu thích</h5>
                            <p class="text-muted mb-4">Hãy thêm sản phẩm vào danh sách yêu thích để xem lại sau</p>
                            <a href="../trangchu/sanpham.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($yeuthich as $product): 
                                $gia_ban = $product['gia_giam'] > 0 ? $product['gia_giam'] : $product['gia'];
                                $giam_gia = $product['gia_giam'] > 0 ? round(($product['gia'] - $product['gia_giam']) / $product['gia'] * 100) : 0;
                            ?>
                            <div class="col-xl-4 col-lg-6">
                                <div class="card product-card h-100 border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="<?= $product['url_anh'] ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($product['ten_sanpham']) ?>"
                                             style="height: 200px; object-fit: cover;">
                                        <?php if ($giam_gia > 0): ?>
                                        <div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                            -<?= $giam_gia ?>%
                                        </div>
                                        <?php endif; ?>
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <button class="btn btn-light btn-sm rounded-circle remove-favorite" 
                                                    data-product-id="<?= $product['id_sanpham'] ?>"
                                                    title="Xóa khỏi yêu thích">
                                                <i class="fas fa-heart text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['ten_danhmuc']) ?></span>
                                        <h6 class="card-title"><?= htmlspecialchars($product['ten_sanpham']) ?></h6>
                                        <div class="product-price mb-2">
                                            <span class="text-danger fw-bold"><?= number_format($gia_ban, 0, ',', '.') ?>₫</span>
                                            <?php if ($product['gia_giam'] > 0): ?>
                                            <span class="text-muted text-decoration-line-through ms-2">
                                                <?= number_format($product['gia'], 0, ',', '.') ?>₫
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            <i class="far fa-clock me-1"></i>
                                            Thêm vào: <?= date('d/m/Y', strtotime($product['ngay_them'])) ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 pt-0">
                                        <div class="d-grid gap-2">
                                            <a href="../trangchu/chitiet_sanpham.php?id=<?= $product['id_sanpham'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                                            </a>
                                            <button class="btn btn-primary add-to-cart" 
                                                    data-product-id="<?= $product['id_sanpham'] ?>">
                                                <i class="fas fa-cart-plus me-2"></i>Thêm giỏ hàng
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xóa khỏi yêu thích
    document.querySelectorAll('.remove-favorite').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productCard = this.closest('.col-xl-4');
            
            removeFromFavorites(productId, productCard);
        });
    });
    
    // Thêm vào giỏ hàng
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
});

function removeFromFavorites(productId, productCard) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách yêu thích?')) {
        return;
    }
    
    fetch('../xuly/yeuthich_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            productCard.remove();
            showAlert('Đã xóa khỏi danh sách yêu thích!', 'success');
            
            // Cập nhật số lượng sản phẩm
            const badge = document.querySelector('.card-header .badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                badge.textContent = (currentCount - 1) + ' sản phẩm';
            }
            
            // Nếu không còn sản phẩm nào, reload trang
            if (document.querySelectorAll('.col-xl-4').length === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi xóa sản phẩm', 'error');
    });
}

function addToCart(productId) {
    fetch('../xuly/giohang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Thêm vào giỏ hàng thành công!', 'success');
            updateCartCount(data.cart_count);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
    });
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.navbar .badge');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
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
.product-card {
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.remove-favorite {
    transition: all 0.3s ease;
}

.remove-favorite:hover {
    background-color: #ffebee !important;
    transform: scale(1.1);
}
</style>

<?php include '../thanhphan/footer.php'; ?>