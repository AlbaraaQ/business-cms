<?php
/**
 * ملف وظائف المصادقة الإدارية
 * 
 * هذا الملف يحتوي على وظائف المصادقة والتحقق من صلاحيات المستخدمين في لوحة التحكم
 */

/**
 * التحقق من تسجيل دخول المدير
 * 
 * @return bool حالة تسجيل الدخول
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * تسجيل دخول المدير
 * 
 * @param string $username اسم المستخدم
 * @param string $password كلمة المرور
 * @return bool نجاح أو فشل عملية تسجيل الدخول
 */
function admin_login($username, $password) {
    global $db;
    
    // تنظيف المدخلات
    $username = clean_input($username);
    
    // الحصول على بيانات المستخدم
    $query = "SELECT * FROM admins WHERE username = ?";
    $admin = db_fetch_row($query, [$username]);
    
    // التحقق من وجود المستخدم وصحة كلمة المرور
    if ($admin && verify_password($password, $admin['password'])) {
        // تخزين بيانات المستخدم في الجلسة
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // تحديث آخر تسجيل دخول
        $query = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
        db_query($query, [$admin['admin_id']]);
        
        // تسجيل نشاط تسجيل الدخول
        log_admin_activity($admin['admin_id'], 'تسجيل دخول');
        
        return true;
    }
    
    return false;
}

/**
 * تسجيل خروج المدير
 * 
 * @return void
 */
function admin_logout() {
    // تسجيل نشاط تسجيل الخروج
    if (isset($_SESSION['admin_id'])) {
        log_admin_activity($_SESSION['admin_id'], 'تسجيل خروج');
    }
    
    // حذف متغيرات الجلسة
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    
    // إعادة تهيئة الجلسة
    session_regenerate_id(true);
}

/**
 * التحقق من صلاحيات المدير
 * 
 * @param string $permission الصلاحية المطلوبة
 * @return bool حالة الصلاحية
 */
function admin_has_permission($permission) {
    // إذا لم يكن المستخدم مسجل الدخول
    if (!is_admin_logged_in()) {
        return false;
    }
    
    // المدير الرئيسي لديه جميع الصلاحيات
    if ($_SESSION['admin_role'] === 'super_admin') {
        return true;
    }
    
    // الحصول على صلاحيات المستخدم
    $admin_permissions = get_admin_permissions($_SESSION['admin_id']);
    
    // التحقق من وجود الصلاحية
    return in_array($permission, $admin_permissions);
}

/**
 * الحصول على صلاحيات المدير
 * 
 * @param int $admin_id معرف المدير
 * @return array مصفوفة الصلاحيات
 */
function get_admin_permissions($admin_id) {
    global $db;
    
    // الحصول على دور المستخدم
    $query = "SELECT role FROM admins WHERE admin_id = ?";
    $admin = db_fetch_row($query, [$admin_id]);
    
    if (!$admin) {
        return [];
    }
    
    // المدير الرئيسي لديه جميع الصلاحيات
    if ($admin['role'] === 'super_admin') {
        return ['*'];
    }
    
    // الحصول على صلاحيات الدور
    $query = "SELECT p.permission_name
              FROM admin_role_permissions rp
              JOIN admin_permissions p ON rp.permission_id = p.permission_id
              JOIN admin_roles r ON rp.role_id = r.role_id
              WHERE r.role_name = ?";
    
    $result = db_query($query, [$admin['role']]);
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    return $permissions;
}

/**
 * تسجيل نشاط المدير
 * 
 * @param int $admin_id معرف المدير
 * @param string $activity النشاط
 * @param string $details تفاصيل إضافية (اختياري)
 * @return bool نجاح أو فشل العملية
 */
