<?php
session_start();
require_once 'helpers.php';
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    set_flash_message('danger', 'Công việc đã được xóa thành công!');
header("Location: dashboard.php");
exit();
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':id', $task_id);
    $stmt->bindParam(':user_id', $user_id);

    $stmt->execute();
    
    header("Location: dashboard.php?task_deleted=true");
    exit();
} catch (PDOException $e) {
    header("Location: dashboard.php?task_delete_error=true");
    exit();
}
?>