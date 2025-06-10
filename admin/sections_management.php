<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إدارة أقسام الموقع";
$current_action = sanitize_input($_GET['action'] ?? 'list');
$section_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$section = null;

$section_types = [
    'hero' => 'قسم هيرو (رئيسي)',
    'about_summary' => 'ملخص عنا',
    'services_overview' => 'نظرة عامة على الخدمات',
    'projects_showcase' => 'عرض مشاريع مميزة',
    'testimonials_slider' => 'سلايدر آراء العملاء',
    'call_to_action' => 'دعوة للعمل (CTA)',
    'contact_info' => 'معلومات الاتصال',
    'map_embed' => 'تضمين خريطة',
    'custom_html' => 'محتوى HTML مخصص',
    'facts_counter' => 'عداد الحقائق والأرقام',
    // Add more types as needed
];


if ($section_id > 0) {
    $section = $db->queryOne("SELECT * FROM homepage_sections WHERE section_id = ?", [$section_id]);
}

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_type = $_SESSION['message']['type'];
    unset($_SESSION['message']);
}

?>

<div class="container mx-auto px-4 py-2">
    <div class="flex justify-between items-center mb-6 border-b pb-2">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
        <button onclick="openSectionModal()" class="bg-pink-600 hover:bg-pink-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors">
            <i data-feather="plus" class="inline-block mr-2"></i> إضافة قسم جديد
        </button>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-xl">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">قائمة الأقسام</h2>
        <p class="text-sm text-gray-600 mb-4">اسحب وأفلت الأقسام لترتيب ظهورها في الصفحة الرئيسية.</p>
        
        <div id="sections-list-container" class="space-y-3">
            <?php
            $sections = $db->query("SELECT * FROM homepage_sections ORDER BY section_order ASC");
            if (empty($sections)): ?>
                <p class="text-gray-500">لا توجد أقسام مضافة حالياً.</p>
            <?php else:
                foreach ($sections as $sec): ?>
                <div class="section-item border p-4 rounded-lg bg-gray-50 shadow-sm flex justify-between items-center" data-id="<?php echo $sec['section_id']; ?>">
                    <div class="flex items-center">
                        <i data-feather="move" class="cursor-move text-gray-400 mr-3 section-drag-handle"></i>
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($sec['title'] ?: '<em>بدون عنوان</em>'); ?></h3>
                            <span class="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($section_types[$sec['section_type']] ?? $sec['section_type']); ?></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="text-xs px-2 py-1 rounded-full <?php echo $sec['is_visible'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'; ?>">
                            <?php echo $sec['is_visible'] ? 'مرئي' : 'مخفي'; ?>
                        </span>
                        <button onclick="openSectionModal(<?php echo $sec['section_id']; ?>)" class="p-1 text-blue-600 hover:text-blue-800" title="تعديل">
                            <i data-feather="edit-2" class="w-5 h-5"></i>
                        </button>
                        <button onclick="confirmDelete(<?php echo $sec['section_id']; ?>, 'section', 'ajax_handler.php')" class="p-1 text-red-600 hover:text-red-800" title="حذف">
                            <i data-feather="trash-2" class="w-5 h-5"></i>
                        </button>
                         <form action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" class="inline-block" onsubmit="return ajaxSubmitForm(this, सामान्य_callback);">
                            <?php echo csrf_input_field(); ?>
                            <input type="hidden" name="action" value="toggle_section_visibility">
                            <input type="hidden" name="section_id" value="<?php echo $sec['section_id']; ?>">
                            <button type="submit" class="p-1 <?php echo $sec['is_visible'] ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800'; ?>" title="<?php echo $sec['is_visible'] ? 'إخفاء' : 'إظهار'; ?>">
                                <i data-feather="<?php echo $sec['is_visible'] ? 'eye-off' : 'eye'; ?>" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
</div>

<!-- Section Modal -->
<div id="sectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="sectionModalTitle">إضافة/تعديل قسم</h3>
            <span class="close-modal-btn" onclick="closeModal('sectionModal')">&times;</span>
        </div>
        <form id="sectionForm" action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return ajaxSubmitForm(this, sectionFormCallback);">
            <?php echo csrf_input_field(); ?>
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section_id" id="section_id_field" value="0">
            
            <div class="modal-body">
                <div>
                    <label for="section_title" class="block text-sm font-medium text-gray-700 mb-1">عنوان القسم:</label>
                    <input type="text" name="title" id="section_title" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="section_subtitle" class="block text-sm font-medium text-gray-700 mb-1">العنوان الفرعي (اختياري):</label>
                    <input type="text" name="subtitle" id="section_subtitle" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="section_type" class="block text-sm font-medium text-gray-700 mb-1">نوع القسم <span class="text-red-500">*</span>:</label>
                    <select name="section_type" id="section_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        <?php foreach ($section_types as $type_key => $type_name): ?>
                            <option value="<?php echo htmlspecialchars($type_key); ?>"><?php echo htmlspecialchars($type_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="section_content_container">
                    <label for="section_content" class="block text-sm font-medium text-gray-700 mb-1">المحتوى (HTML مسموح لبعض الأنواع):</label>
                    <textarea name="content" id="section_content" rows="8" class="tinymceeditor mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="background_image" class="block text-sm font-medium text-gray-700 mb-1">صورة الخلفية (اختياري):</label>
                    <input type="file" name="background_image" id="background_image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                    <div id="background_image_preview_container" class="mt-2"></div>
                    <input type="hidden" name="existing_background_image" id="existing_background_image_field">
                </div>
                 <div>
                    <label for="data_attributes" class="block text-sm font-medium text-gray-700 mb-1">بيانات إضافية (JSON - للمطورين):</label>
                    <textarea name="data_attributes" id="data_attributes_field" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm" placeholder='{"key": "value"}'></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_visible" id="section_is_visible" value="1" class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <label for="section_is_visible" class="ml-2 block text-sm text-gray-900">مرئي في الموقع</label>
                </div>
            </div>
            <div class="modal-footer bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-pink-600 text-base font-medium text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 sm:ml-3 sm:w-auto sm:text-sm">
                    حفظ القسم
                </button>
                <button type="button" onclick="closeModal('sectionModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeTinyMCE('.tinymceeditor');
        
        const sectionsListContainer = document.getElementById('sections-list-container');
        if (sectionsListContainer) {
            new Sortable(sectionsListContainer, {
                animation: 150,
                handle: '.section-drag-handle', // Class of the element to use as a drag handle
                ghostClass: 'bg-pink-100', // Class for the dragging placeholder
                onEnd: function (evt) {
                    const itemEl = evt.item; // Dragged HTMLElement
                    const sectionIds = Array.from(sectionsListContainer.children).map(child => child.dataset.id);
                    
                    const formData = new FormData();
                    formData.append('action', 'reorder_sections');
                    formData.append('ordered_ids', JSON.stringify(sectionIds));
                    formData.append('<?php echo CSRF_TOKEN_NAME; ?>', '<?php echo generate_csrf_token(); ?>');

                    fetch('<?php echo base_url('admin/ajax_handler.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            adminPanel.showAlert('تم تحديث ترتيب الأقسام بنجاح.', 'success');
                            // Optionally, visually confirm reorder or rely on optimistic update
                        } else {
                            adminPanel.showAlert('فشل تحديث الترتيب: ' + (data.message || 'خطأ غير معروف'), 'error');
                            // Revert UI if optimistic update was done, or refresh list
                            // For now, simple alert. A robust solution would revert or reload.
                            // sectionsListContainer.insertBefore(itemEl, evt.originalTarget); // Revert example
                        }
                    })
                    .catch(error => {
                        adminPanel.showAlert('خطأ في الاتصال بالخادم لتحديث الترتيب.', 'error');
                        console.error('Reorder error:', error);
                    });
                }
            });
        }
    });

    // أضف الدالة هنا
    function openSectionModal(sectionId = 0) {
    const form = document.getElementById('sectionForm');
    form.reset();
    destroyExistingTinyMCEInstance('#section_content');
    
    document.getElementById('section_id_field').value = sectionId;
    document.getElementById('background_image_preview_container').innerHTML = '';
    document.getElementById('existing_background_image_field').value = '';

    if (sectionId > 0) {
        document.getElementById('sectionModalTitle').textContent = 'تعديل القسم';
        fetchWithCSRF1(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_section_details&id=${sectionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.section) {
                    const sec = data.section;
                    document.getElementById('section_title').value = sec.title || '';
                    document.getElementById('section_subtitle').value = sec.subtitle || '';
                    document.getElementById('section_type').value = sec.section_type;
                    
                    tinymce.get('section_content')?.setContent(sec.content || '');
                    document.getElementById('data_attributes_field').value = sec.data_attributes ? JSON.stringify(JSON.parse(sec.data_attributes), null, 2) : '';
                    document.getElementById('section_is_visible').checked = parseInt(sec.is_visible) === 1;
                    
                    if (sec.background_image) {
                        document.getElementById('existing_background_image_field').value = sec.background_image;
                        const imgPreview = `<img src="<?php echo UPLOAD_URL; ?>${sec.background_image}" class="h-20 w-auto rounded mt-1" alt="Preview"> <button type="button" class="text-red-500 text-xs" onclick="removeSectionImagePreview('${sec.section_id}', 'background_image')">إزالة الصورة</button>`;
                        document.getElementById('background_image_preview_container').innerHTML = imgPreview;
                    }
                } else {
                    adminPanel.showAlert('فشل تحميل بيانات القسم: ' + (data.message || 'خطأ غير معروف'), 'error');
                }
                initializeTinyMCE('#section_content');
            })
            .catch(error => {
                adminPanel.showAlert('خطأ في الاتصال بالخادم.', 'error');
                console.error('Fetch section error:', error);
                initializeTinyMCE('#section_content');
            });
    } else {
        document.getElementById('sectionModalTitle').textContent = 'إضافة قسم جديد';
        tinymce.get('section_content')?.setContent('');
        initializeTinyMCE('#section_content');
    }
    showModal('sectionModal');
}

