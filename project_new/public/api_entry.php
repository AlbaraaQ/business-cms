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
            // جلب الإعدادات
            $settings = $db->queryOne("SELECT * FROM settings LIMIT 1") ?: [];
            
            // تحويل JSON strings إلى arrays
            if (isset($settings['enabled_frontend_sections'])) {
                $settings['enabled_frontend_sections'] = json_decode($settings['enabled_frontend_sections'], true) ?: [];
            }
            
            // جلب أقسام الصفحة الرئيسية
            $sections = $db->query("SELECT * FROM homepage_sections WHERE is_visible = 1 ORDER BY section_order ASC") ?: [];
            
            foreach ($sections as &$section) {
                if (!empty($section['data_attributes'])) {
                    $section['data_attributes'] = json_decode($section['data_attributes'], true) ?: [];
                }
                
                // جلب البيانات الخاصة بكل قسم
                switch ($section['section_type']) {
                    case 'services_overview':
                        $limit = $section['data_attributes']['limit'] ?? 6;
                        $section['data_attributes']['services'] = $db->query(
                            "SELECT service_id, title, slug, short_description, image 
                             FROM services WHERE is_active = 1 
                             ORDER BY `order` ASC LIMIT ?", 
                            [$limit]
                        ) ?: [];
                        break;
                        
                    case 'projects_showcase':
                        $limit = $section['data_attributes']['limit'] ?? 6;
                        $section['data_attributes']['projects'] = $db->query(
                            "SELECT project_id, title, slug, short_description, main_image, category 
                             FROM projects WHERE is_active = 1 
                             ORDER BY `order` ASC LIMIT ?", 
                            [$limit]
                        ) ?: [];
                        break;
                        
                    case 'testimonials_slider':
                        $limit = $section['data_attributes']['limit'] ?? 5;
                        $section['data_attributes']['testimonials'] = $db->query(
                            "SELECT testimonial_id, client_name, client_photo, feedback, rating, client_title_company 
                             FROM testimonials WHERE is_approved = 1 
                             ORDER BY `order` ASC LIMIT ?", 
                            [$limit]
                        ) ?: [];
                        break;
                        
                    case 'facts_counter':
                        $section['data_attributes']['facts'] = $db->query(
                            "SELECT fact_id, title, number, icon, prefix, suffix 
                             FROM facts 
                             ORDER BY `order` ASC"
                        ) ?: [];
                        break;
                }
            }
            
            // جلب ملخص الخدمات للفوتر
            $services_summary = $db->query(
                "SELECT service_id, title, slug 
                 FROM services 
                 WHERE is_active = 1 
                 ORDER BY `order` ASC LIMIT 4"
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'sections' => $sections,
                    'services_summary' => $services_summary
                ]
            ]);
            break;

        case 'get_services':
            $limit = (int)($_GET['limit'] ?? 6);
            $services = $db->query(
                "SELECT service_id, title, slug, short_description, image 
                 FROM services 
                 WHERE is_active = 1 
                 ORDER BY `order` ASC LIMIT ?", 
                [$limit]
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'services' => $services
                ]
            ]);
            break;

        case 'get_service_details':
            $slug = sanitize_input($_GET['slug'] ?? '');
            if (empty($slug)) {
                send_json_response(['success' => false, 'message' => 'Service slug is required'], 400);
            }
            
            $service = $db->queryOne(
                "SELECT service_id, title, slug, short_description, full_description, image 
                 FROM services 
                 WHERE slug = ? AND is_active = 1", 
                [$slug]
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
                "SELECT project_id, title, slug, short_description, main_image, category 
                 FROM projects 
                 WHERE is_active = 1 
                 ORDER BY `order` ASC LIMIT ?", 
                [$limit]
            ) ?: [];
            
            send_json_response([
                'success' => true,
                'data' => [
                    'projects' => $projects
                ]
            ]);
            break;

        case 'get_project_details':
            $slug = sanitize_input($_GET['slug'] ?? '');
            if (empty($slug)) {
                send_json_response(['success' => false, 'message' => 'Project slug is required'], 400);
            }
            
            $project = $db->queryOne(
                "SELECT project_id, title, slug, short_description, full_description, 
                        main_image, category, completion_date, client_name, location 
                 FROM projects 
                 WHERE slug = ? AND is_active = 1", 
                [$slug]
            );
            
            if ($project) {
                $project['additional_images'] = $db->query(
                    "SELECT image_id, image_path, caption 
                     FROM project_images 
                     WHERE project_id = ?", 
                    [$project['project_id']]
                ) ?: [];
                
                send_json_response([
                    'success' => true,
                    'data' => [
                        'project' => $project
                    ]
                ]);
            } else {
                send_json_response(['success' => false, 'message' => 'Project not found'], 404);
            }
            break;

        case 'get_testimonials':
            $limit = (int)($_GET['limit'] ?? 6);
            $testimonials = $db->query(
                "SELECT testimonial_id, client_name, client_photo, feedback, rating, client_title_company 
                 FROM testimonials 
                 WHERE is_approved = 1 
                 ORDER BY `order` ASC LIMIT ?", 
                [$limit]
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
            
            $required = ['client_name', 'feedback'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    send_json_response(['success' => false, 'message' => "$field is required"], 400);
                }
            }
            
            $data = [
                'client_name' => sanitize_input($_POST['client_name']),
                'client_title_company' => sanitize_input($_POST['client_title_company'] ?? ''),
                'feedback' => sanitize_input($_POST['feedback']),
                'rating' => isset($_POST['rating']) ? (int)$_POST['rating'] : null,
                'is_approved' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->execute(
                "INSERT INTO testimonials 
                (client_name, client_title_company, feedback, rating, is_approved, created_at) 
                VALUES (:client_name, :client_title_company, :feedback, :rating, :is_approved, :created_at)",
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