<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$AUTH_user = new USER();

if (!isset($_SESSION['userSession'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['userSession'];

// Mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    $stmt = $AUTH_user->runQuery("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $id]);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// Delete a notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $notifID = (int) $_POST['id'];
    $stmt = $AUTH_user->runQuery("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
    $stmt->execute([
        ':id' => $notifID,
        ':uid' => $id
    ]);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'deleted']);
    exit;
}

// Delete all notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    $stmt = $AUTH_user->runQuery("DELETE FROM notifications WHERE user_id = :uid");
    $stmt->execute([':uid' => $id]);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'all_deleted']);
    exit;
}

// Get notifications
$stmt = $AUTH_user->runQuery("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid'=>$id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read when page loads
$AUTH_user->runQuery("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0")->execute([':uid'=>$id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/notifications.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="notification-container">
        <div class="notification-header">
            <div class="notification-title">
                <i class="fas fa-bell"></i>
                Notifications
                <?php if(count($notifications) > 0): ?>
                    <span class="unread-badge"><?= count($notifications) ?> New</span>
                <?php endif; ?>
            </div>
            
            <div class="notification-actions">
                <button id="markAllReadBtn" class="btn-mark-all">
                    <i class="fas fa-check-circle me-1"></i> Mark all as read
                </button>
                <button id="deleteAllBtn" class="btn-delete-all">
                    <i class="fas fa-trash-alt me-1"></i> Delete all
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
        
        <?php if(count($notifications) > 0): ?>
            <div class="notification-list">
                <?php foreach($notifications as $notif): ?>
                <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" data-id="<?= $notif['id'] ?>">
                    <!-- Notification content new products details function here -->
                    <div class="notification-content">
                        <a href="notification_details.php"class="notification-text">
                            <?= htmlspecialchars($notif['message']) ?>
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <?= date('M j, Y \a\t g:i a', strtotime($notif['created_at'])) ?>
                            </div>
                        </a>
                        <div class="notification-actions-btn">
                            <button class="btn btn-sm btn-outline-danger delete-btn" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php if(!$notif['is_read']): ?>
                        <div class="notification-badge">New</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="far fa-bell-slash"></i>
                </div>
                <h4 class="empty-title">No notifications yet</h4>
                <p class="empty-text">When you receive new notifications, they'll appear here.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i> Go to Home
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Mark all as read
        $('#markAllReadBtn').click(function() {
            $.post(window.location.href, { action: 'mark_all_read' }, function(response) {
                if(response.status === 'success') {
                    $('.notification-item').removeClass('unread');
                    $('.notification-badge').remove();
                    $('.unread-badge').remove();
                    toastr.success('All notifications marked as read');
                }
            }).fail(function() {
                toastr.error('Failed to mark notifications as read');
            });
        });
        
        // Delete single notification
        $('.delete-btn').click(function(e) {
            e.stopPropagation();
            const notificationItem = $(this).closest('.notification-item');
            const notifID = notificationItem.data('id');
            
            if(confirm('Are you sure you want to delete this notification?')) {
                $.post(window.location.href, { action: 'delete', id: notifID }, function(response) {
                    if(response.status === 'deleted') {
                        notificationItem.fadeOut(300, function() {
                            $(this).remove();
                            updateNotificationCount();
                            toastr.success('Notification deleted');
                        });
                    }
                }).fail(function() {
                    toastr.error('Failed to delete notification');
                });
            }
        });
        
        // Delete all notifications
        $('#deleteAllBtn').click(function() {
            if(confirm('Are you sure you want to delete all notifications?')) {
                $.post(window.location.href, { action: 'delete_all' }, function(response) {
                    if(response.status === 'all_deleted') {
                        $('.notification-list').fadeOut(300, function() {
                            $(this).remove();
                            // Show empty state
                            $('.notification-container').append(`
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="far fa-bell-slash"></i>
                                    </div>
                                    <h4 class="empty-title">No notifications yet</h4>
                                    <p class="empty-text">When you receive new notifications, they'll appear here.</p>
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-home me-1"></i> Go to Home
                                    </a>
                                </div>
                            `);
                            toastr.success('All notifications deleted');
                        });
                    }
                }).fail(function() {
                    toastr.error('Failed to delete notifications');
                });
            }
        });
        
        // Update notification count in header
        function updateNotificationCount() {
            const count = $('.notification-item').length;
            if(count === 0) {
                $('.unread-badge').remove();
            } else {
                if($('.unread-badge').length === 0) {
                    $('.notification-title').append(`<span class="unread-badge">${count} New</span>`);
                } else {
                    $('.unread-badge').text(count + ' New');
                }
            }
        }
        
        // Notification click (optional - if you want to make them clickable)
        $('.notification-item').click(function() {
            // You can add specific action when notification is clicked
            // For example, redirect to related page
            // window.location.href = 'some-page.php?id=' + $(this).data('id');
        });
    });
    </script>
    <!-- Toastr for notifications -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-bottom-right",
            "timeOut": "3000"
        };
    </script>
