<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إعدادات تحسين محركات البحث (SEO)";

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_type = $_SESSION['message']['type'];
    unset($_SESSION['message']);
}

// Fetch current SEO settings
$seo_settings = $db->queryOne("SELECT * FROM seo_settings ORDER BY id ASC LIMIT 1");
if (!$seo_settings) { // Initialize with empty values if no settings found
    $seo_settings = [
        'meta_title_template' => '{page_title} | {site_name}',
        'meta_description' => '',
        'meta_keywords' => '',
        'og_image' => null,
        'twitter_card_type' => 'summary_large_image',
        'twitter_site' => '',
        'google_analytics_id' => '',
        'google_verification' => '',
        'bing_verification' => '',
        'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /config/\nDisallow: /core/\nDisallow: /includes/\nDisallow: /logs/\n\nSitemap: {site_url}sitemap.xml",
        'sitemap_settings' => json_encode([
            'include_services' => true,
            'include_projects' => true,
            'include_sections' => true,
            'auto_generate' => true,
            'last_generated' => null
        ]),
        'canonical_url' => '',
        'enable_schema' => true,
        'schema_type' => 'LocalBusiness',
        'schema_settings' => json_encode([
            'business_name' => '',
            'business_description' => '',
            'business_logo' => '',
            'business_address' => '',
            'business_phone' => '',
            'business_email' => '',
            'geo_latitude' => '',
            'geo_longitude' => '',
            'opening_hours' => '',
            'price_range' => '₪₪'
        ])
    ];
}

// Decode JSON for sitemap_settings and schema_settings
$sitemap_settings = json_decode($seo_settings['sitemap_settings'] ?? '{}', true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $sitemap_settings = [
        'include_services' => true,
        'include_projects' => true,
        'include_sections' => true,
        'auto_generate' => true,
        'last_generated' => null
    ];
}

$schema_settings = json_decode($seo_settings['schema_settings'] ?? '{}', true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $schema_settings = [
        'business_name' => '',
        'business_description' => '',
        'business_logo' => '',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => '',
        'geo_latitude' => '',
        'geo_longitude' => '',
        'opening_hours' => '',
        'price_range' => '₪₪'
    ];
}

// Get site settings for preview
$site_settings = $db->queryOne("SELECT site_name, site_tagline, site_description FROM settings LIMIT 1");
$site_name = $site_settings['site_name'] ?? 'اسم الموقع';
$site_tagline = $site_settings['site_tagline'] ?? 'شعار الموقع';
$site_description = $site_settings['site_description'] ?? 'وصف الموقع';

// Schema.org types
$schema_types = [
    'LocalBusiness' => 'نشاط تجاري محلي',
    'Organization' => 'منظمة/شركة',
    'Person' => 'شخص',
    'Product' => 'منتج',
    'Service' => 'خدمة',
    'WebSite' => 'موقع ويب',
    'Article' => 'مقالة',
    'Event' => 'حدث'
];

// Twitter card types
$twitter_card_types = [
    'summary' => 'ملخص صغير',
    'summary_large_image' => 'ملخص مع صورة كبيرة',
    'app' => 'تطبيق',
    'player' => 'مشغل وسائط'
];

