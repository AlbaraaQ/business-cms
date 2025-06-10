<?php
/**
 * صفحة التثبيت - حداد جده
 * 
 * واجهة رسومية لتثبيت النظام وإعداد قاعدة البيانات
 */

// Configuration
$config_file_path = '../config/config.php';
$install_script_name = basename(__FILE__); // e.g., install.php
$logs_dir = dirname(__DIR__) . '/logs/';

// Ensure logs directory exists and is writable for error logging
if (!is_dir($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}
if (!is_writable($logs_dir)) {
    // If logs dir is not writable, this error won't be logged to file, but will show on screen if display_errors is on.
    // Consider a fallback or just proceed. For installation, basic error display might be enough.\n}


// Prevent access if the config file already exists, means installation is done.\nif (file_exists($config_file_path)) {
    // Determine redirect URL (prefer admin, fallback to site root)
    $admin_url = rtrim(dirname(dirname($_SERVER['REQUEST_URI'])), '/\\\\') . '/admin/';
    $site_url = rtrim(dirname(dirname($_SERVER['REQUEST_URI'])), '/\\\\') . '/';
    
    // Simple message before redirecting, or just redirect.\n    // echo "النظام مثبت بالفعل. يتم توجيهك...";
    // header("Refresh:3; url=" . $admin_url); 
    // For now, direct redirect to admin:\n    header('Location: ' . $admin_url);
    exit;
}

// Include necessary files (Database class, helper functions)\n// These are needed for the installation process itself.\nrequire_once '../core/Database.php'; 
require_once '../includes/functions.php'; // Needed for sanitize_input, redirect etc.

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success_messages = []; // To store success messages like file rename
$form_data = $_POST; // Store POST data for repopulating form

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) { // Assuming single step installation for now
        // --- Server-side validation ---\n       
          $required_fields = ['db_host', 'db_name', 'db_user', 'site_name', 'admin_username', 'admin_password', 'admin_password_confirm'];
        
        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                $errors[] = "الحقل '" . htmlspecialchars(str_replace('_', ' ', $field)) . "' مطلوب.";
            } else {
                // Sanitize inputs that will be used or displayed
                $form_data[$field] = sanitize_input($form_data[$field]);
            }
        }
        // Sanitize non-required fields if they exist
        $form_data['db_pass'] = isset($form_data['db_pass']) ? sanitize_input($form_data['db_pass']) : ''; // Password can be empty
        $form_data['site_tagline'] = isset($form_data['site_tagline']) ? sanitize_input($form_data['site_tagline']) : '';
        $form_data['site_description'] = isset($form_data['site_description']) ? sanitize_input($form_data['site_description']) : '';


        if (!empty($form_data['admin_password']) && $form_data['admin_password'] !== $form_data['admin_password_confirm']) {
            $errors[] = "كلمتا المرور للمدير غير متطابقتين.";
        }
        
        if (!empty($form_data['admin_password']) && strlen($form_data['admin_password']) < 8) {
            $errors[] = "كلمة مرور المدير يجب أن تكون 8 أحرف على الأقل.";
        }

        // --- Database and Installation Logic ---\n        
        if (empty($errors)) {
            $pdo_conn = null; // Initialize PDO connection variable for database operations
            try {
                // 1. Create the database if it doesn't exist
                // Database::createDatabase throws specific exception on failure
             //   Database::createDatabase($form_data['db_host'], $form_data['db_user'], $form_data['db_pass'], $form_data['db_name']);
                $success_messages[] = "تم إنشاء قاعدة البيانات '{$form_data['db_name']}' بنجاح أو كانت موجودة بالفعل.";

                // 2. Connect to the specific database for table creation and data insertion
                $dsn = "mysql:host={$form_data['db_host']};dbname={$form_data['db_name']};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                $pdo_conn = new PDO($dsn, $form_data['db_user'], $form_data['db_pass'], $options);
                $success_messages[] = "تم الاتصال بقاعدة البيانات '{$form_data['db_name']}' بنجاح.";

                // 3. Create database tables using the DDL
                createDatabaseTables($pdo_conn, $form_data); // Pass form_data for potential use in initial data
                $success_messages[] = "تم إنشاء جداول قاعدة البيانات بنجاح.";

                // 4. Create the admin user
                createAdminUser($pdo_conn, $form_data);
                $success_messages[] = "تم إنشاء حساب المدير بنجاح.";
                
                // 5. Insert initial site settings (some from form, others default)\n                insertInitialSettings($pdo_conn, $form_data);
                $success_messages[] = "تم حفظ إعدادات الموقع الأولية.";
                
                // 6. Create config file
                $config_content = createConfigFile($form_data);
                if (!file_put_contents($config_file_path, $config_content)) {
                     throw new Exception("فشل في كتابة ملف الإعداد '{$config_file_path}'. يرجى التحقق من أذونات الكتابة على المجلد '../config/'.");
                }                $success_messages[] = "تم إنشاء ملف الإعداد '{$config_file_path}' بنجاح.";

                // --- Post-installation cleanup ---\n                // 7. Rename or delete install.php for security
                $install_file_full_path = __FILE__; // Full path to the current install.php
                $renamed_file_path = $install_file_full_path . '.installed';

                if (@rename($install_file_full_path, $renamed_file_path)) {
                    $success_messages[] = "تمت إعادة تسمية ملف التثبيت ({$install_script_name}) إلى ({$install_script_name}.installed) للأمان.";
                } else {
                    if (@unlink($install_file_full_path)) {
                        $success_messages[] = "تم حذف ملف التثبيت ({$install_script_name}) للأمان.";
                    } else {
                        $errors[] = "تحذير أمني: فشل في إعادة تسمية أو حذف ملف التثبيت ({$install_script_name}). يرجى حذفه أو إعادة تسميته يدوياً فوراً!";
                        log_error("CRITICAL: Failed to rename or delete installer script: {$install_file_full_path}", __FILE__, __LINE__);
                    }                }
                
                // All steps successful
                // No more errors array modifications beyond this point for this request
                
            } catch (PDOException $e) {
                 $errors[] = "خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage());
                 log_error("Installation PDOException: " . $e->getMessage(), __FILE__, $e->getLine());
                 if (file_exists($config_file_path) && count($success_messages) < 5) { // Crude check if config was written before error
                      @unlink($config_file_path); // Attempt to clean up config file if DB steps failed
                 }            } catch (Exception $e) {
                $errors[] = "خطأ أثناء التثبيت: " . htmlspecialchars($e->getMessage());
                 log_error("Installation Exception: " . $e->getMessage(), __FILE__, $e->getLine());
                 if (file_exists($config_file_path) && count($success_messages) < 5) {
                      @unlink($config_file_path);
                 }            }
        }    }}

