<?php
// Handles authentication actions like logout.
// Login is handled in index.php for this structure.

require_once 'init.php'; // Ensures session, config etc. are loaded

$action = sanitize_input($_GET['action'] ?? '');

if ($action === 'logout') {
    if (!verify_csrf_token($_GET['csrf_token'] ?? null)) { // Updated to use csrf_token
        $_SESSION['message'] = ['type' => 'error', 'text' => 'محاولة تسجيل خروج غير صالحة.'];
        redirect(base_url('admin/index.php?page=dashboard'));
    }

    // Unset all session variables for the admin
    $_SESSION = array(); // Clear the session array

    // If session cookies are used, delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();

    // Redirect to login page with a logged out message
    redirect(base_url('admin/index.php?loggedout=true'));
    exit();
} elseif ($action === 'change_password_process') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(base_url('admin/index.php?page=change_password'));
    }
    if (!is_admin_logged_in()) {
        redirect(base_url('admin/index.php'));
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { // Updated to use csrf_token
        $_SESSION['message'] = ['type' => 'error', 'text' => 'خطأ في التحقق (CSRF). يرجى المحاولة مرة أخرى.'];
        redirect(base_url('admin/index.php?page=change_password'));
    }

    global $db;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'جميع حقول كلمة المرور مطلوبة.'];
        redirect(base_url('admin/index.php?page=change_password'));
    }
    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'كلمتا المرور الجديدتان غير متطابقتين.'];
        redirect(base_url('admin/index.php?page=change_password'));
    }
    if (strlen($new_password) < 8) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.'];
        redirect(base_url('admin/index.php?page=change_password'));
    }

    try {
        $user = $db->queryOne("SELECT password_hash FROM users WHERE id = ?", [$_SESSION['admin_user_id']]);
        if ($user && password_verify($current_password, $user['password_hash'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$new_password_hash, $_SESSION['admin_user_id']]);
            
            // Forcing re-login after password change is good practice
            session_unset();
            session_destroy();
            
            $login_url = base_url('admin/index.php?password_changed=true');
            header("Location: " . $login_url);
            exit();
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'كلمة المرور الحالية غير صحيحة.'];
        }
    } catch (Exception $e) {
        log_error("Password change failed for user ID {$_SESSION['admin_user_id']}: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'حدث خطأ أثناء تغيير كلمة المرور.'];
    }
    redirect(base_url('admin/index.php?page=change_password'));

} else {
    // No valid action specified, redirect to dashboard or login
    if (is_admin_logged_in()) {
        redirect(base_url('admin/index.php?page=dashboard'));
    } else {
        redirect(base_url('admin/index.php'));
    }
}
?>