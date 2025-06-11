-- --------------------------------------------------------
-- مخطط قاعدة بيانات لنظام موقع حداد جده
-- إصدار: 1.0
-- تاريخ: 2023-10-27
-- خادم: MySQL
-- مجموعة حروف: utf8mb4_unicode_ci
-- --------------------------------------------------------

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- --------------------------------------------------------
-- حذف الجداول إذا كانت موجودة (لإعادة الإنشاء أثناء التطوير أو التثبيت)
-- الترتيب مهم لحل مشاكل المفاتيح الخارجية

DROP TABLE IF EXISTS `facts`;
DROP TABLE IF EXISTS `project_images`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `homepage_sections`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- جداول قاعدة البيانات

-- جدول `users` لتخزين بيانات مدراء لوحة التحكم
-- لتسجيل الدخول الآمن
CREATE TABLE `users` (
  `user_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE, -- اسم المستخدم فريد
  `password_hash` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- هاش كلمة المرور (مهم جدا لتأمين الوصول)
  `role` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin', -- دور المستخدم (مثلا: 'admin')
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول مستخدمي لوحة التحكم';

-- --------------------------------------------------------

-- جدول `settings` لتخزين الإعدادات العامة للموقع
-- يتم تخزين جميع الإعدادات في صف واحد لتسهيل الوصول والتحديث
CREATE TABLE `settings` (
  `setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, -- مفتاح رئيسي وهمي (صف واحد فقط عادةً)
  `site_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- اسم الموقع
  `site_tagline` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- شعار الموقع / وصف قصير في الهيدر
  `site_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- وصف الموقع للـ SEO والفوتر (أكثر تفصيلاً)
  `contact_phone` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- رقم الهاتف
  `contact_email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- البريد الإلكتروني
  `contact_address` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- العنوان
  `whatsapp_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- رابط واتساب مباشر
  `instagram_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- رابط انستغرام
  `twitter_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- رابط تويتر (X)
  `facebook_link` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- رابط فيسبوك
  `footer_text` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- نص الفوتر لحقوق النشر أو معلومات إضافية
  `map_location_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- اسم الموقع على الخريطة
  `map_lat` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- خط العرض للخريطة
  `map_lng` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- خط الطول للخريطة
  `map_api_key` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مفتاح API للخريطة (إذا كانت تستخدم API)
  `site_logo_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار الشعار في مجلد الرفع
  `site_favicon_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار الأيقونة في مجلد الرفع
  `enabled_frontend_sections` JSON DEFAULT NULL COMMENT 'JSON object for toggling main frontend sections visibility', -- التحكم بظهور الأقسام الرئيسية (JSON)
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`)
  -- CONSTRAINT `check_single_row` CHECK (`setting_id` = 1) -- يمكن فرض صف واحد على مستوى التطبيق أسهل
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الإعدادات العامة للموقع';

-- --------------------------------------------------------

