<?php
/**
 * صفحة توليد خريطة الموقع
 * 
 * تتيح للمدير توليد وتحديث ملف خريطة الموقع XML لتحسين SEO
 */

require_once __DIR__ . '/init.php'; // Loads admin-specific initialization
// admin_auth.php contains functions like admin_login, admin_logout, is_admin_logged_in (specific version)
// It's assumed that PROJECT_ROOT is defined via config.php loaded in admin/init.php
// and that admin_auth.php's dependencies (like db_query) are met by what admin/init.php sets up,
// OR that admin/init.php will be augmented to make these compatible.
// For now, directly including it using PROJECT_ROOT.
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . '/includes/functions/admin_auth.php';
} else {
    // Fallback or error if PROJECT_ROOT is not defined, though it should be by admin/init.php
    require_once dirname(__DIR__) . '/includes/functions/admin_auth.php';
}

// التحقق من تسجيل الدخول
check_admin_login();

// تحديد مسار ملف خريطة الموقع
$sitemap_path = PROJECT_ROOT . '/sitemap.xml'; // Use PROJECT_ROOT

// Define default settings
$default_sitemap_settings = [
    'sitemap_auto_generate' => 0,
    'sitemap_frequency' => 'weekly',
    'sitemap_include_images' => 0,
    'sitemap_priority_home' => 1.0,
    'sitemap_priority_services' => 0.8,
    'sitemap_priority_projects' => 0.8,
    'sitemap_last_generated' => null
];

// Function to save a single setting
function save_single_setting($setting_name, $setting_value) {
    global $db;
    $sql = "INSERT INTO settings (setting_name, setting_value, created_at, updated_at)
            VALUES (:setting_name, :setting_value, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = :setting_value, updated_at = NOW()";
    return $db->execute($sql, [':setting_name' => $setting_name, ':setting_value' => $setting_value]);
}

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error_message = "خطأ في التحقق (CSRF).";
    } else {
        switch ($action) {
            case 'generate_sitemap':
                $result = generate_sitemap(); // This function will also update sitemap_last_generated
                if ($result) {
                    $success_message = "تم توليد خريطة الموقع بنجاح";
                } else {
                    $error_message = "حدث خطأ أثناء توليد خريطة الموقع";
                }
                break;
                
            case 'update_settings':
                $settings_to_update = [
                    'sitemap_auto_generate' => isset($_POST['sitemap_auto_generate']) ? 1 : 0,
                    'sitemap_frequency' => $_POST['sitemap_frequency'] ?? 'weekly',
                    'sitemap_include_images' => isset($_POST['sitemap_include_images']) ? 1 : 0,
                    'sitemap_priority_home' => (float)($_POST['sitemap_priority_home'] ?? 1.0),
                    'sitemap_priority_services' => (float)($_POST['sitemap_priority_services'] ?? 0.8),
                    'sitemap_priority_projects' => (float)($_POST['sitemap_priority_projects'] ?? 0.8)
                ];

                $all_saved = true;
                foreach($settings_to_update as $key => $value) {
                    if (!save_single_setting($key, $value)) {
                        $all_saved = false;
                        break;
                    }
                }

                if ($all_saved) {
                    $success_message = "تم تحديث إعدادات خريطة الموقع بنجاح";
                } else {
                    $error_message = "فشل تحديث بعض إعدادات خريطة الموقع.";
                }
                break;
        }
    }
}

// الحصول على إعدادات خريطة الموقع من جدول settings (key-value)
$settings_from_db = $db->query("SELECT setting_name, setting_value FROM settings WHERE setting_name LIKE 'sitemap_%'");
$current_settings = [];
foreach ($settings_from_db as $row) {
    $current_settings[$row['setting_name']] = $row['setting_value'];
}
// Merge with defaults to ensure all keys are present
$settings = array_merge($default_sitemap_settings, $current_settings);


