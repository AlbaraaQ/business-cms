<?php
/**
 * وظائف خاصة بإدارة الصور
 * 
 * هذا الملف يحتوي على وظائف متقدمة لإدارة الصور المتعددة
 * مع دعم السحب والإفلات والترتيب والحذف
 */

/**
 * رفع صورة واحدة
 * 
 * @param array $file بيانات الملف من $_FILES
 * @param string $directory المجلد الفرعي للرفع
 * @param string $prefix بادئة اسم الملف
 * @return string|false مسار الصورة المرفوعة أو false في حالة الفشل
 */
function upload_single_image($file, $directory, $prefix = '') {
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
    
    // إنشاء مجلد الرفع إذا لم يكن موجوداً
    $upload_dir = UPLOAD_DIR . '/' . $directory;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // إنشاء اسم فريد للملف
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = $prefix . '_' . uniqid() . '.' . $file_extension;
    $file_path = $directory . '/' . $file_name;
    $upload_path = UPLOAD_DIR . '/' . $file_path;
    
    // رفع الملف
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // تحسين الصورة (ضغط وتغيير الحجم إذا لزم الأمر)
        optimize_image($upload_path);
        
        return $file_path;
    }
    
    return false;
}

/**
 * حذف صورة
 * 
 * @param string $image_path المسار النسبي للصورة
 * @return bool نجاح أو فشل العملية
 */
function delete_image($image_path) {
    if (empty($image_path)) {
        return false;
    }
    
    $full_path = UPLOAD_DIR . '/' . $image_path;
    
    if (file_exists($full_path) && is_file($full_path)) {
        return unlink($full_path);
    }
    
    return false;
}

/**
 * تحسين الصورة (ضغط وتغيير الحجم)
 * 
 * @param string $image_path المسار الكامل للصورة
 * @return bool نجاح أو فشل العملية
 */
function optimize_image($image_path) {
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
    
    // إنشاء صورة من الملف
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
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
    
    // الحصول على أبعاد الصورة
    $width = imagesx($image);
    $height = imagesy($image);
    
    // تغيير حجم الصورة إذا كانت كبيرة جداً
    $max_width = 1920;
    $max_height = 1080;
    
    if ($width > $max_width || $height > $max_height) {
        // حساب النسبة
        $ratio = min($max_width / $width, $max_height / $height);
        
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        // إنشاء صورة جديدة بالحجم الجديد
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // الحفاظ على الشفافية للصور PNG و GIF
        if ($image_type === IMAGETYPE_PNG || $image_type === IMAGETYPE_GIF) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // نسخ الصورة القديمة إلى الصورة الجديدة مع تغيير الحجم
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // تحرير الصورة القديمة
        imagedestroy($image);
        
        // استخدام الصورة الجديدة
        $image = $new_image;
        $width = $new_width;
        $height = $new_height;
    }
    
    // حفظ الصورة
    $result = false;
    
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($image, $image_path, 85); // جودة 85%
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($image, $image_path, 8); // ضغط 8 (من 0 إلى 9)
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($image, $image_path);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($image, $image_path, 85); // جودة 85%
            break;
    }
    
    // تحرير الصورة
    imagedestroy($image);
    
    return $result;
}

/**
 * الحصول على صور الخدمة
 * 
 * @param int $service_id معرف الخدمة
 * @return array مصفوفة تحتوي على بيانات الصور
 */
function get_service_images($service_id) {
    global $db;
    
    $sql = "SELECT * FROM service_images WHERE service_id = :service_id ORDER BY sort_order ASC";
    return $db->query($sql, [':service_id' => $service_id]);
}

/**
 * الحصول على صور المشروع
 * 
 * @param int $project_id معرف المشروع
 * @return array مصفوفة تحتوي على بيانات الصور
 */
function get_project_images($project_id) {
    global $db;
    
    $sql = "SELECT * FROM project_images WHERE project_id = :project_id ORDER BY sort_order ASC";
    return $db->query($sql, [':project_id' => $project_id]);
}

