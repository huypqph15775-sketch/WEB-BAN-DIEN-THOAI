<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_nguoidung'])) {
    header('Location: ../xacthuc/dangnhap.php?redirect=giohang');
    exit;
}

include '../thanhphan/headertrangchu.php';
include_once '../cauhinh/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lấy giỏ hàng
    $cartQuery = "SELECT 
                    gh.id_giohang,
                    gh.soluong,
                    asp.id_anhsanpham,
                    asp.url_anh,
                    asp.gia,
                    asp.gia_giam,
                    sp.id_sanpham,
                    sp.ten_sanpham,
                    ms.ten_mausac,
                    asp.soluong_ton
                  FROM giohang gh
                  INNER JOIN anh_sanpham asp ON gh.id_anhsanpham = asp.id_anhsanpham
                  INNER JOIN sanpham sp ON asp.id_sanpham = sp.id_sanpham
                  INNER JOIN mausac_sanpham ms ON asp.id_mausac = ms.id_mausac
                  WHERE gh.id_nguoidung = ?
                  ORDER BY gh.ngay_them DESC";
    
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->execute([$_SESSION['id_nguoidung']]);
    $giohang = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính tổng
    $tongtien = 0;
    $tonggiam = 0;
    foreach ($giohang as $item) {
        $gia_ban = $item['gia_giam'] > 0 ? $item['gia_giam'] : $item['gia'];
        $tongtien += $gia_ban * $item['soluong'];
        if ($item['gia_giam'] > 0) {
            $tonggiam += ($item['gia'] - $item['gia_giam']) * $item['soluong'];
        }
    }
    
    // Lấy địa chỉ
    $addressQuery = "SELECT * FROM dia_chi 
                     WHERE id_nguoidung = ? AND trangthai = 'active' 
                     ORDER BY mac_dinh DESC";
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
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Giỏ hàng</li>
        </ol>
    </nav>

    <h1 class="mb-4">Giỏ hàng của bạn</h1>

    <?php if (empty($giohang)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3 class="text-muted">Giỏ hàng trống</h3>
            <p class="text-muted mb-4">Hãy thêm sản phẩm vào giỏ hàng để bắt đầu mua sắm</p>
            <a href="sanpham.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Mua sắm ngay
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Danh sách sản phẩm -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Sản phẩm trong giỏ (<?= count($giohang) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($giohang as $item): 
                            $gia_ban = $item['gia_giam'] > 0 ? $item['gia_giam'] : $item['gia'];
                            $thanhtien = $gia_ban * $item['soluong'];
                        ?>
                        <div class="cart-item p-3 border-bottom" data-cart-id="<?= $item['id_giohang'] ?>">
                            <div class="row align-items-center">
                                <div class="col-2">
                                    <img src="<?= $item['url_anh'] ?>" 
                                         alt="<?= htmlspecialchars($item['ten_sanpham']) ?>"
                                         class="img-fluid rounded" style="height: 80px; object-fit: cover;">
                                </div>
                                <div class="col-5">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['ten_sanpham']) ?></h6>
                                    <p class="text-muted mb-0 small">Màu: <?= $item['ten_mausac'] ?></p>
                                    <p class="text-muted mb-0 small">Tồn kho: <?= $item['soluong_ton'] ?></p>
                                </div>
                                <div class="col-2">
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary btn-sm decrease-qty" 
                                                type="button">-</button>
                                        <input type="number" class="form-control text-center quantity-input" 
                                               value="<?= $item['soluong'] ?>" min="1" max="<?= $item['soluong_ton'] ?>">
                                        <button class="btn btn-outline-secondary btn-sm increase-qty" 
                                                type="button">+</button>
                                    </div>
                                </div>
                                <div class="col-2 text-center">
                                    <div class="product-price">
                                        <strong class="text-danger"><?= number_format($gia_ban, 0, ',', '.') ?>₫</strong>
                                        <?php if ($item['gia_giam'] > 0): ?>
                                        <br>
                                        <small class="text-muted text-decoration-line-through">
                                            <?= number_format($item['gia'], 0, ',', '.') ?>₫
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-1 text-end">
                                    <button class="btn btn-outline-danger btn-sm remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Mã giảm giá -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="mb-3">Mã giảm giá</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Nhập mã giảm giá" id="promoCode">
                            <button class="btn btn-outline-primary" type="button" id="applyPromo">Áp dụng</button>
                        </div>
                        <div id="promoMessage" class="mt-2 small"></div>
                    </div>
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tổng thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <span id="subtotal"><?= number_format($tongtien, 0, ',', '.') ?>₫</span>
                        </div>
                        <?php if ($tonggiam > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm giá sản phẩm:</span>
                            <span>-<?= number_format($tonggiam, 0, ',', '.') ?>₫</span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <span id="shippingFee">0₫</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="promoDiscountRow" style="display: none;">
                            <span>Giảm giá khuyến mãi:</span>
                            <span id="promoDiscount">-0₫</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3 fs-5 fw-bold">
                            <span>Tổng cộng:</span>
                            <span id="totalAmount"><?= number_format($tongtien, 0, ',', '.') ?>₫</span>
                        </div>
                        
                        <!-- Địa chỉ giao hàng -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Địa chỉ giao hàng</label>
                            <?php if (!empty($diachi)): ?>
                                <select class="form-select" id="deliveryAddress">
                                    <?php foreach ($diachi as $dc): ?>
                                    <option value="<?= $dc['id_diachi'] ?>" <?= $dc['mac_dinh'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dc['ten_nguoinhan']) ?> - 
                                        <?= htmlspecialchars($dc['sdt_nguoinhan']) ?> - 
                                        <?= htmlspecialchars($dc['diachi_chitiet']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="alert alert-warning py-2">
                                    <small>Chưa có địa chỉ. <a href="../taikhoan/diachi.php">Thêm địa chỉ</a></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Phương thức thanh toán -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Phương thức thanh toán</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="paymentMethod" 
                                       id="cod" value="cod" checked>
                                <label class="form-check-label" for="cod">
                                    <i class="fas fa-money-bill-wave me-2"></i>Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="paymentMethod" 
                                       id="momo" value="momo">
                                <label class="form-check-label" for="momo">
                                    <i class="fas fa-mobile-alt me-2"></i>Ví MoMo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" 
                                       id="banking" value="banking">
                                <label class="form-check-label" for="banking">
                                    <i class="fas fa-university me-2"></i>Chuyển khoản ngân hàng
                                </label>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 btn-lg" id="checkoutBtn" 
                                <?= empty($diachi) ? 'disabled' : '' ?>>
                            <i class="fas fa-credit-card me-2"></i>Đặt hàng
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Bằng cách đặt hàng, bạn đồng ý với 
                                <a href="#">điều khoản dịch vụ</a> của chúng tôi
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật số lượng
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this);
        });
    });
    
    document.querySelectorAll('.increase-qty').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            const max = parseInt(input.getAttribute('max'));
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
                updateCartItem(input);
            }
        });
    });
    
    document.querySelectorAll('.decrease-qty').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
                updateCartItem(input);
            }
        });
    });
    
    // Xóa sản phẩm
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            const cartId = cartItem.getAttribute('data-cart-id');
            removeCartItem(cartId, cartItem);
        });
    });
    
    // Áp dụng mã giảm giá
    document.getElementById('applyPromo').addEventListener('click', function() {
        const promoCode = document.getElementById('promoCode').value.trim();
        if (promoCode) {
            applyPromoCode(promoCode);
        }
    });
    
    // Thanh toán
    document.getElementById('checkoutBtn').addEventListener('click', function() {
        checkoutOrder();
    });
});