// التحقق من وجود ملف خريطة الموقع
$sitemap_path = PROJECT_ROOT . '/sitemap.xml'; // Use PROJECT_ROOT
$sitemap_size = $sitemap_exists ? filesize($sitemap_path) : 0;
$sitemap_url = $sitemap_exists ? SITE_URL . '/sitemap.xml' : '';

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">توليد خريطة الموقع</h1>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="generate_sitemap">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sitemap"></i> توليد خريطة الموقع
                    </button>
                </form>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- معلومات خريطة الموقع -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">معلومات خريطة الموقع</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>حالة خريطة الموقع:</h6>
                                <?php if ($sitemap_exists): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> خريطة الموقع موجودة
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> خريطة الموقع غير موجودة
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($sitemap_exists): ?>
                                <div class="mb-3">
                                    <h6>آخر تحديث:</h6>
                                    <p><?php echo date('Y-m-d H:i', strtotime($settings['sitemap_last_generated'] ?? 'now')); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>حجم الملف:</h6>
                                    <p><?php echo format_file_size($sitemap_size); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>رابط خريطة الموقع:</h6>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?php echo $sitemap_url; ?>" readonly>
                                        <a href="<?php echo $sitemap_url; ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <h6>نصائح لمحركات البحث:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-info-circle text-info"></i> قم بإضافة خريطة الموقع إلى Google Search Console</li>
                                    <li><i class="fas fa-info-circle text-info"></i> تأكد من تحديث خريطة الموقع بانتظام</li>
                                    <li><i class="fas fa-info-circle text-info"></i> تأكد من إضافة الرابط في ملف robots.txt</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">إعدادات خريطة الموقع</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="auto_generate" id="auto_generate" 
                                           <?php echo ($settings['sitemap_auto_generate'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_generate">توليد خريطة الموقع تلقائياً</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="frequency" class="form-label">تكرار التوليد التلقائي</label>
                                    <select name="frequency" id="frequency" class="form-select">
                                        <option value="daily" <?php echo ($settings['sitemap_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>يومياً</option>
                                        <option value="weekly" <?php echo ($settings['sitemap_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>أسبوعياً</option>
                                        <option value="monthly" <?php echo ($settings['sitemap_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>شهرياً</option>
                                    </select>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="include_images" id="include_images" 
                                           <?php echo ($settings['sitemap_include_images'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="include_images">تضمين الصور في خريطة الموقع</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="priority_home" class="form-label">أولوية الصفحة الرئيسية</label>
                                    <input type="number" name="priority_home" id="priority_home" class="form-control" 
                                           value="<?php echo $settings['sitemap_priority_home'] ?? 1.0; ?>" min="0.1" max="1.0" step="0.1">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="priority_services" class="form-label">أولوية صفحات الخدمات</label>
                                    <input type="number" name="priority_services" id="priority_services" class="form-control" 
                                           value="<?php echo $settings['sitemap_priority_services'] ?? 0.8; ?>" min="0.1" max="1.0" step="0.1">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="priority_projects" class="form-label">أولوية صفحات المشاريع</label>
                                    <input type="number" name="priority_projects" id="priority_projects" class="form-control" 
                                           value="<?php echo $settings['sitemap_priority_projects'] ?? 0.8; ?>" min="0.1" max="1.0" step="0.1">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- محتوى خريطة الموقع -->
            <?php if ($sitemap_exists): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">محتوى خريطة الموقع</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>الرابط</th>
                                        <th>آخر تعديل</th>
                                        <th>التكرار</th>
                                        <th>الأولوية</th>
                                    </tr>
                                </thead>
                                <tbody id="sitemapContent">
                                    <!-- سيتم تحميل محتوى خريطة الموقع هنا عبر JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// تحميل محتوى خريطة الموقع
function loadSitemapContent() {
    <?php if ($sitemap_exists): ?>
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_sitemap_content'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('sitemapContent').innerHTML = data.html;
        }
    });
    <?php endif; ?>
}

// تحميل محتوى خريطة الموقع عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    loadSitemapContent();
});
</script>

<?php
// دالة لتوليد خريطة الموقع
function generate_sitemap() {
    global $db, $sitemap_path, $default_sitemap_settings; // Ensure $default_sitemap_settings is accessible or passed
    
    try {
        // الحصول على إعدادات خريطة الموقع من جدول settings (key-value)
        $settings_from_db = $db->query("SELECT setting_name, setting_value FROM settings WHERE setting_name LIKE 'sitemap_%'");
        $current_sitemap_settings = [];
        foreach ($settings_from_db as $row) {
            $current_sitemap_settings[$row['setting_name']] = $row['setting_value'];
        }
        $sitemap_config = array_merge($default_sitemap_settings, $current_sitemap_settings);

        $include_images = (bool)($sitemap_config['sitemap_include_images'] ?? 0);
        $priority_home = (float)($sitemap_config['sitemap_priority_home'] ?? 1.0);
        $priority_services = (float)($sitemap_config['sitemap_priority_services'] ?? 0.8);
        $priority_projects = (float)($sitemap_config['sitemap_priority_projects'] ?? 0.8);
        
        // بدء ملف XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        
        if ($include_images) {
            $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        
        $xml .= '>' . "\n";
        
        // إضافة الصفحة الرئيسية
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . SITE_URL . '/</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>' . $priority_home . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
        
        // إضافة صفحات الخدمات - no is_active filter, no slug, no service_images
        $services = $db->query("SELECT id, updated_at FROM services ORDER BY updated_at DESC");
        if ($services) {
            foreach ($services as $service) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . SITE_URL . '/service-details.php?id=' . $service['id'] . '</loc>' . "\n"; // Use id
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($service['updated_at'])) . '</lastmod>' . "\n";
                $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                $xml .= '    <priority>' . $priority_services . '</priority>' . "\n";
                // Service images removed
                $xml .= '  </url>' . "\n";
            }
        }
        
        // إضافة صفحات المشاريع - no is_active filter, no slug, use project.image_url
        $projects = $db->query("SELECT id, image_url, updated_at FROM projects ORDER BY updated_at DESC");
        if ($projects) {
            foreach ($projects as $project) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . SITE_URL . '/project-details.php?id=' . $project['id'] . '</loc>' . "\n"; // Use id
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($project['updated_at'])) . '</lastmod>' . "\n";
                $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                $xml .= '    <priority>' . $priority_projects . '</priority>' . "\n";
                
                if ($include_images && !empty($project['image_url'])) {
                    $xml .= '    <image:image>' . "\n";
                    // Assuming UPLOAD_URL is the relative path like 'uploads/' and image_url is 'projects/image.jpg'
                    // And SITE_URL is like 'https://example.com'
                    $xml .= '      <image:loc>' . SITE_URL . (defined('UPLOAD_URL') ? UPLOAD_URL : 'uploads/') . htmlspecialchars($project['image_url']) . '</image:loc>' . "\n";
                    // Add <image:title> if you have a title/alt for the project image_url
                    // $xml .= '      <image:title>' . htmlspecialchars($project['title']) . '</image:title>' . "\n";
                    $xml .= '    </image:image>' . "\n";
                }
                $xml .= '  </url>' . "\n";
            }
        }
        
        // إضافة صفحات ثابتة أخرى
        $static_pages = [
            ['url' => '/about.php', 'changefreq' => 'monthly', 'priority' => 0.7],
            ['url' => '/services.php', 'changefreq' => 'weekly', 'priority' => 0.8],
            ['url' => '/projects.php', 'changefreq' => 'weekly', 'priority' => 0.8],
            ['url' => '/contact.php', 'changefreq' => 'monthly', 'priority' => 0.7]
        ];
        
        foreach ($static_pages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . SITE_URL . $page['url'] . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        // إغلاق ملف XML
        $xml .= '</urlset>';
        
        // حفظ الملف
        file_put_contents($sitemap_path, $xml);
        
        // تحديث تاريخ آخر توليد في جدول settings
        save_single_setting('sitemap_last_generated', date('Y-m-d H:i:s'));
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في توليد خريطة الموقع: " . $e->getMessage());
        return false;
    }
}

// دالة لتنسيق حجم الملف
function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>

<?php include 'includes/footer.php'; ?>
