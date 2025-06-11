<?php
/**
 * ملف معالج AJAX لإدارة الصور والعمليات المتقدمة
 * 
 * هذا الملف يتعامل مع طلبات AJAX لرفع الصور المتعددة وترتيبها وحذفها
 * ويدعم عمليات السحب والإفلات والتحميل المتعدد، بالإضافة إلى عمليات CRUD أخرى.
 */

// تضمين ملف التهيئة
require_once __DIR__ . '/init.php'; // Defines $db, loads functions.php (for sanitize_input, send_json_response etc.)

// التحقق من تسجيل دخول المدير
if (!is_admin_logged_in()) {
    // For AJAX, always respond with JSON
    send_json_response(['success' => false, 'message' => 'غير مصرح لك بالوصول'], 403);
    exit;
}

// تحديد العملية المطلوبة
// Use $_REQUEST to allow GET for some read-only actions if desired, but POST for modifications
$action = sanitize_input($_REQUEST['action'] ?? '');

// CSRF token name, assuming it's defined in config.php and loaded by init.php
// Define a default if not set, though it should always be set.
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', 'csrf_token');
}

global $db; // Make $db global for all handler functions/cases

// معالجة العمليات
switch ($action) {
    // --- Image Handlers (Previously Refactored) ---
    case 'upload_images':
        handle_upload_images();
        break;
        
    case 'delete_image':
        handle_delete_image();
        break;
        
    case 'reorder_images':
        handle_reorder_images();
        break;
        
    case 'set_main_image':
        handle_set_main_image();
        break;

    // --- Sections Management ---
    case 'get_section_details':
        // CSRF not typically needed for GET/read-only actions
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            send_json_response(['success' => false, 'message' => 'معرف القسم غير صالح.']);
            break;
        }
        $section_id = (int)$_GET['id'];
        $section = $db->queryOne("SELECT * FROM homepage_sections WHERE section_id = :id", [':id' => $section_id]);
        if ($section) {
            // Attempt to decode JSON string from data_attributes, ensure it doesn't break if already array/object
            if (is_string($section['data_attributes'])) {
                $decoded_attributes = json_decode($section['data_attributes'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $section['data_attributes'] = $decoded_attributes;
                } else {
                    // Optionally log error or handle malformed JSON string
                    $section['data_attributes'] = null; // Or set to empty array []
                }
            }
            send_json_response(['success' => true, 'section' => $section]);
        } else {
            send_json_response(['success' => false, 'message' => 'لم يتم العثور على القسم.']);
        }
        break;

    case 'save_section':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }

        $section_id = (int)sanitize_input($_POST['section_id'] ?? 0);
        $data = [
            'title' => sanitize_input($_POST['title'] ?? ''),
            'subtitle' => sanitize_input($_POST['subtitle'] ?? null),
            'section_type' => sanitize_input($_POST['section_type'] ?? ''),
            'content' => $_POST['content'] ?? '', // Sanitize based on allowed HTML for this field by TinyMCE
            'data_attributes' => $_POST['data_attributes'] ?? null, // Expecting JSON string
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];

        if (empty($data['section_type'])) {
            send_json_response(['success' => false, 'message' => 'نوع القسم مطلوب.']);
            break;
        }

        // Validate data_attributes is valid JSON string or null
        if (!empty($data['data_attributes'])) {
            json_decode($data['data_attributes']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                send_json_response(['success' => false, 'message' => 'بيانات إضافية (JSON) غير صالحة.']);
                break;
            }
        } else {
            $data['data_attributes'] = null; // Store as NULL if empty
        }

        $existing_background_image = sanitize_input($_POST['existing_background_image'] ?? null);

        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            if ($section_id > 0) { // If updating, try to get current image to delete
                $old_section = $db->queryOne("SELECT background_image FROM homepage_sections WHERE section_id = :id", [':id' => $section_id]);
                 if ($old_section && !empty($old_section['background_image'])) {
                    delete_uploaded_file($old_section['background_image']);
                 }
            }
            $uploaded_path = handle_file_upload($_FILES['background_image'], 'sections');
            if ($uploaded_path) {
                $data['background_image'] = $uploaded_path;
            } else {
                send_json_response(['success' => false, 'message' => 'فشل رفع صورة الخلفية: ' . ($_FILES['background_image']['name'] ?? 'N/A')]);
                break;
            }
        } elseif (isset($_POST['remove_background_image']) && $_POST['remove_background_image'] == '1' && $section_id > 0) {
             $old_section = $db->queryOne("SELECT background_image FROM homepage_sections WHERE section_id = :id", [':id' => $section_id]);
             if ($old_section && !empty($old_section['background_image'])) {
                delete_uploaded_file($old_section['background_image']);
             }
            $data['background_image'] = null;
        } elseif ($section_id > 0 && !empty($existing_background_image) && (!isset($_POST['remove_background_image']) || $_POST['remove_background_image'] != '1')) {
            $data['background_image'] = $existing_background_image;
        }


        if ($section_id > 0) { // Update
            $data['updated_at'] = date('Y-m-d H:i:s');
            $set_clauses = [];
            $params_update = [];
            foreach ($data as $key => $value) {
                $set_clauses[] = "$key = :$key";
                $params_update[":$key"] = $value;
            }
            $params_update[':section_id_condition'] = $section_id;

            $sql = "UPDATE homepage_sections SET " . implode(', ', $set_clauses) . " WHERE section_id = :section_id_condition";

            if ($db->execute($sql, $params_update)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث القسم بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث القسم.']);
            }
        } else { // Insert
            $max_order_result = $db->queryOne("SELECT MAX(section_order) as max_o FROM homepage_sections");
            $data['section_order'] = ($max_order_result && $max_order_result['max_o'] !== null) ? $max_order_result['max_o'] + 1 : 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO homepage_sections ($columns) VALUES ($placeholders)";

            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة القسم بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة القسم.']);
            }
        }
        break;

    case 'reorder_sections':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $ordered_ids_json = $_POST['ordered_ids'] ?? '[]';
        $ordered_ids = json_decode($ordered_ids_json, true);

        if (is_array($ordered_ids)) {
            $pdo = $db->getPdo();
            $pdo->beginTransaction();
            try {
                foreach ($ordered_ids as $index => $id) {
                    $db->execute("UPDATE homepage_sections SET section_order = :order WHERE section_id = :id", [':order' => $index + 1, ':id' => (int)$id]);
                }
                $pdo->commit();
                send_json_response(['success' => true, 'message' => 'تم تحديث ترتيب الأقسام.']);
            } catch (Exception $e) {
                $pdo->rollBack();
                log_error("Reorder sections failed: " . $e->getMessage());
                send_json_response(['success' => false, 'message' => 'فشل تحديث الترتيب: ' . $e->getMessage()]);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'بيانات الترتيب غير صالحة.']);
        }
        break;

    case 'toggle_section_visibility':
         if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $section_id = (int)sanitize_input($_POST['section_id'] ?? 0);
        if ($section_id > 0) {
            $current = $db->queryOne("SELECT is_visible FROM homepage_sections WHERE section_id = :id", [':id' => $section_id]);
            if ($current) {
                $new_visibility = $current['is_visible'] ? 0 : 1;
                if ($db->execute("UPDATE homepage_sections SET is_visible = :visibility, updated_at = NOW() WHERE section_id = :id", [':visibility' => $new_visibility, ':id' => $section_id])) {
                    send_json_response(['success' => true, 'message' => 'تم تحديث حالة الظهور.', 'new_status' => $new_visibility]);
                } else {
                    send_json_response(['success' => false, 'message' => 'فشل تحديث حالة الظهور.']);
                }
            } else {
                send_json_response(['success' => false, 'message' => 'القسم غير موجود.']);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'معرف القسم غير صالح.']);
        }
        break;

    // --- Facts Management ---
    case 'get_fact_details':
        // CSRF not typically needed for GET/read-only actions
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            send_json_response(['success' => false, 'message' => 'معرف الحقيقة غير صالح.']);
            break;
        }
        $fact_id = (int)$_GET['id'];
        $fact = $db->queryOne("SELECT * FROM facts WHERE fact_id = :id", [':id' => $fact_id]);
        if ($fact) {
            send_json_response(['success' => true, 'fact' => $fact]);
        } else {
            send_json_response(['success' => false, 'message' => 'لم يتم العثور على الحقيقة.']);
        }
        break;

    case 'save_fact':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $fact_id = (int)sanitize_input($_POST['fact_id'] ?? 0);
        $data = [
            'title' => sanitize_input($_POST['title'] ?? ''),
            'number' => sanitize_input($_POST['number'] ?? ''), // Changed from 'value' to 'number' based on table structure
            'prefix' => sanitize_input($_POST['prefix'] ?? null),
            'suffix' => sanitize_input($_POST['suffix'] ?? null),
            'icon' => sanitize_input($_POST['icon'] ?? null),
            'order' => (int)sanitize_input($_POST['order'] ?? 0),
        ];

        if (empty($data['title']) || $data['number'] === '') { // Number can be '0'
            send_json_response(['success' => false, 'message' => 'العنوان والرقم مطلوبان.']);
            break;
        }

        if ($fact_id > 0) { // Update
            $sql = "UPDATE facts SET title = :title, `number` = :number, prefix = :prefix, suffix = :suffix, icon = :icon, `order` = :order WHERE fact_id = :fact_id_condition";
            $data['fact_id_condition'] = $fact_id;
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث الحقيقة بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث الحقيقة.']);
            }
        } else { // Insert
            $columns = implode(', ', array_map(function($key) { return "`$key`"; }, array_keys($data))); // Add backticks for 'order'
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO facts ($columns) VALUES ($placeholders)";
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة الحقيقة بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة الحقيقة.']);
            }
        }
        break;

    // --- Testimonials Management ---
    case 'get_testimonial_details':
        // CSRF not typically needed for GET/read-only actions
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            send_json_response(['success' => false, 'message' => 'معرف الرأي غير صالح.']);
            break;
        }
        $testimonial_id = (int)$_GET['id'];
        $testimonial = $db->queryOne("SELECT * FROM testimonials WHERE testimonial_id = :id", [':id' => $testimonial_id]);
        if ($testimonial) {
            send_json_response(['success' => true, 'testimonial' => $testimonial]);
        } else {
            send_json_response(['success' => false, 'message' => 'لم يتم العثور على الرأي.']);
        }
        break;

    case 'save_testimonial':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $testimonial_id = (int)sanitize_input($_POST['testimonial_id'] ?? 0);
        $data = [
            'client_name' => sanitize_input($_POST['client_name'] ?? ''),
            'client_title_company' => sanitize_input($_POST['client_title_company'] ?? null),
            'feedback' => sanitize_input($_POST['feedback'] ?? ''),
            'rating' => empty($_POST['rating']) ? null : (int)sanitize_input($_POST['rating']),
            'order' => (int)sanitize_input($_POST['order'] ?? 0),
            'is_approved' => isset($_POST['is_approved']) ? 1 : 0,
        ];

        if (empty($data['client_name']) || empty($data['feedback'])) {
             send_json_response(['success' => false, 'message' => 'اسم العميل ونص الرأي مطلوبان.']);
            break;
        }

        $existing_client_photo = sanitize_input($_POST['existing_client_photo'] ?? null);
        if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
            if ($testimonial_id > 0 && !empty($existing_client_photo)) {
                 $old_testimonial = $db->queryOne("SELECT client_photo FROM testimonials WHERE testimonial_id = :id", [':id' => $testimonial_id]);
                 if ($old_testimonial && !empty($old_testimonial['client_photo'])) { // Check if not empty
                    delete_uploaded_file($old_testimonial['client_photo']);
                 }
            }
            $uploaded_path = handle_file_upload($_FILES['client_photo'], 'testimonials');
            if ($uploaded_path) {
                $data['client_photo'] = $uploaded_path;
            } else {
                send_json_response(['success' => false, 'message' => 'فشل رفع صورة العميل.']);
                break;
            }
        } elseif (isset($_POST['remove_client_photo']) && $_POST['remove_client_photo'] == '1' && $testimonial_id > 0) {
             $old_testimonial = $db->queryOne("SELECT client_photo FROM testimonials WHERE testimonial_id = :id", [':id' => $testimonial_id]);
             if ($old_testimonial && !empty($old_testimonial['client_photo'])) {
                delete_uploaded_file($old_testimonial['client_photo']);
             }
            $data['client_photo'] = null;
        } elseif ($testimonial_id > 0 && !empty($existing_client_photo) && (!isset($_POST['remove_client_photo']) || $_POST['remove_client_photo'] != '1')) {
            $data['client_photo'] = $existing_client_photo;
        }


        if ($testimonial_id > 0) { // Update
            $data['updated_at'] = date('Y-m-d H:i:s');
            $set_clauses = [];
            $params_update = [];
            foreach ($data as $key => $value) {
                $set_clauses[] = "`$key` = :$key"; // Add backticks for 'order'
                $params_update[":$key"] = $value;
            }
            $params_update[':testimonial_id_condition'] = $testimonial_id;

            $sql = "UPDATE testimonials SET " . implode(', ', $set_clauses) . " WHERE testimonial_id = :testimonial_id_condition";
            if ($db->execute($sql, $params_update)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث الرأي بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث الرأي.']);
            }
        } else { // Insert
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $columns = implode(', ', array_map(function($key) { return "`$key`"; }, array_keys($data)));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO testimonials ($columns) VALUES ($placeholders)";
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة الرأي بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة الرأي.']);
            }
        }
        break;

    case 'toggle_testimonial_approval':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $testimonial_id = (int)sanitize_input($_POST['testimonial_id'] ?? 0);
        if ($testimonial_id > 0) {
            $current = $db->queryOne("SELECT is_approved FROM testimonials WHERE testimonial_id = :id", [':id' => $testimonial_id]);
            if ($current) {
                $new_status = $current['is_approved'] ? 0 : 1;
                if ($db->execute("UPDATE testimonials SET is_approved = :status, updated_at = NOW() WHERE testimonial_id = :id", [':status' => $new_status, ':id' => $testimonial_id])) {
                    send_json_response(['success' => true, 'message' => 'تم تحديث حالة الموافقة.', 'new_status' => $new_status]);
                } else {
                    send_json_response(['success' => false, 'message' => 'فشل تحديث حالة الموافقة.']);
                }
            } else {
                send_json_response(['success' => false, 'message' => 'الرأي غير موجود.']);
            }
        } else {
            send_json_response(['success' => false, 'message' => 'معرف الرأي غير صالح.']);
        }
        break;

    case 'submit_testimonial': // Public submission
         if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'محاولة غير صالحة.']);
            break;
        }
        $data = [
            'client_name' => sanitize_input($_POST['client_name'] ?? ''),
            'client_title_company' => sanitize_input($_POST['client_title_company'] ?? null),
            'feedback' => sanitize_input($_POST['feedback'] ?? ''),
            'rating' => empty($_POST['rating']) ? null : (int)sanitize_input($_POST['rating']),
            'is_approved' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if (empty($data['client_name']) || empty($data['feedback'])) {
             send_json_response(['success' => false, 'message' => 'الاسم والرأي مطلوبان.']);
            break;
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO testimonials ($columns) VALUES ($placeholders)";
        if ($db->execute($sql, $data)) {
            send_json_response(['success' => true, 'message' => 'شكراً لك! تم إرسال رأيك للمراجعة.']);
        } else {
            send_json_response(['success' => false, 'message' => 'عفواً، حدث خطأ أثناء إرسال رأيك.']);
        }
        break;

    // --- Site Settings ---
    case 'save_site_settings':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $data = [];
        $allowed_fields = [
            'site_name', 'site_tagline', 'site_description', 'meta_keywords',
            'contact_phone', 'contact_email', 'contact_address',
            'whatsapp_link', 'instagram_link', 'twitter_link', 'facebook_link',
            'footer_text', 'map_location_name', 'map_lat', 'map_lng', 'map_api_key',
            'google_analytics_id', 'google_tag_manager_id', 'facebook_pixel_id'
        ];
        foreach($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                 // footer_text might be HTML from TinyMCE, handle sanitization accordingly if it's not already done client-side or if strict sanitization is needed.
                $data[$field] = ($_POST[$field] === 'footer_text' && !is_array($_POST[$field])) ? $_POST[$field] : sanitize_input($_POST[$field]);
            }
        }
        if (isset($_POST['enabled_frontend_sections']) && is_array($_POST['enabled_frontend_sections'])) {
            // Sanitize keys and values if necessary
            $sanitized_sections = [];
            foreach($_POST['enabled_frontend_sections'] as $key => $value) {
                $sanitized_sections[sanitize_input($key)] = (bool)$value;
            }
            $data['enabled_frontend_sections'] = json_encode($sanitized_sections);
        } else {
            $data['enabled_frontend_sections'] = json_encode([]);
        }

        $file_fields = ['site_logo' => 'site_logo_path', 'site_favicon' => 'site_favicon_path', 'og_image' => 'og_image_path'];
        $current_settings = $db->queryOne("SELECT site_logo_path, site_favicon_path, og_image_path FROM settings WHERE setting_id = 1");

        foreach ($file_fields as $input_name => $db_column) {
            if (isset($_POST['remove_' . $input_name]) && $_POST['remove_' . $input_name] == '1' && $current_settings && !empty($current_settings[$db_column])) {
                delete_uploaded_file($current_settings[$db_column]);
                $data[$db_column] = null;
            }
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                if ($current_settings && !empty($current_settings[$db_column])) {
                    delete_uploaded_file($current_settings[$db_column]);
                }
                $uploaded_path = handle_file_upload($_FILES[$input_name], 'site_assets');
                if ($uploaded_path) {
                    $data[$db_column] = $uploaded_path;
                } else {
                    send_json_response(['success' => false, 'message' => "فشل رفع ملف $input_name."]);
                    return;
                }
            }
        }

        if (empty($data) && empty(array_filter($_FILES))) { // Check if any file was uploaded too
            send_json_response(['success' => true, 'message' => 'لا توجد بيانات لتحديثها.']);
            break;
        }

        $setting_exists = $db->queryOne("SELECT setting_id FROM settings WHERE setting_id = 1");

        if ($setting_exists) {
            if (!empty($data)) { // Only update if there's data to update
                $set_clauses = [];
                $params_update = [];
                foreach ($data as $key => $value) {
                    $set_clauses[] = "`$key` = :$key";
                     $params_update[":$key"] = $value;
                }
                $params_update[':setting_id'] = 1;
                $sql = "UPDATE settings SET " . implode(', ', $set_clauses) . " WHERE setting_id = :setting_id";
                if ($db->execute($sql, $params_update)) {
                    send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات الموقع.']);
                } else {
                    send_json_response(['success' => false, 'message' => 'فشل حفظ إعدادات الموقع.']);
                }
            } else {
                 send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات الموقع (لم يتم تغيير أي بيانات نصية ولكن ربما تم تحديث الملفات).']);
            }
        } else {
            $data['setting_id'] = 1;
            $columns = implode(', ', array_map(function($key) { return "`$key`"; }, array_keys($data)));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO settings ($columns) VALUES ($placeholders)";
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات الموقع.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل حفظ إعدادات الموقع.']);
            }
        }
        break;

    // --- SEO Settings ---
    case 'save_seo_settings':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $data = [];
        $allowed_seo_fields = [
            'meta_title_template', 'meta_description', 'meta_keywords',
            'twitter_card_type', 'twitter_site',
            'google_analytics_id', // This is distinct from the one in 'settings' table
            'google_verification', 'bing_verification',
            'robots_txt', 'canonical_url', 'schema_type'
        ];
         foreach($allowed_seo_fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = ($_POST[$field] === 'robots_txt') ? $_POST[$field] : sanitize_input($_POST[$field]);
            }
        }
        $data['enable_schema'] = isset($_POST['enable_schema']) ? 1 : 0;

        if (isset($_POST['sitemap_settings']) && is_array($_POST['sitemap_settings'])) {
             $data['sitemap_settings'] = json_encode(array_map('sanitize_input', $_POST['sitemap_settings'])); // Sanitize array values
        }
        if (isset($_POST['schema_settings']) && is_array($_POST['schema_settings'])) {
            $data['schema_settings'] = json_encode(array_map('sanitize_input', $_POST['schema_settings'])); // Sanitize array values
        }

        $current_seo_settings = $db->queryOne("SELECT og_image FROM seo_settings WHERE id = 1");
        if (isset($_POST['remove_og_image']) && $_POST['remove_og_image'] == '1' && $current_seo_settings && !empty($current_seo_settings['og_image'])) {
            delete_uploaded_file($current_seo_settings['og_image']);
            $data['og_image'] = null;
        }
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            if ($current_seo_settings && !empty($current_seo_settings['og_image'])) {
                delete_uploaded_file($current_seo_settings['og_image']);
            }
            $uploaded_path = handle_file_upload($_FILES['og_image'], 'site_assets');
            if ($uploaded_path) {
                $data['og_image'] = $uploaded_path;
            } else {
                send_json_response(['success' => false, 'message' => 'فشل رفع صورة OG.']);
                return;
            }
        }

        if (empty($data) && empty(array_filter($_FILES))) {
             send_json_response(['success' => true, 'message' => 'لا توجد بيانات لتحديثها.']);
            break;
        }

        $seo_setting_exists = $db->queryOne("SELECT id FROM seo_settings WHERE id = 1");
        if ($seo_setting_exists) {
             if (!empty($data)) {
                $set_clauses = [];
                $params_update = [];
                foreach ($data as $key => $value) {
                    $set_clauses[] = "`$key` = :$key";
                    $params_update[":$key"] = $value;
                }
                $params_update[':id'] = 1;
                $sql = "UPDATE seo_settings SET " . implode(', ', $set_clauses) . " WHERE id = :id";
                if ($db->execute($sql, $params_update)) {
                    send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات SEO.']);
                } else {
                    send_json_response(['success' => false, 'message' => 'فشل حفظ إعدادات SEO.']);
                }
            } else {
                send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات SEO (لم يتم تغيير أي بيانات نصية ولكن ربما تم تحديث الملفات).']);
            }
        } else {
            $data['id'] = 1;
            $columns = implode(', ', array_map(function($key) { return "`$key`"; }, array_keys($data)));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO seo_settings ($columns) VALUES ($placeholders)";
             if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات SEO.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل حفظ إعدادات SEO.']);
            }
        }
        break;

    case 'get_sitemap_content':
        $sitemap_path = PROJECT_ROOT . '/sitemap.xml';
        if (file_exists($sitemap_path)) {
            // Consider security implications if sitemap can contain sensitive paths or info
            // For admin display, it might be okay.
            send_json_response(['success' => true, 'content' => file_get_contents($sitemap_path)]);
        } else {
            send_json_response(['success' => false, 'message' => 'ملف خريطة الموقع غير موجود.']);
        }
        break;
        
    default:
        send_json_response(['success' => false, 'message' => 'عملية AJAX غير معروفة أو غير محددة.'], 400);
        break;
}