// Function to generate sitemap
function generate_sitemap() {
    global $db, $sitemap_settings;
    
    $base_url = get_site_url();
    $sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $sitemap_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Add homepage
    $sitemap_content .= '  <url>' . "\n";
    $sitemap_content .= '    <loc>' . htmlspecialchars($base_url) . '</loc>' . "\n";
    $sitemap_content .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    $sitemap_content .= '    <changefreq>weekly</changefreq>' . "\n";
    $sitemap_content .= '    <priority>1.0</priority>' . "\n";
    $sitemap_content .= '  </url>' . "\n";
    
    // Add services if enabled
    if ($sitemap_settings['include_services']) {
        $services = $db->query("SELECT slug, updated_at FROM services WHERE is_active = 1");
        foreach ($services as $service) {
            $sitemap_content .= '  <url>' . "\n";
            $sitemap_content .= '    <loc>' . htmlspecialchars($base_url . 'service/' . $service['slug']) . '</loc>' . "\n";
            $sitemap_content .= '    <lastmod>' . date('Y-m-d', strtotime($service['updated_at'])) . '</lastmod>' . "\n";
            $sitemap_content .= '    <changefreq>monthly</changefreq>' . "\n";
            $sitemap_content .= '    <priority>0.8</priority>' . "\n";
            $sitemap_content .= '  </url>' . "\n";
        }
    }
    
    // Add projects if enabled
    if ($sitemap_settings['include_projects']) {
        $projects = $db->query("SELECT slug, updated_at FROM projects WHERE is_active = 1");
        foreach ($projects as $project) {
            $sitemap_content .= '  <url>' . "\n";
            $sitemap_content .= '    <loc>' . htmlspecialchars($base_url . 'project/' . $project['slug']) . '</loc>' . "\n";
            $sitemap_content .= '    <lastmod>' . date('Y-m-d', strtotime($project['updated_at'])) . '</lastmod>' . "\n";
            $sitemap_content .= '    <changefreq>monthly</changefreq>' . "\n";
            $sitemap_content .= '    <priority>0.8</priority>' . "\n";
            $sitemap_content .= '  </url>' . "\n";
        }
    }
    
    // Add sections if enabled
    if ($sitemap_settings['include_sections']) {
        $sections = $db->query("SELECT section_type, updated_at FROM sections WHERE is_active = 1");
        $added_sections = [];
        
        foreach ($sections as $section) {
            // Only add each section type once
            if (!in_array($section['section_type'], $added_sections)) {
                $added_sections[] = $section['section_type'];
                $sitemap_content .= '  <url>' . "\n";
                $sitemap_content .= '    <loc>' . htmlspecialchars($base_url . '#' . $section['section_type']) . '</loc>' . "\n";
                $sitemap_content .= '    <lastmod>' . date('Y-m-d', strtotime($section['updated_at'])) . '</lastmod>' . "\n";
                $sitemap_content .= '    <changefreq>monthly</changefreq>' . "\n";
                $sitemap_content .= '    <priority>0.7</priority>' . "\n";
                $sitemap_content .= '  </url>' . "\n";
            }
        }
    }
    
    $sitemap_content .= '</urlset>';
    
    // Save sitemap to file
    $sitemap_file = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
    file_put_contents($sitemap_file, $sitemap_content);
    
    // Update last generated timestamp
    $sitemap_settings['last_generated'] = date('Y-m-d H:i:s');
    $db->execute(
        "UPDATE seo_settings SET sitemap_settings = ? WHERE id = ?",
        [json_encode($sitemap_settings), $seo_settings['id'] ?? 1]
    );
    
    return true;
}

// Function to generate robots.txt
function generate_robots_txt($content) {
    $base_url = get_site_url();
    $content = str_replace('{site_url}', $base_url, $content);
    
    // Save robots.txt to file
    $robots_file = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
    file_put_contents($robots_file, $content);
    
    return true;
}

// Handle sitemap generation if requested
if (isset($_GET['generate_sitemap']) && $_GET['generate_sitemap'] == 1) {
    $result = generate_sitemap();
    if ($result) {
        $_SESSION['message'] = [
            'text' => 'تم إنشاء ملف خريطة الموقع (sitemap.xml) بنجاح.',
            'type' => 'success'
        ];
    } else {
        $_SESSION['message'] = [
            'text' => 'حدث خطأ أثناء إنشاء ملف خريطة الموقع.',
            'type' => 'error'
        ];
    }
    header('Location: ' . base_url('admin/index.php?page=seo_settings'));
    exit;
}

// Handle robots.txt generation if requested
if (isset($_GET['generate_robots']) && $_GET['generate_robots'] == 1) {
    $result = generate_robots_txt($seo_settings['robots_txt']);
    if ($result) {
        $_SESSION['message'] = [
            'text' => 'تم إنشاء ملف robots.txt بنجاح.',
            'type' => 'success'
        ];
    } else {
        $_SESSION['message'] = [
            'text' => 'حدث خطأ أثناء إنشاء ملف robots.txt.',
            'type' => 'error'
        ];
    }
    header('Location: ' . base_url('admin/index.php?page=seo_settings'));
    exit;
}

?>

