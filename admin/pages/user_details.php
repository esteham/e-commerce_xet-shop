<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';
$DB_con = new USER();

// Check if admin is logged in
if (!isset($_SESSION['userSession']) || $_SESSION['userSession'] !== true) {
    echo "<script>window.location.href='index.php?page=categories';</script>";
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users_profile.php");
    exit();
}

$id = $_GET['id'];
$error = '';
$user = [];
$additional_info = [];
$activities = [];

try { 
    // Get user info
    $stmt = $DB_con->runQuery("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "User not found!";
    } else {
        // Get additional info
        $stmt = $DB_con->runQuery("SELECT `key`, `value` FROM user_additional_info WHERE id = ?");
        $stmt->execute([$id]);
        $additional_info = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Get activities
        $stmt = $DB_con->runQuery("SELECT * FROM user_activities WHERE id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$id]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  
    <div class="container py-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>User Details</h1>
            <a href="index.php?page=users_profile" class="btn btn-secondary">Back to Users</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (empty($user)): ?>
            <div class="alert alert-warning">User not found</div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <img src="../uploads/profile_images/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile Image" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;"
                                 onerror="this.src='../uploads/profile_images/default.jpg'">
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p class="text-muted">@<?php echo htmlspecialchars($user['userName']); ?></p>
                            
                            <div class="d-flex justify-content-center mb-3">
                                <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <div class="text-start">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['userEmail']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                                <p><strong>Joined:</strong> <?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M j, Y H:i', strtotime($user['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            Address Information
                        </div>
                        <div class="card-body">
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>State:</strong> <?php echo htmlspecialchars($user['state']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($user['zip_code']); ?></p>
                                </div>
                            </div>
                            <p><strong>Country:</strong> <?php echo htmlspecialchars($user['country']); ?></p>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            Additional Information
                        </div>
                        <div class="card-body">
                            <?php if (empty($additional_info)): ?>
                                <p>No additional information available.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Key</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($additional_info as $key => $value): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($key); ?></td>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            Recent Activities
                        </div>
                        <div class="card-body">
                            <?php if (empty($activities)): ?>
                                <p>No activities recorded.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Activity</th>
                                                <th>IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['activity_type']); ?>
                                                        <?php if (!empty($activity['description'])): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>