<?php include '../thanhphan/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-auth">
                <div class="card-header">
                    <div class="logo">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Đăng Nhập</h3>
                    <p class="mb-0">Chào mừng bạn đến với cửa hàng</p>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> 
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="xulydangnhap.php" method="POST">
                        <div class="form-group">
                            <label for="tenDangNhap"><i class="fas fa-user"></i> Tên đăng nhập</label>
                            <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" 
                                   placeholder="Nhập tên đăng nhập" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="matKhau"><i class="fas fa-lock"></i> Mật khẩu</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="matKhau" name="matKhau" 
                                       placeholder="Nhập mật khẩu" required>
                                <span class="input-group-text toggle-password" 
                                      onclick="togglePassword('matKhau')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="ghiNho" name="ghiNho">
                            <label class="form-check-label" for="ghiNho">Ghi nhớ đăng nhập</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <div class="loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang xử lý...</span>
                                </div>
                                <p class="mt-2">Đang đăng nhập...</p>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Chưa có tài khoản? 
                            <a href="dangky.php" class="auth-link">Đăng ký ngay</a>
                        </p>
                        <p>
                            <a href="#" class="auth-link">Quên mật khẩu?</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../thanhphan/footer.php'; ?>