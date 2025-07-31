<?php

session_start();
require_once __DIR__ . '/../config/classes/user.php';
$user = new USER();

if(empty($_GET['id']) || empty($_GET['code']))
{
	header('location: ../index.php');
}

if(isset($_POST['resetpass']))
{
	$id = base64_decode($_GET['id']);
	$code = $_GET['code'];

	$stmt = $user->runQuery("SELECT * FROM users WHERE id = :uid AND tokenCode = :token");
	$stmt->execute(array(':uid'=>$id, ':token'=>$code));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if($stmt->rowCount() == 1)
	{
		$pass = $_POST['password1'];
		$cpass = $_POST['password2'];

		if($cpass != $pass)
		{
			$msg = "<div class='alert alert-danger'>Password does not match!</div>";
		}

		else
		{
			$password = md5($cpass);
			$stmt = $user->runQuery("UPDATE users SET userPass = :uPass, tokenCode= NULL WHERE id=:uid");
			$stmt->execute(array(':uPass'=>$password,':uid'=>$row['id']));

			$msg = "<div class='alert alert-success'>Password Changed. Redirecting to Home...</div>";
			header("refresh:3; url=../index.php");

		}
	}
	else
	{

	 $msg = "<div class='alert alert-danger'>Invalid or Expired Reset Link!</div>";
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Reset Password</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
	<style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        
        .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }
        
        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #3a5ccc;
            transform: translateY(-2px);
        }
        
        .toggle-password {
            cursor: pointer;
        }
        
        .password-strength .progress-bar {
            transition: width 0.3s ease;
        }
        
        #password-match {
            height: 18px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-lg rounded-3 overflow-hidden">
                    <div class="card-header bg-primary text-white py-4 text-center">
                        <h3 class="mb-0 fw-bold"><i class="fas fa-key me-2"></i> Reset Your Password</h3>
                    </div>
                    <div class="card-body p-5">
                        <?php if(isset($msg)): ?>
                            <div class="alert alert-<?= strpos($msg, 'success') !== false ? 'success' : 'danger' ?> rounded-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas <?= strpos($msg, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                                    <div><?= $msg ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" class="mt-3">
                            <div class="mb-4">
                                <label for="password1" class="form-label fw-medium">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password1" id="password1" class="form-control py-2" placeholder="Enter new password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Password strength: <span class="strength-text">Weak</span></small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password2" class="form-label fw-medium">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password2" id="password2" class="form-control py-2" placeholder="Confirm new password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="password-match" class="mt-1 small"></div>
                            </div>

                            <button type="submit" name="resetpass" class="btn btn-primary w-100 py-2 mt-3 fw-medium">
                                <i class="fas fa-sync-alt me-2"></i> Update Password
                            </button>
                        </form>
                    </div>
                    <div class="card-footer bg-transparent text-center py-3">
                        <a href="../index.php" class="text-decoration-none text-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
        
        // Password strength indicator
        document.getElementById('password1').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.querySelector('.password-strength .progress-bar');
            const strengthText = document.querySelector('.password-strength .strength-text');
            
            // Reset
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            
            if (password.length === 0) {
                strengthText.textContent = '';
                return;
            }
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update UI
            const width = (strength / 5) * 100;
            strengthBar.style.width = `${width}%`;
            
            if (strength <= 2) {
                strengthBar.classList.add('bg-danger');
                strengthText.textContent = 'Weak';
            } else if (strength <= 4) {
                strengthBar.classList.add('bg-warning');
                strengthText.textContent = 'Medium';
            } else {
                strengthBar.classList.add('bg-success');
                strengthText.textContent = 'Strong';
            }
        });
        
        // Password match checker
        document.getElementById('password2').addEventListener('input', function() {
            const password1 = document.getElementById('password1').value;
            const password2 = this.value;
            const matchIndicator = document.getElementById('password-match');
            
            if (password2.length === 0) {
                matchIndicator.textContent = '';
                matchIndicator.className = 'mt-1 small';
                return;
            }
            
            if (password1 === password2) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Passwords match';
                matchIndicator.className = 'mt-1 small text-success';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i> Passwords do not match';
                matchIndicator.className = 'mt-1 small text-danger';
            }
        });
    </script>
</body>
</html>