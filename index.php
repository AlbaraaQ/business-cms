<?php
/**
 * ملف الصفحة الرئيسية للموقع
 * 
 * هذا الملف يعرض الصفحة الرئيسية للموقع مع الخدمات والمشاريع المميزة
 */

// تضمين ملف التهيئة
require_once 'includes/init.php';

// تضمين ملفات الوظائف اللازمة
require_once 'includes/functions/service_functions.php';
require_once 'includes/functions/project_functions.php';

// تعيين عنوان الصفحة
$page_title = 'الصفحة الرئيسية';

// الحصول على الخدمات المميزة
$featured_services = get_featured_services(6); // Updated function

// الحصول على المشاريع المميزة
$featured_projects = get_featured_projects(6); // Updated function

// الحصول على إعدادات SEO للصفحة الرئيسية
// Ensure admin_seo_functions.php is included or get_seo_settings_by_page_name is accessible
// For now, assuming get_seo_for_page is the correct public wrapper from includes/functions.php
if (function_exists('get_seo_for_page')) {
    $seo_settings = get_seo_for_page('home');
} else {
    // Fallback or error if the function isn't available
    $seo_settings = ['meta_title' => $page_title, 'meta_description' => '', 'meta_keywords' => ''];
    log_error("get_seo_for_page function not found for index.php");
}


// تضمين رأس الصفحة
include 'includes/header.php';
?>

<!-- قسم البانر الرئيسي -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">حلول مبتكرة لتطوير أعمالك</h1>
                <p class="lead mb-4">نقدم خدمات احترافية في مجال تطوير المواقع والتطبيقات وحلول الأعمال المتكاملة</p>
                <div class="d-flex gap-3">
                    <a href="services.php" class="btn btn-light btn-lg">استكشف خدماتنا</a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg">تواصل معنا</a>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0">
                <img src="assets/img/hero-image.svg" alt="صورة توضيحية" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
</section>

<!-- قسم الخدمات -->
<section class="services-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">خدماتنا</h2>
            <p class="section-subtitle">نقدم مجموعة متنوعة من الخدمات لتلبية احتياجات عملائنا</p>
        </div>
        
        <div class="row">
            <?php if (!empty($featured_services)): ?>
                <?php foreach ($featured_services as $service): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card service-card h-100">
                            {/* Image display removed for services on homepage as per new schema (no direct image_url) */}
                            <div class="card-body text-center"> {/* Added text-center for icon */}
                                <h3 class="card-title">
                                    <?php if (!empty($service['icon_class'])): ?>
                                        <i class="<?php echo htmlspecialchars($service['icon_class']); ?> fa-3x mb-3 d-block"></i> {/* Display icon_class */}
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($service['name']); ?> {/* Use name */}
                                </h3>
                                <p class="card-text"><?php echo truncate_text(htmlspecialchars($service['description']), 120); ?></p> {/* Use description */}
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="service-details.php?id=<?php echo htmlspecialchars($service['id']); ?>" class="btn btn-outline-primary">عرض التفاصيل</a> {/* Link to id */}
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        لا توجد خدمات متاحة حالياً
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="services.php" class="btn btn-primary">عرض جميع الخدمات</a>
        </div>
    </div>
</section>

<!-- قسم المميزات -->
<section class="features-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">لماذا تختارنا؟</h2>
            <p class="section-subtitle">نحن نتميز بمجموعة من المميزات التي تجعلنا الخيار الأمثل لك</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-item text-center p-4">
                    <div class="feature-icon mb-3">
                        <i data-feather="award" class="icon-lg"></i>
                    </div>
                    <h3 class="feature-title h4">جودة عالية</h3>
                    <p class="feature-text">نقدم خدمات ذات جودة عالية تلبي توقعات عملائنا وتتجاوزها</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-item text-center p-4">
                    <div class="feature-icon mb-3">
                        <i data-feather="clock" class="icon-lg"></i>
                    </div>
                    <h3 class="feature-title h4">التسليم في الموعد</h3>
                    <p class="feature-text">نلتزم بتسليم المشاريع في الوقت المحدد دون تأخير</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-item text-center p-4">
                    <div class="feature-icon mb-3">
                        <i data-feather="headphones" class="icon-lg"></i>
                    </div>
                    <h3 class="feature-title h4">دعم فني متميز</h3>
                    <p class="feature-text">فريق دعم فني متخصص جاهز للمساعدة في أي وقت</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم المشاريع -->
<section class="projects-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">أحدث مشاريعنا</h2>
            <p class="section-subtitle">تعرف على بعض المشاريع التي قمنا بتنفيذها مؤخراً</p>
        </div>
        
        <div class="row">
            <?php if (!empty($featured_projects)): ?>
                <?php foreach ($featured_projects as $project): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card project-card h-100">
                            <?php if (!empty($project['image_url'])): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['image_url']); ?>" class="card-img-top lazy" alt="<?php echo htmlspecialchars($project['title']); ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="card-text"><?php echo truncate_text(htmlspecialchars($project['description']), 120); ?></p> {/* Use description */}
                                {/* Category display removed */}
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="project-details.php?id=<?php echo htmlspecialchars($project['id']); ?>" class="btn btn-outline-primary">عرض المشروع</a> {/* Link to id */}
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        لا توجد مشاريع متاحة حالياً
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="projects.php" class="btn btn-primary">عرض جميع المشاريع</a>
        </div>
    </div>
</section>

<!-- قسم الاتصال -->
<section class="cta-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="cta-title">هل أنت مستعد لبدء مشروعك التالي معنا؟</h2>
                <p class="cta-text">تواصل معنا الآن للحصول على استشارة مجانية وعرض سعر مخصص لمشروعك</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="contact.php" class="btn btn-light btn-lg">تواصل معنا</a>
            </div>
        </div>
    </div>
</section>

<?php
// تضمين ذيل الصفحة
include 'includes/footer.php';
?>
