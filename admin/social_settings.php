<?php
/**
 * صفحة إعدادات الشبكات الاجتماعية
 * 
 * تتيح للمدير إدارة روابط وإعدادات الشبكات الاجتماعية
 */

require_once '../includes/init.php';
require_once '../includes/functions/admin_auth.php';

// التحقق من تسجيل الدخول
check_admin_login();

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_social':
            $platform_id = (int)$_POST['platform_id'];
            $url = trim($_POST['url']);
            $username = trim($_POST['username'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = (int)$_POST['sort_order'];
            
            // تحديث الإعدادات
            $stmt = $db->prepare("UPDATE social_settings SET url = ?, username = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$url, $username, $is_active, $sort_order, $platform_id]);
            
            // تسجيل النشاط
            log_activity($_SESSION['admin_id'], 'update_social_settings', 'social_settings', $platform_id);
            
            $success_message = "تم تحديث إعدادات الشبكة الاجتماعية بنجاح";
            break;
            
        case 'add_social':
            $platform = trim($_POST['platform']);
            $url = trim($_POST['url']);
            $username = trim($_POST['username'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = (int)$_POST['sort_order'];
            
            // التحقق من عدم وجود المنصة بالفعل
            $stmt = $db->prepare("SELECT COUNT(*) FROM social_settings WHERE platform = ?");
            $stmt->execute([$platform]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $error_message = "هذه المنصة موجودة بالفعل";
            } else {
                // إضافة منصة جديدة
                $stmt = $db->prepare("INSERT INTO social_settings (platform, url, username, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$platform, $url, $username, $is_active, $sort_order]);
                
                $platform_id = $db->lastInsertId();
                
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'add_social_platform', 'social_settings', $platform_id);
                
                $success_message = "تمت إضافة منصة جديدة بنجاح";
            }
            break;
            
        case 'delete_social':
            $platform_id = (int)$_POST['platform_id'];
            
            // الحصول على بيانات المنصة قبل الحذف
            $stmt = $db->prepare("SELECT * FROM social_settings WHERE id = ?");
            $stmt->execute([$platform_id]);
            $platform_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($platform_data) {
                $stmt = $db->prepare("DELETE FROM social_settings WHERE id = ?");
                $stmt->execute([$platform_id]);
                
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'delete_social_platform', 'social_settings', $platform_id, $platform_data);
                
                $success_message = "تم حذف المنصة بنجاح";
            }
            break;
    }
}

// الحصول على إعدادات الشبكات الاجتماعية
$stmt = $db->query("SELECT * FROM social_settings ORDER BY sort_order ASC");
$social_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <?php if (empty($social_settings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد شبكات اجتماعية مضافة</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>الترتيب</th>
                                        <th>المنصة</th>
                                        <th>الرابط</th>
                                        <th>اسم المستخدم</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($social_settings as $social): ?>
                                        <tr>
                                            <td><?php echo $social['sort_order']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fab fa-<?php echo strtolower($social['platform']); ?> fa-2x me-2"></i>
                                                    <span><?php echo ucfirst(htmlspecialchars($social['platform'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($social['url']): ?>
                                                    <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($social['url']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">غير محدد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($social['username']): ?>
                                                    <?php echo htmlspecialchars($social['username']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">غير محدد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($social['is_active']): ?>
                                                    <span class="badge bg-success">مفعل</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">غير مفعل</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editSocial(<?php echo $social['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteSocial(<?php echo $social['id']; ?>, '<?php echo $social['platform']; ?>')">
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
                    <input type="hidden" name="action" value="add_social">
                    
                    <div class="mb-3">
                        <label for="platform" class="form-label">اسم المنصة</label>
                        <input type="text" name="platform" id="platform" class="form-control" required>
                        <small class="form-text text-muted">مثال: facebook, twitter, instagram, linkedin, youtube, whatsapp</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="url" class="form-label">الرابط</label>
                        <input type="url" name="url" id="url" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" id="username" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">الترتيب</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">تفعيل</label>
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
                    <input type="hidden" name="action" value="update_social">
                    <input type="hidden" name="platform_id" id="edit_platform_id">
                    
                    <div class="mb-3">
                        <label for="edit_platform_name" class="form-label">اسم المنصة</label>
                        <input type="text" id="edit_platform_name" class="form-control" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_url" class="form-label">الرابط</label>
                        <input type="url" name="url" id="edit_url" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" id="edit_username" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">الترتيب</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">تفعيل</label>
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
// بيانات الشبكات الاجتماعية
const socialSettings = <?php echo json_encode($social_settings); ?>;

function editSocial(socialId) {
    const social = socialSettings.find(s => s.id == socialId);
    if (social) {
        document.getElementById('edit_platform_id').value = social.id;
        document.getElementById('edit_platform_name').value = social.platform;
        document.getElementById('edit_url').value = social.url;
        document.getElementById('edit_username').value = social.username;
        document.getElementById('edit_sort_order').value = social.sort_order;
        document.getElementById('edit_is_active').checked = social.is_active == 1;
        
        new bootstrap.Modal(document.getElementById('editSocialModal')).show();
    }
}

function deleteSocial(socialId, platformName) {
    if (confirm(`هل أنت متأكد من حذف منصة "${platformName}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_social">
            <input type="hidden" name="platform_id" value="${socialId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
