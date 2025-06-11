<?php
/**
 * ملف تنفيذ عمليات الإضافة والتعديل والحذف المتقدمة للخدمات
 * 
 * هذا الملف يحتوي على وظائف متقدمة لإدارة الخدمات في لوحة التحكم
 * مع دعم رفع الصور المتعددة وإعدادات SEO
 */

// تضمين ملف التهيئة
require_once __DIR__ . '/init.php'; // Loads admin-specific initialization

// تضمين ملفات الوظائف اللازمة
// Assuming PROJECT_ROOT is defined in admin/init.php via config.php
// and that FUNCTIONS_DIR would correspond to PROJECT_ROOT . '/includes/functions'
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . '/includes/functions/admin_images_functions.php';
    require_once PROJECT_ROOT . '/includes/functions/admin_seo_functions.php';
    require_once PROJECT_ROOT . '/includes/functions/service_functions.php';
} else {
    // Fallback or error if PROJECT_ROOT is not defined
    // This part will likely cause issues if PROJECT_ROOT isn't defined,
    // as FUNCTIONS_DIR would not be defined either.
    trigger_error("PROJECT_ROOT not defined, cannot include function files for services_management.php", E_USER_WARNING);
}

// التحقق من تسجيل دخول المدير
// is_admin_logged_in() is expected to be available via admin/init.php (from includes/functions.php)
// or one of the includes above.
if (!is_admin_logged_in()) {
    redirect('login_form.php'); // redirect() also needs to be available.
}

// تعيين عنوان الصفحة
$page_title = 'إدارة الخدمات';

// تحديد العملية المطلوبة
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// معالجة العمليات
switch ($action) {
    case 'add':
        // إضافة خدمة جديدة
        handle_add_service();
        break;
        
    case 'edit':
        // تعديل خدمة موجودة
        handle_edit_service();
        break;
        
    case 'delete':
        // حذف خدمة
        handle_delete_service();
        break;
        
    // case 'manage_images':
    //     // إدارة صور الخدمة
    //     handle_manage_service_images();
    //     break;
        
    case 'seo_settings':
        // إعدادات SEO للخدمة
        handle_service_seo_settings();
        break;
        
    default:
        // عرض قائمة الخدمات
        handle_list_services();
        break;
}

/**
 * عرض قائمة الخدمات
 */
function handle_list_services() {
    global $db, $page_title;
    
    // الحصول على جميع الخدمات
    $services = get_all_services();
    
    // تضمين رأس الصفحة
    include 'includes/header.php';
    ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">قائمة الخدمات</h3>
                        <div class="card-tools">
                            <a href="services_management.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> إضافة خدمة جديدة
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($services)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>أيقونة (Class)</th>
                                            <th>الوصف</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $index => $service): ?>
                                            <tr>
                                                <td><?php echo $service['id']; // Using new id field ?></td>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td>
                                                    <?php if (!empty($service['icon_class'])): ?>
                                                        <i class="<?php echo htmlspecialchars($service['icon_class']); ?> fa-2x"></i> <small>(<?php echo htmlspecialchars($service['icon_class']); ?>)</small>
                                                    <?php else: ?>
                                                        <span class="text-muted">لا يوجد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo truncate_text(htmlspecialchars($service['description']), 100); ?></td>
                                                <td><?php echo format_date($service['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="services_management.php?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                         <a href="services_management.php?action=seo_settings&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" title="إعدادات SEO">
                                                            <i class="fas fa-search"></i>
                                                        </a>
                                                        <a href="services_management.php?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger delete-confirm" title="حذف" data-confirm-message="هل أنت متأكد من رغبتك في حذف هذه الخدمة؟">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                لا توجد خدمات متاحة حالياً. <a href="services_management.php?action=add">إضافة خدمة جديدة</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // تضمين ذيل الصفحة
    include 'includes/footer.php';
}

/**
 * إضافة خدمة جديدة
 */
function handle_add_service() {
    global $db, $page_title;
    
    // تعيين عنوان الصفحة
    $page_title = 'إضافة خدمة جديدة';
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من البيانات
        $name = clean_input($_POST['name'] ?? ''); // Changed from title
        $description = $_POST['description'] ?? ''; // Keep, but ensure it's handled as HTML if using rich editor
        $icon_class = clean_input($_POST['icon_class'] ?? ''); // Changed from icon
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'اسم الخدمة مطلوب'; // Changed from عنوان الخدمة
        }
        // Description might be optional or have different validation
        // if (empty($description)) {
        //     $errors[] = 'الوصف التفصيلي مطلوب';
        // }
        
        // إذا لم تكن هناك أخطاء، أضف الخدمة
        if (empty($errors)) {
            // تحضير بيانات الخدمة
            $service_data = [
                'name' => $name,
                'description' => $description,
                'icon_class' => $icon_class,
                // created_at and updated_at are handled by the add_service function or DB
            ];
            
            // إدراج الخدمة في قاعدة البيانات using the refactored function
            $service_id = add_service($service_data);
            
            if ($service_id) {
                // No SEO settings or image management here for now
                redirect('services_management.php?success=added');
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة الخدمة';
            }
        }
    }
    
    // تضمين رأس الصفحة
    include 'includes/header.php';
    ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="services_management.php">إدارة الخدمات</a></li>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">بيانات الخدمة</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="services_management.php?action=add" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- البيانات الأساسية -->
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">البيانات الأساسية</h3>
                                        </div>
                                        <div class="card-body">
                                <div class="col-md-12"> {/* Adjusted to full width as fewer fields overall */}
                                    <!-- البيانات الأساسية -->
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">البيانات الأساسية</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="icon_class">أيقونة الخدمة (Font Awesome Class)</label>
                                                <input type="text" class="form-control" id="icon_class" name="icon_class" placeholder="مثال: fas fa-cogs" value="<?php echo isset($_POST['icon_class']) ? htmlspecialchars($_POST['icon_class']) : ''; ?>">
                                                <small class="form-text text-muted">ادخل اسم الكلاس الخاص بالأيقونة من <a href="https://fontawesome.com/icons" target="_blank">Font Awesome</a> (e.g., fas fa-user).</small>
                                            </div>

                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي</label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">حفظ</button>
                                    <a href="services_management.php" class="btn btn-secondary">إلغاء</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // تضمين ذيل الصفحة
    include 'includes/footer.php';
}

