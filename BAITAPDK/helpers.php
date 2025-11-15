<?php

function set_flash_message($type, $message) {
    
    $_SESSION['flash_message'] = [
        'type' => $type, // Ví dụ: success, error, info, warning
        'message' => $message
    ];
}

function display_flash_message() {
    
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        
        echo '<div class="alert alert-' . $msg['type'] . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($msg['message']);
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button>';
        echo '</div>';
        
        unset($_SESSION['flash_message']);
    }
}
?>