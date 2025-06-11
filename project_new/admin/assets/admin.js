/**
 * لوحة التحكم - الوظائف الأساسية
 * حداد جده - نظام إدارة المحتوى
 */

class AdminPanel {
    constructor() {
        this.currentSection = 'dashboard';
        // this.apiBaseUrl = 'api/'; // Replaced by direct calls to ajax_handler.php or page itself
        this.tinymceInstances = {};
        this.init();
    }

    init() {
        this.bindEvents();
        console.log('AdminPanel JS initialized. PHP handles routing.');
        feather.replace(); // Ensure Feather icons are re-initialized if content changes dynamically
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') || e.target.closest('.close-modal-btn')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.closeModal(modal.id);
                }
            }
        });
         // Handle dynamic feather icon replacement for new content
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length) {
                    feather.replace();
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        // If TinyMCE was in this modal, destroy its instance to prevent issues
        const textareas = modal.querySelectorAll('.tinymceeditor');
        textareas.forEach(ta => {
            if (tinymce.get(ta.id)) {
                tinymce.get(ta.id).destroy();
            }
        });
    }
    
    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();
        
        const alertDiv = document.createElement('div');
        let bgColor, textColor, borderColor, iconSvg;

        switch (type) {
            case 'success':
                bgColor = 'bg-green-100'; textColor = 'text-green-700'; borderColor = 'border-green-400';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                break;
            case 'error':
                bgColor = 'bg-red-100'; textColor = 'text-red-700'; borderColor = 'border-red-400';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                break;
            case 'warning':
                bgColor = 'bg-yellow-100'; textColor = 'text-yellow-700'; borderColor = 'border-yellow-400';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 3.001-1.742 3.001H4.42c-1.53 0-2.493-1.667-1.743-3.001l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1.75-4.5a1.75 1.75 0 00-3.5 0v.25a1.75 1.75 0 003.5 0v-.25zM10 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" clip-rule="evenodd"></path></svg>'; // Placeholder, use a better warning icon
                break;
            default: // info
                bgColor = 'bg-blue-100'; textColor = 'text-blue-700'; borderColor = 'border-blue-400';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                break;
        }
        
        alertDiv.className = `p-4 mb-3 border-l-4 rounded-md shadow-lg flex items-center ${bgColor} ${textColor} ${borderColor}`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${iconSvg}
            <span class="flex-1">${message}</span>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 hover:bg-opacity-20 hover:bg-current focus:ring-2 focus:ring-current inline-flex h-8 w-8" aria-label="Close">
                <span class="sr-only">إغلاق</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        `;

        alertDiv.querySelector('button').onclick = () => {
            alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => alertDiv.remove(), 300);
        };
        
        alertContainer.appendChild(alertDiv);

        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentNode) {
                     alertDiv.querySelector('button').click(); // Trigger programmatic close
                }
            }, duration);
        }
    }
    
    createAlertContainer() {
        let container = document.getElementById('alert-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alert-container';
            container.className = 'fixed top-20 right-4 z-[1060] w-full max-w-sm space-y-3'; // Higher z-index than modals
            document.body.appendChild(container);
        }
        return container;
    }


    truncateText(text, length = 100) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }
}

let adminPanel; // Make it global for access from PHP-generated inline JS if needed

document.addEventListener('DOMContentLoaded', function() {
    adminPanel = new AdminPanel();
});


// Global helper functions that might be called from inline JS in PHP views
function showModal(modalId) {
    if (adminPanel) adminPanel.openModal(modalId);
}

function closeModal(modalId) {
    if (adminPanel) adminPanel.closeModal(modalId);
}

function confirmDelete(id, entityType, ajaxUrl) {
    // ترجمة نوع الكيان للعربية
    const entityNames = {
        'section': 'قسم',
        'service': 'خدمة', 
        'project': 'مشروع',
        'testimonial': 'رأي عميل',
        'fact': 'حقيقة'
    };
    
    const entityName = entityNames[entityType] || 'عنصر';

    if (confirm(`هل أنت متأكد من حذف هذا الـ ${entityName}؟\nهذا الإجراء لا يمكن التراجع عنه.`)) {
        // إنشاء FormData وإضافة البيانات الأساسية
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', `delete_${entityType}`);
        
        // الحصول على CSRF token بطرق متعددة
        let csrfToken = '';
        const csrfField = document.querySelector('input[name="<?php echo CSRF_TOKEN_NAME; ?>"]');
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        
        if (csrfField) {
            csrfToken = csrfField.value;
        } else if (metaCsrf) {
            csrfToken = metaCsrf.content;
        } else {
            // محاولة الحصول من localStorage/SessionStorage إذا لم يوجد في الصفحة
            csrfToken = localStorage.getItem('csrf_token') || sessionStorage.getItem('csrf_token');
        }

        // إذا لم يتم العثور على token
        if (!csrfToken) {
            adminPanel.showAlert('خطأ أمني: لم يتم العثور على رمز الحماية (CSRF).', 'error');
            console.error('CSRF token not found in page');
            return false;
        }

        formData.append('<?php echo CSRF_TOKEN_NAME; ?>', csrfToken);

        // إضافة رأس X-Requested-With لتحديد أن الطلب AJAX
        const headers = new Headers();
        headers.append('X-Requested-With', 'XMLHttpRequest');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: headers,
            credentials: 'same-origin' // إرسال الكوكيز مع الطلب
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                adminPanel.showAlert(data.message || `تم حذف ${entityName} بنجاح.`, 'success');
                
                // محاولة إزالة الصف من الجدول
                const row = document.getElementById(`${entityType}-row-${id}`);
                if (row) {
                    row.classList.add('bg-red-50');
                    setTimeout(() => row.remove(), 500);
                } else {
                    // إذا لم يتم العثور على الصف، إعادة تحميل الصفحة بعد ثانية
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                throw new Error(data.message || `فشل حذف ${entityName}.`);
            }
        })
        .catch(error => {
            adminPanel.showAlert(`خطأ أثناء حذف ${entityName}: ${error.message}`, 'error');
            console.error(`Delete ${entityType} error:`, error);
            
            // إظهار تفاصيل الخطأ في وضع التطوير
            if (<?php echo DEBUG_MODE ? 'true' : 'false'; ?>) {
                console.debug('Error details:', {
                    entityType: entityType,
                    id: id,
                    ajaxUrl: ajaxUrl
                });
            }
        });
    }
}

function getCSRFToken() {
    // البحث عن حقل CSRF في الصفحة
    const csrfField = document.querySelector('input[name="csrf_token"]');
    if (csrfField) {
        return csrfField.value;
    }
    return null;
}

function fetchWithCSRF(url, options = {}) {
    const token = getCSRFToken();
    if (!token) {
        console.error('CSRF token not found');
        return Promise.reject('CSRF token missing');
    }

    const fetchOptions = { ...options };

    if (!fetchOptions.method || fetchOptions.method.toUpperCase() === 'GET') {
        const separator = url.includes('?') ? '&' : '?';
        url = `${url}${separator}csrf_token=${encodeURIComponent(token)}`;
    } else {
        // Initialize headers object if not present
        if (!fetchOptions.headers) {
            fetchOptions.headers = {};
        }

        // Always add CSRF token header
        fetchOptions.headers['X-CSRF-Token'] = token;

        // Only set Content-Type manually if not using FormData
        if (!(fetchOptions.body instanceof FormData)) {
            fetchOptions.headers['Content-Type'] = 'application/json';
        }
    }

    return fetch(url, fetchOptions)
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON, got: ${text.substring(0, 100)}`);
            }
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}


