<?php
// تفعيل عرض الأخطاء للتصحيح (يمكن إزالته في البيئة الإنتاجية)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// تضمين ملف التهيئة
require_once __DIR__ . '/init.php';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    
    // التحقق من توكن CSRF
    if (!verify_csrf_token()) {
        $_SESSION['login_error'] = 'خطأ في التحقق من الأمان. يرجى المحاولة مرة أخرى.';
        redirect(base_url('admin/index.php'));
        exit();
    }

    // تنظيف المدخلات
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // لا ننظف كلمة المرور قبل التحقق منها

    // التحقق من الحقول المطلوبة
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'يرجى إدخال اسم المستخدم وكلمة المرور';
        redirect(base_url('admin/index.php'));
        exit();
    }

    // محاولة تسجيل الدخول
    try {
        global $db;
        // Changed user_id to id in SELECT
        $user = $db->queryOne("SELECT id, username, password_hash, role FROM users WHERE username = ?", [$username]);
         
        // Removed backdoor password '123456789'
        if ($user && password_verify($password, $user['password_hash'])) {
            // تسجيل الدخول ناجح
            // Changed $user['user_id'] to $user['id']
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];

            // تجديد معرف الجلسة لأمان أفضل
            session_regenerate_id(true);

            // حذف أي أخطاء سابقة
            unset($_SESSION['login_error']);

            // إعادة التوجيه إلى لوحة التحكم
            redirect(base_url('admin/index.php?page=dashboard'));
            exit();
        } else {
            $_SESSION['login_error'] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            log_error("محاولة تسجيل دخول فاشلة لاسم المستخدم: " . $username);
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = 'حدث خطأ أثناء محاولة تسجيل الدخول';
        log_error("خطأ في تسجيل الدخول: " . $e->getMessage());
    }

    // إعادة التوجيه عند فشل تسجيل الدخول
    redirect(base_url('admin/index.php'));
    exit();
}

// التحقق من تسجيل الدخول
if (!is_admin_logged_in()) {
    // عرض نموذج تسجيل الدخول
    include __DIR__ . '/login_form.php';
    exit();
}

// تحديد الصفحة المطلوبة
$page = sanitize_input($_GET['page'] ?? 'dashboard');

// تضمين هيكل لوحة التحكم
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

echo '<div class="flex-1 p-6 bg-gray-100 overflow-y-auto">';
echo '<main>';

// توجيه حسب الصفحة المطلوبة
switch ($page) {
    case 'dashboard':
        include __DIR__ . '/dashboard.php';
        break;
    case 'change_password':
        include __DIR__ . '/change_password.php';
        break;
    case 'sections':
        include __DIR__ . '/sections_management.php';
        break;
    // Removed 'homepage_sections' case as it's redundant with 'sections'
    case 'services':
        include __DIR__ . '/services_management.php';
        break;
    case 'projects':
        include __DIR__ . '/projects_management.php';
        break;
    case 'testimonials':
        include __DIR__ . '/testimonials_management.php';
        break;
    case 'facts':
        include __DIR__ . '/facts_management.php';
        break;
    case 'site_settings':
        include __DIR__ . '/site_settings.php';
        break;
    case 'users':
        include __DIR__ . '/users_management.php'; // Changed to include the actual management page
        break;
    default:
        http_response_code(404);
        echo '<div class="bg-white p-6 rounded-lg shadow-md">';
        echo '<h1 class="text-xl font-semibold text-red-600">صفحة غير موجودة</h1>';
        echo '<p class="mt-2 text-gray-700">الصفحة المطلوبة (' . htmlspecialchars($page) . ') غير متوفرة.</p>';
        echo '</div>';
        break;
}

echo '</main>';
echo '</div>';

// تضمين تذييل الصفحة
include __DIR__ . '/includes/footer.php';
?>