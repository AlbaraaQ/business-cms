-- سكريبت قاعدة البيانات النهائي المحدث
-- يتضمن جميع الجداول والتحسينات المطلوبة

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS `company_website` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `company_website`;

-- جدول الإعدادات العامة
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) NOT NULL,
  `site_description` text,
  `site_keywords` text,
  `contact_email` varchar(255),
  `contact_phone` varchar(50),
  `contact_address` text,
  `facebook_url` varchar(255),
  `twitter_url` varchar(255),
  `instagram_url` varchar(255),
  `linkedin_url` varchar(255),
  `youtube_url` varchar(255),
  `whatsapp_number` varchar(50),
  `logo` varchar(255),
  `favicon` varchar(255),
  `footer_text` text,
  `google_analytics` text,
  `meta_title` varchar(255),
  `meta_description` text,
  `meta_keywords` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المستخدمين (المديرين)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255),
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'editor',
  `avatar` varchar(255),
  `last_login` timestamp NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج مستخدم افتراضي
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin');

-- جدول الخدمات
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `short_description` text,
  `description` longtext,
  `features` longtext,
  `main_image` varchar(255),
  `category` varchar(100),
  `price` decimal(10,2),
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `meta_title` varchar(255),
  `meta_description` text,
  `meta_keywords` text,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول صور الخدمات
CREATE TABLE IF NOT EXISTS `service_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255),
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_service_images_service` (`service_id`),
  CONSTRAINT `fk_service_images_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المشاريع
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `short_description` text,
  `description` longtext,
  `client_name` varchar(255),
  `project_url` varchar(255),
  `completion_date` date,
  `category` varchar(100),
  `technologies` text,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `meta_title` varchar(255),
  `meta_description` text,
  `meta_keywords` text,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول صور المشاريع
CREATE TABLE IF NOT EXISTS `project_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255),
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_project_images_project` (`project_id`),
  CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الرسائل
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50),
  `subject` varchar(255),
  `message` longtext NOT NULL,
  `service_id` int(11),
  `project_id` int(11),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_replied` tinyint(1) NOT NULL DEFAULT 0,
  `reply_message` longtext,
  `replied_at` timestamp NULL,
  `replied_by` int(11),
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_messages_service` (`service_id`),
  KEY `fk_messages_project` (`project_id`),
  KEY `fk_messages_replied_by` (`replied_by`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_messages_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الشهادات والتوصيات
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `client_position` varchar(255),
  `client_company` varchar(255),
  `client_image` varchar(255),
  `testimonial_text` longtext NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الحقائق والإحصائيات
CREATE TABLE IF NOT EXISTS `facts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `number` int(11) NOT NULL,
  `icon` varchar(100),
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول أقسام الصفحة الرئيسية
CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `title` varchar(255),
  `subtitle` varchar(255),
  `content` longtext,
  `image` varchar(255),
  `background_image` varchar(255),
  `button_text` varchar(100),
  `button_link` varchar(255),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول النسخ الاحتياطية
CREATE TABLE IF NOT EXISTS `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20),
  `backup_type` enum('manual','automatic') NOT NULL DEFAULT 'manual',
  `created_by` int(11),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_backups_created_by` (`created_by`),
  CONSTRAINT `fk_backups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100),
  `record_id` int(11),
  `old_values` longtext,
  `new_values` longtext,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_activity_log_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_record` (`table_name`, `record_id`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات SEO العامة
CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` varchar(50) NOT NULL,
  `page_id` int(11),
  `meta_title` varchar(255),
  `meta_description` text,
  `meta_keywords` text,
  `og_title` varchar(255),
  `og_description` text,
  `og_image` varchar(255),
  `canonical_url` varchar(500),
  `robots` varchar(100) DEFAULT 'index,follow',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_page` (`page_type`, `page_id`),
  KEY `idx_page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات الشبكات الاجتماعية
CREATE TABLE IF NOT EXISTS `social_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500),
  `username` varchar(100),
  `api_key` varchar(255),
  `api_secret` varchar(255),
  `access_token` varchar(500),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج إعدادات الشبكات الاجتماعية الافتراضية
INSERT INTO `social_settings` (`platform`, `url`, `is_active`, `sort_order`) VALUES
('facebook', '', 1, 1),
('twitter', '', 1, 2),
('instagram', '', 1, 3),
('linkedin', '', 1, 4),
('youtube', '', 1, 5),
('whatsapp', '', 1, 6);

-- جدول إحصائيات الموقع
CREATE TABLE IF NOT EXISTS `site_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(500) NOT NULL,
  `page_title` varchar(255),
  `visitor_ip` varchar(45),
  `user_agent` text,
  `referer` varchar(500),
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `session_id` varchar(100),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_url` (`page_url`),
  KEY `idx_visit_date` (`visit_date`),
  KEY `idx_visitor_ip` (`visitor_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية للإعدادات العامة
INSERT INTO `site_settings` (`site_name`, `site_description`, `contact_email`, `contact_phone`, `meta_title`, `meta_description`) VALUES
('موقع الشركة', 'وصف موقع الشركة', 'info@company.com', '+1234567890', 'موقع الشركة - الصفحة الرئيسية', 'وصف موقع الشركة للصفحة الرئيسية');

-- إدراج بيانات تجريبية للأقسام
INSERT INTO `sections` (`section_name`, `title`, `subtitle`, `content`, `is_active`, `sort_order`) VALUES
('hero', 'مرحباً بكم في موقعنا', 'نحن نقدم أفضل الخدمات', 'محتوى القسم الرئيسي', 1, 1),
('about', 'من نحن', 'تعرف على شركتنا', 'محتوى قسم من نحن', 1, 2),
('services', 'خدماتنا', 'نقدم مجموعة متنوعة من الخدمات', 'محتوى قسم الخدمات', 1, 3),
('projects', 'مشاريعنا', 'شاهد أعمالنا المميزة', 'محتوى قسم المشاريع', 1, 4),
('contact', 'تواصل معنا', 'نحن هنا لمساعدتك', 'محتوى قسم التواصل', 1, 5);

-- إدراج بيانات تجريبية للحقائق
INSERT INTO `facts` (`title`, `number`, `icon`, `description`, `is_active`, `sort_order`) VALUES
('سنوات الخبرة', 10, 'fas fa-calendar-alt', 'سنوات من الخبرة في المجال', 1, 1),
('المشاريع المكتملة', 150, 'fas fa-project-diagram', 'مشروع مكتمل بنجاح', 1, 2),
('العملاء السعداء', 200, 'fas fa-smile', 'عميل راضٍ عن خدماتنا', 1, 3),
('الجوائز المحققة', 25, 'fas fa-trophy', 'جائزة وتقدير', 1, 4);

-- فهارس إضافية لتحسين الأداء
CREATE INDEX idx_services_slug_active ON services(slug, is_active);
CREATE INDEX idx_projects_slug_active ON projects(slug, is_active);
CREATE INDEX idx_messages_created_read ON messages(created_at, is_read);
CREATE INDEX idx_activity_log_created ON activity_log(created_at);
CREATE INDEX idx_site_statistics_date ON site_statistics(visit_date);

-- إعدادات قاعدة البيانات
SET FOREIGN_KEY_CHECKS = 1;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 1;
