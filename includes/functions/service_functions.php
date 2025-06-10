<?php
/**
 * وظائف خاصة بالخدمات
 * 
 * هذا الملف يحتوي على وظائف التعامل مع الخدمات
 * مع دعم العمليات الأساسية والمتقدمة
 */

/**
 * الحصول على جميع الخدمات
 * 
 * @param bool $active_only الحصول على الخدمات النشطة فقط
 * @param bool $featured_only الحصول على الخدمات المميزة فقط
 * @param int $limit عدد الخدمات المطلوبة (0 للحصول على الكل)
 * @return array مصفوفة تحتوي على بيانات الخدمات
 */
function get_all_services($active_only = false, $featured_only = false, $limit = 0) {
    global $db;
    
    $sql = "SELECT * FROM services";
    $params = [];
    $conditions = [];
    
    if ($active_only) {
        $conditions[] = "is_active = 1";
    }
    
    if ($featured_only) {
        $conditions[] = "is_featured = 1";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit_val";
        $params['limit_val'] = $limit;
    }
    
    return $db->query($sql, $params);
}

/**
 * الحصول على خدمة بواسطة المعرف
 * 
 * @param int $service_id معرف الخدمة
 * @return array|false بيانات الخدمة أو false إذا لم يتم العثور عليها
 */
function get_service_by_id($service_id) {
    global $db;
    
    return $db->queryOne("SELECT * FROM services WHERE service_id = :service_id", [':service_id' => $service_id]);
}

/**
 * الحصول على خدمة بواسطة الرابط المخصص
 * 
 * @param string $slug الرابط المخصص للخدمة
 * @return array|false بيانات الخدمة أو false إذا لم يتم العثور عليها
 */
function get_service_by_slug($slug) {
    global $db;
    
    return $db->queryOne("SELECT * FROM services WHERE slug = :slug", [':slug' => $slug]);
}

/**
 * الحصول على الخدمات حسب التصنيف
 * 
 * @param string $category تصنيف الخدمات
 * @param bool $active_only الحصول على الخدمات النشطة فقط
 * @param int $limit عدد الخدمات المطلوبة (0 للحصول على الكل)
 * @return array مصفوفة تحتوي على بيانات الخدمات
 */
function get_services_by_category($category, $active_only = true, $limit = 0) {
    global $db;
    
    $sql = "SELECT * FROM services WHERE category = ?";
    $params = [$category];
    
    if ($active_only) {
        $sql .= " AND is_active = 1";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit_val";
        // Parameters are already positional, so we need to ensure correct order if mixing named and positional
        // For simplicity with $db->query, ensure all params are consistently positional or named
        // Here, we'll stick to positional for this query as it was originally.
        // $params['limit_val'] = $limit; // This would be for named
        $params[] = $limit;
    }
    
    return $db->query($sql, $params);
}

/**
 * البحث في الخدمات
 * 
 * @param string $keyword كلمة البحث
 * @param bool $active_only البحث في الخدمات النشطة فقط
 * @return array مصفوفة تحتوي على نتائج البحث
 */