function log_admin_activity($admin_id, $activity, $details = '') {
    global $db;
    
    $data = [
        'admin_id' => $admin_id,
        'activity' => $activity,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return db_insert('admin_activity_log', $data) !== false;
}

/**
 * تغيير كلمة مرور المدير
 * 
 * @param int $admin_id معرف المدير
 * @param string $current_password كلمة المرور الحالية
 * @param string $new_password كلمة المرور الجديدة
 * @return bool نجاح أو فشل العملية
 */
function change_admin_password($admin_id, $current_password, $new_password) {
    global $db;
    
    // الحصول على بيانات المستخدم
    $query = "SELECT password FROM admins WHERE admin_id = ?";
    $admin = db_fetch_row($query, [$admin_id]);
    
    if (!$admin) {
        return false;
    }
    
    // التحقق من صحة كلمة المرور الحالية
    if (!verify_password($current_password, $admin['password'])) {
        return false;
    }
    
    // تشفير كلمة المرور الجديدة
    $hashed_password = hash_password($new_password);
    
    // تحديث كلمة المرور
    $query = "UPDATE admins SET password = ? WHERE admin_id = ?";
    $result = db_query($query, [$hashed_password, $admin_id]);
    
    if ($result) {
        // تسجيل نشاط تغيير كلمة المرور
        log_admin_activity($admin_id, 'تغيير كلمة المرور');
        return true;
    }
    
    return false;
}

/**
 * إنشاء رمز إعادة تعيين كلمة المرور
 * 
 * @param string $email البريد الإلكتروني
 * @return string|bool رمز إعادة التعيين أو false في حالة الفشل
 */
function create_password_reset_token($email) {
    global $db;
    
    // التحقق من وجود المستخدم
    $query = "SELECT admin_id FROM admins WHERE email = ?";
    $admin = db_fetch_row($query, [$email]);
    
    if (!$admin) {
        return false;
    }
    
    // إنشاء رمز فريد
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 3600); // صالح لمدة ساعة واحدة
    
    // حذف أي رموز سابقة
    $query = "DELETE FROM admin_password_resets WHERE admin_id = ?";
    db_query($query, [$admin['admin_id']]);
    
    // إدراج الرمز الجديد
    $data = [
        'admin_id' => $admin['admin_id'],
        'token' => $token,
        'expires_at' => $expires_at,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = db_insert('admin_password_resets', $data);
    
    if ($result) {
        return $token;
    }
    
    return false;
}

/**
 * التحقق من صحة رمز إعادة تعيين كلمة المرور
 * 
 * @param string $token الرمز
 * @return int|bool معرف المستخدم أو false في حالة الفشل
 */
function verify_password_reset_token($token) {
    global $db;
    
    // الحصول على بيانات الرمز
    $query = "SELECT admin_id, expires_at FROM admin_password_resets WHERE token = ?";
    $reset = db_fetch_row($query, [$token]);
    
    if (!$reset) {
        return false;
    }
    
    // التحقق من صلاحية الرمز
    if (strtotime($reset['expires_at']) < time()) {
        return false;
    }
    
    return $reset['admin_id'];
}

/**
 * إعادة تعيين كلمة المرور
 * 
 * @param string $token الرمز
 * @param string $new_password كلمة المرور الجديدة
 * @return bool نجاح أو فشل العملية
 */
function reset_admin_password($token, $new_password) {
    global $db;
    
    // التحقق من صحة الرمز
    $admin_id = verify_password_reset_token($token);
    
    if (!$admin_id) {
        return false;
    }
    
    // تشفير كلمة المرور الجديدة
    $hashed_password = hash_password($new_password);
    
    // تحديث كلمة المرور
    $query = "UPDATE admins SET password = ? WHERE admin_id = ?";
    $result = db_query($query, [$hashed_password, $admin_id]);
    
    if ($result) {
        // حذف الرمز
        $query = "DELETE FROM admin_password_resets WHERE token = ?";
        db_query($query, [$token]);
        
        // تسجيل نشاط إعادة تعيين كلمة المرور
        log_admin_activity($admin_id, 'إعادة تعيين كلمة المرور');
        
        return true;
    }
    
    return false;
}
