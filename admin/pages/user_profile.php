<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
$users = new USER();

// Get the user ID from the URL or session
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data from database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $users->runQuery($query);
$stmt->bindParam(1, $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: var(--dark-color);
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #e9ecef;
            margin-right: 30px;
        }
        
        .profile-info h1 {
            margin: 0;
            font-size: 28px;
            color: var(--dark-color);
        }
        
        .profile-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        
        .user-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .detail-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 120px;
            color: #495057;
        }
        
        .detail-value {
            flex: 1;
            color: var(--dark-color);
        }
        
        .last-login {
            font-style: italic;
            color: #6c757d;
            font-size: 14px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-image {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="../uploads/profile_images/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" alt="Profile Image" class="profile-image">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])); ?></h1>
                <p><?php echo htmlspecialchars($user['userEmail']); ?></p>
                <p><?php echo htmlspecialchars($user['phone'] ?? 'Phone not provided'); ?></p>
                <span class="user-status status-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                    <?php echo ucfirst($user['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="profile-details">
            <div class="detail-card">
                <h3>Personal Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Username</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['userName']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">First Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['first_name'] ?? 'Not provided'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['last_name'] ?? 'Not provided'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Account Type</span>
                    <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Member Since</span>
                    <span class="detail-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="detail-card">
                <h3>Contact Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['userEmail']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address</span>
                    <span class="detail-value">
                        <?php 
                        $addressParts = [];
                        if (!empty($user['address'])) $addressParts[] = htmlspecialchars($user['address']);
                        if (!empty($user['city'])) $addressParts[] = htmlspecialchars($user['city']);
                        if (!empty($user['state'])) $addressParts[] = htmlspecialchars($user['state']);
                        if (!empty($user['zip_code'])) $addressParts[] = htmlspecialchars($user['zip_code']);
                        if (!empty($user['country'])) $addressParts[] = htmlspecialchars($user['country']);
                        
                        echo $addressParts ? implode(', ', $addressParts) : 'Not provided';
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-card">
                <h3>Account Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="user-status status-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Updated</span>
                    <span class="detail-value"><?php echo date('F j, Y g:i a', strtotime($user['updated_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Login</span>
                    <span class="detail-value">
                        <?php 
                        echo $user['last_login'] 
                            ? date('F j, Y g:i a', strtotime($user['last_login'])) 
                            : 'Never logged in';
                        ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Password Changed</span>
                    <span class="detail-value">
                        <?php 
                        echo !empty($user['password_changed_at'])
                            ? date('F j, Y', strtotime($user['password_changed_at']))
                            : 'Never changed';
                        ?>
                    </span>
                </div>
            </div>
        </div>
        
        <p class="last-login">Profile viewed on <?php echo date('F j, Y g:i a'); ?></p>
    </div>
</body>
</html>