/**
 * إضافة صورة للخدمة
 * 
 * @param int $service_id معرف الخدمة
 * @param string $image_path مسار الصورة
 * @param int $sort_order ترتيب الصورة
 * @return int|false معرف الصورة الجديدة أو false في حالة الفشل
 */
function add_service_image($service_id, $image_path, $sort_order = null) {
    global $db;
    
    // الحصول على أعلى ترتيب إذا لم يتم توفيره
    if ($sort_order === null) {
        $sql_max_order = "SELECT MAX(sort_order) as max_order FROM service_images WHERE service_id = :service_id";
        $result_row = $db->queryOne($sql_max_order, [':service_id' => $service_id]);
        $sort_order = $result_row && $result_row['max_order'] ? $result_row['max_order'] + 1 : 1;
    }
    
    // إدراج الصورة
    $sql_insert = "INSERT INTO service_images (service_id, image_path, sort_order, created_at)
                   VALUES (:service_id, :image_path, :sort_order, :created_at)";
    $params = [
        ':service_id' => $service_id,
        ':image_path' => $image_path,
        ':sort_order' => $sort_order,
        ':created_at' => date('Y-m-d H:i:s')
    ];

    $result = $db->execute($sql_insert, $params);

    return $result ? $db->lastInsertId() : false;
}

/**
 * إضافة صورة للمشروع
 * 
 * @param int $project_id معرف المشروع
 * @param string $image_path مسار الصورة
 * @param int $sort_order ترتيب الصورة
 * @return int|false معرف الصورة الجديدة أو false في حالة الفشل
 */
function add_project_image($project_id, $image_path, $sort_order = null) {
    global $db;
    
    // الحصول على أعلى ترتيب إذا لم يتم توفيره
    if ($sort_order === null) {
        $sql_max_order = "SELECT MAX(sort_order) as max_order FROM project_images WHERE project_id = :project_id";
        $result_row = $db->queryOne($sql_max_order, [':project_id' => $project_id]);
        $sort_order = $result_row && $result_row['max_order'] ? $result_row['max_order'] + 1 : 1;
    }
    
    // إدراج الصورة
    $sql_insert = "INSERT INTO project_images (project_id, image_path, sort_order, created_at)
                   VALUES (:project_id, :image_path, :sort_order, :created_at)";
    $params = [
        ':project_id' => $project_id,
        ':image_path' => $image_path,
        ':sort_order' => $sort_order,
        ':created_at' => date('Y-m-d H:i:s')
    ];

    $result = $db->execute($sql_insert, $params);

    return $result ? $db->lastInsertId() : false;
}

/**
 * حذف صورة الخدمة
 * 
 * @param int $image_id معرف الصورة
 * @return bool نجاح أو فشل العملية
 */
function delete_service_image($image_id) {
    global $db;
    
    // الحصول على مسار الصورة
    $sql_select = "SELECT image_path FROM service_images WHERE image_id = :image_id";
    $image = $db->queryOne($sql_select, [':image_id' => $image_id]);
    
    if (!$image) {
        return false;
    }
    
    // حذف الصورة من الملفات
    $image_file_deleted = delete_image($image['image_path']); // delete_image is a file system function
    
    // حذف الصورة من قاعدة البيانات
    $sql_delete = "DELETE FROM service_images WHERE image_id = :image_id";
    $db_delete_result = $db->execute($sql_delete, [':image_id' => $image_id]);
    
    return $db_delete_result && $image_file_deleted;
}

/**
 * حذف صورة المشروع
 * 
 * @param int $image_id معرف الصورة
 * @return bool نجاح أو فشل العملية
 */
