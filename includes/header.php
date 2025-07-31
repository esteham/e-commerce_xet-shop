<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/classes/user.php';
$AUTH_user = new USER();

// Initialize messages
$register_msg = '';
$login_msg = '';
$logout_msg = '';
$active_modal = '';

// Consolidated message handling
$messages = [];

if (isset($_SESSION['login_msg'])) {
    $messages[] = $_SESSION['login_msg'];
    unset($_SESSION['login_msg']);
}

if (isset($_SESSION['login_error'])) {
    $messages[] = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
    $active_modal = 'loginModal'; // Auto-show login modal on error
}

if (isset($_SESSION['logout_msg'])) {
    $messages[] = $_SESSION['logout_msg'];
    unset($_SESSION['logout_msg']);
}

// Email availability check
if (isset($_POST['email']) && isset($_POST['check_email_availability'])) {
    $email = trim($_POST['email']);
    $response = ['available' => false, 'message' => ''];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
        echo json_encode($response);
        exit;
    }

    $stmt = $AUTH_user->runQuery("SELECT * FROM users WHERE userEmail = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $response['message'] = "This email is already registered";
    } else {
        $response['available'] = true;
        $response['message'] = "<span style='color: green;'>This email is available</span>";
    }
    
    echo json_encode($response);
    exit;
}

// Logout handling
if(isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $AUTH_user->logout();
    $_SESSION['logout_msg'] = "<div class='alert alert-success text-center'>You have been successfully logged out</div>";
    header('Location: index.php');
    exit;
}