-- جدول `homepage_sections` لتخزين أقسام الصفحة الرئيسية الديناميكية
-- يسمح ببناء الصفحة الرئيسية من أقسام متنوعة وقابلة للترتيب
CREATE TABLE `homepage_sections` (
  `section_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_type` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL, -- نوع القسم (مثل 'hero', 'services_overview', 'projects_showcase', 'testimonials_slider', 'facts_counter', 'map_embed', 'contact_info', 'custom_html')
  `section_order` INT(11) UNSIGNED NOT NULL, -- ترتيب ظهور القسم في الصفحة (قيمة فريدة عادةً)
  `is_visible` BOOLEAN NOT NULL DEFAULT TRUE, -- هل القسم مرئي في الواجهة الأمامية؟
  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- عنوان القسم (يظهر أعلى القسم)
  `subtitle` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- عنوان فرعي للقسم
  `background_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار صورة خلفية للقسم (يُخزن مسار نسبي لمجلد uploads)
  `content` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- محتوى نصي/HTML غني (لأقسام مثل custom_html أو الوصف الطويل)
  `data_attributes` JSON DEFAULT NULL, -- بيانات إضافية خاصة بنوع القسم (مثلا: IDs للخدمات/المشاريع المراد عرضها، إحداثيات الخريطة، نص CTA، ...). استخدم JSON لتخزين بيانات مرنة ومنظمة.
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_id`),
  UNIQUE INDEX `idx_section_order_unique` (`section_order`), -- يجب أن يكون الترتيب فريداً
  INDEX `idx_section_type` (`section_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول أقسام الصفحة الرئيسية الديناميكية';

-- --------------------------------------------------------

-- جدول `services` لتخزين معلومات الخدمات المقدمة
CREATE TABLE `services` (
  `service_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- عنوان الخدمة
  `slug` VARCHAR(255) COLLATE utf8mb4_unicode_ci UNIQUE, -- معرف فريد للرابط (مثال: gates-fabrication)
  `short_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- وصف موجز يظهر في القوائم
  `full_description` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- وصف كامل (باستخدام WYSIWYG)
  `image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار الصورة الرئيسية للخدمة (نسبي لمجلد uploads)
  `icon` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- اسم الأيقونة (مثلا من Feather Icons أو فئة CSS)
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE, -- هل الخدمة مرئية في الواجهة الأمامية؟
  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- ترتيب ظهور الخدمة في القوائم
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  INDEX `idx_order` (`order`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الخدمات';

-- --------------------------------------------------------

-- جدول `projects` لتخزين معلومات المشاريع المكتملة
CREATE TABLE `projects` (
  `project_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- عنوان المشروع
  `slug` VARCHAR(255) COLLATE utf8mb4_unicode_ci UNIQUE, -- معرف فريد للرابط (مثال: airport-hangar-makkah)
  `short_description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- وصف موجز
  `full_description` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- وصف كامل (باستخدام WYSIWYG)
  `main_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار الصورة الرئيسية للمشروع (نسبي لمجلد uploads)
  `category` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- فئة المشروع (مثلا: "هناجر", "مظلات", "درابزينات", "كلادنج")
  `completion_date` DATE DEFAULT NULL, -- تاريخ الانتهاء
  `client_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- اسم العميل (اختياري)
  `location` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- موقع المشروع (اختياري)
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE, -- هل المشروع مرئي في الواجهة الأمامية؟
  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- ترتيب ظهور المشروع في القوائم
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_order` (`order`),
   INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المشاريع';

-- --------------------------------------------------------

-- جدول `project_images` لتخزين الصور الإضافية لكل مشروع
-- علاقة واحد لمشروع -> متعدد لصور
CREATE TABLE `project_images` (
  `image_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) UNSIGNED NOT NULL, -- المفتاح الأجنبي للربط بجدول المشاريع
  `image_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- مسار الصورة (نسبي لمجلد uploads)
  `caption` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- تعليق على الصورة (اختياري)
  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- ترتيب الصورة داخل المشروع
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE, -- حذف الصور عند حذف المشروع المرتبط
  INDEX `idx_project_order` (`project_id`, `order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول صور المشاريع الإضافية';

-- --------------------------------------------------------

-- جدول `testimonials` لتخزين آراء العملاء
CREATE TABLE `testimonials` (
  `testimonial_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- اسم العميل
  `client_title_company` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- منصب العميل أو شركته
  `feedback` TEXT COLLATE utf8mb4_unicode_ci NOT NULL, -- نص رأي العميل
  `client_photo` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- مسار صورة العميل (اختياري، نسبي لمجلد uploads)
  `rating` INT(11) UNSIGNED DEFAULT NULL CHECK (`rating` IS NULL OR (`rating` >= 1 AND `rating` <= 5)), -- التقييم (مثلا من 1 إلى 5 نجوم)
  `is_approved` BOOLEAN NOT NULL DEFAULT FALSE, -- هل تم الموافقة على الرأي لعرضه في الواجهة الأمامية؟
  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- ترتيب ظهور الرأي
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonial_id`),
  INDEX `idx_order` (`order`),
  INDEX `idx_is_approved` (`is_approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول آراء العملاء';

-- --------------------------------------------------------

-- جدول `facts` لتخزين بيانات قسم الحقائق (عداد الأرقام)
-- يرتبط هذا الجدول بقسم واحد من نوع 'facts_counter' في جدول `homepage_sections`
CREATE TABLE `facts` (
  `fact_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `homepage_section_id` INT(11) UNSIGNED DEFAULT NULL, -- المفتاح الأجنبي للربط بقسم الحقائق في الصفحة الرئيسية
  `icon` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- اسم الأيقونة (اختياري، مثال: 'briefcase')
  `number` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- القيمة الرقمية أو النصية للحقائق (مثل "25+", "1000")
  `title` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- عنوان الحقيقة (مثلا: "مشاريع مكتملة", "عميل سعيد")
  `prefix` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- بادئة للرقم (مثل "$")
  `suffix` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- لاحقة للرقم (مثل "+")
  `order` INT(11) UNSIGNED NOT NULL DEFAULT 0, -- ترتيب ظهور الحقيقة داخل قسم الحقائق
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fact_id`),
   FOREIGN KEY (`homepage_section_id`) REFERENCES `homepage_sections`(`section_id`) ON DELETE SET NULL, -- يمكن حذف القسم بدون حذف الحقائق
   INDEX `idx_order` (`order`),
   INDEX `fk_homepage_section_id` (`homepage_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول بيانات قسم الحقائق';

-- --------------------------------------------------------
-- بيانات تجريبية (Demo Data)
-- --------------------------------------------------------

-- بيانات الإعدادات الأساسية
INSERT INTO `settings` (`setting_id`, `site_name`, `site_tagline`, `site_description`, `contact_phone`, `contact_email`, `contact_address`, `whatsapp_link`, `instagram_link`, `twitter_link`, `facebook_link`, `footer_text`, `map_location_name`, `map_lat`, `map_lng`, `map_api_key`, `site_logo_path`, `site_favicon_path`, `enabled_frontend_sections`) VALUES
(1, 'أعمال جده المعدنية', 'خبرة وجودة في عالم الحدادة والكلادنج', 'مؤسسة رائدة في أعمال الحدادة والكلادنج في جده. نقدم حلولاً معدنية مبتكرة وعالية الجودة للمنازل والمشاريع التجارية والصناعية.', '0501234567', 'info@makkahmetal.com', 'جده، حي العزيزية، شارع المعارض', 'https://wa.me/+966501234567', 'https://www.instagram.com/makkahmetal', 'https://twitter.com/makkahmetal', 'https://www.facebook.com/makkahmetal', '© 2023 أعمال جده المعدنية. جميع الحقوق محفوظة.', 'مكتبنا في جده', '21.3891', '39.8579', NULL, 'site_assets/logo_placeholder.png', 'site_assets/favicon_placeholder.ico', '{"hero":true, "about":true, "services":true, "projects":true, "testimonials":true, "facts":true, "contact":true, "map":true}');

-- --------------------------------------------------------
-- بيانات أقسام الصفحة الرئيسية التجريبية

INSERT INTO `homepage_sections` (`section_type`, `section_order`, `is_visible`, `title`, `subtitle`, `background_image`, `content`, `data_attributes`) VALUES
('hero', 1, 1, 'أعمال جده المعدنية', 'شريكك في الإبداع والقوة الحديدية والكلادنج', 'sections/hero_bg_placeholder.jpg', 'نقدم أفضل حلول الحدادة المبتكرة وتصميم وتركيب الكلادنج الحديث بواجهات عصرية وجودة لا تُعلى عليها في جده والمناطق المجاورة.', '{"cta_button_text": "اكتشف خدماتنا", "cta_button_link": "#services", "hero_image_url": "sections/hero_main_placeholder.png"}'),
('about_summary', 2, 1, 'من نحن', 'قصة نجاح في سماء جده', NULL, '<p>نحن مؤسسة رائدة في مجال الأعمال المعدنية والكلادنج، تأسست على يد فريق من الخبراء والفنيين المهرة. نفخر بتاريخنا في تقديم حلول مبتكرة وعالية الجودة تلبي طموحات عملائنا وتفوق توقعاتهم.</p><p>نلتزم بأعلى معايير الجودة والسلامة في جميع مشاريعنا، بدءًا من التصميم الدقيق وصولًا إلى التنفيذ المتقن والتسليم في الوقت المحدد.</p>', NULL),
('services_overview', 3, 1, 'خدماتنا المميزة', 'نقدم مجموعة واسعة من الخدمات لتلبية جميع احتياجاتك', NULL, 'تشمل خدماتنا تصميم وتصنيع وتركيب جميع أنواع الأعمال المعدنية والكلادنج بمختلف الاستخدامات.', '{"limit": 6}'),
('facts_counter', 4, 1, 'أرقام تتحدث عن الإنجاز', 'خبرتنا تتجسد في أرقام', 'sections/facts_bg_placeholder.jpg', NULL, NULL),
('projects_showcase', 5, 1, 'مشاريعنا الأخيرة', 'شاهد أمثلة من أعمالنا المكتملة', NULL, 'استعرض معرض مشاريعنا المتنوعة في مجالات الحدادة والكلادنج التي نفذناها في جده والمناطق المجاورة.', '{"limit": 6}'),
('testimonials_slider', 6, 1, 'ماذا يقول عملاؤنا عنا؟', 'شهادات نعتز بها', NULL, 'نحرص دائماً على كسب ثقة عملائنا ورضاهم التام عن خدماتنا.', '{"limit": 10}'),
('contact_info', 7, 1, 'تواصل معنا', 'نحن هنا لخدمتك والإجابة على استفساراتك', NULL, '<p>يسعدنا تواصلكم معنا في أي وقت. فريقنا جاهز لتقديم الاستشارات وتوفير عروض الأسعار المناسبة لمشاريعكم.</p><p>يمكنك استخدام النموذج أدناه أو التواصل مباشرة عبر الهاتف والبريد الإلكتروني الموجود في التذييل.</p>', NULL),
('map_embed', 8, 1, 'موقعنا', 'تفضل بزيارتنا', NULL, NULL, '{"map_lat":"21.3891", "map_lng":"39.8579", "map_location_name":"مكتبنا في جده"}'),
('custom_html', 9, 0, 'قسم تجريبي مخصص', NULL, NULL, '<h2>هذا مثال لقسم محتوى HTML مخصص</h2><p>يمكنك استخدام هذا النوع من الأقسام لإضافة محتوى خاص غير مغطى بأنواع الأقسام القياسية.</p>', NULL);


-- --------------------------------------------------------
-- بيانات الخدمات التجريبية

INSERT INTO `services` (`title`, `slug`, `short_description`, `full_description`, `image`, `icon`, `is_active`, `order`) VALUES
('تصنيع الأبواب المعدنية', 'metal-doors', 'أبواب حديد قوية وآمنة بتصاميم عصرية وكلاسيكية تناسب جميع الأذواق.', '<p>نقدم خدمات تصميم وتصنيع وتركيب جميع أنواع الأبواب المعدنية، بما في ذلك:</p><ul><li>أبواب الفلل والقصور الفاخرة</li><li>أبواب المداخل الرئيسية والعمارات</li><li>أبواب المستودعات والمصانع</li><li>أبواب السحب والجر</li></ul><p>نستخدم أفضل أنواع الحديد ونطبق تقنيات حديثة في اللحام والتشطيب لضمان أقصى درجات المتانة والجمال.</p>', 'services/sample1.jpg', 'shield', 1, 10),
('أعمال الدرابزين والسور', 'railings-fences', 'تصميم وتنفيذ درابزين وسور حديد مشغول وفورجيه للسلالم والشرفات والحدائق.', '<p>متخصصون في صناعة وتوريد وتركيب الدرابزين والسور الحديدي بأشكال متعددة:</p><ul><li>درابزين السلالم الداخلية والخارجية</li><li>سور الحدائق والأحواش</li><li>بوابات الفلل والمنازل</li><li>حواجز الحماية للنوافذ والشرفات</li></ul><p>نوفر تصاميم متنوعة تتناسب مع الطراز المعماري لمنزلك، بدءاً من الكلاسيكي المزخرف وصولاً إلى المودرن البسيط.</p>', 'services/sample2.png', 'bar-chart-2', 1, 20),
('تركيب الكلادنج وواجهات المباني', 'cladding-installation', 'حلول كلادنج حديثة وفعالة لتحسين واجهات المباني التجارية والسكنية.', '<p>نقدم خدمات تركيب الكلادنج (ألواح الألمنيوم المركبة) للواجهات:</p><ul><li>واجهات المباني التجارية والشركات</li><li>واجهات الفلل والعمارات السكنية</li><li>تكسية الأعمدة والمظلات</li></ul><p>نضمن جودة المواد المستخدمة ومقاومتها للعوامل الجوية وسهولة صيانتها. تتوفر لدينا خيارات متعددة من الألوان والتشطيبات لتصميم واجهة مميزة وجذابة.</p>', 'services/sample3.jpg', 'layers', 1, 30),
('تصنيع الهناجر والمستودعات', 'hangar-fabrication', 'تصميم وتصنيع وتركيب هياكل الهناجر والمستودعات المعدنية الكبرى.', '<p>نقوم بتصميم وتصنيع وتركيب الهناجر والمستودعات بمختلف الأحجام والاستخدامات:</p><ul><li>هناجر صناعية وتجارية</li><li>مستودعات تخزين</li><li>ورش ومصانع</li></ul><p>نلتزم بالمعايير الهندسية لضمان قوة الهيكل ومقاومته للعوامل البيئية، مع إمكانية إضافة العزل ووسائل التهوية حسب الحاجة.</p>', 'services/sample4.jpg', 'box', 1, 40);

-- --------------------------------------------------------
-- بيانات المشاريع التجريبية

INSERT INTO `projects` (`title`, `slug`, `short_description`, `full_description`, `main_image`, `category`, `completion_date`, `client_name`, `location`, `is_active`, `order`) VALUES
('هنجر صناعي بمنطقة جده', 'industrial-hangar-makkah', 'تنفيذ هنجر صناعي متكامل بمواصفات عالية.', '<p>تفاصيل المشروع: تم تصميم وتصنيع وتركيب هيكل هنجر صناعي كبير بمساحة 1500 متر مربع في إحدى المناطق الصناعية بجده. استخدمنا هياكل معدنية قوية وتم تطبيق معايير السلامة بدقة.</p><p><strong>المميزات:</strong> تصميم مقاوم للرياح والزلازل، سرعة في التنفيذ، استخدام مواد عالية الجودة.</p>', 'projects/project_alpha.jpg', 'هناجر', '2023-09-15', 'مصنع الأفق الجديد', 'جده', 1, 10),
('تركيب واجهة كلادنج لمجمع تجاري بجدة', 'cladding-mall-jeddah', 'تركيب واجهات كلادنج حديثة وعازلة للحرارة.', '<p>تفاصيل المشروع: قمنا بتوريد وتركيب واجهات كلادنج لمجمع تجاري كبير في مدينة جدة. تم اختيار ألوان وتصاميم عصرية تضفي مظهراً جذاباً على المبنى وتساعد في العزل الحراري.</p><p><strong>المميزات:</strong> مظهر عصري وجذاب، عزل حراري ممتاز، سهولة الصيانة.</p>', 'projects/project_beta.jpg', 'كلادنج', '2023-10-01', 'مجموعة عقارات البحر الأحمر', 'جدة', 1, 20),
('بوابة وسور فيلا خاصة بالشرائع', 'villa-gate-sharaie', 'تصميم وتنفيذ بوابة وسور حديد مشغول لفيلا فاخرة.', '<p>تفاصيل المشروع: تم تصميم وتنفيذ بوابة رئيسية وسور خارجي من الحديد المشغول لفيلا خاصة في حي الشرائع بجده. تميز العمل بالدقة في التفاصيل والزخارف التي تعكس الطابع الفاخر للفيلا.</p><p><strong>المميزات:</strong> تصميم فني فريد، متانة عالية، تشطيب يدوي دقيق.</p>', 'projects/project_gamma.jpg', 'بوابات وسور', '2023-08-20', 'الشيخ أحمد الزهراني', 'جده - الشرائع', 1, 30);

-- --------------------------------------------------------
-- بيانات صور المشاريع الإضافية التجريبية

-- صور إضافية للمشروع الأول (هنجر صناعي) - project_id = 1 (يفترض أن يكون الأول)
INSERT INTO `project_images` (`project_id`, `image_path`, `caption`, `order`) VALUES
(1, 'projects/project_alpha_img1.jpg', 'الهيكل المعدني أثناء التركيب', 1),
(1, 'projects/project_alpha_img2.jpg', 'الواجهة بعد تركيب جزء من الكلادنج', 2),
(1, 'projects/project_alpha_img3.jpg', 'منظر داخلي للهنجر بعد الانتهاء', 3);

-- صور إضافية للمشروع الثاني (كلادنج مجمع تجاري) - project_id = 2
INSERT INTO `project_images` (`project_id`, `image_path`, `caption`, `order`) VALUES
(2, 'projects/project_beta_img1.jpg', 'جزء من الواجهة قيد الإنشاء', 1),
(2, 'projects/project_beta_img2.jpg', 'الواجهة النهائية للمجمع', 2);


-- --------------------------------------------------------
-- بيانات آراء العملاء التجريبية

INSERT INTO `testimonials` (`client_name`, `client_title_company`, `feedback`, `client_photo`, `rating`, `is_approved`, `order`) VALUES
('م. خالد الأحمدي', 'مقاول مشروع بجدة', 'تعامل احترافي وسرعة في الإنجاز. جودة الأعمال المعدنية كانت ممتازة وفريق العمل متعاون جداً. أنصح بالتعامل معهم.', 'testimonials/client1.jpg', 5, 1, 10),
('أ. سارة الفيصل', 'صاحبة فيلا خاصة بجده', 'تصميم السور والبوابة تجاوز توقعاتي. دقة في التفاصيل والتركيب كان نظيفاً ومرتباً. شكراً لكم على عملكم الرائع.', 'testimonials/client2.jpg', 5, 1, 20),
('شركة البناء الحديث', 'مدير المشاريع', 'نفذوا أعمال الكلادنج لمشروعنا التجاري بكفاءة عالية والتزموا بالجدول الزمني. الشغل نظيف والجودة ممتازة.', NULL, 4, 1, 30),
('أحمد الشهري', 'عميل فردي', 'قدمت طلب عرض سعر قبل أسبوع ولم يتم التواصل معي بعد. آمل أن يتم الرد قريباً.', NULL, 3, 0, 40); -- رأي بانتظار الموافقة (is_approved = 0)

-- --------------------------------------------------------
-- بيانات الحقائق والأرقام التجريبية

-- أولاً: الحصول على ID القسم من نوع 'facts_counter' الذي تم إدراجه سابقاً في homepage_sections
-- يمكن أيضاً ربطها مباشرة بقيمة ثابتة إذا كان القسم معروفاً دائماً أنه سيكون الأول بهذا النوع
SET @facts_section_id = (SELECT section_id FROM `homepage_sections` WHERE section_type = 'facts_counter' LIMIT 1);

INSERT INTO `facts` (`homepage_section_id`, `icon`, `number`, `title`, `prefix`, `suffix`, `order`) VALUES
(@facts_section_id, 'briefcase', '450', 'مشروع منجز', NULL, '+', 10),
(@facts_section_id, 'users', '120', 'عميل سعيد', NULL, '+', 20),
(@facts_section_id, 'award', '10', 'سنوات خبرة', NULL, '+', 30),
(@facts_section_id, 'hard-drive', '8000', 'متر مربع كلادنج', NULL, '+', 40);

-- --------------------------------------------------------
-- ملاحظات إضافية:
-- - هذا الملف لا يحتوي على أمر CREATE DATABASE لأنه يفترض أن يتم إنشاء قاعدة البيانات
--   باستخدام معالج التثبيت install.php أو يدوياً قبل تشغيل هذا السكربت.
-- - لا يتضمن بيانات مستخدمين (جدول users) باستثناء ما يتم إنشاؤه في install.php للمدير الأولي.
-- - مسارات الصور هي أمثلة ويجب أن يتم التعامل مع رفع الملفات وتخزينها في مجلد uploads
--   بواسطة الواجهة الخلفية (PHP) أثناء إدارة المحتوى.
-- --------------------------------------------------------