/**
 * Creates the config file content.\n */
function createConfigFile($data) {
    // Determine SITE_URL
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Path relative to document root, removing /public/install.php or similar
    $script_path = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Up two levels from /public/install.php to get project root
    $site_url = rtrim($scheme . '://' . $host . $script_path, '/');


    $config = "<?php\\n";
    $config .= "/**\\n * ملف الإعداد - حداد جده\\n * تم إنشاؤه بواسطة معالج التثبيت في " . date('Y-m-d H:i:s') . "\\n */\\n\\n";
    $config .= "// إعدادات قاعدة البيانات\\n";
    $config .= "define('DB_HOST', '" . addslashes($data['db_host']) . "');\\n";
    $config .= "define('DB_NAME', '" . addslashes($data['db_name']) . "');\\n";
    $config .= "define('DB_USER', '" . addslashes($data['db_user']) . "');\\n";
    $config .= "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\\n"; // db_pass already sanitized
    $config .= "define('DB_CHARSET', 'utf8mb4');\\n\\n";
    $config .= "// إعدادات الموقع الأساسية\\n";
    $config .= "define('SITE_URL', '" . addslashes($site_url) . "');\\n";
    $config .= "define('SITE_NAME', '" . addslashes($data['site_name']) . "');\\n";
    $config .= "define('SITE_TAGLINE', '" . addslashes($data['site_tagline'] ?? 'خدمات احترافية في الأعمال المعدنية') . "');\\n\\n";
    $config .= "// مسارات الملفات والمجلدات (نسبية من جذر المشروع)\\n";
    $config .= "define('PROJECT_ROOT', dirname(__DIR__)); // المسار المطلق لجذر المشروع\\n";
    $config .= "define('UPLOADS_DIR_NAME', 'uploads'); // اسم مجلد الرفع\\n";
    $config .= "define('UPLOAD_PATH', PROJECT_ROOT . '/' . UPLOADS_DIR_NAME . '/');\\n";
    $config .= "define('UPLOAD_URL', SITE_URL . '/' . UPLOADS_DIR_NAME . '/');\\n\\n";
    $config .= "// إعدادات الأمان\\n";
    $config .= "define('ADMIN_SESSION_NAME', 'makkah_admin_session');\\n";
    $config .= "define('CSRF_TOKEN_NAME', 'csrf_token');\\n";
    $config .= "define('ADMIN_PANEL_LOCKED', false); // لتمكين/تعطيل الوصول للوحة التحكم\\n\\n";
    $config .= "// إعدادات التطبيق\\n";
    $config .= "define('TIMEZONE', 'Asia/Riyadh');\\n";
    $config .= "define('DEBUG_MODE', false); // Set to true for development\\n\\n";
    $config .= "// تعيين المنطقة الزمنية\\n";
    $config .= "if (defined('TIMEZONE')) {\\n";
    $config .= "    date_default_timezone_set(TIMEZONE);\\n";
    $config .= "}\\n\\n";
    $config .= "// بدء الجلسة إذا لم تكن مبدوءة (مهم للوحة التحكم)\\n";
    $config .= "if (session_status() === PHP_SESSION_NONE) {\\n";
    $config .= "    if (defined('ADMIN_SESSION_NAME')) {\\n";
    $config .= "        session_name(ADMIN_SESSION_NAME);\\n";
    $config .= "    }\\n";
    $config .= "    session_start();\\n";
    $config .= "}\\n\\n";
    $config .= "// تعيين ترميز الأحرف\\n";
    $config .= "mb_internal_encoding('UTF-8');\\n";
    $config .= "mb_http_output('UTF-8');\\n";
    $config .= "\\n// Ensure uploads directory exists\\n";
    $config .= "if (!is_dir(UPLOAD_PATH)) {\\n";
    $config .= "    @mkdir(UPLOAD_PATH, 0755, true);\\n";
    $config .= "}\\n";
    $config .= "?>";
    
    return $config;
}