/**
 * تعديل خدمة موجودة
 */
function handle_edit_service() {
    global $db, $page_title;
    
    // التحقق من وجود معرف الخدمة
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('services_management.php');
    }
    
    $service_id = (int)$_GET['id']; // Using 'id' as per new schema
    
    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id); // This function now uses 'id'
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // SEO settings are handled separately, so get_seo_settings call is removed for now.
    
    // تعيين عنوان الصفحة
    $page_title = 'تعديل خدمة: ' . htmlspecialchars($service['name']); // Changed to 'name'
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من البيانات
        $name = clean_input($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $icon_class = clean_input($_POST['icon_class'] ?? '');
        
        $errors = [];
        if (empty($name)) {
            $errors[] = 'اسم الخدمة مطلوب';
        }

        if (empty($errors)) {
            $service_data = [
                'name' => $name,
                'description' => $description,
                'icon_class' => $icon_class,
                // updated_at is handled by update_service function or DB
            ];
            
            // تحديث الخدمة في قاعدة البيانات using the refactored function
            $result = update_service($service_id, $service_data);
            
            if ($result) {
                // No SEO settings update here for now
                redirect('services_management.php?success=updated');
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث الخدمة';
            }
        }
    }
    
    // تضمين رأس الصفحة
    include 'includes/header.php';
    ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="services_management.php">إدارة الخدمات</a></li>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">بيانات الخدمة</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="services_management.php?action=edit&id=<?php echo $service_id; ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- البيانات الأساسية -->
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">البيانات الأساسية</h3>
                                        </div>
                                        <div class="card-body">
                                <div class="col-md-12"> {/* Adjusted to full width */}
                                    <!-- البيانات الأساسية -->
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">البيانات الأساسية</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="name">اسم الخدمة <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="icon_class">أيقونة الخدمة (Font Awesome Class)</label>
                                                <input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($service['icon_class'] ?? ''); ?>" placeholder="مثال: fas fa-cogs">
                                                 <small class="form-text text-muted">ادخل اسم الكلاس الخاص بالأيقونة من <a href="https://fontawesome.com/icons" target="_blank">Font Awesome</a> (e.g., fas fa-user).</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي</label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10"><?php echo htmlspecialchars($service['description']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    {/* SEO settings and Image sections are removed for now */}
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                    <a href="services_management.php" class="btn btn-secondary">إلغاء</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // تضمين ذيل الصفحة
    include 'includes/footer.php';
}

/**
 * حذف خدمة
 */
function handle_delete_service() {
    global $db;
    
    // التحقق من وجود معرف الخدمة
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('services_management.php');
    }
    
    $service_id = (int)$_GET['id'];
    
    $service_id = (int)$_GET['id']; // Using 'id'

    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id); // Uses 'id'
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // حذف الخدمة using the refactored function
    $result = delete_service($service_id); // Uses 'id'
    
    if ($result) {
        // No image or SEO deletion logic here as it's removed from delete_service
        redirect('services_management.php?success=deleted');
    } else {
        redirect('services_management.php?error=delete_failed');
    }
}

