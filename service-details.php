<?php
/**
 * صفحة تفاصيل الخدمة
 * 
 * هذه الصفحة تعرض تفاصيل خدمة محددة مع معرض الصور والمميزات
 */

// تضمين الملفات اللازمة
require_once 'includes/init.php'; // init.php should handle including functions.php
require_once INCLUDES_PATH . '/functions/service_functions.php';
// project_functions.php and future_recommendations.php might not be needed anymore
// require_once INCLUDES_PATH . '/functions/project_functions.php';
// require_once INCLUDES_PATH . '/functions/future_recommendations.php';

// الحصول على معرف الخدمة من الرابط
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Expect 'id'

// التحقق من وجود معرف الخدمة
if ($service_id <= 0) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم تحديد معرف الخدمة صالح
    redirect(base_url()); // Use redirect function from functions.php and base_url
    exit;
}

// الحصول على بيانات الخدمة
$service = get_service_by_id($service_id); // Use new function

// التحقق من وجود الخدمة
if (!$service) {
    // عرض صفحة 404 إذا لم يتم العثور على الخدمة
    http_response_code(404);
    // You might want to include a 404.php page here or a simple message
    // For now, just redirecting to home as a fallback for simplicity
    redirect(base_url('404.php')); // Or a more specific error page
    exit;
}

// $service_images removed as it's no longer a separate table for services
// $related_services removed as it depended on categories

// تعيين عنوان الصفحة ووصفها using new SEO function
$page_name_for_seo = 'service_' . $service['id'];
$seo_settings = [];
if (function_exists('get_seo_for_page')) {
    $seo_settings = get_seo_for_page($page_name_for_seo);
}

$page_title = $seo_settings['meta_title'] ?? htmlspecialchars($service['name']);
$page_description = $seo_settings['meta_description'] ?? truncate_text(strip_tags(htmlspecialchars_decode($service['description'] ?? '')), 155);
$page_keywords = $seo_settings['meta_keywords'] ?? '';


// تتبع زيارة الصفحة - This function might need an update if it logs to DB table that changed
// For now, assuming it's a generic tracker or will be updated/removed later.
// track_page_visit('خدمة: ' . $service['name'], 'service-details.php?id=' . $service_id);

// تضمين رأس الصفحة
include INCLUDES_PATH . '/header.php';
?>

<!-- قسم تفاصيل الخدمة -->
<section class="py-16 bg-light-bg">
    <div class="container mx-auto px-4">
        <!-- رابط العودة -->
        <div class="mb-8">
            <a href="index.php#services" class="inline-flex items-center text-primary hover:text-primary-dark transition-colors">
                <i data-feather="arrow-right" class="w-4 h-4 ml-1"></i>
                العودة إلى الخدمات
            </a>
        </div>

        <!-- عنوان الخدمة -->
        <h1 class="text-3xl md:text-4xl font-bold text-dark-gray mb-6">
            <?php if (!empty($service['icon_class'])): ?>
                <i class="<?php echo htmlspecialchars($service['icon_class']); ?> text-primary mr-2"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($service['name']); ?>
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- تفاصيل الخدمة -->
            <div class="lg:col-span-2">
                {/* Main image and gallery removed */}

                <!-- الوصف التفصيلي -->
                <div class="prose max-w-none mb-8">
                    <?php echo nl2br(htmlspecialchars_decode($service['description'])); ?> {/* Use description, nl2br if it's plain text, or just echo if HTML */}
                </div>

                {/* زر طلب الخدمة */}
                <div class="mt-8">
                    <a href="#contact" class="btn-primary inline-block">
                        <i data-feather="phone" class="w-4 h-4 ml-2"></i>
                        طلب هذه الخدمة
                    </a>
                </div>
            </div>

            <!-- الشريط الجانبي -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-subtle p-6">
                    {/* Features section removed */}
                    {/* Related services section removed */}

                    <h3 class="text-xl font-bold text-dark-gray mb-4">خدمات أخرى</h3>
                    <p class="text-gray-600">قد ترغب في تصفح <a href="<?php echo base_url('services.php'); ?>" class="text-primary hover:underline">جميع خدماتنا</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- تضمين نموذج الاتصال -->
<?php include 'includes/contact_form.php'; ?>

<!-- تضمين ذيل الصفحة -->
<?php include 'includes/footer.php'; ?>

<!-- تهيئة التحميل الكسول للصور -->
<?php initialize_lazy_loading(); ?>

<!-- تضمين مكتبة Fancybox -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
    // تهيئة معرض الصور
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind('[data-fancybox="service-gallery"]', {
                loop: true,
                buttons: ['zoom', 'slideShow', 'fullScreen', 'thumbs', 'close'],
                animationEffect: 'fade',
                transitionEffect: 'fade',
                thumbs: {
                    autoStart: true
                }
            });
        }
    });
</script>
