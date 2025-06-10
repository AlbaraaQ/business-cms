<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إعدادات الموقع العامة";

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_type = $_SESSION['message']['type'];
    unset($_SESSION['message']);
}

// Fetch current settings
$settings = $db->queryOne("SELECT * FROM settings ORDER BY setting_id ASC LIMIT 1");
if (!$settings) {
    $settings = [
        'site_name' => '', 'site_tagline' => '', 'site_description' => '',
        'meta_keywords' => '', 'contact_phone' => '', 'contact_email' => '', 'contact_address' => '',
        'whatsapp_link' => '', 'instagram_link' => '', 'twitter_link' => '', 'facebook_link' => '',
        'footer_text' => '', 'map_location_name' => '', 'map_lat' => '', 'map_lng' => '', 'map_api_key' => '',
        'site_logo_path' => null, 'site_favicon_path' => null, 'og_image_path' => null,
        'google_analytics_id' => '', 'google_tag_manager_id' => '', 'facebook_pixel_id' => '',
        'enabled_frontend_sections' => json_encode([
            "hero" => true, "about" => true, "services" => true, "projects" => true, 
            "testimonials" => true, "facts" => true, "contact" => true, "map" => true
        ])
    ];
}

// Decode JSON for enabled_frontend_sections
$enabled_frontend_sections = json_decode($settings['enabled_frontend_sections'] ?? '[]', true) ?: [
    "hero" => true, "about" => true, "services" => true, "projects" => true, 
    "testimonials" => true, "facts" => true, "contact" => true, "map" => true
];

// Define available frontend sections
$available_sections = [
    'hero' => 'قسم الهيرو (الرئيسي)',
    'about' => 'قسم "عنا"',
    'services' => 'قسم الخدمات',
    'projects' => 'قسم المشاريع',
    'testimonials' => 'قسم آراء العملاء',
    'facts' => 'قسم الحقائق والأرقام',
    'contact' => 'قسم معلومات الاتصال',
    'map' => 'قسم الخريطة'
];
?>

