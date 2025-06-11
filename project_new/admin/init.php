<?php
/**
 * Admin Area Initialization File
 *
 * This file should be included at the beginning of all admin-facing PHP scripts.
 * It handles:
 * - Session start
 * - Loading configuration
 * - Database connection
 * - Loading helper functions
 * - Basic security checks (e.g., if site is installed)
 */

// Define a constant to check if admin area is loaded
define('ADMIN_AREA', true);

// Go up two directories from /admin/init.php to reach project root for config.php
$config_path = dirname(__DIR__) . '/config/config.php';

if (!file_exists($config_path)) {
    // Config file doesn't exist, meaning the site is likely not installed.
    // Redirect to installer.
    $install_url = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\') . '/public/install.php';
    header('Location: ' . $install_url);
    exit;
}

// Load the configuration file
require_once $config_path;

// Load essential core files
// PROJECT_ROOT is defined in app/config/config.php, loaded via the root config.php
require_once PROJECT_ROOT . '/app/services/Database.php'; // Updated path
require_once PROJECT_ROOT . '/app/helpers/GlobalHelper.php'; // Updated path

// Initialize Database connection
$db = new Database();

if (!$db->isConnected()) {
    // Critical error: Failed to connect to database after installation.
    // Log this error.
    log_error("Admin Init: Failed to connect to the database. DB Error: " . $db->getError());
    die("خطأ حرج: لا يمكن الاتصال بقاعدة البيانات. يرجى مراجعة مدير النظام أو ملفات السجل.");
}

// Security: Check if admin panel is locked
if (defined('ADMIN_PANEL_LOCKED') && ADMIN_PANEL_LOCKED === true) {
    // Allow access only to a specific unlock page
    if (basename($_SERVER['PHP_SELF']) !== 'some_unlock_page.php') {
        die("لوحة التحكم معطلة مؤقتاً للصيانة.");
    }
}

// Ensure upload subdirectories exist
$upload_subdirs = ['sections', 'services', 'projects', 'testimonials', 'site_assets'];

foreach ($upload_subdirs as $subdir) {
    $path = UPLOAD_PATH . $subdir;
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
        
        // Add a .htaccess file to restrict script execution in these folders
        if (is_dir($path) && !file_exists($path . '/.htaccess')) {
            $htaccess = <<<HTACCESS
Options -Indexes
RemoveHandler .php .phtml .php3
RemoveType .php .phtml .php3
php_flag engine off

<FilesMatch "\.(php|phtml|php3|pl|py|jsp|asp|htm|html|swf)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(jpg|jpeg|png|gif|svg|webp|pdf|doc|docx|xls|xlsx|mp4|webm)$">
    Order allow,deny
    Allow from all
</FilesMatch>
HTACCESS;
            @file_put_contents($path . '/.htaccess', $htaccess);
        }
    }
}

// Generate CSRF token if not already set for the session
generate_csrf_token();
?>