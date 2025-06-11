<?php
/**
 * ملف تنفيذ عمليات الإضافة والتعديل والحذف المتقدمة للمشاريع
 * 
 * هذا الملف يحتوي على وظائف متقدمة لإدارة المشاريع في لوحة التحكم
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
    require_once PROJECT_ROOT . '/includes/functions/project_functions.php';
} else {
    // Fallback or error if PROJECT_ROOT is not defined
    // This part will likely cause issues if PROJECT_ROOT isn't defined,
    // as FUNCTIONS_DIR would not be defined either.
    trigger_error("PROJECT_ROOT not defined, cannot include function files for projects_management.php", E_USER_WARNING);
}


// التحقق من تسجيل دخول المدير
// is_admin_logged_in() is expected to be available via admin/init.php (from includes/functions.php)
// or one of the includes above.
if (!is_admin_logged_in()) {
    redirect('login_form.php'); // redirect() also needs to be available.
}

// تعيين عنوان الصفحة
$page_title = 'إدارة المشاريع';

// تحديد العملية المطلوبة
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// معالجة العمليات
switch ($action) {
    case 'add':
        // إضافة مشروع جديد
        handle_add_project();
        break;
        
    case 'edit':
        // تعديل مشروع موجود
        handle_edit_project();
        break;
        
    case 'delete':
        // حذف مشروع
        handle_delete_project();
        break;
        
    // case 'manage_images':
    //     // إدارة صور المشروع
    //     // handle_manage_project_images(); // Functionality to be removed/revisited later
    //     break;
        
    case 'seo_settings':
        // إعدادات SEO للمشروع
        handle_project_seo_settings();
        break;
        
    default:
        // عرض قائمة المشاريع
        handle_list_projects();
        break;
}

/**
 * عرض قائمة المشاريع
 */
