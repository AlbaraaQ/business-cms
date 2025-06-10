<?php
/**
 * صفحة تفاصيل المشروع
 * 
 * هذه الصفحة تعرض تفاصيل مشروع محدد مع معرض الصور والمعلومات
 */

// تضمين الملفات اللازمة
require_once 'includes/functions.php';
require_once 'includes/functions/project_functions.php';
require_once 'includes/functions/service_functions.php';
require_once 'includes/functions/future_recommendations.php';

// الحصول على معرف المشروع من الرابط
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// التحقق من وجود معرف المشروع
if (empty($slug)) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم تحديد معرف المشروع
    header('Location: index.php');
    exit;
}

// الحصول على بيانات المشروع
$project = get_project_by_slug($slug);

// التحقق من وجود المشروع
if (!$project) {
    // إعادة التوجيه إلى الصفحة الرئيسية إذا لم يتم العثور على المشروع
    header('Location: index.php');
    exit;
}

// الحصول على صور المشروع
$project_images = get_project_images($project['project_id']);

// الحصول على المشاريع ذات الصلة
$related_projects = get_related_projects($project['project_id'], 3);

// تعيين عنوان الصفحة ووصفها
$page_title = !empty($project['meta_title']) ? $project['meta_title'] : $project['title'];
$page_description = !empty($project['meta_description']) ? $project['meta_description'] : $project['short_description'];
$page_keywords = !empty($project['keywords']) ? $project['keywords'] : '';

// تتبع زيارة الصفحة
track_page_visit('مشروع: ' . $project['title'], 'project-details.php?slug=' . $slug);

// تضمين رأس الصفحة
include 'includes/header.php';
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
                <!-- الصورة الرئيسية -->
                <?php if (!empty($project['main_image'])): ?>
                <div class="mb-8 rounded-lg overflow-hidden shadow-md">
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['main_image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-auto">
                </div>
                <?php endif; ?>

                <!-- الوصف التفصيلي -->
                <div class="prose max-w-none mb-8">
                    <?php echo $project['full_description']; ?>
                </div>

                <!-- معرض الصور -->
                <?php if (!empty($project_images)): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-dark-gray mb-4">معرض الصور</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($project_images as $image): ?>
                        <a href="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" data-fancybox="project-gallery" class="block rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-40 object-cover lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

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
                            <?php if (!empty($project['category'])): ?>
                            <li class="flex items-start">
                                <i data-feather="tag" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">التصنيف:</span>
                                    <span><?php echo htmlspecialchars($project['category']); ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['client_name'])): ?>
                            <li class="flex items-start">
                                <i data-feather="user" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">العميل:</span>
                                    <span><?php echo htmlspecialchars($project['client_name']); ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['location'])): ?>
                            <li class="flex items-start">
                                <i data-feather="map-pin" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">الموقع:</span>
                                    <span><?php echo htmlspecialchars($project['location']); ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['completion_date'])): ?>
                            <li class="flex items-start">
                                <i data-feather="calendar" class="w-5 h-5 text-primary ml-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">تاريخ الإنجاز:</span>
                                    <span><?php echo format_date($project['completion_date']); ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- المشاريع ذات الصلة -->
                    <?php if (!empty($related_projects)): ?>
                    <div>
                        <h3 class="text-xl font-bold text-dark-gray mb-4">مشاريع ذات صلة</h3>
                        <ul class="space-y-4">
                            <?php foreach ($related_projects as $related): ?>
                            <li>
                                <a href="project-details.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="flex items-center group">
                                    <?php if (!empty($related['main_image'])): ?>
                                    <div class="w-16 h-16 rounded-md overflow-hidden ml-3 shadow-sm">
                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($related['main_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-full object-cover lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($related['main_image']); ?>">
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
