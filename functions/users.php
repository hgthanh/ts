<?php
require_once '../config/database.php';
require_once 'notifications.php';

// Toggle follow/unfollow
if ($_POST['action'] == 'toggle_follow') {
    $user_id = $_POST['user_id'];
    $follower_id = $_SESSION['user_id'];
    
    if ($user_id == $follower_id) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể theo dõi chính mình']);
        exit;
    }
    
    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        if ($stmt->execute([$follower_id, $user_id])) {
            echo json_encode(['status' => 'unfollowed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra']);
        }
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        if ($stmt->execute([$follower_id, $user_id])) {
            // Send notification
            notifyFollow($follower_id, $user_id);
            echo json_encode(['status' => 'following']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra']);
        }
    }
    exit;
}

// Update profile
if ($_POST['action'] == 'update_profile') {
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($full_name)) {
        header('Location: ../settings.php?error=Tên không được để trống');
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $bio, $user_id])) {
        $_SESSION['full_name'] = $full_name;
        header('Location: ../settings.php?success=Cập nhật thông tin thành công');
    } else {
        header('Location: ../settings.php?error=Có lỗi xảy ra');
    }
    exit;
}

// Update avatar
if ($_POST['action'] == 'update_avatar') {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            header('Location: ../settings.php?error=Chỉ chấp nhận file ảnh (JPG, PNG, GIF)');
            exit;
        }
        
        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) { // 5MB
            header('Location: ../settings.php?error=File ảnh quá lớn (tối đa 5MB)');
            exit;
        }
        
        $upload_dir = '../uploads/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
            // Delete old avatar if exists
            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user['avatar'] && $user['avatar'] != 'default-avatar.jpg' && file_exists('../' . $user['avatar'])) {
                unlink('../' . $user['avatar']);
            }
            
            // Update database
            $avatar_path = 'uploads/images/' . $new_filename;
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            if ($stmt->execute([$avatar_path, $user_id])) {
                header('Location: ../settings.php?success=Cập nhật ảnh đại diện thành công');
            } else {
                header('Location: ../settings.php?error=Có lỗi xảy ra khi cập nhật database');
            }
        } else {
            header('Location: ../settings.php?error=Có lỗi xảy ra khi upload file');
        }
    } else {
        header('Location: ../settings.php?error=Vui lòng chọn file ảnh');
    }
    exit;
}

// Get user profile
if ($_GET['action'] == 'get_profile') {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Username không hợp lệ']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
               (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
               (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM users u 
        WHERE u.username = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng']);
    }
    exit;
}

// Get user's posts
if ($_GET['action'] == 'get_user_posts') {
    $user_id = $_GET['user_id'] ?? 0;
    $page = $_GET['page'] ?? 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.full_name, u.avatar, u.is_verified,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) as user_liked
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $limit, $offset]);
    $posts = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'posts' => $posts]);
    exit;
}

// Search users
if ($_GET['action'] == 'search_users') {
    $query = $_GET['q'] ?? '';
    $limit = $_GET['limit'] ?? 20;
    
    if (strlen($query) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Từ khóa tìm kiếm quá ngắn']);
        exit;
    }
    
    $search_term = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, u.is_verified, u.bio,
               (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM users u 
        WHERE (u.username LIKE ? OR u.full_name LIKE ?) AND u.id != ?
        ORDER BY 
            CASE WHEN u.is_verified = 1 THEN 0 ELSE 1 END,
            followers_count DESC
        LIMIT ?
    ");
    $stmt->execute([$_SESSION['user_id'], $search_term, $search_term, $_SESSION['user_id'], $limit]);
    $users = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'users' => $users]);
    exit;
}

// Get followers
if ($_GET['action'] == 'get_followers') {
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
    $page = $_GET['page'] ?? 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, u.is_verified, u.bio,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM users u 
        JOIN follows f ON u.id = f.follower_id 
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $limit, $offset]);
    $followers = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'followers' => $followers]);
    exit;
}

// Get following
if ($_GET['action'] == 'get_following') {
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
    $page = $_GET['page'] ?? 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, u.is_verified, u.bio,
               (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM users u 
        JOIN follows f ON u.id = f.following_id 
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $limit, $offset]);
    $following = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'following' => $following]);
    exit;
}

// Block user
if ($_POST['action'] == 'block_user') {
    $user_id = $_POST['user_id'];
    $blocker_id = $_SESSION['user_id'];
    
    if ($user_id == $blocker_id) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể chặn chính mình']);
        exit;
    }
    
    // Remove follow relationships
    $stmt = $pdo->prepare("DELETE FROM follows WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)");
    $stmt->execute([$blocker_id, $user_id, $user_id, $blocker_id]);
    
    echo json_encode(['status' => 'success', 'message' => 'Đã chặn người dùng']);
    exit;
}

// Request verification
if ($_POST['action'] == 'request_verification') {
    $user_id = $_SESSION['user_id'];
    
    // Check if already verified
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user['is_verified']) {
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản đã được xác minh']);
        exit;
    }
    
    // Create notification for admin
    $message = "Người dùng " . $_SESSION['full_name'] . " yêu cầu xác minh tài khoản";
    createNotification(1, 'verify', $message); // Assuming admin has ID 1
    
    echo json_encode(['status' => 'success', 'message' => 'Đã gửi yêu cầu xác minh']);
    exit;
}
?>

