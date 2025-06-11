<?php
/**
 * ملف رأس الصفحة
 * 
 * هذا الملف يحتوي على رأس صفحات الموقع
 * ويتم تضمينه في جميع صفحات الموقع
 */

// $site_settings = get_site_settings(); // Removed, use $GLOBALS['site_settings_global']

// تعيين عنوان الصفحة الافتراضي إذا لم يتم تعيينه
if (!isset($page_title)) {
    $page_title = $GLOBALS['site_settings_global']['site_name'] ?? 'الموقع';
}

// تعيين إعدادات SEO الافتراضية إذا لم يتم تعيينها
// $page_description and $page_keywords are now expected to be set by the calling page (e.g., index.php, service-details.php)
// based on get_seo_for_page() or specific content.
// The $seo_settings array is now expected to be populated by the calling page.
// Fallbacks here are minimal.
if (!isset($seo_settings) || !is_array($seo_settings)) {
    $seo_settings = []; // Ensure it's an array
}
if (!isset($seo_settings['meta_title'])) {
    $seo_settings['meta_title'] = $page_title;
}
if (!isset($seo_settings['meta_description'])) {
    $seo_settings['meta_description'] = $GLOBALS['site_settings_global']['site_description'] ?? '';
}
if (!isset($seo_settings['meta_keywords'])) {
    $seo_settings['meta_keywords'] = $GLOBALS['site_settings_global']['meta_keywords'] ?? ''; // Assuming 'meta_keywords' is a global setting key
}
if (!isset($seo_settings['canonical_url'])) {
    $seo_settings['canonical_url'] = SITE_URL . $_SERVER['REQUEST_URI'];
}
if (!isset($seo_settings['og_image']) && isset($GLOBALS['site_settings_global']['og_image_path'])) { // Default OG image from global settings
    $seo_settings['og_image'] = SITE_URL . UPLOAD_URL_PUBLIC_ACCESSIBLE_PATH . htmlspecialchars($GLOBALS['site_settings_global']['og_image_path']);
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
    <?php if (isset($GLOBALS['site_settings_global']['google_analytics_id']) && !empty($GLOBALS['site_settings_global']['google_analytics_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($GLOBALS['site_settings_global']['google_analytics_id']); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($GLOBALS['site_settings_global']['google_analytics_id']); ?>');
    </script>
    <?php endif; ?>

    <?php if (isset($GLOBALS['site_settings_global']['site_favicon_path']) && !empty($GLOBALS['site_settings_global']['site_favicon_path'])): ?>
        <link rel="icon" href="<?php echo SITE_URL . UPLOAD_URL_PUBLIC_ACCESSIBLE_PATH . htmlspecialchars($GLOBALS['site_settings_global']['site_favicon_path']); ?>">
    <?php endif; ?>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <!-- شعار الموقع -->
                <a class="navbar-brand" href="<?php echo base_url(); // Use base_url() for homepage link ?>">
                    <?php if (isset($GLOBALS['site_settings_global']['site_logo_path']) && !empty($GLOBALS['site_settings_global']['site_logo_path'])): ?>
                        <img src="<?php echo SITE_URL . UPLOAD_URL_PUBLIC_ACCESSIBLE_PATH . htmlspecialchars($GLOBALS['site_settings_global']['site_logo_path']); ?>" alt="<?php echo htmlspecialchars($GLOBALS['site_settings_global']['site_name'] ?? 'Site Logo'); ?>" height="40">
                    <?php else: ?>
                        <?php echo htmlspecialchars($GLOBALS['site_settings_global']['site_name'] ?? 'الموقع'); ?>
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>" href="<?php echo base_url('about.php'); ?>">من نحن</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_GET['#services'])) || basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>" href="<?php echo base_url('index.php#services'); ?>">خدماتنا</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_GET['#projects'])) || basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'active' : ''; ?>" href="<?php echo base_url('index.php#projects'); ?>">مشاريعنا</a>
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
