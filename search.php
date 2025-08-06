<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Tìm kiếm';
include 'includes/header.php';
include 'includes/navbar.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_results = [];
$hot_searches = [];

// Get hot searches
$stmt = $pdo->prepare("SELECT keyword, search_count FROM hot_searches ORDER BY search_count DESC LIMIT 10");
$stmt->execute();
$hot_searches = $stmt->fetchAll();

if (!empty($search_query)) {
    // Update or insert search keyword
    $stmt = $pdo->prepare("INSERT INTO hot_searches (keyword) VALUES (?) ON DUPLICATE KEY UPDATE search_count = search_count + 1, updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$search_query]);
    
    $search_term = "%$search_query%";
    
    // Search users
    $stmt = $pdo->prepare("
        SELECT 'user' as type, id, username, full_name, avatar, is_verified, NULL as content, NULL as created_at 
        FROM users 
        WHERE username LIKE ? OR full_name LIKE ? 
        LIMIT 10
    ");
    $stmt->execute([$search_term, $search_term]);
    $user_results = $stmt->fetchAll();
    
    // Search posts
    $stmt = $pdo->prepare("
        SELECT 'post' as type, p.id, u.username, u.full_name, u.avatar, u.is_verified, p.content, p.created_at
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.content LIKE ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$search_term]);
    $post_results = $stmt->fetchAll();
    
    // Search hashtags
    if (strpos($search_query, '#') === 0) {
        $hashtag = substr($search_query, 1);
        $stmt = $pdo->prepare("
            SELECT 'hashtag' as type, h.id, h.tag, h.usage_count, NULL as username, NULL as full_name, NULL as avatar, NULL as is_verified, NULL as content, NULL as created_at
            FROM hashtags h 
            WHERE h.tag LIKE ? 
            ORDER BY h.usage_count DESC 
            LIMIT 10
        ");
        $stmt->execute(["%$hashtag%"]);
        $hashtag_results = $stmt->fetchAll();
        
        // Get posts with this hashtag
        $stmt = $pdo->prepare("
            SELECT 'post' as type, p.id, u.username, u.full_name, u.avatar, u.is_verified, p.content, p.created_at
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            JOIN post_hashtags ph ON p.id = ph.post_id
            JOIN hashtags h ON ph.hashtag_id = h.id
            WHERE h.tag LIKE ? 
            ORDER BY p.created_at DESC 
            LIMIT 20
        ");
        $stmt->execute(["%$hashtag%"]);
        $hashtag_posts = $stmt->fetchAll();
        
        $search_results = array_merge($hashtag_results, $hashtag_posts);
    } else {
        $search_results = array_merge($user_results, $post_results);
    }
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
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-3">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </h4>
                    
                    <form method="GET" action="">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" 
                                   placeholder="Tìm kiếm người dùng, bài viết, hashtag..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($hot_searches)): ?>
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Tìm kiếm phổ biến:</h6>
                        <?php foreach ($hot_searches as $hot): ?>
                            <a href="search.php?q=<?php echo urlencode($hot['keyword']); ?>" class="hot-search-item">
                                <?php echo htmlspecialchars($hot['keyword']); ?>
                                <span class="badge bg-secondary"><?php echo $hot['search_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($search_query)): ?>
                <h5 class="mb-3">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search_query); ?>"</h5>
                
                <?php if (empty($search_results)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>Không tìm thấy kết quả</h5>
                        <p class="text-muted">Hãy thử với từ khóa khác.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($search_results as $result): ?>
                        <div class="card search-result">
                            <?php if ($result['type'] == 'user'): ?>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $result['avatar'] ? $result['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                         alt="Avatar" class="avatar me-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($result['full_name']); ?></h6>
                                            <?php if ($result['is_verified']): ?>
                                                <i class="fas fa-check-circle verified-badge"></i>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-muted mb-0">@<?php echo htmlspecialchars($result['username']); ?></p>
                                    </div>
                                    <div>
                                        <?php if ($result['id'] != $_SESSION['user_id']): ?>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
                                            $stmt->execute([$_SESSION['user_id'], $result['id']]);
                                            $is_following = $stmt->fetch();
                                            ?>
                                            <button class="btn btn-<?php echo $is_following ? 'secondary' : 'primary'; ?> btn-sm"
                                                    onclick="toggleFollow(<?php echo $result['id']; ?>, this)">
                                                <?php echo $is_following ? 'Đang theo dõi' : 'Theo dõi'; ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="profile.php?user=<?php echo $result['username']; ?>" class="btn btn-outline-primary btn-sm">
                                            Xem trang cá nhân
                                        </a>
                                    </div>
                                </div>
                            
                            <?php elseif ($result['type'] == 'post'): ?>
                                <div class="d-flex">
                                    <img src="<?php echo $result['avatar'] ? $result['avatar'] : 'assets/images/default-avatar.jpg'; ?>" 
                                         alt="Avatar" class="avatar me-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($result['full_name']); ?></h6>
                                            <?php if ($result['is_verified']): ?>
                                                <i class="fas fa-check-circle verified-badge"></i>
                                            <?php endif; ?>
                                            <small class="text-muted ms-2">@<?php echo htmlspecialchars($result['username']); ?> • <?php echo timeAgo($result['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars(substr($result['content'], 0, 200))); ?>...</p>
                                    </div>
                                </div>
                            
                            <?php elseif ($result['type'] == 'hashtag'): ?>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="mb-0">
                                            <a href="search.php?q=%23<?php echo urlencode($result['tag']); ?>" class="hashtag">
                                                #<?php echo htmlspecialchars($result['tag']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted"><?php echo $result['usage_count']; ?> bài viết</small>
                                    </div>
                                    <i class="fas fa-hashtag fa-2x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            button.textContent = 'Đang theo dõi';
            button.className = 'btn btn-secondary btn-sm';
        } else if (data.status === 'unfollowed') {
            button.textContent = 'Theo dõi';
            button.className = 'btn btn-primary btn-sm';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
