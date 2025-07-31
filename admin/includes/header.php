<?php
// Include Database & User Class Files
require_once __DIR__ . '/../../config/classes/user.php';
$DB_con = new USER();

// Function to format time
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Initialize variables to prevent undefined variable errors
$orderCount = 0;
$recentOrders = [];

try {
    // Get notification data
    $countStmt = $DB_con->runQuery("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $countStmt->execute();
    $orderCount = $countStmt->fetchColumn();

    $limit = 5;
    $orderStmt = $DB_con->runQuery("
        SELECT o.order_id, o.total_amount, o.order_date, u.userName, u.profile_image as user_avatar 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.order_date DESC
        LIMIT :limit
    ");
    $orderStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $orderStmt->execute();
    $recentOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error or handle it appropriately
    error_log("Database error: " . $e->getMessage());
}

$id = $_SESSION['userSession'];

$stmt = "SELECT id, profile_image FROM users WHERE id = :id";
$stmt = $user->runQuery($stmt);
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$img = $user['profile_image'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #343a40;
            --sidebar-color: #dee2e6;
            --sidebar-active-bg: #007bff;
            --sidebar-active-color: #fff;
            --sidebar-hover-bg: #495057;
            --header-bg: #f8f9fa;
            --topbar-bg: #fff;
            --topbar-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding-top: 60px;
        }
        
        /* Top Navigation Bar */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: 60px;
            background: var(--topbar-bg);
            box-shadow: var(--topbar-shadow);
            z-index: 999;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            padding: 0 40px 0 20px;
            
        }
        
        .topbar-search {
            position: relative;
            max-width: 400px;
            width: 100%;
        }
        
        .topbar-search input {
            background: #f5f5f5;
            border: none;
            padding-left: 40px;
            border-radius: 20px;
            height: 40px;
        }
        
        .topbar-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        
        .topbar-icon {
            position: relative;
            margin-left: 20px;
            color: #6c757d;
            font-size: 1.25rem;
            transition: all 0.3s;
        }
        
        .topbar-icon:hover {
            color: #007bff;
        }
        
        .topbar-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-dropdown {
            width: 350px;
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-dropdown .dropdown-header {
            padding: 10px 15px;
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
        }
        
        .notification-item .dropdown-item {
            padding: 10px 15px;
            white-space: normal;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .notification-item .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item:last-child .dropdown-item {
            border-bottom: none;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .user-name {
            font-weight: 500;
            color: #343a40;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link {
            color: var(--sidebar-color);
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.2s;
            padding: 10px 15px;
        }
        
        .sidebar .nav-link:hover {
            background: var(--sidebar-hover-bg);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: calc(100vh - 60px);
            transition: all 0.3s;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        
        .submenu {
            background: rgba(0,0,0,0.1);
            border-radius: 5px;
            padding: 5px 0;
        }
        
        .submenu .nav-link {
            padding-left: 40px;
            font-size: 0.9rem;
        }
        
        .page-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .footer {
            background-color: #343a40;        
            color: #fff;
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            text-align: center;    
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                left: -var(--sidebar-width);
            }
            
            .main-content, .topbar, .footer {
                margin-left: 0;
                width: 100%;
            }
            
            body {
                padding-top: 0;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content.active, .topbar.active, .footer.active {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="topbar ">
    <!-- Search Bar -->
    <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" class="form-control" placeholder="Search...">
    </div>
    
    <!-- Right Side Icons -->
    <div class="topbar-right">
        <div class="dropdown">
            <a href="#" class="topbar-icon dropdown-toggle" data-bs-toggle="dropdown" id="notificationDropdown">
                <i class="fas fa-bell"></i>
                <?php if($orderCount > 0): ?>
                    <span class="topbar-badge"><?php echo htmlspecialchars($orderCount); ?></span>
                <?php endif; ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                <li class="dropdown-header">New Orders (<?php echo htmlspecialchars($orderCount); ?>)</li>
                <?php if(!empty($recentOrders)): ?>
                    <?php foreach($recentOrders as $order): ?>
                        <li class="notification-item">
                            <a href="orders.php?action=view&id=<?php echo htmlspecialchars($order['order_id']); ?>" class="dropdown-item">
                                <div class="d-flex align-items-center">
                                    <?php if(!empty($order['user_avatar'])): ?>
                                        <div class="flex-shrink-0">
                                            <img src="<?php echo htmlspecialchars($order['user_avatar']); ?>" 
                                                 alt="User" class="rounded-circle" width="40" height="40">
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($order['userName']); ?></h6>
                                        <small class="text-muted">Order #<?php echo htmlspecialchars($order['order_id']); ?> - $<?php echo number_format($order['total_amount'], 2); ?></small>
                                        <small class="d-block text-muted"><?php echo time_elapsed_string($order['order_date']); ?></small>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="index.php?page=view_orders" class="dropdown-item text-center">View all orders</a></li>
                <?php else: ?>
                    <li><a class="dropdown-item text-center">No new orders</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="dropdown">
            <a href="#" class="topbar-icon dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-envelope"></i>
                <span class="topbar-badge">5</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item">No new messages</a></li>
            </ul>
        </div>
        
        <!-- User Profile -->
        <div class="user-profile dropdown">
            <a href="#" class="dropdown-toggle d-flex align-item-center" data-toggle="dropdown">
            <img src="../uploads/profile_images/<?=$img?>" alt="admin" width="30" height="30" class="rounded-circle mr-2"> &nbsp; <?= ($_SESSION['user_type'] == 'admin') ? 'Admin' : $_SESSION['user_type'] ?>
			</a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="index.php?page=admin_profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Include Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
</html>
