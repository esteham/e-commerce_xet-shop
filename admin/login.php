<?php
session_start();
require_once __DIR__ . '/../config/classes/user.php';
require_once __DIR__ . '/../config/classes/adminProfile.php';

$user = new AdminProfile();
$error = "";
$show_logout_message = false;

// Check if user is already logged in
if($user->is_logged_in()) {
    if($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'manager' || $_SESSION['user_type'] == 'delivaryman') 
    {
        header('location: index.php');
        exit;
    } else {
        $error = "Access Denied";
        session_destroy();
    }
}

// Process login form
if(isset($_POST['btn-login'])) {
    $email = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic input validation
    if(empty($email)) {
        $error = "Please enter your username";
    } elseif(empty($password)) {
        $error = "Please enter your password";
    } else {
        if($user->adminlogin($email, $password)) {
            if($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'manager' || $_SESSION['user_type'] == 'delivaryman')
            {
            header('location: index.php');
                exit;
            } else {
                $error = "Access denied! You are not an admin.";
                session_destroy();
            }
        } else {
            $error = "Invalid login credentials";
            // Log failed login attempt here
        }
    }
}

// Only show logout message if there's no error and the logout parameter is present
if(empty($error) && isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $show_logout_message = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark:rgb(27, 26, 54);
            --dark-color:rgb(43, 34, 77);
            --light-color: #f8fafc;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
        
        .input-group-text {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        
        .logo {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
        }
        
        .alert {
            text-align: center;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        #togglePassword {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php elseif ($show_logout_message): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                You have been successfully logged out.
            </div>
        <?php endif; ?>
        
        <div class="login-card animate__animated animate__fadeIn">
            <div class="card-header">
                <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                    <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.8L20 9v6l-8 4-8-4V9l8-4.2z"/>
                    <path d="M12 12l-8-4v8l8 4 8-4V8l-8 4z"/>
                </svg>
                <h4 class="mb-0">Admin Portal</h4>
                <p class="mb-0 opacity-75">Secure access for administrators</p>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="btn-login" class="btn btn-primary mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.classList.add('animate__fadeOut');
                setTimeout(() => alert.remove(), 1000);
            });
        }, 5000);
    </script>
</body>
</html>