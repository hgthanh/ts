<?php
require_once '../config/database.php';

if ($_GET['action'] == 'logout') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

if ($_POST['action'] == 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        header('Location: ../login.php?error=Vui lòng điền đầy đủ thông tin');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['is_verified'] = $user['is_verified'];
        
        header('Location: ../index.php');
        exit;
    } else {
        header('Location: ../login.php?error=Email hoặc mật khẩu không đúng');
        exit;
    }
}

if ($_POST['action'] == 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        header('Location: ../register.php?error=Vui lòng điền đầy đủ thông tin');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: ../register.php?error=Mật khẩu xác nhận không khớp');
        exit;
    }
    
    if (strlen($password) < 6) {
        header('Location: ../register.php?error=Mật khẩu phải có ít nhất 6 ký tự');
        exit;
    }
    
    // Check existing user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        header('Location: ../register.php?error=Email hoặc username đã tồn tại');
        exit;
    }
    
    // Create user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
        header('Location: ../login.php?success=Đăng ký thành công! Vui lòng đăng nhập');
        exit;
    } else {
        header('Location: ../register.php?error=Có lỗi xảy ra, vui lòng thử lại');
        exit;
    }
}

if ($_POST['action'] == 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        header('Location: ../settings.php?error=Mật khẩu mới không khớp');
        exit;
    }
    
    if (strlen($new_password) < 6) {
        header('Location: ../settings.php?error=Mật khẩu mới phải có ít nhất 6 ký tự');
        exit;
    }
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password'])) {
        header('Location: ../settings.php?error=Mật khẩu hiện tại không đúng');
        exit;
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
        header('Location: ../settings.php?success=Đổi mật khẩu thành công');
        exit;
    } else {
        header('Location: ../settings.php?error=Có lỗi xảy ra');
        exit;
    }
}

if ($_POST['action'] == 'update_email') {
    $new_email = trim($_POST['new_email']);
    
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../settings.php?error=Email không hợp lệ');
        exit;
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$new_email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        header('Location: ../settings.php?error=Email đã được sử dụng');
        exit;
    }
    
    // Update email
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    if ($stmt->execute([$new_email, $_SESSION['user_id']])) {
        $_SESSION['email'] = $new_email;
        header('Location: ../settings.php?success=Cập nhật email thành công');
        exit;
    } else {
        header('Location: ../settings.php?error=Có lỗi xảy ra');
        exit;
    }
}
?>