// تأكد من وجود هذه الدوال المساعدة في ملف admin.js
function fetchWithCSRF1(url, options = {}) {
    const token = document.querySelector('input[name="csrf_token"]')?.value;
    if (!token) {
        console.error('CSRF token missing');
        return Promise.reject('CSRF token missing');
    }

    const separator = url.includes('?') ? '&' : '?';
    return fetch(`${url}${separator}csrf_token=${encodeURIComponent(token)}`, options);
}
    
    function removeSectionImagePreview(sectionId, fieldName) {
        // This is primarily for UI. Actual removal on server happens if form is saved without re-uploading.
        // Or, can add an AJAX call here to immediately mark for deletion.
        // For simplicity, we'll clear the hidden field and preview. Server logic will handle it.
        if (fieldName === 'background_image') {
            document.getElementById('existing_background_image_field').value = ''; // Mark for removal on save
            document.getElementById('background_image_preview_container').innerHTML = '<span class="text-xs text-gray-500">تم تحديد الصورة الحالية للحذف.</span>';
        }
    }

    function sectionFormCallback(response) {
        if (response.success) {
            adminPanel.showAlert(response.message || 'تم حفظ القسم بنجاح!', 'success');
            closeModal('sectionModal');
            // Refresh the list of sections or update UI dynamically
            setTimeout(() => window.location.reload(), 1000); // Simple reload for now
        } else {
            adminPanel.showAlert(response.message || 'فشل حفظ القسم.', 'error');
        }
    }

</script>