<div class="container mx-auto px-4 py-2">
    <div class="flex justify-between items-center mb-6 border-b pb-2">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <form id="siteSettingsForm" action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" enctype="multipart/form-data" class="space-y-8 bg-white p-6 rounded-lg shadow-xl" onsubmit="return ajaxSubmitForm(this, siteSettingsFormCallback);">
        <?php echo csrf_input_field(); ?>
        <input type="hidden" name="action" value="save_site_settings">

        <!-- Basic Information Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">معلومات الموقع الأساسية</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">اسم الموقع:</label>
                    <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" class="form-input" required>
                </div>
                <div>
                    <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">شعار الموقع:</label>
                    <input type="text" name="site_tagline" id="site_tagline" value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">وصف الموقع (لـ SEO):</label>
                    <textarea name="site_description" id="site_description" rows="3" class="form-input"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-1">كلمات دلالية (لـ SEO):</label>
                    <input type="text" name="meta_keywords" id="meta_keywords" value="<?php echo htmlspecialchars($settings['meta_keywords'] ?? ''); ?>" class="form-input" placeholder="كلمات مفتاحية مفصولة بفواصل">
                </div>
            </div>
        </fieldset>

        <!-- Contact Information Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">معلومات الاتصال</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف:</label>
                    <input type="tel" name="contact_phone" id="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني:</label>
                    <input type="email" name="contact_email" id="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div class="md:col-span-2">
                    <label for="contact_address" class="block text-sm font-medium text-gray-700 mb-1">العنوان:</label>
                    <input type="text" name="contact_address" id="contact_address" value="<?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?>" class="form-input">
                </div>
            </div>
        </fieldset>

        <!-- Social Media Links Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">روابط التواصل الاجتماعي</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <label for="whatsapp_link" class="block text-sm font-medium text-gray-700 mb-1">رابط واتساب:</label>
                    <input type="url" name="whatsapp_link" id="whatsapp_link" value="<?php echo htmlspecialchars($settings['whatsapp_link'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div>
                    <label for="instagram_link" class="block text-sm font-medium text-gray-700 mb-1">رابط انستغرام:</label>
                    <input type="url" name="instagram_link" id="instagram_link" value="<?php echo htmlspecialchars($settings['instagram_link'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div>
                    <label for="twitter_link" class="block text-sm font-medium text-gray-700 mb-1">رابط تويتر:</label>
                    <input type="url" name="twitter_link" id="twitter_link" value="<?php echo htmlspecialchars($settings['twitter_link'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div>
                    <label for="facebook_link" class="block text-sm font-medium text-gray-700 mb-1">رابط فيسبوك:</label>
                    <input type="url" name="facebook_link" id="facebook_link" value="<?php echo htmlspecialchars($settings['facebook_link'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
            </div>
        </fieldset>
        
        <!-- Visual Identity Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">الهوية البصرية</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-1">شعار الموقع:</label>
                    <input type="file" name="site_logo" id="site_logo" accept="image/*" class="form-input-file" onchange="previewImage(this, 'logo_preview')">
                    <?php if (!empty($settings['site_logo_path'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($settings['site_logo_path']); ?>" alt="الشعار الحالي" class="h-16 w-auto border rounded" id="logo_preview">
                            <label class="inline-flex items-center mt-1 text-xs">
                                <input type="checkbox" name="remove_site_logo" value="1" class="form-checkbox h-4 w-4 text-red-600">
                                <span class="ml-2 text-red-600">إزالة الشعار الحالي</span>
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 hidden" id="logo_preview_container">
                            <img id="logo_preview" class="h-16 w-auto border rounded">
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="site_favicon" class="block text-sm font-medium text-gray-700 mb-1">أيقونة الموقع:</label>
                    <input type="file" name="site_favicon" id="site_favicon" accept=".ico,image/x-icon,image/png" class="form-input-file" onchange="previewImage(this, 'favicon_preview')">
                    <?php if (!empty($settings['site_favicon_path'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($settings['site_favicon_path']); ?>" alt="الأيقونة الحالية" class="h-8 w-8 border rounded" id="favicon_preview">
                            <label class="inline-flex items-center mt-1 text-xs">
                                <input type="checkbox" name="remove_site_favicon" value="1" class="form-checkbox h-4 w-4 text-red-600">
                                <span class="ml-2 text-red-600">إزالة الأيقونة الحالية</span>
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 hidden" id="favicon_preview_container">
                            <img id="favicon_preview" class="h-8 w-8 border rounded">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="md:col-span-2">
                    <label for="og_image" class="block text-sm font-medium text-gray-700 mb-1">صورة المشاركة (Open Graph):</label>
                    <input type="file" name="og_image" id="og_image" accept="image/*" class="form-input-file" onchange="previewImage(this, 'og_preview')">
                    <p class="text-xs text-gray-500 mt-1">الأبعاد الموصى بها: 1200x630 بكسل</p>
                    <?php if (!empty($settings['og_image_path'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($settings['og_image_path']); ?>" alt="صورة المشاركة الحالية" class="h-24 w-auto border rounded" id="og_preview">
                            <label class="inline-flex items-center mt-1 text-xs">
                                <input type="checkbox" name="remove_og_image" value="1" class="form-checkbox h-4 w-4 text-red-600">
                                <span class="ml-2 text-red-600">إزالة الصورة الحالية</span>
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 hidden" id="og_preview_container">
                            <img id="og_preview" class="h-24 w-auto border rounded">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </fieldset>

        <!-- Analytics Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">تحليلات الزوار</legend>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                <div>
                    <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-1">Google Analytics:</label>
                    <input type="text" name="google_analytics_id" id="google_analytics_id" value="<?php echo htmlspecialchars($settings['google_analytics_id'] ?? ''); ?>" class="form-input ltr text-left" placeholder="UA-XXXXXX-X">
                </div>
                <div>
                    <label for="google_tag_manager_id" class="block text-sm font-medium text-gray-700 mb-1">Google Tag Manager:</label>
                    <input type="text" name="google_tag_manager_id" id="google_tag_manager_id" value="<?php echo htmlspecialchars($settings['google_tag_manager_id'] ?? ''); ?>" class="form-input ltr text-left" placeholder="GTM-XXXXXX">
                </div>
                <div>
                    <label for="facebook_pixel_id" class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel:</label>
                    <input type="text" name="facebook_pixel_id" id="facebook_pixel_id" value="<?php echo htmlspecialchars($settings['facebook_pixel_id'] ?? ''); ?>" class="form-input ltr text-left" placeholder="XXXXXXXXXXXXXXX">
                </div>
            </div>
        </fieldset>

        <!-- Map Settings Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">إعدادات الخريطة</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="md:col-span-2">
                    <label for="map_location_name" class="block text-sm font-medium text-gray-700 mb-1">اسم الموقع على الخريطة:</label>
                    <input type="text" name="map_location_name" id="map_location_name" value="<?php echo htmlspecialchars($settings['map_location_name'] ?? ''); ?>" class="form-input">
                </div>
                <div>
                    <label for="map_lat" class="block text-sm font-medium text-gray-700 mb-1">خط العرض:</label>
                    <input type="text" name="map_lat" id="map_lat" value="<?php echo htmlspecialchars($settings['map_lat'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div>
                    <label for="map_lng" class="block text-sm font-medium text-gray-700 mb-1">خط الطول:</label>
                    <input type="text" name="map_lng" id="map_lng" value="<?php echo htmlspecialchars($settings['map_lng'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
                <div class="md:col-span-2">
                    <label for="map_api_key" class="block text-sm font-medium text-gray-700 mb-1">مفتاح Google Maps API:</label>
                    <input type="text" name="map_api_key" id="map_api_key" value="<?php echo htmlspecialchars($settings['map_api_key'] ?? ''); ?>" class="form-input ltr text-left">
                </div>
            </div>
        </fieldset>
        
        <!-- Footer Text Section -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">نصوص التذييل</legend>
            <div class="mt-4">
                <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1">نص حقوق النشر:</label>
                <textarea name="footer_text" id="footer_text" rows="3" class="form-input tinymceeditor_basic"><?php echo htmlspecialchars($settings['footer_text'] ?? ('© ' . date('Y') . ' ' . ($settings['site_name'] ?: 'اسم موقعك'))); ?></textarea>
            </div>
        </fieldset>

        <!-- Frontend Sections Visibility -->
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-semibold text-pink-600 px-2">التحكم في ظهور الأقسام</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                <?php foreach ($available_sections as $key => $label): ?>
                <div class="flex items-center">
                    <input type="checkbox" name="enabled_frontend_sections[<?php echo $key; ?>]" id="section_toggle_<?php echo $key; ?>" value="1" 
                           <?php echo isset($enabled_frontend_sections[$key]) && $enabled_frontend_sections[$key] ? 'checked' : ''; ?>
                           class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <label for="section_toggle_<?php echo $key; ?>" class="ml-2 block text-sm text-gray-900"><?php echo htmlspecialchars($label); ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="pt-5">
            <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                حفظ الإعدادات
            </button>
        </div>
    </form>
</div>

<style>
.form-input { @apply mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm; }
.form-input-file { @apply mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100 cursor-pointer; }
.form-checkbox { @apply rounded border-gray-300 text-pink-600 shadow-sm focus:border-pink-300 focus:ring focus:ring-pink-200 focus:ring-opacity-50; }
</style>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = document.getElementById(previewId + '_container');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            if (container) container.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function siteSettingsFormCallback(response) {
    if (response.success) {
        adminPanel.showAlert(response.message || 'تم حفظ الإعدادات بنجاح!', 'success');
        setTimeout(() => window.location.reload(), 1500);
    } else {
        adminPanel.showAlert(response.message || 'فشل حفظ الإعدادات.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeTinyMCE('.tinymceeditor_basic', {
        height: 150,
        menubar: false,
        plugins: 'lists link code',
        toolbar: 'undo redo | bold italic | bullist numlist | link | code'
    });
});
</script>