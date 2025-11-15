<?php
session_start();
require_once 'helpers.php';
require_once 'db_connect.php'; 

$error = '';
$username = '';
$email = '';

if (isset($_POST['register_submit'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation cơ bản
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ Tên đăng nhập và Mật khẩu.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // 2. Kiểm tra tên đăng nhập đã tồn tại chưa
        $sql = "SELECT id FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->fetch()) {
            $error = "Tên đăng nhập này đã được sử dụng.";
        } else {
            // 3. Tiến hành Đăng ký
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $email_value = empty($email) ? null : $email; // Email có thể NULL

            $sql = "INSERT INTO users (username, password, email) VALUES (:username, :password, :email)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindValue(':email', $email_value, ($email_value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));

            if ($stmt->execute()) {
                set_flash_message("success", "Đăng ký thành công! Vui lòng Đăng nhập.");
                header("Location: login.php");
                exit();
            } else {
                $error = "Có lỗi xảy ra khi tạo tài khoản.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký Tài khoản mới</title>
    <link rel="icon" type="image/png" href="favicon.png"> 
    <meta name="theme-color" content="#5D3E5D">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        /* MÀU SẮC CHỦ ĐẠO */
        body { font-family: 'Roboto', sans-serif; background-color: #1a1a2e; }
        .full-height { min-height: 100vh; }
        
        /* BACKGROUND VÀ LỚP PHỦ */
        .auth-background { background-image: url('images/log.jpg'); background-size: cover; background-position: center; position: relative; }
        .auth-background::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: -1; }

        /* HIỆU ỨNG CARD (GLASSMORPHISM) */
        .card {
            z-index: 2; position: relative; 
            background-color: rgba(255, 255, 255, 0.15); /* Nền mờ, trong suốt */
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(10px); /* Kính mờ mạnh */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); 
            border-radius: 20px;
        }
        .card-body { color: #f0f0f0; } /* Đảm bảo chữ bên trong Card là màu sáng */

        /* INPUTS */
        .form-control { 
            border-radius: 8px !important; 
            background-color: rgba(255, 255, 255, 0.1) !important; 
            color: #f0f0f0 !important; 
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .form-control:focus { 
            border-color: #007bff; /* Màu xanh neon khi focus */
            box-shadow: 0 0 0 0.15rem rgba(0, 123, 255, 0.4); 
        }

        /* BUTTONS */
        .btn { border-radius: 8px !important; font-weight: 500; }
        .btn-main { background-color: #007bff !important; border-color: #007bff !important; color: white !important; }
        .btn-main:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
        
        /* TEXT */
        .card-title { color: #f0f0f0 !important; font-weight: 700; } /* Đổi màu tiêu đề thành trắng */
        .text-main-link { color: #007bff !important; }
        .text-main-link:hover { color: #0056b3 !important; text-decoration: underline; }
    </style>
</head>
<body class="auth-background d-flex justify-content-center align-items-center full-height">
<div class="container d-flex justify-content-center align-items-center full-height">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-body">
                <h3 class="card-title text-center mb-4"><i class="fas fa-user-plus"></i> Đăng Ký Tài Khoản</h3>
                
                <?php display_flash_message(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập *:</label>
                        <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email (Tùy chọn):</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu *:</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="form-text text-light">Mật khẩu tối thiểu 6 ký tự.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận Mật khẩu *:</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="register_submit" class="btn btn-main btn-block">Đăng Ký</button>
                </form>
                <p class="mt-3 text-center">Đã có tài khoản? <a href="login.php" class="text-main-link">Đăng nhập</a>.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>