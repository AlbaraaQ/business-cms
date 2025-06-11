<?php
// This file is included by admin/index.php when page=change_password
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}
?>
<div class="container mx-auto px-4 py-2">
    <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">تغيير كلمة المرور</h1>

    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl max-w-lg mx-auto">
        <?php
        if (isset($_SESSION['message'])) {
            $msg_type = $_SESSION['message']['type'] === 'success' ? 'green' : 'red';
            echo "<div class=\"bg-{$msg_type}-100 border-l-4 border-{$msg_type}-500 text-{$msg_type}-700 p-4 mb-6\" role=\"alert\">";
            echo "<p>" . htmlspecialchars($_SESSION['message']['text']) . "</p>";
            echo "</div>";
            unset($_SESSION['message']);
        }
        ?>

        <form action="<?php echo base_url('admin/auth.php?action=change_password_process'); ?>" method="POST" class="space-y-6">
            <?php echo csrf_input_field(); ?>
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الحالية <span class="text-red-500">*</span></label>
                <input type="password" name="current_password" id="current_password" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الجديدة <span class="text-red-500">*</span></label>
                <input type="password" name="new_password" id="new_password" required minlength="8"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">يجب أن تكون 8 أحرف على الأقل.</p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور الجديدة <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                    تحديث كلمة المرور
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Optional: Client-side validation for matching new passwords
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    if (newPassword && confirmPassword) {
        function validateNewPasswords() {
            if (newPassword.value !== confirmPassword.value && confirmPassword.value.length > 0) {
                confirmPassword.setCustomValidity('كلمتا المرور الجديدتان غير متطابقتين.');
                confirmPassword.classList.add('border-red-500');
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('border-red-500');
            }
        }
        newPassword.addEventListener('input', validateNewPasswords);
        confirmPassword.addEventListener('input', validateNewPasswords);
    }
</script>
