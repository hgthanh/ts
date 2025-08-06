<?php
require_once '../config/database.php';

function extractHashtags($content) {
    preg_match_all('/#([a-zA-Z0-9_\p{L}]+)/u', $content, $matches);
    return $matches[1];
}

function insertHashtags($post_id, $hashtags, $pdo) {
    foreach ($hashtags as $tag) {
        // Insert or update hashtag
        $stmt = $pdo->prepare("INSERT INTO hashtags (tag) VALUES (?) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([strtolower($tag)]);
        
        // Get hashtag ID
        $stmt = $pdo->prepare("SELECT id FROM hashtags WHERE tag = ?");
        $stmt->execute([strtolower($tag)]);
        $hashtag_id = $stmt->fetchColumn();
        
        // Link post with hashtag
        $stmt = $pdo->prepare("INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $hashtag_id]);
    }
}

if ($_POST['action'] == 'create_post') {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($content)) {
        header('Location: ../newpost.php?error=Nội dung bài viết không được để trống');
        exit;
    }
    
    $image_path = null;
    $audio_path = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // 5MB limit
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/images/' . $new_filename;
            }
        }
    }
    
    // Handle audio upload
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] == 0) {
        $allowed_types = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
        $file_type = $_FILES['audio']['type'];
        $file_size = $_FILES['audio']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 10000000) { // 10MB limit
            $file_extension = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/audio/' . $new_filename;
            
            if (move_uploaded_file($_FILES['audio']['tmp_name'], $upload_path)) {
                $audio_path = 'uploads/audio/' . $new_filename;
            }
        }
    }
    
    // Insert post
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_path, audio_path) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$user_id, $content, $image_path, $audio_path])) {
        $post_id = $pdo->lastInsertId();
        
        // Extract and insert hashtags
        $hashtags = extractHashtags($content);
        if (!empty($hashtags)) {
            insertHashtags($post_id, $hashtags, $pdo);
        }
        
        header('Location: ../index.php?success=Đăng bài thành công');
        exit;
    } else {
        header('Location: ../newpost.php?error=Có lỗi xảy ra khi đăng bài');
        exit;
    }
}

if ($_POST['action'] == 'like_post') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    
    if ($stmt->fetch()) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        echo json_encode(['status' => 'unliked']);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Create notification for post owner
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post_owner = $stmt->fetchColumn();
        
        if ($post_owner != $user_id) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $username = $stmt->fetchColumn();
            
            $message = "$username đã thích bài viết của bạn";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'like', ?)");
            $stmt->execute([$post_owner, $message]);
        }
        
        echo json_encode(['status' => 'liked']);
    }
    exit;
}

if ($_POST['action'] == 'add_comment') {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($content)) {
        header('Location: ../index.php?error=Nội dung bình luận không được để trống');
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$post_id, $user_id, $content])) {
        // Create notification for post owner
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post_owner = $stmt->fetchColumn();
        
        if ($post_owner != $user_id) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $username = $stmt->fetchColumn();
            
            $message = "$username đã bình luận về bài viết của bạn";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'comment', ?)");
            $stmt->execute([$post_owner, $message]);
        }
        
        header('Location: ../index.php?success=Bình luận thành công');
    } else {
        header('Location: ../index.php?error=Có lỗi xảy ra');
    }
    exit;
}

if ($_POST['action'] == 'follow_user') {
    $following_id = $_POST['user_id'];
    $follower_id = $_SESSION['user_id'];
    
    if ($following_id == $follower_id) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể follow chính mình']);
        exit;
    }
    
    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $following_id]);
    
    if ($stmt->fetch()) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        echo json_encode(['status' => 'unfollowed']);
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        
        // Create notification
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$follower_id]);
        $username = $stmt->fetchColumn();
        
        $message = "$username đã bắt đầu theo dõi bạn";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'follow', ?)");
        $stmt->execute([$following_id, $message]);
        
        echo json_encode(['status' => 'followed']);
    }
    exit;
}
?>
