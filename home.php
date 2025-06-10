<?php
// This file will act as the main entry point for the public-facing website.
// PHP placeholders are for initial render / SEO, JS will fill in dynamic data.

$site_name_placeholder = "حداد جده";
$site_tagline_placeholder = "خدمات احترافية في الأعمال المعدنية والكلادنج";
$favicon_placeholder = "assets/images/favicon.ico"; // Updated by JS from settings
$base_asset_path = ''; // Assuming index.php is at the project root

// Default values (will be replaced by JS from API)
$default_logo_url = 'https://r2.flowith.net/files/o/1748059983588-Professional_Innovative_Logo_Design_for_Metalworks_Company_index_0@1024x1024.png';
$default_contact_phone = '+966123456789';
$default_contact_email = '[email protected]'; // Cloudflare protected

// Helper function for Cloudflare email encoding (if needed before main.js loads, though main.js has its own)
if (!function_exists('cf_encode_email')) {
    function cf_encode_email($email_address) {
        $output = '';
        for ($i = 0; $i < strlen($email_address); $i++) {
            $output .= '%' . dechex(ord($email_address[$i]));
        }
        return $output;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="meta-title"><?php echo htmlspecialchars($site_name_placeholder); ?> - <?php echo htmlspecialchars($site_tagline_placeholder); ?></title>
    <meta name="description" id="meta-description" content="نقدم خدمات الحدادة المتخصصة والأعمال المعدنية والكلادنج في جده. جودة عالية، أسعار تنافسية، وخبرة تمتد لسنوات.">
    <link rel="icon" href="<?php echo $base_asset_path; ?><?php echo htmlspecialchars($favicon_placeholder); ?>" id="favicon-link">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_asset_path; ?>assets/css/styles.css"> 
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        primary: '#fb2056',
                        'primary-dark': '#da1c4b',
                        'dark-gray': '#191919',
                        'text-gray': '#404040',
                        'light-bg': '#f8fafc',
                        'medium-gray': '#6b7280',
                    },
                    boxShadow: {
                        'subtle': '0 2px 8px rgba(0,0,0,0.06)',
                        'interactive': '0 4px 12px rgba(0,0,0,0.1)',
                    }
                }
            }
        }
    </script>
    <!-- Minimal essential styles if any; most styles are in assets/css/styles.css -->
    <style>
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Decorative elements */
        .hero-decoration {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, #fb2056, #ff6b93);
            filter: blur(60px);
            opacity: 0.15;
            z-index: 1;
        }
        
        .hero-decoration-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -100px;
        }
        
        .hero-decoration-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: -50px;
            background: linear-gradient(45deg, #3b82f6, #60a5fa);
        }
    </style>
