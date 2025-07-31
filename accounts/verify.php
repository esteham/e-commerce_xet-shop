<?php
require_once __DIR__ . '/../config/classes/user.php';
$user = new USER();

if(empty($_GET['id']) && empty($_GET['code']))
{
    header('refresh:5; url=https://eshop.xetroot.com');
    exit;
}

if(isset($_GET['id']) && isset($_GET['code']))
{
	$id = base64_decode($_GET['id']);
	$code = $_GET['code'];

	$status_active = 'active';
	$status_inactive = 'inactive';

	$stmt = $user->runQuery("SELECT id, status FROM users WHERE id = :uID AND tokenCode = :code LIMIT 1");
	$stmt->execute(array(':uID'=>$id, ':code'=>$code));

	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if($stmt->rowCount() > 0)
	{
		if($row['status'] == $status_inactive)
		{
			$stmt = $user->runQuery("UPDATE users SET status = :status WHERE id = :uID");
			$stmt->bindParam(":status", $status_active);
			$stmt->bindParam(":uID", $id);
			$stmt->execute();
			$msg = "<div class='alert alert-info'><b>Your Account Has Been Activated Successfully.</b></div>";
			header('refresh:5; url=https://eshop.xetroot.com');
		}

		else
		{
			$msg = "<div class='alert alert-info'><b>Soory, But Your Account Already Activated.</b></div>";
			header('refresh:5; url=https://eshop.xetroot.com');
		}
	}

	else
	{
		$msg = "<div class='alert alert-warning'><b>Account Not Found! Please correct email or Create a account</b></div>";
	}

}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Verify User</title>

	 <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
	 <style>
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            width: 90%;
            animation: slideIn 0.3s ease-out forwards;
        }

        .notification {
            display: flex;
            align-items: center;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            color: white;
        }

        .notification:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .notification.success:before {
            background-color: #2E7D32;
        }

        .notification.error {
            background-color: #F44336;
        }

        .notification.error:before {
            background-color: #C62828;
        }

        .notification.warning {
            background-color: #FF9800;
        }

        .notification.warning:before {
            background-color: #F57C00;
        }

        .notification.info {
            background-color: #2196F3;
        }

        .notification.info:before {
            background-color: #1565C0;
        }

        .notification-icon {
            margin-right: 12px;
            flex-shrink: 0;
        }

        .notification-content {
            flex-grow: 1;
            font-size: 14px;
            line-height: 1.5;
        }

        .notification-close {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 12px;
            opacity: 0.7;
            transition: opacity 0.2s;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .notification-close:hover {
            opacity: 1;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOut 0.3s ease-in forwards;
        }
    </style>
</head>
<body>
    <?php if(isset($msg)): ?>
        <div class="notification-container">
            <div class="notification <?= strpos($msg, 'success') !== false ? 'success' : 
                                    (strpos($msg, 'error') !== false || strpos($msg, 'danger') !== false ? 'error' : 
                                    (strpos($msg, 'warning') !== false ? 'warning' : 'info')) ?>">
                <div class="notification-icon">
                    <?php if(strpos($msg, 'success') !== false): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    <?php elseif(strpos($msg, 'error') !== false || strpos($msg, 'danger') !== false): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    <?php elseif(strpos($msg, 'warning') !== false): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="notification-content">
                    <?= $msg ?>
                </div>
                <button class="notification-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const closeButtons = document.querySelectorAll('.notification-close');
            
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const notification = this.closest('.notification');
                    notification.classList.add('hide');
                    
                    // Remove the notification after animation completes
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                });
            });

            // Auto-dismiss notifications after 5 seconds
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
