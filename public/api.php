<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تعريف المسار الجذري
define('ROOT_PATH', dirname(__DIR__));

// تحميل ملفات التهيئة
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/includes/functions.php';

// تسجيل طلبات API
file_put_contents(ROOT_PATH . '/logs/api_requests.log', 
    date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n", 
    FILE_APPEND);

// الدوال المساعدة
if (!function_exists('send_json_response')) {
    function send_json_response($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('log_error')) {
    function log_error($message) {
        $log_file = ROOT_PATH . '/logs/api_errors.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

// إعدادات الجلسة والأمان
if (session_status() == PHP_SESSION_NONE) {
    session_name(APP_SESSION_NAME ?? 'APP_SESSION');
    session_start();
}

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// الاتصال بقاعدة البيانات
try {
    $db = new Database();
    if (!$db->isConnected()) {
        send_json_response(['success' => false, 'message' => 'Database connection failed'], 500);
    }
} catch (PDOException $e) {
    log_error("Database connection failed: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Database connection error'], 500);
}

// الحصول على الإجراء المطلوب
$action = sanitize_input($_GET['action'] ?? '');

// تسجيل الإجراء المطلوب
file_put_contents(ROOT_PATH . '/logs/api_actions.log', 
    date('Y-m-d H:i:s') . " - Action: $action\n", 
    FILE_APPEND);

try {
    switch ($action) {
        case 'get_site_layout_data':
            // Fetch settings from new key-value table
            $settings_result = $db->query("SELECT setting_name, setting_value FROM settings");
            $site_settings_assoc = [];
            if ($settings_result) {
                foreach ($settings_result as $row) {
                    $site_settings_assoc[$row['setting_name']] = $row['setting_value'];
                }
            }

            // Decode enabled_frontend_sections if it exists
            if (isset($site_settings_assoc['enabled_frontend_sections'])) {
                $site_settings_assoc['enabled_frontend_sections'] = json_decode($site_settings_assoc['enabled_frontend_sections'], true) ?: [];
            }
            
            // Fetch generic sections
            $sections = $db->query("SELECT id, name, content, image_url, video_url FROM sections ORDER BY created_at ASC") ?: [];
            // Note: The old logic of fetching specific data (services, projects, etc.) per section_type within this loop is removed.
            // The frontend (main.js) will now be responsible for how it uses the generic sections and the global data lists.

            // Fetch all data types that might be displayed in sections or globally
            $all_services = $db->query(
                "SELECT id, name, description, icon_class FROM services ORDER BY created_at DESC LIMIT 6"
            ) ?: [];

            $all_projects = $db->query(
                "SELECT id, title, description, image_url, project_url FROM projects ORDER BY created_at DESC LIMIT 6"
            ) ?: [];

            $all_testimonials = $db->query(
                "SELECT id, author_name, testimonial_text, author_image_url FROM testimonials ORDER BY created_at DESC LIMIT 5"
            ) ?: [];
            
            $all_facts = $db->query(
                "SELECT id, fact_text, fact_value, icon_class FROM facts ORDER BY id ASC" // Or some other meaningful order
            ) ?: [];
            
            // Fetch services summary for footer (using new schema)
            $services_summary = $db->query(
                "SELECT id, name, icon_class FROM services ORDER BY created_at DESC LIMIT 4"
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'settings' => $site_settings_assoc, // Use the new associative array
                    'sections' => $sections,
                    'all_services' => $all_services,
                    'all_projects' => $all_projects,
                    'all_testimonials' => $all_testimonials,
                    'all_facts' => $all_facts,
                    'services_summary' => $services_summary
                ]
            ]);
            break;

        case 'get_services':
            $limit = (int)($_GET['limit'] ?? 6);
            $services = $db->query(
                "SELECT id, name, description, icon_class
                 FROM services 
                 ORDER BY created_at DESC LIMIT :limit",
                [':limit' => $limit]
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'services' => $services
                ]
            ]);
            break;

        case 'get_service_details':
            $service_id = (int)($_GET['id'] ?? 0); // Expect 'id'
            if ($service_id <= 0) {
                send_json_response(['success' => false, 'message' => 'Service ID is required and must be a number'], 400);
            }
            
            $service = $db->queryOne(
                "SELECT id, name, description, icon_class
                 FROM services 
                 WHERE id = :id",
                [':id' => $service_id]
            );
            
            if ($service) {
                send_json_response([
                    'success' => true,
                    'data' => [
                        'service' => $service
                    ]
                ]);
            } else {
                send_json_response(['success' => false, 'message' => 'Service not found'], 404);
            }
            break;

        case 'get_projects':
            $limit = (int)($_GET['limit'] ?? 6);
            $projects = $db->query(
                "SELECT id, title, description, image_url, project_url
                 FROM projects 
                 ORDER BY created_at DESC LIMIT :limit",
                [':limit' => $limit]
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'projects' => $projects
                ]
            ]);
            break;

        case 'get_project_details':
            $project_id = (int)($_GET['id'] ?? 0); // Expect 'id'
            if ($project_id <= 0) {
                send_json_response(['success' => false, 'message' => 'Project ID is required and must be a number.'], 400);
            }
            
            $project = $db->queryOne(
                "SELECT id, title, description, image_url, project_url
                 FROM projects 
                 WHERE id = :id",
                [':id' => $project_id]
            );
            
            if ($project) {
                // $project['additional_images'] logic removed
                send_json_response([
                    'success' => true,
                    'data' => [
                        'project' => $project // Contains only fields from the projects table
                    ]
                ]);
            } else {
                send_json_response(['success' => false, 'message' => 'Project not found'], 404);
            }
            break;

        case 'get_testimonials':
            $limit = (int)($_GET['limit'] ?? 6);
            $testimonials = $db->query(
                "SELECT id, author_name, testimonial_text, author_image_url
                 FROM testimonials 
                 ORDER BY created_at DESC LIMIT :limit",
                [':limit' => $limit]
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'testimonials' => $testimonials
                ]
            ]);
            break;

        case 'submit_testimonial':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                send_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            // CSRF verification should be added for POST requests if not already handled globally for API
            if (function_exists('verify_csrf_token') && !verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
                 send_json_response(['success' => false, 'message' => 'CSRF token validation failed.'], 403);
            }
            
            $required = ['author_name', 'testimonial_text']; // Changed from client_name, feedback
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    // Map form field names if they are different from DB columns
                    $form_field_name = $field;
                    if ($field === 'author_name' && isset($_POST['client_name'])) $form_field_name = 'client_name';
                    if ($field === 'testimonial_text' && isset($_POST['feedback'])) $form_field_name = 'feedback';

                    if (empty($_POST[$form_field_name])) {
                         send_json_response(['success' => false, 'message' => "$form_field_name is required"], 400);
                    }
                }
            }
            
            $data = [
                // Map form field `client_name` to `author_name`
                'author_name' => sanitize_input($_POST['client_name'] ?? $_POST['author_name']),
                // Map form field `feedback` to `testimonial_text`
                'testimonial_text' => sanitize_input($_POST['feedback'] ?? $_POST['testimonial_text']),
                // author_image_url is not handled here as public form usually doesn't upload images for testimonials
                // created_at and updated_at will be set by NOW()
            ];
            
            $db->execute(
                "INSERT INTO testimonials (author_name, testimonial_text, created_at, updated_at)
                VALUES (:author_name, :testimonial_text, NOW(), NOW())",
                $data
            );
            
            send_json_response([
                'success' => true,
                'message' => 'شكراً لك! تم استلام رأيك وسيتم مراجعته قريباً.'
            ]);
            break;

        default:
            send_json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (PDOException $e) {
    log_error("Database error in action $action: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    log_error("General error in action $action: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'An error occurred'], 500);
}