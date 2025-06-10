<?php
/**
 * ملف الوظائف المساعدة
 * 
 * هذا الملف يحتوي على وظائف مساعدة متنوعة للمشروع
 */

/**
 * تحميل صورة واحدة
 * 
 * @param array $file معلومات الملف من $_FILES
 * @param string $destination_dir مجلد الوجهة
 * @param string $prefix بادئة اسم الملف
 * @return string|bool مسار الصورة النسبي أو false في حالة الفشل
 */
function upload_single_image($file, $destination_dir, $prefix = '') {
    // التحقق من وجود الملف
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // التحقق من نوع الملف
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // التحقق من حجم الملف (2 ميجابايت كحد أقصى)
    $max_size = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // إنشاء مجلد الوجهة إذا لم يكن موجوداً
    $upload_dir = UPLOAD_DIR . '/' . $destination_dir;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // إنشاء اسم ملف فريد
    $filename = $prefix . '_' . time() . '_' . uniqid();
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename .= '.' . $extension;
    
    // مسار الملف الكامل
    $filepath = $upload_dir . '/' . $filename;
    
    // نقل الملف المرفوع
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // تحسين الصورة إذا كانت الوظيفة متاحة
        if (function_exists('optimize_image')) {
            optimize_image($filepath, $filepath);
        }
        
        // إرجاع المسار النسبي للصورة
        return $destination_dir . '/' . $filename;
    }
    
    return false;
}

/**
 * معالجة رفع الصور بالسحب والإفلات
 * 
 * @param string $destination_dir مجلد الوجهة
 * @param string $prefix بادئة اسم الملف
 * @return array نتيجة العملية
 */
function handle_drag_drop_upload($destination_dir, $prefix = '') {
    // التحقق من وجود ملفات
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'لم يتم تحديد ملف أو حدث خطأ أثناء الرفع'
        ];
    }
    
    // تحميل الصورة
    $image_path = upload_single_image($_FILES['file'], $destination_dir, $prefix);
    
    if ($image_path) {
        return [
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح',
            'images' => [$image_path]
        ];
    }
    
    return [
        'success' => false,
        'message' => 'فشل في رفع الصورة'
    ];
}

/**
 * حذف صورة
 * 
 * @param string $image_path مسار الصورة النسبي
 * @return bool نجاح أو فشل العملية
 */
function delete_image($image_path) {
    if (empty($image_path)) {
        return false;
    }
    
    $filepath = UPLOAD_DIR . '/' . $image_path;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * تحسين الصورة
 * 
 * @param string $source_path مسار الصورة المصدر
 * @param string $destination_path مسار الصورة الوجهة
 * @param int $quality جودة الصورة (0-100)
 * @return bool نجاح أو فشل العملية
 */
function optimize_image($source_path, $destination_path, $quality = 85) {
    // التحقق من وجود الملف
    if (!file_exists($source_path)) {
        return false;
    }
    
    // الحصول على معلومات الصورة
    $info = getimagesize($source_path);
    if ($info === false) {
        return false;
    }
    
    // إنشاء صورة من المصدر
    $image = null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($source_path);
            }
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // الحفاظ على الشفافية للصور PNG و GIF
    if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_GIF) {
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
    
    // حفظ الصورة بالجودة المحددة
    $result = false;
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($image, $destination_path, $quality);
            break;
        case IMAGETYPE_PNG:
            // تحويل جودة JPEG إلى مستوى ضغط PNG (0-9)
            $png_quality = round((100 - $quality) / 11.111111);
            $result = imagepng($image, $destination_path, $png_quality);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($image, $destination_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $result = imagewebp($image, $destination_path, $quality);
            }
            break;
    }
    
    // تحرير الذاكرة
    imagedestroy($image);
    
    return $result;
}

/**
 * إنشاء خريطة الموقع
 * 
 * @return bool نجاح أو فشل العملية
 */