/**
 * Creates database tables using DDL from additional_context.\n * Requires a valid PDO connection to the target database.\n */
function createDatabaseTables($pdo, $formData) {
    // DDL based on `additional_context` (VOeoq) and modifications\n   
     $sql = "\nSET NAMES utf8mb4;\nSET CHARACTER SET utf8mb4;\nSET collation_connection = 'utf8mb4_unicode_ci';\n\nCREATE TABLE IF NOT EXISTS `users` (\n  `user_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `username` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,\n  `password_hash` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `role` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`user_id`),\n  INDEX `idx_username` (`username`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول مستخدمي لوحة التحكم';\n\nCREATE TABLE IF NOT EXISTS `settings` (\n  `setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `site_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `site_tagline` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `site_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `contact_phone` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `contact_email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `contact_address` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `whatsapp_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `instagram_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `twitter_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `facebook_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `footer_text` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `map_location_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `map_lat` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `map_lng` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `map_api_key` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `site_logo_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `site_favicon_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `enabled_frontend_sections` JSON DEFAULT NULL COMMENT 'JSON object for toggling main frontend sections visibility',\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`setting_id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الإعدادات العامة للموقع';\n\nCREATE TABLE IF NOT EXISTS `homepage_sections` (\n  `section_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `section_type` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `section_order` INT(11) UNSIGNED NOT NULL,\n  `is_visible` BOOLEAN NOT NULL DEFAULT TRUE,\n  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `subtitle` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `background_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `content` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `data_attributes` JSON DEFAULT NULL,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`section_id`),\n  INDEX `idx_section_order` (`section_order`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول أقسام الصفحة الرئيسية الديناميكية';\n\nCREATE TABLE IF NOT EXISTS `services` (\n  `service_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `slug` VARCHAR(255) COLLATE utf8mb4_unicode_ci UNIQUE,\n  `short_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `full_description` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `icon` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,\n  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`service_id`),\n  INDEX `idx_order` (`order`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الخدمات';\n\nCREATE TABLE IF NOT EXISTS `projects` (\n  `project_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `slug` VARCHAR(255) COLLATE utf8mb4_unicode_ci UNIQUE,\n  `short_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `full_description` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `main_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `category` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `completion_date` DATE DEFAULT NULL,\n  `client_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `location` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,\n  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`project_id`),\n  INDEX `idx_category` (`category`),\n  INDEX `idx_order` (`order`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المشاريع';\n\nCREATE TABLE IF NOT EXISTS `project_images` (\n  `image_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `project_id` INT(11) UNSIGNED NOT NULL,\n  `image_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `caption` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  PRIMARY KEY (`image_id`),\n  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE,\n  INDEX `idx_project_order` (`project_id`, `order`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول صور المشاريع الإضافية';\n\nCREATE TABLE IF NOT EXISTS `testimonials` (\n  `testimonial_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `client_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `client_title_company` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `feedback` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,\n  `client_photo` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `rating` INT(11) UNSIGNED DEFAULT NULL,\n  `is_approved` BOOLEAN NOT NULL DEFAULT FALSE,\n  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`testimonial_id`),\n  INDEX `idx_order` (`order`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول آراء العملاء';\n\nCREATE TABLE IF NOT EXISTS `facts` (\n  `fact_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n  `homepage_section_id` INT(11) UNSIGNED DEFAULT NULL,\n  `icon` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `number` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `prefix` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `suffix` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`fact_id`),\n  FOREIGN KEY (`homepage_section_id`) REFERENCES `homepage_sections`(`section_id`) ON DELETE SET NULL,\n  INDEX `idx_order` (`order`),\n  INDEX `fk_homepage_section_id` (`homepage_section_id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول بيانات قسم الحقائق';\n    \";
    
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }   }    
    // --- Insert Demo Data ---
         $stmt_section_check = $pdo->prepare("SELECT COUNT(*) FROM `homepage_sections` WHERE section_type = ?");
    
    $stmt_section_check->execute(['facts_counter']); // Updated from 'facts' to 'facts_counter'
    if ($stmt_section_check->fetchColumn() == 0) {
        $stmt_insert_section = $pdo->prepare("INSERT INTO `homepage_sections` (section_type, section_order, title, is_visible, content) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert_section->execute(['facts_counter', 1, 'حقائق وأرقام', 1, 'بعض الأرقام التي تعكس إنجازاتنا.']);
        $facts_section_id = $pdo->lastInsertId();

        $stmt_facts = $pdo->prepare("INSERT INTO `facts` (homepage_section_id, title, number, `order`, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt_facts->execute([$facts_section_id, 'أعمال منجزة', '651', 1, 'briefcase']); 
        $stmt_facts->execute([$facts_section_id, 'عميل راضٍ', '163', 2, 'smile']);
        $stmt_facts->execute([$facts_section_id, 'مشروع كبير', '90', 3, 'award']);
    }
    $stmt_section_check->execute(['services_overview']); // Updated from 'services_list' to 'services_overview'
    if ($stmt_section_check->fetchColumn() == 0) {
         $max_order_stmt = $pdo->query("SELECT MAX(section_order) FROM `homepage_sections`");
         $max_order = $max_order_stmt->fetchColumn() ?? 0;
         $stmt_insert_section = $pdo->prepare("INSERT INTO `homepage_sections` (section_type, section_order, title, subtitle, is_visible) VALUES (?, ?, ?, ?, ?)");
         $stmt_insert_section->execute(['services_overview', $max_order + 1, 'خدماتنا المميزة', 'نقدم مجموعة واسعة من خدمات الحدادة والكلادنج.', 1]);
    }
    $stmt_service_check = $pdo->query("SELECT COUNT(*) FROM `services`");
    if ($stmt_service_check->fetchColumn() == 0) {
        $stmt_insert_service = $pdo->prepare("INSERT INTO `services` (title, slug, short_description, full_description, icon, is_active, `order`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert_service->execute([
            'تصنيع الأبواب المعدنية', 'metal-doors', 'أبواب حديد قوية وآمنة بتصاميم عصرية وكلاسيكية.', 'نقدم خدمات تصنيع وتركيب جميع أنواع الأبواب المعدنية الخارجية والداخلية، مع ضمان الجودة والمتانة.', 'shield', 1, 1
        ]);
        $stmt_insert_service->execute([
            'أعمال الدرابزين والسور', 'railings-fences', 'درابزين وسور حديد مشغول وفورجيه للفلل والمباني.', 'نصمم ونصنع درابزين للسلالم والشرفات، بالإضافة إلى سور وبوابات خارجية بأشكال متنوعة وجودة عالية.', 'bar-chart-2', 1, 2
        ]);
         $stmt_insert_service->execute([
            'تركيب الكلادنج', 'cladding-installation', 'واجهات كلادنج حديثة للمباني التجارية والسكنية.', 'متخصصون في تركيب واجهات الكلادنج المقاومة للعوامل الجوية بألوان وتصاميم متعددة.', 'layers', 1, 3
        ]);
    }
    $stmt_testimonial_check = $pdo->query("SELECT COUNT(*) FROM `testimonials`");
    if ($stmt_testimonial_check->fetchColumn() == 0) {
        $stmt_insert_testimonial = $pdo->prepare("INSERT INTO `testimonials` (client_name, client_title_company, feedback, rating, is_approved, `order`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert_testimonial->execute([
            'م. خالد الأحمدي', 'مقاول مشروع جدة', 'تعامل احترافي وجودة عالية في التنفيذ. أوصي بهم بشدة.', 5, 1, 1
        ]);
         $stmt_insert_testimonial->execute([
            'شركة البناء الحديث', 'مدير المشاريع', 'خدمة ممتازة ودقة في المواعيد. سعداء بالتعامل معهم في مشاريعنا القادمة.', 4, 0, 2 // Example unapproved
        ]);
    }}
}
/**
 * Inserts initial site settings into the database.\n */