function updateCartItem(input) {
    const cartItem = input.closest('.cart-item');
    const cartId = cartItem.getAttribute('data-cart-id');
    const quantity = input.value;
    
    fetch('../xuly/giohang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&cart_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartSummary(data.cart_summary);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
            // Reset về giá trị cũ
            input.value = data.old_quantity || 1;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật giỏ hàng', 'error');
    });
}

function removeCartItem(cartId, cartElement) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        return;
    }
    
    fetch('../xuly/giohang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cartElement.remove();
            updateCartSummary(data.cart_summary);
            updateCartCount(data.cart_count);
            
            if (data.cart_count === 0) {
                location.reload();
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

function applyPromoCode(promoCode) {
    fetch('../xuly/khuyenmai_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=apply_promo&promo_code=${promoCode}`
    })
    .then(response => response.json())
    .then(data => {
        const messageEl = document.getElementById('promoMessage');
        if (data.success) {
            messageEl.innerHTML = `<span class="text-success">${data.message}</span>`;
            // Hiển thị discount
            document.getElementById('promoDiscountRow').style.display = 'flex';
            document.getElementById('promoDiscount').textContent = `-${data.khuyenmai.giam_gia.toLocaleString('vi-VN')}₫`;
            
            // Cập nhật tổng tiền
            const currentTotal = parseFloat(document.getElementById('totalAmount').textContent.replace(/[^\d]/g, ''));
            const newTotal = currentTotal - data.khuyenmai.giam_gia;
            document.getElementById('totalAmount').textContent = newTotal.toLocaleString('vi-VN') + '₫';
            
        } else {
            messageEl.innerHTML = `<span class="text-danger">${data.message}</span>`;
            document.getElementById('promoDiscountRow').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi áp dụng mã khuyến mãi', 'error');
    });
}

function checkoutOrder() {
    const addressId = document.getElementById('deliveryAddress')?.value;
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
    
    if (!addressId) {
        showAlert('Vui lòng chọn địa chỉ giao hàng', 'error');
        return;
    }
    
    fetch('../xuly/donhang_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=create_order&address_id=${addressId}&payment_method=${paymentMethod}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Đặt hàng thành công!', 'success');
            setTimeout(() => {
                window.location.href = '../taikhoan/donhang.php';
            }, 2000);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi đặt hàng', 'error');
    });
}

function updateCartSummary(summary) {
    if (summary) {
        document.getElementById('subtotal').textContent = summary.tongtien.toLocaleString('vi-VN') + '₫';
        document.getElementById('totalAmount').textContent = summary.tongtien.toLocaleString('vi-VN') + '₫';
    }
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
.cart-item:hover {
    background-color: #f8f9fa;
}

.quantity-input {
    max-width: 70px;
}

.sticky-top {
    position: sticky;
    z-index: 100;
}

.input-group.input-group-sm {
    width: 120px;
}
</style>

<?php include '../thanhphan/footer.php'; ?>