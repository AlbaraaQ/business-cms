<?php
/**
 * ملف الدوال المساعدة - حداد جده
 *
 * يحتوي على دوال مشتركة للاستخدام في جميع أنحاء التطبيق
 */

/**
 * تنظيف وتأمين البيانات المدخلة
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data); // Use with caution if magic_quotes_gpc is on (though deprecated)
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * التحقق من تسجيل الدخول للمشرف
 */
function is_admin_logged_in() {
    // Ensure session is started (idempotent)
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('ADMIN_SESSION_NAME')) {
            session_name(ADMIN_SESSION_NAME);
        }
        @session_start(); // Suppress errors if headers already sent, though ideally this is called early.
    }
    return isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}


/**
 * إعادة توجيه المستخدم
 */
function redirect($url) {
    // Ensure URL is somewhat safe, prevent header injection
    // A more robust solution might involve a whitelist or ensuring it's a relative path or same-domain.
    if (strpos($url, "\r") !== false || strpos($url, "\n") !== false) {
        // Invalid characters in URL, potential header injection
        log_error("Attempted header injection in redirect URL: " . $url);
        // Default to a safe redirect or error page
        $safe_redirect_url = defined('SITE_URL') ? SITE_URL : '/';
        header("Location: " . $safe_redirect_url);
        exit();
    }
    header("Location: $url");
    exit();
}

/**
 * إنشاء رمز CSRF
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('ADMIN_SESSION_NAME')) { session_name(ADMIN_SESSION_NAME); }
        @session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * إنشاء حقل CSRF token للنماذج
 */
function csrf_input_field() {
    $token_name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
    return '<input type="hidden" name="' . htmlspecialchars($token_name) . '" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}


/**
 * التحقق من رمز CSRF
 */
function verify_csrf_token($token_from_form = null) {
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('ADMIN_SESSION_NAME')) { session_name(ADMIN_SESSION_NAME); }
        @session_start();
    }
    $token_name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
    $token_from_form = $token_from_form ?? ($_POST[$token_name] ?? $_GET[$token_name] ?? null);

    if (empty($_SESSION['csrf_token']) || empty($token_from_form)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token_from_form);
}

/**
 * تسجيل الأخطاء
 */
function log_error($message, $file = null, $line = null) {
    $log_dir_path = defined('PROJECT_ROOT') ? PROJECT_ROOT . '/logs/' : dirname(__DIR__) . '/logs/';
    // Ensure log directory exists
    if (!is_dir($log_dir_path)) {
        @mkdir($log_dir_path, 0755, true);
    }
    $log_message = "[" . date('Y-m-d H:i:s') . "] ERROR: $message";
    if ($file) $log_message .= " in $file";
    if ($line) $log_message .= " on line $line";

    // Append to error.log file
    // Check if directory is writable, if not, fallback or warn
    if (is_writable($log_dir_path)) {
        @error_log($log_message . PHP_EOL, 3, $log_dir_path . 'error.log');
    } else {
        // Fallback: log to default PHP error log if custom dir not writable
        @error_log("LOG DIR NOT WRITABLE: " . $log_dir_path . " | " . $log_message);
    }
}


/**
 * إرسال استجابة JSON
 */
function send_json_response($data, $status_code = 200) {
    if (!headers_sent()) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * تقليص النص
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
}

/**
 * Get base URL for assets, links etc.
 */
function base_url($path = '') {
    $base = defined('SITE_URL') ? SITE_URL : '';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}


/**
 * Generate a URL-friendly slug from a string.
 */
function generate_slug($text) {
    // Normalize Arabic characters to their basic forms
    $text = preg_replace('/[أإآ]/u', 'ا', $text);
    $text = preg_replace('/[يى]/u', 'ي', $text);
    $text = preg_replace('/[ؤ]/u', 'و', $text);
    $text = preg_replace('/[ئ]/u', 'ي', $text);
    $text = preg_replace('/[ة]/u', 'ه', $text);
    $text = preg_replace('/[ك]/u', 'ک', $text); // Normalize Kaf

    // Remove puncuation and special characters (allow a-z, 0-9, Arabic letters, underscore, hyphen)
    $text = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $text);
    
    // Transliterate to a limited set of ASCII or keep Arabic if preferred (current keeps Arabic)
    // For purely ASCII slugs, a transliteration library would be needed.
    // This example will create slugs with Arabic characters.

    // Replace spaces and consecutive hyphens/underscores with a single hyphen
    $text = preg_replace('/[\s_-]+/u', '-', $text);
    
    // Trim hyphens from start and end
    $text = trim($text, '-');
    
    // Lowercase (optional, but common for slugs)
    // $text = mb_strtolower($text, 'UTF-8');

    if (empty($text)) {
        return 'n-a-' . time(); // Fallback for empty slugs
    }

    return $text;
}

/**
 * Handle file upload.
 * @param array $file_info $_FILES['input_name']
 * @param string $upload_subdir Subdirectory within UPLOAD_PATH (e.g., 'services', 'projects')
 * @param array $allowed_mime_types Array of allowed MIME types
 * @param int $max_file_size Maximum file size in bytes
 * @return string|false Relative path to uploaded file on success, false on failure.
 */
function handle_file_upload($file_info, $upload_subdir, $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $max_file_size = 5 * 1024 * 1024) {
    if (!isset($file_info['error']) || is_array($file_info['error'])) {
        log_error("Invalid file upload parameters or multiple files uploaded where single expected.");
        return false; // Invalid parameters or multiple files with single handler
    }

    switch ($file_info['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            // log_error("No file sent for upload."); // Not necessarily an error if optional
            return null; // No file uploaded, can be valid
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            log_error("Exceeded filesize limit.");
            return false;
        default:
            log_error("Unknown errors during file upload. Code: " . $file_info['error']);
            return false;
    }

    if ($file_info['size'] > $max_file_size) {
        log_error("Exceeded filesize limit (custom check). Size: " . $file_info['size']);
        return false;
    }

    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_info['tmp_name']);
    if (false === array_search($mime_type, $allowed_mime_types, true)) {
        log_error("Invalid file type: " . $mime_type);
        return false;
    }

    // Sanitize filename and create unique name
    $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    $safe_filename_base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file_info['name'], PATHINFO_FILENAME));
    $unique_filename = $safe_filename_base . '_' . uniqid() . '.' . $file_extension;

    $destination_dir = rtrim(UPLOAD_PATH, '/') . '/' . trim($upload_subdir, '/');
    if (!is_dir($destination_dir)) {
        if (!@mkdir($destination_dir, 0755, true)) {
            log_error("Failed to create upload directory: " . $destination_dir);
            return false;
        }
    }
    
    $destination_path = $destination_dir . '/' . $unique_filename;

    if (!@move_uploaded_file($file_info['tmp_name'], $destination_path)) {
        log_error("Failed to move uploaded file to: " . $destination_path);
        return false;
    }

    // Return path relative to UPLOADS_DIR_NAME for storing in DB
    return trim($upload_subdir, '/') . '/' . $unique_filename;
}

/**
 * Delete a file from the uploads directory.
 * @param string $relative_path Path relative to UPLOAD_PATH
 * @return bool True on success or if file doesn't exist, false on failure.
 */
function delete_uploaded_file($relative_path) {
    if (empty($relative_path)) return true;

    $full_path = UPLOAD_PATH . $relative_path;
    if (file_exists($full_path) && is_file($full_path)) {
        if (@unlink($full_path)) {
            return true;
        } else {
            log_error("Failed to delete file: " . $full_path);
            return false;
        }
    }
    return true; // File doesn't exist, so it's "deleted"
}

?>
