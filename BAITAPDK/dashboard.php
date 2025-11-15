<?php
session_start();
require_once 'helpers.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// --- CẤU HÌNH PHÂN TRANG VÀ TRUY VẤN CSDL (GIỮ NGUYÊN) ---
$tasks_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $tasks_per_page;
if ($current_page < 1) $current_page = 1;

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';

// TRUY VẤN DỮ LIỆU CÔNG VIỆC HIỂN THỊ
$sql = "SELECT id, title, description, due_date, status FROM tasks WHERE user_id = :user_id";
$params_fetch = [':user_id' => $user_id];
$where_clause = "";
if ($filter_status !== 'all' && in_array($filter_status, ['pending', 'in_progress', 'completed'])) {
    $where_clause .= " AND status = :status";
    $params_fetch[':status'] = $filter_status;
}
$count_sql = "SELECT COUNT(id) AS total FROM tasks WHERE user_id = :user_id" . $where_clause;
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params_fetch);
$total_tasks = $stmt_count->fetchColumn();
$total_pages = ceil($total_tasks / $tasks_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $tasks_per_page;
}
$sql .= $where_clause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$params_fetch[':limit'] = $tasks_per_page;
$params_fetch[':offset'] = $offset;
$stmt = $pdo->prepare($sql);
foreach ($params_fetch as $key => &$val) {
    if ($key == ':limit' || $key == ':offset') { $stmt->bindValue($key, $val, PDO::PARAM_INT); } else { $stmt->bindParam($key, $val); }
}
unset($val);
$stmt->execute();
$tasks = $stmt->fetchAll();

// LOGIC TÍNH TOÁN THỐNG KÊ (Cho Stats Card)
$all_tasks_stmt = $pdo->prepare("SELECT id, title, description, due_date, status FROM tasks WHERE user_id = :user_id");
$all_tasks_stmt->execute([':user_id' => $user_id]);
$all_user_tasks = $all_tasks_stmt->fetchAll();

$total_pending = 0; $total_in_progress = 0; $total_completed = 0; $total_overdue_due_soon = 0;
$today_midnight = (new DateTime())->setTime(0, 0, 0); 
$due_soon_midnight = (new DateTime('+1 day'))->setTime(0,0,0);
$overdue_count = 0; $due_soon_count = 0;

