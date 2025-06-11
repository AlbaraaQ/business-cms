// بدلاً من استدعاء config.php مباشرة
// أنشئ ملف api/check-installed.php:

<?php
echo file_exists(__DIR__ . '/../config/config.php') ? 'installed' : 'not_installed';
