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
        
    case 'manage_images':
        // إدارة صور المشروع
        handle_manage_project_images();
        break;
        
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
                                            <th>التصنيف</th>
                                            <th>تاريخ الإنجاز</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $index => $project): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <?php if (!empty($project['main_image'])): ?>
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['main_image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-thumbnail" width="50">
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">لا توجد صورة</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                                <td><?php echo htmlspecialchars($project['category']); ?></td>
                                                <td><?php echo format_date($project['completion_date']); ?></td>
                                                <td>
                                                    <?php if ($project['is_active']): ?>
                                                        <span class="badge bg-success">نشط</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">غير نشط</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo format_date($project['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="projects_management.php?action=edit&id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-info" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="projects_management.php?action=manage_images&id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-success" title="إدارة الصور">
                                                            <i class="fas fa-images"></i>
                                                        </a>
                                                        <a href="projects_management.php?action=seo_settings&id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary" title="إعدادات SEO">
                                                            <i class="fas fa-search"></i>
                                                        </a>
                                                        <a href="projects_management.php?action=delete&id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-danger delete-confirm" title="حذف" data-confirm-message="هل أنت متأكد من رغبتك في حذف هذا المشروع؟">
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
        // التحقق من البيانات
        $title = clean_input($_POST['title'] ?? '');
        $short_description = clean_input($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $category = clean_input($_POST['category'] ?? '');
        $client = clean_input($_POST['client'] ?? '');
        $completion_date = clean_input($_POST['completion_date'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = clean_input($_POST['slug'] ?? '');
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'عنوان المشروع مطلوب';
        }
        
        if (empty($short_description)) {
            $errors[] = 'الوصف المختصر مطلوب';
        }
        
        if (empty($description)) {
            $errors[] = 'الوصف التفصيلي مطلوب';
        }
        
        // إنشاء slug إذا لم يتم توفيره
        if (empty($slug)) {
            $slug = generate_slug($title);
        } else {
            $slug = generate_slug($slug);
        }
        
        // التحقق من تفرد slug
        $slug = ensure_unique_slug($slug, 'projects', 'project_id');
        
        // إذا لم تكن هناك أخطاء، أضف المشروع
        if (empty($errors)) {
            // تحضير بيانات المشروع
            $project_data = [
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'category' => $category,
                'client' => $client,
                'completion_date' => $completion_date,
                'is_active' => $is_active,
                'is_featured' => $is_featured,
                'slug' => $slug,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // رفع الصورة الرئيسية إذا تم توفيرها
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = upload_single_image($_FILES['main_image'], 'projects', 'project');
                
                if ($image_path) {
                    $project_data['main_image'] = $image_path;
                }
            }
            
            // إدراج المشروع في قاعدة البيانات
            $project_id = db_insert('projects', $project_data);
            
            if ($project_id) {
                // إضافة إعدادات SEO
                $seo_data = [
                    'entity_type' => 'project',
                    'entity_id' => $project_id,
                    'meta_title' => clean_input($_POST['meta_title'] ?? $title),
                    'meta_description' => clean_input($_POST['meta_description'] ?? $short_description),
                    'keywords' => clean_input($_POST['keywords'] ?? ''),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // db_insert('seo_settings', $seo_data);
                $columns_seo = implode(', ', array_keys($seo_data));
                $placeholders_seo = ':' . implode(', :', array_keys($seo_data));
                $sql_seo_insert = "INSERT INTO seo_settings ($columns_seo) VALUES ($placeholders_seo)";
                $db->execute($sql_seo_insert, $seo_data);
                
                // إعادة التوجيه إلى صفحة إدارة الصور
                redirect('projects_management.php?action=manage_images&id=' . $project_id . '&success=1');
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة المشروع';
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
                                            <div class="form-group">
                                                <label for="title">عنوان المشروع <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="slug">الرابط المخصص</label>
                                                <input type="text" class="form-control" id="slug" name="slug" placeholder="سيتم إنشاؤه تلقائياً إذا تركته فارغاً">
                                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للمشروع</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="short_description">الوصف المختصر <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="3" required></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي <span class="text-danger">*</span></label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10" required></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="category">تصنيف المشروع</label>
                                                        <input type="text" class="form-control" id="category" name="category">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="client">العميل</label>
                                                        <input type="text" class="form-control" id="client" name="client">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="completion_date">تاريخ الإنجاز</label>
                                                <input type="date" class="form-control" id="completion_date" name="completion_date">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- إعدادات SEO -->
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">إعدادات SEO</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title">
                                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"></textarea>
                                                <small class="form-text text-muted">يفضل ألا يتجاوز 160 حرفاً</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="keywords">الكلمات المفتاحية (Keywords)</label>
                                                <input type="text" class="form-control" id="keywords" name="keywords">
                                                <small class="form-text text-muted">افصل بين الكلمات المفتاحية بفواصل</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <!-- الصورة والإعدادات -->
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">الصورة والإعدادات</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="main_image">الصورة الرئيسية</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="main_image" name="main_image" accept="image/*">
                                                    <label class="custom-file-label" for="main_image">اختر صورة</label>
                                                </div>
                                                <small class="form-text text-muted">الحد الأقصى لحجم الصورة: 2 ميجابايت</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                                    <label class="custom-control-label" for="is_active">نشط</label>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured">
                                                    <label class="custom-control-label" for="is_featured">مميز</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
    
    $project_id = (int)$_GET['id'];
    
    // الحصول على بيانات المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        redirect('projects_management.php');
    }
    
    // الحصول على إعدادات SEO للمشروع
    $seo_settings = get_seo_settings('project', $project_id);
    
    // تعيين عنوان الصفحة
    $page_title = 'تعديل مشروع: ' . $project['title'];
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من البيانات
        $title = clean_input($_POST['title'] ?? '');
        $short_description = clean_input($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $category = clean_input($_POST['category'] ?? '');
        $client = clean_input($_POST['client'] ?? '');
        $completion_date = clean_input($_POST['completion_date'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = clean_input($_POST['slug'] ?? '');
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'عنوان المشروع مطلوب';
        }
        
        if (empty($short_description)) {
            $errors[] = 'الوصف المختصر مطلوب';
        }
        
        if (empty($description)) {
            $errors[] = 'الوصف التفصيلي مطلوب';
        }
        
        // إنشاء slug إذا لم يتم توفيره
        if (empty($slug)) {
            $slug = generate_slug($title);
        } else {
            $slug = generate_slug($slug);
        }
        
        // التحقق من تفرد slug
        $slug = ensure_unique_slug($slug, 'projects', 'project_id', $project_id);
        
        // إذا لم تكن هناك أخطاء، حدث المشروع
        if (empty($errors)) {
            // تحضير بيانات المشروع
            $project_data = [
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'category' => $category,
                'client' => $client,
                'completion_date' => $completion_date,
                'is_active' => $is_active,
                'is_featured' => $is_featured,
                'slug' => $slug,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // رفع الصورة الرئيسية إذا تم توفيرها
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = upload_single_image($_FILES['main_image'], 'projects', 'project');
                
                if ($image_path) {
                    // حذف الصورة القديمة إذا كانت موجودة
                    if (!empty($project['main_image'])) {
                        delete_image($project['main_image']);
                    }
                    
                    $project_data['main_image'] = $image_path;
                }
            }
            
            // تحديث المشروع في قاعدة البيانات
            $result = db_update('projects', $project_data, 'project_id = ?', [$project_id]);
            
            if ($result) {
                // تحديث إعدادات SEO
                $seo_data = [
                    'meta_title' => clean_input($_POST['meta_title'] ?? $title),
                    'meta_description' => clean_input($_POST['meta_description'] ?? $short_description),
                    'keywords' => clean_input($_POST['keywords'] ?? ''),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($seo_settings) {
                    // تحديث إعدادات SEO الموجودة
                    // db_update('seo_settings', $seo_data, 'entity_type = ? AND entity_id = ?', ['project', $project_id]);
                    $seo_update_clauses = [];
                    foreach (array_keys($seo_data) as $key) {
                        $seo_update_clauses[] = "$key = :$key";
                    }
                    $sql_seo_update = "UPDATE seo_settings SET " . implode(', ', $seo_update_clauses) . " WHERE entity_type = :entity_type_condition AND entity_id = :entity_id_condition";
                    $seo_data_for_execute = $seo_data;
                    $seo_data_for_execute['entity_type_condition'] = 'project';
                    $seo_data_for_execute['entity_id_condition'] = $project_id;
                    $db->execute($sql_seo_update, $seo_data_for_execute);
                } else {
                    // إضافة إعدادات SEO جديدة
                    $seo_data['entity_type'] = 'project';
                    $seo_data['entity_id'] = $project_id;
                    $seo_data['created_at'] = date('Y-m-d H:i:s');
                    
                    // db_insert('seo_settings', $seo_data);
                    $columns_seo = implode(', ', array_keys($seo_data));
                    $placeholders_seo = ':' . implode(', :', array_keys($seo_data));
                    $sql_seo_insert = "INSERT INTO seo_settings ($columns_seo) VALUES ($placeholders_seo)";
                    $db->execute($sql_seo_insert, $seo_data);
                }
                
                // إعادة التوجيه مع رسالة نجاح
                redirect('projects_management.php?success=1');
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث المشروع';
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
                                            <div class="form-group">
                                                <label for="title">عنوان المشروع <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="slug">الرابط المخصص</label>
                                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($project['slug']); ?>">
                                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للمشروع</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="short_description">الوصف المختصر <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="3" required><?php echo htmlspecialchars($project['short_description']); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي <span class="text-danger">*</span></label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="category">تصنيف المشروع</label>
                                                        <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($project['category']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="client">العميل</label>
                                                        <input type="text" class="form-control" id="client" name="client" value="<?php echo htmlspecialchars($project['client']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="completion_date">تاريخ الإنجاز</label>
                                                <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo htmlspecialchars($project['completion_date']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- إعدادات SEO -->
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">إعدادات SEO</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $project['title']); ?>">
                                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $project['short_description']); ?></textarea>
                                                <small class="form-text text-muted">يفضل ألا يتجاوز 160 حرفاً</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="keywords">الكلمات المفتاحية (Keywords)</label>
                                                <input type="text" class="form-control" id="keywords" name="keywords" value="<?php echo htmlspecialchars($seo_settings['keywords'] ?? ''); ?>">
                                                <small class="form-text text-muted">افصل بين الكلمات المفتاحية بفواصل</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <!-- الصورة والإعدادات -->
                                    <div class="card card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">الصورة والإعدادات</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="main_image">الصورة الرئيسية</label>
                                                <?php if (!empty($project['main_image'])): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($project['main_image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-thumbnail" style="max-height: 200px;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="main_image" name="main_image" accept="image/*">
                                                    <label class="custom-file-label" for="main_image">اختر صورة</label>
                                                </div>
                                                <small class="form-text text-muted">الحد الأقصى لحجم الصورة: 2 ميجابايت</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $project['is_active'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="is_active">نشط</label>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" <?php echo $project['is_featured'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="is_featured">مميز</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
    
    // الحصول على بيانات المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        redirect('projects_management.php');
    }
    
    // حذف المشروع
    $result = db_delete('projects', 'project_id = ?', [$project_id]);
    
    if ($result) {
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
        db_delete('project_images', 'project_id = ?', [$project_id]);
        
        // حذف إعدادات SEO
        db_delete('seo_settings', 'entity_type = ? AND entity_id = ?', ['project', $project_id]);
        
        // إعادة التوجيه مع رسالة نجاح
        redirect('projects_management.php?deleted=1');
    } else {
        // إعادة التوجيه مع رسالة خطأ
        redirect('projects_management.php?error=1');
    }
}

/**
 * إدارة صور المشروع
 */
function handle_manage_project_images() {
    global $db, $page_title;
    
    // التحقق من وجود معرف المشروع
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('projects_management.php');
    }
    
    $project_id = (int)$_GET['id'];
    
    // الحصول على بيانات المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        redirect('projects_management.php');
    }
    
    // تعيين عنوان الصفحة
    $page_title = 'إدارة صور المشروع: ' . $project['title'];
    
    // الحصول على صور المشروع
    $project_images = get_project_images($project_id);
    
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
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                        تم حفظ المشروع بنجاح. يمكنك الآن إضافة المزيد من الصور.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">رفع صور متعددة</h3>
                            </div>
                            <div class="card-body">
                                <div class="dropzone" id="dropzone" data-entity-type="project" data-entity-id="<?php echo $project_id; ?>"></div>
                                <div class="mt-3">
                                    <p class="text-muted">اسحب وأفلت الصور هنا أو انقر للتحميل. يمكنك تحميل صور متعددة دفعة واحدة.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">صور المشروع</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($project_images)): ?>
                                    <div class="row" id="sortable-images" data-entity-type="project">
                                        <?php foreach ($project_images as $image): ?>
                                            <div class="col-md-3 mb-4 image-item" data-id="<?php echo $image['image_id']; ?>">
                                                <div class="card">
                                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" class="card-img-top" alt="صورة المشروع">
                                                    <div class="card-body p-2">
                                                        <div class="btn-group w-100">
                                                            <button type="button" class="btn btn-sm btn-info drag-handle" title="سحب لإعادة الترتيب">
                                                                <i class="fas fa-arrows-alt"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger delete-image" title="حذف">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        لا توجد صور للمشروع حالياً. استخدم منطقة السحب والإفلات أعلاه لإضافة صور.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <a href="projects_management.php" class="btn btn-secondary">العودة إلى قائمة المشاريع</a>
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
 * إعدادات SEO للمشروع
 */
function handle_project_seo_settings() {
    global $db, $page_title;
    
    // التحقق من وجود معرف المشروع
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('projects_management.php');
    }
    
    $project_id = (int)$_GET['id'];
    
    // الحصول على بيانات المشروع
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        redirect('projects_management.php');
    }
    
    // الحصول على إعدادات SEO للمشروع
    $seo_settings = get_seo_settings('project', $project_id);
    
    // تعيين عنوان الصفحة
    $page_title = 'إعدادات SEO للمشروع: ' . $project['title'];
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من البيانات
        $meta_title = clean_input($_POST['meta_title'] ?? '');
        $meta_description = clean_input($_POST['meta_description'] ?? '');
        $keywords = clean_input($_POST['keywords'] ?? '');
        $slug = clean_input($_POST['slug'] ?? '');
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($meta_title)) {
            $meta_title = $project['title'];
        }
        
        if (empty($meta_description)) {
            $meta_description = $project['short_description'];
        }
        
        // إنشاء slug إذا لم يتم توفيره
        if (empty($slug)) {
            $slug = $project['slug'];
        } else {
            $slug = generate_slug($slug);
        }
        
        // التحقق من تفرد slug
        $slug = ensure_unique_slug($slug, 'projects', 'project_id', $project_id);
        
        // إذا لم تكن هناك أخطاء، حدث الإعدادات
        if (empty($errors)) {
            // تحديث slug في جدول المشاريع
            // db_update('projects', ['slug' => $slug], 'project_id = ?', [$project_id]);
            $db->execute("UPDATE projects SET slug = :slug WHERE project_id = :project_id", [':slug' => $slug, ':project_id' => $project_id]);
            
            // تحضير بيانات SEO
            $seo_data = [
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'keywords' => $keywords,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($seo_settings) {
                // تحديث إعدادات SEO الموجودة
                // db_update('seo_settings', $seo_data, 'entity_type = ? AND entity_id = ?', ['project', $project_id]);
                $seo_update_clauses = [];
                foreach (array_keys($seo_data) as $key) {
                    $seo_update_clauses[] = "$key = :$key";
                }
                $sql_seo_update = "UPDATE seo_settings SET " . implode(', ', $seo_update_clauses) . " WHERE entity_type = :entity_type_condition AND entity_id = :entity_id_condition";
                $seo_data_for_execute = $seo_data;
                $seo_data_for_execute['entity_type_condition'] = 'project';
                $seo_data_for_execute['entity_id_condition'] = $project_id;
                $db->execute($sql_seo_update, $seo_data_for_execute);
            } else {
                // إضافة إعدادات SEO جديدة
                $seo_data['entity_type'] = 'project';
                $seo_data['entity_id'] = $project_id;
                $seo_data['created_at'] = date('Y-m-d H:i:s');
                
                // db_insert('seo_settings', $seo_data);
                $columns_seo = implode(', ', array_keys($seo_data));
                $placeholders_seo = ':' . implode(', :', array_keys($seo_data));
                $sql_seo_insert = "INSERT INTO seo_settings ($columns_seo) VALUES ($placeholders_seo)";
                $db->execute($sql_seo_insert, $seo_data);
            }
            
            // إعادة التوجيه مع رسالة نجاح
            redirect('projects_management.php?seo_updated=1');
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
                        <h3 class="card-title">إعدادات SEO</h3>
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
                        
                        <form action="projects_management.php?action=seo_settings&id=<?php echo $project_id; ?>" method="post">
                            <div class="form-group">
                                <label for="slug">الرابط المخصص (Slug)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?php echo SITE_URL; ?>/project-details.php?slug=</span>
                                    </div>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($project['slug']); ?>">
                                </div>
                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للمشروع</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $project['title']); ?>">
                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $project['short_description']); ?></textarea>
                                <small class="form-text text-muted">يفضل ألا يتجاوز 160 حرفاً</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="keywords">الكلمات المفتاحية (Keywords)</label>
                                <input type="text" class="form-control" id="keywords" name="keywords" value="<?php echo htmlspecialchars($seo_settings['keywords'] ?? ''); ?>">
                                <small class="form-text text-muted">افصل بين الكلمات المفتاحية بفواصل</small>
                            </div>
                            
                            <div class="form-group">
                                <label>معاينة نتائج البحث</label>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <h5 class="text-primary mb-1" id="preview_title"><?php echo htmlspecialchars($seo_settings['meta_title'] ?? $project['title']); ?></h5>
                                        <div class="text-success small mb-1"><?php echo SITE_URL; ?>/project-details.php?slug=<?php echo htmlspecialchars($project['slug']); ?></div>
                                        <p class="mb-0" id="preview_description"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $project['short_description']); ?></p>
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
    $(document).ready(function() {
        // تحديث المعاينة عند تغيير العنوان
        $('#meta_title').on('input', function() {
            $('#preview_title').text($(this).val());
        });
        
        // تحديث المعاينة عند تغيير الوصف
        $('#meta_description').on('input', function() {
            $('#preview_description').text($(this).val());
        });
    });
    </script>
    
    <?php
    // تضمين ذيل الصفحة
    include 'includes/footer.php';
}