function ajaxSubmitForm(formElement, callback) {
    if (!(formElement instanceof HTMLFormElement)) {
        console.error('Element is not a form');
        adminPanel.showAlert('حدث خطأ في إرسال النموذج', 'error');
        return false;
    }

    const submitButton = formElement.querySelector('button[type="submit"]');
    const originalButtonText = submitButton?.innerHTML;
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin inline-block h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg> جاري الحفظ...
        `;
    }

    // Handle TinyMCE editors
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }

    const formData = new FormData(formElement);
    const actionUrl = formElement.getAttribute('action');

    fetchWithCSRF(actionUrl, {
        method: formElement.method,
        body: formData
    })
    .then(data => {
        if (typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('AJAX Submit Error:', error);
        adminPanel.showAlert(`حدث خطأ: ${error.message}`, 'error');
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });

    return false;
}


function 일반_callback(response) { // Generic callback, often for simple actions like toggle
    if (response.success) {
        adminPanel.showAlert(response.message || 'تم تنفيذ الإجراء بنجاح!', 'success');
        setTimeout(() => window.location.reload(), 1000); // Simple reload
    } else {
        adminPanel.showAlert(response.message || 'فشل تنفيذ الإجراء.', 'error');
    }
}

// TinyMCE helpers
function initializeTinyMCE(selector = '.tinymceeditor') {
    const elements = document.querySelectorAll(selector);
    elements.forEach(el => {
        // if (tinymce.get(el.id)) { // If an instance already exists for this ID, remove it
        //     tinymce.get(el.id).destroy();
        // }
        if (!el.id) { // Ensure textarea has an ID
            el.id = 'tinymce_' + Math.random().toString(36).substr(2, 9);
        }
        tinymce.init({
            selector: '#' + el.id,
            plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount directionality',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help | ltr rtl | code',
            language: 'ar',
            directionality: 'rtl',
            height: 300,
            menubar: true,
            convert_urls: false, // Keep URLs as they are
            relative_urls: false, // Keep URLs as they are
            remove_script_host: false, // Keep URLs as they are
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save(); // Persist changes to the underlying textarea
                });
            }
        });
    });
}

function destroyExistingTinyMCEInstance(selector) {
    const element = document.querySelector(selector);
    if (element && element.id && tinymce.get(element.id)) {
        tinymce.get(element.id).destroy();
    }
}

// Call on DOM ready if any .tinymceeditor exists on initial page load (not in modal)
// document.addEventListener('DOMContentLoaded', function() {
//     initializeTinyMCE();
// });


