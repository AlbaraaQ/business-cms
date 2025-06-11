<?php
/**
 * ملف تضمين الملفات الأساسية في لوحة التحكم
 *
 * يقوم بتضمين جميع ملفات CSS و JavaScript المطلوبة للوحة التحكم
 */

// تضمين ملفات CSS
function include_admin_css() {
    // SITE_URL and PROJECT_ROOT should be defined from config.php (loaded via admin/init.php)
    $admin_asset_base_url = SITE_URL . '/admin/assets'; // Web path to admin-specific assets
    $public_vendor_base_url = SITE_URL . '/public/assets/vendors'; // Web path to shared vendor assets

    $css_files = [
        // Examples assuming vendor files are placed in public/assets/vendors/
        // These paths might need adjustment based on actual vendor file structure if locally hosted.
        // It's often better to use CDNs for common libraries if possible.
        // $public_vendor_base_url . '/bootstrap/css/bootstrap.rtl.min.css',
        // $public_vendor_base_url . '/fontawesome/css/all.min.css',

        // For now, using placeholder CDN links or assuming they might be added later.
        // If local files are preferred, ensure they exist at the specified paths.
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css', // Example CDN
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', // Example CDN

        $admin_asset_base_url . '/admin.css', // Corrected: was admin-custom.css, but admin.css was moved there
    ];

    foreach ($css_files as $url) {
        $file_system_path = '';
        if (strpos($url, $admin_asset_base_url) === 0) {
            // Admin specific asset
            $relative_path = str_replace($admin_asset_base_url . '/', '', $url);
            $file_system_path = PROJECT_ROOT . '/admin/assets/' . $relative_path;
        } elseif (strpos($url, $public_vendor_base_url) === 0) {
            // Public vendor asset (if locally hosted)
            $relative_path = str_replace($public_vendor_base_url . '/', '', $url);
            $file_system_path = PROJECT_ROOT . '/public/assets/vendors/' . $relative_path;
        }
        // Else, it's an external CDN link, filemtime doesn't apply / isn't needed.

        if ($file_system_path && file_exists($file_system_path)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '?v=' . filemtime($file_system_path) . '">' . PHP_EOL;
        } else {
            // For CDN links or files not found, link without version
            echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">' . PHP_EOL;
        }
    }
}

// تضمين ملفات JavaScript
function include_admin_js() {
    $admin_asset_base_url = SITE_URL . '/admin/assets';
    $public_vendor_base_url = SITE_URL . '/public/assets/vendors';

    $js_files = [
        // Example CDN links or local paths if vendor files are hosted
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js', // Example CDN
        // $public_vendor_base_url . '/tinymce/tinymce.min.js', // Example local
        // $public_vendor_base_url . '/sortablejs/Sortable.min.js', // Example local
        // $public_vendor_base_url . '/dropzone/dropzone.min.js',   // Example local

        $admin_asset_base_url . '/admin.js', // Corrected: was admin-custom.js, but admin.js was moved there
    ];

    foreach ($js_files as $url) {
        $file_system_path = '';
        if (strpos($url, $admin_asset_base_url) === 0) {
            $relative_path = str_replace($admin_asset_base_url . '/', '', $url);
            $file_system_path = PROJECT_ROOT . '/admin/assets/' . $relative_path;
        } elseif (strpos($url, $public_vendor_base_url) === 0) {
            $relative_path = str_replace($public_vendor_base_url . '/', '', $url);
            $file_system_path = PROJECT_ROOT . '/public/assets/vendors/' . $relative_path;
        }

        if ($file_system_path && file_exists($file_system_path)) {
            echo '<script src="' . htmlspecialchars($url) . '?v=' . filemtime($file_system_path) . '"></script>' . PHP_EOL;
        } else {
            echo '<script src="' . htmlspecialchars($url) . '"></script>' . PHP_EOL;
        }
    }
}

?>