function generate_sitemap() {
    global $db;
    
    // مسار ملف خريطة الموقع
    $sitemap_file = ROOT_DIR . '/sitemap.xml';
    
    // بدء محتوى خريطة الموقع
    $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    // إضافة الصفحات الثابتة
    $static_pages = [
        '' => '1.0',
        'about.php' => '0.8',
        'services.php' => '0.9',
        'projects.php' => '0.9',
        'contact.php' => '0.7'
    ];
    
    foreach ($static_pages as $page => $priority) {
        $url = SITE_URL . '/' . $page;
        $content .= '  <url>' . PHP_EOL;
        $content .= '    <loc>' . $url . '</loc>' . PHP_EOL;
        $content .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
        $content .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $content .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
        $content .= '  </url>' . PHP_EOL;
    }
    
    // إضافة صفحات الخدمات
    $services = db_fetch_all("SELECT slug, updated_at FROM services WHERE is_active = 1");
    foreach ($services as $service) {
        $url = SITE_URL . '/service-details.php?slug=' . $service['slug'];
        $lastmod = !empty($service['updated_at']) ? date('Y-m-d', strtotime($service['updated_at'])) : date('Y-m-d');
        
        $content .= '  <url>' . PHP_EOL;
        $content .= '    <loc>' . $url . '</loc>' . PHP_EOL;
        $content .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $content .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
        $content .= '    <priority>0.8</priority>' . PHP_EOL;
        $content .= '  </url>' . PHP_EOL;
    }
    
    // إضافة صفحات المشاريع
    $projects = db_fetch_all("SELECT slug, updated_at FROM projects WHERE is_active = 1");
    foreach ($projects as $project) {
        $url = SITE_URL . '/project-details.php?slug=' . $project['slug'];
        $lastmod = !empty($project['updated_at']) ? date('Y-m-d', strtotime($project['updated_at'])) : date('Y-m-d');
        
        $content .= '  <url>' . PHP_EOL;
        $content .= '    <loc>' . $url . '</loc>' . PHP_EOL;
        $content .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $content .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
        $content .= '    <priority>0.7</priority>' . PHP_EOL;
        $content .= '  </url>' . PHP_EOL;
    }
    
    // إنهاء محتوى خريطة الموقع
    $content .= '</urlset>';
    
    // كتابة الملف
    return file_put_contents($sitemap_file, $content) !== false;
}

/**
 * إنشاء رابط مخصص (slug) من نص
 * 
 * @param string $text النص المراد تحويله
 * @return string الرابط المخصص
 */
function generate_slug($text) {
    // تحويل الحروف إلى حروف صغيرة
    $text = mb_strtolower($text, 'UTF-8');
    
    // استبدال الحروف غير اللاتينية
    $text = str_replace(['أ', 'إ', 'آ'], 'a', $text);
    $text = str_replace(['ب'], 'b', $text);
    $text = str_replace(['ت'], 't', $text);
    $text = str_replace(['ث'], 'th', $text);
    $text = str_replace(['ج'], 'j', $text);
    $text = str_replace(['ح'], 'h', $text);
    $text = str_replace(['خ'], 'kh', $text);
    $text = str_replace(['د'], 'd', $text);
    $text = str_replace(['ذ'], 'th', $text);
    $text = str_replace(['ر'], 'r', $text);
    $text = str_replace(['ز'], 'z', $text);
    $text = str_replace(['س'], 's', $text);
    $text = str_replace(['ش'], 'sh', $text);
    $text = str_replace(['ص'], 's', $text);
    $text = str_replace(['ض'], 'd', $text);
    $text = str_replace(['ط'], 't', $text);
    $text = str_replace(['ظ'], 'z', $text);
    $text = str_replace(['ع'], 'a', $text);
    $text = str_replace(['غ'], 'gh', $text);
    $text = str_replace(['ف'], 'f', $text);
    $text = str_replace(['ق'], 'q', $text);
    $text = str_replace(['ك'], 'k', $text);
    $text = str_replace(['ل'], 'l', $text);
    $text = str_replace(['م'], 'm', $text);
    $text = str_replace(['ن'], 'n', $text);
    $text = str_replace(['ه'], 'h', $text);
    $text = str_replace(['و'], 'w', $text);
    $text = str_replace(['ي'], 'y', $text);
    
    // استبدال المسافات بشرطات
    $text = preg_replace('/\s+/', '-', $text);
    
    // إزالة الأحرف الخاصة
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    
    // إزالة الشرطات المتكررة
    $text = preg_replace('/-+/', '-', $text);
    
    // إزالة الشرطات من البداية والنهاية
    $text = trim($text, '-');
    
    // إذا كان الناتج فارغاً، استخدم قيمة افتراضية
    if (empty($text)) {
        $text = 'item-' . time();
    }
    
    return $text;
}

/**
 * التحقق من تفرد الرابط المخصص
 * 
 * @param string $slug الرابط المخصص
 * @param string $table اسم الجدول
 * @param string $id_column اسم عمود المعرف
 * @param int $id معرف العنصر (للتحديث)
 * @return string الرابط المخصص الفريد
 */
function ensure_unique_slug($slug, $table, $id_column, $id = 0) {
    global $db;
    
    $original_slug = $slug;
    $counter = 1;
    $unique = false;
    
    while (!$unique) {
        if ($id > 0) {
            $query = "SELECT COUNT(*) as count FROM $table WHERE slug = ? AND $id_column != ?";
            $result = db_query($query, [$slug, $id]);
        } else {
            $query = "SELECT COUNT(*) as count FROM $table WHERE slug = ?";
            $result = db_query($query, [$slug]);
        }
        
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            $unique = true;
        } else {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
    }
    
    return $slug;
}
