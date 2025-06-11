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
        // Use new schema: id, name, content, image_url, video_url
        $section = $db->queryOne("SELECT id, name, content, image_url, video_url FROM sections WHERE id = :id", [':id' => $section_id]);
        if ($section) {
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

        $section_id = (int)sanitize_input($_POST['section_id'] ?? 0); // This is 'id' in the new table

        // New schema fields from form
        $name = sanitize_input($_POST['name'] ?? ''); // Was 'title'
        $content = $_POST['content'] ?? ''; // Assuming TinyMCE content, handle sanitization appropriately if needed
        $video_url = sanitize_input($_POST['video_url'] ?? null);

        $data = [
            'name' => $name,
            'content' => $content,
            'video_url' => $video_url,
        ];

        if (empty($data['name'])) { // Name is likely mandatory
            send_json_response(['success' => false, 'message' => 'اسم القسم مطلوب.']);
            break;
        }

        // Handle image upload for image_url
        $current_image_url = null;
        if ($section_id > 0) {
            $old_section_data = $db->queryOne("SELECT image_url FROM sections WHERE id = :id", [':id' => $section_id]);
            if ($old_section_data) {
                $current_image_url = $old_section_data['image_url'];
            }
        }

        if (isset($_POST['remove_image_file']) && $_POST['remove_image_file'] == '1' && !empty($current_image_url)) {
            delete_uploaded_file($current_image_url);
            $data['image_url'] = null;
        } elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            if (!empty($current_image_url)) {
                delete_uploaded_file($current_image_url);
            }
            $uploaded_path = handle_file_upload($_FILES['image_file'], 'sections'); // 'sections' is subfolder
            if ($uploaded_path) {
                $data['image_url'] = $uploaded_path;
            } else {
                send_json_response(['success' => false, 'message' => 'فشل رفع صورة القسم.']);
                break;
            }
        } else {
            // If no new image and no removal, keep existing image path
            if ($section_id > 0 && $current_image_url !== null) {
                 $data['image_url'] = $current_image_url;
            } else if ($section_id == 0) { // For new section, if no image, set to null
                 $data['image_url'] = null;
            }
        }


        if ($section_id > 0) { // Update
            $params_update = $data;
            $params_update[':id'] = $section_id;

            $set_clauses = [];
            foreach ($data as $key => $value) {
                 if ($key === 'image_url' && $value === $current_image_url && !isset($_FILES['image_file']) && !(isset($_POST['remove_image_file']) && $_POST['remove_image_file'] == '1') ) {
                    continue; // Skip updating image_url if it hasn't changed
                }
                $set_clauses[] = "$key = :$key";
            }

            if(empty($set_clauses)){
                 send_json_response(['success' => true, 'message' => 'لم يتم تغيير أي بيانات.']);
                 break;
            }

            $sql = "UPDATE sections SET " . implode(', ', $set_clauses) . ", updated_at = NOW() WHERE id = :id";

            if ($db->execute($sql, $params_update)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث القسم بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث القسم.']);
            }
        } else { // Insert
            // created_at and updated_at handled by NOW()
            $sql = "INSERT INTO sections (name, content, image_url, video_url, created_at, updated_at)
                    VALUES (:name, :content, :image_url, :video_url, NOW(), NOW())";

            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة القسم بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة القسم.']);
            }
        }
        break;

    // reorder_sections case removed
    // toggle_section_visibility case removed

    case 'delete_section':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $section_id = (int)sanitize_input($_POST['id'] ?? 0); // Expect 'id' from confirmDelete

        if ($section_id <= 0) {
            send_json_response(['success' => false, 'message' => 'معرف القسم غير صالح.']);
            break;
        }

        $section = $db->queryOne("SELECT image_url FROM sections WHERE id = :id", [':id' => $section_id]);

        if (!$section) {
            send_json_response(['success' => false, 'message' => 'القسم غير موجود.']);
            break;
        }

        if ($db->execute("DELETE FROM sections WHERE id = :id", [':id' => $section_id])) {
            if ($section && !empty($section['image_url'])) {
                delete_uploaded_file($section['image_url']);
            }
            send_json_response(['success' => true, 'message' => 'تم حذف القسم بنجاح.']);
        } else {
            send_json_response(['success' => false, 'message' => 'فشل حذف القسم.']);
        }
        break;

    // --- Facts Management ---
    // ... (facts cases remain here) ...

    // --- Message Details (for viewMessage modal) ---
    case 'get_message': // Assuming this is the action called by viewMessage in messages_management.php
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null, false) && !verify_csrf_token($_GET[CSRF_TOKEN_NAME] ?? null, false)) {
             // Allow GET for this if CSRF is passed in URL, or POST.
             // However, viewMessage in messages_management.php seems to use POST.
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح أو مفقود.']);
            break;
        }

        $message_id = (int)($_REQUEST['message_id'] ?? 0);
        if ($message_id <= 0) {
            send_json_response(['success' => false, 'message' => 'معرف الرسالة غير صالح.']);
            break;
        }

        $message = $db->queryOne("SELECT id, sender_name, sender_email, subject, message_body, received_at, is_read FROM messages WHERE id = :id", [':id' => $message_id]);

        if ($message) {
            // Mark as read if it's being viewed and was unread
            if (!$message['is_read']) {
                $db->execute("UPDATE messages SET is_read = 1 WHERE id = :id", [':id' => $message_id]);
            }

            // Construct HTML for the modal body
            $html = "<p><strong>من:</strong> " . htmlspecialchars($message['sender_name']) . " (" . htmlspecialchars($message['sender_email']) . ")</p>";
            $html .= "<p><strong>الموضوع:</strong> " . htmlspecialchars($message['subject'] ?: '<em>بدون موضوع</em>') . "</p>";
            $html .= "<p><strong>تاريخ الاستلام:</strong> " . date('Y-m-d H:i:s', strtotime($message['received_at'])) . "</p>";
            $html .= "<hr><p><strong>نص الرسالة:</strong></p><div style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message['message_body'])) . "</div>";

            send_json_response(['success' => true, 'html' => $html]);
        } else {
            send_json_response(['success' => false, 'message' => 'لم يتم العثور على الرسالة.']);
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
        // Use new schema fields: id, fact_text, fact_value, icon_class
        $fact = $db->queryOne("SELECT id, fact_text, fact_value, icon_class FROM facts WHERE id = :id", [':id' => $fact_id]);
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

        // New schema fields from form (map old names to new if necessary)
        $fact_text = sanitize_input($_POST['fact_text'] ?? ''); // Was 'title'
        $fact_value = sanitize_input($_POST['fact_value'] ?? ''); // Was 'number'
        $icon_class = sanitize_input($_POST['icon_class'] ?? null); // Was 'icon'

        $data = [
            'fact_text' => $fact_text,
            'fact_value' => $fact_value,
            'icon_class' => $icon_class,
        ];

        if (empty($data['fact_text']) || $data['fact_value'] === '') { // fact_value can be '0'
            send_json_response(['success' => false, 'message' => 'نص الحقيقة وقيمتها مطلوبان.']);
            break;
        }

        if ($fact_id > 0) { // Update
            $params_update = $data;
            $params_update[':id'] = $fact_id;
            $sql = "UPDATE facts SET fact_text = :fact_text, fact_value = :fact_value, icon_class = :icon_class, updated_at = NOW() WHERE id = :id";
            if ($db->execute($sql, $params_update)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث الحقيقة بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث الحقيقة.']);
            }
        } else { // Insert
            // created_at and updated_at handled by NOW()
            $sql = "INSERT INTO facts (fact_text, fact_value, icon_class, created_at, updated_at)
                    VALUES (:fact_text, :fact_value, :icon_class, NOW(), NOW())";
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة الحقيقة بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة الحقيقة.']);
            }
        }
        break;

    case 'delete_fact':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $fact_id = (int)sanitize_input($_POST['id'] ?? 0); // Assuming 'id' is sent from confirmDelete

        if ($fact_id <= 0) {
            send_json_response(['success' => false, 'message' => 'معرف الحقيقة غير صالح.']);
            break;
        }

        if ($db->execute("DELETE FROM facts WHERE id = :id", [':id' => $fact_id])) {
            send_json_response(['success' => true, 'message' => 'تم حذف الحقيقة بنجاح.']);
        } else {
            send_json_response(['success' => false, 'message' => 'فشل حذف الحقيقة.']);
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
        // Use new schema fields: id, author_name, testimonial_text, author_image_url
        $testimonial = $db->queryOne("SELECT id, author_name, testimonial_text, author_image_url FROM testimonials WHERE id = :id", [':id' => $testimonial_id]);
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
        $testimonial_id = (int)sanitize_input($_POST['testimonial_id'] ?? 0); // Renamed from testimonial_id_field for clarity

        // New schema fields
        $author_name = sanitize_input($_POST['author_name'] ?? '');
        $testimonial_text = sanitize_input($_POST['feedback'] ?? ''); // 'feedback' from form maps to 'testimonial_text'

        $data = [
            'author_name' => $author_name,
            'testimonial_text' => $testimonial_text,
        ];

        if (empty($data['author_name']) || empty($data['testimonial_text'])) {
             send_json_response(['success' => false, 'message' => 'اسم المؤلف ونص الرأي مطلوبان.']);
            break;
        }

        // Handle image upload for author_image_url
        $current_image_path = null;
        if ($testimonial_id > 0) {
            $old_testimonial = $db->queryOne("SELECT author_image_url FROM testimonials WHERE id = :id", [':id' => $testimonial_id]);
            if ($old_testimonial) {
                $current_image_path = $old_testimonial['author_image_url'];
            }
        }

        if (isset($_POST['remove_author_image']) && $_POST['remove_author_image'] == '1' && !empty($current_image_path)) {
            delete_uploaded_file($current_image_path);
            $data['author_image_url'] = null;
        } elseif (isset($_FILES['author_image_file']) && $_FILES['author_image_file']['error'] === UPLOAD_ERR_OK) {
            if (!empty($current_image_path)) {
                delete_uploaded_file($current_image_path); // Delete old image if new one is uploaded
            }
            $uploaded_path = handle_file_upload($_FILES['author_image_file'], 'testimonials'); // 'testimonials' is the subfolder
            if ($uploaded_path) {
                $data['author_image_url'] = $uploaded_path;
            } else {
                send_json_response(['success' => false, 'message' => 'فشل رفع صورة المؤلف.']);
                break;
            }
        } else {
            // If no new image and no removal, keep existing image path
            // This key should only be added if an update to the path is intended.
            // If it's not in $data, it won't be part of the SQL SET clause for UPDATE unless explicitly handled.
            if ($testimonial_id > 0 && $current_image_path !== null) {
                 $data['author_image_url'] = $current_image_path; // Ensure existing image is kept if not changed/removed
            } else if ($testimonial_id == 0) { // For new testimonial, if no image, set to null
                 $data['author_image_url'] = null;
            }
        }


        if ($testimonial_id > 0) { // Update
            $data['updated_at'] = date('Y-m-d H:i:s'); // Handled by DB NOW() in new query
            $set_clauses = [];
            $params_update = [':id' => $testimonial_id];
            foreach ($data as $key => $value) {
                if ($key === 'author_image_url' && $value === $current_image_path && !isset($_FILES['author_image_file']) && !(isset($_POST['remove_author_image']) && $_POST['remove_author_image'] == '1') ) {
                    continue; // Skip updating image_url if it hasn't changed
                }
                $set_clauses[] = "$key = :$key";
                $params_update[":$key"] = $value;
            }

            if(empty($set_clauses)){ // Nothing to update except potentially updated_at
                 send_json_response(['success' => true, 'message' => 'لم يتم تغيير أي بيانات.']);
                 break;
            }

            $sql = "UPDATE testimonials SET " . implode(', ', $set_clauses) . ", updated_at = NOW() WHERE id = :id";
            if ($db->execute($sql, $params_update)) {
                send_json_response(['success' => true, 'message' => 'تم تحديث الرأي بنجاح.']);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل تحديث الرأي.']);
            }
        } else { // Insert
            // created_at and updated_at handled by NOW()
            $sql = "INSERT INTO testimonials (author_name, testimonial_text, author_image_url, created_at, updated_at)
                    VALUES (:author_name, :testimonial_text, :author_image_url, NOW(), NOW())";
            if ($db->execute($sql, $data)) {
                send_json_response(['success' => true, 'message' => 'تم إضافة الرأي بنجاح.', 'new_id' => $db->lastInsertId()]);
            } else {
                send_json_response(['success' => false, 'message' => 'فشل إضافة الرأي.']);
            }
        }
        break;

    // toggle_testimonial_approval case block removed

    case 'delete_testimonial':
        if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? null)) {
            send_json_response(['success' => false, 'message' => 'رمز CSRF غير صالح.']);
            break;
        }
        $testimonial_id = (int)sanitize_input($_POST['id'] ?? 0); // Assuming 'id' is sent from confirmDelete

        if ($testimonial_id <= 0) {
            send_json_response(['success' => false, 'message' => 'معرف الرأي غير صالح.']);
            break;
        }

        // Fetch author_image_url before deleting the record
        $testimonial = $db->queryOne("SELECT author_image_url FROM testimonials WHERE id = :id", [':id' => $testimonial_id]);

        if (!$testimonial) {
            send_json_response(['success' => false, 'message' => 'الرأي غير موجود.']);
            break;
        }

        if ($db->execute("DELETE FROM testimonials WHERE id = :id", [':id' => $testimonial_id])) {
            // If record deletion is successful, delete the image file if it exists
            if ($testimonial && !empty($testimonial['author_image_url'])) {
                delete_uploaded_file($testimonial['author_image_url']);
            }
            send_json_response(['success' => true, 'message' => 'تم حذف الرأي بنجاح.']);
        } else {
            send_json_response(['success' => false, 'message' => 'فشل حذف الرأي.']);
        }
        break;

    case 'submit_testimonial': // Public submission - this should be reviewed for new schema if used publicly
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

        global $db;
        $pdo = $db->getPdo();
        $pdo->beginTransaction();

        try {
            $sql_upsert = "INSERT INTO settings (setting_name, setting_value, created_at, updated_at)
                           VALUES (:setting_name, :setting_value, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE setting_value = :setting_value, updated_at = NOW()";

            // Handle text-based settings
            foreach ($_POST as $key => $value) {
                if (in_array($key, ['action', CSRF_TOKEN_NAME]) || strpos($key, 'remove_') === 0) {
                    continue; // Skip non-setting fields and removal checkboxes (handled separately)
                }

                $setting_name = sanitize_input($key);
                $setting_value = $value; // Value already sanitized or HTML content

                if ($setting_name === 'enabled_frontend_sections') {
                    if (is_array($value)) {
                        $sanitized_sections = [];
                        foreach ($value as $section_key => $section_status) {
                            $sanitized_sections[sanitize_input($section_key)] = (bool)$section_status;
                        }
                        $setting_value = json_encode($sanitized_sections);
                    } else {
                        $setting_value = json_encode([]); // Default to empty if not an array
                    }
                } elseif ($setting_name === 'footer_text') {
                    // Assuming TinyMCE provides HTML, no further server-side sanitization here
                    // to preserve formatting. Client-side sanitization or purifier if needed.
                } else {
                     if (is_array($value)) {
                        // If for some reason other fields could be arrays and need special handling:
                        // $setting_value = json_encode(array_map('sanitize_input', $value));
                        // For now, assume other array values are not expected for simple settings.
                        // If they are, this part might need adjustment.
                        // For this specific form, only enabled_frontend_sections is an array.
                        log_error("Unexpected array value for setting: $setting_name");
                        continue; // Skip this field
                    } else {
                        $setting_value = sanitize_input($value);
                    }
                }

                $db->execute($sql_upsert, [':setting_name' => $setting_name, ':setting_value' => $setting_value]);
            }

            // Handle file uploads
            $file_fields_map = [
                'site_logo' => 'site_logo_path',
                'site_favicon' => 'site_favicon_path',
                'og_image' => 'og_image_path'
            ];

            foreach ($file_fields_map as $input_name => $setting_name_for_path) {
                // Check for file removal
                if (isset($_POST['remove_' . $input_name]) && $_POST['remove_' . $input_name] == '1') {
                    $current_path_row = $db->queryOne("SELECT setting_value FROM settings WHERE setting_name = :setting_name", [':setting_name' => $setting_name_for_path]);
                    if ($current_path_row && !empty($current_path_row['setting_value'])) {
                        delete_uploaded_file($current_path_row['setting_value']);
                    }
                    $db->execute($sql_upsert, [':setting_name' => $setting_name_for_path, ':setting_value' => null]);
                }

                // Check for new file upload
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    // Delete old file if it exists
                    $old_path_row = $db->queryOne("SELECT setting_value FROM settings WHERE setting_name = :setting_name", [':setting_name' => $setting_name_for_path]);
                    if ($old_path_row && !empty($old_path_row['setting_value'])) {
                        delete_uploaded_file($old_path_row['setting_value']);
                    }

                    // Upload new file
                    // Assuming handle_file_upload returns path relative to UPLOAD_DIR or similar base
                    // And that UPLOAD_DIR is inside project root and web accessible.
                    // The path stored should be relative to a web accessible root if UPLOAD_URL is used for display.
                    // E.g., if UPLOAD_URL is 'uploads/', and files are in 'project_root/public/uploads/',
                    // then handle_file_upload should return 'category/filename.ext'
                    // and this is stored in DB.
                    $uploaded_relative_path = handle_file_upload($_FILES[$input_name], 'site_assets');
                    if ($uploaded_relative_path) {
                        $db->execute($sql_upsert, [':setting_name' => $setting_name_for_path, ':setting_value' => $uploaded_relative_path]);
                    } else {
                        throw new Exception("فشل رفع ملف $input_name.");
                    }
                }
            }

            $pdo->commit();
            send_json_response(['success' => true, 'message' => 'تم حفظ إعدادات الموقع بنجاح.']);

        } catch (Exception $e) {
            $pdo->rollBack();
            log_error("Failed to save site settings: " . $e->getMessage());
            send_json_response(['success' => false, 'message' => 'فشل حفظ إعدادات الموقع: ' . $e->getMessage()]);
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
