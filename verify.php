<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Xác minh tài khoản';
include 'includes/header.php';
include 'includes/navbar.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_verified = $stmt->fetchColumn();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($is_verified): ?>
                        <div class="py-5">
                            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                            <h3 class="text-success mb-3">Tài khoản đã được xác minh!</h3>
                            <p class="text-muted mb-4">
                                Chúc mừng! Tài khoản của bạn đã được xác minh và có dấu tick xanh.
                                Bạn có thể tận hưởng tất cả các tính năng của Thazh Social.
                            </p>
                            <a href="profile.php" class="btn btn-primary">
                                <i class="fas fa-user"></i> Xem trang cá nhân
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="py-4">
                            <i class="fas fa-shield-alt fa-5x text-primary mb-4"></i>
                            <h3 class="mb-3">Xác minh tài khoản</h3>
                            <p class="text-muted mb-4">
                                Xác minh tài khoản để nhận dấu tick xanh và được cộng đồng tin tưởng hơn.
                                Tài khoản đã xác minh sẽ có độ uy tín cao hơn và xuất hiện trong kết quả tìm kiếm.
                            </p>
                            
                            <div class="alert alert-info text-start">
                                <h6><i class="fas fa-info-circle"></i> Quy trình xác minh:</h6>
                                <ol class="mb-0">
                                    <li>Điền form bên dưới với thông tin chính xác</li>
                                    <li>Gửi email đến <strong>verify@thazh.is-a.dev</strong></li>
                                    <li>Đợi admin xem xét (1-3 ngày làm việc)</li>
                                    <li>Nhận thông báo kết quả qua hệ thống</li>
                                </ol>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Form xác minh tài khoản</h5>
                                </div>
                                <div class="card-body text-start">
                                    <form id="verifyForm">
                                        <div class="mb-3">
                                            <label for="fullName" class="form-label">Họ tên đầy đủ *</label>
                                            <input type="text" class="form-control" id="fullName" required 
                                                   value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Tên người dùng *</label>
                                            <input type="text" class="form-control" id="username" required 
                                                   value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" required 
                                                   value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Lý do xác minh *</label>
                                            <select class="form-select" id="reason" required>
                                                <option value="">Chọn lý do</option>
                                                <option value="personal">Tài khoản cá nhân</option>
                                                <option value="celebrity">Người nổi tiếng</option>
                                                <option value="business">Doanh nghiệp/Thương hiệu</option>
                                                <option value="organization">Tổ chức</option>
                                                <option value="media">Báo chí/Truyền thông</option>
                                                <option value="government">Cơ quan chính phủ</option>
                                                <option value="other">Khác</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Mô tả chi tiết *</label>
                                            <textarea class="form-control" id="description" rows="4" required
                                                      placeholder="Mô tả về bản thân, lý do muốn xác minh, thành tích nổi bật..."></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="socialLinks" class="form-label">Liên kết mạng xã hội khác (tùy chọn)</label>
                                            <textarea class="form-control" id="socialLinks" rows="3"
                                                      placeholder="Facebook: https://facebook.com/username&#10;Instagram: https://instagram.com/username&#10;Website: https://example.com"></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="idNumber" class="form-label">Số CMND/CCCD (tùy chọn)</label>
                                            <input type="text" class="form-control" id="idNumber"
                                                   placeholder="Chỉ cần thiết cho một số trường hợp đặc biệt">
                                            <small class="text-muted">Thông tin này sẽ được bảo mật tuyệt đối</small>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                            <label class="form-check-label" for="agreeTerms">
                                                Tôi xác nhận rằng tất cả thông tin trên là chính xác và đồng ý với 
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">điều khoản xác minh</a>
                                            </label>
                                        </div>
                                        
                                        <button type="button" class="btn btn-primary" onclick="generateEmail()">
                                            <i class="fas fa-envelope"></i> Tạo email xác minh
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Lưu ý quan trọng:</h6>
                                <ul class="text-muted text-start">
                                    <li>Chỉ các tài khoản có ảnh hưởng thực sự mới được xác minh</li>
                                    <li>Thông tin gian dối sẽ dẫn đến từ chối xác minh vĩnh viễn</li>
                                    <li>Quá trình xem xét có thể mất 1-3 ngày làm việc</li>
                                    <li>Admin có quyền từ chối mà không cần lý do</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Điều khoản xác minh tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Điều kiện xác minh:</h6>
                <ul>
                    <li>Tài khoản phải hoạt động tích cực ít nhất 30 ngày</li>
                    <li>Có ít nhất 100 người theo dõi</li>
                    <li>Không vi phạm quy tắc cộng đồng</li>
                    <li>Thông tin tài khoản phải chính xác và đầy đủ</li>
                </ul>
                
                <h6>2. Quy trình xem xét:</h6>
                <ul>
                    <li>Admin sẽ xem xét dựa trên thông tin cung cấp</li>
                    <li>Có thể yêu cầu bổ sung tài liệu chứng minh</li>
                    <li>Quyết định của admin là cuối cùng</li>
                </ul>
                
                <h6>3. Quyền lợi khi được xác minh:</h6>
                <ul>
                    <li>Dấu tick xanh bên cạnh tên</li>
                    <li>Ưu tiên hiển thị trong tìm kiếm</li>
                    <li>Độ tin cậy cao hơn từ cộng đồng</li>
                </ul>
                
                <h6>4. Trách nhiệm:</h6>
                <ul>
                    <li>Duy trì hoạt động tích cực</li>
                    <li>Không vi phạm quy tắc cộng đồng</li>
                    <li>Thông tin cập nhật phải chính xác</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function generateEmail() {
    const form = document.getElementById('verifyForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    if (!document.getElementById('agreeTerms').checked) {
        alert('Vui lòng đồng ý với điều khoản xác minh');
        return;
    }
    
    // Get form values
    const fullName = document.getElementById('fullName').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const reason = document.getElementById('reason').value;
    const description = document.getElementById('description').value;
    const socialLinks = document.getElementById('socialLinks').value;
    const idNumber = document.getElementById('idNumber').value;
    
    // Generate email content
    const emailSubject = `Yêu cầu xác minh tài khoản - ${username}`;
    const emailBody = `Chào admin Thazh Social,

Tôi xin gửi yêu cầu xác minh tài khoản với thông tin sau:

THÔNG TIN CÁ NHÂN:
- Họ tên: ${fullName}
- Username: ${username}
- Email: ${email}

THÔNG TIN XÁC MINH:
- Lý do xác minh: ${document.querySelector('#reason option:checked').text}
- Mô tả chi tiết: ${description}

${socialLinks ? `LIÊN KẾT MẠNG XÃ HỘI:\n${socialLinks}\n` : ''}
${idNumber ? `SỐ
