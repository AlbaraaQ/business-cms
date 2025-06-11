<?php
/**
 * ملف التهيئة الرئيسي للمشروع
 * 
 * هذا الملف يحتوي على الإعدادات الأساسية والثوابت والوظائف المشتركة
 * يتم تضمينه في جميع صفحات المشروع
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)));
}

// تحميل ملف الإعدادات المركزي
// هذا الملف يحتوي على تعريفات مثل DB_HOST, SITE_URL, الخ.
require_once BASE_PATH . '/config/config.php';

// تعريف المسارات الأساسية
define('INCLUDES_DIR', BASE_PATH . '/includes');
define('FUNCTIONS_DIR', INCLUDES_DIR . '/functions');
define('ADMIN_DIR', BASE_PATH . '/admin');
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('ASSETS_DIR', BASE_PATH . '/assets');

// تعريف روابط الموقع
$site_url_init = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$site_url_init .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
// SITE_URL is now defined in config/config.php. The line below is removed.
// define('SITE_URL', rtrim($site_url_init, '/'));
define('ADMIN_URL', SITE_URL . '/admin'); // Uses SITE_URL from config.php
// UPLOAD_URL is defined in config.php
// define('UPLOAD_URL', SITE_URL . '/uploads');
define('ASSETS_URL', SITE_URL . '/assets');

// إعدادات قاعدة البيانات (تم نقلها إلى config/config.php)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'project_db');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة للموقع (SITE_NAME و SITE_TAGLINE معرفة في config/config.php)
// define('SITE_NAME', 'اسم الموقع');
// define('SITE_DESCRIPTION', 'وصف الموقع'); // استبدل بـ SITE_TAGLINE من config.php
define('SITE_EMAIL', 'info@example.com'); // تبقى هنا حالياً، يمكن نقلها لـ config.php
define('SITE_PHONE', '+1234567890'); // تبقى هنا حالياً، يمكن نقلها لـ config.php
define('SITE_ADDRESS', 'عنوان الموقع'); // تبقى هنا حالياً، يمكن نقلها لـ config.php

// تضمين ملفات الوظائف الأساسية
// require_once FUNCTIONS_DIR . '/database.php'; // Replaced with core/Database.php
require_once FUNCTIONS_DIR . '/utilities.php';
require_once FUNCTIONS_DIR . '/service_functions.php';
require_once FUNCTIONS_DIR . '/project_functions.php';
require_once FUNCTIONS_DIR . '/admin_auth.php';
// require_once FUNCTIONS_DIR . '/code_optimization.php'; // Commented out
// require_once FUNCTIONS_DIR . '/future_recommendations.php'; // Commented out

// تحميل واستخدام الفئة Database Core
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . '/core/Database.php';
} else {
    // Fallback if PROJECT_ROOT is not defined (should not happen if config.php is loaded)
    require_once BASE_PATH . '/core/Database.php';
}

// إنشاء اتصال قاعدة البيانات باستخدام الفئة
// $db = db_connect(); // Old procedural connection
$db = new Database(); // New class-based connection

// بدء الجلسة إذا لم تكن قد بدأت
// يتم التعامل مع بدء الجلسة الآن في config.php
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// Helper functions like clean_input, redirect, truncate_text, is_admin_logged_in, get_current_admin_id,
// format_date, show_alert, lazy_load_image, create_page_link, create_service_link, create_project_link
// have been moved to includes/functions.php or are already defined there.
// The function load_admin_assets has been removed as it's specific to an older admin structure.

/**
 * تحميل ملفات JavaScript في نهاية الصفحة
 * 
 * @param array $files مصفوفة تحتوي على مسارات ملفات JavaScript
 * @return string كود HTML لتحميل الملفات
 */
function load_scripts($files) {
    $output = '';
    
    foreach ($files as $file) {
        if (strpos($file, '//') === 0 || strpos($file, 'http') === 0) {
            // رابط خارجي
            $output .= '<script src="' . $file . '"></script>' . PHP_EOL;
        } else {
            // ملف محلي
            $output .= '<script src="' . ASSETS_URL . '/js/' . $file . '"></script>' . PHP_EOL;
        }
    }
    
    return $output;
}

/**
 * تحميل ملفات CSS
 * 
 * @param array $files مصفوفة تحتوي على مسارات ملفات CSS
 * @return string كود HTML لتحميل الملفات
 */
function load_styles($files) {
    $output = '';
    
    foreach ($files as $file) {
        if (strpos($file, '//') === 0 || strpos($file, 'http') === 0) {
            // رابط خارجي
            $output .= '<link rel="stylesheet" href="' . $file . '">' . PHP_EOL;
        } else {
            // ملف محلي
            $output .= '<link rel="stylesheet" href="' . ASSETS_URL . '/css/' . $file . '">' . PHP_EOL;
        }
    }
    
    return $output;
}

/**
 * تحميل ملفات CSS و JavaScript المطلوبة للواجهة الأمامية
 */
