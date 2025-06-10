<?php
/**
 * صفحة تحسين الصور
 * 
 * تتيح للمدير تحسين وضغط الصور الموجودة في الموقع
 */

require_once '../includes/init.php';
require_once '../includes/functions/admin_auth.php';

// التحقق من تسجيل الدخول
check_admin_login();

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'optimize_all':
            $optimized_count = 0;
            $total_saved = 0;
            
            // مجلدات الصور
            $image_dirs = [
                UPLOAD_DIR . '/services',
                UPLOAD_DIR . '/projects',
                UPLOAD_DIR . '/testimonials',
                UPLOAD_DIR . '/sections',
                UPLOAD_DIR . '/site_assets'
            ];
            
            foreach ($image_dirs as $dir) {
                if (is_dir($dir)) {
                    $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                    
                    foreach ($images as $image) {
                        $original_size = filesize($image);
                        
                        if (optimize_image_advanced($image)) {
                            $new_size = filesize($image);
                            $saved = $original_size - $new_size;
                            
                            if ($saved > 0) {
                                $optimized_count++;
                                $total_saved += $saved;
                            }
                        }
                    }
                }
            }
            
            // تسجيل النشاط
            log_activity($_SESSION['admin_id'], 'optimize_all_images', 'images', null, [
                'optimized_count' => $optimized_count,
                'total_saved' => $total_saved
            ]);
            
            $success_message = "تم تحسين {$optimized_count} صورة وتوفير " . format_file_size($total_saved) . " من المساحة";
            break;
            
        case 'optimize_single':
            $image_path = $_POST['image_path'] ?? '';
            
            if (file_exists($image_path)) {
                $original_size = filesize($image_path);
                
                if (optimize_image_advanced($image_path)) {
                    $new_size = filesize($image_path);
                    $saved = $original_size - $new_size;
                    
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'optimize_single_image', 'images', null, [
                        'image_path' => $image_path,
                        'original_size' => $original_size,
                        'new_size' => $new_size,
                        'saved' => $saved
                    ]);
                    
                    if ($saved > 0) {
                        $success_message = "تم تحسين الصورة وتوفير " . format_file_size($saved) . " من المساحة";
                    } else {
                        $success_message = "الصورة محسنة بالفعل";
                    }
                } else {
                    $error_message = "حدث خطأ أثناء تحسين الصورة";
                }
            } else {
                $error_message = "الصورة غير موجودة";
            }
            break;
            
        case 'convert_to_webp':
            $image_path = $_POST['image_path'] ?? '';
            
            if (file_exists($image_path)) {
                $webp_path = convert_to_webp($image_path);
                
                if ($webp_path) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'convert_to_webp', 'images', null, [
                        'original_path' => $image_path,
                        'webp_path' => $webp_path
                    ]);
                    
                    $success_message = "تم تحويل الصورة إلى تنسيق WebP بنجاح";
                } else {
                    $error_message = "حدث خطأ أثناء تحويل الصورة إلى WebP";
                }
            } else {
                $error_message = "الصورة غير موجودة";
            }
            break;
            
        case 'resize_images':
            $max_width = (int)$_POST['max_width'];
            $max_height = (int)$_POST['max_height'];
            $quality = (int)$_POST['quality'];
            
            $resized_count = 0;
            
            // مجلدات الصور
            $image_dirs = [
                UPLOAD_DIR . '/services',
                UPLOAD_DIR . '/projects',
                UPLOAD_DIR . '/testimonials',
                UPLOAD_DIR . '/sections',
                UPLOAD_DIR . '/site_assets'
            ];
            
            foreach ($image_dirs as $dir) {
                if (is_dir($dir)) {
                    $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                    
                    foreach ($images as $image) {
                        if (resize_image($image, $max_width, $max_height, $quality)) {
                            $resized_count++;
                        }
                    }
                }
            }
            
            // تسجيل النشاط
            log_activity($_SESSION['admin_id'], 'resize_images', 'images', null, [
                'resized_count' => $resized_count,
                'max_width' => $max_width,
                'max_height' => $max_height,
                'quality' => $quality
            ]);
            
            $success_message = "تم تغيير حجم {$resized_count} صورة";
            break;
    }
}

