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
            if($db->execute("UPDATE messages SET is_read = 1, updated_at = NOW() WHERE id = :id", [':id' => $message_id])) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'mark_message_read', 'messages', $message_id);
                $success_message = "تم تحديد الرسالة كمقروءة";
            } else {
                $error_message = "فشل تحديث حالة الرسالة.";
            }
            break;
            
        case 'mark_unread':
            $message_id = (int)$_POST['message_id'];
            if($db->execute("UPDATE messages SET is_read = 0, updated_at = NOW() WHERE id = :id", [':id' => $message_id])) {
                // تسجيل النشاط
                log_activity($_SESSION['admin_id'], 'mark_message_unread', 'messages', $message_id);
                $success_message = "تم تحديد الرسالة كغير مقروءة";
            } else {
                $error_message = "فشل تحديث حالة الرسالة.";
            }
            break;
            
        case 'reply':
            $message_id = (int)$_POST['message_id'];
            $reply_message = trim($_POST['reply_message']);
            
            if (!empty($reply_message)) {
                $sql_reply = "UPDATE messages SET is_replied = 1, reply_message = :reply_message, replied_at = NOW(), replied_by = :replied_by, updated_at = NOW() WHERE id = :id";
                $params_reply = [
                    ':reply_message' => $reply_message,
                    ':replied_by' => $_SESSION['admin_id'],
                    ':id' => $message_id
                ];
                if($db->execute($sql_reply, $params_reply)) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'reply_message', 'messages', $message_id);
                    $success_message = "تم إرسال الرد بنجاح";
                } else {
                    $error_message = "فشل إرسال الرد.";
                }
                
                $success_message = "تم إرسال الرد بنجاح";
            } else {
                $error_message = "يرجى كتابة نص الرد";
            }
            break;
            
        case 'delete':
            $message_id = (int)$_POST['message_id'];
            
            // الحصول على بيانات الرسالة قبل الحذف
            $message_data = $db->queryOne("SELECT * FROM messages WHERE id = :id", [':id' => $message_id]);
            
            if ($message_data) {
                if($db->execute("DELETE FROM messages WHERE id = :id", [':id' => $message_id])) {
                    // تسجيل النشاط
                    log_activity($_SESSION['admin_id'], 'delete_message', 'messages', $message_id, $message_data);
                    $success_message = "تم حذف الرسالة بنجاح";
                } else {
                    $error_message = "فشل حذف الرسالة.";
                }
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
$params = [];

if ($filter === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where_conditions[] = "is_read = 1";
} elseif ($filter === 'replied') {
    $where_conditions[] = "is_replied = 1";
} elseif ($filter === 'unreplied') {
    $where_conditions[] = "is_replied = 0";
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// عدد الرسائل الإجمالي
// For $db->queryOne with positional placeholders, parameters must be indexed numerically in order.
// If $search is empty, $params is empty. If $search is not empty, $params has 4 elements.
$count_query_sql = "SELECT COUNT(*) as total_count FROM messages $where_clause";
$count_result = $db->queryOne($count_query_sql, $params);
$total_messages = $count_result ? $count_result['total_count'] : 0;


// الحصول على الرسائل
// Similar care for parameters if they are positional for $db->query
$messages_query_sql = "SELECT m.*, s.title as service_title, p.title as project_title, u.full_name as replied_by_name
                       FROM messages m
                       LEFT JOIN services s ON m.service_id = s.id
                       LEFT JOIN projects p ON m.project_id = p.id
                       LEFT JOIN users u ON m.replied_by = u.id
                       $where_clause
                       ORDER BY m.created_at DESC
                       LIMIT :limit OFFSET :offset";

$query_params_for_messages = $params; // Start with existing search/filter params
$query_params_for_messages['limit'] = $per_page;
$query_params_for_messages['offset'] = $offset;
// Convert to named if $params was positional for search
// This is getting complex due to mixed param types. Assuming $db->query handles it or $params is made named.
// For simplicity, let's assume $db->query can handle an array of mixed (indexed for WHERE, named for LIMIT/OFFSET)
// Or, better, make all params named if possible or stick to positional if the class allows.
// Given the structure of $params from search, it's positional. Let's adjust.
$messages_query_sql_positional = "SELECT m.*, s.title as service_title, p.title as project_title, u.full_name as replied_by_name
                                 FROM messages m
                                 LEFT JOIN services s ON m.service_id = s.id
                                 LEFT JOIN projects p ON m.project_id = p.id
                                 LEFT JOIN users u ON m.replied_by = u.id
                                 $where_clause
                                 ORDER BY m.created_at DESC
                                 LIMIT ? OFFSET ?";
$params_for_messages_positional = $params;
$params_for_messages_positional[] = $per_page;
$params_for_messages_positional[] = $offset;
$messages = $db->query($messages_query_sql_positional, $params_for_messages_positional);


// إحصائيات سريعة
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN is_replied = 1 THEN 1 ELSE 0 END) as replied
    FROM messages";
$stats = $db->queryOne($stats_sql);

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
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total']; ?></h5>
                            <p class="card-text">إجمالي الرسائل</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['unread']; ?></h5>
                            <p class="card-text">غير مقروءة</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['replied']; ?></h5>
                            <p class="card-text">تم الرد عليها</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['total'] - $stats['replied']; ?></h5>
                            <p class="card-text">بانتظار الرد</p>
                        </div>
                    </div>
                </div>
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
                                <option value="replied" <?php echo $filter === 'replied' ? 'selected' : ''; ?>>تم الرد عليها</option>
                                <option value="unreplied" <?php echo $filter === 'unreplied' ? 'selected' : ''; ?>>بانتظار الرد</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">البحث</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="البحث في الاسم، البريد الإلكتروني، الموضوع أو الرسالة..." 
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
                                        <th>الموضوع</th>
                                        <th>الخدمة/المشروع</th>
                                        <th>تاريخ الإرسال</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo !$message['is_read'] ? 'table-warning' : ''; ?>">
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <?php if (!$message['is_read']): ?>
                                                        <span class="badge bg-warning text-dark mb-1">غير مقروءة</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success mb-1">مقروءة</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($message['is_replied']): ?>
                                                        <span class="badge bg-info">تم الرد</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($message['name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                                                    <?php if ($message['phone']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($message['phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($message['subject'] ?: 'بدون موضوع'); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo mb_substr(strip_tags($message['message']), 0, 100) . '...'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($message['service_title']): ?>
                                                    <span class="badge bg-primary">خدمة: <?php echo htmlspecialchars($message['service_title']); ?></span>
                                                <?php elseif ($message['project_title']): ?>
                                                    <span class="badge bg-secondary">مشروع: <?php echo htmlspecialchars($message['project_title']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">عام</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?>
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
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-success" title="تحديد كمقروءة">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="mark_unread">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-warning" title="تحديد كغير مقروءة">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-outline-info" 
                                                            onclick="replyMessage(<?php echo $message['id']; ?>)">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                    
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

<!-- مودال الرد على الرسالة -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">الرد على الرسالة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="message_id" id="replyMessageId">
                    
                    <div class="mb-3">
                        <label for="reply_message" class="form-label">نص الرد</label>
                        <textarea name="reply_message" id="reply_message" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
}

function replyMessage(messageId) {
    document.getElementById('replyMessageId').value = messageId;
    new bootstrap.Modal(document.getElementById('replyModal')).show();
}

function deleteMessage(messageId) {
    if (confirm('هل أنت متأكد من حذف هذه الرسالة؟ لا يمكن التراجع عن هذا الإجراء.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" value="${messageId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
