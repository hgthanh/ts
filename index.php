<?php
require_once 'config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Trang chủ';
include 'includes/header.php';
include 'includes/navbar.php';

// Get all posts with user info
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.full_name, u.avatar, u.is_verified,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) as user_liked
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll();

function formatHashtags($content) {
    return preg_replace('/#([a-zA-Z0-9_\p{L}]+)/u', '<a href="search.php?q=%23$1" class="hashtag">#$1</a>', $content);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    return date('d/m/Y', strtotime($datetime));
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
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
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4>Chưa có bài viết nào</h4>
                    <p class="text-muted">Hãy bắt đầu đăng bài viết đầu tiên của bạn!</p>
                    <a href="newpost.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Đăng bài ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card post-card">
                        <div class="post-header">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $post['avatar'] ? $post['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Avatar" class="avatar me-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($post['full_name']); ?></h6>
                                        <?php if ($post['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-badge"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo timeAgo($post['created_at']); ?></small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if ($post['user_id'] == $_SESSION['user_id'] || $_SESSION['is_admin']): ?>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deletePost(<?php echo $post['id']; ?>)">
                                                <i class="fas fa-trash"></i> Xóa bài viết
                                            </a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item" href="#" onclick="reportPost(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-flag"></i> Báo cáo
                                        </a></li>
                                    </ul>
                                </div>
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
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
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
                            
                            <!-- Comments section -->
                            <div id="comments-<?php echo $post['id']; ?>" style="display: none;" class="mt-3">
                                <hr>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT c.*, u.username, u.full_name, u.avatar, u.is_verified
                                    FROM comments c 
                                    JOIN users u ON c.user_id = u.id 
                                    WHERE c.post_id = ? 
                                    ORDER BY c.created_at ASC
                                ");
                                $stmt->execute([$post['id']]);
                                $comments = $stmt->fetchAll();
                                ?>
                                
                                <?php foreach ($comments as $comment): ?>
                                    <div class="d-flex mb-2">
                                        <img src="<?php echo $comment['avatar'] ? $comment['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                             alt="Avatar" class="avatar me-2" style="width: 30px; height: 30px;">
                                        <div class="flex-grow-1">
                                            <div class="bg-light rounded p-2">
                                                <div class="d-flex align-items-center mb-1">
                                                    <strong class="me-2"><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                    <?php if ($comment['is_verified']): ?>
                                                        <i class="fas fa-check-circle verified-badge" style="font-size: 12px;"></i>
                                                    <?php endif; ?>
                                                    <small class="text-muted ms-auto"><?php echo timeAgo($comment['created_at']); ?></small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <form action="functions/posts.php" method="POST" class="d-flex mt-3">
                                    <input type="hidden" name="action" value="add_comment">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="text" class="form-control me-2" name="content" 
                                           placeholder="Viết bình luận..." required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
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
        // Update like count
        location.reload();
    });
}

function toggleComments(postId) {
    const commentsDiv = document.getElementById(`comments-${postId}`);
    commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
}

function sharePost(postId) {
    const url = `${window.location.origin}/post.php?id=${postId}`;
    navigator.clipboard.writeText(url).then(() => {
        alert('Đã sao chép link bài viết!');
    });
}

function deletePost(postId) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
        // Add delete functionality
        alert('Tính năng đang được phát triển');
    }
}

function reportPost(postId) {
    if (confirm('Báo cáo bài viết này?')) {
        alert('Cảm ơn bạn đã báo cáo. Chúng tôi sẽ xem xét.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
