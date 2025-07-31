<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';
$DB_con = new USER();

// Handle message deletion
if (isset($_GET['delete'])) {
    $messageId = $_GET['delete'];
    echo "Trying to delete message ID: " . $messageId;

    $stmt = $DB_con->runQuery("DELETE FROM contact_messages WHERE id = ?");
    if ($stmt->execute([$messageId])) {
        echo "Deleted successfully.";
        echo "<script>window.location.href='index.php?page=messages';</script>";
        exit();
    } else {
        echo "Failed to delete.";
    }
}

// Get all contact messages sorted by newest first
$stmt = $DB_con->runQuery("SELECT * FROM contact_messages ORDER BY created_at DESC");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if viewing a single message
$viewMessage = null;
if (isset($_GET['view'])) {
    $messageId = $_GET['view'];
    $stmt = $DB_con->runQuery("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $viewMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($viewMessage) {
        // Mark the message as read (optional)
        $stmt = $DB_con->runQuery("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$messageId]);
    } else {
        echo "<script>window.location.href='index.php?page=messages';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/messages.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="ms-sm-auto">
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Message deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Contact Messages</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">All Messages</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="messagesTable" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($message['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($message['name']) ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($message['email']) ?>"><?= htmlspecialchars($message['email']) ?></a></td>
                                        <td><?= htmlspecialchars($message['subject']) ?></td>
                                        <td class="message-preview" title="<?= htmlspecialchars($message['message']) ?>">
                                            <?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...
                                        </td>
                                        <td><?= date('M j, Y g:i a', strtotime($message['created_at'])) ?></td>
                                        <td>
                                            <a href="?view=<?= $message['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="index.php?page=messages&delete=<?= $message['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Message View Modal - Only show if we have a message to view -->
    <?php if ($viewMessage): ?>
    <div class="modal fade show" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message Details</h5>
                    <a href="?" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Name:</div>
                        <div class="col-md-8"><?= htmlspecialchars($viewMessage['name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8"><?= htmlspecialchars($viewMessage['email']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Subject:</div>
                        <div class="col-md-8"><?= htmlspecialchars($viewMessage['subject']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Date:</div>
                        <div class="col-md-8"><?= date('M j, Y g:i a', strtotime($viewMessage['created_at'])) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Message:</div>
                        <div class="col-md-8">
                            <div class="card card-body bg-light">
                                <?= nl2br(htmlspecialchars($viewMessage['message'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?" class="btn btn-secondary">Close</a>
                    <a href="mailto:<?= htmlspecialchars($viewMessage['email']) ?>" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Reply
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Close modal when clicking outside or on close button
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('messageModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal || e.target.classList.contains('btn-close') || e.target.classList.contains('btn-secondary')) {
                        window.location.href = '?';
                    }
                });
            }
        });
    </script>
</body>
</html>