/**
 * ملف JavaScript مخصص للوحة التحكم
 * 
 * يحتوي على وظائف وتحسينات لتعزيز تجربة المستخدم في لوحة التحكم
 */

// تنفيذ الكود عند اكتمال تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة محرر النصوص المنسقة
    initRichTextEditor();
    
    // تهيئة نظام رفع الصور المتعددة
    initMultiImageUpload();
    
    // تهيئة ترتيب العناصر بالسحب والإفلات
    initSortableItems();
    
    // تهيئة معاينة SEO
    initSeoPreview();
    
    // تهيئة تأكيدات الحذف
    initDeleteConfirmations();
    
    // تهيئة زر التمرير لأعلى
    initScrollToTop();
    
    // تهيئة التنبيهات التلقائية
    initAutoAlerts();
    
    // تهيئة القوائم المنسدلة المتقدمة
    initAdvancedSelects();
    
    // تهيئة الرسوم البيانية (إذا كانت موجودة)
    if (typeof Chart !== 'undefined') {
        initCharts();
    }
});

/**
 * تهيئة محرر النصوص المنسقة
 */
function initRichTextEditor() {
    // التحقق من وجود عناصر محرر النصوص
    const richTextElements = document.querySelectorAll('.rich-text-editor');
    
    if (richTextElements.length > 0 && typeof tinymce !== 'undefined') {
        richTextElements.forEach(function(element) {
            tinymce.init({
                selector: '#' + element.id,
                directionality: 'rtl',
                language: 'ar',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | ' +
                    'bold italic backcolor | alignright aligncenter alignleft alignjustify | ' +
                    'bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family: "Cairo", "Tajawal", sans-serif; font-size: 14px; }',
                setup: function(editor) {
                    // إضافة زر لإدراج صورة من المكتبة
                    editor.ui.registry.addButton('insertFromLibrary', {
                        text: 'إدراج صورة من المكتبة',
                        onAction: function() {
                            openMediaLibrary(function(imageUrl) {
                                editor.insertContent('<img src="' + imageUrl + '" alt="" />');
                            });
                        }
                    });
                }
            });
        });
    }
}

/**
 * تهيئة نظام رفع الصور المتعددة
 */
function initMultiImageUpload() {
    // التحقق من وجود عناصر رفع الصور
    const dropzoneElements = document.querySelectorAll('.dropzone');
    
    if (dropzoneElements.length > 0 && typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
        
        dropzoneElements.forEach(function(element) {
            const uploadUrl = element.dataset.url || 'ajax_handler.php';
            const maxFiles = parseInt(element.dataset.maxFiles || 10);
            const acceptedFiles = element.dataset.acceptedFiles || 'image/*';
            const paramName = element.dataset.paramName || 'file';
            const targetType = element.dataset.targetType || '';
            const targetId = element.dataset.targetId || '';
            
            const myDropzone = new Dropzone(element, {
                url: uploadUrl,
                paramName: paramName,
                maxFiles: maxFiles,
                acceptedFiles: acceptedFiles,
                addRemoveLinks: true,
                dictDefaultMessage: 'اسحب الصور وأفلتها هنا أو انقر للرفع',
                dictRemoveFile: 'حذف',
                dictCancelUpload: 'إلغاء',
                dictMaxFilesExceeded: 'لا يمكنك رفع المزيد من الملفات',
                init: function() {
                    // إضافة معلومات إضافية للطلب
                    this.on('sending', function(file, xhr, formData) {
                        formData.append('action', 'upload_image');
                        formData.append('target_type', targetType);
                        formData.append('target_id', targetId);
                    });
                    
                    // عند نجاح الرفع
                    this.on('success', function(file, response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // إضافة معرف الصورة للملف
                                file.imageId = data.image_id;
                                
                                // إضافة الصورة إلى معرض الصور
                                if (data.image_html) {
                                    const galleryContainer = document.querySelector('.gallery-container');
                                    if (galleryContainer) {
                                        galleryContainer.insertAdjacentHTML('beforeend', data.image_html);
                                    }
                                }
                                
                                // تحديث حقل الصور المخفي
                                updateImagesField();
                            } else {
                                showAlert('danger', data.message || 'حدث خطأ أثناء رفع الصورة');
                                this.removeFile(file);
                            }
                        } catch (e) {
                            showAlert('danger', 'حدث خطأ أثناء معالجة استجابة الخادم');
                            this.removeFile(file);
                        }
                    });
                    
                    // عند حذف الصورة
                    this.on('removedfile', function(file) {
                        if (file.imageId) {
                            // إرسال طلب لحذف الصورة من الخادم
                            fetch(uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=delete_image&image_id=' + file.imageId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // حذف الصورة من معرض الصور
                                    const imageElement = document.querySelector(`.gallery-item[data-image-id="${file.imageId}"]`);
                                    if (imageElement) {
                                        imageElement.remove();
                                    }
                                    
                                    // تحديث حقل الصور المخفي
                                    updateImagesField();
                                } else {
                                    showAlert('danger', data.message || 'حدث خطأ أثناء حذف الصورة');
                                }
                            })
                            .catch(error => {
                                showAlert('danger', 'حدث خطأ أثناء الاتصال بالخادم');
                            });
                        }
                    });
                    
                    // تحميل الصور الموجودة
                    const existingImages = element.dataset.existingImages;
                    if (existingImages) {
                        try {
                            const images = JSON.parse(existingImages);
                            images.forEach(image => {
                                // إنشاء ملف وهمي
                                const mockFile = {
                                    name: image.filename,
                                    size: image.size,
                                    accepted: true,
                                    imageId: image.id
                                };
                                
                                // إضافة الملف إلى Dropzone
                                this.emit('addedfile', mockFile);
                                this.emit('thumbnail', mockFile, image.url);
                                this.emit('complete', mockFile);
                                
                                // إضافة الملف إلى الملفات المقبولة
                                this.files.push(mockFile);
                            });
                        } catch (e) {
                            console.error('خطأ في تحليل الصور الموجودة:', e);
                        }
                    }
                }
            });
        });
    }
}