/*
 * إدارة صور الخدمة - This function is now removed/commented out
function handle_manage_service_images() {
    // ... entire function content removed ...
}
*/

/*
 * إعدادات SEO للخدمة - This function is now removed/commented out
function handle_service_seo_settings() {
    // ... entire function content removed ...
}
*/
// Make sure the function definition for handle_service_seo_settings is uncommented and refactored

/**
 * إعدادات SEO للخدمة
 */
function handle_service_seo_settings() {
    global $db, $page_title;

    // التحقق من وجود معرف الخدمة
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('services_management.php');
    }

    $service_id = (int)$_GET['id'];

    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id); // Already updated to use id

    if (!$service) {
        redirect('services_management.php');
    }

    // Determine page_name for SEO settings
    $page_name = 'service_' . $service_id;

    // الحصول على إعدادات SEO للخدمة
    $seo_settings = get_seo_settings_by_page_name($page_name); // Use new function

    // تعيين عنوان الصفحة
    $page_title = 'إعدادات SEO للخدمة: ' . htmlspecialchars($service['name']);

    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Check
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            $errors[] = "خطأ في التحقق (CSRF).";
        } else {
            // التحقق من البيانات
            $meta_title = clean_input($_POST['meta_title'] ?? '');
            $meta_description = clean_input($_POST['meta_description'] ?? '');
            $meta_keywords = clean_input($_POST['meta_keywords'] ?? ''); // Changed from keywords

            $errors = []; // Initialize errors array

            // Default meta title and description if empty
            if (empty($meta_title)) {
                $meta_title = $service['name'];
            }
            if (empty($meta_description) && !empty($service['description'])) {
                 // Use a truncated version of the main description if meta_description is empty
                $meta_description = truncate_text(strip_tags($service['description']), 160);
            }


            if (empty($errors)) {
                $seo_data_to_save = [
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'meta_keywords' => $meta_keywords // Changed from keywords
                ];

                if (save_seo_settings($page_name, $seo_data_to_save)) { // Use new function
                    redirect('services_management.php?action=seo_settings&id=' . $service_id . '&success=1');
                } else {
                    $errors[] = 'حدث خطأ أثناء حفظ إعدادات SEO.';
                }
            }
        }
    }

    // تضمين رأس الصفحة
    include 'includes/header.php';
    ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="services_management.php">إدارة الخدمات</a></li>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="container-fluid">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">تم حفظ إعدادات SEO بنجاح.</div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">إعدادات SEO</h3>
                    </div>
                    <div class="card-body">
                        <form action="services_management.php?action=seo_settings&id=<?php echo $service_id; ?>" method="post">
                            <?php echo csrf_input_field(); ?>
                            <div class="form-group">
                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $service['name']); ?>">
                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً. سيتم استخدام اسم الخدمة كافتراضي.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? truncate_text(strip_tags($service['description'] ?? ''), 160)); ?></textarea>
                                <small class="form-text text-muted">يفضل ألا يتجاوز 160 حرفاً. سيتم استخدام جزء من وصف الخدمة كافتراضي.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_keywords">الكلمات المفتاحية (Meta Keywords)</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($seo_settings['meta_keywords'] ?? ''); ?>">
                                <small class="form-text text-muted">افصل بين الكلمات المفتاحية بفواصل.</small>
                            </div>
                            
                            <div class="form-group">
                                <label>معاينة نتائج البحث (تقريبية)</label>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <h5 class="text-primary mb-1" id="preview_title"><?php echo htmlspecialchars($seo_settings['meta_title'] ?? $service['name']); ?></h5>
                                        <div class="text-success small mb-1"><?php echo SITE_URL . '/service/' . $service_id; ?></div> {/* Simplified URL */}
                                        <p class="mb-0" id="preview_description"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? truncate_text(strip_tags($service['description'] ?? ''), 160)); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            <a href="services_management.php" class="btn btn-secondary">إلغاء</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const metaTitleInput = document.getElementById('meta_title');
        const metaDescriptionInput = document.getElementById('meta_description');
        const previewTitle = document.getElementById('preview_title');
        const previewDescription = document.getElementById('preview_description');

        if(metaTitleInput && previewTitle) {
            metaTitleInput.addEventListener('input', function() {
                previewTitle.textContent = this.value || '<?php echo htmlspecialchars(addslashes($service['name'])); ?>';
            });
        }

        if(metaDescriptionInput && previewDescription) {
            metaDescriptionInput.addEventListener('input', function() {
                previewDescription.textContent = this.value || '<?php echo htmlspecialchars(addslashes(truncate_text(strip_tags($service['description'] ?? ''), 160))); ?>';
            });
        }
    });
    </script>
    
    <?php
    // تضمين ذيل الصفحة
    include 'includes/footer.php';
