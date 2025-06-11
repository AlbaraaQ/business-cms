<?php
// This file is included by admin/index.php when user is logged in and page=dashboard
if (!defined('ADMIN_AREA') || !is_admin_logged_in()) {
    die('Access denied');
}

global $db; // Use the global $db connection from init.php

// Fetch some stats for the dashboard (Example)
$stats = [
    'services_count' => 0,
    'projects_count' => 0,
    'testimonials_count' => 0,
    'users_count' => 0,
];

try {
    $stats['services_count'] = $db->queryOne("SELECT COUNT(*) as count FROM services")['count'] ?? 0;
    $stats['projects_count'] = $db->queryOne("SELECT COUNT(*) as count FROM projects")['count'] ?? 0;
    // Updated testimonials count query - removed WHERE is_approved = 1
    $stats['testimonials_count'] = $db->queryOne("SELECT COUNT(*) as count FROM testimonials")['count'] ?? 0;
    $stats['users_count'] = $db->queryOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
} catch (Exception $e) {
    log_error("Dashboard stats query failed: " . $e->getMessage());
    // You might want to display an error on the dashboard or just show 0s
}

?>
<div class="container mx-auto px-4 py-2">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">لوحة التحكم الرئيسية</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Stat Card: Services -->
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-4xl font-bold"><?php echo htmlspecialchars($stats['services_count']); ?></p>
                    <p class="text-lg">الخدمات</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                     <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                </div>
            </div>
        </div>
        <!-- Stat Card: Projects -->
         <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-4xl font-bold"><?php echo htmlspecialchars($stats['projects_count']); ?></p>
                    <p class="text-lg">المشاريع</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                </div>
            </div>
        </div>
        <!-- Stat Card: Testimonials -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-4xl font-bold"><?php echo htmlspecialchars($stats['testimonials_count']); ?></p>
                    <p class="text-lg">آراء العملاء</p> {/* Label updated */}
                </div>
                 <div class="bg-white bg-opacity-20 p-3 rounded-full">
                   <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-3.861 8.25-8.625 8.25S3.75 16.556 3.75 12c0-4.556 3.861-8.25 8.625-8.25S21 7.444 21 12z" /></svg>
                </div>
            </div>
        </div>
        <!-- Stat Card: Users -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-4xl font-bold"><?php echo htmlspecialchars($stats['users_count']); ?></p>
                    <p class="text-lg">المستخدمون (المدراء)</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">مرحباً بك <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
        <p class="text-gray-600">
            هذه هي لوحة التحكم الرئيسية لموقع حداد جده. يمكنك من هنا إدارة محتوى الموقع مثل الخدمات، المشاريع، آراء العملاء، والإعدادات العامة.
        </p>
        <p class="text-gray-600 mt-2">
            استخدم القائمة الجانبية للتنقل بين أقسام الإدارة المختلفة.
        </p>
        <!-- Placeholder for quick actions or recent activity -->
        <div class="mt-6 border-t pt-4">
            <h3 class="text-lg font-semibold text-gray-700">روابط سريعة (قيد التطوير):</h3>
            <ul class="list-disc list-inside text-pink-600 mt-2 space-y-1">
                <li><a href="#" class="hover:underline">إضافة خدمة جديدة</a></li>
                <li><a href="#" class="hover:underline">إضافة مشروع جديد</a></li>
                <li><a href="#" class="hover:underline">عرض طلبات العملاء</a></li>
            </ul>
        </div>
    </div>
</div>
