<?php
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db;
$page_title = "إدارة الحقائق والأرقام";

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_type = $_SESSION['message']['type'];
    unset($_SESSION['message']);
}

// Fetch homepage sections of type 'facts_counter' to link facts if desired (optional for current scope)
// $facts_sections = $db->query("SELECT section_id, title FROM homepage_sections WHERE section_type = 'facts_counter' ORDER BY title ASC");

?>

<div class="container mx-auto px-4 py-2">
    <div class="flex justify-between items-center mb-6 border-b pb-2">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
        <button onclick="openFactModal()" class="bg-pink-600 hover:bg-pink-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors">
            <i data-feather="plus" class="inline-block mr-2"></i> إضافة حقيقة جديدة
        </button>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-xl overflow-x-auto">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">قائمة الحقائق</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النص</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">القيمة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الأيقونة (Class)</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإضافة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                // Updated SQL Query
                $facts = $db->query("SELECT id, fact_text, fact_value, icon_class, created_at FROM facts ORDER BY created_at DESC");
                if (empty($facts)): ?>
                    <tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">لا توجد حقائق مضافة حالياً.</td></tr>
                <?php else:
                    foreach ($facts as $fact): ?>
                    <tr id="fact-row-<?php echo $fact['id']; ?>"> {/* Use id */}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fact['fact_text']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($fact['fact_value']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($fact['icon_class']): ?>
                                <i class="<?php echo htmlspecialchars($fact['icon_class']); ?> inline-block"></i> (<?php echo htmlspecialchars($fact['icon_class']); ?>)
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('Y-m-d', strtotime($fact['created_at'])); ?>
                        </td>
                        {/* Order column removed */}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 space-x-reverse">
                            <button onclick="openFactModal(<?php echo $fact['id']; ?>)" class="text-pink-600 hover:text-pink-900" title="تعديل"><i data-feather="edit" class="w-5 h-5"></i></button>
                            <button onclick="confirmDelete(<?php echo $fact['id']; ?>, 'delete_fact', '<?php echo base_url('admin/ajax_handler.php'); ?>', 'fact-row-<?php echo $fact['id']; ?>')" class="text-red-600 hover:text-red-900" title="حذف"><i data-feather="trash-2" class="w-5 h-5"></i></button>
                        </td>
                    </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fact Modal -->
<div id="factModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="factModalTitle">إضافة/تعديل حقيقة</h3>
            <span class="close-modal-btn" onclick="closeModal('factModal')">&times;</span>
        </div>
        <form id="factForm" action="<?php echo base_url('admin/ajax_handler.php'); ?>" method="POST" class="space-y-4" onsubmit="return ajaxSubmitForm(this, factFormCallback);">
            <?php echo csrf_input_field(); ?>
            <input type="hidden" name="action" value="save_fact">
            <input type="hidden" name="fact_id" id="fact_id_field" value="0"> {/* Maps to id */}
            
            <div class="modal-body">
                <div>
                    <label for="fact_text_input" class="block text-sm font-medium text-gray-700 mb-1">نص الحقيقة <span class="text-red-500">*</span>:</label>
                    <input type="text" name="fact_text" id="fact_text_input" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="fact_value_input" class="block text-sm font-medium text-gray-700 mb-1">القيمة <span class="text-red-500">*</span>:</label>
                    <input type="text" name="fact_value" id="fact_value_input" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm" placeholder="مثال: 100 أو +25 مشروعاً">
                </div>
                {/* Prefix and Suffix fields removed */}
                <div>
                    <label for="fact_icon_class_input" class="block text-sm font-medium text-gray-700 mb-1">الأيقونة (Font Awesome Class - اختياري):</label>
                    <input type="text" name="icon_class" id="fact_icon_class_input" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm ltr text-left" placeholder="e.g., fas fa-briefcase">
                     <p class="text-xs text-gray-500 mt-1">راجع <a href="https://fontawesome.com/icons" target="_blank" class="text-pink-600 hover:underline">مكتبة Font Awesome</a> للأسماء.</p>
                </div>
                {/* Order field removed */}
            </div>
            <div class="modal-footer bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-pink-600 text-base font-medium text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 sm:ml-3 sm:w-auto sm:text-sm">
                    حفظ الحقيقة
                </button>
                <button type="button" onclick="closeModal('factModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openFactModal(factId = 0) {
        const form = document.getElementById('factForm');
        form.reset();
        document.getElementById('fact_id_field').value = factId;

        if (factId > 0) {
            document.getElementById('factModalTitle').textContent = 'تعديل الحقيقة';
            fetch(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_fact_details&id=${factId}&<?php echo CSRF_TOKEN_NAME; ?>=<?php echo generate_csrf_token(); ?>`) // Ensure CSRF for GET if needed, or remove
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.fact) {
                        const item = data.fact;
                        document.getElementById('fact_text_input').value = item.fact_text || '';
                        document.getElementById('fact_value_input').value = item.fact_value || '';
                        document.getElementById('fact_icon_class_input').value = item.icon_class || '';
                        // prefix, suffix, order removed
                    } else {
                        adminPanel.showAlert('فشل تحميل بيانات الحقيقة: ' + (data.message || 'خطأ غير معروف'), 'error');
                    }
                })
                .catch(error => {
                     adminPanel.showAlert('خطأ في الاتصال بالخادم.', 'error');
                     console.error('Fetch fact error:', error);
                });
        } else {
            document.getElementById('factModalTitle').textContent = 'إضافة حقيقة جديدة';
        }
        showModal('factModal');
    }
    
    function factFormCallback(response) {
        if (response.success) {
            adminPanel.showAlert(response.message || 'تم حفظ الحقيقة بنجاح!', 'success');
            closeModal('factModal');
            setTimeout(() => window.location.reload(), 1000); 
        } else {
            adminPanel.showAlert(response.message || 'فشل حفظ الحقيقة.', 'error');
        }
    }
</script>
