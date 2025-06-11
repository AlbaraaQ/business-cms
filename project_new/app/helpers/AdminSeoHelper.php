<?php
/**
 * وظائف خاصة بإعدادات SEO
 * 
 * هذا الملف يحتوي على وظائف متقدمة لإدارة إعدادات SEO
 * مع دعم العناوين والأوصاف والكلمات المفتاحية والروابط المخصصة
 */

/**
 * الحصول على إعدادات SEO لكيان معين
 * 
 * @param string $entity_type نوع الكيان (service, project, page)
 * @param int $entity_id معرف الكيان
 * @return array|false بيانات إعدادات SEO أو false إذا لم تكن موجودة
 */
function get_seo_settings($entity_type, $entity_id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT * FROM seo_settings 
        WHERE entity_type = ? AND entity_id = ?
    ");
    
    $stmt->execute([$entity_type, $entity_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * إضافة أو تحديث إعدادات SEO لكيان معين
 * 
 * @param string $entity_type نوع الكيان (service, project, page)
 * @param int $entity_id معرف الكيان
 * @param array $data بيانات إعدادات SEO
 * @return bool نجاح أو فشل العملية
 */
function save_seo_settings($entity_type, $entity_id, $data) {
    global $db;
    
    // التحقق من وجود إعدادات SEO للكيان
    $existing = get_seo_settings($entity_type, $entity_id);
    
    // تحضير البيانات
    $seo_data = [
        'meta_title' => $data['meta_title'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'keywords' => $data['keywords'] ?? '',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($existing) {
        // تحديث الإعدادات الموجودة
        $stmt = $db->prepare("
            UPDATE seo_settings 
            SET meta_title = ?, meta_description = ?, keywords = ?, updated_at = ? 
            WHERE entity_type = ? AND entity_id = ?
        ");
        
        $params = array_values($seo_data);
        $params[] = $entity_type;
        $params[] = $entity_id;
        
        return $stmt->execute($params);
    } else {
        // إضافة إعدادات جديدة
        $seo_data['entity_type'] = $entity_type;
        $seo_data['entity_id'] = $entity_id;
        $seo_data['created_at'] = date('Y-m-d H:i:s');
        
        $columns = implode(', ', array_keys($seo_data));
        $placeholders = implode(', ', array_fill(0, count($seo_data), '?'));
        
        $stmt = $db->prepare("
            INSERT INTO seo_settings ($columns) 
            VALUES ($placeholders)
        ");
        
        return $stmt->execute(array_values($seo_data));
    }
}

/**
 * إنشاء slug من نص
 * 
 * @param string $text النص المراد تحويله إلى slug
 * @return string الـ slug الناتج
 */
function generate_slug($text) {
    // تحويل الحروف إلى حروف صغيرة
    $text = mb_strtolower($text, 'UTF-8');
    
    // استبدال الحروف العربية بحروف لاتينية (transliteration)
    $arabic_map = [
        'أ' => 'a', 'إ' => 'e', 'آ' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th',
        'ج' => 'j', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'th', 'ر' => 'r',
        'ز' => 'z', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't',
        'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => 'h', 'و' => 'w', 'ي' => 'y',
        'ى' => 'a', 'ئ' => 'e', 'ء' => 'a', 'ؤ' => 'o', 'ة' => 'h', 'ا' => 'a'
    ];
    
    foreach ($arabic_map as $arabic => $latin) {
        $text = str_replace($arabic, $latin, $text);
    }
    
    // استبدال المسافات والرموز بشرطات
    $text = preg_replace('/[^a-z0-9]/', '-', $text);
    
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
 * التأكد من تفرد slug
 * 
 * @param string $slug الـ slug المراد التحقق منه
 * @param string $table اسم الجدول
 * @param string $id_column اسم عمود المعرف
 * @param int|null $exclude_id معرف الكيان المستثنى من التحقق (للتعديل)
 * @return string الـ slug الفريد
 */
function ensure_unique_slug($slug, $table, $id_column, $exclude_id = null) {
    global $db;
    
    $original_slug = $slug;
    $counter = 1;
    $is_unique = false;
    
    while (!$is_unique) {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE slug = ?";
        $params = [$slug];
        
        if ($exclude_id !== null) {
            $sql .= " AND $id_column != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $is_unique = true;
        } else {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
    }
    
    return $slug;
}

/**
 * الحصول على عنوان الصفحة (meta title) لكيان معين
 * 
 * @param string $entity_type نوع الكيان (service, project, page)
 * @param int $entity_id معرف الكيان
 * @param string $default_title العنوان الافتراضي إذا لم يكن هناك عنوان مخصص
 * @return string عنوان الصفحة
 */
function get_meta_title($entity_type, $entity_id, $default_title = '') {
    $seo_settings = get_seo_settings($entity_type, $entity_id);
    
    if ($seo_settings && !empty($seo_settings['meta_title'])) {
        return $seo_settings['meta_title'];
    }
    
    return $default_title;
}

/**
 * الحصول على وصف الصفحة (meta description) لكيان معين
 * 
 * @param string $entity_type نوع الكيان (service, project, page)
 * @param int $entity_id معرف الكيان
 * @param string $default_description الوصف الافتراضي إذا لم يكن هناك وصف مخصص
 * @return string وصف الصفحة
 */
function get_meta_description($entity_type, $entity_id, $default_description = '') {
    $seo_settings = get_seo_settings($entity_type, $entity_id);
    
    if ($seo_settings && !empty($seo_settings['meta_description'])) {
        return $seo_settings['meta_description'];
    }
    
    return $default_description;
}

/**
 * الحصول على الكلمات المفتاحية (keywords) لكيان معين
 * 
 * @param string $entity_type نوع الكيان (service, project, page)
 * @param int $entity_id معرف الكيان
 * @param string $default_keywords الكلمات المفتاحية الافتراضية إذا لم تكن هناك كلمات مخصصة
 * @return string الكلمات المفتاحية
 */
function get_keywords($entity_type, $entity_id, $default_keywords = '') {
    $seo_settings = get_seo_settings($entity_type, $entity_id);
    
    if ($seo_settings && !empty($seo_settings['keywords'])) {
        return $seo_settings['keywords'];
    }
    
    return $default_keywords;
}

/**
 * إضافة وسوم SEO إلى رأس الصفحة
 * 
 * @param string $title عنوان الصفحة
 * @param string $description وصف الصفحة
 * @param string $keywords الكلمات المفتاحية
 * @param string $canonical_url الرابط القانوني (canonical URL)
 * @return string وسوم SEO
 */
function generate_seo_tags($title, $description = '', $keywords = '', $canonical_url = '') {
    $tags = '';
    
    // عنوان الصفحة
    if (!empty($title)) {
        $tags .= '<title>' . htmlspecialchars($title) . '</title>' . PHP_EOL;
        $tags .= '<meta name="title" content="' . htmlspecialchars($title) . '">' . PHP_EOL;
        $tags .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . PHP_EOL;
        $tags .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . PHP_EOL;
    }
    
    // وصف الصفحة
    if (!empty($description)) {
        $tags .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
        $tags .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
        $tags .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . PHP_EOL;
    }
    
    // الكلمات المفتاحية
    if (!empty($keywords)) {
        $tags .= '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . PHP_EOL;
    }
    
    // الرابط القانوني
    if (!empty($canonical_url)) {
        $tags .= '<link rel="canonical" href="' . htmlspecialchars($canonical_url) . '">' . PHP_EOL;
        $tags .= '<meta property="og:url" content="' . htmlspecialchars($canonical_url) . '">' . PHP_EOL;
    }
    
    // إضافة وسوم إضافية
    $tags .= '<meta property="og:type" content="website">' . PHP_EOL;
    $tags .= '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
    
    return $tags;
}
