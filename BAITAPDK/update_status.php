<?php
session_start();
require_once 'helpers.php';
require_once 'db_connect.php'; 

// 1. Kiểm tra truy cập
if (!isset($_SESSION['user_id'])) {
	set_flash_message('success', 'Trạng thái công việc đã được cập nhật!');
header("Location: dashboard.php");
exit();
    header("Location: login.php");
	
    exit();
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? null;
$new_status = $_GET['status'] ?? null;

// Kiểm tra dữ liệu đầu vào
if (!$task_id || !$new_status || !in_array($new_status, ['pending', 'in_progress', 'completed'])) {
    header("Location: dashboard.php");
    exit();
}

try {
    $sql = "UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $task_id);
    $stmt->bindParam(':user_id', $user_id);

    $stmt->execute();
    
} catch (PDOException $e) {
}

header("Location: dashboard.php");
exit();
?>