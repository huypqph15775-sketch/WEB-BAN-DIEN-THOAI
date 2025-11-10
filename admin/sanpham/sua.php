<?php
include '../../thanhphan/kiemtradangnhap.php';
kiemTraQuyen(3); // Chỉ admin và quản lý

include '../../thanhphan/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lấy thông tin sản phẩm
    $productQuery = "SELECT 
                        sp.*,
                        dm.ten_danhmuc
                     FROM sanpham sp
                     INNER JOIN danhmuc dm ON sp.id_danhmuc = dm.id_danhmuc
                     WHERE sp.id_sanpham = ?";
    $productStmt = $db->prepare($productQuery);
    $productStmt->execute([$product_id]);
    $sanpham = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sanpham) {
        header('Location: index.php');
        exit;
    }
    
    // Lấy danh mục
    $categoriesQuery = "SELECT * FROM danhmuc WHERE trangthai = 'active' ORDER BY ten_danhmuc";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $danhmuc = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy biến thể sản phẩm
    $variantsQuery = "SELECT 
                        asp.*,
                        ms.ten_mausac,
                        ms.ma_mausac
                     FROM anh_sanpham asp
                     INNER JOIN mausac_sanpham ms ON asp.id_mausac = ms.id_mausac
                     WHERE asp.id_sanpham = ?
                     ORDER BY asp.thutu";
    $variantsStmt = $db->prepare($variantsQuery);
    $variantsStmt->execute([$product_id]);
    $bienthe = $variantsStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                <h1 class="h2">Sửa sản phẩm</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form id="editProductForm" action="../../xuly/sanpham_ajax.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $sanpham['id_sanpham'] ?>">
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Thông tin cơ bản</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên sản phẩm *</label>
                                <input type="text" class="form-control" name="ten_sanpham" 
                                       value="<?= htmlspecialchars($sanpham['ten_sanpham']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mã sản phẩm *</label>
                                <input type="text" class="form-control" name="ma_sanpham" 
                                       value="<?= htmlspecialchars($sanpham['ma_sanpham']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Danh mục *</label>
                                <select class="form-control" name="id_danhmuc" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($danhmuc as $dm): ?>
                                        <option value="<?= $dm['id_danhmuc'] ?>" 
                                                <?= $dm['id_danhmuc'] == $sanpham['id_danhmuc'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dm['ten_danhmuc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-control" name="trangthai">
                                    <option value="active" <?= $sanpham['trangthai'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $sanpham['trangthai'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Mô tả sản phẩm</label>
                                <textarea class="form-control" name="mota" rows="4"><?= htmlspecialchars($sanpham['mota'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Thông số kỹ thuật</label>
                                <textarea class="form-control" name="thongsokythuat" rows="6"><?= htmlspecialchars($sanpham['thongsokythuat'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Product Variants -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="border-bottom pb-2">Biến thể sản phẩm</h5>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addVariant()">
                                        <i class="fas fa-plus me-1"></i>Thêm biến thể
                                    </button>
                                </div>
                                
                                <div id="variantsContainer">
                                    <?php foreach ($bienthe as $index => $variant): ?>
                                    <div class="variant-item card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Màu sắc</label>
                                                    <select class="form-control" name="variants[<?= $index ?>][id_mausac]" required>
                                                        <option value="">Chọn màu</option>
                                                        <?php
                                                        $colorsQuery = "SELECT * FROM mausac_sanpham";
                                                        $colorsStmt = $db->prepare($colorsQuery);
                                                        $colorsStmt->execute();
                                                        $mausac = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);
                                                        
                                                        foreach ($mausac as $ms): ?>
                                                            <option value="<?= $ms['id_mausac'] ?>" 
                                                                    <?= $ms['id_mausac'] == $variant['id_mausac'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($ms['ten_mausac']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Giá *</label>
                                                    <input type="number" class="form-control" name="variants[<?= $index ?>][gia]" 
                                                           value="<?= $variant['gia'] ?>" min="0" required>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Giá giảm</label>
                                                    <input type="number" class="form-control" name="variants[<?= $index ?>][gia_giam]" 
                                                           value="<?= $variant['gia_giam'] ?>" min="0">
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Tồn kho *</label>
                                                    <input type="number" class="form-control" name="variants[<?= $index ?>][soluong_ton]" 
                                                           value="<?= $variant['soluong_ton'] ?>" min="0" required>
                                                </div>
                                                
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Thứ tự</label>
                                                    <input type="number" class="form-control" name="variants[<?= $index ?>][thutu]" 
                                                           value="<?= $variant['thutu'] ?>">
                                                </div>
                                                
                                                <div class="col-md-1 mb-3 d-flex align-items-end">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeVariant(this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label class="form-label">URL ảnh</label>
                                                    <input type="text" class="form-control" name="variants[<?= $index ?>][url_anh]" 
                                                           value="<?= htmlspecialchars($variant['url_anh']) ?>" placeholder="https://...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Cập nhật sản phẩm
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">Hủy</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let variantCount = <?= count($bienthe) ?>;

function addVariant() {
    const container = document.getElementById('variantsContainer');
    const newVariant = `
        <div class="variant-item card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Màu sắc</label>
                        <select class="form-control" name="variants[${variantCount}][id_mausac]" required>
                            <option value="">Chọn màu</option>
                            <?php foreach ($mausac as $ms): ?>
                                <option value="<?= $ms['id_mausac'] ?>"><?= htmlspecialchars($ms['ten_mausac']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Giá *</label>
                        <input type="number" class="form-control" name="variants[${variantCount}][gia]" min="0" required>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Giá giảm</label>
                        <input type="number" class="form-control" name="variants[${variantCount}][gia_giam]" min="0">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Tồn kho *</label>
                        <input type="number" class="form-control" name="variants[${variantCount}][soluong_ton]" min="0" required>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control" name="variants[${variantCount}][thutu]" value="0">
                    </div>
                    
                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger" onclick="removeVariant(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">URL ảnh</label>
                        <input type="text" class="form-control" name="variants[${variantCount}][url_anh]" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', newVariant);
    variantCount++;
}

function removeVariant(button) {
    if (document.querySelectorAll('.variant-item').length > 1) {
        button.closest('.variant-item').remove();
    } else {
        alert('Sản phẩm phải có ít nhất một biến thể!');
    }
}

document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../../xuly/sanpham_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật sản phẩm thành công!', 'success');
            setTimeout(() => {
                window.location.href = 'index.php?success=Cập nhật sản phẩm thành công';
            }, 1500);
        } else {
            showAlert('Có lỗi xảy ra: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật sản phẩm', 'error');
    });
});

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
.variant-item {
    border-left: 4px solid #007bff;
}

.variant-item .card-body {
    padding: 1.5rem;
}
</style>

<?php include '../../thanhphan/footer.php'; ?>