// الحصول على إحصائيات الصور
$image_stats = get_image_statistics();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">تحسين الصور</h1>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="optimize_all">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('هل أنت متأكد من تحسين جميع الصور؟ قد تستغرق هذه العملية وقتاً طويلاً.')">
                        <i class="fas fa-magic"></i> تحسين جميع الصور
                    </button>
                </form>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- إحصائيات الصور -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $image_stats['total_images']; ?></h5>
                            <p class="card-text">إجمالي الصور</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo format_file_size($image_stats['total_size']); ?></h5>
                            <p class="card-text">الحجم الإجمالي</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $image_stats['large_images']; ?></h5>
                            <p class="card-text">صور كبيرة الحجم</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo format_file_size($image_stats['average_size']); ?></h5>
                            <p class="card-text">متوسط حجم الصورة</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أدوات التحسين -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">تغيير حجم الصور</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="resize_images">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_width" class="form-label">العرض الأقصى (بكسل)</label>
                                            <input type="number" name="max_width" id="max_width" class="form-control" value="1920" min="100" max="5000">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_height" class="form-label">الارتفاع الأقصى (بكسل)</label>
                                            <input type="number" name="max_height" id="max_height" class="form-control" value="1080" min="100" max="5000">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="quality" class="form-label">جودة الصورة (%)</label>
                                    <input type="range" name="quality" id="quality" class="form-range" min="10" max="100" value="85" oninput="document.getElementById('qualityValue').textContent = this.value + '%'">
                                    <small class="form-text text-muted">الجودة الحالية: <span id="qualityValue">85%</span></small>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('هل أنت متأكد من تغيير حجم جميع الصور؟')">
                                        <i class="fas fa-expand-arrows-alt"></i> تغيير حجم الصور
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">إعدادات التحسين</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>تنسيقات الصور المدعومة:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> JPEG/JPG</li>
                                    <li><i class="fas fa-check text-success"></i> PNG</li>
                                    <li><i class="fas fa-check text-success"></i> GIF</li>
                                    <li><i class="fas fa-check text-success"></i> WebP</li>
                                </ul>
                            </div>
                            
                            <div class="mb-3">
                                <h6>ميزات التحسين:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-compress text-primary"></i> ضغط الصور</li>
                                    <li><i class="fas fa-expand-arrows-alt text-primary"></i> تغيير الحجم</li>
                                    <li><i class="fas fa-image text-primary"></i> تحويل إلى WebP</li>
                                    <li><i class="fas fa-magic text-primary"></i> تحسين تلقائي</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة الصور -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">الصور الموجودة</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="imagesList">
                        <!-- سيتم تحميل الصور هنا عبر JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تحميل قائمة الصور
function loadImages() {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_images_list'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('imagesList').innerHTML = data.html;
        }
    });
}

// تحسين صورة واحدة
function optimizeImage(imagePath) {
    if (confirm('هل أنت متأكد من تحسين هذه الصورة؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="optimize_single">
            <input type="hidden" name="image_path" value="${imagePath}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// تحويل إلى WebP
function convertToWebP(imagePath) {
    if (confirm('هل أنت متأكد من تحويل هذه الصورة إلى تنسيق WebP؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="convert_to_webp">
            <input type="hidden" name="image_path" value="${imagePath}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// تحميل الصور عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    loadImages();
});
</script>

<?php
// دالة للحصول على إحصائيات الصور
function get_image_statistics() {
    $stats = [
        'total_images' => 0,
        'total_size' => 0,
        'large_images' => 0,
        'average_size' => 0
    ];
    
    $image_dirs = [
        UPLOAD_DIR . '/services',
        UPLOAD_DIR . '/projects',
        UPLOAD_DIR . '/testimonials',
        UPLOAD_DIR . '/sections',
        UPLOAD_DIR . '/site_assets'
    ];
    
    foreach ($image_dirs as $dir) {
        if (is_dir($dir)) {
            $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            
            foreach ($images as $image) {
                $size = filesize($image);
                $stats['total_images']++;
                $stats['total_size'] += $size;
                
                // صور كبيرة الحجم (أكبر من 1 ميجابايت)
                if ($size > 1024 * 1024) {
                    $stats['large_images']++;
                }
            }
        }
    }
    
    if ($stats['total_images'] > 0) {
        $stats['average_size'] = $stats['total_size'] / $stats['total_images'];
    }
    
    return $stats;
}

// دالة لتغيير حجم الصورة
function resize_image($image_path, $max_width, $max_height, $quality = 85) {
    if (!file_exists($image_path)) {
        return false;
    }
    
    $image_info = getimagesize($image_path);
    if ($image_info === false) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $image_type = $image_info[2];
    
    // التحقق مما إذا كانت الصورة تحتاج إلى تغيير الحجم
    if ($width <= $max_width && $height <= $max_height) {
        return true; // لا حاجة لتغيير الحجم
    }
    
    // حساب النسبة
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // إنشاء صورة جديدة
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // إنشاء صورة من الملف
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($image_path);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // تغيير حجم الصورة
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // حفظ الصورة
    $result = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $image_path, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $image_path, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $image_path);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($new_image, $image_path, $quality);
            break;
    }
    
    // تحرير الذاكرة
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}

// دالة لتحويل الصورة إلى WebP
function convert_to_webp($image_path) {
    if (!file_exists($image_path)) {
        return false;
    }
    
    $image_info = getimagesize($image_path);
    if ($image_info === false) {
        return false;
    }
    
    $image_type = $image_info[2];
    
    // إنشاء صورة من الملف
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // إنشاء مسار الملف الجديد
    $path_info = pathinfo($image_path);
    $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    
    // حفظ كـ WebP
    $result = imagewebp($image, $webp_path, 85);
    
    // تحرير الذاكرة
    imagedestroy($image);
    
    return $result ? $webp_path : false;
}

// دالة لتنسيق حجم الملف
function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>

<?php include 'includes/footer.php'; ?>
