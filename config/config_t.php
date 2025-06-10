<?php
/**
 * ملف الإعداد - تم إنشاؤه تلقائياً
 * تاريخ الإنشاء: 2025-05-29 03:05:44
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'new_web2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'حداد جده');
define('SITE_TAGLINE', 'أعمال حدادة وتركيب كلادنج احترافية');

// مسارات الملفات
define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOADS_DIR_NAME', 'uploads');
define('UPLOAD_PATH', PROJECT_ROOT . '/' . UPLOADS_DIR_NAME . '/');
define('UPLOAD_URL', SITE_URL . '/' . UPLOADS_DIR_NAME . '/');

// إعدادات الأمان
define('ADMIN_SESSION_NAME', 'admin_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('ADMIN_PANEL_LOCKED', false);

// إعدادات أخرى
define('TIMEZONE', 'Asia/Riyadh');
define('DEBUG_MODE', false);

// تعيين المنطقة الزمنية
date_default_timezone_set(TIMEZONE);

// بدء الجلسة إذا لم تكن مبدوءة (مهم للوحة التحكم)
if (session_status() === PHP_SESSION_NONE) {
    if (defined('ADMIN_SESSION_NAME')) {
        session_name(ADMIN_SESSION_NAME);
    }
    session_start();
}

// تعيين الترميز
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}

// Display errors if DEBUG_MODE is true
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
?>