<div class="container mx-auto px-4 py-2">
    <div class="flex justify-between items-center mb-6 border-b pb-2">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
        <div class="flex space-x-2 space-x-reverse">
            <a href="<?php echo base_url('admin/index.php?page=seo_settings&generate_sitemap=1'); ?>" class="btn btn-secondary">
                <i data-feather="map" class="w-4 h-4 ml-1"></i> إنشاء خريطة الموقع
            </a>
            <a href="<?php echo base_url('admin/index.php?page=seo_settings&generate_robots=1'); ?>" class="btn btn-secondary">
                <i data-feather="file-text" class="w-4 h-4 ml-1"></i> إنشاء ملف Robots.txt
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- SEO Settings Tabs -->
    <div class="tabs" id="seo-tabs">
        <div class="tab active" data-tab="general">العامة</div>
        <div class="tab" data-tab="meta-tags">العلامات الوصفية</div>
        <div class="tab" data-tab="social-media">وسائل التواصل الاجتماعي</div>
        <div class="tab" data-tab="sitemap">خريطة الموقع</div>
        <div class="tab" data-tab="schema">بيانات هيكلية (Schema)</div>
        <div class="tab" data-tab="analytics">التحليلات</div>
    </div>

    <form id="seoSettingsForm" action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" enctype="multipart/form-data" class="space-y-8" onsubmit="return ajaxSubmitForm(this, seoSettingsFormCallback);">
        <?php echo csrf_input_field(); ?>
        <input type="hidden" name="action" value="save_seo_settings">

        <!-- General SEO Settings Tab -->
        <div class="tab-content active" id="tab-general">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="search" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">الإعدادات العامة لتحسين محركات البحث</h2>
                </div>
                
                <div class="mt-6">
                    <div class="seo-preview">
                        <div class="seo-preview-title" id="preview-title"><?php echo htmlspecialchars(str_replace(['{page_title}', '{site_name}'], ['الصفحة الرئيسية', $site_name], $seo_settings['meta_title_template'])); ?></div>
                        <div class="seo-preview-url" id="preview-url"><?php echo htmlspecialchars(get_site_url()); ?></div>
                        <div class="seo-preview-description" id="preview-description"><?php echo htmlspecialchars($seo_settings['meta_description'] ?: $site_description); ?></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label for="meta_title_template" class="form-label">قالب عنوان الصفحة (Meta Title)</label>
                            <input type="text" name="meta_title_template" id="meta_title_template" value="<?php echo htmlspecialchars($seo_settings['meta_title_template']); ?>" class="form-input" oninput="updateSeoPreview()">
                            <div class="seo-character-count" id="title-count">0/60 حرف</div>
                            <p class="text-xs text-gray-500 mt-1">استخدم {page_title} لعنوان الصفحة و {site_name} لاسم الموقع.</p>
                        </div>
                        
                        <div>
                            <label for="canonical_url" class="form-label">الرابط القانوني (Canonical URL)</label>
                            <input type="url" name="canonical_url" id="canonical_url" value="<?php echo htmlspecialchars($seo_settings['canonical_url']); ?>" class="form-input ltr text-left" placeholder="https://www.example.com">
                            <p class="text-xs text-gray-500 mt-1">اتركه فارغًا لاستخدام الرابط الحالي تلقائيًا.</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="meta_description" class="form-label">الوصف العام للموقع (Meta Description)</label>
                            <textarea name="meta_description" id="meta_description" rows="3" class="form-input" oninput="updateSeoPreview()"><?php echo htmlspecialchars($seo_settings['meta_description']); ?></textarea>
                            <div class="seo-character-count" id="description-count">0/160 حرف</div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="meta_keywords" class="form-label">الكلمات المفتاحية (Meta Keywords)</label>
                            <input type="text" name="meta_keywords" id="meta_keywords" value="<?php echo htmlspecialchars($seo_settings['meta_keywords']); ?>" class="form-input" placeholder="كلمة1, كلمة2, كلمة3">
                            <p class="text-xs text-gray-500 mt-1">افصل بين الكلمات المفتاحية بفواصل. ملاحظة: تأثيرها محدود في محركات البحث الحديثة.</p>
                        </div>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح لتحسين محركات البحث:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>استخدم عنوانًا وصفيًا يحتوي على الكلمات المفتاحية الرئيسية (60-70 حرف).</li>
                            <li>اكتب وصفًا جذابًا يلخص محتوى الموقع ويشجع على النقر (150-160 حرف).</li>
                            <li>تأكد من أن جميع الصفحات تحتوي على عناوين فرعية (H1, H2, H3) منظمة بشكل هرمي.</li>
                            <li>استخدم روابط داخلية لربط المحتوى ذي الصلة داخل موقعك.</li>
                            <li>تأكد من أن موقعك سريع ومتوافق مع الأجهزة المحمولة.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Tags Tab -->
        <div class="tab-content" id="tab-meta-tags">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="code" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">العلامات الوصفية الإضافية</h2>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">العلامات الوصفية الإضافية تساعد محركات البحث على فهم محتوى موقعك بشكل أفضل.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="google_verification" class="form-label">رمز التحقق من Google</label>
                            <input type="text" name="google_verification" id="google_verification" value="<?php echo htmlspecialchars($seo_settings['google_verification']); ?>" class="form-input ltr text-left" placeholder="google-site-verification=xxxxxxxxxxxxxxxx">
                            <p class="text-xs text-gray-500 mt-1">أدخل رمز التحقق من Google Search Console.</p>
                        </div>
                        
                        <div>
                            <label for="bing_verification" class="form-label">رمز التحقق من Bing</label>
                            <input type="text" name="bing_verification" id="bing_verification" value="<?php echo htmlspecialchars($seo_settings['bing_verification']); ?>" class="form-input ltr text-left" placeholder="xxxxxxxxxxxxxxxx">
                            <p class="text-xs text-gray-500 mt-1">أدخل رمز التحقق من Bing Webmaster Tools.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="robots_txt" class="form-label">محتوى ملف Robots.txt</label>
                        <textarea name="robots_txt" id="robots_txt" rows="8" class="form-input font-mono ltr text-left"><?php echo htmlspecialchars($seo_settings['robots_txt']); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">استخدم {site_url} ليتم استبدالها تلقائيًا برابط موقعك.</p>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح للعلامات الوصفية:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>تأكد من تسجيل موقعك في Google Search Console وBing Webmaster Tools.</li>
                            <li>استخدم ملف robots.txt للتحكم في الصفحات التي يمكن لمحركات البحث الوصول إليها.</li>
                            <li>تجنب منع محركات البحث من الوصول إلى الصفحات المهمة.</li>
                            <li>تأكد من أن ملف robots.txt يشير إلى موقع خريطة الموقع (sitemap.xml).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Tab -->
        <div class="tab-content" id="tab-social-media">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="share-2" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">إعدادات وسائل التواصل الاجتماعي</h2>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">تساعد هذه الإعدادات في تحسين ظهور موقعك عند مشاركته على وسائل التواصل الاجتماعي.</p>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="og_image" class="form-label">صورة المشاركة الافتراضية (Open Graph Image)</label>
                            <input type="file" name="og_image" id="og_image" accept="image/png, image/jpeg, image/jpg" class="form-input-file">
                            <?php if (!empty($seo_settings['og_image'])): ?>
                                <div class="file-preview mt-2">
                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($seo_settings['og_image']); ?>" alt="صورة المشاركة الحالية" class="file-preview-image">
                                    <div class="file-preview-info">
                                        <div class="file-preview-name">الصورة الحالية</div>
                                        <label class="inline-flex items-center mt-1 text-xs">
                                            <input type="checkbox" name="remove_og_image" value="1" class="form-checkbox h-4 w-4 text-red-600">
                                            <span class="mr-2 text-red-600">إزالة الصورة الحالية</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">الحجم المثالي: 1200×630 بكسل. سيتم استخدام هذه الصورة عند مشاركة موقعك على Facebook وغيرها.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="twitter_card_type" class="form-label">نوع بطاقة تويتر (Twitter Card)</label>
                                <select name="twitter_card_type" id="twitter_card_type" class="form-select">
                                    <?php foreach ($twitter_card_types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $seo_settings['twitter_card_type'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="twitter_site" class="form-label">اسم المستخدم على تويتر (@username)</label>
                                <input type="text" name="twitter_site" id="twitter_site" value="<?php echo htmlspecialchars($seo_settings['twitter_site']); ?>" class="form-input ltr text-left" placeholder="@username">
                            </div>
                        </div>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح لوسائل التواصل الاجتماعي:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>استخدم صورة جذابة وواضحة لتحسين معدل النقر عند مشاركة موقعك.</li>
                            <li>تأكد من أن العنوان والوصف يظهران بشكل صحيح في معاينات المشاركة.</li>
                            <li>اختبر كيفية ظهور موقعك على مختلف منصات التواصل الاجتماعي باستخدام أدوات المعاينة.</li>
                            <li>حدّث صورة المشاركة بانتظام لتعكس أحدث محتوى أو عروض موقعك.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sitemap Tab -->
        <div class="tab-content" id="tab-sitemap">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="map" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">إعدادات خريطة الموقع (Sitemap)</h2>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">خريطة الموقع تساعد محركات البحث على اكتشاف وفهرسة صفحات موقعك بشكل أفضل.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                                <div>
                                    <h3 class="font-semibold text-gray-800">حالة خريطة الموقع</h3>
                                    <p class="text-sm text-gray-600">
                                        <?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml')): ?>
                                            تم إنشاء خريطة الموقع. 
                                            <?php if (!empty($sitemap_settings['last_generated'])): ?>
                                                آخر تحديث: <?php echo date('Y-m-d H:i', strtotime($sitemap_settings['last_generated'])); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            لم يتم إنشاء خريطة الموقع بعد.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <a href="<?php echo base_url('admin/index.php?page=seo_settings&generate_sitemap=1'); ?>" class="btn btn-primary">
                                    <i data-feather="refresh-cw" class="w-4 h-4 ml-1"></i> إنشاء/تحديث الخريطة
                                </a>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <h3 class="form-label">محتوى خريطة الموقع</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="sitemap_settings[include_services]" id="include_services" value="1" <?php echo isset($sitemap_settings['include_services']) && $sitemap_settings['include_services'] ? 'checked' : ''; ?> class="form-checkbox">
                                    <label for="include_services" class="mr-2 text-sm">تضمين صفحات الخدمات</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="sitemap_settings[include_projects]" id="include_projects" value="1" <?php echo isset($sitemap_settings['include_projects']) && $sitemap_settings['include_projects'] ? 'checked' : ''; ?> class="form-checkbox">
                                    <label for="include_projects" class="mr-2 text-sm">تضمين صفحات المشاريع</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="sitemap_settings[include_sections]" id="include_sections" value="1" <?php echo isset($sitemap_settings['include_sections']) && $sitemap_settings['include_sections'] ? 'checked' : ''; ?> class="form-checkbox">
                                    <label for="include_sections" class="mr-2 text-sm">تضمين أقسام الصفحة الرئيسية</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="sitemap_settings[auto_generate]" id="auto_generate" value="1" <?php echo isset($sitemap_settings['auto_generate']) && $sitemap_settings['auto_generate'] ? 'checked' : ''; ?> class="form-checkbox">
                                <label for="auto_generate" class="mr-2 text-sm">تحديث خريطة الموقع تلقائيًا عند إضافة أو تعديل المحتوى</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح لخريطة الموقع:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>تأكد من تحديث خريطة الموقع بانتظام، خاصة بعد إضافة محتوى جديد.</li>
                            <li>أرسل خريطة الموقع إلى Google Search Console وBing Webmaster Tools.</li>
                            <li>تأكد من أن ملف robots.txt يشير إلى موقع خريطة الموقع.</li>
                            <li>استبعد الصفحات غير المهمة أو المكررة من خريطة الموقع.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schema.org Tab -->
        <div class="tab-content" id="tab-schema">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="layers" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">البيانات الهيكلية (Schema.org)</h2>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">البيانات الهيكلية تساعد محركات البحث على فهم محتوى موقعك وعرضه بشكل أفضل في نتائج البحث.</p>
                    
                    <div class="flex items-center mb-6">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_schema" value="1" <?php echo $seo_settings['enable_schema'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="mr-2 text-sm font-medium">تفعيل البيانات الهيكلية (Schema.org)</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="schema_type" class="form-label">نوع البيانات الهيكلية</label>
                            <select name="schema_type" id="schema_type" class="form-select">
                                <?php foreach ($schema_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $seo_settings['schema_type'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="schema-local-business" class="schema-type-fields mt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">معلومات النشاط التجاري</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="business_name" class="form-label">اسم النشاط التجاري</label>
                                <input type="text" name="schema_settings[business_name]" id="business_name" value="<?php echo htmlspecialchars($schema_settings['business_name'] ?? ''); ?>" class="form-input">
                            </div>
                            
                            <div>
                                <label for="business_logo" class="form-label">رابط شعار النشاط التجاري</label>
                                <input type="text" name="schema_settings[business_logo]" id="business_logo" value="<?php echo htmlspecialchars($schema_settings['business_logo'] ?? ''); ?>" class="form-input ltr text-left" placeholder="https://example.com/logo.png">
                                <p class="text-xs text-gray-500 mt-1">سيتم استخدام شعار الموقع إذا تركت هذا الحقل فارغًا.</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="business_description" class="form-label">وصف النشاط التجاري</label>
                                <textarea name="schema_settings[business_description]" id="business_description" rows="3" class="form-input"><?php echo htmlspecialchars($schema_settings['business_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="business_phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" name="schema_settings[business_phone]" id="business_phone" value="<?php echo htmlspecialchars($schema_settings['business_phone'] ?? ''); ?>" class="form-input ltr text-left">
                            </div>
                            
                            <div>
                                <label for="business_email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="schema_settings[business_email]" id="business_email" value="<?php echo htmlspecialchars($schema_settings['business_email'] ?? ''); ?>" class="form-input ltr text-left">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="business_address" class="form-label">العنوان</label>
                                <input type="text" name="schema_settings[business_address]" id="business_address" value="<?php echo htmlspecialchars($schema_settings['business_address'] ?? ''); ?>" class="form-input">
                            </div>
                            
                            <div>
                                <label for="geo_latitude" class="form-label">خط العرض (Latitude)</label>
                                <input type="text" name="schema_settings[geo_latitude]" id="geo_latitude" value="<?php echo htmlspecialchars($schema_settings['geo_latitude'] ?? ''); ?>" class="form-input ltr text-left" placeholder="21.3891">
                            </div>
                            
                            <div>
                                <label for="geo_longitude" class="form-label">خط الطول (Longitude)</label>
                                <input type="text" name="schema_settings[geo_longitude]" id="geo_longitude" value="<?php echo htmlspecialchars($schema_settings['geo_longitude'] ?? ''); ?>" class="form-input ltr text-left" placeholder="39.8579">
                            </div>
                            
                            <div>
                                <label for="opening_hours" class="form-label">ساعات العمل</label>
                                <input type="text" name="schema_settings[opening_hours]" id="opening_hours" value="<?php echo htmlspecialchars($schema_settings['opening_hours'] ?? ''); ?>" class="form-input" placeholder="الأحد-الخميس 9:00-17:00">
                            </div>
                            
                            <div>
                                <label for="price_range" class="form-label">نطاق الأسعار</label>
                                <select name="schema_settings[price_range]" id="price_range" class="form-select">
                                    <option value="₪" <?php echo ($schema_settings['price_range'] ?? '') === '₪' ? 'selected' : ''; ?>>₪ (اقتصادي)</option>
                                    <option value="₪₪" <?php echo ($schema_settings['price_range'] ?? '') === '₪₪' ? 'selected' : ''; ?>>₪₪ (متوسط)</option>
                                    <option value="₪₪₪" <?php echo ($schema_settings['price_range'] ?? '') === '₪₪₪' ? 'selected' : ''; ?>>₪₪₪ (مرتفع)</option>
                                    <option value="₪₪₪₪" <?php echo ($schema_settings['price_range'] ?? '') === '₪₪₪₪' ? 'selected' : ''; ?>>₪₪₪₪ (فاخر)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح للبيانات الهيكلية:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>استخدم البيانات الهيكلية المناسبة لنوع موقعك ونشاطك التجاري.</li>
                            <li>أضف معلومات دقيقة وكاملة لتحسين ظهور موقعك في نتائج البحث المميزة.</li>
                            <li>اختبر البيانات الهيكلية باستخدام أداة اختبار البيانات المنظمة من Google.</li>
                            <li>حدّث البيانات الهيكلية عند تغيير معلومات نشاطك التجاري.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div class="tab-content" id="tab-analytics">
            <div class="seo-section">
                <div class="seo-section-header">
                    <div class="seo-section-icon">
                        <i data-feather="bar-chart-2" class="w-5 h-5"></i>
                    </div>
                    <h2 class="seo-section-title">إعدادات التحليلات (Analytics)</h2>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-600 mb-4">إعدادات التحليلات تساعدك على تتبع زوار موقعك وفهم سلوكهم.</p>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="google_analytics_id" class="form-label">معرف Google Analytics</label>
                            <input type="text" name="google_analytics_id" id="google_analytics_id" value="<?php echo htmlspecialchars($seo_settings['google_analytics_id']); ?>" class="form-input ltr text-left" placeholder="G-XXXXXXXXXX أو UA-XXXXXXXX-X">
                            <p class="text-xs text-gray-500 mt-1">أدخل معرف Google Analytics الخاص بك (يبدأ بـ G- أو UA-).</p>
                        </div>
                    </div>
                    
                    <div class="seo-tips mt-6">
                        <h3 class="seo-tips-title">نصائح للتحليلات:</h3>
                        <ul class="seo-tips-list list-disc">
                            <li>استخدم Google Analytics لتتبع حركة المرور على موقعك وفهم سلوك المستخدمين.</li>
                            <li>قم بإعداد أهداف في Google Analytics لتتبع التحويلات المهمة.</li>
                            <li>استخدم Google Search Console لمراقبة أداء موقعك في نتائج البحث.</li>
                            <li>راقب معدل الارتداد وزمن البقاء على الصفحة لتحسين تجربة المستخدم.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-5">
            <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                حفظ إعدادات SEO
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                tab.classList.add('active');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
        
        // Character count for SEO fields
        updateSeoPreview();
        
        // Schema type fields toggle
        const schemaType = document.getElementById('schema_type');
        if (schemaType) {
            schemaType.addEventListener('change', toggleSchemaFields);
            toggleSchemaFields(); // Initial toggle
        }
    });
    
    function updateSeoPreview() {
        const titleTemplate = document.getElementById('meta_title_template').value;
        const description = document.getElementById('meta_description').value;
        const siteName = '<?php echo addslashes($site_name); ?>';
        
        // Update preview
        const previewTitle = document.getElementById('preview-title');
        const previewDescription = document.getElementById('preview-description');
        
        if (previewTitle) {
            const title = titleTemplate.replace('{page_title}', 'الصفحة الرئيسية').replace('{site_name}', siteName);
            previewTitle.textContent = title;
            document.getElementById('title-count').textContent = title.length + '/60 حرف';
            
            if (title.length > 60) {
                document.getElementById('title-count').classList.add('text-red-500');
            } else {
                document.getElementById('title-count').classList.remove('text-red-500');
            }
        }
        
        if (previewDescription) {
            previewDescription.textContent = description || '<?php echo addslashes($site_description); ?>';
            document.getElementById('description-count').textContent = description.length + '/160 حرف';
            
            if (description.length > 160) {
                document.getElementById('description-count').classList.add('text-red-500');
            } else {
                document.getElementById('description-count').classList.remove('text-red-500');
            }
        }
    }
    
    function toggleSchemaFields() {
        const schemaType = document.getElementById('schema_type').value;
        const localBusinessFields = document.getElementById('schema-local-business');
        
        // Hide all schema type fields first
        document.querySelectorAll('.schema-type-fields').forEach(el => {
            el.style.display = 'none';
        });
        
        // Show fields based on schema type
        if (schemaType === 'LocalBusiness' || schemaType === 'Organization') {
            localBusinessFields.style.display = 'block';
        }
        
        // Add more conditions for other schema types if needed
    }
    
    function seoSettingsFormCallback(response) {
        if (response.success) {
            adminPanel.showAlert(response.message || 'تم حفظ إعدادات SEO بنجاح!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            adminPanel.showAlert(response.message || 'فشل حفظ إعدادات SEO.', 'error');
        }
    }
</script>
