<?php
if (!defined('ADMIN_AREA')) {
    die('Access denied');
}
$current_page = sanitize_input($_GET['page'] ?? 'dashboard');
?>
<!-- Sidebar -->
<aside id="adminSidebar" class="w-64 bg-white shadow-lg fixed top-16 right-0 z-30 md:top-0 transform translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out print:hidden overflow-y-auto h-[calc(100vh-4rem)] md:h-screen"> {/* RTL Change: -translate-x-full to translate-x-full for initial off-screen right */}
    <div class="flex flex-col h-full">
        <div class="p-4 flex-grow">
            <!-- Logo / Branding -->
            <div class="mb-8 flex items-center justify-center md:justify-start">
                <a href="<?php echo base_url('admin/index.php?page=dashboard'); ?>" class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-pink-700 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-md">
                        <?php echo mb_substr(defined('SITE_NAME') ? SITE_NAME : 'S', 0, 1, 'UTF-8'); ?>
                    </div>
                    <span class="mr-3 text-xl font-semibold text-gray-700 hidden md:inline"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'النظام'; ?></span>
                </a>
            </div>

            <!-- Main Navigation -->
            <nav class="space-y-1">
                <a href="<?php echo base_url('admin/index.php?page=dashboard'); ?>" class="sidebar-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
                    <i data-feather="home" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                    <span>لوحة التحكم</span>
                </a>

                <!-- Content Management Section -->
                <div class="mt-6">
                    <h3 class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">إدارة المحتوى</h3>
                    <div class="space-y-1">
                        <a href="<?php echo base_url('admin/index.php?page=sections'); ?>" class="sidebar-link <?php echo ($current_page === 'sections' || $current_page === 'homepage_sections') ? 'active' : ''; ?>">
                            <i data-feather="layout" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>أقسام الموقع</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=services'); ?>" class="sidebar-link <?php echo ($current_page === 'services') ? 'active' : ''; ?>">
                            <i data-feather="briefcase" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>الخدمات</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=projects'); ?>" class="sidebar-link <?php echo ($current_page === 'projects') ? 'active' : ''; ?>">
                            <i data-feather="archive" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>المشاريع</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=testimonials'); ?>" class="sidebar-link <?php echo ($current_page === 'testimonials') ? 'active' : ''; ?>">
                            <i data-feather="message-square" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>آراء العملاء</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=facts'); ?>" class="sidebar-link <?php echo ($current_page === 'facts') ? 'active' : ''; ?>">
                            <i data-feather="bar-chart-2" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>الحقائق والأرقام</span>
                        </a>
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="mt-6">
                    <h3 class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">الإعدادات</h3>
                    <div class="space-y-1">
                        <a href="<?php echo base_url('admin/index.php?page=site_settings'); ?>" class="sidebar-link <?php echo ($current_page === 'site_settings') ? 'active' : ''; ?>">
                            <i data-feather="tool" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>إعدادات الموقع</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=users'); ?>" class="sidebar-link <?php echo ($current_page === 'users') ? 'active' : ''; ?>">
                            <i data-feather="users" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>إدارة المستخدمين</span>
                        </a>
                        <a href="<?php echo base_url('admin/index.php?page=change_password'); ?>" class="sidebar-link <?php echo ($current_page === 'change_password') ? 'active' : ''; ?>">
                            <i data-feather="lock" class="mr-3"></i> {/* RTL Change: ml-3 to mr-3 */}
                            <span>تغيير كلمة المرور</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Footer Links -->
        <div class="p-4 border-t border-gray-200">
            <a href="<?php echo base_url(''); ?>" target="_blank" class="flex items-center justify-center md:justify-start text-sm text-gray-600 hover:text-pink-600 transition-colors">
                <i data-feather="external-link" class="mr-2 h-4 w-4"></i> {/* RTL Change: ml-2 to mr-2 */}
                <span>عرض الموقع العام</span>
            </a>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const sidebar = document.getElementById('adminSidebar');
    
    if(mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('translate-x-full'); // RTL Change: from -translate-x-full
            sidebar.classList.toggle('translate-x-0');
            
            // Change icon based on state
            const icon = mobileMenuButton.querySelector('i');
            if(sidebar.classList.contains('translate-x-full')) { // RTL Change: from -translate-x-full
                icon.setAttribute('data-feather', 'menu');
            } else {
                icon.setAttribute('data-feather', 'x');
            }
            feather.replace();
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if(window.innerWidth < 768 && sidebar && !sidebar.classList.contains('translate-x-full') && // Check if sidebar is open
           !sidebar.contains(event.target) &&
           event.target !== mobileMenuButton && !mobileMenuButton.contains(event.target)) {
            sidebar.classList.add('translate-x-full'); // RTL Change: from -translate-x-full
            sidebar.classList.remove('translate-x-0');
            
            const icon = mobileMenuButton.querySelector('i');
            icon.setAttribute('data-feather', 'menu');
            feather.replace();
        }
    });
    
    // Initialize feather icons
    feather.replace();
});
</script>