/**
 * تحديث حقل الصور المخفي
 */
function updateImagesField() {
    const imagesField = document.getElementById('images_ids');
    if (imagesField) {
        const galleryItems = document.querySelectorAll('.gallery-item');
        const imageIds = Array.from(galleryItems).map(item => item.dataset.imageId);
        imagesField.value = JSON.stringify(imageIds);
    }
}

/**
 * تهيئة ترتيب العناصر بالسحب والإفلات
 */
function initSortableItems() {
    // التحقق من وجود عناصر قابلة للترتيب
    const sortableContainers = document.querySelectorAll('.sortable-container');
    
    if (sortableContainers.length > 0 && typeof Sortable !== 'undefined') {
        sortableContainers.forEach(function(container) {
            Sortable.create(container, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.sortable-handle',
                onEnd: function(evt) {
                    // تحديث ترتيب العناصر في قاعدة البيانات
                    const items = container.querySelectorAll('.sortable-item');
                    const itemIds = Array.from(items).map(item => item.dataset.id);
                    
                    // الحصول على معلومات الترتيب
                    const targetType = container.dataset.targetType || '';
                    const updateUrl = container.dataset.updateUrl || 'ajax_handler.php';
                    
                    // إرسال طلب لتحديث الترتيب
                    fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=update_order&target_type=' + targetType + '&items=' + JSON.stringify(itemIds)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            showAlert('danger', data.message || 'حدث خطأ أثناء تحديث الترتيب');
                        }
                    })
                    .catch(error => {
                        showAlert('danger', 'حدث خطأ أثناء الاتصال بالخادم');
                    });
                }
            });
        });
    }
}

/**
 * تهيئة معاينة SEO
 */
function initSeoPreview() {
    // التحقق من وجود عناصر معاينة SEO
    const seoPreviewContainer = document.querySelector('.seo-preview');
    
    if (seoPreviewContainer) {
        const titleInput = document.getElementById('meta_title');
        const descriptionInput = document.getElementById('meta_description');
        const slugInput = document.getElementById('slug');
        
        const previewTitle = seoPreviewContainer.querySelector('.seo-preview-title');
        const previewUrl = seoPreviewContainer.querySelector('.seo-preview-url');
        const previewDescription = seoPreviewContainer.querySelector('.seo-preview-description');
        
        // تحديث المعاينة عند تغيير العنوان
        if (titleInput && previewTitle) {
            titleInput.addEventListener('input', function() {
                previewTitle.textContent = this.value || 'عنوان الصفحة';
            });
        }
        
        // تحديث المعاينة عند تغيير الوصف
        if (descriptionInput && previewDescription) {
            descriptionInput.addEventListener('input', function() {
                previewDescription.textContent = this.value || 'وصف الصفحة يظهر هنا. أضف وصفاً جذاباً ومختصراً للصفحة.';
            });
        }
        
        // تحديث المعاينة عند تغيير الرابط
        if (slugInput && previewUrl) {
            slugInput.addEventListener('input', function() {
                const baseUrl = previewUrl.dataset.baseUrl || 'https://example.com/';
                previewUrl.textContent = baseUrl + (this.value || 'page-slug');
            });
        }
    }
}

