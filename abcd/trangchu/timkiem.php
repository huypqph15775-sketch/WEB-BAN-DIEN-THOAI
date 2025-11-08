<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../thanhphan/headertrangchu.php';
include '../cauhinh/database.php';

$keyword = $_GET['q'] ?? '';
$category = $_GET['danhmuc'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Hàm lấy ảnh sản phẩm
function getProductImage($brand, $productId = null) {
    $imageDir = "../thuvien/hinhanh/" . strtolower($brand) . "/";
    $placeholder = "https://via.placeholder.com/300x300/007bff/ffffff?text=" . $brand;
    
    if (!is_dir($imageDir)) {
        return $placeholder;
    }
    
    $images = glob($imageDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    
    if (empty($images)) {
        return $placeholder;
    }
    
    if ($productId) {
        $index = $productId % count($images);
        return $images[$index];
    }
    
    return $images[array_rand($images)];
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Xây dựng query tìm kiếm
    $whereConditions = ["sp.trangthai = 'active'"];
    $params = [];
    
    if (!empty($keyword)) {
        $whereConditions[] = "(sp.ten_sanpham LIKE ? OR sp.mota LIKE ?)";
        $searchTerm = "%$keyword%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $whereConditions[] = "sp.id_danhmuc = ?";
        $params[] = $category;
    }
    
    if (!empty($min_price)) {
        $whereConditions[] = "asp.gia >= ?";
        $params[] = $min_price;
    }
    
    if (!empty($max_price)) {
        $whereConditions[] = "asp.gia <= ?";
        $params[] = $max_price;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Đếm tổng số sản phẩm
    $countQuery = "SELECT COUNT(DISTINCT sp.id_sanpham) as total 
                   FROM sanpham sp
                   INNER JOIN anh_sanpham asp ON sp.id_sanpham = asp.id_sanpham AND asp.is_anhchinh = 1
                   $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total_products = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_products / $limit);
    
    // Lấy sản phẩm
    $productsQuery = "SELECT 
                        sp.id_sanpham,
                        sp.ten_sanpham,
                        sp.id_danhmuc,
                        dm.ten_danhmuc,
                        asp.url_anh,
                        asp.gia,
                        asp.gia_giam,
                        ms.ten_mausac
                     FROM sanpham sp
                     INNER JOIN danhmuc dm ON sp.id_danhmuc = dm.id_danhmuc
                     INNER JOIN anh_sanpham asp ON sp.id_sanpham = asp.id_sanpham AND asp.is_anhchinh = 1
                     INNER JOIN mausac_sanpham ms ON asp.id_mausac = ms.id_mausac
                     $whereClause
                     GROUP BY sp.id_sanpham
                     ORDER BY sp.ngay_tao DESC
                     LIMIT ? OFFSET ?";
    
    $productsStmt = $db->prepare($productsQuery);
    $params[] = $limit;
    $params[] = $offset;
    $productsStmt->execute($params);
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh mục cho filter
    $categoriesQuery = "SELECT * FROM danhmuc WHERE trangthai = 'active' ORDER BY ten_danhmuc";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Tìm kiếm</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Sidebar filters -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Bộ lọc</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="timkiem.php">
                        <!-- Tìm kiếm -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Từ khóa</label>
                            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($keyword) ?>" 
                                   placeholder="Nhập từ khóa...">
                        </div>
                        
                        <!-- Danh mục -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Danh mục</label>
                            <select class="form-control" name="danhmuc">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_danhmuc'] ?>" 
                                            <?= $category == $cat['id_danhmuc'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['ten_danhmuc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Khoảng giá -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Khoảng giá</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" 
                                           value="<?= $min_price ?>" placeholder="Từ" min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" 
                                           value="<?= $max_price ?>" placeholder="Đến" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Áp dụng bộ lọc
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product list -->
        <div class="col-lg-9">
            <!-- Search header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Kết quả tìm kiếm</h2>
                    <?php if (!empty($keyword) || !empty($category)): ?>
                        <p class="text-muted mb-0">
                            Tìm thấy <strong><?= $total_products ?></strong> sản phẩm
                            <?php if (!empty($keyword)): ?>
                                cho từ khóa "<strong><?= htmlspecialchars($keyword) ?></strong>"
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2">
                    <select class="form-select" style="width: auto;">
                        <option>Sắp xếp theo</option>
                        <option>Mới nhất</option>
                        <option>Giá thấp đến cao</option>
                        <option>Giá cao đến thấp</option>
                        <option>Bán chạy nhất</option>
                    </select>
                </div>
            </div>

            <!-- Products grid -->
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Không tìm thấy sản phẩm nào</h4>
                    <p class="text-muted mb-4">Hãy thử với từ khóa khác hoặc điều chỉnh bộ lọc</p>
                    <a href="sanpham.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): 
                        $gia_ban = $product['gia_giam'] > 0 ? $product['gia_giam'] : $product['gia'];
                        $giam_gia = $product['gia_giam'] > 0 ? round(($product['gia'] - $product['gia_giam']) / $product['gia'] * 100) : 0;
                        
                        $productImage = $product['url_anh'];
                        if (empty($productImage) || !file_exists($productImage)) {
                            $brand = strtolower($product['ten_danhmuc']);
                            $productImage = getProductImage($brand, $product['id_sanpham']);
                        }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            <div class="position-relative">
                                <img src="<?= $productImage ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['ten_sanpham']) ?>"
                                     style="height: 200px; object-fit: cover;"
                                     onerror="this.src='<?= getProductImage(strtolower($product['ten_danhmuc'])) ?>'">
                                <?php if ($giam_gia > 0): ?>
                                <div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                    -<?= $giam_gia ?>%
                                </div>
                                <?php endif; ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <button class="btn btn-light btn-sm rounded-circle add-to-wishlist" 
                                            data-product-id="<?= $product['id_sanpham'] ?>">
                                        <i class="far fa-heart text-danger"></i>
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
                                <div class="product-rating text-warning small mb-3">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <span class="text-muted ms-1">(<?= rand(50, 200) ?>)</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary add-to-cart" 
                                            data-product-id="<?= $product['id_sanpham'] ?>">
                                        <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                                    </button>
                                    <a href="chitiet_sanpham.php?id=<?= $product['id_sanpham'] ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>Xem Chi Tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?q=<?= urlencode($keyword) ?>&danhmuc=<?= $category ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&page=<?= $page - 1 ?>">
                                    Trước
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" 
                                   href="?q=<?= urlencode($keyword) ?>&danhmuc=<?= $category ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?q=<?= urlencode($keyword) ?>&danhmuc=<?= $category ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&page=<?= $page + 1 ?>">
                                    Tiếp
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Thêm vào giỏ hàng
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
    
    // Thêm vào yêu thích
    document.querySelectorAll('.add-to-wishlist').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const icon = this.querySelector('i');
            
            if (icon.classList.contains('far')) {
                addToWishlist(productId, icon);
            } else {
                removeFromWishlist(productId, icon);
            }
        });
    });
});

function addToCart(productId) {
    <?php if (isset($_SESSION['id_nguoidung'])): ?>
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
    });
    <?php else: ?>
    showAlert('Vui lòng đăng nhập để thêm vào giỏ hàng', 'error');
    setTimeout(() => {
        window.location.href = '../xacthuc/dangnhap.php';
    }, 2000);
    <?php endif; ?>
}

function addToWishlist(productId, icon) {
    <?php if (isset($_SESSION['id_nguoidung'])): ?>
    fetch('../xuly/yeuthich_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            showAlert('Đã thêm vào yêu thích!', 'success');
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    });
    <?php else: ?>
    showAlert('Vui lòng đăng nhập để thêm vào yêu thích', 'error');
    <?php endif; ?>
}

function removeFromWishlist(productId, icon) {
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
            icon.classList.remove('fas');
            icon.classList.add('far');
            showAlert('Đã xóa khỏi yêu thích!', 'success');
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
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

function updateCartCount(count) {
    const cartBadge = document.querySelector('.navbar .badge');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}
</script>

<?php include '../thanhphan/footer.php'; ?>