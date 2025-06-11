<?php
/**
 * صفحة إدارة المستخدمين
 * 
 * تتيح للمدير إدارة حسابات المستخدمين في لوحة التحكم
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

// التحقق من تسجيل الدخول وصلاحيات المدير
check_admin_login();
check_admin_role('admin');

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
        $error_message = "خطأ في التحقق (CSRF). يرجى المحاولة مرة أخرى.";
        // To prevent further execution of the switch case for this request,
        // we can set action to something that won't match cases or simply exit/redirect.
        // For now, we'll let the error message be displayed by falling through.
        // A more robust solution might involve a redirect or die().
    } else {
        // CSRF token is valid, proceed with action
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add_user':
                // ... (rest of add_user case)
            // Ensure to close the else block after the switch
    // Original switch content will be indented or wrapped in this else
    // For the diff, I will add the check and then the rest of the switch cases will follow,
    // assuming they are now inside this else block.
    // The actual diff will show the exact placement.
    // For brevity here, only showing the start of the modification.
    // The following switch statement will be inside the 'else' of the CSRF check.
    
    // $action = $_POST['action'] ?? ''; // This is now inside the else block

    switch ($action) { // This switch is now conditional on CSRF success
        case 'add_user':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // التحقق من البيانات
            $errors = [];
            
            if (empty($username)) {
                $errors[] = "اسم المستخدم مطلوب";
            } elseif (strlen($username) < 3) {
                $errors[] = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل";
            } else {
                // التحقق من عدم وجود اسم المستخدم بالفعل
                $count_user = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE username = :username", [':username' => $username]);
                if ($count_user && $count_user['count'] > 0) {
                    $errors[] = "اسم المستخدم موجود بالفعل";
                }
            }
            
            if (empty($email)) {
                $errors[] = "البريد الإلكتروني مطلوب";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "البريد الإلكتروني غير صالح";
            } else {
                // التحقق من عدم وجود البريد الإلكتروني بالفعل
                $count_email = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE email = :email", [':email' => $email]);
                if ($count_email && $count_email['count'] > 0) {
                    $errors[] = "البريد الإلكتروني موجود بالفعل";
                }
            }
            
            if (empty($password)) {
                $errors[] = "كلمة المرور مطلوبة";
            } elseif (strlen($password) < 6) {
                $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            } elseif ($password !== $confirm_password) {
                $errors[] = "كلمة المرور وتأكيدها غير متطابقين";
            }
            
            if (empty($full_name)) {
                $errors[] = "الاسم الكامل مطلوب";
            }
            
            if (empty($role)) {
                $errors[] = "الدور مطلوب";
            }
            
            if (empty($errors)) {
                // تشفير كلمة المرور
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // إضافة المستخدم
                $sql_insert_user = "INSERT INTO users (username, email, password, full_name, role, is_active, created_at)
                                    VALUES (:username, :email, :password, :full_name, :role, :is_active, NOW())";
                $params_insert = [
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':full_name' => $full_name,
                    ':role' => $role,
                    ':is_active' => $is_active
                ];
                if ($db->execute($sql_insert_user, $params_insert)) {
                    $user_id = $db->lastInsertId();
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'add_user', 'users', $user_id);
                    $success_message = "تمت إضافة المستخدم بنجاح";
                } else {
                    $errors[] = "فشل إضافة المستخدم إلى قاعدة البيانات.";
                    $error_message = implode("<br>", $errors);
                }
            } else {
                $error_message = implode("<br>", $errors);
            }
            break;
            
        case 'update_user':
            $user_id = (int)$_POST['user_id'];
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $password = $_POST['password'];
            
            // التحقق من البيانات
            $errors = [];
            
            if (empty($email)) {
                $errors[] = "البريد الإلكتروني مطلوب";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "البريد الإلكتروني غير صالح";
            } else {
                // التحقق من عدم وجود البريد الإلكتروني بالفعل لمستخدم آخر
                $count_email = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :user_id", [':email' => $email, ':user_id' => $user_id]);
                if ($count_email && $count_email['count'] > 0) {
                    $errors[] = "البريد الإلكتروني موجود بالفعل";
                }
            }
            
            if (empty($full_name)) {
                $errors[] = "الاسم الكامل مطلوب";
            }
            
            if (empty($role)) {
                $errors[] = "الدور مطلوب";
            }
            
            if (!empty($password) && strlen($password) < 6) {
                $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            }
            
            if (empty($errors)) {
                // تحديث المستخدم
                // تحديث المستخدم
                $sql_update_user = "UPDATE users SET email = :email, full_name = :full_name, role = :role, is_active = :is_active, updated_at = NOW()";
                $params_update = [
                    ':email' => $email,
                    ':full_name' => $full_name,
                    ':role' => $role,
                    ':is_active' => $is_active,
                    ':user_id' => $user_id
                ];

                if (!empty($password)) {
                    // تشفير كلمة المرور الجديدة
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql_update_user .= ", password = :password";
                    $params_update[':password'] = $hashed_password;
                }
                $sql_update_user .= " WHERE id = :user_id";
                
                if($db->execute($sql_update_user, $params_update)) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'update_user', 'users', $user_id);
                    $success_message = "تم تحديث المستخدم بنجاح";
                } else {
                    $errors[] = "فشل تحديث المستخدم في قاعدة البيانات.";
                    $error_message = implode("<br>", $errors);
                }
            } else {
                $error_message = implode("<br>", $errors);
            }
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            
            // التحقق من عدم حذف المستخدم الحالي
            if ($user_id == $_SESSION['admin_id']) {
                $error_message = "لا يمكن حذف المستخدم الحالي";
            } else {
                // الحصول على بيانات المستخدم قبل الحذف
                $user_data = $db->queryOne("SELECT * FROM users WHERE id = :user_id", [':user_id' => $user_id]);
                
                if ($user_data) {
                    if ($db->execute("DELETE FROM users WHERE id = :user_id", [':user_id' => $user_id])) {
                        // تسجيل النشاط
                        log_activity($_SESSION['admin_id'], 'delete_user', 'users', $user_id, $user_data);
                        $success_message = "تم حذف المستخدم بنجاح";
                    } else {
                        $error_message = "فشل حذف المستخدم.";
                    }
                } else {
                    $error_message = "المستخدم غير موجود";
                }
            }
            break;
    }
}

// الحصول على المستخدمين
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">إدارة المستخدمين</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> إضافة مستخدم جديد
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

            <!-- قائمة المستخدمين -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">المستخدمين</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا يوجد مستخدمين</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>اسم المستخدم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الاسم الكامل</th>
                                        <th>الدور</th>
                                        <th>الحالة</th>
                                        <th>آخر تسجيل دخول</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">مدير</span>
                                                <?php elseif ($user['role'] === 'editor'): ?>
                                                    <span class="badge bg-primary">محرر</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">مشاهد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">مفعل</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">غير مفعل</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">لم يسجل الدخول بعد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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

<!-- مودال إضافة مستخدم -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة مستخدم جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">الاسم الكامل</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">الدور</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="admin">مدير</option>
                            <option value="editor" selected>محرر</option>
                            <option value="viewer">مشاهد</option>
                        </select>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">مفعل</label>
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

<!-- مودال تعديل مستخدم -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل مستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">اسم المستخدم</label>
                        <input type="text" id="edit_username" class="form-control" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">كلمة المرور (اتركها فارغة للاحتفاظ بالحالية)</label>
                        <input type="password" name="password" id="edit_password" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">الاسم الكامل</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">الدور</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="admin">مدير</option>
                            <option value="editor">محرر</option>
                            <option value="viewer">مشاهد</option>
                        </select>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">مفعل</label>
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
// بيانات المستخدمين
const users = <?php echo json_encode($users); ?>;

function editUser(userId) {
    const user = users.find(u => u.id == userId);
    if (user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_is_active').checked = user.is_active == 1;
        
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
}

function deleteUser(userId, username) {
    if (confirm(`هل أنت متأكد من حذف المستخدم "${username}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        const form = document.createElement('form');
        form.method = 'POST';

        // Get CSRF token name and value
        // Assumes CSRF_TOKEN_NAME is available as a JS variable or directly echoed by PHP
        const csrfTokenName = '<?php echo CSRF_TOKEN_NAME; ?>';
        const csrfTokenInput = document.querySelector(`input[name="${csrfTokenName}"]`);
        let csrfTokenValue = '';

        if (csrfTokenInput) {
            csrfTokenValue = csrfTokenInput.value;
        } else {
            console.error('CSRF token field not found for deleteUser. Deletion will likely fail server-side.');
            // Optionally, alert the user or prevent form submission
            alert('خطأ في الأمان. لا يمكن إكمال الحذف.');
            return;
        }

        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="${csrfTokenName}" value="${csrfTokenValue}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Ensure a CSRF token field is available on the page for the deleteUser JS function to pick up,
// if not already rendered by a main form or another mechanism.
// This is a bit of a fallback; ideally, it's part of a more structured approach.
if (function_exists('csrf_input_field')) {
    echo '<div style="display:none;">' . csrf_input_field() . '</div>';
}
?>

<?php include 'includes/footer.php'; ?>
