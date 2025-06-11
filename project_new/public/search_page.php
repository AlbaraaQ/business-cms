<?php
/**
 * صفحة البحث في الموقع
 * 
 * هذه الصفحة تتيح للمستخدم البحث في الخدمات والمشاريع
 */

// تضمين الملفات اللازمة
require_once __DIR__ . '/../app/core_init.php'; // Loads GlobalHelper, ServiceService, ProjectService, FutureRecommendationsHelper etc.

// الحصول على كلمة البحث
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// التحقق من وجود كلمة بحث
$has_search = !empty($query);

// البحث في الموقع
$search_results = $has_search ? search_website($query) : [];

// تعيين عنوان الصفحة
$page_title = $has_search ? "نتائج البحث عن: " . htmlspecialchars($query) : "البحث في الموقع";
$page_description = "ابحث في خدمات ومشاريع " . SITE_NAME;

// تضمين رأس الصفحة
include __DIR__ . '/../app/views/partials/header.php'; // Adjusted path
?>

<!-- قسم البحث -->
<section class="py-16 bg-light-bg">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold text-dark-gray mb-8"><?php echo $page_title; ?></h1>
        
        <!-- نموذج البحث -->
        <div class="mb-10 max-w-2xl mx-auto">
            <form action="search.php" method="get" class="flex">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="ابحث عن خدمات أو مشاريع..." class="flex-grow px-4 py-3 rounded-r-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-l-lg transition-colors">
                    <i data-feather="search" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
        
        <?php if ($has_search): ?>
            <?php if (empty($search_results['services']) && empty($search_results['projects'])): ?>
                <!-- لا توجد نتائج -->
                <div class="text-center py-10">
                    <div class="mb-4">
                        <i data-feather="search" class="w-16 h-16 mx-auto text-gray-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-dark-gray mb-2">لم يتم العثور على نتائج</h2>
                    <p class="text-gray-600 mb-6">لم نتمكن من العثور على نتائج مطابقة لـ "<?php echo htmlspecialchars($query); ?>"</p>
                    <div>
                        <a href="index.php" class="btn-primary">العودة للصفحة الرئيسية</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- عرض نتائج البحث -->
                <div class="grid grid-cols-1 gap-8">
                    <!-- نتائج الخدمات -->
                    <?php if (!empty($search_results['services'])): ?>
                        <div>
                            <h2 class="text-2xl font-bold text-dark-gray mb-4">الخدمات (<?php echo count($search_results['services']); ?>)</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($search_results['services'] as $service): ?>
                                    <div class="bg-white rounded-lg shadow-subtle p-6 h-full flex flex-col transition-transform hover:shadow-interactive hover:-translate-y-1">
                                        <?php if (!empty($service['image'])): ?>
                                            <div class="service-image mb-4">
                                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="w-full h-48 object-cover rounded-lg shadow-md lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($service['image']); ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex items-center mb-3">
                                            <?php if (!empty($service['icon'])): ?>
                                                <i data-feather="<?php echo htmlspecialchars($service['icon']); ?>" class="text-primary w-5 h-5 ml-2"></i>
                                            <?php endif; ?>
                                            <h3 class="text-xl font-bold text-dark-gray"><?php echo htmlspecialchars($service['title']); ?></h3>
                                        </div>
                                        <p class="text-gray-600 mb-4 flex-grow"><?php echo truncate_text($service['short_description'] ?? '', 120); ?></p>
                                        <a href="service-details.php?slug=<?php echo htmlspecialchars($service['slug']); ?>" class="text-primary font-semibold hover:text-primary-dark flex items-center transition-colors">
                                            عرض المزيد
                                            <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- نتائج المشاريع -->
                    <?php if (!empty($search_results['projects'])): ?>
                        <div class="mt-10">
                            <h2 class="text-2xl font-bold text-dark-gray mb-4">المشاريع (<?php echo count($search_results['projects']); ?>)</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($search_results['projects'] as $project): ?>
                                    <div class="bg-white rounded-lg shadow-subtle overflow-hidden transition-transform hover:shadow-interactive hover:-translate-y-1">
                                        <div class="relative project-image-container">
                                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['main_image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-56 object-cover lazy" data-src="<?php echo UPLOAD_URL . htmlspecialchars($project['main_image']); ?>">
                                            <?php if (!empty($project['category'])): ?>
                                                <span class="absolute top-3 right-3 bg-primary/80 text-white text-xs py-1 px-2 rounded-full backdrop-blur-sm"><?php echo htmlspecialchars($project['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-5">
                                            <h3 class="text-xl font-bold text-dark-gray mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                            <p class="text-gray-600 mb-3"><?php echo truncate_text($project['short_description'] ?? '', 100); ?></p>
                                            <div class="flex justify-between items-center">
                                                <a href="project-details.php?slug=<?php echo htmlspecialchars($project['slug']); ?>" class="text-primary font-semibold hover:text-primary-dark flex items-center transition-colors">
                                                    عرض المزيد
                                                    <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i>
                                                </a>
                                                <?php if (!empty($project['completion_date'])): ?>
                                                    <span class="text-xs text-gray-500"><?php echo format_date($project['completion_date']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- صفحة البحث الرئيسية -->
            <div class="text-center py-10">
                <div class="mb-4">
                    <i data-feather="search" class="w-16 h-16 mx-auto text-primary"></i>
                </div>
                <h2 class="text-2xl font-bold text-dark-gray mb-2">ابحث في موقعنا</h2>
                <p class="text-gray-600 mb-6">يمكنك البحث عن الخدمات والمشاريع التي تقدمها شركتنا</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- تضمين ذيل الصفحة -->
<?php include __DIR__ . '/../app/views/partials/footer.php'; // Adjusted path
?>

<!-- تهيئة التحميل الكسول للصور -->
<?php initialize_lazy_loading(); ?>