foreach ($all_user_tasks as $task) {
    if ($task['status'] === 'pending') { $total_pending++; } 
    elseif ($task['status'] === 'in_progress') { $total_in_progress++; } 
    elseif ($task['status'] === 'completed') { $total_completed++; }

    if ($task['due_date'] && $task['status'] !== 'completed') {
        try {
            $due_date_midnight = (new DateTime($task['due_date']))->setTime(0, 0, 0);
            
            if ($due_date_midnight < $today_midnight) { $total_overdue_due_soon++; } 
            elseif ($due_date_midnight <= $due_soon_midnight) { $total_overdue_due_soon++; }
        } catch (Exception $e) { }
    }
}
foreach ($tasks as $key => $task) {
    if ($task['due_date'] && $task['status'] !== 'completed') {
        try {
            $due_date_midnight = (new DateTime($task['due_date']))->setTime(0, 0, 0);
            if ($due_date_midnight < $today_midnight) { $tasks[$key]['alert_type'] = 'overdue'; $overdue_count++;} 
            elseif ($due_date_midnight <= $due_soon_midnight) { $tasks[$key]['alert_type'] = 'due_soon'; $due_soon_count++;}
        } catch (Exception $e) { }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - WorkTrack</title>
    <link rel="icon" type="image/png" href="favicon.png"> 
    <meta name="theme-color" content="#1a1a2e"> 
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.min.css">
    
    <style>
        /* CSS CHUNG */
        html { overflow-y: scroll; height: 100%; margin: 0; padding: 0; }
        body { min-height: 100vh; padding-bottom: 50px; font-family: 'Roboto', sans-serif; color: #e0e0e0; background-color: #1a1a2e; }
        h1, h2, h3, h4, h5, h6, .dashboard-title, .card-title, .modal-title { color: #f0f0f0 !important; font-weight: 700; }

        /* BACKGROUND VÀ GLASSMORPHISM */
        .full-background { background-image: url('images/main-background.gif'); background-size: cover; background-attachment: fixed; position: relative; overflow: hidden; }
        .full-background::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: -1; }
        .container { position: relative; z-index: 1; }
        .card, .navbar { background-color: rgba(255, 255, 255, 0.1) !important; border-radius: 18px; border: 1px solid rgba(255, 255, 255, 0.2) !important; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); }
        .navbar { border-radius: 0; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2); }

        /* BUTTONS */
        .btn { border-radius: 10px !important; font-weight: 500; }
        .btn-main { 
    background-color: #993333 !important; /* Đỏ Burgundy */
    border-color: #993333 !important; 
    color: white !important; 
}
.btn-main:hover { 
    background-color: #7d2626 !important; /* Đỏ đậm hơn khi hover */
    border-color: #7d2626 !important; 
}
.btn-outline-main { 
    color: #993333 !important; /* Viền và chữ màu Đỏ Burgundy */
    border-color: #993333 !important; 
    background-color: rgba(153, 51, 51, 0.1) !important; /* Nền trong suốt màu đỏ nhạt */
}
.btn-outline-main:hover { 
    background-color: #993333 !important; 
    color: white !important; 
}
        .btn-secondary { background-color: rgba(255, 255, 255, 0.1) !important; border-color: rgba(255, 255, 255, 0.2) !important; color: #f0f0f0 !important; }

        /* KHỐI THỐNG KÊ (STATS CARD) */
        .stats-card { background-color: rgba(255, 255, 255, 0.15) !important; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid rgba(255, 255, 255, 0.2) !important;}
        .stats-item { padding: 0.5rem 0; display: flex; justify-content: space-between; align-items: center; }
        
        #current-time { font-size: 3.5em; font-weight: 300; letter-spacing: -2px; text-shadow: 0 0 15px rgba(0, 123, 255, 0.5); color: white; }
        #current-date { font-size: 1.2em; color: rgba(255, 255, 255, 0.8); }

        /* TABLE VÀ CẢNH BÁO */
        .table { background-color: rgba(255, 255, 255, 0.05) !important; color: #e0e0e0; border: 1px solid rgba(255, 255, 255, 0.1); }
        .table thead th { background-color: rgba(255, 255, 255, 0.08) !important; color: #f0f0f0 !important; border-bottom: 2px solid rgba(255, 255, 255, 0.2) !important; }
        .table-danger { background-color: rgba(220, 53, 69, 0.25) !important; } 
        .table-warning { background-color: rgba(255, 193, 7, 0.25) !important; }
        .alert-danger { background-color: rgba(220, 53, 69, 0.25) !important; border-color: rgba(220, 53, 69, 0.5) !important; color: #ffebf0 !important; }
        .alert-warning { background-color: rgba(255, 193, 7, 0.25) !important; border-color: rgba(255, 193, 7, 0.5) !important; color: #fff8e0 !important; }

        /* Dropdown menu */
        .dropdown-menu { background-color: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; backdrop-filter: blur(15px) !important; box-shadow: 0 4px 20px rgba(0,0,0,0.3); border-radius: 12px; }
        .dropdown-item { color: #f0f0f0 !important; }
        .dropdown-item:hover, .dropdown-item.active { background-color: rgba(0, 123, 255, 0.2) !important; color: #fff !important; }
        .dropdown-toggle.btn-main, .dropdown-toggle.btn-outline-main { width: auto !important; min-width: 120px; }
    </style>
</head>
<body class="full-background">
    <nav class="navbar navbar-expand-lg"> 
        <a class="navbar-brand text-light-trans" href="dashboard.php"><i class="fas fa-clipboard-list"></i> WorkTrack</a>
        <div class="ml-auto d-flex align-items-center">
            <span class="navbar-text mr-3 text-light-trans">
                Hi, <b><?php echo htmlspecialchars($username); ?></b>!
            </span>
            <a href="logout.php" class="btn btn-danger my-2 my-sm-0 btn-sm"><i class="fas fa-sign-out-alt"></i> Thoát</a>
        </div>
    </nav>

    <div class="container mt-4">
        
        <div class="card stats-card mx-auto" style="max-width: 900px;">
            <div class="row w-100 no-gutters">
                
                <div class="col-md-4 text-center d-flex flex-column justify-content-center border-right border-secondary-transparent p-3">
                    <div id="current-time" class="time-display">00:00:00</div>
                    <div id="current-date" class="date-display"><?php echo date('l, F j, Y'); ?></div>
                </div>

                <div class="col-md-8 pl-md-4">
                    <div class="row mb-2">
                        <div class="col-6 stats-item">
                            <span class="text-light-trans"><i class="fas fa-hourglass-start"></i> Chưa làm:</span>
                            <span class="badge badge-danger stats-value"><?php echo $total_pending; ?></span>
                        </div>
                        <div class="col-6 stats-item">
                            <span class="text-light-trans"><i class="fas fa-exclamation-triangle"></i> Cần chú ý:</span>
                            <?php if ($total_overdue_due_soon > 0): ?>
                                <span class="badge badge-warning stats-value"><?php echo $total_overdue_due_soon; ?></span>
                            <?php else: ?>
                                <span class="badge badge-success stats-value">OK</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 stats-item">
                            <span class="text-light-trans"><i class="fas fa-spinner"></i> Đang làm:</span>
                            <span class="badge badge-warning stats-value"><?php echo $total_in_progress; ?></span>
                        </div>
                        <div class="col-6 stats-item">
                            <span class="text-light-trans"><i class="far fa-check-circle"></i> Đã hoàn thành:</span>
                            <span class="badge badge-success stats-value"><?php echo $total_completed; ?></span>
                        </div>
                    </div>
                    <div class="row pt-3 mt-3 border-top border-secondary-transparent">
                        <div class="col-12 stats-item">
                            <span class="text-light-trans"><i class="fas fa-tasks"></i> Tổng cộng:</span>
                            <span class="stats-value"><?php echo $total_tasks; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
            <h2 class="dashboard-title">Bảng Công Việc Chi Tiết</h2>
            <a href="create_task.php" class="btn btn-main btn-lg shadow">Thêm công việc</a>
        </div>

        <?php display_flash_message(); ?>

        <?php if ($overdue_count > 0): ?>
            <div class="alert alert-danger shadow-sm border-danger mb-4">
                ⚠️ **CẢNH BÁO: <?php echo $overdue_count; ?> công việc ĐÃ QUÁ HẠN!** Vui lòng kiểm tra ngay.
            </div>
        <?php endif; ?>
        
        <?php if ($due_soon_count > 0): ?>
            <div class="alert alert-warning shadow-sm border-warning mb-4">
                ⏰ **QUAN TRỌNG: <?php echo $due_soon_count; ?> công việc SẮP HẾT HẠN** (trong 24 giờ tới).
            </div>
        <?php endif; ?>
        <div class="card mb-4 shadow-sm"> 
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="card-title">Lọc theo Trạng thái:</h5>
                        <div class="dropdown">
                            <button class="btn btn-main dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo $filter_status == 'all' ? 'Tất cả Công việc' : ucfirst(str_replace('_', ' ', $filter_status)); ?>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="filterDropdown">
                                <a class="dropdown-item <?php echo $filter_status == 'all' ? 'active' : ''; ?>" href="dashboard.php?sort=<?php echo $sort_by; ?>&status=all">Tất cả Công việc</a>
                                <a class="dropdown-item <?php echo $filter_status == 'pending' ? 'active' : ''; ?>" href="dashboard.php?sort=<?php echo $sort_by; ?>&status=pending">Chờ xử lý</a>
                                <a class="dropdown-item <?php echo $filter_status == 'in_progress' ? 'active' : ''; ?>" href="dashboard.php?sort=<?php echo $sort_by; ?>&status=in_progress">Đang tiến hành</a>
                                <a class="dropdown-item <?php echo $filter_status == 'completed' ? 'active' : ''; ?>" href="dashboard.php?sort=<?php echo $sort_by; ?>&status=completed">Hoàn thành</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 text-right">
                        <h5 class="card-title">Sắp xếp:</h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-main dropdown-toggle" type="button" id="sortDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo $sort_by == 'due_date' ? 'Ngày hết hạn' : 'Mới nhất'; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="sortDropdown">
                                <a class="dropdown-item <?php echo $sort_by == 'created_at' ? 'active' : ''; ?>" href="dashboard.php?status=<?php echo $filter_status; ?>&sort=created_at">Mới nhất</a>
                                <a class="dropdown-item <?php echo $sort_by == 'due_date' ? 'active' : ''; ?>" href="dashboard.php?status=<?php echo $filter_status; ?>&sort=due_date">Ngày hết hạn</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="dashboard-title">Danh sách Công việc</h3>

        <?php if (empty($tasks)): ?>
            <div class="alert alert-info text-center mt-5">🎉 Hoàn thành xuất sắc! Bạn không có công việc nào trong danh sách.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Ngày hết hạn</th>
                            <th>Trạng thái</th>
                            <th style="width: 150px;">Thao tác</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): 
                            $task_status_display = ucfirst(str_replace('_', ' ', $task['status']));
                            $row_class = '';
                            if (isset($task['alert_type']) && $task['alert_type'] === 'overdue') {
                                $row_class = 'table-danger';
                            } elseif (isset($task['alert_type']) && $task['alert_type'] === 'due_soon') {
                                $row_class = 'table-warning';
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td 
                                    class="<?php echo $task['status'] == 'completed' ? 'task-title completed' : 'task-title'; ?>"
                                    data-task-id="<?php echo $task['id']; ?>"
                                    data-task-title="<?php echo htmlspecialchars($task['title']); ?>"
                                    data-task-description="<?php echo htmlspecialchars($task['description'] ?? ''); ?>"
                                    data-task-due-date="<?php echo $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : 'Không đặt'; ?>"
                                    data-task-status="<?php echo $task_status_display; ?>"
                                    title="Nhấn để xem chi tiết mô tả"
                                >
                                    <?php 
                                    if ($task['status'] == 'completed') {
                                        echo '✅ ';
                                    }
                                    echo htmlspecialchars($task['title']); 
                                    ?>
                                </td>
                                <td><?php echo $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : '<span class="text-muted">Không đặt</span>'; ?></td>
                                <td>
                                    <?php 
                                    $badge_color = 'secondary';
                                    if ($task['status'] == 'completed') $badge_color = 'success'; 
                                    elseif ($task['status'] == 'in_progress') $badge_color = 'primary'; 
                                    elseif ($task['status'] == 'pending') $badge_color = 'warning'; 
                                    ?>
                                    <span class="badge badge-<?php echo $badge_color; ?> p-2"><?php echo $task_status_display; ?></span>
                                </td>
                                
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Thao tác
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="actionDropdown">
                                            
                                            <a class="dropdown-item" href="edit_task.php?id=<?php echo $task['id']; ?>">Chỉnh sửa</a>
                                            
                                            <?php if ($task['status'] != 'completed'): ?>
                                                <a class="dropdown-item text-success" href="update_status.php?id=<?php echo $task['id']; ?>&status=completed">Hoàn thành</a>
                                            <?php endif; ?>
                                            
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="delete_task.php?id=<?php echo $task['id']; ?>" onclick="return confirm('Xác nhận xóa công việc này?');">Xóa</a>
                                            
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ($total_pages > 1): ?>
            <?php endif; ?>
        
        <div style="height: 50px;"></div> 
    </div>
    
    <div class="modal fade" id="taskDetailModal" tabindex="-1" role="dialog" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-indigo text-white">
                    <h5 class="modal-title" id="taskDetailModalLabel">Chi tiết Công việc</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h4 id="modal-title" class="mb-3"></h4>
                    <p><strong>Ngày hết hạn:</strong> <span id="modal-due-date"></span></p>
                    <p><strong>Trạng thái:</strong> <span id="modal-status"></span></p>
                    <hr>
                    <p><strong>Mô tả chi tiết:</strong></p>
                    <p id="modal-description"></p>
                </div>
                <div class="modal-footer">
                    <a id="modal-edit-link" href="#" class="btn btn-main">Chỉnh sửa</a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.vi.min.js"></script>
    
    <script>
        // JS cho Đồng hồ thời gian thực
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            document.getElementById('current-time').textContent = timeString;

            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('vi-VN', dateOptions);
        }

        setInterval(updateClock, 1000);
        updateClock(); 

        // JS cho Modal chi tiết (giữ nguyên)
        $(document).ready(function() {
            $('.task-title').on('click', function() {
                var taskId = $(this).data('task-id');
                var taskTitle = $(this).data('task-title');
                var taskDesc = $(this).data('task-description');
                var taskDue = $(this).data('task-due-date');
                var taskStatus = $(this).data('task-status');
                
                $('#modal-title').text(taskTitle);
                $('#modal-description').text(taskDesc || 'Không có mô tả chi tiết.');
                $('#modal-due-date').text(taskDue || 'Không đặt');
                $('#modal-status').text(taskStatus);
                $('#modal-edit-link').attr('href', 'edit_task.php?id=' + taskId); 
                
                $('#taskDetailModal').modal('show');
            });
        });
    </script>
</body>
</html>