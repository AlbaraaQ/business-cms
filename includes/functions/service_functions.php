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
function get_all_services($limit = 0) { // Removed $active_only, $featured_only
    global $db;
    
    // Selecting specific columns as per new schema
    $sql = "SELECT id, name, description, icon_class, created_at, updated_at FROM services ORDER BY created_at DESC";
    $params = [];
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit_val";
        $params[':limit_val'] = $limit; // Ensure placeholder is named if using named params
    }
    
    return $db->query($sql, $params);
}

/**
 * الحصول على خدمة بواسطة المعرف
 * 
 * @param int $service_id معرف الخدمة
 * @return array|false بيانات الخدمة أو false إذا لم يتم العثور عليها
 */
function get_service_by_id($service_id) { // Parameter name kept as $service_id for consistency with caller, but will use as :id
    global $db;
    // Selecting specific columns as per new schema, using 'id' as the column name
    return $db->queryOne("SELECT id, name, description, icon_class, created_at, updated_at FROM services WHERE id = :id", [':id' => $service_id]);
}

/**
 * إضافة خدمة جديدة
 * 
 * @param array $data بيانات الخدمة
 * @return int|false معرف الخدمة الجديدة أو false في حالة الفشل
 */
function add_service($data) {
    global $db;
    
    // التحقق من البيانات المطلوبة (name is the primary identifier now, description and icon_class are other fields)
    if (empty($data['name'])) { // Assuming 'name' is required
        return false;
    }
    
    // Prepare data for insertion according to new schema
    $service_data = [
        'name' => $data['name'],
        'description' => $data['description'] ?? null, // Description can be nullable or empty
        'icon_class' => $data['icon_class'] ?? null,   // Icon class can be nullable or empty
        // created_at and updated_at will be set by NOW() in the query
    ];
    
    // إدراج الخدمة في قاعدة البيانات
    $sql = "INSERT INTO services (name, description, icon_class, created_at, updated_at)
            VALUES (:name, :description, :icon_class, NOW(), NOW())";

    $result = $db->execute($sql, $service_data);
    return $result ? $db->lastInsertId() : false;
}

/**
 * تحديث خدمة
 * 
 * @param int $service_id معرف الخدمة
 * @param array $data بيانات الخدمة
 * @return bool نجاح أو فشل العملية
 */
function update_service($service_id, $data) { // $service_id is the ID of the service to update
    global $db;
    
    // التحقق من وجود الخدمة (using the updated get_service_by_id which queries by `id`)
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // Prepare data for update according to new schema
    // Only 'name', 'description', 'icon_class' are updatable via this function as per requirements
    $update_data = [];
    if (isset($data['name'])) {
        $update_data['name'] = $data['name'];
    }
    if (isset($data['description'])) {
        $update_data['description'] = $data['description'];
    }
    if (isset($data['icon_class'])) {
        $update_data['icon_class'] = $data['icon_class'];
    }

    if (empty($update_data)) { // Nothing to update
        return true; // Or false if an update was expected
    }

    // Build SET clauses
    $set_clauses = [];
    foreach (array_keys($update_data) as $key) {
        $set_clauses[] = "$key = :$key";
    }
    
    // Add updated_at timestamp
    $set_clauses[] = "updated_at = NOW()";
    
    $sql = "UPDATE services SET " . implode(', ', $set_clauses) . " WHERE id = :id";
    
    // Add the service ID to the parameters for the WHERE clause
    $update_data[':id'] = $service_id;
    
    // Map parameters for execute, ensuring correct placeholders
    $execute_params = [];
    foreach($update_data as $key => $value){
        // If key already starts with ':', it's the :id for WHERE clause
        $param_key = (strpos($key, ':') === 0) ? substr($key, 1) : $key;
        $execute_params[":$param_key"] = $value;
    }

    return $db->execute($sql, $execute_params);
}

/**
 * حذف خدمة
 * 
 * @param int $service_id معرف الخدمة
 * @return bool نجاح أو فشل العملية
 */
function delete_service($service_id) {
    global $db;
    
    // التحقق من وجود الخدمة (using the updated get_service_by_id which queries by `id`)
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        return false;
    }
    
    // No need to delete images or SEO settings as per new requirements for this function

    // حذف الخدمة من قاعدة البيانات using `id`
    return $db->execute("DELETE FROM services WHERE id = :id", [':id' => $service_id]);
}

