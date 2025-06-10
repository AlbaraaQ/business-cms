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
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $testimonials = $db->query("SELECT * FROM testimonials ORDER BY `order` ASC, created_at DESC");
                if (empty($testimonials)): ?>
                    <tr><td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">لا توجد آراء مضافة حالياً.</td></tr>
                <?php else:
                    foreach ($testimonials as $testimonial): ?>
                    <tr id="testimonial-row-<?php echo $testimonial['testimonial_id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($testimonial['client_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($testimonial['client_title_company'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($testimonial['client_photo']): ?>
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($testimonial['client_photo']); ?>" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" class="h-12 w-12 object-cover rounded-full">
                            <?php else: ?>
                                <span class="text-xs text-gray-400">لا يوجد</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-normal text-sm text-gray-700 max-w-sm"><?php echo htmlspecialchars(truncate_text($testimonial['feedback'], 100)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $testimonial['is_approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo $testimonial['is_approved'] ? 'معتمد' : 'بانتظار الموافقة'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($testimonial['order']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 space-x-reverse">
                            <button onclick="openTestimonialModal(<?php echo $testimonial['testimonial_id']; ?>)" class="text-pink-600 hover:text-pink-900" title="تعديل"><i data-feather="edit" class="w-5 h-5"></i></button>
                            <button onclick="toggleTestimonialApproval(<?php echo $testimonial['testimonial_id']; ?>, this)" class="text-blue-600 hover:text-blue-900" title="<?php echo $testimonial['is_approved'] ? 'إلغاء الموافقة' : 'اعتماد'; ?>">
                                <i data-feather="<?php echo $testimonial['is_approved'] ? 'eye-off' : 'eye'; ?>" class="w-5 h-5"></i>
                            </button>
                            <button onclick="confirmDelete(<?php echo $testimonial['testimonial_id']; ?>, 'testimonial', '<?php echo base_url('admin/ajax_handler.php'); ?>')" class="text-red-600 hover:text-red-900" title="حذف"><i data-feather="trash-2" class="w-5 h-5"></i></button>
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
            <input type="hidden" name="testimonial_id" id="testimonial_id_field" value="0">
            
            <div class="modal-body">
                <div>
                    <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">اسم العميل <span class="text-red-500">*</span>:</label>
                    <input type="text" name="client_name" id="client_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="client_title_company" class="block text-sm font-medium text-gray-700 mb-1">المنصب/الشركة (اختياري):</label>
                    <input type="text" name="client_title_company" id="client_title_company" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">نص الرأي <span class="text-red-500">*</span>:</label>
                    <textarea name="feedback" id="feedback" rows="5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="client_photo" class="block text-sm font-medium text-gray-700 mb-1">صورة العميل (اختياري):</label>
                    <input type="file" name="client_photo" id="client_photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                    <div id="client_photo_preview_container" class="mt-2"></div>
                    <input type="hidden" name="existing_client_photo" id="existing_client_photo_field">
                </div>
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">التقييم (اختياري, 1-5):</label>
                    <input type="number" name="rating" id="rating" min="1" max="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                 <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-1">ترتيب الظهور:</label>
                    <input type="number" name="order" id="order" value="0" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_approved" id="is_approved" value="1" class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <label for="is_approved" class="ml-2 block text-sm text-gray-900">معتمد (مرئي في الموقع)</label>
                </div>
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
        
        document.getElementById('testimonial_id_field').value = testimonialId;
        document.getElementById('client_photo_preview_container').innerHTML = '';
        document.getElementById('existing_client_photo_field').value = '';
        document.getElementById('client_photo').value = ''; // Clear file input

        if (testimonialId > 0) {
            document.getElementById('testimonialModalTitle').textContent = 'تعديل رأي العميل';
            fetch(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_testimonial_details&id=${testimonialId}&<?php echo CSRF_TOKEN_NAME; ?>=<?php echo generate_csrf_token(); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.testimonial) {
                        const item = data.testimonial;
                        document.getElementById('client_name').value = item.client_name || '';
                        document.getElementById('client_title_company').value = item.client_title_company || '';
                        document.getElementById('feedback').value = item.feedback || '';
                        document.getElementById('rating').value = item.rating || '';
                        document.getElementById('order').value = item.order || 0;
                        document.getElementById('is_approved').checked = parseInt(item.is_approved) === 1;
                        
                        if (item.client_photo) {
                            document.getElementById('existing_client_photo_field').value = item.client_photo;
                            const imgPreview = `<img src="<?php echo UPLOAD_URL; ?>${item.client_photo}" class="h-20 w-auto rounded mt-1" alt="Preview"> <button type="button" class="text-red-500 text-xs" onclick="removeTestimonialImagePreview()">إزالة الصورة</button>`;
                            document.getElementById('client_photo_preview_container').innerHTML = imgPreview;
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
            document.getElementById('is_approved').checked = false; // Default to not approved for new entries
        }
        showModal('testimonialModal');
    }
    
    function removeTestimonialImagePreview() {
        document.getElementById('existing_client_photo_field').value = ''; 
        document.getElementById('client_photo_preview_container').innerHTML = '<span class="text-xs text-gray-500">تم تحديد الصورة الحالية للحذف.</span>';
        document.getElementById('client_photo').value = ''; // Clear file input
    }

    function testimonialFormCallback(response) {
        if (response.success) {
            adminPanel.showAlert(response.message || 'تم حفظ رأي العميل بنجاح!', 'success');
            closeModal('testimonialModal');
            setTimeout(() => window.location.reload(), 1000); 
        } else {
            adminPanel.showAlert(response.message || 'فشل حفظ الرأي.', 'error');
        }
    }

    function toggleTestimonialApproval(testimonialId, buttonElement) {
        const formData = new FormData();
        formData.append('action', 'toggle_testimonial_approval');
        formData.append('testimonial_id', testimonialId);
        formData.append('<?php echo CSRF_TOKEN_NAME; ?>', '<?php echo generate_csrf_token(); ?>');

        fetch('<?php echo base_url('admin/ajax_handler.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                adminPanel.showAlert(data.message || 'تم تحديث حالة الموافقة.', 'success');
                // Update UI dynamically
                const row = document.getElementById(`testimonial-row-${testimonialId}`);
                if (row) {
                    const statusSpan = row.querySelector('td:nth-child(4) span');
                    const iconElement = buttonElement.querySelector('i');
                    if (data.new_status == 1) {
                        statusSpan.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                        statusSpan.textContent = 'معتمد';
                        buttonElement.title = 'إلغاء الموافقة';
                        iconElement.setAttribute('data-feather', 'eye-off');
                    } else {
                        statusSpan.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
                        statusSpan.textContent = 'بانتظار الموافقة';
                        buttonElement.title = 'اعتماد';
                        iconElement.setAttribute('data-feather', 'eye');
                    }
                    feather.replace(); // Re-render Feather icons
                } else {
                     setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                adminPanel.showAlert(data.message || 'فشل تحديث حالة الموافقة.', 'error');
            }
        })
        .catch(error => {
            adminPanel.showAlert('خطأ في الاتصال بالخادم.', 'error');
            console.error('Toggle approval error:', error);
        });
    }
</script>
