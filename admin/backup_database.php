<?php
/**
 * صفحة النسخ الاحتياطي لقاعدة البيانات
 * 
 * تتيح للمدير إنشاء وإدارة النسخ الاحتياطية لقاعدة البيانات
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

// تحديد مجلد النسخ الاحتياطية
$backup_dir = BASE_PATH . '/backups';

// إنشاء المجلد إذا لم يكن موجوداً
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            // إنشاء اسم الملف
            $date = date('Y-m-d_H-i-s');
            $backup_file = $backup_dir . '/backup_' . $date . '.sql';
            
            // إنشاء النسخة الاحتياطية
            $result = create_database_backup($backup_file);
            
            if ($result) {
                // تسجيل النسخة الاحتياطية في قاعدة البيانات
                $file_size = filesize($backup_file);
                $sql_insert_backup = "INSERT INTO backups (filename, file_path, file_size, backup_type, created_by)
                                      VALUES (:filename, :file_path, :file_size, 'manual', :created_by)";
                $params_insert = [
                    ':filename' => basename($backup_file),
                    ':file_path' => $backup_file,
                    ':file_size' => $file_size,
                    ':created_by' => $_SESSION['admin_id']
                ];
                if ($db->execute($sql_insert_backup, $params_insert)) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'create_backup', 'backups', $db->lastInsertId());
                    $success_message = "تم إنشاء النسخة الاحتياطية بنجاح";
                } else {
                    $error_message = "فشل تسجيل النسخة الاحتياطية في قاعدة البيانات";
                }
            } else {
                $error_message = "حدث خطأ أثناء إنشاء النسخة الاحتياطية";
            }
            break;
            
        case 'delete_backup':
            $backup_id = (int)$_POST['backup_id'];
            
            // الحصول على بيانات النسخة الاحتياطية
            $backup = $db->queryOne("SELECT * FROM backups WHERE id = :id", [':id' => $backup_id]);
            
            if ($backup && file_exists($backup['file_path'])) {
                // حذف الملف
                unlink($backup['file_path']);
                
                // حذف السجل من قاعدة البيانات
                $sql_delete_backup = "DELETE FROM backups WHERE id = :id";
                if($db->execute($sql_delete_backup, [':id' => $backup_id])) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'delete_backup', 'backups', $backup_id, $backup);
                    $success_message = "تم حذف النسخة الاحتياطية بنجاح";
                } else {
                    $error_message = "فشل حذف النسخة الاحتياطية من قاعدة البيانات";
                }
            } else {
                $error_message = "النسخة الاحتياطية غير موجودة";
            }
            break;
            
        case 'restore_backup':
            $backup_id = (int)$_POST['backup_id'];
            
            // الحصول على بيانات النسخة الاحتياطية
            $backup = $db->queryOne("SELECT * FROM backups WHERE id = :id", [':id' => $backup_id]);
            
            if ($backup && file_exists($backup['file_path'])) {
                // إنشاء نسخة احتياطية قبل الاستعادة
                $date = date('Y-m-d_H-i-s');
                $pre_restore_backup = $backup_dir . '/pre_restore_' . $date . '.sql';
                create_database_backup($pre_restore_backup);
                
                // استعادة النسخة الاحتياطية
                $result = restore_database_backup($backup['file_path']);
                
                if ($result) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'restore_backup', 'backups', $backup_id);
                    
                    $success_message = "تم استعادة النسخة الاحتياطية بنجاح";
                } else {
                    $error_message = "حدث خطأ أثناء استعادة النسخة الاحتياطية";
                }
            } else {
                $error_message = "النسخة الاحتياطية غير موجودة";
            }
            break;
            
        case 'download_backup':
            $backup_id = (int)$_POST['backup_id'];
            
            // الحصول على بيانات النسخة الاحتياطية
            $backup = $db->queryOne("SELECT * FROM backups WHERE id = :id", [':id' => $backup_id]);
            
            if ($backup && file_exists($backup['file_path'])) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'download_backup', 'backups', $backup_id);
                
                // تنزيل الملف
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($backup['file_path']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($backup['file_path']));
                readfile($backup['file_path']);
                exit;
            } else {
                $error_message = "النسخة الاحتياطية غير موجودة";
            }
            break;
            
        case 'update_settings':
            $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
            $backup_frequency = (int)$_POST['backup_frequency'];
            $keep_days = (int)$_POST['keep_days'];
            
            // تحديث الإعدادات في قاعدة البيانات
            $sql_update_settings = "UPDATE site_settings SET
                                        auto_backup = :auto_backup,
                                        backup_frequency = :backup_frequency,
                                        backup_keep_days = :backup_keep_days
                                    WHERE id = 1";
            $params_update_settings = [
                ':auto_backup' => $auto_backup,
                ':backup_frequency' => $backup_frequency,
                ':backup_keep_days' => $keep_days
            ];
            if($db->execute($sql_update_settings, $params_update_settings)) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'update_backup_settings', 'site_settings', 1);
                $success_message = "تم تحديث إعدادات النسخ الاحتياطي بنجاح";
            } else {
                $error_message = "فشل تحديث إعدادات النسخ الاحتياطي";
            }
            break;
    }
}

// الحصول على النسخ الاحتياطية
$sql_fetch_backups = "SELECT b.*, u.full_name as created_by_name
                      FROM backups b
                      LEFT JOIN users u ON b.created_by = u.id
                      ORDER BY b.created_at DESC";
$backups = $db->query($sql_fetch_backups);

// الحصول على إعدادات النسخ الاحتياطي
$settings = $db->queryOne("SELECT auto_backup, backup_frequency, backup_keep_days FROM site_settings WHERE id = 1");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">النسخ الاحتياطي لقاعدة البيانات</h1>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> إنشاء نسخة احتياطية جديدة
                    </button>
                </form>
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

            <!-- إعدادات النسخ الاحتياطي -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">إعدادات النسخ الاحتياطي</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="auto_backup" id="auto_backup" 
                                   <?php echo ($settings['auto_backup'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="auto_backup">تفعيل النسخ الاحتياطي التلقائي</label>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="backup_frequency" class="form-label">تكرار النسخ الاحتياطي (بالأيام)</label>
                                    <input type="number" name="backup_frequency" id="backup_frequency" class="form-control" 
                                           value="<?php echo $settings['backup_frequency'] ?? 7; ?>" min="1" max="30">
                                    <small class="form-text text-muted">عدد الأيام بين كل نسخة احتياطية تلقائية</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="keep_days" class="form-label">الاحتفاظ بالنسخ الاحتياطية (بالأيام)</label>
                                    <input type="number" name="keep_days" id="keep_days" class="form-control" 
                                           value="<?php echo $settings['backup_keep_days'] ?? 30; ?>" min="1" max="365">
                                    <small class="form-text text-muted">عدد الأيام للاحتفاظ بالنسخ الاحتياطية قبل حذفها تلقائياً</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- قائمة النسخ الاحتياطية -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">النسخ الاحتياطية المتوفرة</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-database fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد نسخ احتياطية متوفرة</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>اسم الملف</th>
                                        <th>النوع</th>
                                        <th>الحجم</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>بواسطة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(basename($backup['filename'])); ?></td>
                                            <td>
                                                <?php if ($backup['backup_type'] === 'automatic'): ?>
                                                    <span class="badge bg-info">تلقائي</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">يدوي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo format_file_size($backup['file_size']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($backup['created_at'])); ?></td>
                                            <td>
                                                <?php if ($backup['created_by_name']): ?>
                                                    <?php echo htmlspecialchars($backup['created_by_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">النظام</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="download_backup">
                                                        <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-primary" title="تنزيل">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="confirmRestore(<?php echo $backup['id']; ?>, '<?php echo basename($backup['filename']); ?>')" title="استعادة">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $backup['id']; ?>, '<?php echo basename($backup['filename']); ?>')" title="حذف">
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

<script>
function confirmDelete(backupId, filename) {
    if (confirm(`هل أنت متأكد من حذف النسخة الاحتياطية "${filename}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_backup">
            <input type="hidden" name="backup_id" value="${backupId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmRestore(backupId, filename) {
    if (confirm(`هل أنت متأكد من استعادة النسخة الاحتياطية "${filename}"؟ سيتم استبدال جميع البيانات الحالية بالبيانات من هذه النسخة الاحتياطية. سيتم إنشاء نسخة احتياطية للبيانات الحالية قبل الاستعادة.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="restore_backup">
            <input type="hidden" name="backup_id" value="${backupId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// دالة لتنسيق حجم الملف
function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// دالة لإنشاء نسخة احتياطية
function create_database_backup($file_path) {
    global $db_host, $db_name, $db_user, $db_pass;
    
    // استخدام mysqldump إذا كان متاحاً
    $command = "mysqldump --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name} > {$file_path}";
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        return true;
    }
    
    // إذا فشل mysqldump، استخدم PHP
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- النسخة الاحتياطية لقاعدة البيانات {$db_name}\n";
        $sql .= "-- تاريخ الإنشاء: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // هيكل الجدول
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $sql .= $row['Create Table'] . ";\n\n";
            
            // بيانات الجدول
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $sql .= "INSERT INTO `{$table}` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $row_values[] = 'NULL';
                        } else {
                            $row_values[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(', ', $row_values) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents($file_path, $sql);
        
        return true;
    } catch (PDOException $e) {
        error_log("خطأ في إنشاء النسخة الاحتياطية: " . $e->getMessage());
        return false;
    }
}

// دالة لاستعادة نسخة احتياطية
function restore_database_backup($file_path) {
    global $db_host, $db_name, $db_user, $db_pass;
    
    // استخدام mysql إذا كان متاحاً
    $command = "mysql --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name} < {$file_path}";
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        return true;
    }
    
    // إذا فشل mysql، استخدم PHP
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = file_get_contents($file_path);
        $pdo->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        error_log("خطأ في استعادة النسخة الاحتياطية: " . $e->getMessage());
        return false;
    }
}
?>

<?php include 'includes/footer.php'; ?>