/**
 * الحصول على الخدمات المميزة (الأحدث حالياً)
 * 
 * @param int $limit عدد الخدمات المطلوبة
 * @return array مصفوفة تحتوي على بيانات الخدمات
 */
function get_featured_services($limit = 6) {
    global $db;
    $sql = "SELECT id, name, description, icon_class FROM services ORDER BY created_at DESC LIMIT :limit";
    return $db->query($sql, [':limit' => $limit]);
}

/**
 * عرض الخدمة في الواجهة الأمامية
 * 
 * @param array $service بيانات الخدمة
 * @return string كود HTML لعرض الخدمة
 */
/*
function render_service_card($service) {
    $output = '<div class="col-md-4 mb-4">';
    $output .= '<div class="card service-card h-100">';
    
    // صورة الخدمة
    if (!empty($service['image'])) { // This would need to be changed if image handling changes
        $output .= '<div class="service-image">';
        $output .= lazy_load_image(UPLOAD_URL . '/' . $service['image'], $service['name'], 'card-img-top'); // title -> name
        $output .= '</div>';
    } elseif (!empty($service['icon_class'])) { // Display icon if no image
        $output .= '<div class="service-icon-display text-center p-3"><i class="' . htmlspecialchars($service['icon_class']) . ' fa-3x"></i></div>';
    }
    
    $output .= '<div class="card-body">';
    
    // عنوان الخدمة
    $output .= '<h3 class="card-title">' . htmlspecialchars($service['name']) . '</h3>'; // title -> name
    
    // وصف الخدمة - using description, truncated
    $output .= '<p class="card-text">' . truncate_text($service['description'], 100) . '</p>';
    
    $output .= '</div>';
    
    $output .= '<div class="card-footer bg-transparent border-0">';
    // Link generation would need to be updated if slugs are removed or structure changes
    // For now, assuming a generic link or placeholder
    $output .= '<a href="service-details.php?id=' . $service['id'] . '" class="btn btn-primary">عرض المزيد</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض تفاصيل الخدمة في الواجهة الأمامية
 * 
 * @param array $service بيانات الخدمة
 * @param array $service_images صور الخدمة (This parameter might become obsolete or change)
 * @return string كود HTML لعرض تفاصيل الخدمة
 */
/*
function render_service_details($service, $service_images = []) { // service_images might be removed or handled differently
    $output = '<div class="service-details">';
    
    // عنوان الخدمة
    $output .= '<h1 class="service-title">' . htmlspecialchars($service['name']) . '</h1>'; // title -> name
    
    // Icon if available
    if (!empty($service['icon_class'])) {
        $output .= '<div class="service-main-icon mb-3"><i class="' . htmlspecialchars($service['icon_class']) . ' fa-2x"></i></div>';
    }
    
    // وصف الخدمة
    $output .= '<div class="service-description mb-4">';
    $output .= '<h2>وصف الخدمة</h2>';
    // Assuming description field contains HTML if it was from a rich text editor
    $output .= '<div class="content">' . ($service['description']) . '</div>';
    $output .= '</div>';
    
    // زر طلب الخدمة (link might need update)
    $output .= '<div class="service-cta mt-4">';
    $output .= '<a href="contact.php?service=' . urlencode($service['name']) . '" class="btn btn-primary btn-lg">طلب الخدمة</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض الخدمات ذات الصلة
 * 
 * @param array $related_services الخدمات ذات الصلة
 * @return string كود HTML لعرض الخدمات ذات الصلة
 */
/*
function render_related_services($related_services) {
    if (empty($related_services)) {
        return '';
    }
    
    $output = '<div class="related-services mt-5">';
    $output .= '<h2 class="section-title mb-4">خدمات ذات صلة</h2>';
    
    $output .= '<div class="row">';
    
    // foreach ($related_services as $service) {
    //     $output .= render_service_card($service); // This function is commented out
    // }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض تصنيفات الخدمات
 * 
 * @param array $categories تصنيفات الخدمات
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات الخدمات
 */
/*
function render_service_categories($categories, $active_category = '') {
    // This function is likely obsolete with the removal of categories from services table
    return '';
}
*/
}
