<?php
/**
 * وظائف التوصيات المستقبلية
 * 
 * هذا الملف يحتوي على وظائف وتحسينات مستقبلية تم تنفيذها
 * لتحسين تجربة المستخدم وأداء الموقع
 */

/**
 * تنفيذ التحميل الكسول للصور
 * 
 * يساعد في تحسين سرعة تحميل الصفحة عن طريق تحميل الصور فقط عندما تكون في نطاق الرؤية
 * 
 * @param string $image_url رابط الصورة
 * @param string $alt النص البديل للصورة
 * @param string $class فئات CSS إضافية
 * @return string كود HTML للصورة مع التحميل الكسول
 */
function lazy_load_image_advanced($image_url, $alt = '', $class = '') {
    // إنشاء صورة placeholder صغيرة جداً
    $placeholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 3 2\'%3E%3C/svg%3E';
    
    return '<img src="' . $placeholder . '" data-src="' . $image_url . '" alt="' . htmlspecialchars($alt) . '" class="lazyload ' . $class . '">';
}

/**
 * تحسين الصور تلقائياً
 * 
 * يقوم بضغط وتغيير حجم الصور لتحسين الأداء
 * 
 * @param string $image_path مسار الصورة
 * @param int $quality جودة الصورة (1-100)
 * @param int $max_width العرض الأقصى للصورة
 * @param int $max_height الارتفاع الأقصى للصورة
 * @return bool نجاح أو فشل العملية
 */
function optimize_image_advanced($image_path, $quality = 85, $max_width = 1920, $max_height = 1080) {
    // التحقق من وجود الملف
    if (!file_exists($image_path) || !is_file($image_path)) {
        return false;
    }
    
    // الحصول على نوع الصورة
    $image_info = getimagesize($image_path);
    
    if ($image_info === false) {
        return false;
    }
    
    $image_type = $image_info[2];
    $width = $image_info[0];
    $height = $image_info[1];
    
    // التحقق مما إذا كانت الصورة تحتاج إلى تغيير الحجم
    if ($width <= $max_width && $height <= $max_height) {
        // لا حاجة لتغيير الحجم، فقط ضغط الصورة
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($image_path);
                imagejpeg($image, $image_path, $quality);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($image_path);
                imagepng($image, $image_path, 9); // أقصى ضغط للصور PNG
                break;
            case IMAGETYPE_GIF:
                // لا نقوم بضغط صور GIF لأنها قد تكون متحركة
                return true;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($image_path);
                imagewebp($image, $image_path, $quality);
                break;
            default:
                return false;
        }
        
        if (isset($image)) {
            imagedestroy($image);
        }
        
        return true;
    }
    
    // تغيير حجم الصورة
    // حساب النسبة
    $ratio = min($max_width / $width, $max_height / $height);
    
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // إنشاء صورة جديدة بالحجم الجديد
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // إنشاء صورة من الملف
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            // الحفاظ على الشفافية
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
            // الحفاظ على الشفافية
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($image_path);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // نسخ الصورة القديمة إلى الصورة الجديدة مع تغيير الحجم
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // حفظ الصورة
    $result = false;
    
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $image_path, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $image_path, 9); // أقصى ضغط للصور PNG
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $image_path);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($new_image, $image_path, $quality);
            break;
    }
    
    // تحرير الذاكرة
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}

/**
 * إنشاء خريطة الموقع XML
 * 
 * يقوم بإنشاء ملف sitemap.xml لتحسين فهرسة محركات البحث
 * 
 * @param string $output_file مسار ملف خريطة الموقع
 * @return bool نجاح أو فشل العملية
 */
function generate_sitemap($output_file = 'sitemap.xml') {
    global $db;
    
    try {
        // بدء ملف XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // إضافة الصفحات الثابتة
        $static_pages = [
            '' => '1.0', // الصفحة الرئيسية
            'services.php' => '0.8',
            'projects.php' => '0.8',
            'about.php' => '0.7',
            'contact.php' => '0.7'
        ];
        
        foreach ($static_pages as $page => $priority) {
            $url = SITE_URL . '/' . $page;
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $url . '</loc>' . PHP_EOL;
            $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }
        
        // إضافة صفحات الخدمات
        $services = get_all_services(true);
        
        foreach ($services as $service) {
            $url = create_service_link($service['slug']);
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $url . '</loc>' . PHP_EOL;
            $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.6</priority>' . PHP_EOL;
            
            if (!empty($service['updated_at'])) {
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($service['updated_at'])) . '</lastmod>' . PHP_EOL;
            }
            
            $xml .= '  </url>' . PHP_EOL;
        }
        
        // إضافة صفحات المشاريع
        $projects = get_all_projects(true);
        
        foreach ($projects as $project) {
            $url = create_project_link($project['slug']);
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $url . '</loc>' . PHP_EOL;
            $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.6</priority>' . PHP_EOL;
            
            if (!empty($project['updated_at'])) {
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($project['updated_at'])) . '</lastmod>' . PHP_EOL;
            }
            
            $xml .= '  </url>' . PHP_EOL;
        }
        
        // إنهاء ملف XML
        $xml .= '</urlset>';
        
        // كتابة الملف
        file_put_contents(BASE_PATH . '/' . $output_file, $xml);
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إنشاء خريطة الموقع: " . $e->getMessage());
        return false;
    }
}

/**
 * إنشاء نسخة احتياطية تلقائية لقاعدة البيانات
 * 
 * يقوم بإنشاء نسخة احتياطية لقاعدة البيانات بشكل دوري
 * 
 * @param string $backup_dir مجلد النسخ الاحتياطية
 * @return string|false مسار ملف النسخة الاحتياطية أو false في حالة الفشل
 */
