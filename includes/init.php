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

// تعريف المسارات الأساسية
define('INCLUDES_DIR', BASE_PATH . '/includes');
define('FUNCTIONS_DIR', INCLUDES_DIR . '/functions');
define('ADMIN_DIR', BASE_PATH . '/admin');
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('ASSETS_DIR', BASE_PATH . '/assets');

// تعريف روابط الموقع
$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$site_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);

define('SITE_URL', rtrim($site_url, '/'));
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_URL', SITE_URL . '/uploads');
define('ASSETS_URL', SITE_URL . '/assets');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'project_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة للموقع
define('SITE_NAME', 'اسم الموقع');
define('SITE_DESCRIPTION', 'وصف الموقع');
define('SITE_EMAIL', 'info@example.com');
define('SITE_PHONE', '+1234567890');
define('SITE_ADDRESS', 'عنوان الموقع');

// تضمين ملفات الوظائف الأساسية
require_once FUNCTIONS_DIR . '/database.php';
require_once FUNCTIONS_DIR . '/utilities.php';
require_once FUNCTIONS_DIR . '/service_functions.php';
require_once FUNCTIONS_DIR . '/project_functions.php';
require_once FUNCTIONS_DIR . '/admin_auth.php';
require_once FUNCTIONS_DIR . '/code_optimization.php';
require_once FUNCTIONS_DIR . '/future_recommendations.php';

// إنشاء اتصال قاعدة البيانات
$db = db_connect();

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * تنظيف المدخلات
 * 
 * @param string $input النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * إعادة التوجيه إلى صفحة أخرى
 * 
 * @param string $url الرابط المراد التوجيه إليه
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * اختصار النص
 * 
 * @param string $text النص المراد اختصاره
 * @param int $length الطول المطلوب
 * @param string $append النص المضاف في نهاية النص المختصر
 * @return string النص المختصر
 */
function truncate_text($text, $length = 100, $append = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $length, 'UTF-8');
    $text = mb_substr($text, 0, mb_strrpos($text, ' ', 0, 'UTF-8'), 'UTF-8');
    
    return $text . $append;
}

/**
 * تنسيق التاريخ
 * 
 * @param string $date التاريخ المراد تنسيقه
 * @param string $format صيغة التاريخ
 * @return string التاريخ المنسق
 */
function format_date($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

/**
 * التحقق من تسجيل دخول المدير
 * 
 * @return bool هل المدير مسجل الدخول
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * الحصول على معرف المدير الحالي
 * 
 * @return int|null معرف المدير أو null إذا لم يكن مسجل الدخول
 */
function get_current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * عرض رسالة تنبيه
 * 
 * @param string $message نص الرسالة
 * @param string $type نوع الرسالة (success, info, warning, danger)
 * @return string كود HTML للرسالة
 */
function show_alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        ' . $message . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}

/**
 * تحميل التحميل الكسول للصور
 * 
 * @param string $image_path مسار الصورة
 * @param string $alt النص البديل للصورة
 * @param string $class فئات CSS إضافية
 * @return string كود HTML للصورة مع التحميل الكسول
 */
function lazy_load_image($image_path, $alt = '', $class = '') {
    return '<img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 3 2\'%3E%3C/svg%3E" data-src="' . $image_path . '" alt="' . $alt . '" class="lazyload ' . $class . '">';
}

/**
 * إنشاء رابط صفحة
 * 
 * @param string $page اسم الصفحة
 * @param array $params معلمات إضافية
 * @return string الرابط الكامل
 */
function create_page_link($page, $params = []) {
    $url = SITE_URL . '/' . $page;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * إنشاء رابط خدمة
 * 
 * @param string $slug المعرف المخصص للخدمة
 * @return string رابط صفحة تفاصيل الخدمة
 */
function create_service_link($slug) {
    return create_page_link('service-details.php', ['slug' => $slug]);
}

/**
 * إنشاء رابط مشروع
 * 
 * @param string $slug المعرف المخصص للمشروع
 * @return string رابط صفحة تفاصيل المشروع
 */
function create_project_link($slug) {
    return create_page_link('project-details.php', ['slug' => $slug]);
}

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
        require_once FUNCTIONS_DIR . '/admin_seo_functions.php';
    }
    
    // إنشاء وسوم SEO
    echo generate_seo_tags($title, $description, $keywords, $canonical_url);
}