/**
 * تهيئة تأكيدات الحذف
 */
function initDeleteConfirmations() {
    // التحقق من وجود أزرار الحذف
    const deleteButtons = document.querySelectorAll('.delete-btn, [data-action="delete"]');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const confirmMessage = this.dataset.confirmMessage || 'هل أنت متأكد من حذف هذا العنصر؟';
            const targetId = this.dataset.id;
            const targetType = this.dataset.type;
            const deleteUrl = this.dataset.url || this.getAttribute('href');
            
            if (confirm(confirmMessage)) {
                if (deleteUrl.startsWith('javascript:')) {
                    // تنفيذ الكود JavaScript
                    eval(deleteUrl.replace('javascript:', ''));
                } else {
                    // إرسال طلب حذف
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', targetId);
                    formData.append('type', targetType);
                    
                    fetch(deleteUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // إزالة العنصر من الصفحة
                            const targetElement = document.querySelector(`[data-id="${targetId}"]`);
                            if (targetElement) {
                                targetElement.remove();
                            }
                            
                            showAlert('success', data.message || 'تم الحذف بنجاح');
                        } else {
                            showAlert('danger', data.message || 'حدث خطأ أثناء الحذف');
                        }
                    })
                    .catch(error => {
                        showAlert('danger', 'حدث خطأ أثناء الاتصال بالخادم');
                    });
                }
            }
        });
    });
}

/**
 * تهيئة زر التمرير لأعلى
 */
function initScrollToTop() {
    // إنشاء زر التمرير لأعلى
    const scrollButton = document.createElement('div');
    scrollButton.className = 'scroll-to-top';
    scrollButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    document.body.appendChild(scrollButton);
    
    // إظهار/إخفاء الزر عند التمرير
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollButton.classList.add('show');
        } else {
            scrollButton.classList.remove('show');
        }
    });
    
    // التمرير لأعلى عند النقر على الزر
    scrollButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * تهيئة التنبيهات التلقائية
 */
function initAutoAlerts() {
    // إخفاء التنبيهات تلقائياً بعد فترة
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            } else {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    });
}

/**
 * تهيئة القوائم المنسدلة المتقدمة
 */
function initAdvancedSelects() {
    // التحقق من وجود عناصر القوائم المنسدلة المتقدمة
    const advancedSelects = document.querySelectorAll('.advanced-select');
    
    if (advancedSelects.length > 0 && typeof Choices !== 'undefined') {
        advancedSelects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: true,
                itemSelectText: 'اضغط للاختيار',
                noResultsText: 'لا توجد نتائج',
                noChoicesText: 'لا توجد خيارات',
                searchPlaceholderValue: 'اكتب للبحث',
                placeholder: true,
                placeholderValue: 'اختر...',
                classNames: {
                    containerOuter: 'choices choices-rtl'
                }
            });
        });
    }
}

/**
 * تهيئة الرسوم البيانية
 */
