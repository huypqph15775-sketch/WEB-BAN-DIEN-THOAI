// Hiệu ứng loading khi submit form
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const loading = this.querySelector('.loading');
            
            if (submitBtn && loading) {
                submitBtn.style.display = 'none';
                loading.style.display = 'block';
            }
        });
    });

    // Hiệu ứng cho input
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Kiểm tra mật khẩu trùng khớp
    const formDangKy = document.getElementById('formDangKy');
    if (formDangKy) {
        formDangKy.addEventListener('submit', function(e) {
            const matKhau = document.getElementById('matKhau');
            const xacNhanMatKhau = document.getElementById('xacNhanMatKhau');
            
            if (matKhau && xacNhanMatKhau && matKhau.value !== xacNhanMatKhau.value) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                xacNhanMatKhau.focus();
            }
        });
    }

    // Khởi tạo tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Xử lý filter sản phẩm
    initProductFilters();
});

// Hiển thị/ẩn mật khẩu
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.toggle-password i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Xử lý filter sản phẩm
function initProductFilters() {
    const categoryCheckboxes = document.querySelectorAll('input[type="checkbox"][id^="cat-"]');
    const priceRadios = document.querySelectorAll('input[type="radio"][name="priceRange"]');
    
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterProducts);
    });
    
    priceRadios.forEach(radio => {
        radio.addEventListener('change', filterProducts);
    });
}

function filterProducts() {
    // Lấy danh mục được chọn
    const selectedCategories = Array.from(document.querySelectorAll('input[type="checkbox"][id^="cat-"]:checked'))
        .map(cb => cb.id.replace('cat-', ''));
    
    // Lấy khoảng giá được chọn
    const selectedPriceRange = document.querySelector('input[type="radio"][name="priceRange"]:checked');
    const priceRange = selectedPriceRange ? selectedPriceRange.id : '';
    
    // Ở đây có thể thực hiện AJAX request để lọc sản phẩm
    // Hiện tại chỉ log để debug
    console.log('Selected categories:', selectedCategories);
    console.log('Selected price range:', priceRange);
    
    // Có thể thêm AJAX call ở đây:
    // fetch('/api/filter-products', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify({ categories: selectedCategories, priceRange: priceRange })
    // })
    // .then(response => response.json())
    // .then(data => updateProductList(data.products));
}

function updateProductList(products) {
    // Cập nhật danh sách sản phẩm dựa trên kết quả filter
    const productContainer = document.querySelector('.row.g-4');
    if (productContainer && products) {
        // Xóa sản phẩm hiện tại
        productContainer.innerHTML = '';
        
        // Thêm sản phẩm mới
        products.forEach(product => {
            const productHTML = createProductHTML(product);
            productContainer.innerHTML += productHTML;
        });
        
        // Re-init event listeners cho các button mới
        initProductButtons();
    }
}

function createProductHTML(product) {
    // Tạo HTML cho một sản phẩm
    return `
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card product-card h-100 border-0 shadow-sm">
                <div class="position-relative">
                    <img src="${product.image}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                    ${product.discount > 0 ? `<div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 small">-${product.discount}%</div>` : ''}
                </div>
                <div class="card-body">
                    <h6 class="card-title">${product.name}</h6>
                    <div class="product-price">
                        <span class="text-danger fw-bold">${formatPrice(product.price)}</span>
                        ${product.originalPrice > product.price ? `<span class="text-muted text-decoration-line-through ms-2">${formatPrice(product.originalPrice)}</span>` : ''}
                    </div>
                    <div class="product-rating text-warning small">
                        ${generateStarRating(product.rating)}
                        <span class="text-muted ms-1">(${product.reviewCount})</span>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <button class="btn btn-primary w-100 add-to-cart" data-product-id="${product.id}">
                        <i class="fas fa-cart-plus me-2"></i>Thêm Giỏ Hàng
                    </button>
                </div>
            </div>
        </div>
    `;
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
}

function generateStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(rating)) {
            stars += '<i class="fas fa-star"></i>';
        } else if (i === Math.ceil(rating) && rating % 1 !== 0) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    return stars;
}

function initProductButtons() {
    // Re-init event listeners cho các button sản phẩm mới
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format số
function formatNumber(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

// Hiển thị thông báo
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Xử lý lỗi AJAX
function handleAjaxError(error) {
    console.error('AJAX Error:', error);
    showToast('Có lỗi xảy ra. Vui lòng thử lại!', 'danger');
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number (Vietnam)
function validatePhone(phone) {
    const re = /(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b/;
    return re.test(phone);
}