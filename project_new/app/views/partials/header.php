<?php
/**
 * ملف رأس الصفحة
 * 
 * هذا الملف يحتوي على رأس صفحات الموقع
 * ويتم تضمينه في جميع صفحات الموقع
 */

// الحصول على إعدادات الموقع
$site_settings = get_site_settings();

// تعيين عنوان الصفحة الافتراضي إذا لم يتم تعيينه
if (!isset($page_title)) {
    $page_title = isset($site_settings['site_name']) ? $site_settings['site_name'] : 'الموقع';
}

// تعيين إعدادات SEO الافتراضية إذا لم يتم تعيينها
if (!isset($seo_settings)) {
    $seo_settings = [
        'meta_title' => $page_title,
        'meta_description' => isset($site_settings['site_description']) ? $site_settings['site_description'] : '',
        'keywords' => isset($site_settings['site_keywords']) ? $site_settings['site_keywords'] : '',
        'canonical_url' => SITE_URL . $_SERVER['REQUEST_URI']
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- إعدادات SEO -->
    <title><?php echo htmlspecialchars($seo_settings['meta_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_settings['meta_description']); ?>">
    <?php if (!empty($seo_settings['keywords'])): ?>
        <meta name="keywords" content="<?php echo htmlspecialchars($seo_settings['keywords']); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($seo_settings['canonical_url']); ?>">
    
    <!-- وسوم Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_settings['meta_title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_settings['meta_description']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($seo_settings['canonical_url']); ?>">
    <?php if (isset($seo_settings['og_image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($seo_settings['og_image']); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_AR">
    
    <!-- وسوم Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_settings['meta_title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_settings['meta_description']); ?>">
    <?php if (isset($seo_settings['og_image'])): ?>
        <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_settings['og_image']); ?>">
    <?php endif; ?>
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS الأساسي -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- CSS للمكونات الإضافية -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.19/dist/fancybox/fancybox.css">
    
    <!-- تحسينات الأمان -->
    <?php enhance_page_security(); ?>
    
    <!-- تحسينات الأداء -->
    <?php optimize_page_performance(); ?>
    
    <!-- رمز تتبع Google Analytics (إذا كان متاحاً) -->
    <?php if (isset($site_settings['google_analytics_id']) && !empty($site_settings['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($site_settings['google_analytics_id']); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($site_settings['google_analytics_id']); ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <!-- شعار الموقع -->
                <a class="navbar-brand" href="index.php">
                    <?php if (isset($site_settings['site_logo']) && !empty($site_settings['site_logo'])): ?>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($site_settings['site_logo']); ?>" alt="<?php echo htmlspecialchars($site_settings['site_name']); ?>" height="40">
                    <?php else: ?>
                        <?php echo htmlspecialchars($site_settings['site_name'] ?? 'الموقع'); ?>
                    <?php endif; ?>
                </a>
                
                <!-- زر القائمة للشاشات الصغيرة -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- روابط التنقل -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">الرئيسية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>" href="about.php">من نحن</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>" href="services.php">خدماتنا</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'active' : ''; ?>" href="projects.php">مشاريعنا</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="contact.php">اتصل بنا</a>
                        </li>
                    </ul>
                    
                    <!-- زر البحث -->
                    <div class="d-flex ms-2">
                        <a href="search.php" class="btn btn-outline-primary">
                            <i data-feather="search" class="icon-sm"></i>
                            <span class="d-none d-md-inline ms-1">بحث</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- المحتوى الرئيسي -->
    <main class="main-content">
