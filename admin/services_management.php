<?php
/**
 * ملف تنفيذ عمليات الإضافة والتعديل والحذف المتقدمة للخدمات
 * 
 * هذا الملف يحتوي على وظائف متقدمة لإدارة الخدمات في لوحة التحكم
 * مع دعم رفع الصور المتعددة وإعدادات SEO
 */

// تضمين ملف التهيئة
require_once '../includes/init.php';

// تضمين ملفات الوظائف اللازمة
require_once FUNCTIONS_DIR . '/admin_images_functions.php';
require_once FUNCTIONS_DIR . '/admin_seo_functions.php';
require_once FUNCTIONS_DIR . '/service_functions.php';

// التحقق من تسجيل دخول المدير
if (!is_admin_logged_in()) {
    redirect('login_form.php');
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
        
    case 'manage_images':
        // إدارة صور الخدمة
        handle_manage_service_images();
        break;
        
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
                                            <th>الصورة</th>
                                            <th>العنوان</th>
                                            <th>الوصف المختصر</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $index => $service): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <?php if (!empty($service['image'])): ?>
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="img-thumbnail" width="50">
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">لا توجد صورة</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($service['title']); ?></td>
                                                <td><?php echo truncate_text($service['short_description'], 100); ?></td>
                                                <td>
                                                    <?php if ($service['is_active']): ?>
                                                        <span class="badge bg-success">نشط</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">غير نشط</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo format_date($service['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="services_management.php?action=edit&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-info" title="تعديل">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="services_management.php?action=manage_images&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-success" title="إدارة الصور">
                                                            <i class="fas fa-images"></i>
                                                        </a>
                                                        <a href="services_management.php?action=seo_settings&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-primary" title="إعدادات SEO">
                                                            <i class="fas fa-search"></i>
                                                        </a>
                                                        <a href="services_management.php?action=delete&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-danger delete-confirm" title="حذف" data-confirm-message="هل أنت متأكد من رغبتك في حذف هذه الخدمة؟">
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
        $title = clean_input($_POST['title'] ?? '');
        $short_description = clean_input($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $icon = clean_input($_POST['icon'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = clean_input($_POST['slug'] ?? '');
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'عنوان الخدمة مطلوب';
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
        $slug = ensure_unique_slug($slug, 'services', 'service_id');
        
        // إذا لم تكن هناك أخطاء، أضف الخدمة
        if (empty($errors)) {
            // تحضير بيانات الخدمة
            $service_data = [
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'icon' => $icon,
                'is_active' => $is_active,
                'is_featured' => $is_featured,
                'slug' => $slug,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // رفع الصورة الرئيسية إذا تم توفيرها
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_path = upload_single_image($_FILES['image'], 'services', 'service');
                
                if ($image_path) {
                    $service_data['image'] = $image_path;
                }
            }
            
            // إدراج الخدمة في قاعدة البيانات
            $service_id = db_insert('services', $service_data);
            
            if ($service_id) {
                // إضافة إعدادات SEO
                $seo_data = [
                    'entity_type' => 'service',
                    'entity_id' => $service_id,
                    'meta_title' => clean_input($_POST['meta_title'] ?? $title),
                    'meta_description' => clean_input($_POST['meta_description'] ?? $short_description),
                    'keywords' => clean_input($_POST['keywords'] ?? ''),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                db_insert('seo_settings', $seo_data);
                
                // إعادة التوجيه إلى صفحة إدارة الصور
                redirect('services_management.php?action=manage_images&id=' . $service_id . '&success=1');
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
                                            <div class="form-group">
                                                <label for="title">عنوان الخدمة <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="slug">الرابط المخصص</label>
                                                <input type="text" class="form-control" id="slug" name="slug" placeholder="سيتم إنشاؤه تلقائياً إذا تركته فارغاً">
                                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للخدمة</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="short_description">الوصف المختصر <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="3" required></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي <span class="text-danger">*</span></label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10" required></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="icon">أيقونة الخدمة</label>
                                                <input type="text" class="form-control" id="icon" name="icon" placeholder="مثال: settings">
                                                <small class="form-text text-muted">اسم الأيقونة من مكتبة Feather Icons</small>
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
                                                <label for="image">الصورة الرئيسية</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                                    <label class="custom-file-label" for="image">اختر صورة</label>
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
    
    $service_id = (int)$_GET['id'];
    
    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // الحصول على إعدادات SEO للخدمة
    $seo_settings = get_seo_settings('service', $service_id);
    
    // تعيين عنوان الصفحة
    $page_title = 'تعديل خدمة: ' . $service['title'];
    
    // معالجة النموذج عند الإرسال
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من البيانات
        $title = clean_input($_POST['title'] ?? '');
        $short_description = clean_input($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $icon = clean_input($_POST['icon'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = clean_input($_POST['slug'] ?? '');
        
        // التحقق من الحقول المطلوبة
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'عنوان الخدمة مطلوب';
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
        $slug = ensure_unique_slug($slug, 'services', 'service_id', $service_id);
        
        // إذا لم تكن هناك أخطاء، حدث الخدمة
        if (empty($errors)) {
            // تحضير بيانات الخدمة
            $service_data = [
                'title' => $title,
                'short_description' => $short_description,
                'description' => $description,
                'icon' => $icon,
                'is_active' => $is_active,
                'is_featured' => $is_featured,
                'slug' => $slug,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // رفع الصورة الرئيسية إذا تم توفيرها
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_path = upload_single_image($_FILES['image'], 'services', 'service');
                
                if ($image_path) {
                    // حذف الصورة القديمة إذا كانت موجودة
                    if (!empty($service['image'])) {
                        delete_image($service['image']);
                    }
                    
                    $service_data['image'] = $image_path;
                }
            }
            
            // تحديث الخدمة في قاعدة البيانات
            $result = db_update('services', $service_data, 'service_id = ?', [$service_id]);
            
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
                    db_update('seo_settings', $seo_data, 'entity_type = ? AND entity_id = ?', ['service', $service_id]);
                } else {
                    // إضافة إعدادات SEO جديدة
                    $seo_data['entity_type'] = 'service';
                    $seo_data['entity_id'] = $service_id;
                    $seo_data['created_at'] = date('Y-m-d H:i:s');
                    
                    db_insert('seo_settings', $seo_data);
                }
                
                // إعادة التوجيه مع رسالة نجاح
                redirect('services_management.php?success=1');
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
                                            <div class="form-group">
                                                <label for="title">عنوان الخدمة <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($service['title']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="slug">الرابط المخصص</label>
                                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($service['slug']); ?>">
                                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للخدمة</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="short_description">الوصف المختصر <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="3" required><?php echo htmlspecialchars($service['short_description']); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description">الوصف التفصيلي <span class="text-danger">*</span></label>
                                                <textarea class="form-control rich-editor" id="description" name="description" rows="10" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="icon">أيقونة الخدمة</label>
                                                <input type="text" class="form-control" id="icon" name="icon" value="<?php echo htmlspecialchars($service['icon']); ?>" placeholder="مثال: settings">
                                                <small class="form-text text-muted">اسم الأيقونة من مكتبة Feather Icons</small>
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
                                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $service['title']); ?>">
                                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $service['short_description']); ?></textarea>
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
                                                <label for="image">الصورة الرئيسية</label>
                                                <?php if (!empty($service['image'])): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="img-thumbnail" style="max-height: 200px;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                                    <label class="custom-file-label" for="image">اختر صورة</label>
                                                </div>
                                                <small class="form-text text-muted">الحد الأقصى لحجم الصورة: 2 ميجابايت</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $service['is_active'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="is_active">نشط</label>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" <?php echo $service['is_featured'] ? 'checked' : ''; ?>>
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
    
    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // حذف الخدمة
    $result = db_delete('services', 'service_id = ?', [$service_id]);
    
    if ($result) {
        // حذف الصورة الرئيسية
        if (!empty($service['image'])) {
            delete_image($service['image']);
        }
        
        // حذف صور الخدمة
        $service_images = get_service_images($service_id);
        foreach ($service_images as $image) {
            delete_image($image['image_path']);
        }
        
        // حذف صور الخدمة من قاعدة البيانات
        db_delete('service_images', 'service_id = ?', [$service_id]);
        
        // حذف إعدادات SEO
        db_delete('seo_settings', 'entity_type = ? AND entity_id = ?', ['service', $service_id]);
        
        // إعادة التوجيه مع رسالة نجاح
        redirect('services_management.php?deleted=1');
    } else {
        // إعادة التوجيه مع رسالة خطأ
        redirect('services_management.php?error=1');
    }
}

/**
 * إدارة صور الخدمة
 */
function handle_manage_service_images() {
    global $db, $page_title;
    
    // التحقق من وجود معرف الخدمة
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        redirect('services_management.php');
    }
    
    $service_id = (int)$_GET['id'];
    
    // الحصول على بيانات الخدمة
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // تعيين عنوان الصفحة
    $page_title = 'إدارة صور الخدمة: ' . $service['title'];
    
    // الحصول على صور الخدمة
    $service_images = get_service_images($service_id);
    
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
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> نجاح!</h5>
                        تم حفظ الخدمة بنجاح. يمكنك الآن إضافة المزيد من الصور.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">رفع صور متعددة</h3>
                            </div>
                            <div class="card-body">
                                <div class="dropzone" id="dropzone" data-entity-type="service" data-entity-id="<?php echo $service_id; ?>"></div>
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
                                <h3 class="card-title">صور الخدمة</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($service_images)): ?>
                                    <div class="row" id="sortable-images" data-entity-type="service">
                                        <?php foreach ($service_images as $image): ?>
                                            <div class="col-md-3 mb-4 image-item" data-id="<?php echo $image['image_id']; ?>">
                                                <div class="card">
                                                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_path']); ?>" class="card-img-top" alt="صورة الخدمة">
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
                                        لا توجد صور للخدمة حالياً. استخدم منطقة السحب والإفلات أعلاه لإضافة صور.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <a href="services_management.php" class="btn btn-secondary">العودة إلى قائمة الخدمات</a>
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
    $service = get_service_by_id($service_id);
    
    if (!$service) {
        redirect('services_management.php');
    }
    
    // الحصول على إعدادات SEO للخدمة
    $seo_settings = get_seo_settings('service', $service_id);
    
    // تعيين عنوان الصفحة
    $page_title = 'إعدادات SEO للخدمة: ' . $service['title'];
    
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
            $meta_title = $service['title'];
        }
        
        if (empty($meta_description)) {
            $meta_description = $service['short_description'];
        }
        
        // إنشاء slug إذا لم يتم توفيره
        if (empty($slug)) {
            $slug = $service['slug'];
        } else {
            $slug = generate_slug($slug);
        }
        
        // التحقق من تفرد slug
        $slug = ensure_unique_slug($slug, 'services', 'service_id', $service_id);
        
        // إذا لم تكن هناك أخطاء، حدث الإعدادات
        if (empty($errors)) {
            // تحديث slug في جدول الخدمات
            db_update('services', ['slug' => $slug], 'service_id = ?', [$service_id]);
            
            // تحضير بيانات SEO
            $seo_data = [
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'keywords' => $keywords,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($seo_settings) {
                // تحديث إعدادات SEO الموجودة
                db_update('seo_settings', $seo_data, 'entity_type = ? AND entity_id = ?', ['service', $service_id]);
            } else {
                // إضافة إعدادات SEO جديدة
                $seo_data['entity_type'] = 'service';
                $seo_data['entity_id'] = $service_id;
                $seo_data['created_at'] = date('Y-m-d H:i:s');
                
                db_insert('seo_settings', $seo_data);
            }
            
            // إعادة التوجيه مع رسالة نجاح
            redirect('services_management.php?seo_updated=1');
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
                        
                        <form action="services_management.php?action=seo_settings&id=<?php echo $service_id; ?>" method="post">
                            <div class="form-group">
                                <label for="slug">الرابط المخصص (Slug)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?php echo SITE_URL; ?>/service-details.php?slug=</span>
                                    </div>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($service['slug']); ?>">
                                </div>
                                <small class="form-text text-muted">سيتم استخدام هذا الرابط في عنوان URL للخدمة</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title'] ?? $service['title']); ?>">
                                <small class="form-text text-muted">يفضل ألا يتجاوز 60 حرفاً</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $service['short_description']); ?></textarea>
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
                                        <h5 class="text-primary mb-1" id="preview_title"><?php echo htmlspecialchars($seo_settings['meta_title'] ?? $service['title']); ?></h5>
                                        <div class="text-success small mb-1"><?php echo SITE_URL; ?>/service-details.php?slug=<?php echo htmlspecialchars($service['slug']); ?></div>
                                        <p class="mb-0" id="preview_description"><?php echo htmlspecialchars($seo_settings['meta_description'] ?? $service['short_description']); ?></p>
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
