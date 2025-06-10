<?php
/**
 * وظائف خاصة بقاعدة البيانات
 * 
 * هذا الملف يحتوي على وظائف التعامل مع قاعدة البيانات
 * مع دعم العمليات الأساسية والمتقدمة
 */

/**
 * إنشاء اتصال بقاعدة البيانات
 * 
 * @return PDO كائن اتصال قاعدة البيانات
 */
function db_connect() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // تسجيل الخطأ وعرض رسالة مناسبة للمستخدم
        error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى لاحقاً.");
    }
}

/**
 * تنفيذ استعلام قاعدة البيانات
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @return PDOStatement نتيجة الاستعلام
 */
function db_query($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في استعلام قاعدة البيانات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * الحصول على صف واحد من قاعدة البيانات
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @return array|false صف واحد من البيانات أو false إذا لم يتم العثور على نتائج
 */
function db_fetch_row($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt->fetch();
}

/**
 * الحصول على جميع الصفوف من قاعدة البيانات
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @return array مصفوفة تحتوي على جميع الصفوف
 */
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * الحصول على قيمة واحدة من قاعدة البيانات
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @return mixed القيمة المطلوبة أو false إذا لم يتم العثور على نتائج
 */
function db_fetch_value($sql, $params = []) {
    $stmt = db_query($sql, $params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : false;
}

/**
 * إدراج بيانات في قاعدة البيانات
 * 
 * @param string $table اسم الجدول
 * @param array $data البيانات المراد إدراجها (مصفوفة مفاتيح وقيم)
 * @return int|false معرف السجل الجديد أو false في حالة الفشل
 */
function db_insert($table, $data) {
    global $db;
    
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute(array_values($data));
        
        if ($result) {
            return $db->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في إدراج البيانات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * تحديث بيانات في قاعدة البيانات
 * 
 * @param string $table اسم الجدول
 * @param array $data البيانات المراد تحديثها (مصفوفة مفاتيح وقيم)
 * @param string $where شرط التحديث
 * @param array $params معلمات شرط التحديث
 * @return bool نجاح أو فشل العملية
 */
function db_update($table, $data, $where, $params = []) {
    global $db;
    
    try {
        $set = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $set_clause = implode(', ', $set);
        
        $sql = "UPDATE $table SET $set_clause WHERE $where";
        
        $stmt = $db->prepare($sql);
        
        // دمج معلمات البيانات ومعلمات الشرط
        $all_params = array_merge(array_values($data), $params);
        
        return $stmt->execute($all_params);
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في تحديث البيانات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * حذف بيانات من قاعدة البيانات
 * 
 * @param string $table اسم الجدول
 * @param string $where شرط الحذف
 * @param array $params معلمات شرط الحذف
 * @return bool نجاح أو فشل العملية
 */
function db_delete($table, $where, $params = []) {
    global $db;
    
    try {
        $sql = "DELETE FROM $table WHERE $where";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في حذف البيانات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * التحقق من وجود سجل في قاعدة البيانات
 * 
 * @param string $table اسم الجدول
 * @param string $where شرط البحث
 * @param array $params معلمات شرط البحث
 * @return bool هل السجل موجود
 */
function db_exists($table, $where, $params = []) {
    global $db;
    
    try {
        $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في التحقق من وجود السجل: " . $e->getMessage());
        throw $e;
    }
}

/**
 * الحصول على عدد السجلات في قاعدة البيانات
 * 
 * @param string $table اسم الجدول
 * @param string $where شرط البحث (اختياري)
 * @param array $params معلمات شرط البحث (اختياري)
 * @return int عدد السجلات
 */
function db_count($table, $where = '', $params = []) {
    global $db;
    
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في حساب عدد السجلات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * تنفيذ استعلام مع صفحات
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @param int $page رقم الصفحة
 * @param int $per_page عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على البيانات ومعلومات الصفحات
 */
function db_paginate($sql, $params = [], $page = 1, $per_page = 10) {
    global $db;
    
    try {
        // حساب إجمالي عدد السجلات
        $count_sql = "SELECT COUNT(*) FROM ($sql) as count_table";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = (int)$count_stmt->fetchColumn();
        
        // حساب عدد الصفحات
        $total_pages = ceil($total / $per_page);
        
        // التأكد من أن رقم الصفحة صحيح
        $page = max(1, min($page, $total_pages));
        
        // حساب الإزاحة
        $offset = ($page - 1) * $per_page;
        
        // إضافة حدود الصفحة إلى الاستعلام
        $paginated_sql = "$sql LIMIT $offset, $per_page";
        
        // تنفيذ الاستعلام
        $stmt = $db->prepare($paginated_sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // إرجاع البيانات ومعلومات الصفحات
        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ];
    } catch (PDOException $e) {
        // تسجيل الخطأ وإعادة إلقاء الاستثناء
        error_log("خطأ في تنفيذ استعلام مع صفحات: " . $e->getMessage());
        throw $e;
    }
}

/**
 * بدء معاملة قاعدة البيانات
 * 
 * @return bool نجاح أو فشل العملية
 */
function db_begin_transaction() {
    global $db;
    return $db->beginTransaction();
}

/**
 * تأكيد معاملة قاعدة البيانات
 * 
 * @return bool نجاح أو فشل العملية
 */
function db_commit() {
    global $db;
    return $db->commit();
}

/**
 * التراجع عن معاملة قاعدة البيانات
 * 
 * @return bool نجاح أو فشل العملية
 */
function db_rollback() {
    global $db;
    return $db->rollBack();
}

/**
 * الحصول على آخر خطأ في قاعدة البيانات
 * 
 * @return string رسالة الخطأ
 */
function db_last_error() {
    global $db;
    $error = $db->errorInfo();
    return $error[2];
}

/**
 * تنفيذ ملف SQL
 * 
 * @param string $file_path مسار ملف SQL
 * @return bool نجاح أو فشل العملية
 */
function db_execute_sql_file($file_path) {
    global $db;
    
    if (!file_exists($file_path)) {
        return false;
    }
    
    try {
        $sql = file_get_contents($file_path);
        
        // تقسيم الملف إلى استعلامات منفصلة
        $queries = explode(';', $sql);
        
        $db->beginTransaction();
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            if (!empty($query)) {
                $db->exec($query);
            }
        }
        
        return $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("خطأ في تنفيذ ملف SQL: " . $e->getMessage());
        return false;
    }
}

/**
 * إنشاء نسخة احتياطية لقاعدة البيانات
 * 
 * @param string $output_file مسار ملف النسخة الاحتياطية
 * @return bool نجاح أو فشل العملية
 */
function db_backup($output_file) {
    try {
        // التحقق من وجود المجلد
        $dir = dirname($output_file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // إنشاء أمر النسخ الاحتياطي
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($output_file)
        );
        
        // تنفيذ الأمر
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    } catch (Exception $e) {
        error_log("خطأ في إنشاء نسخة احتياطية لقاعدة البيانات: " . $e->getMessage());
        return false;
    }
}

/**
 * استعادة قاعدة البيانات من نسخة احتياطية
 * 
 * @param string $input_file مسار ملف النسخة الاحتياطية
 * @return bool نجاح أو فشل العملية
 */
function db_restore($input_file) {
    try {
        // التحقق من وجود الملف
        if (!file_exists($input_file)) {
            return false;
        }
        
        // إنشاء أمر الاستعادة
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($input_file)
        );
        
        // تنفيذ الأمر
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    } catch (Exception $e) {
        error_log("خطأ في استعادة قاعدة البيانات: " . $e->getMessage());
        return false;
    }
}
