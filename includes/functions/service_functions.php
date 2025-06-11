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
 * البحث في الخدمات
 * 
 * @param string $keyword كلمة البحث
 * @return array مصفوفة تحتوي على نتائج البحث
 */
function search_services($keyword) {
    global $db;
    
    $keyword_param = '%' . $keyword . '%';
    
    $sql = "SELECT id, name, description, icon_class
            FROM services
            WHERE (name LIKE :keyword OR description LIKE :keyword)
            ORDER BY created_at DESC";
    
    return $db->query($sql, [':keyword' => $keyword_param]);
}

/**
 * عرض الخدمة في الواجهة الأمامية
 *
 * @param array $service بيانات الخدمة
 * @return string كود HTML لعرض الخدمة
 */
// Function render_service_card commented out and content removed
/*
*/

/**
 * عرض تفاصيل الخدمة في الواجهة الأمامية
 * 
 * @param array $service بيانات الخدمة
 * @param array $service_images صور الخدمة (This parameter might become obsolete or change)
 * @return string كود HTML لعرض تفاصيل الخدمة
 */
// Function render_service_details commented out and content removed
/*
*/

/**
 * عرض الخدمات ذات الصلة
 * 
 * @param array $related_services الخدمات ذات الصلة
 * @return string كود HTML لعرض الخدمات ذات الصلة
 */
// Function render_related_services commented out and content removed
/*
*/

/**
 * عرض تصنيفات الخدمات
 * 
 * @param array $categories تصنيفات الخدمات
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات الخدمات
 */
// Function render_service_categories commented out and content removed
/*
*/
}