// Registration handling
if(isset($_POST['signup'])) {
    $uname = trim($_POST['username']);
    $email = trim($_POST['email']);
    $upass = trim($_POST['password']);
    $code = md5(uniqid(rand()));

    $stmt = $AUTH_user->runQuery("SELECT * FROM users WHERE userEmail=:email_id");
    $stmt->execute(array(':email_id'=>$email));

    if ($stmt->rowCount() > 0) {
        $register_msg = "<div class='alert alert-danger' text-center role='alert'>
            <i class='bi bi-exclamation-circle-fill'></i> This email is already registered!
        </div>";
    } else {
        try {
            if ($AUTH_user->register($uname, $email, $upass, $code)) {
                $id = $AUTH_user->lastID();
                $key = base64_encode($id);
                $verificationUrl = "https://eshop.xetroot.com/accounts/verify.php?id=$key&code=$code";
    
                $message = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: 'Segoe UI', Roboto, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 20px auto; padding: 20px; }
                            .header { color: #2563eb; font-size: 24px; margin-bottom: 20px; }
                            .button { 
                                display: inline-block; 
                                padding: 12px 24px; 
                                background-color: #2563eb; 
                                color: white !important; 
                                text-decoration: none; 
                                border-radius: 6px; 
                                font-weight: 500;
                                margin: 15px 0;
                            }
                            .footer { margin-top: 30px; font-size: 14px; color: #6b7280; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>Welcome to SpiDer MonKey Center</div>
                            <p>Hello $uname,</p>
                            <p>Thank you for registering with us. To complete your registration, please verify your email address by clicking the button below:</p>
                            <a href='$verificationUrl' class='button'>Verify Your Account</a>
                            <p>If you didn't request this, you can safely ignore this email.</p>
                            <div class='footer'>
                                <p>Best regards,<br>The SpiDer MonKey Team</p>
                            </div>
                        </div>
                    </body>
                    </html>";
    
                $subject = "Complete Your Registration - SpiDer MonKey Center";
    
                if ($AUTH_user->sendMail($email, $message, $subject)) {
                    $register_msg = "<div class='alert alert-success' role='alert'>
                        <i class='bi bi-check-circle-fill'></i> Registration successful! Please check your email to activate your account.
                    </div>";
                    $active_modal = 'registerModal';
                } else {
                    throw new Exception("Failed to send verification email");
                }
            } else {
                throw new Exception("Registration failed");
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $register_msg = "<div class='alert alert-danger' role='alert'>
                <i class='bi bi-exclamation-circle-fill'></i> " . htmlspecialchars($e->getMessage()) . ". Please try again.
            </div>";
            $active_modal = 'registerModal';
        }
    }
}

// Login handling
if(isset($_POST['signin'])) {
    $email = trim($_POST['email']);
    $upass = trim($_POST['password']);

    $result = $AUTH_user->login($email, $upass);

    if($result === "success") {
        $_SESSION['login_msg'] = "Login successful! Welcome back.";
        header('Location: index.php');
        exit;
    } 
    else if($result === "inactive") {
        $_SESSION['login_error'] = "Email is registered but your account is not active. Please check your mailbox or contact support team.";
        $_SESSION['active_modal'] = 'loginModal';
        header('Location: index.php');
        exit;
    }
    else if($result === "wrongpass") {
        $_SESSION['login_error'] = "Incorrect password. Please try again.";
        $_SESSION['active_modal'] = 'loginModal';
        header('Location: index.php');
        exit;
    }
    else if($result === "notfound") {
        $_SESSION['login_error'] = "No account found with this email.";
        $_SESSION['active_modal'] = 'loginModal';
        header('Location: index.php');
        exit;
    }
    else {
        $_SESSION['login_error'] = "Something went wrong. Please try again later.";
        $_SESSION['active_modal'] = 'loginModal';
        header('Location: index.php');
        exit;
    }
}

// Toast Function
function showToast($messages) {
    foreach ($messages as $msg) {
        if (!empty($msg)) {
            $escaped_msg = htmlspecialchars($msg, ENT_QUOTES);
            echo "
                <div class='position-fixed top-0 start-50 translate-middle-x p-3' style='z-index: 1050'>
                    <div id='liveToast' class='toast align-items-center text-white bg-success border-0 show' role='alert' aria-live='assertive' aria-atomic='true'>
                        <div class='d-flex'>
                            <div class='toast-body'>
                                $escaped_msg
                            </div>
                            <button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast' aria-label='Close'></button>
                        </div>
                    </div>
                </div>
                <script>
                    setTimeout(() => {
                        const toastEl = document.getElementById('liveToast');
                        if (toastEl) toastEl.style.display = 'none';
                    }, 5000);
                </script>
                ";
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Shop - Premium Online Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.8rem 1rem;
            background-color: white !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
        }
        
        .navbar-brand span {
            color: var(--accent-color);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            color: var(--dark-color) !important;
        }
        
        .nav-link:hover {
            color: var(--secondary-color) !important;
        }
        
        .search-box {
            width: 400px;
            position: relative;
            margin-right: 1rem;
        }
        
        .search-input {
            border-radius: 20px;
            padding-left: 40px;
            border: 1px solid #ddd;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #777;
        }
        
        .cart-icon, .user-icon {
            position: relative;
            margin-left: 1rem;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #cartBadge {
            color: #ddd;
            padding: 2px 3px !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--secondary-color);
        }
        
        .btn-login {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 20px;
            padding: 0.4rem 1.2rem;
            font-weight: 500;
            border: none;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-register {
            background-color: var(--accent-color);
            color: white;
            border-radius: 20px;
            padding: 0.4rem 1.2rem;
            font-weight: 500;
            border: none;
            margin-left: 0.5rem;
        }
        
        .btn-register:hover {
            background-color: #c0392b;
            color: white;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        .footer{
            bottom: 2px;
        }
        
        .notification-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 0;
        }

        .notification-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .notification-item {
            padding: 12px 15px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
            transform: translateX(2px);
        }

        .notification-item .notification-content p {
            margin-bottom: 2px;
            color: #333;
        }

        .notification-item:hover .notification-content p {
            color: #007bff;
        }

        .empty-notification {
            opacity: 0.5;
        }
        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #888;
        }
        
        @media (max-width: 992px) {
            .search-box {
                width: 100%;
                margin: 10px 0;
            }
            
            .navbar-nav {
                margin-top: 10px;
            }
            
            .btn-login, .btn-register {
                margin: 5px 0;
                width: 100%;
            }
        }
        /* Logout Modal Styles */
        #logoutModal .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        #logoutModal .modal-header {
            background-color: #f8f9fa;
        }

        #logoutModal .modal-body i {
            color: #e74c3c;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        #logoutModal .modal-footer .btn {
            min-width: 100px;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Top Announcement Bar -->
    <div class="bg-primary text-white py-2 text-center">
        <div class="container">
            <small>Free shipping on orders over $50 | <a href="index.php" class="text-white font-weight-bold">Shop Now</a></small>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Xet<span>Shop</span></a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <?php showToast($messages); ?>
                
                <!-- Search Form -->
                <form class="form-inline search-box" id="search-form">
                    <input class="form-control search-input w-100" type="search" id="live-search" 
                        placeholder="Search for products..." autocomplete="off">
                </form>
                
                <ul class="navbar-nav ml-auto align-items-lg-center">
                    <?php if($AUTH_user->is_logged_in()): ?>
                        <!--Cart -->
                        <li class="nav-item cart-icon">
                            <a href="cart.php" class="nav-link">
                                <i class="fas fa-shopping-cart fa-lg"></i>
                                <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                </span>
                            </a>
                        </li>

                        <!--Bell Notification-->
                        <li class="nav-item dropdown">          
                            <?php
                                $auth_id = $_SESSION['userSession'];
                                $notif_stmt = $AUTH_user->runQuery("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
                                $notif_stmt->execute([':uid'=>$auth_id]);
                                $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);

                                $count_stmt = $AUTH_user->runQuery("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = :uid AND is_read = 0");
                                $count_stmt->execute([':uid'=>$auth_id]);
                                $unreadCount = $count_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
                            ?>
                            <a href="#" class="nav-link dropdown-toggle text-white" id="notifDropdown" data-toggle="dropdown">
                                <i class="fa-solid fa-bell fa-lg"></i>

                                <?php if($unreadCount > 0): ?>
                                    <span class="badge badge-danger rounded-circle" style="font-size: 0.5rem; position: relative; top: -12px;"><?= $unreadCount ?></span>
                                <?php endif; ?>
                            </a>

                            <div class="dropdown-menu dropdown-menu-right notification-menu" style="min-width: 350px; max-height: 400px; overflow-y: auto; background-color: #f0e6cc;">
                                <div class="notification-header px-3 py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Notifications</h6>
                                    <span class="badge badge-pill badge-primary" style="font-size: 0.8rem;"><?= count($notifications) ?></span>
                                </div>
                                
                                <?php if(count($notifications) > 0): ?>
                                    <div class="notification-list">
                                        <?php foreach($notifications as $notif): ?>
                                        <a class="dropdown-item notification-item font-weight-bold" href="index.php?page=notification_view&id=<?= $notif['id'] ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="notification-icon bg-light-primary rounded-circle p-2 mr-3">
                                                    <i class="fas fa-bell text-primary"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <p class="mb-1"><?= htmlspecialchars(substr($notif['message'],0,50)) ?><?= strlen($notif['message']) > 50 ? '...' : '' ?></p>
                                                    <small class="text-muted"><i class="far fa-clock mr-1"></i><?= $notif['created_at'] ?></small>
                                                </div>
                                            </div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="dropdown-divider"></div>
                                    <a href="notifications.php" class="dropdown-item text-center text-primary font-weight-bold">
                                        View All Notifications <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="empty-notification mb-3">
                                            <i class="far fa-bell-slash fa-3x text-muted"></i>
                                        </div>
                                        <h6 class="text-muted">No new notifications</h6>
                                        <small class="text-muted">We'll notify you when something arrives</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php
                                function getUserAvatar($id, $defaultAvatar = 'assets/images/default-avatar.jpg') {
                                    global $AUTH_user;
                                    $stmt = $AUTH_user->runQuery("SELECT profile_image FROM users WHERE id = :id");
                                    $stmt->execute([':id' => $id]);
                                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                    return (!empty($user['profile_image']) && file_exists('uploads/profile_images/' . $user['profile_image']))
                                            ? 'uploads/profile_images/' . $user['profile_image'] : $defaultAvatar;
                                }
                                ?>
                                <img src="<?= getUserAvatar($_SESSION['userSession']) ?>" class="user-avatar" alt="User">
                                <span>My Account</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="user_profile.php"><i class="fas fa-user mr-2"></i>Profile</a>
                                <a class="dropdown-item" href="my_orders.php"><i class="fas fa-box mr-2"></i>My Orders</a>
                                <a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart mr-2"></i>Wishlist</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="btn btn-login" data-toggle="modal" data-target="#loginModal">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-register" data-toggle="modal" data-target="#registerModal">
                                <i class="fas fa-user-plus mr-1"></i> Register
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- The login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-weight-bold" id="loginModalLabel">Welcome Back</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body px-4 py-0">
                    <!-- Login Error Messages Display -->
                    <?php 
                    if(!empty($messages)) {
                        foreach($messages as $msg) {
                            if(strpos($msg, 'Login') !== false || strpos($msg, 'password') !== false || strpos($msg, 'account') !== false) {
                                echo '<div class="alert alert-danger">'.$msg.'</div>';
                            }
                        }
                    }
                    ?>
                    
                    <!-- Social Login Buttons -->
                    <div class="social-login text-center mb-4">
                        <p class="text-muted mb-3">Sign in with</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="btn btn-outline-primary rounded-circle mx-2 social-btn">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger rounded-circle mx-2 social-btn">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info rounded-circle mx-2 social-btn">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="divider d-flex align-items-center my-2">
                        <p class="text-center text-muted mx-2 mb-0">Or</p>
                    </div>
                    
                    <!-- Email/Password Form -->
                    <form method="POST">
                        
                        <div class="form-group">
                            <input type="email" class="form-control form-control-lg" name="email" placeholder="Email address" required>
                        </div>
                        
                        <div class="form-group position-relative">
                            <input type="password" class="form-control form-control-lg" name="password" id="passwordField" placeholder="Password" required>
                            <span class="position-absolute" onclick="togglePassword('passwordField', 'toggleIcon')" id="toggleIcon"
                                  style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; font-size: 20px;">
                                🙈️
                            </span>
                        </div>

                    
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>
                            <a href="accounts/fpass.php" class="text-primary">Forgot password?</a>
                        </div>
                    
                        <button type="submit" name="signin" class="btn btn-primary btn-lg btn-block mb-3">Sign In</button>
                    </form>
                    
                    <!-- Registration Option -->
                    <div class="text-center mt-4 pb-3">
                        <p class="text-muted">Don't have an account? 
                            <button class="btn btn-outline-secondary btn-sm ml-2" data-dismiss="modal" data-toggle="modal" data-target="#registerModal">
                                Sign Up
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-weight-bold" id="registerModalLabel">Create Your Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body px-4 py-0">
                    <?php if(!empty($register_msg)): ?>
                        <div class="register-messages">
                            <?= $register_msg ?>
                        </div>
                    <?php endif; ?>
                    <!-- Social Sign-Up Buttons -->
                    <div class="social-login text-center mb-2">
                        <p class="text-muted mb-2">Sign up with</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="btn btn-outline-primary rounded-circle mx-2 social-btn">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger rounded-circle mx-2 social-btn">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info rounded-circle mx-2 social-btn">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="divider d-flex align-items-cente">
                        <p class="text-center text-muted">Or</p>
                    </div>
                    
                    <!-- Registration Form -->
                    <form method="POST">
                        <div class="form-group">
                            <input type="text" class="form-control form-control-lg" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control form-control-lg" name="email" id="email" placeholder="Email address" required>
                            <small id="email-status" class="text-danger"></small>
                        </div>
                        <div class="form-group password-wrapper position-relative">
                            <input type="password" id="createPassword" name="password"
                                   class="form-control form-control-lg"
                                   placeholder="Create password" required>
                            <span id="createToggleIcon"
                                  onclick="togglePassword('createPassword', 'createToggleIcon')"
                                  style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                🙈️
                            </span>
                        </div>
                        
                        <ul id="password-criteria" style="font-size: 0.9rem; list-style: none; padding-left: 0; display: none;">
                            <li id="lowercase" style="color: #c0392b;">❌ At least one lowercase letter</li>
                            <li id="uppercase" style="color: #c0392b;">❌ At least one uppercase letter</li>
                            <li id="length" style="color: #c0392b;">❌ Minimum 8 characters</li>
                        </ul>
                        
                        <p id="success-message" style="display: none; color: green; font-weight: bold;">
                            ✅ Strong password
                        </p>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="termsAgree" required>
                                <label class="form-check-label" for="termsAgree">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="signup" class="btn btn-success btn-lg btn-block mb-2" 
                                <?php if(!empty($register_msg) && strpos($register_msg, 'already registered') !== false) echo 'disabled'; ?>>
                            Create Account
                        </button>                    
                    </form>
                    
                    <!-- Login Option -->
                    <div class="text-center mt-3 pb-3">
                        <p class="text-muted">Already have an account? 
                            <button class="btn btn-outline-primary btn-sm ml-2" data-dismiss="modal" data-toggle="modal" data-target="#loginModal">
                                Sign In
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-weight-bold" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-sign-out-alt fa-4x text-primary mb-3"></i>
                    <h5>Are you sure you want to logout?</h5>
                    <p class="text-muted">You'll need to sign in again to access your account</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Cancel</button>
                    <a href="?logout=true" class="btn btn-danger px-4">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>
    
<script>
    $(document).on('click', '.modal .close', function() {
    $(this).closest('.modal').modal('hide');
});

$(document).ready(function(){
    $('.modal .close').click(function() {
        $(this).closest('.modal').modal('hide');
    });

    // Email availability check
    $('#registerModal #email').on('input', function(){
        var email = $(this).val();
        var signupButton = $('button[name="signup"]');
        
        if(email.length > 4){
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    email: email,
                    check_email_availability: true
                },
                dataType: 'json',
                success: function(response){
                    $('#email-status').html(response.message);
                    
                    if (response.available) {
                        $('#email-status').css('color', 'green');
                        signupButton.prop('disabled', false);
                    } else {
                        $('#email-status').css('color', 'red');
                        signupButton.prop('disabled', true);
                    }
                },
                error: function() {
                    $('#email-status').html('Error checking email availability');
                    $('#email-status').css('color', 'red');
                }
            });
        } else {
            $('#email-status').html('');
            signupButton.prop('disabled', false);
        }
    });

    // Auto-close alerts after 5 seconds
    setTimeout(function(){
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);

    // Show modal if needed
    <?php if(!empty($active_modal)): ?>
        $('#<?= $active_modal ?>').modal('show');
    <?php endif; ?>

    // Initialize dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Alternative click handler if needed
    $('.dropdown').on('show.bs.dropdown', function() {
        $(this).find('.dropdown-menu').first().stop(true, true).slideDown();
    });
    
    $('.dropdown').on('hide.bs.dropdown', function() {
        $(this).find('.dropdown-menu').first().stop(true, true).slideUp();
    });

    // Password strength validation
    const passwordInput = document.getElementById("createPassword");
    const criteriaList = document.getElementById("password-criteria");
    const successMessage = document.getElementById("success-message");

    const checks = {
        lowercase: /[a-z]/,
        uppercase: /[A-Z]/,
        length: /.{8,}/
    };

    passwordInput.addEventListener("focus", function () {
        if (!successMessage.style.display || successMessage.style.display === "none") {
            criteriaList.style.display = "block";
        }
    });

    passwordInput.addEventListener("input", function () {
        const value = passwordInput.value;
        let allPassed = true;

        for (const [key, regex] of Object.entries(checks)) {
            const isValid = regex.test(value);
            updateCriteria(key, isValid);
            if (!isValid) allPassed = false;
        }

        if (allPassed) {
            criteriaList.style.display = "none";
            successMessage.style.display = "block";
        } else {
            criteriaList.style.display = "block";
            successMessage.style.display = "none";
        }
    });

    passwordInput.addEventListener("blur", function () {
        const value = passwordInput.value;
        let allPassed = true;
        for (const regex of Object.values(checks)) {
            if (!regex.test(value)) {
                allPassed = false;
                break;
            }
        }

        if (!allPassed) {
            criteriaList.style.display = "none";
        }
    });

    function updateCriteria(id, isValid) {
        const element = document.getElementById(id);
        if (isValid) {
            element.style.color = "green";
            element.textContent = "✅ " + element.textContent.slice(2);
        } else {
            element.style.color = "#c0392b";
            element.textContent = "❌ " + element.textContent.slice(2);
        }
    }
});

    // Toggle password visibility
    function togglePassword(fieldId, iconId) {
        const passField = document.getElementById(fieldId);
        const toggleIcon = document.getElementById(iconId);

        if (passField.type === "password") {
            passField.type = "text";
            toggleIcon.textContent = "👁️";
        } else {
            passField.type = "password";
            toggleIcon.textContent = "🙈️";
        }
    }

    // Smooth logout modal transition
    $('#logoutModal').on('show.bs.modal', function (e) {
        $(this).css('display', 'flex');
        $(this).find('.modal-dialog').css('transform', 'translateY(0)');
    });

    $('#logoutModal').on('hidden.bs.modal', function (e) {
        $(this).hide();
    });
</script>

</body>
</html>