function handle_list_projects() {
    global $db, $page_title;
    
    // الحصول على جميع المشاريع
    $projects = get_all_projects();
    
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
                        <h3 class="card-title">قائمة المشاريع</h3>
                        <div class="card-tools">
                            <a href="projects_management.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> إضافة مشروع جديد
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($projects)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الصورة</th>
                                            <th>العنوان</th>
                                            <th>رابط المشروع</th>
                                            <th>الوصف</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                            <tr>
                                                <td><?php echo $project['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($project['image_url'])): ?>
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-thumbnail" width="50">
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">لا توجد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                                <td>
                                                    <?php if (!empty($project['project_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($project['project_url']); ?></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">لا يوجد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo truncate_text(htmlspecialchars($project['description']), 100); ?></td>
                                                <td><?php echo format_date($project['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="projects_management.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="projects_management.php?action=seo_settings&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary" title="إعدادات SEO">
                                                            <i class="fas fa-search"></i>
                                                        </a>
                                                        <a href="projects_management.php?action=delete&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger delete-confirm" title="حذف" data-confirm-message="هل أنت متأكد من رغبتك في حذف هذا المشروع؟">
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
                                لا توجد مشاريع متاحة حالياً. <a href="projects_management.php?action=add">إضافة مشروع جديد</a>
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
 * إضافة مشروع جديد
 */
function handle_add_project() {
    global $db, $page_title;
    
    // تعيين عنوان الصفحة
    $page_title = 'إضافة مشروع جديد';
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = clean_input($_POST['title'] ?? '');
        $description = $_POST['description'] ?? ''; // Keep as HTML if using rich editor
        $project_url = clean_input($_POST['project_url'] ?? '');
        $image_url = null;

        $errors = [];
        if (empty($title)) {
            $errors[] = 'عنوان المشروع مطلوب';
        }
        // Add other validations as needed for description, project_url

        if (empty($errors)) {
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $uploaded_path = upload_single_image($_FILES['main_image'], 'projects', 'project');
                if ($uploaded_path) {
                    $image_url = $uploaded_path;
                } else {
                    // Handle upload error - perhaps add to $errors
                    $errors[] = 'فشل رفع الصورة.';
                }
            }

            if(empty($errors)) { // Check errors again after potential image upload failure
                $project_data = [
                    'title' => $title,
                    'description' => $description,
                    'project_url' => $project_url,
                    'image_url' => $image_url,
                ];

                $project_id = add_project($project_data); // Use refactored function

                if ($project_id) {
                    // No SEO settings or image management redirection for now
                    redirect('projects_management.php?success=added');
                } else {
                    $errors[] = 'حدث خطأ أثناء إضافة المشروع';
                    if ($image_url) { // If project add failed, delete the uploaded image
                        delete_image($image_url);
                    }
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
                            <li class="breadcrumb-item"><a href="projects_management.php">إدارة المشاريع</a></li>
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
                        <h3 class="card-title">بيانات المشروع</h3>
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
                        
                        <form action="projects_management.php?action=add" method="post" enctype="multipart/form-data">
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
                                                <label for="title">عنوان المشروع <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="project_url">رابط المشروع</label>
                                                <input type="url" class="form-control" id="project_url" name="project_url" placeholder="https://example.com" value="<?php echo isset($_POST['project_url']) ? htmlspecialchars($_POST['project_url']) : ''; ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي</label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                            </div>
                                             <div class="form-group">
                                                <label for="main_image">صورة المشروع</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="main_image" name="main_image" accept="image/*">
                                                    <label class="custom-file-label" for="main_image">اختر صورة</label>
                                                </div>
                                                <small class="form-text text-muted">الحد الأقصى لحجم الصورة: 2 ميجابايت</small>
                                            </div>
                                        </div>
                                    </div>
                                    {/* SEO and other settings removed */}
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">حفظ</button>
                                    <a href="projects_management.php" class="btn btn-secondary">إلغاء</a>
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
 * تعديل مشروع موجود
 */
function handle_edit_project() {
    global $db, $page_title;
    
    // التحقق من وجود معرف المشروع
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('projects_management.php');
    }
    
    $project_id = (int)$_GET['id']; // Use 'id'
    
    $project = get_project_by_id($project_id); // Uses new function
    if (!$project) {
        redirect('projects_management.php');
    }
    
    $page_title = 'تعديل مشروع: ' . htmlspecialchars($project['title']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = clean_input($_POST['title'] ?? '');
        $description = $_POST['description'] ?? '';
        $project_url = clean_input($_POST['project_url'] ?? '');
        $image_url = $project['image_url']; // Keep old image by default

        $errors = [];
        if (empty($title)) {
            $errors[] = 'عنوان المشروع مطلوب';
        }

        if (empty($errors)) {
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $new_image_path = upload_single_image($_FILES['main_image'], 'projects', 'project');
                if ($new_image_path) {
                    if (!empty($image_url)) { // If old image exists, delete it
                        delete_image($image_url);
                    }
                    $image_url = $new_image_path; // Set new image path
                } else {
                    $errors[] = 'فشل رفع الصورة الجديدة.';
                }
            }

            if(empty($errors)) {
                $project_data = [
                    'title' => $title,
                    'description' => $description,
                    'project_url' => $project_url,
                    'image_url' => $image_url, // This will be new or old path
                ];

                $result = update_project($project_id, $project_data); // Use refactored function

                if ($result) {
                    redirect('projects_management.php?success=updated');
                } else {
                    $errors[] = 'حدث خطأ أثناء تحديث المشروع';
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
                            <li class="breadcrumb-item"><a href="projects_management.php">إدارة المشاريع</a></li>
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
                        <h3 class="card-title">بيانات المشروع</h3>
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
                        
                        <form action="projects_management.php?action=edit&id=<?php echo $project_id; ?>" method="post" enctype="multipart/form-data">
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
                                                <label for="title">عنوان المشروع <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="project_url">رابط المشروع</label>
                                                <input type="url" class="form-control" id="project_url" name="project_url" value="<?php echo htmlspecialchars($project['project_url'] ?? ''); ?>" placeholder="https://example.com">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي</label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10"><?php echo htmlspecialchars($project['description']); ?></textarea>
                                            </div>
                                             <div class="form-group">
                                                <label for="main_image">صورة المشروع</label>
                                                <?php if (!empty($project['image_url'])): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-thumbnail" style="max-height: 150px;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="main_image" name="main_image" accept="image/*">
                                                    <label class="custom-file-label" for="main_image">اختر صورة جديدة لتغيير الحالية</label>
                                                </div>
                                                <small class="form-text text-muted">الحد الأقصى لحجم الصورة: 2 ميجابايت</small>
                                            </div>
                                        </div>
                                    </div>
                                    {/* SEO and other settings removed */}
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                    <a href="projects_management.php" class="btn btn-secondary">إلغاء</a>
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
 * حذف مشروع
 */
function handle_delete_project() {
    global $db;
    
    // التحقق من وجود معرف المشروع
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('projects_management.php');
    }
    
    $project_id = (int)$_GET['id'];
    
    $project_id = (int)$_GET['id']; // Use 'id'

    // الحصول على بيانات المشروع
    $project = get_project_by_id($project_id); // Uses new function
    
    if (!$project) {
        redirect('projects_management.php');
    }
    
    // Delete the main image file if it exists
    if (!empty($project['image_url'])) {
        delete_image($project['image_url']); // Assumes delete_image can handle paths like 'projects/filename.jpg'
    }

    // حذف المشروع using the refactored function
    $result = delete_project($project_id); // Uses 'id'
    
    if ($result) {
        redirect('projects_management.php?success=deleted');
    } else {
        redirect('projects_management.php?error=delete_failed');
    }
}

/*
 * إدارة صور المشروع - This function is now commented out
function handle_manage_project_images() {
    // ... entire function content removed ...
}
*/

/*
 * إعدادات SEO للمشروع - This function is now commented out
function handle_project_seo_settings() {
    // ... entire function content removed ...
}
*/

/**
 * إعدادات SEO للمشروع
 */
function handle_project_seo_settings() {
    global $db, $page_title;

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('projects_management.php');
    }

    $project_id = (int)$_GET['id'];
    $project = get_project_by_id($project_id); // Already updated to use id

    if (!$project) {
        redirect('projects_management.php');
    }

    $page_name = 'project_' . $project_id;
    $seo_settings = get_seo_settings_by_page_name($page_name); // Use new function

    $page_title = 'إعدادات SEO للمشروع: ' . htmlspecialchars($project['title']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            $errors[] = "خطأ في التحقق (CSRF).";
        } else {
            $meta_title = clean_input($_POST['meta_title'] ?? '');
            $meta_description = clean_input($_POST['meta_description'] ?? '');
            $meta_keywords = clean_input($_POST['meta_keywords'] ?? '');

            $errors = []; // Initialize errors

            if (empty($meta_title)) {
                $meta_title = $project['title'];
            }
            if (empty($meta_description) && !empty($project['description'])) {
                $meta_description = truncate_text(strip_tags($project['description']), 160);
            }

            if (empty($errors)) {
                $seo_data_to_save = [
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'meta_keywords' => $meta_keywords
                ];

                if (save_seo_settings($page_name, $seo_data_to_save)) { // Use new function
                    redirect('projects_management.php?action=seo_settings&id=' . $project_id . '&success=1');
                } else {
                    $errors[] = 'حدث خطأ أثناء حفظ إعدادات SEO.';
                }
            }
        }
    }

    include 'includes/header.php';
    ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1 class="m-0"><?php echo $page_title; ?></h1></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="projects_management.php">إدارة المشاريع</a></li>
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
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">إعدادات SEO</h3></div>
                    <div class="card-body">
                        <form action="projects_management.php?action=seo_settings&id=<?php echo $project_id; ?>" method="post">
                            <?php echo csrf_input_field(); ?>
                            <div class="form-group">
                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $project['title']); ?>">
                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً. سيتم استخدام عنوان المشروع كافتراضي.</small>
                            </div>
                            <div class="form-group">
                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? truncate_text(strip_tags($project['description'] ?? ''), 160)); ?></textarea>
                                <small class="form-text text-muted">يفضل ألا يتجاوز 160 حرفاً. سيتم استخدام جزء من وصف المشروع كافتراضي.</small>
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
                                        <h5 class="text-primary mb-1" id="preview_title"><?php echo htmlspecialchars($seo_settings['meta_title'] ?? $project['title']); ?></h5>
                                        <div class="text-success small mb-1"><?php echo SITE_URL . '/project/' . $project_id; ?></div> {/* Simplified URL */}
                                        <p class="mb-0" id="preview_description"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? truncate_text(strip_tags($project['description'] ?? ''), 160)); ?></p>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            <a href="projects_management.php" class="btn btn-secondary">إلغاء</a>
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
                previewTitle.textContent = this.value || '<?php echo htmlspecialchars(addslashes($project['title'])); ?>';
            });
        }

        if(metaDescriptionInput && previewDescription) {
            metaDescriptionInput.addEventListener('input', function() {
                previewDescription.textContent = this.value || '<?php echo htmlspecialchars(addslashes(truncate_text(strip_tags($project['description'] ?? ''), 160))); ?>';
            });
        }
    });
    </script>
    <?php
    include 'includes/footer.php';
