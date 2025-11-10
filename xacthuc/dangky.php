<?php include '../thanhphan/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card card-auth">
                <div class="card-header">
                    <div class="logo">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Đăng Ký Tài Khoản</h3>
                    <p class="mb-0">Tham gia cùng chúng tôi</p>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form id="formDangKy" action="xulydangky.php" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hoTen"><i class="fas fa-user"></i> Họ và tên *</label>
                                    <input type="text" class="form-control" id="hoTen" name="hoTen" 
                                           placeholder="Nhập họ và tên" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tenDangNhap"><i class="fas fa-user-circle"></i> Tên đăng nhập *</label>
                                    <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" 
                                           placeholder="Nhập tên đăng nhập" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Nhập email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="soDienThoai"><i class="fas fa-phone"></i> Số điện thoại</label>
                                    <input type="tel" class="form-control" id="soDienThoai" name="soDienThoai" 
                                           placeholder="Nhập số điện thoại">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="matKhau"><i class="fas fa-lock"></i> Mật khẩu *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="matKhau" name="matKhau" 
                                               placeholder="Nhập mật khẩu" required minlength="6">
                                        <span class="input-group-text toggle-password" 
                                              onclick="togglePassword('matKhau')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Mật khẩu ít nhất 6 ký tự</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="xacNhanMatKhau"><i class="fas fa-lock"></i> Xác nhận mật khẩu *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="xacNhanMatKhau" 
                                               placeholder="Nhập lại mật khẩu" required>
                                        <span class="input-group-text toggle-password" 
                                              onclick="togglePassword('xacNhanMatKhau')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gioiTinh"><i class="fas fa-venus-mars"></i> Giới tính</label>
                                    <select class="form-control" id="gioiTinh" name="gioiTinh">
                                        <option value="">Chọn giới tính</option>
                                        <option value="nam">Nam</option>
                                        <option value="nu">Nữ</option>
                                        <option value="khac">Khác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ngaySinh"><i class="fas fa-birthday-cake"></i> Ngày sinh</label>
                                    <input type="date" class="form-control" id="ngaySinh" name="ngaySinh">
                                </div>
                            </div>
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="dongYDieuKhoan" required>
                            <label class="form-check-label" for="dongYDieuKhoan">
                                Tôi đồng ý với <a href="#" class="auth-link">điều khoản sử dụng</a> và 
                                <a href="#" class="auth-link">chính sách bảo mật</a>
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Đăng Ký
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <div class="loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang xử lý...</span>
                                </div>
                                <p class="mt-2">Đang tạo tài khoản...</p>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p>Đã có tài khoản? 
                            <a href="dangnhap.php" class="auth-link">Đăng nhập ngay</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../thanhphan/footer.php'; ?>