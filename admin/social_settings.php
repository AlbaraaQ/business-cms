<?php
/**
 * صفحة إعدادات الشبكات الاجتماعية
 * 
 * تتيح للمدير إدارة روابط وإعدادات الشبكات الاجتماعية
 */

require_once __DIR__ . '/init.php'; // Loads admin-specific initialization
// admin_auth.php contains functions like admin_login, admin_logout, is_admin_logged_in (specific version)
// It's assumed that PROJECT_ROOT is defined via config.php loaded in admin/init.php
// and that admin_auth.php's dependencies (like db_query) are met by what admin/init.php sets up,
// OR that admin/init.php will be augmented to make these compatible.
// For now, directly including it using PROJECT_ROOT.
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . '/includes/functions/admin_auth.php';
} else {
    // Fallback or error if PROJECT_ROOT is not defined, though it should be by admin/init.php
    require_once dirname(__DIR__) . '/includes/functions/admin_auth.php';
}

// التحقق من تسجيل الدخول
check_admin_login();

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF Token Verification
    if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error_message = "خطأ في التحقق (CSRF). يرجى المحاولة مرة أخرى.";
        // To prevent further execution, you might want to set $action to an invalid value or exit/redirect
        // For now, falling through to display error_message.
    } else {
        switch ($action) {
            case 'update_social':
                $link_id = (int)$_POST['link_id']; // Changed from platform_id
                $profile_url = trim($_POST['profile_url']); // Changed from url
                $icon_class = trim($_POST['icon_class'] ?? '');

                // تحديث الرابط
                // platform_name is generally not updatable to maintain consistency, only URL and icon.
                $sql_update = "UPDATE social_links SET profile_url = :profile_url, icon_class = :icon_class, updated_at = NOW() WHERE id = :id";
                $params_update = [
                    ':profile_url' => $profile_url,
                    ':icon_class' => $icon_class,
                    ':id' => $link_id
                ];
                if ($db->execute($sql_update, $params_update)) {
                    log_activity($_SESSION['admin_id'], 'update_social_link', 'social_links', $link_id);
                    $success_message = "تم تحديث رابط الشبكة الاجتماعية بنجاح";
                } else {
                    $error_message = "فشل تحديث رابط الشبكة الاجتماعية.";
                }
                break;

            case 'add_social':
                $platform_name = trim($_POST['platform_name']); // Changed from platform
                $profile_url = trim($_POST['profile_url']); // Changed from url
                $icon_class = trim($_POST['icon_class'] ?? '');

                // التحقق من عدم وجود اسم المنصة بالفعل
                $count_platform = $db->queryOne("SELECT COUNT(*) as count FROM social_links WHERE platform_name = :platform_name", [':platform_name' => $platform_name]);
                $exists = ($count_platform && $count_platform['count'] > 0);

                if ($exists) {
                    $error_message = "اسم هذه المنصة موجود بالفعل";
                } else {
                    // إضافة رابط جديد
                    $sql_insert = "INSERT INTO social_links (platform_name, profile_url, icon_class, created_at, updated_at) VALUES (:platform_name, :profile_url, :icon_class, NOW(), NOW())";
                    $params_insert = [
                        ':platform_name' => $platform_name,
                        ':profile_url' => $profile_url,
                        ':icon_class' => $icon_class
                    ];
                    if ($db->execute($sql_insert, $params_insert)) {
                        $link_id = $db->lastInsertId();
                        log_activity($_SESSION['admin_id'], 'add_social_link', 'social_links', $link_id);
                        $success_message = "تمت إضافة رابط منصة جديدة بنجاح";
                    } else {
                        $error_message = "فشل إضافة رابط منصة جديدة.";
                    }
                }
                break;

            case 'delete_social':
                $link_id = (int)$_POST['link_id']; // Changed from platform_id

                // الحصول على بيانات الرابط قبل الحذف
                $link_data = $db->queryOne("SELECT * FROM social_links WHERE id = :id", [':id' => $link_id]);

                if ($link_data) {
                    if ($db->execute("DELETE FROM social_links WHERE id = :id", [':id' => $link_id])) {
                        log_activity($_SESSION['admin_id'], 'delete_social_link', 'social_links', $link_id, $link_data);
                        $success_message = "تم حذف رابط المنصة بنجاح";
                    } else {
                        $error_message = "فشل حذف رابط المنصة.";
                    }
                } else {
                    $error_message = "الرابط غير موجود.";
                }
                break;
        }
    }
}

