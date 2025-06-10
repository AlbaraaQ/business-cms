<?php
/**
 * ملف تضمين رأس لوحة التحكم
 * 
 * يحتوي على الهيكل العام لرأس صفحات لوحة التحكم
 */

// التأكد من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// تضمين ملف الأصول
require_once 'includes/assets.php';

// الحصول على معلومات المستخدم الحالي
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// الحصول على عدد الرسائل غير المقروءة
$stmt = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
$unread_messages_count = $stmt->fetchColumn();

// تحديد الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo SITE_NAME; ?></title>
    
    <!-- تضمين ملفات CSS -->
    <?php include_admin_css(); ?>
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="/assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" class="img-fluid" style="max-width: 150px;">
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'services_management.php' ? 'active' : ''; ?>" href="services_management.php">
                                <i class="fas fa-cogs"></i>
                                إدارة الخدمات
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'projects_management.php' ? 'active' : ''; ?>" href="projects_management.php">
                                <i class="fas fa-project-diagram"></i>
                                إدارة المشاريع
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'messages_management.php' ? 'active' : ''; ?>" href="messages_management.php">
                                <i class="fas fa-envelope"></i>
                                إدارة الرسائل
                                <?php if ($unread_messages_count > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $unread_messages_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>الإعدادات</span>
                    </h6>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'social_settings.php' ? 'active' : ''; ?>" href="social_settings.php">
                                <i class="fas fa-share-alt"></i>
                                الشبكات الاجتماعية
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'generate_sitemap.php' ? 'active' : ''; ?>" href="generate_sitemap.php">
                                <i class="fas fa-sitemap"></i>
                                خريطة الموقع
                            </a>
                        </li>
                        
                        <?php if (check_admin_role('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'users_management.php' ? 'active' : ''; ?>" href="users_management.php">
                                    <i class="fas fa-users"></i>
                                    إدارة المستخدمين
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>أدوات</span>
                    </h6>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'optimize_images.php' ? 'active' : ''; ?>" href="optimize_images.php">
                                <i class="fas fa-images"></i>
                                تحسين الصور
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'backup_database.php' ? 'active' : ''; ?>" href="backup_database.php">
                                <i class="fas fa-database"></i>
                                النسخ الاحتياطي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <button class="navbar-toggler d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($current_user['full_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> الملف الشخصي</a></li>
                                <li><a class="dropdown-item" href="../" target="_blank"><i class="fas fa-external-link-alt"></i> عرض الموقع</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- حاوية التنبيهات -->
                <div class="alert-container"></div>
