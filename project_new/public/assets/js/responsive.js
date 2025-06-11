/* 
 * ملف JavaScript للتأكد من التفاعل والاستجابة في جميع الشاشات
 * يحتوي على وظائف JavaScript للتصميم المتجاوب والتفاعل
 */

// تهيئة التطبيق عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeResponsiveFeatures();
    initializeLazyLoading();
    initializeImageGallery();
    initializeMobileMenu();
    initializeFormValidation();
    initializeTooltips();
    initializeScrollEffects();
});

/**
 * تهيئة الميزات المتجاوبة
 */
function initializeResponsiveFeatures() {
    // تحديث التخطيط عند تغيير حجم النافذة
    window.addEventListener('resize', function() {
        updateLayoutForScreenSize();
        updateGalleryLayout();
        updateTableResponsiveness();
    });
    
    // تحديث التخطيط الأولي
    updateLayoutForScreenSize();
}

/**
 * تحديث التخطيط حسب حجم الشاشة
 */
function updateLayoutForScreenSize() {
    const screenWidth = window.innerWidth;
    
    // تحديث قائمة التنقل
    updateNavigationForScreenSize(screenWidth);
    
    // تحديث البطاقات
    updateCardsForScreenSize(screenWidth);
    
    // تحديث الأزرار
    updateButtonsForScreenSize(screenWidth);
}

/**
 * تحديث قائمة التنقل حسب حجم الشاشة
 */
function updateNavigationForScreenSize(screenWidth) {
    const navbar = document.querySelector('.navbar');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (screenWidth < 992) {
        // شاشة صغيرة - إظهار زر القائمة
        if (navbarToggler) {
            navbarToggler.style.display = 'block';
        }
        
        if (navbarCollapse) {
            navbarCollapse.classList.add('collapse');
        }
    } else {
        // شاشة كبيرة - إخفاء زر القائمة
        if (navbarToggler) {
            navbarToggler.style.display = 'none';
        }
        
        if (navbarCollapse) {
            navbarCollapse.classList.remove('collapse');
            navbarCollapse.style.display = 'block';
        }
    }
}

/**
 * تحديث البطاقات حسب حجم الشاشة
 */
function updateCardsForScreenSize(screenWidth) {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        if (screenWidth < 576) {
            // شاشة صغيرة جداً
            card.classList.add('mb-3');
            card.classList.remove('mb-4');
        } else {
            // شاشة متوسطة أو كبيرة
            card.classList.add('mb-4');
            card.classList.remove('mb-3');
        }
    });
}

/**
 * تحديث الأزرار حسب حجم الشاشة
 */
function updateButtonsForScreenSize(screenWidth) {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        if (screenWidth < 576) {
            // شاشة صغيرة - أزرار أصغر
            button.classList.add('btn-sm');
            button.classList.remove('btn-lg');
        } else if (screenWidth > 1200) {
            // شاشة كبيرة - أزرار أكبر
            button.classList.add('btn-lg');
            button.classList.remove('btn-sm');
        } else {
            // شاشة متوسطة - حجم عادي
            button.classList.remove('btn-sm', 'btn-lg');
        }
    });
}

/**
 * تهيئة التحميل الكسول للصور
 */
function initializeLazyLoading() {
    // التحقق من دعم Intersection Observer
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('.lazyload');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazyload');
                    img.classList.add('lazyloaded');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // تحميل جميع الصور فوراً إذا لم يكن هناك دعم
        const lazyImages = document.querySelectorAll('.lazyload');
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazyload');
            img.classList.add('lazyloaded');
        });
    }
}

/**
 * تهيئة معرض الصور
 */
function initializeImageGallery() {
    // تهيئة Fancybox للمعارض
    if (typeof Fancybox !== 'undefined') {
        Fancybox.bind('[data-fancybox]', {
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: [
                        "zoomIn",
                        "zoomOut",
                        "toggle1to1",
                        "rotateCCW",
                        "rotateCW",
                        "flipX",
                        "flipY",
                    ],
                    right: ["slideshow", "thumbs", "close"],
                },
            },
            Thumbs: {
                autoStart: false,
            },
        });
    }
    
    // تحديث تخطيط المعرض
    updateGalleryLayout();
}

/**
 * تحديث تخطيط المعرض
 */
function updateGalleryLayout() {
    const galleries = document.querySelectorAll('.gallery');
    const screenWidth = window.innerWidth;
    
    galleries.forEach(gallery => {
        let columns;
        
        if (screenWidth < 576) {
            columns = 2; // عمودين في الشاشات الصغيرة
        } else if (screenWidth < 768) {
            columns = 3; // ثلاثة أعمدة في الشاشات المتوسطة
        } else if (screenWidth < 992) {
            columns = 4; // أربعة أعمدة في الشاشات الكبيرة
        } else {
            columns = 5; // خمسة أعمدة في الشاشات الكبيرة جداً
        }
        
        gallery.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
    });
}

/**
 * تهيئة قائمة الهاتف المحمول
 */
function initializeMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
        
        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', function(event) {
            if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

/**
 * تهيئة التحقق من صحة النماذج
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!validateForm(form)) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
        
        // التحقق في الوقت الفعلي
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
        });
    });
}

/**
 * التحقق من صحة النموذج
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * التحقق من صحة حقل واحد
 */
