<?php
/**
 * ملف معالج AJAX لإدارة الصور والعمليات المتقدمة
 * 
 * هذا الملف يتعامل مع طلبات AJAX لرفع الصور المتعددة وترتيبها وحذفها
 * ويدعم عمليات السحب والإفلات والتحميل المتعدد
 */

// تضمين ملف التهيئة
require_once '../includes/init.php';

// التحقق من تسجيل دخول المدير
if (!is_admin_logged_in()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

// تحديد العملية المطلوبة
$action = isset($_POST['action']) ? $_POST['action'] : '';

// معالجة العمليات
switch ($action) {
    case 'upload_images':
        // رفع صور متعددة
        handle_upload_images();
        break;
        
    case 'delete_image':
        // حذف صورة
        handle_delete_image();
        break;
        
    case 'reorder_images':
        // إعادة ترتيب الصور
        handle_reorder_images();
        break;
        
    case 'set_main_image':
        // تعيين الصورة الرئيسية
        handle_set_main_image();
        break;
        
    default:
        // عملية غير معروفة
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
        break;
}

/**
 * معالجة رفع صور متعددة
 */
function handle_upload_images() {
    global $db;
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['entity_type']) || !isset($_POST['entity_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
        exit;
    }
    
    $entity_type = clean_input($_POST['entity_type']);
    $entity_id = (int)$_POST['entity_id'];
    
    // التحقق من نوع الكيان
    if (!in_array($entity_type, ['service', 'project'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'نوع كيان غير صالح']);
        exit;
    }
    
    // التحقق من وجود الكيان
    $entity_exists = false;
    
    if ($entity_type === 'service') {
        $entity_exists = db_exists('services', 'service_id = ?', [$entity_id]);
    } else if ($entity_type === 'project') {
        $entity_exists = db_exists('projects', 'project_id = ?', [$entity_id]);
    }
    
    if (!$entity_exists) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'الكيان غير موجود']);
        exit;
    }
    
    // التحقق من وجود ملفات
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'لم يتم توفير ملف صالح']);
        exit;
    }
    
    // رفع الصورة
    $upload_dir = $entity_type . 's';
    $image_path = upload_single_image($_FILES['file'], $upload_dir, $entity_type);
    
    if (!$image_path) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل في رفع الصورة']);
        exit;
    }
    
    // الحصول على أعلى ترتيب حالي
    $table_name = $entity_type . '_images';
    $id_column = $entity_type . '_id';
    
    $stmt = $db->prepare("SELECT MAX(sort_order) as max_order FROM $table_name WHERE $id_column = ?");
    $stmt->execute([$entity_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sort_order = $result['max_order'] ? $result['max_order'] + 1 : 1;
    
    // إدراج الصورة في قاعدة البيانات
    $image_data = [
        $id_column => $entity_id,
        'image_path' => $image_path,
        'sort_order' => $sort_order,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $image_id = db_insert($table_name, $image_data);
    
    if (!$image_id) {
        // حذف الصورة المرفوعة إذا فشل الإدراج في قاعدة البيانات
        delete_image($image_path);
        
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل في حفظ بيانات الصورة']);
        exit;
    }
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'message' => 'تم رفع الصورة بنجاح',
        'image' => [
            'id' => $image_id,
            'path' => UPLOAD_URL . $image_path,
            'sort_order' => $sort_order
        ]
    ]);
}

/**
 * معالجة حذف صورة
 */
function handle_delete_image() {
    global $db;
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['entity_type']) || !isset($_POST['image_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
        exit;
    }
    
    $entity_type = clean_input($_POST['entity_type']);
    $image_id = (int)$_POST['image_id'];
    
    // التحقق من نوع الكيان
    if (!in_array($entity_type, ['service', 'project'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'نوع كيان غير صالح']);
        exit;
    }
    
    // الحصول على بيانات الصورة
    $table_name = $entity_type . '_images';
    
    $stmt = $db->prepare("SELECT image_path FROM $table_name WHERE image_id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'الصورة غير موجودة']);
        exit;
    }
    
    // حذف الصورة من الملفات
    $image_deleted = delete_image($image['image_path']);
    
    // حذف الصورة من قاعدة البيانات
    $stmt = $db->prepare("DELETE FROM $table_name WHERE image_id = ?");
    $result = $stmt->execute([$image_id]);
    
    if (!$result) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل في حذف الصورة من قاعدة البيانات']);
        exit;
    }
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف الصورة بنجاح',
        'image_id' => $image_id
    ]);
}

/**
 * معالجة إعادة ترتيب الصور
 */
function handle_reorder_images() {
    global $db;
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['entity_type']) || !isset($_POST['image_order'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
        exit;
    }
    
    $entity_type = clean_input($_POST['entity_type']);
    $image_order = json_decode($_POST['image_order'], true);
    
    // التحقق من نوع الكيان
    if (!in_array($entity_type, ['service', 'project'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'نوع كيان غير صالح']);
        exit;
    }
    
    // التحقق من صحة بيانات الترتيب
    if (!is_array($image_order)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات الترتيب غير صالحة']);
        exit;
    }
    
    // تحديث ترتيب الصور
    $table_name = $entity_type . '_images';
    $success = true;
    
    $db->beginTransaction();
    
    try {
        foreach ($image_order as $index => $image_id) {
            $stmt = $db->prepare("UPDATE $table_name SET sort_order = ? WHERE image_id = ?");
            $result = $stmt->execute([$index + 1, $image_id]);
            
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $db->commit();
        } else {
            $db->rollBack();
        }
    } catch (Exception $e) {
        $db->rollBack();
        $success = false;
    }
    
    if (!$success) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث ترتيب الصور']);
        exit;
    }
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث ترتيب الصور بنجاح'
    ]);
}

/**
 * معالجة تعيين الصورة الرئيسية
 */
function handle_set_main_image() {
    global $db;
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['entity_type']) || !isset($_POST['entity_id']) || !isset($_POST['image_id'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
        exit;
    }
    
    $entity_type = clean_input($_POST['entity_type']);
    $entity_id = (int)$_POST['entity_id'];
    $image_id = (int)$_POST['image_id'];
    
    // التحقق من نوع الكيان
    if (!in_array($entity_type, ['service', 'project'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'نوع كيان غير صالح']);
        exit;
    }
    
    // الحصول على بيانات الصورة
    $images_table = $entity_type . '_images';
    $entity_table = $entity_type . 's';
    $entity_id_column = $entity_type . '_id';
    
    $stmt = $db->prepare("SELECT image_path FROM $images_table WHERE image_id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'الصورة غير موجودة']);
        exit;
    }
    
    // تحديث الصورة الرئيسية للكيان
    $image_column = $entity_type === 'service' ? 'image' : 'main_image';
    
    $stmt = $db->prepare("UPDATE $entity_table SET $image_column = ? WHERE $entity_id_column = ?");
    $result = $stmt->execute([$image['image_path'], $entity_id]);
    
    if (!$result) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل في تعيين الصورة الرئيسية']);
        exit;
    }
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'message' => 'تم تعيين الصورة الرئيسية بنجاح',
        'image_path' => UPLOAD_URL . $image['image_path']
    ]);
}
