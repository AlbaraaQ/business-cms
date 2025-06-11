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
 * عرض المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @return string كود HTML لعرض المشروع
 */
/*
function render_project_card($project) {
    $output = '<div class="col-md-4 mb-4">';
    $output .= '<div class="card project-card h-100">';
    
    // صورة المشروع
    if (!empty($project['image_url'])) { // Changed from main_image to image_url
        $output .= '<div class="project-image">';
        $output .= lazy_load_image(UPLOAD_URL . '/' . $project['image_url'], $project['title'], 'card-img-top');
        $output .= '</div>';
    }
    
    $output .= '<div class="card-body">';
    
    // عنوان المشروع
    $output .= '<h3 class="card-title">' . htmlspecialchars($project['title']) . '</h3>';
    
    // وصف المشروع - using description (formerly short_description was used here)
    $output .= '<p class="card-text">' . truncate_text($project['description'], 100) . '</p>';
    
    $output .= '</div>';
    
    $output .= '<div class="card-footer bg-transparent border-0">';
    // Link generation would need update if slugs are removed. Assuming project_url or an ID-based link.
    $project_link = !empty($project['project_url']) ? htmlspecialchars($project['project_url']) : 'project-details.php?id=' . $project['id'];
    $output .= '<a href="' . $project_link . '" class="btn btn-primary"'.(!empty($project['project_url']) ? ' target="_blank"' : '').'>عرض المزيد</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض تفاصيل المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @param array $project_images صور المشروع (obsolete with new schema)
 * @return string كود HTML لعرض تفاصيل المشروع
 */
/*
function render_project_details($project, $project_images = []) { // project_images is now obsolete
    $output = '<div class="project-details">';
    
    // عنوان المشروع
    $output .= '<h1 class="project-title">' . htmlspecialchars($project['title']) . '</h1>';
    
    // Project URL if exists
    if (!empty($project['project_url'])) {
        $output .= '<p><a href="'.htmlspecialchars($project['project_url']).'" target="_blank" class="btn btn-info">زيارة المشروع</a></p>';
    }

    // الصورة الرئيسية (image_url)
    if (!empty($project['image_url'])) {
        $output .= '<div class="project-main-image mb-4">';
        $output .= '<a href="' . UPLOAD_URL . '/' . $project['image_url'] . '" data-fancybox>';
        $output .= '<img src="' . UPLOAD_URL . '/' . $project['image_url'] . '" alt="' . htmlspecialchars($project['title']) . '" class="img-fluid rounded">';
        $output .= '</a>';
        $output .= '</div>';
    }
    
    // وصف المشروع
    $output .= '<div class="project-description mb-4">';
    $output .= '<h2>وصف المشروع</h2>';
    // Assuming description field contains HTML if it was from a rich text editor
    $output .= '<div class="content">' . ($project['description']) . '</div>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض المشاريع ذات الصلة
 * 
 * @param array $related_projects المشاريع ذات الصلة
 * @return string كود HTML لعرض المشاريع ذات الصلة
 */
/*
function render_related_projects($related_projects) {
    if (empty($related_projects)) {
        return '';
    }
    
    $output = '<div class="related-projects mt-5">';
    $output .= '<h2 class="section-title mb-4">مشاريع ذات صلة</h2>';
    
    $output .= '<div class="row">';
    
    // foreach ($related_projects as $project) {
    //     $output .= render_project_card($project); // This function is commented out
    // }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}
*/

/**
 * عرض تصنيفات المشاريع
 * 
 * @param array $categories تصنيفات المشاريع
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات المشاريع
 */
/*
function render_project_categories($categories, $active_category = '') {
    // This function is likely obsolete with the removal of categories from projects table
    return '';
}
*/
}