function validateField(field) {
    let isValid = true;
    const value = field.value.trim();
    
    // التحقق من الحقول المطلوبة
    if (field.hasAttribute('required') && value === '') {
        showFieldError(field, 'هذا الحقل مطلوب');
        isValid = false;
    }
    
    // التحقق من البريد الإلكتروني
    if (field.type === 'email' && value !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'يرجى إدخال بريد إلكتروني صحيح');
            isValid = false;
        }
    }
    
    // التحقق من رقم الهاتف
    if (field.type === 'tel' && value !== '') {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            showFieldError(field, 'يرجى إدخال رقم هاتف صحيح');
            isValid = false;
        }
    }
    
    if (isValid) {
        hideFieldError(field);
    }
    
    return isValid;
}

/**
 * إظهار خطأ في الحقل
 */
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    let errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentNode.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
}

/**
 * إخفاء خطأ الحقل
 */
function hideFieldError(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * تهيئة التلميحات
 */
function initializeTooltips() {
    // تهيئة Bootstrap tooltips إذا كانت متوفرة
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * تهيئة تأثيرات التمرير
 */
function initializeScrollEffects() {
    // تأثير الظهور عند التمرير
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // مراقبة العناصر التي تحتاج إلى تأثيرات
    const animatedElements = document.querySelectorAll('.card, .hero-section, .details-section');
    animatedElements.forEach(el => observer.observe(el));
    
    // زر العودة إلى الأعلى
    createBackToTopButton();
}

/**
 * إنشاء زر العودة إلى الأعلى
 */
function createBackToTopButton() {
    const backToTopButton = document.createElement('button');
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopButton.className = 'btn btn-primary back-to-top';
    backToTopButton.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: none;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(backToTopButton);
    
    // إظهار/إخفاء الزر حسب موضع التمرير
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    // العودة إلى الأعلى عند النقر
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * تحديث استجابة الجداول
 */
function updateTableResponsiveness() {
    const tables = document.querySelectorAll('table');
    const screenWidth = window.innerWidth;
    
    tables.forEach(table => {
        if (screenWidth < 768) {
            // تحويل الجدول إلى تخطيط عمودي في الشاشات الصغيرة
            if (!table.classList.contains('table-responsive-stack')) {
                makeTableResponsive(table);
            }
        } else {
            // إعادة الجدول إلى التخطيط العادي
            if (table.classList.contains('table-responsive-stack')) {
                restoreTableLayout(table);
            }
        }
    });
}

/**
 * جعل الجدول متجاوباً
 */
function makeTableResponsive(table) {
    table.classList.add('table-responsive-stack');
    
    const headers = table.querySelectorAll('th');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            if (headers[index]) {
                cell.setAttribute('data-label', headers[index].textContent);
            }
        });
    });
}

/**
 * استعادة تخطيط الجدول العادي
 */
function restoreTableLayout(table) {
    table.classList.remove('table-responsive-stack');
    
    const cells = table.querySelectorAll('td[data-label]');
    cells.forEach(cell => {
        cell.removeAttribute('data-label');
    });
}

/**
 * تهيئة البحث المباشر
 */
function initializeLiveSearch() {
    const searchInput = document.querySelector('#search-input');
    const searchResults = document.querySelector('#search-results');
    
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performLiveSearch(query, searchResults);
                }, 300);
            } else {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
            }
        });
        
        // إخفاء النتائج عند النقر خارجها
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}

/**
 * تنفيذ البحث المباشر
 */
function performLiveSearch(query, resultsContainer) {
    // هنا يمكن إضافة استدعاء AJAX للبحث
    // في الوقت الحالي، سنعرض رسالة تحميل
    resultsContainer.innerHTML = '<div class="p-3">جاري البحث...</div>';
    resultsContainer.style.display = 'block';
    
    // محاكاة استدعاء AJAX
    setTimeout(() => {
        resultsContainer.innerHTML = `
            <div class="p-3">
                <div class="search-result-item mb-2">
                    <a href="#" class="text-decoration-none">نتيجة البحث 1</a>
                </div>
                <div class="search-result-item mb-2">
                    <a href="#" class="text-decoration-none">نتيجة البحث 2</a>
                </div>
                <div class="text-center">
                    <a href="search.php?q=${encodeURIComponent(query)}" class="btn btn-sm btn-primary">عرض جميع النتائج</a>
                </div>
            </div>
        `;
    }, 500);
}

// إضافة أنماط CSS للتأثيرات
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .table-responsive-stack {
        border: 0;
    }
    
    @media (max-width: 767px) {
        .table-responsive-stack thead {
            display: none;
        }
        
        .table-responsive-stack tr {
            display: block;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        
        .table-responsive-stack td {
            display: block;
            text-align: right;
            border: none;
            padding: 10px;
            position: relative;
        }
        
        .table-responsive-stack td:before {
            content: attr(data-label) ": ";
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
    }
    
    .back-to-top {
        transition: all 0.3s ease;
    }
    
    .back-to-top:hover {
        transform: translateY(-2px);
    }
`;

document.head.appendChild(style);
