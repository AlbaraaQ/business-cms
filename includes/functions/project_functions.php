<?php
/**
 * وظائف خاصة بالمشاريع
 * 
 * هذا الملف يحتوي على وظائف التعامل مع المشاريع
 * مع دعم العمليات الأساسية والمتقدمة
 */

/**
 * الحصول على جميع المشاريع
 * 
 * @param bool $active_only الحصول على المشاريع النشطة فقط
 * @param bool $featured_only الحصول على المشاريع المميزة فقط
 * @param int $limit عدد المشاريع المطلوبة (0 للحصول على الكل)
 * @return array مصفوفة تحتوي على بيانات المشاريع
 */
function get_all_projects($limit = 0) { // Removed $active_only, $featured_only
    global $db;
    
    // Selecting specific columns as per new schema
    $sql = "SELECT id, title, description, image_url, project_url, created_at, updated_at FROM projects ORDER BY created_at DESC";
    $params = [];
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit_val";
        $params[':limit_val'] = $limit;
    }
    
    return $db->query($sql, $params);
}

/**
 * الحصول على مشروع بواسطة المعرف
 * 
 * @param int $project_id معرف المشروع
 * @return array|false بيانات المشروع أو false إذا لم يتم العثور عليه
 */
function get_project_by_id($project_id) { // Parameter name kept as $project_id for consistency, maps to :id
    global $db;
    // Selecting specific columns as per new schema, using 'id' as the column name
    return $db->queryOne("SELECT id, title, description, image_url, project_url, created_at, updated_at FROM projects WHERE id = :id", [':id' => $project_id]);
}

/**
 * إضافة مشروع جديد
 * 
 * @param array $data بيانات المشروع
 * @return int|false معرف المشروع الجديد أو false في حالة الفشل
 */
function add_project($data) {
    global $db;
    
    // Expects title, description, project_url, and optionally image_url
    if (empty($data['title'])) { // Title is likely mandatory
        return false;
    }
    
    $project_data = [
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'project_url' => $data['project_url'] ?? null,
        'image_url' => $data['image_url'] ?? null,
        // created_at and updated_at handled by NOW()
    ];
    
    $sql = "INSERT INTO projects (title, description, image_url, project_url, created_at, updated_at)
            VALUES (:title, :description, :image_url, :project_url, NOW(), NOW())";
    
    $result = $db->execute($sql, $project_data);
    return $result ? $db->lastInsertId() : false;
}

/**
 * تحديث مشروع
 * 
 * @param int $project_id معرف المشروع
 * @param array $data بيانات المشروع
 * @return bool نجاح أو فشل العملية
 */
function update_project($project_id, $data) { // $project_id is the ID of the project
    global $db;
    
    $project = get_project_by_id($project_id); // Uses new get_project_by_id
    if (!$project) {
        return false;
    }
    
    // Prepare data for update according to new schema
    $update_data = [];
    if (isset($data['title'])) {
        $update_data['title'] = $data['title'];
    }
    if (isset($data['description'])) {
        $update_data['description'] = $data['description'];
    }
    if (array_key_exists('image_url', $data)) { // Allow setting image_url to null
        $update_data['image_url'] = $data['image_url'];
    }
    if (array_key_exists('project_url', $data)) { // Allow setting project_url to null
        $update_data['project_url'] = $data['project_url'];
    }

    if (empty($update_data)) {
        return true; // No data to update
    }

    $set_clauses = [];
    foreach (array_keys($update_data) as $key) {
        $set_clauses[] = "$key = :$key";
    }
    $set_clauses[] = "updated_at = NOW()";

    $sql = "UPDATE projects SET " . implode(', ', $set_clauses) . " WHERE id = :id";

    $execute_params = [];
    foreach($update_data as $key => $value){
        $execute_params[":$key"] = $value;
    }
    $execute_params[':id'] = $project_id;

    return $db->execute($sql, $execute_params);
}

/**
 * حذف مشروع
 * 
 * @param int $project_id معرف المشروع
 * @return bool نجاح أو فشل العملية
 */
function delete_project($project_id) {
    global $db;
    
    // التحقق من وجود المشروع (uses new get_project_by_id)
    $project = get_project_by_id($project_id);
    if (!$project) {
        return false;
    }
    // Image file deletion for image_url is handled in admin/projects_management.php
    // No project_images table or old seo_settings to delete for this simplified schema.
    
    return $db->execute("DELETE FROM projects WHERE id = :id", [':id' => $project_id]);
}

/**
 * الحصول على المشاريع المميزة (الأحدث حالياً)
 * 
 * @param int $limit عدد المشاريع المطلوبة
 * @return array مصفوفة تحتوي على بيانات المشاريع
 */
function get_featured_projects($limit = 6) {
    global $db;
    $sql = "SELECT id, title, description, image_url, project_url FROM projects ORDER BY created_at DESC LIMIT :limit";
    return $db->query($sql, [':limit' => $limit]);
}

/**
 * البحث في المشاريع
 *
 * @param string $keyword كلمة البحث
 * @return array مصفوفة تحتوي على نتائج البحث
 */
function search_projects($keyword) {
    global $db;

    $keyword_param = '%' . $keyword . '%';

    $sql = "SELECT id, title, description, image_url, project_url
            FROM projects
            WHERE (title LIKE :keyword OR description LIKE :keyword)
            ORDER BY created_at DESC";

    return $db->query($sql, [':keyword' => $keyword_param]);
}

/**
 * عرض المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @return string كود HTML لعرض المشروع
 */
// Function render_project_card commented out and content removed
/*
*/

/**
 * عرض تفاصيل المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @param array $project_images صور المشروع (obsolete with new schema)
 * @return string كود HTML لعرض تفاصيل المشروع
 */
// Function render_project_details commented out and content removed
/*
*/

/**
 * عرض المشاريع ذات الصلة
 * 
 * @param array $related_projects المشاريع ذات الصلة
 * @return string كود HTML لعرض المشاريع ذات الصلة
 */
// Function render_related_projects commented out and content removed
/*
*/

/**
 * عرض تصنيفات المشاريع
 * 
 * @param array $categories تصنيفات المشاريع
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات المشاريع
 */
// Function render_project_categories commented out and content removed
/*
*/
}