function delete_project_image($image_id) {
    global $db;
    
    // الحصول على مسار الصورة
    $sql_select = "SELECT image_path FROM project_images WHERE image_id = :image_id";
    $image = $db->queryOne($sql_select, [':image_id' => $image_id]);
    
    if (!$image) {
        return false;
    }
    
    // حذف الصورة من الملفات
    $image_file_deleted = delete_image($image['image_path']); // delete_image is a file system function
    
    // حذف الصورة من قاعدة البيانات
    $sql_delete = "DELETE FROM project_images WHERE image_id = :image_id";
    $db_delete_result = $db->execute($sql_delete, [':image_id' => $image_id]);
    
    return $db_delete_result && $image_file_deleted;
}

/**
 * تحديث ترتيب صور الخدمة
 * 
 * @param array $image_order مصفوفة تحتوي على معرفات الصور بالترتيب المطلوب
 * @return bool نجاح أو فشل العملية
 */
function update_service_images_order($image_order) {
    global $db;
    
    // Assuming $db object has transaction methods or provides access to PDO for them
    $pdo = $db->getPdo(); // Example: Get PDO instance from Database class
    $pdo->beginTransaction();
    $success = true;
    
    try {
        $sql = "UPDATE service_images SET sort_order = :sort_order WHERE image_id = :image_id";
        foreach ($image_order as $index => $image_id) {
            $result = $db->execute($sql, [':sort_order' => $index + 1, ':image_id' => $image_id]);
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        // Log error $e->getMessage()
    }
    
    return $success;
}

/**
 * تحديث ترتيب صور المشروع
 * 
 * @param array $image_order مصفوفة تحتوي على معرفات الصور بالترتيب المطلوب
 * @return bool نجاح أو فشل العملية
 */
function update_project_images_order($image_order) {
    global $db;
    
    // Assuming $db object has transaction methods or provides access to PDO for them
    $pdo = $db->getPdo(); // Example: Get PDO instance from Database class
    $pdo->beginTransaction();
    $success = true;
    
    try {
        $sql = "UPDATE project_images SET sort_order = :sort_order WHERE image_id = :image_id";
        foreach ($image_order as $index => $image_id) {
            $result = $db->execute($sql, [':sort_order' => $index + 1, ':image_id' => $image_id]);
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        // Log error $e->getMessage()
    }
    
    return $success;
}

/**
 * تعيين الصورة الرئيسية للخدمة
 * 
 * @param int $service_id معرف الخدمة
 * @param int $image_id معرف الصورة
 * @return bool نجاح أو فشل العملية
 */
function set_service_main_image($service_id, $image_id) {
    global $db;
    
    // الحصول على مسار الصورة
    $sql_select = "SELECT image_path FROM service_images WHERE image_id = :image_id AND service_id = :service_id";
    $image = $db->queryOne($sql_select, [':image_id' => $image_id, ':service_id' => $service_id]);
    
    if (!$image) {
        return false;
    }
    
    // تحديث الصورة الرئيسية للخدمة
    $sql_update = "UPDATE services SET image = :image_path WHERE service_id = :service_id";
    return $db->execute($sql_update, [':image_path' => $image['image_path'], ':service_id' => $service_id]);
}

/**
 * تعيين الصورة الرئيسية للمشروع
 * 
 * @param int $project_id معرف المشروع
 * @param int $image_id معرف الصورة
 * @return bool نجاح أو فشل العملية
 */
function set_project_main_image($project_id, $image_id) {
    global $db;
    
    // الحصول على مسار الصورة
    $sql_select = "SELECT image_path FROM project_images WHERE image_id = :image_id AND project_id = :project_id";
    $image = $db->queryOne($sql_select, [':image_id' => $image_id, ':project_id' => $project_id]);
    
    if (!$image) {
        return false;
    }
    
    // تحديث الصورة الرئيسية للمشروع
    $sql_update = "UPDATE projects SET main_image = :main_image WHERE project_id = :project_id";
    return $db->execute($sql_update, [':main_image' => $image['image_path'], ':project_id' => $project_id]);
}