function initCharts() {
    // الرسم البياني للإحصائيات
    const statsChart = document.getElementById('statsChart');
    if (statsChart) {
        const ctx = statsChart.getContext('2d');
        
        // الحصول على البيانات من السمات
        const labels = JSON.parse(statsChart.dataset.labels || '[]');
        const values = JSON.parse(statsChart.dataset.values || '[]');
        const colors = JSON.parse(statsChart.dataset.colors || '[]');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'الإحصائيات',
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // الرسم البياني للزيارات
    const visitsChart = document.getElementById('visitsChart');
    if (visitsChart) {
        const ctx = visitsChart.getContext('2d');
        
        // الحصول على البيانات من السمات
        const labels = JSON.parse(visitsChart.dataset.labels || '[]');
        const values = JSON.parse(visitsChart.dataset.values || '[]');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'الزيارات',
                    data: values,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * فتح مكتبة الوسائط
 * @param {Function} callback دالة الاستدعاء عند اختيار صورة
 */
function openMediaLibrary(callback) {
    // إنشاء مودال مكتبة الوسائط
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'mediaLibraryModal';
    modal.tabIndex = '-1';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">مكتبة الوسائط</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // إظهار المودال
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // تحميل الصور من الخادم
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_media_library'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث محتوى المودال
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = data.html || `
                <div class="text-center py-5">
                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                    <p class="text-muted">لا توجد صور في المكتبة</p>
                </div>
            `;
            
            // إضافة معالج النقر على الصور
            const images = modalBody.querySelectorAll('.media-item');
            images.forEach(function(image) {
                image.addEventListener('click', function() {
                    const imageUrl = this.dataset.url;
                    callback(imageUrl);
                    modalInstance.hide();
                });
            });
        } else {
            // عرض رسالة خطأ
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    ${data.message || 'حدث خطأ أثناء تحميل مكتبة الوسائط'}
                </div>
            `;
        }
    })
    .catch(error => {
        // عرض رسالة خطأ
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                حدث خطأ أثناء الاتصال بالخادم
            </div>
        `;
    });
    
    // إزالة المودال عند إغلاقه
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

/**
 * عرض تنبيه
 * @param {string} type نوع التنبيه (success, danger, warning, info)
 * @param {string} message نص التنبيه
 * @param {boolean} isPermanent هل التنبيه دائم
 */
function showAlert(type, message, isPermanent = false) {
    // إنشاء عنصر التنبيه
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show ${isPermanent ? 'alert-permanent' : ''}`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // إضافة التنبيه إلى الصفحة
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.appendChild(alert);
    } else {
        // إنشاء حاوية التنبيهات إذا لم تكن موجودة
        const container = document.createElement('div');
        container.className = 'alert-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        container.appendChild(alert);
        document.body.appendChild(container);
    }
    
    // إخفاء التنبيه تلقائياً بعد فترة
    if (!isPermanent) {
        setTimeout(function() {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            } else {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    }
}

/**
 * توليد slug من نص
 * @param {string} text النص المراد تحويله إلى slug
 * @returns {string} النص بعد التحويل إلى slug
 */
function generateSlug(text) {
    return text
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-');
}

/**
 * تهيئة توليد الـ slug التلقائي
 */
function initAutoSlug() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        // توليد الـ slug عند تغيير العنوان
        titleInput.addEventListener('input', function() {
            // التحقق من أن حقل الـ slug فارغ أو لم يتم تعديله يدوياً
            if (slugInput.dataset.autoGenerate !== 'false') {
                slugInput.value = generateSlug(this.value);
            }
        });
        
        // تعطيل التوليد التلقائي عند تعديل الـ slug يدوياً
        slugInput.addEventListener('input', function() {
            this.dataset.autoGenerate = 'false';
        });
    }
}

/**
 * تهيئة معاينة الصور قبل الرفع
 */
function initImagePreview() {
    const imageInputs = document.querySelectorAll('.image-input');
    
    imageInputs.forEach(function(input) {
        const previewContainer = document.getElementById(input.dataset.preview);
        
        if (previewContainer) {
            input.addEventListener('change', function() {
                // مسح المعاينة السابقة
                previewContainer.innerHTML = '';
                
                // التحقق من وجود ملفات
                if (this.files && this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        
                        // التحقق من أن الملف صورة
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'img-thumbnail';
                                img.style.maxHeight = '200px';
                                img.style.marginRight = '5px';
                                previewContainer.appendChild(img);
                            };
                            
                            reader.readAsDataURL(file);
                        }
                    }
                }
            });
        }
    });
}

/**
 * تهيئة تبديل حالة العناصر
 */
function initToggleStatus() {
    const toggleButtons = document.querySelectorAll('.toggle-status');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.dataset.id;
            const targetType = this.dataset.type;
            const currentStatus = this.dataset.status === '1';
            const updateUrl = this.dataset.url || 'ajax_handler.php';
            
            // تحديث حالة الزر مؤقتاً
            this.dataset.status = currentStatus ? '0' : '1';
            this.innerHTML = currentStatus ? '<i class="fas fa-times text-danger"></i>' : '<i class="fas fa-check text-success"></i>';
            
            // إرسال طلب لتحديث الحالة
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_status&id=' + targetId + '&type=' + targetType + '&status=' + (currentStatus ? '0' : '1')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث حالة الزر
                    this.dataset.status = data.status;
                    this.innerHTML = data.status === '1' ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                } else {
                    // إعادة الحالة السابقة
                    this.dataset.status = currentStatus ? '1' : '0';
                    this.innerHTML = currentStatus ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                    
                    showAlert('danger', data.message || 'حدث خطأ أثناء تحديث الحالة');
                }
            })
            .catch(error => {
                // إعادة الحالة السابقة
                this.dataset.status = currentStatus ? '1' : '0';
                this.innerHTML = currentStatus ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                
                showAlert('danger', 'حدث خطأ أثناء الاتصال بالخادم');
            });
        });
    });
}

// تنفيذ الدوال الإضافية عند اكتمال تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initAutoSlug();
    initImagePreview();
    initToggleStatus();
});
