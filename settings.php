<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Cài đặt';
include 'includes/header.php';
include 'includes/navbar.php';

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h4 class="mb-4">
                <i class="fas fa-cog"></i> Cài đặt tài khoản
            </h4>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Account Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> Thông tin tài khoản
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên người dùng</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-muted">Tên người dùng không thể thay đổi</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Họ tên</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                            <small class="text-muted">Liên hệ admin để thay đổi họ tên</small>
                        </div>
                    </div>
                    
                    <form action="functions/auth.php" method="POST">
                        <input type="hidden" name="action" value="update_email">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="new_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cập nhật email
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Ngày tham gia</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái xác minh</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $user['is_verified'] ? 'Đã xác minh' : 'Chưa xác minh'; ?>" disabled>
                                <?php if ($user['is_verified']): ?>
                                    <span class="input-group-text text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                <?php else: ?>
                                    <a href="verify.php" class="btn btn-outline-primary">Xác minh ngay</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock"></i> Đổi mật khẩu
                    </h5>
                </div>
                <div class="card-body">
                    <form action="functions/auth.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Profile Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Tùy chỉnh trang cá nhân
                    </h5>
                </div>
                <div class="card-body">
                    <form action="functions/users.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Tiểu sử</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" 
                                      placeholder="Hãy viết vài dòng về bản thân..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Ảnh đại diện</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <small class="text-muted">Chỉ chấp nhận file ảnh. Tối đa 2MB.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Privacy & Security -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt"></i> Quyền riêng tư & Bảo mật
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="private_profile" checked disabled>
                        <label class="form-check-label" for="private_profile">
                            Trang cá nhân công khai
                        </label>
                        <small class="text-muted d-block">Tính năng này đang được phát triển</small>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="email_notifications" checked disabled>
                        <label class="form-check-label" for="email_notifications">
                            Nhận thông báo qua email
                        </label>
                        <small class="text-muted d-block">Tính năng này đang được phát triển</small>
                    </div>
                    
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="fas fa-download"></i> Tải xuống dữ liệu cá nhân
                    </button>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Vùng nguy hiểm
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Các hành động sau đây không thể hoàn tác. Vui lòng cân nhắc kỹ trước khi thực hiện.</p>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger" onclick="deactivateAccount()">
                            <i class="fas fa-user-times"></i> Vô hiệu hóa tài khoản tạm thời
                        </button>
                        
                        <button class="btn btn-danger" onclick="deleteAccount()">
                            <i class="fas fa-trash"></i> Xóa tài khoản vĩnh viễn
                        </button>
                        
                        <a href="functions/auth.php?action=logout" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deactivateAccount() {
    if (confirm('Bạn có chắc chắn muốn vô hiệu hóa tài khoản? Bạn có thể kích hoạt lại bằng cách đăng nhập.')) {
        alert('Tính năng này đang được phát triển');
    }
}

function deleteAccount() {
    const confirmation = prompt('Để xác nhận xóa tài khoản, vui lòng nhập "XOA TAI KHOAN":');
    if (confirmation === 'XOA TAI KHOAN') {
        alert('Tính năng này đang được phát triển');
    } else if (confirmation !== null) {
        alert('Xác nhận không chính xác');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strength = document.getElementById('passwordStrength');
    
    if (!strength) {
        const div = document.createElement('div');
        div.id = 'passwordStrength';
        div.className = 'mt-1';
        this.parentNode.appendChild(div);
    }
    
    let score = 0;
    let feedback = [];
    
    if (password.length >= 8) score++;
    else feedback.push('Ít nhất 8 ký tự');
    
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('Có chữ hoa');
    
    if (/[a-z]/.test(password)) score++;
    else feedback.push('Có chữ thường');
    
    if (/[0-9]/.test(password)) score++;
    else feedback.push('Có số');
    
    if (/[^A-Za-z0-9]/.test(password)) score++;
    else feedback.push('Có ký tự đặc biệt');
    
    const colors = ['danger', 'warning', 'info', 'success', 'success'];
    const texts = ['Rất yếu', 'Yếu', 'Trung bình', 'Mạnh', 'Rất mạnh'];
    
    document.getElementById('passwordStrength').innerHTML = `
        <small class="text-${colors[score]}">
            Độ mạnh: ${texts[score]} ${feedback.length > 0 ? '- Cần: ' + feedback.join(', ') : ''}
        </small>
    `;
});

// Confirm password match
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    
    if (confirm && password !== confirm) {
        this.setCustomValidity('Mật khẩu xác nhận không khớp');
        this.className = 'form-control is-invalid';
    } else {
        this.setCustomValidity('');
        this.className = 'form-control is-valid';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
