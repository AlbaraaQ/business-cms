<?php
/**
 * ملف تنظيف وإعادة هيكلة الكود
 * 
 * هذا الملف يحتوي على وظائف تحسين الكود وإزالة التكرار
 * وتحسين الأداء والأمان في المشروع
 */

/**
 * تنظيف المدخلات
 * 
 * @param string $data البيانات المراد تنظيفها
 * @return string البيانات بعد التنظيف
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * التحقق من صحة البريد الإلكتروني
 * 
 * @param string $email البريد الإلكتروني المراد التحقق منه
 * @return bool صحة البريد الإلكتروني
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من صحة رقم الهاتف
 * 
 * @param string $phone رقم الهاتف المراد التحقق منه
 * @return bool صحة رقم الهاتف
 */
function validate_phone($phone) {
    // إزالة الرموز غير الرقمية
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // التحقق من طول رقم الهاتف (8-15 رقم)
    return strlen($phone) >= 8 && strlen($phone) <= 15;
}

/**
 * التحقق من صحة URL
 * 
 * @param string $url الرابط المراد التحقق منه
 * @return bool صحة الرابط
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * تشفير كلمة المرور
 * 
 * @param string $password كلمة المرور المراد تشفيرها
 * @return string كلمة المرور المشفرة
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 * 
 * @param string $password كلمة المرور المدخلة
 * @param string $hash كلمة المرور المشفرة المخزنة
 * @return bool صحة كلمة المرور
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * إنشاء رمز CSRF
 * 
 * @return string رمز CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من رمز CSRF
 * 
 * @param string $token الرمز المراد التحقق منه
 * @return bool صحة الرمز
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تسجيل الأخطاء
 * 
 * @param string $message رسالة الخطأ
 * @param string $level مستوى الخطأ (error, warning, info)
 * @return bool نجاح أو فشل العملية
 */
function log_error($message, $level = 'error') {
    $log_file = ROOT_DIR . '/logs/error.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] [$level] $message" . PHP_EOL;
    
    return file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * تحويل التاريخ إلى تنسيق قاعدة البيانات
 * 
 * @param string $date التاريخ بتنسيق d/m/Y
 * @return string التاريخ بتنسيق Y-m-d
 */
function date_to_database($date) {
    if (empty($date)) {
        return null;
    }
    
    $date_parts = explode('/', $date);
    if (count($date_parts) !== 3) {
        return null;
    }
    
    return $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
}

/**
 * تحويل التاريخ من تنسيق قاعدة البيانات
 * 
 * @param string $date التاريخ بتنسيق Y-m-d
 * @return string التاريخ بتنسيق d/m/Y
 */
function date_from_database($date) {
    if (empty($date)) {
        return '';
    }
    
    $date_parts = explode('-', $date);
    if (count($date_parts) !== 3) {
        return '';
    }
    
    return $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
}

/**
 * تحويل النص إلى HTML
 * 
 * @param string $text النص المراد تحويله
 * @return string النص بعد التحويل
 */
function text_to_html($text) {
    // تحويل الروابط إلى روابط قابلة للنقر
    $text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $text);
    
    // تحويل أسطر النص إلى فقرات HTML
    $paragraphs = explode("\n\n", $text);
    $html = '';
    
    foreach ($paragraphs as $paragraph) {
        if (trim($paragraph) !== '') {
            $html .= '<p>' . nl2br($paragraph) . '</p>';
        }
    }
    
    return $html;
}

/**
 * تنظيف HTML من الوسوم غير المرغوب فيها
 * 
 * @param string $html النص HTML المراد تنظيفه
 * @return string النص HTML بعد التنظيف
 */
function sanitize_html($html) {
    // قائمة الوسوم المسموح بها
    $allowed_tags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><img><table><tr><td><th><thead><tbody>';
    
    // إزالة الوسوم غير المسموح بها
    $html = strip_tags($html, $allowed_tags);
    
    // إزالة السمات غير المرغوب فيها من وسوم الصور
    $html = preg_replace('/<img[^>]+>/i', function($match) {
        // استخراج السمات المسموح بها فقط
        if (preg_match('/src=["\'](.*?)["\']/', $match, $src) &&
            preg_match('/alt=["\'](.*?)["\']/', $match, $alt)) {
            return '<img src="' . $src[1] . '" alt="' . $alt[1] . '" class="img-fluid">';
        }
        
        return '';
    }, $html);
    
    // إزالة السمات غير المرغوب فيها من وسوم الروابط
    $html = preg_replace('/<a[^>]+>/i', function($match) {
        // استخراج السمات المسموح بها فقط
        if (preg_match('/href=["\'](.*?)["\']/', $match, $href)) {
            $target = strpos($href[1], 'http') === 0 ? ' target="_blank"' : '';
            return '<a href="' . $href[1] . '"' . $target . '>';
        }
        
        return '';
    }, $html);
    
    return $html;
}

/**
 * تحسين أداء الصفحة
 */
function optimize_page_performance() {
    // ضغط المخرجات
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'On');
        ini_set('zlib.output_compression_level', '5');
    }
    
    // تعيين رؤوس التخزين المؤقت
    $cache_time = 60 * 60 * 24; // يوم واحد
    header('Cache-Control: public, max-age=' . $cache_time);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
}

