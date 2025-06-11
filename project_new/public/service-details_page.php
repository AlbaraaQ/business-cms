<?php
/**
 * صفحة تفاصيل الخدمة
 * 
 * هذه الصفحة تعرض تفاصيل خدمة محددة مع معرض الصور والمميزات
 */

// تضمين الملفات اللازمة
require_once __DIR__ . '/../app/core_init.php'; // Loads GlobalHelper, ServiceService, ProjectService etc.

// الحصول على معرف الخدمة من الرابط
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// التحقق من وجود معرف الخدمة
if (empty($slug)) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم تحديد معرف الخدمة
    header('Location: index.php');
    exit;
}

// الحصول على بيانات الخدمة
$service = get_service_by_slug($slug);

// التحقق من وجود الخدمة
if (!$service) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم العثور على الخدمة
    header('Location: index.php');
    exit;
}

// الحصول على صور الخدمة
$service_images = get_service_images($service['service_id']);

// الحصول على الخدمات ذات الصلة
$related_services = get_related_services($service['service_id'], 3);

// تعيين عنوان الصفحة ووصفها
$page_title = !empty($service['meta_title']) ? $service['meta_title'] : $service['title'];
$page_description = !empty($service['meta_description']) ? $service['meta_description'] : $service['short_description'];
$page_keywords = !empty($service['keywords']) ? $service['keywords'] : '';

// تتبع زيارة الصفحة
track_page_visit('خدمة: ' . $service['title'], 'service-details.php?slug=' . $slug);

// تضمين رأس الصفحة
include __DIR__ . '/../app/views/partials/header.php'; // Adjusted path
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
        <h1 class="text-3xl md:text-4xl font-bold text-dark-gray mb-6"><?php echo htmlspecialchars($service['title']); ?></h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- تفاصيل الخدمة -->
            <div class="lg:col-span-2">
                <!-- الصورة الرئيسية -->
                <?php if (!empty($service['image'])): ?>
                <div class="mb-8 rounded-lg overflow-hidden shadow-md">
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="w-full h-auto">
                </div>
                <?php endif; ?>

                <!-- الوصف التفصيلي -->
                <div class="prose max-w-none mb-8">
                    <?php echo $service['full_description']; ?>
                </div>

                <!-- معرض الصور -->
                <?php if (!empty($service_images)): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-dark-gray mb-4">معرض الصور</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($service_images as $image): ?>
                        <a href="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" data-fancybox="service-gallery" class="block rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="w-full h-40 object-cover lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- زر طلب الخدمة -->
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
                    <!-- مميزات الخدمة -->
                    <div class="mb-8">
                        <h3 class="text-xl font-bold text-dark-gray mb-4">مميزات الخدمة</h3>
                        <ul class="space-y-3">
                            <?php 
                            // عرض مميزات الخدمة إذا كانت موجودة
                            $features = !empty($service['features']) ? explode("\n", $service['features']) : [
                                'جودة عالية في التنفيذ',
                                'فريق عمل محترف',
                                'ضمان على جميع الأعمال',
                                'أسعار تنافسية',
                                'سرعة في التنفيذ'
                            ];
                            
                            foreach ($features as $feature):
                                if (!empty(trim($feature))):
                            ?>
                            <li class="flex items-start">
                                <i data-feather="check-circle" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <span><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>

                    <!-- الخدمات ذات الصلة -->
                    <?php if (!empty($related_services)): ?>
                    <div>
                        <h3 class="text-xl font-bold text-dark-gray mb-4">خدمات ذات صلة</h3>
                        <ul class="space-y-4">
                            <?php foreach ($related_services as $related): ?>
                            <li>
                                <a href="service-details.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="flex items-center group">
                                    <?php if (!empty($related['image'])): ?>
                                    <div class="w-16 h-16 rounded-md overflow-hidden ml-3 shadow-sm">
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-full object-cover lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($related['image']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="font-semibold text-dark-gray group-hover:text-primary transition-colors"><?php echo htmlspecialchars($related['title']); ?></h4>
                                        <p class="text-sm text-gray-500"><?php echo truncate_text($related['short_description'], 60); ?></p>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- تضمين نموذج الاتصال -->
<?php include __DIR__ . '/../app/views/partials/contact_form.php'; // Adjusted path, assuming contact_form.php will be moved
?>

<!-- تضمين ذيل الصفحة -->
<?php include __DIR__ . '/../app/views/partials/footer.php'; // Adjusted path
?>

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
