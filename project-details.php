<?php
/**
 * صفحة تفاصيل المشروع
 * 
 * هذه الصفحة تعرض تفاصيل مشروع محدد مع معرض الصور والمعلومات
 */

// تضمين الملفات اللازمة
require_once 'includes/init.php'; // init.php should handle including functions.php
require_once INCLUDES_PATH . '/functions/project_functions.php';
// service_functions.php and future_recommendations.php likely not needed
// require_once INCLUDES_PATH . '/functions/service_functions.php';
// require_once INCLUDES_PATH . '/functions/future_recommendations.php';


// الحصول على معرف المشروع من الرابط
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Expect 'id'

// التحقق من وجود معرف المشروع
if ($project_id <= 0) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم تحديد معرف المشروع صالح
    redirect(base_url());
    exit;
}

// الحصول على بيانات المشروع
$project = get_project_by_id($project_id); // Use new function

// التحقق من وجود المشروع
if (!$project) {
    // عرض صفحة 404 إذا لم يتم العثور على المشروع
    http_response_code(404);
    redirect(base_url('404.php')); // Or a more specific error page
    exit;
}

// $project_images removed (no separate gallery table)
// $related_projects removed (relied on categories)

// تعيين عنوان الصفحة ووصفها using new SEO function
$page_name_for_seo = 'project_' . $project['id'];
$seo_settings = [];
if (function_exists('get_seo_for_page')) {
    $seo_settings = get_seo_for_page($page_name_for_seo);
}

$page_title = $seo_settings['meta_title'] ?? htmlspecialchars($project['title']);
$page_description = $seo_settings['meta_description'] ?? truncate_text(strip_tags(htmlspecialchars_decode($project['description'] ?? '')), 155);
$page_keywords = $seo_settings['meta_keywords'] ?? '';

// تتبع زيارة الصفحة - Commented out for now
// track_page_visit('مشروع: ' . $project['title'], 'project-details.php?id=' . $project_id);

// تضمين رأس الصفحة
include INCLUDES_PATH . '/header.php';
?>

<!-- قسم تفاصيل المشروع -->
<section class="py-16 bg-light-bg">
    <div class="container mx-auto px-4">
        <!-- رابط العودة -->
        <div class="mb-8">
            <a href="index.php#projects" class="inline-flex items-center text-primary hover:text-primary-dark transition-colors">
                <i data-feather="arrow-right" class="w-4 h-4 ml-1"></i>
                العودة إلى المشاريع
            </a>
        </div>

        <!-- عنوان المشروع -->
        <h1 class="text-3xl md:text-4xl font-bold text-dark-gray mb-6"><?php echo htmlspecialchars($project['title']); ?></h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- تفاصيل المشروع -->
            <div class="lg:col-span-2">
                <!-- الصورة الرئيسية (image_url) -->
                <?php if (!empty($project['image_url'])): ?>
                <div class="mb-8 rounded-lg overflow-hidden shadow-md">
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-auto">
                </div>
                <?php endif; ?>

                <!-- الوصف التفصيلي -->
                <div class="prose max-w-none mb-8">
                    <?php echo nl2br(htmlspecialchars_decode($project['description'])); ?> {/* Use description */}
                </div>

                {/* Image gallery removed */}

                <!-- زر استفسار عن مشروع مماثل -->
                <div class="mt-8">
                    <a href="#contact" class="btn-primary inline-block">
                        <i data-feather="message-circle" class="w-4 h-4 ml-2"></i>
                        استفسر عن مشروع مماثل
                    </a>
                </div>
            </div>

            <!-- الشريط الجانبي -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-subtle p-6">
                    <!-- معلومات المشروع -->
                    <div class="mb-8">
                        <h3 class="text-xl font-bold text-dark-gray mb-4">معلومات المشروع</h3>
                        <ul class="space-y-3">
                            {/* Category, Client, Location, Completion Date removed */}
                            <?php if (!empty($project['project_url'])): ?>
                            <li class="flex items-start">
                                <i data-feather="link" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">رابط المشروع:</span>
                                    <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline break-all"><?php echo htmlspecialchars($project['project_url']); ?></a>
                                </div>
                            </li>
                            <?php endif; ?>
                             <li class="flex items-start">
                                <i data-feather="calendar" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">تاريخ الإضافة:</span>
                                    <span><?php echo format_date($project['created_at']); ?></span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {/* Related Projects section removed */}
                     <div>
                        <h3 class="text-xl font-bold text-dark-gray mb-4">مشاريع أخرى</h3>
                        <p class="text-gray-600">يمكنك تصفح <a href="<?php echo base_url('projects.php'); ?>" class="text-primary hover:underline">جميع مشاريعنا</a>.</p>
                    </div>
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
            Fancybox.bind('[data-fancybox="project-gallery"]', {
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