</head>
<body class="font-cairo bg-light-bg text-text-gray text-base leading-relaxed">
    <!-- Navigation Header -->
    <header class="fixed top-0 w-full bg-white/90 backdrop-blur-md shadow-subtle z-[100] smooth-transition" id="header">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-3">
                <!-- Logo -->
                <a href="#hero" class="flex items-center logo-container" aria-label="الصفحة الرئيسية">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-dark rounded-lg flex items-center justify-center ml-3 overflow-hidden shadow-md">
                         <img src="<?php echo htmlspecialchars($default_logo_url); ?>" alt="شعار <?php echo htmlspecialchars($site_name_placeholder); ?>" class="w-full h-full object-cover" id="site-logo-header">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-dark-gray" id="site-title-header"><?php echo htmlspecialchars($site_name_placeholder); ?></h1>
                        <p class="text-xs text-medium-gray" id="site-tagline-header"><?php echo htmlspecialchars($site_tagline_placeholder); ?></p>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex space-x-6 space-x-reverse items-center" id="desktop-nav-links">
                    <a href="#hero" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition active">الرئيسية</a>
                    <a href="#services" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition">خدماتنا</a>
                    <a href="#projects" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition">أعمالنا</a>
                    <a href="#about" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition">من نحن</a>
                    <a href="#testimonials" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition">آراء العملاء</a>
                    <a href="#contact" class="nav-link text-dark-gray hover:text-primary font-semibold smooth-transition">اتصل بنا</a>
                </nav>

                <!-- Mobile Menu Button & Admin Link -->
                <div class="flex items-center">
                    <a href="tel:<?php echo htmlspecialchars($default_contact_phone); ?>" id="header-cta-button" class="hidden sm:inline-block btn-primary ml-4">
                        اطلب عرض سعر
                    </a>
                    <a href="<?php echo $base_asset_path; ?>admin/" class="text-medium-gray hover:text-primary smooth-transition ml-4 lg:ml-0" title="لوحة التحكم">
                        <i data-feather="settings" class="w-5 h-5"></i>
                    </a>
                    <button class="lg:hidden text-dark-gray p-2" id="mobile-menu-btn" aria-label="فتح القائمة">
                        <i data-feather="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="lg:hidden hidden bg-white shadow-lg rounded-b-lg overflow-hidden" id="mobile-menu">
                <div class="py-3 px-2 border-t border-gray-200">
                    <nav class="space-y-1" id="mobile-nav-links">
                        <a href="#hero" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition active">الرئيسية</a>
                        <a href="#services" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition">خدماتنا</a>
                        <a href="#projects" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition">أعمالنا</a>
                        <a href="#about" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition">من نحن</a>
                        <a href="#testimonials" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition">آراء العملاء</a>
                        <a href="#contact" class="block text-dark-gray hover:text-primary hover:bg-gray-100 font-semibold px-3 py-2 rounded-md smooth-transition">اتصل بنا</a>
                        <a href="tel:<?php echo htmlspecialchars($default_contact_phone); ?>" id="mobile-cta-button" class="block bg-primary text-white mt-2 px-4 py-3 rounded-lg font-semibold text-center smooth-transition hover:bg-primary-dark">اطلب عرض سعر</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main id="homepage-content" class="pt-20 md:pt-24">
        <!-- Sections will be dynamically inserted here by main.js -->
        <div class="min-h-screen flex items-center justify-center">
            <div class="loading-spinner w-12 h-12 border-4"></div> <!-- Initial Loading Spinner -->
        </div>
    </main>
    <div id="homepage-sections" class="hidden">
        <!-- Sections will be dynamically inserted here by main.js -->
    </div>


    <!-- Footer -->
    <footer class="bg-dark-gray text-gray-300 pt-16 pb-8 footer" id="footer">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                <!-- Company Info -->
                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center mb-5">
                         <div class="w-10 h-10 bg-gradient-to-br from-primary to-primary-dark rounded-lg flex items-center justify-center ml-3 overflow-hidden shadow-md">
                             <img src="<?php echo htmlspecialchars($default_logo_url); ?>" alt="شعار <?php echo htmlspecialchars($site_name_placeholder); ?> في الفوتر" class="w-full h-full object-cover" id="site-logo-footer">
                        </div>
                        <span class="text-xl font-bold text-white" id="site-title-footer"><?php echo htmlspecialchars($site_name_placeholder); ?></span>
                    </div>
                    <p class="text-sm mb-5 leading-relaxed" id="footer-description-placeholder">نقدم خدمات احترافية في جميع أنواع الأعمال المعدنية والكلادنج بجودة عالية ودقة في التنفيذ.</p>
                    <div class="flex space-x-3 space-x-reverse footer-social" id="footer-social-links">
                        <!-- Social links will be loaded dynamically by JS -->
                         <a href="#" class="footer-social-link"><i data-feather="facebook" class="w-5 h-5"></i></a>
                         <a href="#" class="footer-social-link"><i data-feather="twitter" class="w-5 h-5"></i></a>
                         <a href="#" class="footer-social-link"><i data-feather="instagram" class="w-5 h-5"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div data-aos="fade-up" data-aos-delay="200">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="space-y-3" id="footer-quick-links">
                        <li><a href="#hero" class="footer-link">الرئيسية</a></li>
                        <li><a href="#services" class="footer-link">خدماتنا</a></li>
                        <li><a href="#projects" class="footer-link">أعمالنا</a></li>
                        <li><a href="#about" class="footer-link">من نحن</a></li>
                        <li><a href="#testimonials" class="footer-link">آراء العملاء</a></li>
                        <li><a href="#contact" class="footer-link">اتصل بنا</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div data-aos="fade-up" data-aos-delay="300">
                    <h3 class="footer-title">أبرز خدماتنا</h3>
                    <ul class="space-y-3" id="footer-services-summary">
                        <!-- Services will be loaded dynamically by JS -->
                        <li><a href="#services" class="footer-link">خدمة 1</a></li>
                        <li><a href="#services" class="footer-link">خدمة 2</a></li>
                        <li><a href="#services" class="footer-link">خدمة 3</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div data-aos="fade-up" data-aos-delay="400">
                    <h3 class="footer-title">معلومات التواصل</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center">
                            <i data-feather="phone" class="w-4 h-4 ml-3 text-primary"></i>
                            <a href="tel:<?php echo htmlspecialchars($default_contact_phone); ?>" id="footer-contact-phone-link" class="footer-link"><span id="footer-contact-phone"><?php echo htmlspecialchars($default_contact_phone); ?></span></a>
                        </li>
                        <li class="flex items-center">
                            <i data-feather="mail" class="w-4 h-4 ml-3 text-primary"></i>
                            <a href="mailto:<?php echo htmlspecialchars($default_contact_email); ?>" id="footer-contact-email-link" class="footer-link"><span id="footer-contact-email"><span class="__cf_email__" data-cfemail="<?php echo cf_encode_email($default_contact_email); ?>">[email&#160;protected]</span></span></a>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="map-pin" class="w-4 h-4 ml-3 text-primary mt-1"></i>
                            <span id="footer-contact-address">جده، المملكة العربية السعودية</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="footer-bottom">
                <p class="text-sm text-gray-400" id="footer-copyright-text">&copy; <span id="current-year"><?php echo date('Y'); ?></span> <span id="copyright-site-name-footer"><?php echo htmlspecialchars($site_name_placeholder); ?></span>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" title="العودة للأعلى" class="fixed bottom-6 right-6 bg-primary text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center opacity-0 invisible smooth-transition hover:bg-primary-dark transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 z-50">
        <i data-feather="arrow-up" class="w-6 h-6"></i>
    </button>

    <!-- Modal Structure -->
    <div id="details-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"></h2>
                <button id="modal-close-btn" class="modal-close" aria-label="إغلاق">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="modal-body" class="prose max-w-none modal-body">
                <!-- Content will be injected here by JS -->
            </div>
            <div id="modal-images" class="mt-4 image-gallery">
                <!-- Images will be injected here by JS -->
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="<?php echo $base_asset_path; ?>assets/js/main.js"></script>
    <script>
        // Initialize Feather Icons
        feather.replace();
        
        // Initialize AOS animations
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            
            initializeSite(); // This function is in main.js
        });
    </script>
</body>
</html>
