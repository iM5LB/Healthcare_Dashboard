<?php
function redirectWithMessage($success_msg = '', $error_msg = '', $base_url) {
    $url = $base_url;
    if ($error_msg) {
        $url .= "?error=" . urlencode($error_msg);
    } elseif ($success_msg) {
        $url .= "?success=" . urlencode($success_msg);
    }
    header("Location: " . $url);
    exit;
}

// Define sanitizeInput if not already defined
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>