// الحصول على روابط الشبكات الاجتماعية
$social_links = $db->query("SELECT id, platform_name, profile_url, icon_class, created_at FROM social_links ORDER BY id ASC"); // Changed table and columns

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">إعدادات الشبكات الاجتماعية</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSocialModal">
                    <i class="fas fa-plus"></i> إضافة منصة جديدة
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">الشبكات الاجتماعية</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($social_links)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد شبكات اجتماعية مضافة</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>المنصة</th>
                                        <th>الرابط</th>
                                        <th>أيقونة (Class)</th>
                                        <th>تاريخ الإضافة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($social_links as $link): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="<?php echo htmlspecialchars($link['icon_class']); ?> fa-2x me-2"></i>
                                                    <span><?php echo htmlspecialchars($link['platform_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($link['profile_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($link['profile_url']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($link['profile_url']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">غير محدد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($link['icon_class']): ?>
                                                    <code><?php echo htmlspecialchars($link['icon_class']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">غير محدد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('Y-m-d', strtotime($link['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editSocial(<?php echo $link['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteSocial(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars(addslashes($link['platform_name'])); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة منصة جديدة -->
<div class="modal fade" id="addSocialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة منصة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="action" value="add_social">
                    
                    <div class="mb-3">
                        <label for="add_platform_name" class="form-label">اسم المنصة</label>
                        <input type="text" name="platform_name" id="add_platform_name" class="form-control" required>
                        <small class="form-text text-muted">مثال: Facebook, Twitter, Instagram</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_profile_url" class="form-label">رابط الملف الشخصي</label>
                        <input type="url" name="profile_url" id="add_profile_url" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_icon_class" class="form-label">فئة الأيقونة (Icon Class)</label>
                        <input type="text" name="icon_class" id="add_icon_class" class="form-control">
                        <small class="form-text text-muted">مثال: fab fa-facebook-f, fab fa-twitter. استخدم <a href="https://fontawesome.com/icons" target="_blank">Font Awesome</a>.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال تعديل منصة -->
<div class="modal fade" id="editSocialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل منصة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="action" value="update_social">
                    <input type="hidden" name="link_id" id="edit_link_id">
                    
                    <div class="mb-3">
                        <label for="edit_platform_name_display" class="form-label">اسم المنصة</label>
                        <input type="text" id="edit_platform_name_display" class="form-control" disabled>
                        <small>لا يمكن تغيير اسم المنصة بعد الإضافة.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_profile_url" class="form-label">رابط الملف الشخصي</label>
                        <input type="url" name="profile_url" id="edit_profile_url" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icon_class" class="form-label">فئة الأيقونة (Icon Class)</label>
                        <input type="text" name="icon_class" id="edit_icon_class" class="form-control">
                        <small class="form-text text-muted">مثال: fab fa-facebook-f, fab fa-twitter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// بيانات روابط الشبكات الاجتماعية
const socialLinks = <?php echo json_encode($social_links); ?>;

function editSocial(socialId) {
    const link = socialLinks.find(s => s.id == socialId);
    if (link) {
        document.getElementById('edit_link_id').value = link.id;
        document.getElementById('edit_platform_name_display').value = link.platform_name;
        document.getElementById('edit_profile_url').value = link.profile_url;
        document.getElementById('edit_icon_class').value = link.icon_class;
        
        new bootstrap.Modal(document.getElementById('editSocialModal')).show();
    }
}

function deleteSocial(linkId, platformName) {
    if (confirm(`هل أنت متأكد من حذف رابط منصة "${platformName}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        const csrfTokenInput = document.querySelector('input[name="<?php echo CSRF_TOKEN_NAME; ?>"]');

        form.innerHTML = `
            <input type="hidden" name="action" value="delete_social">
            <input type="hidden" name="link_id" value="${linkId}">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="${csrfTokenInput ? csrfTokenInput.value : ''}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