function load_frontend_assets() {
    // ملفات CSS
    $css_files = [
        'bootstrap.min.css',
        'fontawesome.min.css',
        'fancybox.min.css',
        'style.css'
    ];
    
    // ملفات JavaScript
    $js_files = [
        'jquery.min.js',
        'bootstrap.bundle.min.js',
        'fancybox.min.js',
        'lazysizes.min.js',
        'main.js',
        'frontend_integration.js'
    ];
    
    echo load_styles($css_files);
    echo load_scripts($js_files);
}

/**
 * تحميل ملفات CSS و JavaScript المطلوبة للوحة التحكم
 */
function load_admin_assets() {
    // ملفات CSS
    $css_files = [
        'adminlte.min.css',
        'fontawesome.min.css',
        'dropzone.min.css',
        'summernote.min.css',
        'admin-style.css'
    ];
    
    // ملفات JavaScript
    $js_files = [
        'jquery.min.js',
        'bootstrap.bundle.min.js',
        'adminlte.min.js',
        'dropzone.min.js',
        'sortable.min.js',
        'summernote.min.js',
        'summernote-ar-AR.min.js',
        'admin-script.js'
    ];
    
    echo load_styles($css_files);
    echo load_scripts($js_files);
}

/**
 * إضافة وسوم SEO للصفحة
 * 
 * @param string $title عنوان الصفحة
 * @param string $description وصف الصفحة
 * @param string $keywords الكلمات المفتاحية
 * @param string $canonical_url الرابط القانوني
 */
/*
function add_seo_tags($title = '', $description = '', $keywords = '', $canonical_url = '') {
    // استخدام القيم الافتراضية إذا لم يتم توفير قيم
    $title = $title ?: SITE_NAME;
    $description = $description ?: SITE_DESCRIPTION;
    
    // إضافة اسم الموقع إلى العنوان إذا لم يكن موجوداً
    if (strpos($title, SITE_NAME) === false) {
        $title .= ' | ' . SITE_NAME;
    }
    
    // إنشاء الرابط القانوني إذا لم يتم توفيره
    if (empty($canonical_url)) {
        $canonical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    // تضمين ملف وظائف SEO إذا لم يكن متضمناً
    if (!function_exists('generate_seo_tags')) {
        // Ensure PROJECT_ROOT is available (defined in config.php)
        if (defined('PROJECT_ROOT')) {
            require_once PROJECT_ROOT . '/includes/functions/admin_seo_functions.php';
        } else {
            // Fallback or error if PROJECT_ROOT is not defined
            // This indicates a problem with the loading sequence of config.php
            trigger_error("PROJECT_ROOT not defined. Cannot include admin_seo_functions.php.", E_USER_WARNING);
        }
    }
    
    // إنشاء وسوم SEO
    echo generate_seo_tags($title, $description, $keywords, $canonical_url);
}
*/

// Pre-load essential site settings
$GLOBALS['site_settings_global'] = [];
if (isset($db) && $db instanceof Database) {
    $essential_settings_keys = [
        'site_name', 'site_description', 'contact_email', 'contact_phone',
        'contact_address', 'google_analytics_id', 'site_logo_path', // Changed from 'logo'
        'site_favicon_path', // Changed from 'favicon'
        'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'whatsapp_number' // For social links in footer
    ];
    $placeholders = rtrim(str_repeat('?,', count($essential_settings_keys)), ',');

    // It's better to fetch all settings once if many are needed, or fetch individually if few.
    // For this specific list, fetching individually or with a WHERE IN clause is fine.
    $settings_sql = "SELECT setting_name, setting_value FROM settings WHERE setting_name IN ($placeholders)";
    try {
        $loaded_settings = $db->query($settings_sql, $essential_settings_keys);
        if ($loaded_settings) {
            foreach ($loaded_settings as $row) {
                $GLOBALS['site_settings_global'][$row['setting_name']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        log_error("Failed to load global site settings in init.php: " . $e->getMessage());
    }

    // Provide defaults for any essential setting not found in the DB
    $default_values = [
        'site_name' => 'اسم الموقع الافتراضي',
        'site_description' => 'وصف الموقع الافتراضي.',
        // Add other necessary defaults here
    ];
    foreach ($essential_settings_keys as $key) {
        if (!isset($GLOBALS['site_settings_global'][$key])) {
            $GLOBALS['site_settings_global'][$key] = $default_values[$key] ?? null;
        }
    }
} else {
    log_error("Database connection not available in init.php for loading global settings.");
    // Populate with defaults if DB is not available
    foreach ($default_sitemap_settings as $key => $value) { // Assuming $default_sitemap_settings is still in scope from generate_sitemap.php, which is not ideal.
                                                            // This part needs careful review of where defaults should live.
                                                            // For now, this is a placeholder for robust default handling.
         if (strpos($key, 'sitemap_') !== 0 && !isset($GLOBALS['site_settings_global'][$key])) { // Avoid overwriting sitemap defaults if they were set for some reason
              $GLOBALS['site_settings_global'][$key] = $value;
         }
    }
     if (!isset($GLOBALS['site_settings_global']['site_name'])) $GLOBALS['site_settings_global']['site_name'] = 'اسم الموقع الافتراضي';
     if (!isset($GLOBALS['site_settings_global']['site_description'])) $GLOBALS['site_settings_global']['site_description'] = 'وصف الموقع الافتراضي.';
}
