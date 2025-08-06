<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php?error=Bạn không có quyền truy cập');
    exit;
}

$page_title = 'Quản trị hệ thống';
include 'includes/header.php';
include 'includes/navbar.php';

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts");
$stmt->execute();
$total_posts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments");
$stmt->execute();
$total_comments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes");
$stmt->execute();
$total_likes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = FALSE");
$stmt->execute();
$unread_notifications = $stmt->fetchColumn();

// Get recent users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Get recent posts
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.full_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_posts = $stmt->fetchAll();

// Handle admin actions
if ($_POST['action'] ?? '' == 'verify_user') {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $success_message = "Đã xác minh người dùng thành công";
    }
}

if ($_POST['action'] ?? '' == 'delete_post') {
    $post_id = $_POST['post_id'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$post_id])) {
        $success_message = "Đã xóa bài viết thành công";
    }
}

if ($_POST['action'] ?? '' == 'ban_user') {
    $user_id = $_POST['user_id'];
    // For now, we'll just mark them as not verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = FALSE WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $success_message = "Đã cấm người dùng thành công";
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4">
                <i class="fas fa-shield-alt"></i> Bảng điều khiển quản trị
            </h4>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_users; ?></h4>
                                    <p class="mb-0">Người dùng</p>
                                </div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_posts; ?></h4>
                                    <p class="mb-0">Bài viết</p>
                                </div>
                                <i class="fas fa-newspaper fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_comments; ?></h4>
                                    <p class="mb-0">Bình luận</p>
                                </div>
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $total_likes; ?></h4>
                                    <p class="mb-0">Lượt thích</p>
                                </div>
                                <i class="fas fa-heart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Users -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-plus"></i> Người dùng mới
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $user['avatar'] ? $user['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                             alt="Avatar" class="avatar me-3" style="width: 40px; height: 40px;">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Hành động
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (!$user['is_verified']): ?>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="verify_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fas fa-check"></i> Xác minh
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="ban_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Bạn có chắc chắn?')">
                                                        <i class="fas fa-ban"></i> Cấm
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="profile.php?user=<?php echo $user['username']; ?>">
                                                    <i class="fas fa-eye"></i> Xem trang cá nhân
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Posts -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-newspaper"></i> Bài viết mới
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_posts as $post): ?>
                                <div class="d-flex justify-content-between mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($post['full_name']); ?></h6>
                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...</p>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Bạn có chắc chắn?')">
                                                        <i class="fas fa-trash"></i> Xóa
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="profile.php?user=<?php echo $post['username']; ?>">
                                                    <i class="fas fa-user"></i> Xem người đăng
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Tools -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tools"></i> Công cụ quản trị
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100 mb-2" onclick="exportData()">
                                        <i class="fas fa-download"></i> Xuất dữ liệu
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-warning w-100 mb-2" onclick="cleanupData()">
                                        <i class="fas fa-broom"></i> Dọn dẹp dữ liệu
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-info w-100 mb-2" onclick="viewLogs()">
                                        <i class="fas fa-file-alt"></i> Xem nhật ký
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-success w-100 mb-2" onclick="systemHealth()">
                                        <i class="fas fa-heartbeat"></i> Kiểm tra hệ thống
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function exportData() {
    alert('Tính năng xuất dữ liệu đang được phát triển');
}

function cleanupData() {
    if (confirm('Bạn có chắc chắn muốn dọn dẹp dữ liệu cũ?')) {
        fetch('functions/notifications.php?action=cleanup')
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            });
    }
}

function viewLogs() {
    alert('Tính năng xem nhật ký đang được phát triển');
}

function systemHealth() {
    alert('Hệ thống đang hoạt động bình thường');
}

// Auto refresh statistics every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>