function insertInitialSettings($pdo, $formData) {
    $stmt_check = $pdo->query("SELECT COUNT(*) FROM `settings`");
    if ($stmt_check->fetchColumn() == 0) {
        $default_enabled_sections = json_encode([
            "hero" => true, "about" => true, "services_overview" => true, "projects_showcase" => true, 
            "testimonials_slider" => true, "facts_counter" => true, "contact_info" => true, "map_embed" => true
        ]);

        $sql = "INSERT INTO `settings` (site_name, site_tagline, site_description, contact_phone, contact_email, footer_text, enabled_frontend_sections) \n                VALUES (:site_name, :site_tagline, :site_description, :contact_phone, :contact_email, :footer_text, :enabled_frontend_sections)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':site_name' => $formData['site_name'] ?? 'حداد جده',
            ':site_tagline' => $formData['site_tagline'] ?? 'خدمات احترافية في الأعمال المعدنية',
            ':site_description' => $formData['site_description'] ?? 'نقدم خدمات احترافية في جميع أنواع الأعمال المعدنية والكلادنج بجودة عالية ودقة في التنفيذ.',
            ':contact_phone' => $formData['contact_phone'] ?? '0501234567', 
            ':contact_email' => $formData['contact_email'] ?? 'info@example.com', 
            ':footer_text' => '© ' . date('Y') . ' ' . ($formData['site_name'] ?? 'حداد جده') . '. جميع الحقوق محفوظة.',
            ':enabled_frontend_sections' => $default_enabled_sections
        ]);
    }}


