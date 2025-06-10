<?php
/**
 * ملف تضمين الملفات الأساسية في لوحة التحكم
 * 
 * يقوم بتضمين جميع ملفات CSS و JavaScript المطلوبة للوحة التحكم
 */

// تضمين ملفات CSS
function include_admin_css() {
    $css_files = [
        'assets/css/bootstrap.rtl.min.css',
        'assets/css/fontawesome.min.css',
        'assets/css/admin-custom.css'
    ];
    
    foreach ($css_files as $file) {
        echo '<link rel="stylesheet" href="/' . $file . '?v=' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/' . $file) . '">';
    }
}

// تضمين ملفات JavaScript
function include_admin_js() {
    $js_files = [
        'assets/js/bootstrap.bundle.min.js',
        'assets/js/tinymce/tinymce.min.js',
        'assets/js/sortable.min.js',
        'assets/js/dropzone.min.js',
        'assets/js/admin-custom.js'
    ];
    
    foreach ($js_files as $file) {
        echo '<script src="/' . $file . '?v=' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/' . $file) . '"></script>';
    }
}
