<?php
/**
 * صفحة إدارة الرسائل
 * 
 * تتيح للمدير عرض وإدارة الرسائل الواردة من العملاء
 */

require_once __DIR__ . '/init.php'; // Loads admin-specific initialization
// admin_auth.php contains functions like admin_login, admin_logout, is_admin_logged_in (specific version)
// It's assumed that PROJECT_ROOT is defined via config.php loaded in admin/init.php
// and that admin_auth.php's dependencies (like db_query) are met by what admin/init.php sets up,
// OR that admin/init.php will be augmented to make these compatible.
// For now, directly including it using PROJECT_ROOT.
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . '/includes/functions/admin_auth.php';
} else {
    // Fallback or error if PROJECT_ROOT is not defined, though it should be by admin/init.php
    require_once dirname(__DIR__) . '/includes/functions/admin_auth.php';
}

// التحقق من تسجيل الدخول
check_admin_login();

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $message_id = (int)$_POST['message_id'];
            // Removed updated_at = NOW()
            if($db->execute("UPDATE messages SET is_read = 1 WHERE id = :id", [':id' => $message_id])) {
                // log_activity removed
                $success_message = "تم تحديد الرسالة كمقروءة";
            } else {
                $error_message = "فشل تحديث حالة الرسالة.";
            }
            break;
            
        case 'mark_unread':
            $message_id = (int)$_POST['message_id'];
            // Removed updated_at = NOW()
            if($db->execute("UPDATE messages SET is_read = 0 WHERE id = :id", [':id' => $message_id])) {
                // log_activity removed
                $success_message = "تم تحديد الرسالة كغير مقروءة";
            } else {
                $error_message = "فشل تحديث حالة الرسالة.";
            }
            break;
            
        // 'reply' case block removed entirely
            
        case 'delete':
            $message_id = (int)$_POST['message_id'];
            
            // No need to fetch message_data if log_activity is removed
            // $message_data = $db->queryOne("SELECT id, sender_name FROM messages WHERE id = :id", [':id' => $message_id]);
            
            if($db->execute("DELETE FROM messages WHERE id = :id", [':id' => $message_id])) {
                // log_activity removed
                $success_message = "تم حذف الرسالة بنجاح";
            } else {
                $error_message = "فشل حذف الرسالة.";
            }
            break;
    }
}

// الحصول على المعاملات
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// بناء الاستعلام
$where_conditions = [];
$params = []; // Use named parameters for clarity

if ($filter === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where_conditions[] = "is_read = 1";
}
// Removed 'replied' and 'unreplied' filters

if (!empty($search)) {
    // Updated field names for search
    $where_conditions[] = "(sender_name LIKE :search OR sender_email LIKE :search OR subject LIKE :search OR message_body LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// عدد الرسائل الإجمالي
$count_query_sql = "SELECT COUNT(*) as total_count FROM messages $where_clause";
$count_result = $db->queryOne($count_query_sql, $params); // Pass named params
$total_messages = $count_result ? $count_result['total_count'] : 0;


// الحصول على الرسائل
// Removed JOINs, updated field names, and ORDER BY received_at
$messages_query_sql = "SELECT id, sender_name, sender_email, subject, message_body, received_at, is_read
                       FROM messages
                       $where_clause
                       ORDER BY received_at DESC
                       LIMIT :limit OFFSET :offset";

$query_params_for_messages = $params; // Start with existing search/filter params (named)
$query_params_for_messages[':limit'] = $per_page;
$query_params_for_messages[':offset'] = $offset;

$messages = $db->query($messages_query_sql, $query_params_for_messages);


// إحصائيات سريعة - Removed is_replied logic
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
    FROM messages";
$stats = $db->queryOne($stats_sql); // No params needed for this simplified query

$total_pages = ceil($total_messages / $per_page);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">إدارة الرسائل</h1>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> تحديث
                    </button>
                </div>
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

            <!-- إحصائيات سريعة -->
            <div class="row mb-4">
                <div class="col-md-6"> {/* Adjusted to col-md-6 for two boxes */}
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total']; ?></h5>
                            <p class="card-text">إجمالي الرسائل</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"> {/* Adjusted to col-md-6 for two boxes */}
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['unread'] ?? 0; ?></h5> {/* Added null coalescing for unread */}
                            <p class="card-text">غير مقروءة</p>
                        </div>
                    </div>
                </div>
                {/* Replied and Awaiting Reply stat boxes removed */}
            </div>

            <!-- فلاتر البحث -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter" class="form-label">تصفية حسب الحالة</label>
                            <select name="filter" id="filter" class="form-select">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>جميع الرسائل</option>
                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>غير مقروءة</option>
                                <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>مقروءة</option>
                                {/* Replied and Awaiting Reply filter options removed */}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">البحث</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="البحث في اسم المرسل، بريده، الموضوع أو نص الرسالة..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- قائمة الرسائل -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">الرسائل (<?php echo $total_messages; ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد رسائل</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحالة</th>
                                        <th>المرسل</th>
                                        <th>الموضوع/الرسالة</th>
                                        {/* Service/Project column removed */}
                                        <th>تاريخ الاستلام</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo !$message['is_read'] ? 'table-warning' : ''; ?>">
                                            <td>
                                                <?php if (!$message['is_read']): ?>
                                                    <span class="badge bg-warning text-dark">غير مقروءة</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">مقروءة</span>
                                                <?php endif; ?>
                                                {/* Replied badge removed */}
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($message['sender_email']); ?></small>
                                                    {/* Phone removed */}
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($message['subject'] ?: 'بدون موضوع'); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo mb_substr(strip_tags($message['message_body']), 0, 100) . '...'; ?>
                                                </small>
                                            </td>
                                            {/* Service/Project data cell removed */}
                                            <td>
                                                <small>
                                                    <?php echo date('Y-m-d H:i', strtotime($message['received_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="viewMessage(<?php echo $message['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if (!$message['is_read']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrf_input_field(); ?>
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-success" title="تحديد كمقروءة">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrf_input_field(); ?>
                                                            <input type="hidden" name="action" value="mark_unread">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-warning" title="تحديد كغير مقروءة">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    {/* Reply button removed */}
                                                    
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- صفحات -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="صفحات الرسائل" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">السابق</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">التالي</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- مودال عرض الرسالة -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الرسالة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageContent">
                <!-- سيتم تحميل المحتوى هنا -->
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal and its trigger are removed -->

<script>
function viewMessage(messageId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_message&message_id=' + messageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('messageContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('messageModal')).show();
        } else {
            alert('حدث خطأ في تحميل الرسالة');
        }
    });
    // If viewMessage makes an AJAX call, its success handler needs to be updated
    // to expect new field names (sender_name, sender_email, subject, message_body, received_at)
    // and not expect phone, service/project details, or reply info.
    // For this subtask, we ensure the modal structure and JS here are not breaking.
    // The actual content of data.html is generated by ajax_handler.php (if used for this).
}

// replyMessage function and its call removed.

function deleteMessage(messageId) {
    if (confirm('هل أنت متأكد من حذف هذه الرسالة؟ لا يمكن التراجع عن هذا الإجراء.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" value="${messageId}">
            <?php echo csrf_input_field_for_js(); ?>
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