/**
 * Creates the initial admin user.\n */
function createAdminUser($pdo, $data) {
    $password_hash = password_hash($data['admin_password'], PASSWORD_DEFAULT);
    
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
    $stmt_check->execute([$data['admin_username']]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("اسم المستخدم الإداري '{$data['admin_username']}' موجود بالفعل. يرجى اختيار اسم آخر.");
    }
    $sql = "INSERT INTO `users` (username, password_hash, role) VALUES (?, ?, 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['admin_username'], $password_hash]);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n    <title>تثبيت النظام - حداد جده</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }\n        .form-label { @apply block text-sm font-medium text-gray-700 mb-1; }\n        .form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500; }\n        .btn-primary { @apply bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors w-full disabled:opacity-50; }\n        .alert { @apply p-4 mb-4 border rounded-md; }\n        .alert-danger { @apply bg-red-50 border-red-300 text-red-700; }\n        .alert-success { @apply bg-green-50 border-green-300 text-green-700; }\n        .alert-info { @apply bg-blue-50 border-blue-300 text-blue-700; }\n         /* Custom scrollbar for better aesthetics if content overflows */\n        ::-webkit-scrollbar { width: 8px; height: 8px; }\n        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }\n        ::-webkit-scrollbar-thumb { background: #fda4af; border-radius: 10px; } /* Tailwind pink-300 */\n        ::-webkit-scrollbar-thumb:hover { background: #f472b6; } /* Tailwind pink-400 */\n    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        primary: '#fb2056', // Main pink\n                        'primary-dark': '#da1c4b', // Darker pink\n                        pink: { // Full Tailwind pink palette for utility classes
                            50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af',
                            400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c',
                            800: '#9f1239', 900: '#881337', 950: '#4c0519'
                        }\n                    }\n                }\n            }\n        }\n    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="container mx-auto max-w-2xl">
        <div class="bg-white shadow-xl rounded-xl p-6 sm:p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                 <div class="w-20 h-20 bg-gradient-to-br from-pink-500 to-pink-700 rounded-full mx-auto mb-3 flex items-center justify-center shadow-md">
                    <svg class="w-10 h-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3" />
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">معالج تثبيت النظام</h1>
                <p class="text-gray-600 mt-1 text-sm sm:text-base">حداد جده - نظام إدارة المحتوى</p>
            </div>

            <?php if (!empty($success_messages) && empty($errors)): ?>
                <!-- Success Message -->
                <div class="alert alert-success">
                    <h3 class="text-lg font-semibold mb-2">تم التثبيت بنجاح!</h3>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($success_messages as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-6 space-y-2 sm:space-y-0 sm:flex sm:space-x-3 sm:space-x-reverse">
                        <a href="<?php echo rtrim(dirname(dirname($_SERVER['REQUEST_URI'])), '/\\\\') . '/admin/'; ?>" class="block text-center bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors w-full sm:w-auto">
                            الذهاب إلى لوحة التحكم
                        </a>
                        <a href="<?php echo rtrim(dirname(dirname($_SERVER['REQUEST_URI'])), '/\\\\') . '/'; ?>" class="block text-center bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors w-full sm:w-auto">
                            عرض الموقع
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Installation Form -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h3 class="text-lg font-semibold mb-2">حدث خطأ!</h3>
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; /* Already HTML escaped or is safe */ ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                 <?php if (!empty($success_messages) && !empty($errors)): // Show partial successes if any ?>
                    <div class="alert alert-info">
                        <h3 class="text-lg font-semibold mb-2">ملاحظات العملية:</h3>
                        <ul class="list-disc list-inside">
                            <?php foreach ($success_messages as $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>


                <form method="post" action="<?php echo htmlspecialchars($install_script_name); ?>?step=1" class="space-y-6" id="installForm">
                    
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-3">إعدادات قاعدة البيانات</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">
                            <div>
                                <label for="db_host" class="form-label">مضيف قاعدة البيانات <span class="text-red-500">*</span></label>
                                <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($form_data['db_host'] ?? 'localhost'); ?>" required class="form-input">
                            </div>
                            <div>
                                <label for="db_name" class="form-label">اسم قاعدة البيانات <span class="text-red-500">*</span></label>
                                <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($form_data['db_name'] ?? ''); ?>" required class="form-input">
                            </div>
                            <div>
                                <label for="db_user" class="form-label">اسم مستخدم قاعدة البيانات <span class="text-red-500">*</span></label>
                                <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($form_data['db_user'] ?? ''); ?>" required class="form-input">
                            </div>
                            <div>
                                <label for="db_pass" class="form-label">كلمة مرور قاعدة البيانات</label>
                                <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($form_data['db_pass'] ?? ''); ?>" class="form-input">
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-3">معلومات الموقع</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="site_name" class="form-label">اسم الموقع <span class="text-red-500">*</span></label>
                                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($form_data['site_name'] ?? 'حداد جده'); ?>" required class="form-input">
                            </div>
                            <div>
                                <label for="site_tagline" class="form-label">شعار الموقع (وصف قصير)</label>
                                <input type="text" id="site_tagline" name="site_tagline" value="<?php echo htmlspecialchars($form_data['site_tagline'] ?? 'أعمال حدادة وتركيب كلادنج احترافية'); ?>" class="form-input">
                            </div>
                            <div>
                                <label for="site_description" class="form-label">وصف الموقع (للـ SEO)</label>
                                <textarea id="site_description" name="site_description" rows="3" class="form-input"><?php echo htmlspecialchars($form_data['site_description'] ?? 'نقدم أفضل خدمات الحدادة والكلادنج في جده، جودة عالية وأسعار تنافسية.'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-700 mb-3">إنشاء حساب المدير</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">
                            <div>
                                <label for="admin_username" class="form-label">اسم مستخدم المدير <span class="text-red-500">*</span></label>
                                <input type="text" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($form_data['admin_username'] ?? 'admin'); ?>" required class="form-input" pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 حرف إنجليزي أو رقم أو _">
                            </div>
                            <div>
                                <!-- Empty div for spacing or future use -->
                            </div>
                            <div>
                                <label for="admin_password" class="form-label">كلمة مرور المدير <span class="text-red-500">*</span></label>
                                <input type="password" id="admin_password" name="admin_password" required minlength="8" class="form-input">
                            </div>
                            <div>
                                <label for="admin_password_confirm" class="form-label">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8" class="form-input">
                            </div>
                        </div>
                    </div>

                    <div class="pt-5">
                        <button type="submit" id="submitBtn" class="btn-primary">
                            <svg class="inline-block w-5 h-5 mr-2 animate-spin hidden" id="loadingSpinner" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm0 14c-3.314 0-6-2.686-6-6s2.686-6 6-6 6 2.686 6 6-2.686 6-6 6zm-1-9v4h2V8h-2z"></path> </svg>
                            <span id="submitBtnText">بدء التثبيت</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center text-xs text-gray-500">
                <p>نظام إدارة المحتوى - حداد جده</p>
                <p>الإصدار 1.0.0 | PHP <?php echo PHP_VERSION; ?></p>
            </div>
        </div>
    </div>

    <script>
        const installForm = document.getElementById('installForm');
        if (installForm) {
            const password = document.getElementById('admin_password');
            const confirmPassword = document.getElementById('admin_password_confirm');
            const submitBtn = document.getElementById('submitBtn');
            const loadingSpinner = document.getElementById('loadingSpinner'); 
            const submitBtnText = document.getElementById('submitBtnText');


            function validatePasswords() {
                if (password.value !== confirmPassword.value && confirmPassword.value.length > 0) {
                    confirmPassword.setCustomValidity('كلمتا المرور غير متطابقتين');
                    confirmPassword.classList.add('border-red-500');
                    confirmPassword.classList.remove('border-green-500');
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.classList.remove('border-red-500');
                    if (confirmPassword.value.length > 0) {
                         confirmPassword.classList.add('border-green-500');   
                    } else {
                         confirmPassword.classList.remove('border-green-500');   
                    }\n                }
            }\n
            if (password && confirmPassword) {
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);

                password.addEventListener('input', function() {
                    if (password.value.length > 0 && password.value.length < 8) {
                        password.setCustomValidity('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
                        password.classList.add('border-red-500');
                        password.classList.remove('border-green-500');
                    } else {
                        password.setCustomValidity('');
                        password.classList.remove('border-red-500');
                        if(password.value.length >= 8) password.classList.add('border-green-500'); else password.classList.remove('border-green-500');
                    }\n                });
            }\n            
            installForm.addEventListener('submit', function() {
                if (submitBtn && loadingSpinner && submitBtnText) {
                    submitBtn.disabled = true;
                    loadingSpinner.style.display = 'inline-block';
                    submitBtnText.textContent = 'جاري التثبيت...';
                }\n            });
        }\n    </script>
</body>
</html>
