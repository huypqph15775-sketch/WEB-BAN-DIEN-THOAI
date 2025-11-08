<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once '../thanhphan/headertrangchu.php';

// Hàm lấy ảnh sản phẩm ngẫu nhiên theo hãng
function getRandomProductImage($brand) {
    $imageDir = "../thuvien/hinhanh/" . $brand . "/";
    $placeholder = "#" . $brand;
    
    if (!is_dir($imageDir)) {
        return $placeholder;
    }
    
    $images = glob($imageDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
    
    if (empty($images)) {
        return $placeholder;
    }
    
    // Lấy ảnh ngẫu nhiên
    $randomImage = $images[array_rand($images)];
    return $randomImage;
}

// Hàm lấy ảnh danh mục
function getCategoryImage($categoryName) {
    $brandMap = [
        'SamSung' => 'samsung',
        'Motorola' => 'motorola', 
        'Nokia' => 'nokia',
        'Vivo' => 'vivo',
        'Xiaomi' => 'xiaomi',
        'Realme' => 'realme',
        'Oppo' => 'oppo',
        'Apple' => 'apple'
    ];
    
    $brand = $brandMap[$categoryName] ?? strtolower($categoryName);
    return getRandomProductImage($brand);
}
?>

<!-- Hero Section -->
<section class="hero-section bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Điện Thoại Thông Minh Chính Hãng</h1>
                <p class="lead mb-4">Khám phá những sản phẩm công nghệ mới nhất với giá tốt nhất thị trường</p>
                <div class="d-flex gap-3">
                    <a href="sanpham.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Mua Ngay
                    </a>
                    <a href="#san-pham-noi-bat" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-eye me-2"></i>Xem Sản Phẩm
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo getRandomProductImage('apple'); ?>" 
                     alt="Điện thoại iPhone" 
                     class="img-fluid rounded shadow"
                     style="height: 400px; object-fit: cover;"
                     onerror="this.src='#">
            </div>
        </div>
    </div>
</section>

<!-- Danh mục sản phẩm -->
<section class="categories-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Danh Mục Sản Phẩm</h2>
        <div class="row g-4">
            <?php
            include_once '../cauhinh/database.php';
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT * FROM danhmuc WHERE trangthai = 'active' ORDER BY thutu LIMIT 8";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                while ($danhmuc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $categoryImage = getCategoryImage($danhmuc['ten_danhmuc']);
                    echo '
                    <div class="col-md-3 col-6">
                        <div class="card category-card h-100 text-center border-0 shadow-sm hover-lift">
                            <div class="card-body p-3">
                                <div class="category-image mb-3">
                                    <img src="' . $categoryImage . '" 
                                         alt="' . htmlspecialchars($danhmuc['ten_danhmuc']) . '"
                                         class="img-fluid rounded"
                                         style="height: 120px; object-fit: cover; width: 100%;"
                                         onerror="this.src=\'#' . urlencode($danhmuc['ten_danhmuc']) . '\'">
                                </div>
                                <h6 class="card-title mb-2">' . htmlspecialchars($danhmuc['ten_danhmuc']) . '</h6>
                                <a href="sanpham.php?danhmuc=' . $danhmuc['id_danhmuc'] . '" 
                                   class="btn btn-outline-primary btn-sm">Xem sản phẩm</a>
                            </div>
                        </div>
                    </div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12 text-center"><p>Đang tải danh mục...</p></div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Sản phẩm nổi bật -->
<!-- Sản phẩm nổi bật -->
<section id="san-pham-noi-bat" class="featured-products py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2>Sản Phẩm Nổi Bật</h2>
            <a href="sanpham.php" class="btn btn-outline-primary">Xem Tất Cả</a>
        </div>
        
        <div class="row g-4">
            <?php
            try {
                // Lấy 8 sản phẩm ngẫu nhiên từ database
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
                             ORDER BY RAND()
                             LIMIT 8";
                
                $productStmt = $db->prepare($productQuery);
                $productStmt->execute();
                $featuredProducts = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($featuredProducts)) {
                    // Fallback: hiển thị sản phẩm mẫu nếu không có dữ liệu
                    for ($i = 1; $i <= 8; $i++) {
                        // ... code sản phẩm mẫu ...
                    }
                } else {
                    // Hiển thị sản phẩm thực từ database
                    foreach ($featuredProducts as $product) {
                        // ... code hiển thị sản phẩm thực ...
                    }
                }
            } catch (PDOException $e) {
                echo '<div class="col-12 text-center"><p>Đang tải sản phẩm...</p></div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Khuyến mãi -->
<section class="promotion-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3">Ưu Đãi Đặc Biệt</h3>
                <p class="mb-4">Giảm ngay 10% cho đơn hàng đầu tiên. Miễn phí vận chuyển toàn quốc.</p>
                <div class="d-flex gap-3">
                    <div class="promo-item text-center">
                        <div class="promo-icon mb-2">
                            <i class="fas fa-tag fa-2x"></i>
                        </div>
                        <div>Giảm 10%</div>
                    </div>
                    <div class="promo-item text-center">
                        <div class="promo-icon mb-2">
                            <i class="fas fa-shipping-fast fa-2x"></i>
                        </div>
                        <div>Free Ship</div>
                    </div>
                    <div class="promo-item text-center">
                        <div class="promo-icon mb-2">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                        <div>Bảo Hành</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="promo-countdown bg-white text-dark p-4 rounded shadow">
                    <h5 class="text-primary">Ưu Đãi Kết Thúc Sau</h5>
                    <div id="countdown" class="display-6 fw-bold text-danger">23:59:59</div>
                    <a href="sanpham.php" class="btn btn-primary mt-3">Mua Ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Countdown timer
function updateCountdown() {
    const now = new Date();
    const endOfDay = new Date();
    endOfDay.setHours(23, 59, 59, 999);
    
    const diff = endOfDay - now;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('countdown').textContent = 
        `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

setInterval(updateCountdown, 1000);
updateCountdown();

// Xử lý thêm vào giỏ hàng
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        addToCart(productId);
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
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
    });
    <?php else: ?>
    showAlert('Vui lòng đăng nhập để thêm vào giỏ hàng', 'error');
    setTimeout(() => {
        window.location.href = '../xacthuc/dangnhap.php';
    }, 2000);
    <?php endif; ?>
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

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.category-card {
    transition: all 0.3s ease;
}

.product-card {
    transition: all 0.3s ease;
}

.hero-section img {
    border-radius: 15px;
}

.promo-item {
    flex: 1;
}
</style>

<?php include '../thanhphan/footer.php'; ?>