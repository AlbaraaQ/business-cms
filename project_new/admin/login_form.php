<?php
// This file is intended to be included by admin/index.php
if (!defined('ADMIN_AREA')) {
    die('Access denied');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
        }
        .login-container {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        .btn-login {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%);
            transform: translateY(-2px);
        }
        .input-field:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-container max-w-md w-full rounded-xl overflow-hidden border border-gray-200">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full mx-auto mb-4 flex items-center justify-center shadow-lg">
                <svg class="w-10 h-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">لوحة التحكم الإدارية</h1>
            <p class="text-purple-200 mt-1">يجب تسجيل الدخول للمتابعة</p>
        </div>

        <!-- Messages -->
        <div class="px-6 pt-4">
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="bg-red-50 border-r-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <strong class="font-bold">خطأ!</strong>
                    </div>
                    <p class="mt-1"><?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['loggedout'])): ?>
                <div class="bg-green-50 border-r-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <strong class="font-bold">تم بنجاح!</strong>
                    </div>
                    <p class="mt-1">تم تسجيل خروجك بنجاح.</p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['password_changed'])): ?>
                <div class="bg-green-50 border-r-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <strong class="font-bold">تم بنجاح!</strong>
                    </div>
                    <p class="mt-1">تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول مرة أخرى.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Login Form -->
        <form class="px-6 pb-8 space-y-4" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <?php echo csrf_input_field(); ?>
            <input type="hidden" name="action" value="login">

            <div class="space-y-2">
                <label for="username" class="block text-sm font-medium text-gray-700">اسم المستخدم</label>
                <input id="username" name="username" type="text" autocomplete="username" required
                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="أدخل اسم المستخدم"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-gray-700">كلمة المرور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="input-field w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="أدخل كلمة المرور">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="btn-login w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    تسجيل الدخول
                </button>
            </div>

            <div class="text-center pt-2">
                <a href="<?php echo rtrim(base_url(), '/'); ?>/" class="text-sm text-purple-600 hover:text-purple-500 hover:underline">
                    العودة إلى الموقع الرئيسي
                </a>
            </div>
        </form>
    </div>
</body>
</html>