function search_services($keyword, $active_only = true) {
    global $db;
    
    $keyword = '%' . $keyword . '%';
    
    $sql = "SELECT * FROM services WHERE (title LIKE ? OR description LIKE ? OR short_description LIKE ? OR category LIKE ?)";
    $params = [$keyword, $keyword, $keyword, $keyword];
    
    if ($active_only) {
        $sql .= " AND is_active = 1";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    return $db->query($sql, $params);
}

/**
 * الحصول على الخدمات ذات الصلة
 * 
 * @param int $service_id معرف الخدمة الحالية
 * @param string $category تصنيف الخدمة الحالية
 * @param int $limit عدد الخدمات المطلوبة
 * @return array مصفوفة تحتوي على الخدمات ذات الصلة
 */
function get_related_services($service_id, $category, $limit = 3) {
    global $db;
    
    $sql = "SELECT * FROM services WHERE service_id != :service_id AND category = :category AND is_active = 1 ORDER BY RAND() LIMIT :limit_val";
    
    return $db->query($sql, [':service_id' => $service_id, ':category' => $category, ':limit_val' => $limit]);
}

/**
 * الحصول على تصنيفات الخدمات
 * 
 * @param bool $active_only الحصول على تصنيفات الخدمات النشطة فقط
 * @return array مصفوفة تحتوي على تصنيفات الخدمات
 */
function get_service_categories($active_only = true) {
    global $db;
    
    $sql = "SELECT DISTINCT category FROM services";
    
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY category ASC";
    
    $results = $db->query($sql);
    $categories = [];
    if ($results) {
        foreach ($results as $row) {
            if (!empty($row['category'])) {
                $categories[] = $row['category'];
            }
        }
    }
    return $categories;
}

/**
 * إضافة خدمة جديدة
 * 
 * @param array $data بيانات الخدمة
 * @return int|false معرف الخدمة الجديدة أو false في حالة الفشل
 */
function add_service($data) {
    global $db;
    
    // التحقق من البيانات المطلوبة
    if (empty($data['title']) || empty($data['short_description']) || empty($data['description'])) {
        return false;
    }
    
    // إنشاء slug إذا لم يتم توفيره
    if (empty($data['slug'])) {
        $data['slug'] = generate_slug($data['title']);
    } else {
        $data['slug'] = generate_slug($data['slug']);
    }
    
    // التحقق من تفرد slug
    $data['slug'] = ensure_unique_slug($data['slug'], 'services', 'service_id');
    
    // إضافة تاريخ الإنشاء والتحديث
    if (!isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    
    if (!isset($data['updated_at'])) {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    // إدراج الخدمة في قاعدة البيانات
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO services ($columns) VALUES ($placeholders)";
    $result = $db->execute($sql, $data);
    return $result ? $db->lastInsertId() : false;
}

/**
 * تحديث خدمة
 * 
 * @param int $service_id معرف الخدمة
 * @param array $data بيانات الخدمة
 * @return bool نجاح أو فشل العملية
 */
function update_service($service_id, $data) {
    global $db;
    
    // التحقق من وجود الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // تحديث slug إذا تم توفيره
    if (isset($data['slug']) && !empty($data['slug'])) {
        $data['slug'] = generate_slug($data['slug']);
        $data['slug'] = ensure_unique_slug($data['slug'], 'services', 'service_id', $service_id);
    }
    
    // إضافة تاريخ التحديث
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    // تحديث الخدمة في قاعدة البيانات
    $set_clauses = [];
    foreach (array_keys($data) as $key) {
        $set_clauses[] = "$key = :$key";
    }
    $sql = "UPDATE services SET " . implode(', ', $set_clauses) . " WHERE service_id = :service_id_condition";
    $data_for_execute = $data; // Use a copy for execute
    $data_for_execute['service_id_condition'] = $service_id;
    return $db->execute($sql, $data_for_execute);
}

/**
 * حذف خدمة
 * 
 * @param int $service_id معرف الخدمة
 * @return bool نجاح أو فشل العملية
 */
function delete_service($service_id) {
    global $db;
    
    // التحقق من وجود الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // حذف الصورة
    if (!empty($service['image'])) {
        delete_image($service['image']);
    }
    
    // حذف صور الخدمة
    $service_images = get_service_images($service_id);
    
    foreach ($service_images as $image) {
        delete_image($image['image_path']);
    }
    
    // حذف صور الخدمة من قاعدة البيانات
    $db->execute("DELETE FROM service_images WHERE service_id = ?", [$service_id]);
    
    // حذف إعدادات SEO
    $db->execute("DELETE FROM seo_settings WHERE entity_type = ? AND entity_id = ?", ['service', $service_id]);
    
    // حذف الخدمة من قاعدة البيانات
    return $db->execute("DELETE FROM services WHERE service_id = ?", [$service_id]);
}

/**
 * تغيير حالة الخدمة (نشطة/غير نشطة)
 * 
 * @param int $service_id معرف الخدمة
 * @param bool $is_active الحالة الجديدة
 * @return bool نجاح أو فشل العملية
 */
function toggle_service_status($service_id, $is_active) {
    global $db;
    
    // التحقق من وجود الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // تحديث حالة الخدمة
    $sql = "UPDATE services SET is_active = :is_active WHERE service_id = :service_id";
    return $db->execute($sql, [':is_active' => $is_active ? 1 : 0, ':service_id' => $service_id]);
}

/**
 * تغيير حالة تمييز الخدمة (مميزة/غير مميزة)
 * 
 * @param int $service_id معرف الخدمة
 * @param bool $is_featured الحالة الجديدة
 * @return bool نجاح أو فشل العملية
 */
function toggle_service_featured($service_id, $is_featured) {
    global $db;
    
    // التحقق من وجود الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // تحديث حالة تمييز الخدمة
    $sql = "UPDATE services SET is_featured = :is_featured WHERE service_id = :service_id";
    return $db->execute($sql, [':is_featured' => $is_featured ? 1 : 0, ':service_id' => $service_id]);
}

/**
 * عرض الخدمة في الواجهة الأمامية
 * 
 * @param array $service بيانات الخدمة
 * @return string كود HTML لعرض الخدمة
 */
function render_service_card($service) {
    $output = '<div class="col-md-4 mb-4">';
    $output .= '<div class="card service-card h-100">';
    
    // صورة الخدمة
    if (!empty($service['image'])) {
        $output .= '<div class="service-image">';
        $output .= lazy_load_image(UPLOAD_URL . '/' . $service['image'], $service['title'], 'card-img-top');
        $output .= '</div>';
    }
    
    $output .= '<div class="card-body">';
    
    // تصنيف الخدمة
    if (!empty($service['category'])) {
        $output .= '<div class="service-category mb-2">' . htmlspecialchars($service['category']) . '</div>';
    }
    
    // عنوان الخدمة
    $output .= '<h3 class="card-title">' . htmlspecialchars($service['title']) . '</h3>';
    
    // وصف الخدمة
    $output .= '<p class="card-text">' . truncate_text($service['short_description'], 100) . '</p>';
    
    $output .= '</div>';
    
    $output .= '<div class="card-footer bg-transparent border-0">';
    $output .= '<a href="' . create_service_link($service['slug']) . '" class="btn btn-primary">عرض المزيد</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض تفاصيل الخدمة في الواجهة الأمامية
 * 
 * @param array $service بيانات الخدمة
 * @param array $service_images صور الخدمة
 * @return string كود HTML لعرض تفاصيل الخدمة
 */
function render_service_details($service, $service_images) {
    $output = '<div class="service-details">';
    
    // عنوان الخدمة
    $output .= '<h1 class="service-title">' . htmlspecialchars($service['title']) . '</h1>';
    
    // معلومات الخدمة
    $output .= '<div class="service-meta mb-4">';
    
    if (!empty($service['category'])) {
        $output .= '<span class="service-category me-3"><i class="fas fa-tag"></i> ' . htmlspecialchars($service['category']) . '</span>';
    }
    
    $output .= '</div>';
    
    // معرض الصور
    if (!empty($service_images)) {
        $output .= '<div class="service-gallery mb-4">';
        $output .= '<div class="row">';
        
        foreach ($service_images as $image) {
            $output .= '<div class="col-md-4 col-6 mb-3">';
            $output .= '<a href="' . UPLOAD_URL . '/' . $image['image_path'] . '" data-fancybox="service-gallery">';
            $output .= lazy_load_image(UPLOAD_URL . '/' . $image['image_path'], $service['title'], 'img-fluid rounded');
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
    } else if (!empty($service['image'])) {
        // إذا لم تكن هناك صور متعددة، عرض الصورة الرئيسية
        $output .= '<div class="service-main-image mb-4">';
        $output .= '<a href="' . UPLOAD_URL . '/' . $service['image'] . '" data-fancybox>';
        $output .= '<img src="' . UPLOAD_URL . '/' . $service['image'] . '" alt="' . htmlspecialchars($service['title']) . '" class="img-fluid rounded">';
        $output .= '</a>';
        $output .= '</div>';
    }
    
    // وصف الخدمة
    $output .= '<div class="service-description mb-4">';
    $output .= '<h2>وصف الخدمة</h2>';
    $output .= '<div class="content">' . $service['description'] . '</div>';
    $output .= '</div>';
    
    // مميزات الخدمة
    if (!empty($service['features'])) {
        $output .= '<div class="service-features mb-4">';
        $output .= '<h2>مميزات الخدمة</h2>';
        $output .= '<div class="content">' . $service['features'] . '</div>';
        $output .= '</div>';
    }
    
    // زر طلب الخدمة
    $output .= '<div class="service-cta mt-4">';
    $output .= '<a href="contact.php?service=' . urlencode($service['title']) . '" class="btn btn-primary btn-lg">طلب الخدمة</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض الخدمات ذات الصلة
 * 
 * @param array $related_services الخدمات ذات الصلة
 * @return string كود HTML لعرض الخدمات ذات الصلة
 */
function render_related_services($related_services) {
    if (empty($related_services)) {
        return '';
    }
    
    $output = '<div class="related-services mt-5">';
    $output .= '<h2 class="section-title mb-4">خدمات ذات صلة</h2>';
    
    $output .= '<div class="row">';
    
    foreach ($related_services as $service) {
        $output .= render_service_card($service);
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض تصنيفات الخدمات
 * 
 * @param array $categories تصنيفات الخدمات
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات الخدمات
 */
function render_service_categories($categories, $active_category = '') {
    if (empty($categories)) {
        return '';
    }
    
    $output = '<div class="service-categories mb-4">';
    $output .= '<ul class="nav nav-pills">';
    
    // إضافة زر "الكل"
    $all_active = empty($active_category) ? 'active' : '';
    $output .= '<li class="nav-item">';
    $output .= '<a class="nav-link ' . $all_active . '" href="services.php">الكل</a>';
    $output .= '</li>';
    
    foreach ($categories as $category) {
        $active = ($category === $active_category) ? 'active' : '';
        
        $output .= '<li class="nav-item">';
        $output .= '<a class="nav-link ' . $active . '" href="services.php?category=' . urlencode($category) . '">' . htmlspecialchars($category) . '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</div>';
    
    return $output;
}
