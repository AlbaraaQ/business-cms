<?php
/**
 * ملف الإعداد النموذجي - حداد جده
 * 
 * يتم نسخ هذا الملف إلى config.php أثناء التثبيت
 * مع تعبئة القيم الفعلية
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع الأساسية
define('SITE_URL', 'http://localhost/makkah-metalworks'); // سيتم تحديثه تلقائياً بواسطة المثبت
define('SITE_NAME', 'حداد جده');
define('SITE_TAGLINE', 'خدمات احترافية في الأعمال المعدنية');

// مسارات الملفات والمجلدات (نسبية من جذر المشروع)
define('PROJECT_ROOT', dirname(__DIR__)); // المسار المطلق لجذر المشروع
define('UPLOADS_DIR_NAME', 'uploads'); // اسم مجلد الرفع
define('UPLOAD_PATH', PROJECT_ROOT . '/' . UPLOADS_DIR_NAME . '/');
define('UPLOAD_URL', SITE_URL . '/' . UPLOADS_DIR_NAME . '/');


// إعدادات الأمان
define('ADMIN_SESSION_NAME', 'makkah_admin_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('ADMIN_PANEL_LOCKED', false); // true لتعطيل الوصول للوحة التحكم, false لتمكينها

// إعدادات التطبيق
define('TIMEZONE', 'Asia/Riyadh');
define('DEBUG_MODE', false); // Set to true for development, false for production

// تعيين المنطقة الزمنية
if (defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
}

// بدء الجلسة إذا لم تكن مبدوءة (مهم للوحة التحكم)
if (session_status() === PHP_SESSION_NONE) {
    if (defined('ADMIN_SESSION_NAME')) {
        session_name(ADMIN_SESSION_NAME);
    }
    session_start();
}

// تعيين ترميز الأحرف
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Ensure uploads directory exists
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