/**
 * تحسين أمان الصفحة
 */
function enhance_page_security() {
    // تعيين رؤوس الأمان
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // تعيين سياسة أمان المحتوى
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; ";
    $csp .= "img-src 'self' data: https:; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; ";
    $csp .= "connect-src 'self';";
    
    header('Content-Security-Policy: ' . $csp);
}

/**
 * تحسين SEO للصفحة
 * 
 * @param string $title عنوان الصفحة
 * @param string $description وصف الصفحة
 * @param string $keywords الكلمات المفتاحية
 * @param string $canonical_url الرابط القانوني
 * @param string $image صورة المشاركة
 */
function enhance_page_seo($title, $description = '', $keywords = '', $canonical_url = '', $image = '') {
    // تعيين العنوان
    echo '<title>' . htmlspecialchars($title) . '</title>' . PHP_EOL;
    
    // تعيين الوصف
    if (!empty($description)) {
        echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
    }
    
    // تعيين الكلمات المفتاحية
    if (!empty($keywords)) {
        echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . PHP_EOL;
    }
    
    // تعيين الرابط القانوني
    if (!empty($canonical_url)) {
        echo '<link rel="canonical" href="' . htmlspecialchars($canonical_url) . '">' . PHP_EOL;
    }
    
    // تعيين وسوم Open Graph
    echo '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . PHP_EOL;
    
    if (!empty($description)) {
        echo '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
    }
    
    if (!empty($canonical_url)) {
        echo '<meta property="og:url" content="' . htmlspecialchars($canonical_url) . '">' . PHP_EOL;
    }
    
    if (!empty($image)) {
        echo '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . PHP_EOL;
    }
    
    echo '<meta property="og:type" content="website">' . PHP_EOL;
    
    // تعيين وسوم Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
    echo '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . PHP_EOL;
    
    if (!empty($description)) {
        echo '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
    }
    
    if (!empty($image)) {
        echo '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . PHP_EOL;
    }
}

/**
 * تحسين تجربة المستخدم
 */
function enhance_user_experience() {
    // إضافة سكريبت التحميل الكسول للصور
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // التحميل الكسول للصور
        const lazyImages = document.querySelectorAll("img.lazy");
        
        if ("IntersectionObserver" in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const image = entry.target;
                        image.src = image.dataset.src;
                        image.classList.remove("lazy");
                        imageObserver.unobserve(image);
                    }
                });
            });
            
            lazyImages.forEach(function(image) {
                imageObserver.observe(image);
            });
        } else {
            // Fallback for browsers that don\'t support IntersectionObserver
            let lazyLoadThrottleTimeout;
            
            function lazyLoad() {
                if (lazyLoadThrottleTimeout) {
                    clearTimeout(lazyLoadThrottleTimeout);
                }
                
                lazyLoadThrottleTimeout = setTimeout(function() {
                    const scrollTop = window.pageYOffset;
                    
                    lazyImages.forEach(function(img) {
                        if (img.offsetTop < (window.innerHeight + scrollTop)) {
                            img.src = img.dataset.src;
                            img.classList.remove("lazy");
                        }
                    });
                    
                    if (lazyImages.length == 0) {
                        document.removeEventListener("scroll", lazyLoad);
                        window.removeEventListener("resize", lazyLoad);
                        window.removeEventListener("orientationChange", lazyLoad);
                    }
                }, 20);
            }
            
            document.addEventListener("scroll", lazyLoad);
            window.addEventListener("resize", lazyLoad);
            window.addEventListener("orientationChange", lazyLoad);
        }
        
        // تأكيد الحذف
        document.querySelectorAll(".delete-confirm").forEach(function(element) {
            element.addEventListener("click", function(e) {
                e.preventDefault();
                
                var targetUrl = this.getAttribute("href") || this.getAttribute("data-href");
                var message = this.getAttribute("data-confirm-message") || "هل أنت متأكد من رغبتك في حذف هذا العنصر؟";
                
                if (confirm(message)) {
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    } else {
                        // إذا كان العنصر زر داخل نموذج
                        var form = this.closest("form");
                        if (form) {
                            form.submit();
                        }
                    }
                }
            });
        });
    });
    </script>';
}

/**
 * تحسين الاستجابة للأجهزة المختلفة
 */
function enhance_responsive_design() {
    echo '<style>
    /* تحسينات الاستجابة للأجهزة المختلفة */
    @media (max-width: 576px) {
        .container {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        h1 {
            font-size: 1.8rem;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    }
    
    @media (min-width: 577px) and (max-width: 768px) {
        h1 {
            font-size: 2rem;
        }
        
        h2 {
            font-size: 1.75rem;
        }
    }
    
    /* تحسينات عامة */
    img {
        max-width: 100%;
        height: auto;
    }
    
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* تحسينات للنموذج */
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-control {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        color: #495057;
        background-color: #fff;
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    </style>';
}

/**
 * تحسين الأداء العام للمشروع
 */
function optimize_project_performance() {
    // تحسين أداء الصفحة
    optimize_page_performance();
    
    // تحسين أمان الصفحة
    enhance_page_security();
    
    // تحسين تجربة المستخدم
    enhance_user_experience();
    
    // تحسين الاستجابة للأجهزة المختلفة
    enhance_responsive_design();
}