function create_automatic_backup($backup_dir = 'backups') {
    // التحقق من وجود المجلد
    $backup_path = BASE_PATH . '/' . $backup_dir;
    
    if (!is_dir($backup_path)) {
        mkdir($backup_path, 0755, true);
    }
    
    // إنشاء اسم الملف
    $date = date('Y-m-d_H-i-s');
    $backup_file = $backup_path . '/backup_' . $date . '.sql';
    
    // إنشاء النسخة الاحتياطية
    if (db_backup($backup_file)) {
        // ضغط الملف
        $zip_file = $backup_path . '/backup_' . $date . '.zip';
        
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE) === true) {
            $zip->addFile($backup_file, basename($backup_file));
            $zip->close();
            
            // حذف ملف SQL الأصلي
            unlink($backup_file);
            
            return $zip_file;
        }
        
        return $backup_file;
    }
    
    return false;
}

/**
 * تنظيف النسخ الاحتياطية القديمة
 * 
 * يقوم بحذف النسخ الاحتياطية القديمة للحفاظ على مساحة التخزين
 * 
 * @param string $backup_dir مجلد النسخ الاحتياطية
 * @param int $keep_days عدد الأيام للاحتفاظ بالنسخ الاحتياطية
 * @return int عدد الملفات التي تم حذفها
 */
function cleanup_old_backups($backup_dir = 'backups', $keep_days = 30) {
    $backup_path = BASE_PATH . '/' . $backup_dir;
    
    if (!is_dir($backup_path)) {
        return 0;
    }
    
    $deleted_count = 0;
    $current_time = time();
    $max_age = $keep_days * 86400; // تحويل الأيام إلى ثواني
    
    $files = glob($backup_path . '/*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $file_age = $current_time - filemtime($file);
            
            if ($file_age > $max_age) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }
    }
    
    return $deleted_count;
}

/**
 * تحسين أداء الموقع
 * 
 * يقوم بتنفيذ مجموعة من التحسينات لزيادة سرعة الموقع
 * 
 * @return array مصفوفة تحتوي على نتائج التحسينات
 */
function optimize_website_performance() {
    $results = [
        'image_optimization' => 0,
        'cache_generation' => false,
        'sitemap_generation' => false
    ];
    
    // تحسين الصور
    $image_dirs = [
        UPLOAD_DIR . '/services',
        UPLOAD_DIR . '/projects'
    ];
    
    foreach ($image_dirs as $dir) {
        if (is_dir($dir)) {
            $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            
            foreach ($images as $image) {
                if (optimize_image_advanced($image)) {
                    $results['image_optimization']++;
                }
            }
        }
    }
    
    // إنشاء خريطة الموقع
    $results['sitemap_generation'] = generate_sitemap();
    
    return $results;
}

/**
 * تنفيذ البحث المتقدم في الموقع
 * 
 * يقوم بالبحث في جميع محتويات الموقع (الخدمات، المشاريع، الصفحات)
 * 
 * @param string $keyword كلمة البحث
 * @return array مصفوفة تحتوي على نتائج البحث
 */
function advanced_search($keyword) {
    if (empty($keyword)) {
        return [];
    }
    
    $results = [
        'services' => [],
        'projects' => [],
        'total' => 0
    ];
    
    // البحث في الخدمات
    $services = search_services($keyword);
    $results['services'] = $services;
    $results['total'] += count($services);
    
    // البحث في المشاريع
    $projects = search_projects($keyword);
    $results['projects'] = $projects;
    $results['total'] += count($projects);
    
    return $results;
}

/**
 * عرض نتائج البحث
 * 
 * @param array $results نتائج البحث
 * @param string $keyword كلمة البحث
 * @return string كود HTML لعرض نتائج البحث
 */
function render_search_results($results, $keyword) {
    $output = '<div class="search-results">';
    
    // عنوان نتائج البحث
    $output .= '<h1 class="mb-4">نتائج البحث عن: <span class="text-primary">' . htmlspecialchars($keyword) . '</span></h1>';
    
    if ($results['total'] == 0) {
        $output .= '<div class="alert alert-info">لم يتم العثور على نتائج مطابقة لبحثك.</div>';
        
        // اقتراحات للبحث
        $output .= '<div class="search-suggestions mt-4">';
        $output .= '<h3>اقتراحات للبحث:</h3>';
        $output .= '<ul>';
        $output .= '<li>تأكد من كتابة جميع الكلمات بشكل صحيح.</li>';
        $output .= '<li>جرب كلمات مفتاحية مختلفة.</li>';
        $output .= '<li>جرب كلمات مفتاحية أكثر عمومية.</li>';
        $output .= '</ul>';
        $output .= '</div>';
    } else {
        $output .= '<div class="mb-3">تم العثور على <strong>' . $results['total'] . '</strong> نتيجة.</div>';
        
        // عرض نتائج الخدمات
        if (!empty($results['services'])) {
            $output .= '<h2 class="mt-4 mb-3">الخدمات</h2>';
            $output .= '<div class="row">';
            
            foreach ($results['services'] as $service) {
                $output .= render_service_card($service);
            }
            
            $output .= '</div>';
        }
        
        // عرض نتائج المشاريع
        if (!empty($results['projects'])) {
            $output .= '<h2 class="mt-4 mb-3">المشاريع</h2>';
            $output .= '<div class="row">';
            
            foreach ($results['projects'] as $project) {
                $output .= render_project_card($project);
            }
            
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    
    return $output;
}
