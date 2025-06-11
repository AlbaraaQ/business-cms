<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إدارة أقسام الموقع";

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
        
        <div id="sections-list-container" class="space-y-3">
            <?php
            $sections = $db->query("SELECT id, name, content, image_url, video_url, created_at FROM sections ORDER BY created_at DESC");
            if (empty($sections)): ?>
                <p class="text-gray-500">لا توجد أقسام مضافة حالياً.</p>
            <?php else:
                foreach ($sections as $sec): ?>
                <div class="section-item border p-4 rounded-lg bg-gray-50 shadow-sm flex justify-between items-center" data-id="<?php echo $sec['id']; ?>" id="section-item-<?php echo $sec['id']; ?>">
                    <div class="flex items-center">
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($sec['name'] ?: '<em>بدون اسم</em>'); ?></h3>
                             <small class="text-xs text-gray-500">تاريخ الإضافة: <?php echo date('Y-m-d H:i', strtotime($sec['created_at'])); ?></small>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button onclick="openSectionModal(<?php echo $sec['id']; ?>)" class="p-1 text-blue-600 hover:text-blue-800" title="تعديل">
                            <i data-feather="edit-2" class="w-5 h-5"></i>
                        </button>
                        <button onclick="confirmDelete(<?php echo $sec['id']; ?>, 'delete_section', '<?php echo base_url('admin/ajax_handler.php'); ?>', 'section-item-<?php echo $sec['id']; ?>')" class="p-1 text-red-600 hover:text-red-800" title="حذف">
                            <i data-feather="trash-2" class="w-5 h-5"></i>
                        </button>
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
                    <label for="section_name_input" class="block text-sm font-medium text-gray-700 mb-1">اسم القسم <span class="text-red-500">*</span>:</label>
                    <input type="text" name="name" id="section_name_input" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div id="section_content_container">
                    <label for="section_content" class="block text-sm font-medium text-gray-700 mb-1">المحتوى:</label>
                    <textarea name="content" id="section_content" rows="8" class="tinymceeditor mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="image_file_input" class="block text-sm font-medium text-gray-700 mb-1">صورة القسم (اختياري):</label>
                    <input type="file" name="image_file" id="image_file_input" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                    <div id="image_url_preview_container" class="mt-2"></div>
                    <input type="hidden" name="existing_image_url" id="existing_image_url_field">
                     <label class="inline-flex items-center mt-1 text-xs" id="remove_image_url_label" style="display:none;">
                        <input type="checkbox" name="remove_image_file" id="remove_image_file_checkbox" value="1" class="form-checkbox h-4 w-4 text-red-600">
                        <span class="ml-2 text-red-600">إزالة الصورة الحالية</span>
                    </label>
                </div>
                <div>
                    <label for="video_url_input" class="block text-sm font-medium text-gray-700 mb-1">رابط الفيديو (اختياري, e.g., YouTube URL):</label>
                    <input type="url" name="video_url" id="video_url_input" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm ltr text-left" placeholder="https://www.youtube.com/watch?v=example">
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
    });

    function openSectionModal(sectionId = 0) {
        const form = document.getElementById('sectionForm');
        form.reset();
        destroyExistingTinyMCEInstance('#section_content');

        document.getElementById('section_id_field').value = sectionId;
        document.getElementById('image_url_preview_container').innerHTML = '';
        document.getElementById('existing_image_url_field').value = '';
        document.getElementById('image_file_input').value = '';
        document.getElementById('remove_image_file_checkbox').checked = false;
        document.getElementById('remove_image_url_label').style.display = 'none';


        if (sectionId > 0) {
            document.getElementById('sectionModalTitle').textContent = 'تعديل القسم';
            fetchWithCSRF1(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_section_details&id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.section) {
                        const sec = data.section;
                        document.getElementById('section_name_input').value = sec.name || '';
                        tinymce.get('section_content')?.setContent(sec.content || '');
                        document.getElementById('video_url_input').value = sec.video_url || '';

                        if (sec.image_url) {
                            document.getElementById('existing_image_url_field').value = sec.image_url;
                            const imgPreview = `<img src="<?php echo UPLOAD_URL; ?>${sec.image_url}" class="h-20 w-auto rounded mt-1" alt="Preview">`;
                            document.getElementById('image_url_preview_container').innerHTML = imgPreview;
                            document.getElementById('remove_image_url_label').style.display = 'inline-flex';
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

    document.getElementById('remove_image_file_checkbox').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('image_url_preview_container').style.opacity = '0.5';
        } else {
            document.getElementById('image_url_preview_container').style.opacity = '1';
        }
    });

    // Assuming fetchWithCSRF1 is globally available or defined elsewhere (e.g. admin.js)
    // If not, it should be defined here or included.
    // function fetchWithCSRF1(url, options = {}) { ... }


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
