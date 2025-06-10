<?php
/**
 * ملف ذيل الصفحة
 * 
 * هذا الملف يحتوي على ذيل صفحات الموقع
 * ويتم تضمينه في جميع صفحات الموقع
 */

// الحصول على إعدادات الموقع
$site_settings = get_site_settings();

// الحصول على روابط التواصل الاجتماعي
$social_links = get_social_links();
?>
    </main>
    
    <!-- ذيل الصفحة -->
    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <!-- معلومات الموقع -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-title"><?php echo htmlspecialchars($site_settings['site_name'] ?? 'الموقع'); ?></h3>
                    <p class="footer-description">
                        <?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?>
                    </p>
                    
                    <!-- روابط التواصل الاجتماعي -->
                    <?php if (!empty($social_links)): ?>
                        <div class="social-links mt-3">
                            <?php foreach ($social_links as $social): ?>
                                <a href="<?php echo htmlspecialchars($social['url']); ?>" class="social-link me-2" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars($social['name']); ?>">
                                    <i data-feather="<?php echo htmlspecialchars($social['icon']); ?>" class="icon-sm"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- روابط سريعة -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links list-unstyled">
                        <li><a href="index.php" class="footer-link">الرئيسية</a></li>
                        <li><a href="about.php" class="footer-link">من نحن</a></li>
                        <li><a href="services.php" class="footer-link">خدماتنا</a></li>
                        <li><a href="projects.php" class="footer-link">مشاريعنا</a></li>
                        <li><a href="contact.php" class="footer-link">اتصل بنا</a></li>
                    </ul>
                </div>
                
                <!-- معلومات الاتصال -->
                <div class="col-lg-4">
                    <h3 class="footer-title">اتصل بنا</h3>
                    <ul class="contact-info list-unstyled">
                        <?php if (!empty($site_settings['contact_address'])): ?>
                            <li class="mb-2">
                                <i data-feather="map-pin" class="icon-sm me-2"></i>
                                <?php echo htmlspecialchars($site_settings['contact_address']); ?>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($site_settings['contact_email'])): ?>
                            <li class="mb-2">
                                <i data-feather="mail" class="icon-sm me-2"></i>
                                <a href="mailto:<?php echo htmlspecialchars($site_settings['contact_email']); ?>" class="footer-link">
                                    <?php echo htmlspecialchars($site_settings['contact_email']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($site_settings['contact_phone'])): ?>
                            <li class="mb-2">
                                <i data-feather="phone" class="icon-sm me-2"></i>
                                <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $site_settings['contact_phone'])); ?>" class="footer-link">
                                    <?php echo htmlspecialchars($site_settings['contact_phone']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <hr class="mt-4 mb-4 border-secondary">
            
            <!-- حقوق النشر -->
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_settings['site_name'] ?? 'الموقع'); ?>. جميع الحقوق محفوظة.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        تم التطوير بواسطة <a href="#" class="footer-link">فريق التطوير</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- زر العودة للأعلى -->
    <button id="back-to-top" class="btn btn-primary back-to-top-btn" title="العودة للأعلى">
        <i data-feather="arrow-up" class="icon-sm"></i>
    </button>

    <!-- JavaScript الأساسي -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    
    <!-- JavaScript للمكونات الإضافية -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.19/dist/fancybox/fancybox.umd.js"></script>
    
    <!-- JavaScript المخصص -->
    <script>
        window.APP_SETTINGS = window.APP_SETTINGS || {};
        window.APP_SETTINGS.uploadUrl = '<?php echo defined("UPLOAD_URL") ? rtrim(UPLOAD_URL, "/") . "/" : "/uploads/"; ?>';
    </script>
    <script src="assets/js/main.js"></script>
    
    <script>
    $(document).ready(function() {
        // تهيئة أيقونات Feather
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
        
        // تهيئة Fancybox
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind('[data-fancybox]', {
                // خيارات Fancybox
            });
        }
        
        // زر العودة للأعلى
        var backToTopBtn = $('#back-to-top');
        
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                backToTopBtn.fadeIn();
            } else {
                backToTopBtn.fadeOut();
            }
        });
        
        backToTopBtn.click(function() {
            $('html, body').animate({scrollTop: 0}, 800);
            return false;
        });
        
        // تحميل الصور بشكل كسول
        if ('loading' in HTMLImageElement.prototype) {
            // دعم متصفح أصلي للتحميل الكسول
            const images = document.querySelectorAll('img.lazy');
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        } else {
            // استخدام مكتبة للمتصفحات القديمة
            // يمكن تنفيذ ذلك باستخدام مكتبة مثل lazysizes
        }
    });
    </script>
</body>
</html>
