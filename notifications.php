<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Thông báo';
include 'includes/header.php';
include 'includes/navbar.php';

// Mark all notifications as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}

// Get all notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    return date('d/m/Y', strtotime($datetime));
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'follow': return 'fas fa-user-plus text-primary';
        case 'like': return 'fas fa-heart text-danger';
        case 'comment': return 'fas fa-comment text-info';
        case 'verify': return 'fas fa-check-circle text-success';
        case 'warning': return 'fas fa-exclamation-triangle text-warning';
        default: return 'fas fa-bell text-secondary';
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>
                    <i class="fas fa-bell"></i> Thông báo
                </h4>
                <?php if (!empty($notifications)): ?>
                    <a href="notifications.php?mark_all_read=1" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-check-double"></i> Đánh dấu đã đọc tất cả
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5>Không có thông báo nào</h5>
                    <p class="text-muted">Các thông báo sẽ xuất hiện ở đây khi có hoạt động mới.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="<?php echo getNotificationIcon($notification['type']); ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                </small>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <div class="ms-2">
                                    <span class="badge bg-primary rounded-pill">Mới</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Hiển thị 50 thông báo gần nhất
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
