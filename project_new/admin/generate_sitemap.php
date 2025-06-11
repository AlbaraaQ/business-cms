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
$sitemap_path = BASE_PATH . '/sitemap.xml';

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_sitemap':
            $result = generate_sitemap();
            
            if ($result) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'generate_sitemap', 'sitemap', null);
                
                $success_message = "تم توليد خريطة الموقع بنجاح";
            } else {
                $error_message = "حدث خطأ أثناء توليد خريطة الموقع";
            }
            break;
            
        case 'update_settings':
            $auto_generate = isset($_POST['auto_generate']) ? 1 : 0;
            $frequency = $_POST['frequency'] ?? 'weekly';
            $include_images = isset($_POST['include_images']) ? 1 : 0;
            $priority_home = (float)$_POST['priority_home'];
            $priority_services = (float)$_POST['priority_services'];
            $priority_projects = (float)$_POST['priority_projects'];
            
            // تحديث الإعدادات في قاعدة البيانات
            $sql_update_settings = "UPDATE site_settings SET
                                        sitemap_auto_generate = :sitemap_auto_generate,
                                        sitemap_frequency = :sitemap_frequency,
                                        sitemap_include_images = :sitemap_include_images,
                                        sitemap_priority_home = :sitemap_priority_home,
                                        sitemap_priority_services = :sitemap_priority_services,
                                        sitemap_priority_projects = :sitemap_priority_projects
                                    WHERE id = 1";
            $params_update = [
                ':sitemap_auto_generate' => $auto_generate,
                ':sitemap_frequency' => $frequency,
                ':sitemap_include_images' => $include_images,
                ':sitemap_priority_home' => $priority_home,
                ':sitemap_priority_services' => $priority_services,
                ':sitemap_priority_projects' => $priority_projects
            ];
            if ($db->execute($sql_update_settings, $params_update)) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'update_sitemap_settings', 'site_settings', 1);
                $success_message = "تم تحديث إعدادات خريطة الموقع بنجاح";
            } else {
                $error_message = "فشل تحديث إعدادات خريطة الموقع.";
            }
            break;
    }
}

// الحصول على إعدادات خريطة الموقع
$settings_query_sql = "SELECT
    sitemap_auto_generate, 
    sitemap_frequency, 
    sitemap_include_images,
    sitemap_priority_home,
    sitemap_priority_services,
    sitemap_priority_projects,
    sitemap_last_generated
FROM site_settings WHERE id = 1";
$settings = $db->queryOne($settings_query_sql);

// التحقق من وجود ملف خريطة الموقع
$sitemap_exists = file_exists($sitemap_path);
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
    global $db, $sitemap_path;
    
    try {
        // الحصول على إعدادات خريطة الموقع
        $settings_sitemap_sql = "SELECT
            sitemap_include_images,
            sitemap_priority_home,
            sitemap_priority_services,
            sitemap_priority_projects
        FROM site_settings WHERE id = 1";
        $settings = $db->queryOne($settings_sitemap_sql);
        
        $include_images = $settings['sitemap_include_images'] ?? 0;
        $priority_home = $settings['sitemap_priority_home'] ?? 1.0;
        $priority_services = $settings['sitemap_priority_services'] ?? 0.8;
        $priority_projects = $settings['sitemap_priority_projects'] ?? 0.8;
        
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
        
        // إضافة صفحات الخدمات
        $services = $db->query("SELECT id, slug, updated_at FROM services WHERE is_active = 1");
        if ($services) {
            foreach ($services as $service) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . SITE_URL . '/service-details.php?slug=' . $service['slug'] . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($service['updated_at'])) . '</lastmod>' . "\n";
                $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                $xml .= '    <priority>' . $priority_services . '</priority>' . "\n";
                
                // إضافة الصور إذا كان مطلوباً
                if ($include_images) {
                    // الصورة الرئيسية
                    $main_image = $db->queryOne("SELECT image_path, alt_text FROM service_images WHERE service_id = :service_id AND is_main = 1", [':service_id' => $service['id']]);
                    if ($main_image) {
                        $xml .= '    <image:image>' . "\n";
                        $xml .= '      <image:loc>' . SITE_URL . '/' . $main_image['image_path'] . '</image:loc>' . "\n";
                        if ($main_image['alt_text']) {
                            $xml .= '      <image:title>' . htmlspecialchars($main_image['alt_text']) . '</image:title>' . "\n";
                        }
                        $xml .= '    </image:image>' . "\n";
                    }

                    // الصور الإضافية
                    $images = $db->query("SELECT image_path, alt_text FROM service_images WHERE service_id = :service_id AND is_main = 0", [':service_id' => $service['id']]);
                    if($images){
                        foreach ($images as $image) {
                            $xml .= '    <image:image>' . "\n";
                            $xml .= '      <image:loc>' . SITE_URL . '/' . $image['image_path'] . '</image:loc>' . "\n";
                            if ($image['alt_text']) {
                                $xml .= '      <image:title>' . htmlspecialchars($image['alt_text']) . '</image:title>' . "\n";
                            }
                            $xml .= '    </image:image>' . "\n";
                        }
                    }
                }
                $xml .= '  </url>' . "\n";
            }
        }
        
        // إضافة صفحات المشاريع
        $projects = $db->query("SELECT id, slug, updated_at FROM projects WHERE is_active = 1");
        if ($projects) {
            foreach ($projects as $project) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . SITE_URL . '/project-details.php?slug=' . $project['slug'] . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($project['updated_at'])) . '</lastmod>' . "\n";
                $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                $xml .= '    <priority>' . $priority_projects . '</priority>' . "\n";
                
                // إضافة الصور إذا كان مطلوباً
                if ($include_images) {
                    // الصورة الرئيسية
                    $main_image = $db->queryOne("SELECT image_path, alt_text FROM project_images WHERE project_id = :project_id AND is_main = 1", [':project_id' => $project['id']]);
                    if ($main_image) {
                        $xml .= '    <image:image>' . "\n";
                        $xml .= '      <image:loc>' . SITE_URL . '/' . $main_image['image_path'] . '</image:loc>' . "\n";
                        if ($main_image['alt_text']) {
                            $xml .= '      <image:title>' . htmlspecialchars($main_image['alt_text']) . '</image:title>' . "\n";
                        }
                        $xml .= '    </image:image>' . "\n";
                    }

                    // الصور الإضافية
                    $images = $db->query("SELECT image_path, alt_text FROM project_images WHERE project_id = :project_id AND is_main = 0", [':project_id' => $project['id']]);
                    if($images){
                        foreach ($images as $image) {
                            $xml .= '    <image:image>' . "\n";
                            $xml .= '      <image:loc>' . SITE_URL . '/' . $image['image_path'] . '</image:loc>' . "\n";
                            if ($image['alt_text']) {
                                $xml .= '      <image:title>' . htmlspecialchars($image['alt_text']) . '</image:title>' . "\n";
                            }
                            $xml .= '    </image:image>' . "\n";
                        }
                    }
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
        
        // تحديث تاريخ آخر توليد
        $db->execute("UPDATE site_settings SET sitemap_last_generated = NOW() WHERE id = 1");
        
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
