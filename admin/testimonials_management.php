<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إدارة آراء العملاء";

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
        <button onclick="openTestimonialModal()" class="bg-pink-600 hover:bg-pink-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors">
            <i data-feather="plus" class="inline-block mr-2"></i> إضافة رأي جديد
        </button>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-xl overflow-x-auto">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">قائمة آراء العملاء</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم العميل</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصورة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الرأي (مقتطف)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الترتيب</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
            </thead>
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم المؤلف</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصورة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الرأي (مقتطف)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإضافة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                // Updated SQL query
                $testimonials = $db->query("SELECT id, author_name, testimonial_text, author_image_url, created_at FROM testimonials ORDER BY created_at DESC");
                if (empty($testimonials)): ?>
                    <tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">لا توجد آراء مضافة حالياً.</td></tr>
                <?php else:
                    foreach ($testimonials as $testimonial): ?>
                    <tr id="testimonial-row-<?php echo $testimonial['id']; ?>"> {/* Use id */}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($testimonial['author_name']); ?></div>
                            {/* client_title_company removed */}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($testimonial['author_image_url']): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($testimonial['author_image_url']); ?>" alt="<?php echo htmlspecialchars($testimonial['author_name']); ?>" class="h-12 w-12 object-cover rounded-full">
                            <?php else: ?>
                                <span class="text-xs text-gray-400">لا يوجد</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-normal text-sm text-gray-700 max-w-sm"><?php echo htmlspecialchars(truncate_text($testimonial['testimonial_text'], 100)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('Y-m-d', strtotime($testimonial['created_at'])); ?>
                        </td>
                        {/* Status and Order columns removed */}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 space-x-reverse">
                            <button onclick="openTestimonialModal(<?php echo $testimonial['id']; ?>)" class="text-pink-600 hover:text-pink-900" title="تعديل"><i data-feather="edit" class="w-5 h-5"></i></button>
                            {/* Toggle Approval button removed */}
                            <button onclick="confirmDelete(<?php echo $testimonial['id']; ?>, 'delete_testimonial', '<?php echo base_url('admin/ajax_handler.php'); ?>', ' testimonial-row-<?php echo $testimonial['id']; ?>')" class="text-red-600 hover:text-red-900" title="حذف"><i data-feather="trash-2" class="w-5 h-5"></i></button>
                        </td>
                    </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Testimonial Modal -->
<div id="testimonialModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="testimonialModalTitle">إضافة/تعديل رأي عميل</h3>
            <span class="close-modal-btn" onclick="closeModal('testimonialModal')">&times;</span>
        </div>
        <form id="testimonialForm" action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return ajaxSubmitForm(this, testimonialFormCallback);">
            <?php echo csrf_input_field(); ?>
            <input type="hidden" name="action" value="save_testimonial">
            <input type="hidden" name="testimonial_id" id="testimonial_id_field" value="0"> {/* This will map to id */}
            
            <div class="modal-body">
                <div>
                    <label for="author_name" class="block text-sm font-medium text-gray-700 mb-1">اسم المؤلف <span class="text-red-500">*</span>:</label>
                    <input type="text" name="author_name" id="author_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                {/* client_title_company field removed */}
                <div>
                    <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">نص الرأي <span class="text-red-500">*</span>:</label>
                    <textarea name="feedback" id="feedback" rows="5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="author_image_file" class="block text-sm font-medium text-gray-700 mb-1">صورة المؤلف (اختياري):</label>
                    <input type="file" name="author_image_file" id="author_image_file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                    <div id="author_image_preview_container" class="mt-2"></div>
                    <input type="hidden" name="existing_author_image_url" id="existing_author_image_url_field">
                    <label class="inline-flex items-center mt-1 text-xs" id="remove_author_image_label" style="display:none;">
                        <input type="checkbox" name="remove_author_image" id="remove_author_image_checkbox" value="1" class="form-checkbox h-4 w-4 text-red-600">
                        <span class="ml-2 text-red-600">إزالة الصورة الحالية</span>
                    </label>
                </div>
                {/* Rating, order, and is_approved fields removed */}
            </div>
            <div class="modal-footer bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-pink-600 text-base font-medium text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 sm:ml-3 sm:w-auto sm:text-sm">
                    حفظ الرأي
                </button>
                <button type="button" onclick="closeModal('testimonialModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTestimonialModal(testimonialId = 0) {
        const form = document.getElementById('testimonialForm');
        form.reset();
        
        document.getElementById('testimonial_id_field').value = testimonialId; // Maps to 'id'
        document.getElementById('author_image_preview_container').innerHTML = '';
        document.getElementById('existing_author_image_url_field').value = '';
        document.getElementById('author_image_file').value = '';
        document.getElementById('remove_author_image_checkbox').checked = false;
        document.getElementById('remove_author_image_label').style.display = 'none';


        if (testimonialId > 0) {
            document.getElementById('testimonialModalTitle').textContent = 'تعديل رأي المؤلف';
            // Fetch with new field names expected
            fetch(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_testimonial_details&id=${testimonialId}&<?php echo CSRF_TOKEN_NAME; ?>=<?php echo generate_csrf_token(); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.testimonial) {
                        const item = data.testimonial;
                        document.getElementById('author_name').value = item.author_name || '';
                        document.getElementById('feedback').value = item.testimonial_text || '';
                        // Removed client_title_company, rating, order, is_approved
                        
                        if (item.author_image_url) {
                            document.getElementById('existing_author_image_url_field').value = item.author_image_url;
                            const imgPreview = `<img src="<?php echo UPLOAD_URL; ?>${item.author_image_url}" class="h-20 w-auto rounded mt-1" alt="Preview">`;
                            document.getElementById('author_image_preview_container').innerHTML = imgPreview;
                            document.getElementById('remove_author_image_label').style.display = 'inline-flex';
                        }
                    } else {
                        adminPanel.showAlert('فشل تحميل بيانات الرأي: ' + (data.message || 'خطأ غير معروف'), 'error');
                    }
                })
                .catch(error => {
                     adminPanel.showAlert('خطأ في الاتصال بالخادم.', 'error');
                     console.error('Fetch testimonial error:', error);
                });
        } else {
            document.getElementById('testimonialModalTitle').textContent = 'إضافة رأي جديد';
            // Default values for new entry if any (e.g. is_approved was here, but removed)
        }
        showModal('testimonialModal');
    }
    
    // This function is effectively replaced by checking a checkbox 'remove_author_image' during form submission in ajax_handler
    // The preview can be simply cleared or updated by the file input's onchange event if needed.
    // For simplicity, direct preview update on remove checkbox click can be added if desired,
    // but server-side logic will handle the actual removal based on checkbox.
    // For now, the removeTestimonialImagePreview function is not strictly needed with the checkbox.
    // Let's ensure the checkbox toggles the preview state.
    document.getElementById('remove_author_image_checkbox').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('author_image_preview_container').style.opacity = '0.5';
        } else {
            document.getElementById('author_image_preview_container').style.opacity = '1';
        }
    });


    function testimonialFormCallback(response) {
        if (response.success) {
            adminPanel.showAlert(response.message || 'تم حفظ الرأي بنجاح!', 'success'); // Updated message
            closeModal('testimonialModal');
            setTimeout(() => window.location.reload(), 1000); 
        } else {
            adminPanel.showAlert(response.message || 'فشل حفظ الرأي.', 'error');
        }
    }

    // toggleTestimonialApproval function removed as 'is_approved' field is removed.
</script>
