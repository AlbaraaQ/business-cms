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
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">العنوان</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الرقم/القيمة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الأيقونة</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الترتيب</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $facts = $db->query("SELECT * FROM facts ORDER BY `order` ASC, title ASC");
                if (empty($facts)): ?>
                    <tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">لا توجد حقائق مضافة حالياً.</td></tr>
                <?php else:
                    foreach ($facts as $fact): ?>
                    <tr id="fact-row-<?php echo $fact['fact_id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fact['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($fact['prefix'] ?? '') . htmlspecialchars($fact['number']) . htmlspecialchars($fact['suffix'] ?? ''); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($fact['icon']): ?>
                                <i data-feather="<?php echo htmlspecialchars($fact['icon']); ?>" class="inline-block"></i> (<?php echo htmlspecialchars($fact['icon']); ?>)
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($fact['order']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-1 space-x-reverse">
                            <button onclick="openFactModal(<?php echo $fact['fact_id']; ?>)" class="text-pink-600 hover:text-pink-900" title="تعديل"><i data-feather="edit" class="w-5 h-5"></i></button>
                            <button onclick="confirmDelete(<?php echo $fact['fact_id']; ?>, 'fact', '<?php echo base_url('admin/ajax_handler.php'); ?>')" class="text-red-600 hover:text-red-900" title="حذف"><i data-feather="trash-2" class="w-5 h-5"></i></button>
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
            <input type="hidden" name="fact_id" id="fact_id_field" value="0">
            
            <div class="modal-body">
                <div>
                    <label for="fact_title" class="block text-sm font-medium text-gray-700 mb-1">العنوان <span class="text-red-500">*</span>:</label>
                    <input type="text" name="title" id="fact_title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <div>
                    <label for="fact_number" class="block text-sm font-medium text-gray-700 mb-1">الرقم/القيمة <span class="text-red-500">*</span>:</label>
                    <input type="text" name="number" id="fact_number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm" placeholder="مثال: 100 أو 25+">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="fact_prefix" class="block text-sm font-medium text-gray-700 mb-1">بادئة (اختياري):</label>
                        <input type="text" name="prefix" id="fact_prefix" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm" placeholder="مثال: $">
                    </div>
                    <div>
                        <label for="fact_suffix" class="block text-sm font-medium text-gray-700 mb-1">لاحقة (اختياري):</label>
                        <input type="text" name="suffix" id="fact_suffix" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm" placeholder="مثال: + أو %">
                    </div>
                </div>
                <div>
                    <label for="fact_icon" class="block text-sm font-medium text-gray-700 mb-1">الأيقونة (اسم أيقونة Feather - اختياري):</label>
                    <input type="text" name="icon" id="fact_icon" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm ltr text-left" placeholder="e.g., package, users, award">
                     <p class="text-xs text-gray-500 mt-1">راجع <a href="https://feathericons.com/" target="_blank" class="text-pink-600 hover:underline">مكتبة Feather Icons</a> للأسماء.</p>
                </div>
                 <div>
                    <label for="fact_order" class="block text-sm font-medium text-gray-700 mb-1">ترتيب الظهور:</label>
                    <input type="number" name="order" id="fact_order" value="0" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                </div>
                <!-- Hidden field for homepage_section_id if needed in future -->
                <!-- <input type="hidden" name="homepage_section_id" id="fact_homepage_section_id" value=""> -->
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
            fetch(`<?php echo base_url('admin/ajax_handler.php'); ?>?action=get_fact_details&id=${factId}&<?php echo CSRF_TOKEN_NAME; ?>=<?php echo generate_csrf_token(); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.fact) {
                        const item = data.fact;
                        document.getElementById('fact_title').value = item.title || '';
                        document.getElementById('fact_number').value = item.number || '';
                        document.getElementById('fact_icon').value = item.icon || '';
                        document.getElementById('fact_prefix').value = item.prefix || '';
                        document.getElementById('fact_suffix').value = item.suffix || '';
                        document.getElementById('fact_order').value = item.order || 0;
                        // document.getElementById('fact_homepage_section_id').value = item.homepage_section_id || '';
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
