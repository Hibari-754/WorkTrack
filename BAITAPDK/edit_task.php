<?php
session_start();
require_once 'helpers.php';
require_once 'db_connect.php'; 


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$task = null;
$error = '';

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    
    // 1. Lấy thông tin công việc hiện tại (đảm bảo thuộc về người dùng đang đăng nhập)
    $sql = "SELECT id, title, description, due_date, status FROM tasks WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $task_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $task = $stmt->fetch();

    if (!$task) {
        set_flash_message("danger", "Công việc không tồn tại hoặc bạn không có quyền chỉnh sửa.");
        header("Location: dashboard.php");
        exit();
    }
} else {
    set_flash_message("danger", "Không có ID công việc được chỉ định.");
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['edit_task_submit'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']);
    $status = $_POST['status'];

    if (empty($title)) {
        $error = "Tiêu đề công việc là bắt buộc.";
    } elseif (!in_array($status, ['pending', 'in_progress', 'completed'])) {
        $error = "Trạng thái công việc không hợp lệ.";
    } else {
        // Thiết lập due_date thành NULL nếu để trống
        $due_date_value = empty($due_date) ? null : $due_date;
        
        $sql = "UPDATE tasks SET title = :title, description = :description, due_date = :due_date, status = :status 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindValue(':description', empty($description) ? null : $description, empty($description) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':due_date', $due_date_value, ($due_date_value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $task['id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            set_flash_message("success", "Đã cập nhật công việc thành công.");
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Lỗi: Không thể cập nhật công việc. Vui lòng thử lại.";
        }
    }
    // Cập nhật lại đối tượng $task để hiển thị giá trị mới sau khi lỗi POST
    $task['title'] = $title;
    $task['description'] = $description;
    $task['due_date'] = $due_date;
    $task['status'] = $status;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa Công việc</title>
    <link rel="icon" type="image/png" href="favicon.png"> 
    <meta name="theme-color" content="#1a1a2e">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        /* CSS CHUNG */
        body { font-family: 'Roboto', sans-serif; background-color: #1a1a2e; }
        .full-height { min-height: 100vh; }
        
        /* BACKGROUND GIỐNG DASHBOARD (Deadpool Style) */
        .auth-background { 
            background-image: url('images/main-background.gif'); /* Sử dụng background tối */
            background-size: cover; 
            background-position: center; 
            position: relative; 
            background-attachment: fixed;
        }
        .auth-background::before { 
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0, 0, 0, 0.5); /* Lớp phủ mờ hơn */
            z-index: -1; 
        }


.card {
            z-index: 2; position: relative; background-color: rgba(255, 255, 255, 0.15); 
            border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); border-radius: 30px; /* Card chính bo góc 30px */
            overflow: hidden; /* QUAN TRỌNG: Buộc nội dung Card con bo góc theo */
        }
        .card-header { 
            background-color: #993333 !important; color: white; 
            border-top-left-radius: 30px !important; border-top-right-radius: 30px !important; /* FIX: Bo góc trên */
            border-bottom-left-radius: 0 !important; border-bottom-right-radius: 0 !important; /* Loại bỏ bo góc dưới */
            border-bottom: none; 
            padding: 15px; font-size: 1.2rem; font-weight: 700; 
        }

        .card-body { color: #f0f0f0; }

        /* INPUTS */
        .form-control, .custom-select { 
            border-radius: 8px !important; 
            background-color: rgba(255, 255, 255, 0.1) !important; 
            color: #f0f0f0 !important; 
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .form-control:focus, .custom-select:focus { 
            border-color: #993333; /* Đỏ Burgundy khi focus */
            box-shadow: 0 0 0 0.15rem rgba(153, 51, 51, 0.4); 
            background-color: rgba(0, 0, 0, 0.3) !important;
            color: #f0f0f0 !important;
        }
        /* Đảm bảo text trong option của select là màu tối để dễ đọc trên nền sáng */
        .custom-select option {
            background-color: #2c3e50; 
            color: #f0f0f0; 
        }

        /* BUTTONS (Đỏ Burgundy) */
        .btn { border-radius: 8px !important; font-weight: 500; }
        .btn-main { 
            background-color: #993333 !important; 
            border-color: #993333 !important; 
            color: white !important; 
        }
        .btn-main:hover { 
            background-color: #7d2626 !important; 
            border-color: #7d2626 !important; 
        }
        .btn-secondary-link {
            color: #f0f0f0 !important; 
            background-color: rgba(255, 255, 255, 0.1); 
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-secondary-link:hover {
            background-color: rgba(255, 255, 255, 0.2); 
            color: white !important;
        }
    </style>
</head>
<body class="auth-background d-flex justify-content-center align-items-center full-height">
<div class="container d-flex justify-content-center align-items-center full-height">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <i class="fas fa-edit"></i> Chỉnh Sửa Công Việc: <?php echo htmlspecialchars($task['title']); ?>
            </div>
            <div class="card-body">
                <?php display_flash_message(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="edit_task.php?id=<?php echo $task['id']; ?>" method="POST">
                    <div class="form-group">
                        <label for="title">Tiêu đề Công việc *:</label>
                        <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($task['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">Mô tả chi tiết (Tùy chọn):</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Ngày hết hạn (Tùy chọn):</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($task['due_date']); ?>">
                        <small class="form-text text-light">Định dạng: yyyy-mm-dd</small>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng thái:</label>
                        <select name="status" class="custom-select" required>
                            <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Chờ xử lý</option>
                            <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>Đang tiến hành</option>
                            <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Hoàn thành</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="edit_task_submit" class="btn btn-main btn-block mt-4"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                </form>

                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-secondary-link btn-block"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>