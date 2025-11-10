<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hàm lấy ảnh sản phẩm từ thư mục
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
    
    // Nếu có productId, chọn ảnh dựa trên ID để nhất quán
    if ($productId) {
        $index = $productId % count($images);
        return $images[$index];
    }
    
    // Ngẫu nhiên nếu không có ID
    return $images[array_rand($images)];
}

include '../thanhphan/headertrangchu.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar filters -->
        <div class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Bộ lọc</h5>
                </div>
                <div class="card-body">
                    <!-- Danh mục -->
                    <div class="mb-4">
                        <h6>Danh mục</h6>
                        <?php
                        include_once '../cauhinh/database.php';
                        try {
                            $database = new Database();
                            $db = $database->getConnection();
                            
                            $query = "SELECT * FROM danhmuc WHERE trangthai = 'active' ORDER BY ten_danhmuc";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            
                            while ($danhmuc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cat-' . $danhmuc['id_danhmuc'] . '">
                                    <label class="form-check-label" for="cat-' . $danhmuc['id_danhmuc'] . '">
                                        ' . htmlspecialchars($danhmuc['ten_danhmuc']) . '
                                    </label>
                                </div>';
                            }
                        } catch (PDOException $e) {
                            echo '<p>Lỗi tải danh mục</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Khoảng giá -->
                    <div class="mb-4">
                        <h6>Khoảng giá</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="priceRange" id="price1">
                            <label class="form-check-label" for="price1">Dưới 5 triệu</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="priceRange" id="price2">
                            <label class="form-check-label" for="price2">5 - 10 triệu</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="priceRange" id="price3">
                            <label class="form-check-label" for="price3">10 - 20 triệu</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="priceRange" id="price4">
                            <label class="form-check-label" for="price4">Trên 20 triệu</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product list -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Tất Cả Sản Phẩm</h2>
                <div class="d-flex gap-2">
                    <select class="form-select" style="width: auto;">
                        <option>Sắp xếp theo</option>
                        <option>Giá thấp đến cao</option>
                        <option>Giá cao đến thấp</option>
                        <option>Bán chạy nhất</option>
                        <option>Mới nhất</option>
                    </select>
                </div>
            </div>

            <div class="row g-4">
                <?php
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    $productQuery = "SELECT 
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
                                 WHERE sp.trangthai = 'active'
                                 ORDER BY sp.ngay_tao DESC
                                 LIMIT 12";
                    
                    $productStmt = $db->prepare($productQuery);
                    $productStmt->execute();
                    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($products)) {
                        // Fallback: hiển thị sản phẩm mẫu
                        for ($i = 1; $i <= 12; $i++) {
                            $brands = ['apple', 'samsung', 'xiaomi', 'oppo'];
                            $randomBrand = $brands[array_rand($brands)];
                            $productImage = getProductImage($randomBrand);
                            
                            echo '
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card product-card h-100 border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="' . $productImage . '" 
                                             class="card-img-top" 
                                             alt="Sản phẩm ' . $i . '"
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.src=\'https://via.placeholder.com/300x300/007bff/ffffff?text=Sản+Phẩm+' . $i . '\'">
                                        <div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                            -' . rand(5, 20) . '%
                                        </div>
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <button class="btn btn-light btn-sm rounded-circle">
                                                <i class="far fa-heart text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title">Điện Thoại Smartphone ' . $i . '</h6>
                                        <div class="product-price mb-2">
                                            <span class="text-danger fw-bold">' . number_format(rand(5000000, 25000000), 0, ',', '.') . '₫</span>
                                            <span class="text-muted text-decoration-line-through ms-2">' . number_format(rand(6000000, 30000000), 0, ',', '.') . '₫</span>
                                        </div>
                                        <div class="product-rating text-warning small mb-3">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                            <span class="text-muted ms-1">(' . rand(50, 500) . ')</span>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary add-to-cart" data-product-id="' . $i . '">
                                                <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                                            </button>
                                            <a href="chitiet_sanpham.php?id=' . $i . '" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i>Xem Chi Tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        // Hiển thị sản phẩm thực từ database
                        foreach ($products as $product) {
                            $gia_ban = $product['gia_giam'] > 0 ? $product['gia_giam'] : $product['gia'];
                            $giam_gia = $product['gia_giam'] > 0 ? round(($product['gia'] - $product['gia_giam']) / $product['gia'] * 100) : 0;
                            
                            $productImage = $product['url_anh'];
                            if (empty($productImage) || !file_exists($productImage)) {
                                $brand = strtolower($product['ten_danhmuc']);
                                $productImage = getProductImage($brand);
                            }
                            
                            echo '
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card product-card h-100 border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="' . $productImage . '" 
                                             class="card-img-top" 
                                             alt="' . htmlspecialchars($product['ten_sanpham']) . '"
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.src=\'' . getProductImage(strtolower($product['ten_danhmuc'])) . '\'">
                                        ' . ($giam_gia > 0 ? '
                                        <div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                            -' . $giam_gia . '%
                                        </div>' : '') . '
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <button class="btn btn-light btn-sm rounded-circle add-to-wishlist" data-product-id="' . $product['id_sanpham'] . '">
                                                <i class="far fa-heart text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <span class="badge bg-secondary mb-2">' . htmlspecialchars($product['ten_danhmuc']) . '</span>
                                        <h6 class="card-title">' . htmlspecialchars($product['ten_sanpham']) . '</h6>
                                        <div class="product-price mb-2">
                                            <span class="text-danger fw-bold">' . number_format($gia_ban, 0, ',', '.') . '₫</span>
                                            ' . ($product['gia_giam'] > 0 ? '
                                            <span class="text-muted text-decoration-line-through ms-2">' . number_format($product['gia'], 0, ',', '.') . '₫</span>' : '') . '
                                        </div>
                                        <div class="product-rating text-warning small mb-3">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                            <span class="text-muted ms-1">(' . rand(50, 200) . ')</span>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary add-to-cart" data-product-id="' . $product['id_sanpham'] . '">
                                                <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                                            </button>
                                            <a href="chitiet_sanpham.php?id=' . $product['id_sanpham'] . '" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i>Xem Chi Tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12 text-center"><p>Đang tải sản phẩm...</p></div>';
                }
                ?>
            </div>

            <!-- Pagination -->
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a class="page-link" href="#">Trước</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Tiếp</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
// Xử lý thêm vào giỏ hàng
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        addToCart(productId);
    });
});

// Xử lý thêm vào yêu thích
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