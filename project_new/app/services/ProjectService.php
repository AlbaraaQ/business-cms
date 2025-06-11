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
function get_all_projects($active_only = false, $featured_only = false, $limit = 0) {
    global $db;
    
    $sql = "SELECT * FROM projects";
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
 * الحصول على مشروع بواسطة المعرف
 * 
 * @param int $project_id معرف المشروع
 * @return array|false بيانات المشروع أو false إذا لم يتم العثور عليه
 */
function get_project_by_id($project_id) {
    global $db;
    
    return $db->queryOne("SELECT * FROM projects WHERE project_id = :project_id", [':project_id' => $project_id]);
}

/**
 * الحصول على مشروع بواسطة الرابط المخصص
 * 
 * @param string $slug الرابط المخصص للمشروع
 * @return array|false بيانات المشروع أو false إذا لم يتم العثور عليه
 */
function get_project_by_slug($slug) {
    global $db;
    
    return $db->queryOne("SELECT * FROM projects WHERE slug = :slug", [':slug' => $slug]);
}

/**
 * الحصول على المشاريع حسب التصنيف
 * 
 * @param string $category تصنيف المشاريع
 * @param bool $active_only الحصول على المشاريع النشطة فقط
 * @param int $limit عدد المشاريع المطلوبة (0 للحصول على الكل)
 * @return array مصفوفة تحتوي على بيانات المشاريع
 */
function get_projects_by_category($category, $active_only = true, $limit = 0) {
    global $db;
    
    $sql = "SELECT * FROM projects WHERE category = ?";
    $params = [$category];
    
    if ($active_only) {
        $sql .= " AND is_active = 1";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT :limit_val";
        $params[] = $limit; // Assuming $db->query can handle mixed or understands positional for LIMIT
    }
    
    return $db->query($sql, $params);
}

/**
 * البحث في المشاريع
 * 
 * @param string $keyword كلمة البحث
 * @param bool $active_only البحث في المشاريع النشطة فقط
 * @return array مصفوفة تحتوي على نتائج البحث
 */
function search_projects($keyword, $active_only = true) {
    global $db;
    
    $keyword = '%' . $keyword . '%';
    
    $sql = "SELECT * FROM projects WHERE (title LIKE ? OR description LIKE ? OR short_description LIKE ? OR category LIKE ? OR client LIKE ?)";
    $params = [$keyword, $keyword, $keyword, $keyword, $keyword];
    
    if ($active_only) {
        $sql .= " AND is_active = 1";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    return $db->query($sql, $params);
}

/**
 * الحصول على المشاريع ذات الصلة
 * 
 * @param int $project_id معرف المشروع الحالي
 * @param string $category تصنيف المشروع الحالي
 * @param int $limit عدد المشاريع المطلوبة
 * @return array مصفوفة تحتوي على المشاريع ذات الصلة
 */
function get_related_projects($project_id, $category, $limit = 3) {
    global $db;
    
    $sql = "SELECT * FROM projects WHERE project_id != :project_id AND category = :category AND is_active = 1 ORDER BY RAND() LIMIT :limit_val";
    
    return $db->query($sql, [':project_id' => $project_id, ':category' => $category, ':limit_val' => $limit]);
}

/**
 * الحصول على تصنيفات المشاريع
 * 
 * @param bool $active_only الحصول على تصنيفات المشاريع النشطة فقط
 * @return array مصفوفة تحتوي على تصنيفات المشاريع
 */
function get_project_categories($active_only = true) {
    global $db;
    
    $sql = "SELECT DISTINCT category FROM projects";
    
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
 * إضافة مشروع جديد
 * 
 * @param array $data بيانات المشروع
 * @return int|false معرف المشروع الجديد أو false في حالة الفشل
 */
function add_project($data) {
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
    $data['slug'] = ensure_unique_slug($data['slug'], 'projects', 'project_id');
    
    // إضافة تاريخ الإنشاء والتحديث
    if (!isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    
    if (!isset($data['updated_at'])) {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    // إدراج المشروع في قاعدة البيانات
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO projects ($columns) VALUES ($placeholders)";
    $result = $db->execute($sql, $data);
    return $result ? $db->lastInsertId() : false;
}

/**
 * تحديث مشروع
 * 
 * @param int $project_id معرف المشروع
 * @param array $data بيانات المشروع
 * @return bool نجاح أو فشل العملية
 */
function update_project($project_id, $data) {
    global $db;
    
    // التحقق من وجود المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        return false;
    }
    
    // تحديث slug إذا تم توفيره
    if (isset($data['slug']) && !empty($data['slug'])) {
        $data['slug'] = generate_slug($data['slug']);
        $data['slug'] = ensure_unique_slug($data['slug'], 'projects', 'project_id', $project_id);
    }
    
    // إضافة تاريخ التحديث
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    // تحديث المشروع في قاعدة البيانات
    $set_clauses = [];
    foreach (array_keys($data) as $key) {
        $set_clauses[] = "$key = :$key";
    }
    $sql = "UPDATE projects SET " . implode(', ', $set_clauses) . " WHERE project_id = :project_id_condition";
    $data_for_execute = $data;
    $data_for_execute['project_id_condition'] = $project_id;
    return $db->execute($sql, $data_for_execute);
}

/**
 * حذف مشروع
 * 
 * @param int $project_id معرف المشروع
 * @return bool نجاح أو فشل العملية
 */
function delete_project($project_id) {
    global $db;
    
    // التحقق من وجود المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        return false;
    }
    
    // حذف الصورة الرئيسية
    if (!empty($project['main_image'])) {
        delete_image($project['main_image']);
    }
    
    // حذف صور المشروع
    $project_images = get_project_images($project_id);
    
    foreach ($project_images as $image) {
        delete_image($image['image_path']);
    }
    
    // حذف صور المشروع من قاعدة البيانات
    $db->execute("DELETE FROM project_images WHERE project_id = ?", [$project_id]);
    
    // حذف إعدادات SEO
    $db->execute("DELETE FROM seo_settings WHERE entity_type = ? AND entity_id = ?", ['project', $project_id]);
    
    // حذف المشروع من قاعدة البيانات
    return $db->execute("DELETE FROM projects WHERE project_id = ?", [$project_id]);
}

/**
 * تغيير حالة المشروع (نشط/غير نشط)
 * 
 * @param int $project_id معرف المشروع
 * @param bool $is_active الحالة الجديدة
 * @return bool نجاح أو فشل العملية
 */
function toggle_project_status($project_id, $is_active) {
    global $db;
    
    // التحقق من وجود المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        return false;
    }
    
    // تحديث حالة المشروع
    $sql = "UPDATE projects SET is_active = :is_active WHERE project_id = :project_id";
    return $db->execute($sql, [':is_active' => $is_active ? 1 : 0, ':project_id' => $project_id]);
}

/**
 * تغيير حالة تمييز المشروع (مميز/غير مميز)
 * 
 * @param int $project_id معرف المشروع
 * @param bool $is_featured الحالة الجديدة
 * @return bool نجاح أو فشل العملية
 */
function toggle_project_featured($project_id, $is_featured) {
    global $db;
    
    // التحقق من وجود المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        return false;
    }
    
    // تحديث حالة تمييز المشروع
    $sql = "UPDATE projects SET is_featured = :is_featured WHERE project_id = :project_id";
    return $db->execute($sql, [':is_featured' => $is_featured ? 1 : 0, ':project_id' => $project_id]);
}

/**
 * عرض المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @return string كود HTML لعرض المشروع
 */
function render_project_card($project) {
    $output = '<div class="col-md-4 mb-4">';
    $output .= '<div class="card project-card h-100">';
    
    // صورة المشروع
    if (!empty($project['main_image'])) {
        $output .= '<div class="project-image">';
        $output .= lazy_load_image(UPLOAD_URL . '/' . $project['main_image'], $project['title'], 'card-img-top');
        $output .= '</div>';
    }
    
    $output .= '<div class="card-body">';
    
    // تصنيف المشروع
    if (!empty($project['category'])) {
        $output .= '<div class="project-category mb-2">' . htmlspecialchars($project['category']) . '</div>';
    }
    
    // عنوان المشروع
    $output .= '<h3 class="card-title">' . htmlspecialchars($project['title']) . '</h3>';
    
    // وصف المشروع
    $output .= '<p class="card-text">' . truncate_text($project['short_description'], 100) . '</p>';
    
    $output .= '</div>';
    
    $output .= '<div class="card-footer bg-transparent border-0">';
    $output .= '<a href="' . create_project_link($project['slug']) . '" class="btn btn-primary">عرض المزيد</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض تفاصيل المشروع في الواجهة الأمامية
 * 
 * @param array $project بيانات المشروع
 * @param array $project_images صور المشروع
 * @return string كود HTML لعرض تفاصيل المشروع
 */
function render_project_details($project, $project_images) {
    $output = '<div class="project-details">';
    
    // عنوان المشروع
    $output .= '<h1 class="project-title">' . htmlspecialchars($project['title']) . '</h1>';
    
    // معلومات المشروع
    $output .= '<div class="project-meta mb-4">';
    
    if (!empty($project['category'])) {
        $output .= '<span class="project-category me-3"><i class="fas fa-tag"></i> ' . htmlspecialchars($project['category']) . '</span>';
    }
    
    if (!empty($project['client'])) {
        $output .= '<span class="project-client me-3"><i class="fas fa-user"></i> ' . htmlspecialchars($project['client']) . '</span>';
    }
    
    if (!empty($project['completion_date'])) {
        $output .= '<span class="project-date"><i class="fas fa-calendar"></i> ' . format_date($project['completion_date']) . '</span>';
    }
    
    $output .= '</div>';
    
    // معرض الصور
    if (!empty($project_images)) {
        $output .= '<div class="project-gallery mb-4">';
        $output .= '<div class="row">';
        
        foreach ($project_images as $image) {
            $output .= '<div class="col-md-4 col-6 mb-3">';
            $output .= '<a href="' . UPLOAD_URL . '/' . $image['image_path'] . '" data-fancybox="project-gallery">';
            $output .= lazy_load_image(UPLOAD_URL . '/' . $image['image_path'], $project['title'], 'img-fluid rounded');
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
    } else if (!empty($project['main_image'])) {
        // إذا لم تكن هناك صور متعددة، عرض الصورة الرئيسية
        $output .= '<div class="project-main-image mb-4">';
        $output .= '<a href="' . UPLOAD_URL . '/' . $project['main_image'] . '" data-fancybox>';
        $output .= '<img src="' . UPLOAD_URL . '/' . $project['main_image'] . '" alt="' . htmlspecialchars($project['title']) . '" class="img-fluid rounded">';
        $output .= '</a>';
        $output .= '</div>';
    }
    
    // وصف المشروع
    $output .= '<div class="project-description mb-4">';
    $output .= '<h2>وصف المشروع</h2>';
    $output .= '<div class="content">' . $project['description'] . '</div>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض المشاريع ذات الصلة
 * 
 * @param array $related_projects المشاريع ذات الصلة
 * @return string كود HTML لعرض المشاريع ذات الصلة
 */
function render_related_projects($related_projects) {
    if (empty($related_projects)) {
        return '';
    }
    
    $output = '<div class="related-projects mt-5">';
    $output .= '<h2 class="section-title mb-4">مشاريع ذات صلة</h2>';
    
    $output .= '<div class="row">';
    
    foreach ($related_projects as $project) {
        $output .= render_project_card($project);
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * عرض تصنيفات المشاريع
 * 
 * @param array $categories تصنيفات المشاريع
 * @param string $active_category التصنيف النشط
 * @return string كود HTML لعرض تصنيفات المشاريع
 */
function render_project_categories($categories, $active_category = '') {
    if (empty($categories)) {
        return '';
    }
    
    $output = '<div class="project-categories mb-4">';
    $output .= '<ul class="nav nav-pills">';
    
    // إضافة زر "الكل"
    $all_active = empty($active_category) ? 'active' : '';
    $output .= '<li class="nav-item">';
    $output .= '<a class="nav-link ' . $all_active . '" href="projects.php">الكل</a>';
    $output .= '</li>';
    
    foreach ($categories as $category) {
        $active = ($category === $active_category) ? 'active' : '';
        
        $output .= '<li class="nav-item">';
        $output .= '<a class="nav-link ' . $active . '" href="projects.php?category=' . urlencode($category) . '">' . htmlspecialchars($category) . '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</div>';
    
    return $output;
}
