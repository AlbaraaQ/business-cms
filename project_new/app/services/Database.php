<?php
/**
 * كلاس قاعدة البيانات - حداد جده
 * 
 * يوفر اتصال آمن بقاعدة البيانات MySQL باستخدام PDO
 * مع حماية من هجمات SQL Injection
 */

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    private $error;

    /**
     * إنشاء اتصال قاعدة البيانات
     */
    public function __construct($host = null, $dbname = null, $username = null, $password = null, $charset_param = null) {
        $this->host = $host ?? (defined('DB_HOST') ? DB_HOST : null);
        $this->dbname = $dbname ?? (defined('DB_NAME') ? DB_NAME : null);
        $this->username = $username ?? (defined('DB_USER') ? DB_USER : null);
        $this->password = $password ?? (defined('DB_PASS') ? DB_PASS : null); // Password can be an empty string
        $this->charset = $charset_param ?? (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
        
        // Only attempt to connect if essential parameters are available.
        // This is crucial for scenarios like install.php where Database.php might be included
        // before DB configuration is finalized and constants are defined.
        if ($this->host && $this->dbname && $this->username !== null && $this->password !== null) {
            $this->connect();
        }
    }

    /**
     * الاتصال بقاعدة البيانات
     */
    private function connect() {
        if (!$this->host || !$this->dbname || $this->username === null || $this->password === null) {
            $this->error = "Database connection parameters are incomplete.";
            // error_log("Database Connection Error: Incomplete parameters.");
            // Do not throw exception here, allow instantiation but flag error. Operations will fail.
            return;
        }
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            // It's better to let the calling code decide how to handle this,
            // especially during installation. For general use, an Exception is good.
            // For now, we log and set the error. isConnected() or getError() can be checked.
            // throw new Exception("فشل الاتصال بقاعدة البيانات: " . $this->error);
        }
    }

    /**
     * Check if PDO connection is established.
     */
    public function isConnected() {
        return $this->pdo !== null;
    }


    /**
     * تنفيذ استعلام بدون إرجاع نتائج (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Execute Error: " . $this->error . " | SQL: " . $sql);
            throw new Exception("خطأ في تنفيذ الاستعلام: " . $this->error);
        }
    }

    /**
     * تنفيذ استعلام مع إرجاع النتائج (SELECT)
     */
    public function query($sql, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Query Error: " . $this->error . " | SQL: " . $sql);
            throw new Exception("خطأ في تنفيذ الاستعلام: " . $this->error);
        }
    }

    /**
     * تنفيذ استعلام مع إرجاع صف واحد فقط
     */
    public function queryOne($sql, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database QueryOne Error: " . $this->error . " | SQL: " . $sql);
            throw new Exception("خطأ في تنفيذ الاستعلام: " . $this->error);
        }
    }

    /**
     * الحصول على معرف آخر سجل تم إدراجه
     */
    public function lastInsertId() {
        if (!$this->isConnected()) {
            // Or return null, or throw exception. Depends on expected behavior.
            // lastInsertId on a null $this->pdo would error anyway.
            return null; 
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * بدء معاملة قاعدة البيانات
     */
    public function beginTransaction() {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        return $this->pdo->beginTransaction();
    }

    /**
     * تأكيد المعاملة
     */
    public function commit() {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        return $this->pdo->commit();
    }

    /**
     * إلغاء المعاملة
     */
    public function rollback() {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        return $this->pdo->rollback();
    }

    /**
     * تشغيل عدة استعلامات SQL (لاستخدامها في التثبيت)
     * This method is problematic for DDL with PDO's exec if sqlFile contains multiple statements
     * separated by typical delimiters and comments. PDO::exec is for single statement or simple multi.
     * install.php uses direct PDO exec loop which is safer.
     */
    public function runMultipleQueriesFromFile($sqlFile) {
        if (!$this->isConnected()) {
            throw new Exception("Not connected to database. " . $this->error);
        }
        try {
            $sql = file_get_contents($sqlFile);
            // A more robust parser would be needed for complex SQL files.
            // For simple DDL scripts, exec MIGHT work if statements are simple and correctly terminated.
            // However, it's safer to parse and execute one by one if there are issues.
            $this->pdo->exec($sql); 
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Multiple Queries Error: " . $this->error);
            throw new Exception("خطأ في تنفيذ استعلامات قاعدة البيانات: " . $this->error);
        }
    }

    /**
     * اختبار الاتصال بقاعدة البيانات
     * This specific instance method is a bit redundant if connect() is successful.
     * The static createDatabase method is for initial DB creation.
     */
    public function testConnection() {
        if (!$this->isConnected()) return false;
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * إنشاء قاعدة البيانات إذا لم تكن موجودة
     */
    public static function createDatabase($host, $username, $password, $dbname, $charset = 'utf8mb4') {
        try {
            // Connect to MySQL server (without selecting a specific database yet)
            $dsn = "mysql:host={$host};charset={$charset}";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // إنشاء قاعدة البيانات
            // Use backticks for dbname in case it contains special characters or is a reserved word
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci";
            $pdo->exec($sql);
            
            return true;
        } catch (PDOException $e) {
            error_log("Database Creation Error: " . $e->getMessage());
            // Provide a more user-friendly or specific message if possible
            $errorMsg = "فشل في إنشاء قاعدة البيانات `{$dbname}`. ";
            if (strpos(strtolower($e->getMessage()), 'access denied') !== false) {
                $errorMsg .= "يرجى التحقق من أن المستخدم `{$username}` لديه صلاحيات إنشاء قواعد بيانات.";
            } else {
                $errorMsg .= "الخطأ: " . $e->getMessage();
            }
            throw new Exception($errorMsg);
        }
    }

    /**
     * الحصول على آخر خطأ
     */
    public function getError() {
        return $this->error;
    }

    /**
     * إغلاق الاتصال
     */
    public function close() {
        $this->pdo = null;
    }

    /**
     * Get the PDO instance, e.g., for specific operations not covered by this class.
     * Use with caution.
     */
    public function getPdo() {
        return $this->pdo;
    }
}
?>