// --- Image Handling Functions (already refactored in previous steps) ---
/**
 * معالجة رفع صور متعددة
 */
function handle_upload_images() {
    global $db;
    
    if (!isset($_POST['entity_type']) || !isset($_POST['entity_id'])) {
        send_json_response(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
        exit;
    }
    
    $entity_type = sanitize_input($_POST['entity_type']);
    $entity_id = (int)$_POST['entity_id'];
    
    if (!in_array($entity_type, ['service', 'project'])) {
        send_json_response(['success' => false, 'message' => 'نوع كيان غير صالح'], 400);
        exit;
    }
    
    $entity_exists = false;
    if ($entity_type === 'service') {
        $entity_exists = (bool)$db->queryOne("SELECT 1 FROM services WHERE service_id = :id LIMIT 1", [':id' => $entity_id]);
    } else if ($entity_type === 'project') {
        $entity_exists = (bool)$db->queryOne("SELECT 1 FROM projects WHERE project_id = :id LIMIT 1", [':id' => $entity_id]);
    }
    
    if (!$entity_exists) {
        send_json_response(['success' => false, 'message' => 'الكيان غير موجود'], 404);
        exit;
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        send_json_response(['success' => false, 'message' => 'لم يتم توفير ملف صالح'], 400);
        exit;
    }
    
    $upload_dir = $entity_type . 's';
    $image_path = upload_single_image($_FILES['file'], $upload_dir, $entity_type);
    
    if (!$image_path) {
        send_json_response(['success' => false, 'message' => 'فشل في رفع الصورة'], 500);
        exit;
    }
    
    $table_name = $entity_type . '_images';
    $id_column = $entity_type . '_id';
    
    $result_row = $db->queryOne("SELECT MAX(sort_order) as max_order FROM $table_name WHERE $id_column = :entity_id", [':entity_id' => $entity_id]);
    $sort_order = $result_row && $result_row['max_order'] ? $result_row['max_order'] + 1 : 1;
    
    $image_data = [
        $id_column => $entity_id,
        'image_path' => $image_path,
        'sort_order' => $sort_order,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $columns = implode(', ', array_keys($image_data));
    $placeholders = ':' . implode(', :', array_keys($image_data));
    $sql_insert = "INSERT INTO $table_name ($columns) VALUES ($placeholders)";
    
    $new_image_id = null;
    if ($db->execute($sql_insert, $image_data)) {
        $new_image_id = $db->lastInsertId();
    }

    if (!$new_image_id) {
        delete_uploaded_file($image_path);
        send_json_response(['success' => false, 'message' => 'فشل في حفظ بيانات الصورة'], 500);
        exit;
    }
    
    send_json_response([
        'success' => true,
        'message' => 'تم رفع الصورة بنجاح',
        'image' => [
            'id' => $new_image_id,
            'path' => UPLOAD_URL . $image_path,
            'sort_order' => $sort_order
        ]
    ]);
}

/**
 * معالجة حذف صورة
 */
function handle_delete_image() {
    global $db;
    
    if (!isset($_POST['entity_type']) || !isset($_POST['image_id'])) {
        send_json_response(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
        exit;
    }
    
    $entity_type = sanitize_input($_POST['entity_type']);
    $image_id = (int)$_POST['image_id'];
    
    if (!in_array($entity_type, ['service', 'project'])) {
         send_json_response(['success' => false, 'message' => 'نوع كيان غير صالح'], 400);
        exit;
    }
    
    $table_name = $entity_type . '_images';
    $image = $db->queryOne("SELECT image_path FROM $table_name WHERE image_id = :image_id", [':image_id' => $image_id]);
    
    if (!$image) {
        send_json_response(['success' => false, 'message' => 'الصورة غير موجودة'], 404);
        exit;
    }
    
    delete_uploaded_file($image['image_path']);
    
    $db_delete_result = $db->execute("DELETE FROM $table_name WHERE image_id = :image_id", [':image_id' => $image_id]);
    
    if (!$db_delete_result) {
        send_json_response(['success' => false, 'message' => 'فشل في حذف الصورة من قاعدة البيانات'], 500);
        exit;
    }
    
    send_json_response([
        'success' => true,
        'message' => 'تم حذف الصورة بنجاح',
        'image_id' => $image_id
    ]);
}

/**
 * معالجة إعادة ترتيب الصور
 */
function handle_reorder_images() {
    global $db;
    
    if (!isset($_POST['entity_type']) || !isset($_POST['image_order'])) {
        send_json_response(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
        exit;
    }
    
    $entity_type = sanitize_input($_POST['entity_type']);
    $image_order_json = $_POST['image_order'];
    $image_order = json_decode($image_order_json, true);

    if (!in_array($entity_type, ['service', 'project'])) {
        send_json_response(['success' => false, 'message' => 'نوع كيان غير صالح'], 400);
        exit;
    }
    
    if (!is_array($image_order)) {
        send_json_response(['success' => false, 'message' => 'بيانات الترتيب غير صالحة'], 400);
        exit;
    }
    
    $table_name = $entity_type . '_images';
    $success = true;
    
    $pdo = $db->getPdo();
    $pdo->beginTransaction();
    
    try {
        $sql_update_order = "UPDATE $table_name SET sort_order = :sort_order WHERE image_id = :image_id";
        foreach ($image_order as $index => $img_id) {
            $result = $db->execute($sql_update_order, [':sort_order' => $index + 1, ':image_id' => (int)$img_id]);
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        log_error("Reorder images failed for $entity_type: " . $e->getMessage());
    }
    
    if (!$success) {
        send_json_response(['success' => false, 'message' => 'فشل في تحديث ترتيب الصور'], 500);
        exit;
    }
    
    send_json_response(['success' => true, 'message' => 'تم تحديث ترتيب الصور بنجاح']);
}

/**
 * معالجة تعيين الصورة الرئيسية
 */
function handle_set_main_image() {
    global $db;
    
    if (!isset($_POST['entity_type']) || !isset($_POST['entity_id']) || !isset($_POST['image_id'])) {
        send_json_response(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
        exit;
    }
    
    $entity_type = sanitize_input($_POST['entity_type']);
    $entity_id = (int)$_POST['entity_id'];
    $image_id = (int)$_POST['image_id'];
    
    if (!in_array($entity_type, ['service', 'project'])) {
         send_json_response(['success' => false, 'message' => 'نوع كيان غير صالح'], 400);
        exit;
    }
    
    $images_table = $entity_type . '_images';
    $entity_table = $entity_type . 's';
    $id_column = $entity_type . '_id'; // Renamed from entity_id_column for clarity
    
    $image = $db->queryOne("SELECT image_path FROM $images_table WHERE image_id = :image_id AND $id_column = :entity_id",
                           [':image_id' => $image_id, ':entity_id' => $entity_id]);
    
    if (!$image) {
        send_json_response(['success' => false, 'message' => 'الصورة غير موجودة أو لا تنتمي لهذا الكيان.'], 404);
        exit;
    }
    
    $image_column_name = ($entity_type === 'service') ? 'image' : 'main_image';
    
    $sql_update_main = "UPDATE $entity_table SET $image_column_name = :image_path WHERE $id_column = :entity_id";
    $result = $db->execute($sql_update_main, [':image_path' => $image['image_path'], ':entity_id' => $entity_id]);
    
    if (!$result) {
        send_json_response(['success' => false, 'message' => 'فشل في تعيين الصورة الرئيسية'], 500);
        exit;
    }
    
    send_json_response([
        'success' => true,
        'message' => 'تم تعيين الصورة الرئيسية بنجاح',
        'image_path' => UPLOAD_URL . $image['image_path']
    ]);
}

?>
