<?php
session_start();

require_once __DIR__ . '/../config/classes/user.php';

$user = new USER();

if($user->is_logged_in() != "") {
    header('location: index.php');
}

if(isset($_POST['resetpass'])) {
    $email = trim($_POST['email']);
    $stmt = $user->runQuery("SELECT id FROM users WHERE userEmail = :email LIMIT 1");
    $stmt->execute(array(':email'=>$email));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($stmt->rowCount() == 1) {
        $id = base64_encode($row['id']);
        $code = md5(uniqid(rand()));

        $stmt = $user->runQuery("UPDATE users SET tokenCode = :token WHERE userEmail = :email");
        $stmt->execute(array(":token"=>$code, ":email"=>$email));

        $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .button { 
                            display: inline-block; 
                            padding: 10px 20px; 
                            background-color:rgb(10, 5, 99); 
                            color: white; 
                            text-decoration: none; 
                            border-radius: 5px; 
                            margin: 15px 0;
                        }
                        .footer { margin-top: 30px; font-size: 0.9em; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>Password Reset Request</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your account password. If you initiated this request, please click the button below to reset your password.</p>
                        <p>If you didn't request a password reset, you can safely ignore this email.</p>
                        
                        <a href='https://eshop.xetroot.com/accounts/resetpass.php?id=$id&code=$code' class='button'>Reset Password</a>
                        
                        <p>This link will expire in 24 hours for security reasons.</p>
                        
                        <div class='footer'>
                            <p>Thanks,<br>Your Website Team</p>
                        </div>
                    </div>
                </body>
                </html>";

        $subject = "Password Reset Request";
        $user->sendMail($email, $message, $subject);
        header('refresh:5; url=../index.php');
        $msg = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> We've sent an email to $email with instructions to reset your password. Please check your inbox.</div>";
    } else {
        $msg = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Sorry, we couldn't find an account with that email address.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Your Website</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #f9fafb;
            --text-color: #1f2937;
            --light-gray: #f3f4f6;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-color);
        }
        
        .auth-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .auth-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .auth-body {
            padding: 2rem;
            background-color: white;
        }
        
        .auth-footer {
            background-color: var(--secondary-color);
            padding: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .auth-logo {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6b7280;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .divider::before {
            margin-right: 1rem;
        }
        
        .divider::after {
            margin-left: 1rem;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
        }
        
        .alert i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="mb-0"><i class="fas fa-key me-2"></i> Reset Password</h2>
                    </div>
                    <div class="auth-body">
                        <?php if(isset($msg)) { 
                            echo $msg;
                        } else { ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Forgot your password?</strong> Enter your email address and we'll send you a link to reset it.
                            </div>
                        <?php } ?>
                        
                        <form method="post" action="" class="mt-4">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" required>
                                </div>
                                <small class="text-muted">Enter the email address associated with your account.</small>
                            </div>
                            
                            <button type="submit" name="resetpass" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                            </button>
                            
                            <div class="text-center mt-3">
                                <a href="../index.php" class="text-decoration-none text-primary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="auth-footer">
                        Remember your password? <a href="../index.php" class="text-primary text-decoration-none">Sign in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>