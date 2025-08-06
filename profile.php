<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$username = isset($_GET['user']) ? $_GET['user'] : $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?error=Người dùng không tồn tại');
    exit;
}

$is_own_profile = ($user['id'] == $_SESSION['user_id']);

// Get user stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$user['id']]);
$posts_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$user['id']]);
$followers_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$user['id']]);
$following_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(likes_count) FROM posts WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_likes = $stmt->fetchColumn() ?? 0;

// Check if current user is following this user
$is_following = false;
if (!$is_own_profile) {
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user['id']]);
    $is_following = $stmt->fetch();
}

// Get user posts
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) as user_liked
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $user['id']]);
$user_posts = $stmt->fetchAll();

$page_title = htmlspecialchars($user['full_name']);
include 'includes/header.php';
include 'includes/navbar.php';

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    return date('d/m/Y', strtotime($datetime));
}

function formatHashtags($content) {
    return preg_replace('/#([a-zA-Z0-9_\p{L}]+)/u', '<a href="search.php?q=%23$1" class="hashtag">#$1</a>', $content);
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Profile Header -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?php echo $user['avatar'] ? $user['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                 alt="Avatar" class="avatar-large mb-3">
                            <?php if ($is_own_profile): ?>
                                <button class="btn btn-outline-primary btn-sm d-block mx-auto" onclick="changeAvatar()">
                                    <i class="fas fa-camera"></i> Đổi ảnh đại diện
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <?php if ($user['is_verified']): ?>
                                    <i class="fas fa-check-circle verified-badge ms-2" style="font-size: 24px;"></i>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                            
                            <?php if ($user['bio']): ?>
                                <p class="mb-3"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <?php endif; ?>
                            
                            <div class="row text-center mb-3">
                                <div class="col-3">
                                    <h5 class="fw-bold text-primary"><?php echo $posts_count; ?></h5>
                                    <small class="text-muted">Bài viết</small>
                                </div>
                                <div class="col-3">
                                    <h5 class="fw-bold text-primary"><?php echo $followers_count; ?></h5>
                                    <small class="text-muted">Người theo dõi</small>
                                </div>
                                <div class="col-3">
                                    <h5 class="fw-bold text-primary"><?php echo $following_count; ?></h5>
                                    <small class="text-muted">Đang theo dõi</small>
                                </div>
                                <div class="col-3">
                                    <h5 class="fw-bold text-primary"><?php echo $total_likes; ?></h5>
                                    <small class="text-muted">Lượt thích</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-2">
                                <?php if ($is_own_profile): ?>
                                    <a href="settings.php" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i> Chỉnh sửa trang cá nhân
                                    </a>
                                    <a href="newpost.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Đăng bài mới
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-<?php echo $is_following ? 'secondary' : 'primary'; ?>" 
                                            onclick="toggleFollow(<?php echo $user['id']; ?>, this)">
                                        <i class="fas fa-<?php echo $is_following ? 'user-minus' : 'user-plus'; ?>"></i>
                                        <?php echo $is_following ? 'Bỏ theo dõi' : 'Theo dõi'; ?>
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="sendMessage(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-envelope"></i> Nhắn tin
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Posts Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold">
                    <i class="fas fa-newspaper"></i> 
                    <?php echo $is_own_profile ? 'Bài viết của bạn' : 'Bài viết của ' . htmlspecialchars($user['full_name']); ?>
                </h5>
                <span class="badge bg-secondary"><?php echo $posts_count; ?> bài viết</span>
            </div>
            
            <?php if (empty($user_posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h5>Chưa có bài viết nào</h5>
                    <?php if ($is_own_profile): ?>
                        <p class="text-muted">Hãy chia sẻ khoảnh khắc đầu tiên của bạn!</p>
                        <a href="newpost.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Đăng bài ngay
                        </a>
                    <?php else: ?>
                        <p class="text-muted"><?php echo htmlspecialchars($user['full_name']); ?> chưa đăng bài viết nào.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($user_posts as $post): ?>
                    <div class="card post-card">
                        <div class="post-header">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $user['avatar'] ? $user['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Avatar" class="avatar me-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                        <?php if ($user['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-badge"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?> • <?php echo timeAgo($post['created_at']); ?></small>
                                </div>
                                <?php if ($is_own_profile || $_SESSION['is_admin']): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deletePost(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-trash"></i> Xóa bài viết
                                        </a></li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <p class="mb-2"><?php echo formatHashtags(nl2br(htmlspecialchars($post['content']))); ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" class="post-image img-fluid">
                            <?php endif; ?>
                            
                            <?php if ($post['audio_path']): ?>
                                <audio controls class="w-100 mt-2">
                                    <source src="<?php echo htmlspecialchars($post['audio_path']); ?>" type="audio/mpeg">
                                    Trình duyệt của bạn không hỗ trợ audio.
                                </audio>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-actions">
                            <div class="d-flex align-items-center">
                                <button class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                    <i class="fas fa-heart"></i> <?php echo $post['likes_count']; ?>
                                </button>
                                
                                <button class="comment-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-comment"></i> Bình luận
                                </button>
                                
                                <button class="comment-btn" onclick="sharePost(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-share"></i> Chia sẻ
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleFollow(userId, button) {
    fetch('functions/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=follow_user&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'followed') {
            button.innerHTML = '<i class="fas fa-user-minus"></i> Bỏ theo dõi';
            button.className = 'btn btn-secondary';
        } else if (data.status === 'unfollowed') {
            button.innerHTML = '<i class="fas fa-user-plus"></i> Theo dõi';
            button.className = 'btn btn-primary';
        }
        // Reload to update follower count
        setTimeout(() => location.reload(), 1000);
    });
}

function toggleLike(postId, button) {
    fetch('functions/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=like_post&post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'liked') {
            button.classList.add('liked');
        } else {
            button.classList.remove('liked');
        }
        location.reload();
    });
}

function toggleComments(postId) {
    // Add comments functionality
    alert('Tính năng bình luận đang được phát triển');
}

function sharePost(postId) {
    const url = `${window.location.origin}/profile.php?user=<?php echo $user['username']; ?>`;
    navigator.clipboard.writeText(url).then(() => {
        alert('Đã sao chép link trang cá nhân!');
    });
}

function deletePost(postId) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
        alert('Tính năng xóa bài viết đang được phát triển');
    }
}

function changeAvatar() {
    alert('Tính năng đổi ảnh đại diện đang được phát triển');
}

function sendMessage(userId) {
    alert('Tính năng nhắn tin đang được phát triển');
}
</script>

<?php include 'includes/footer.php'; ?>
