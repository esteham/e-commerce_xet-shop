<?php
// Include the database configuration file
require_once __DIR__ . '/../../config/dbconfig.php'; 

class USER
{
	private $conn;

	// Constructor
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}

	
	// Function to run a query
	public function runQuery($sql)
	{
		$stmt = $this->conn->prepare($sql);
		return $stmt;
	}


	// Function to get the last inserted ID
	public function lastID()
	{
		return $this->conn->lastInsertId();
	}
	
	// Function to generate a global order ID
	public function generateGlobalOrderId()
	{
		return 'GID'. strtoupper(uniqid());
	}


	// Function to register a new user
	public function register($uname, $email, $upass, $code)
	{
		try {
			$password = password_hash($upass, PASSWORD_DEFAULT);
			$stmt = $this->conn->prepare("INSERT INTO users(userName, userEmail, userPass, tokenCode) VALUES(:user_name, :user_mail, :user_pass, :active_code)");
			$stmt->bindParam(':user_name', $uname);
			$stmt->bindParam(':user_mail', $email);
			$stmt->bindParam(':user_pass', $password);
			$stmt->bindParam(':active_code', $code);
			$stmt->execute();
			return $stmt;
		} catch (PDOException $ex) {
			echo $ex->getMessage();
		}
	}


	// Function to log in a user
	public function login($email, $upass)
	{
		try {
			$stmt = $this->conn->prepare("SELECT * FROM users WHERE userEmail = :email_id");
			$stmt->execute(['email_id' => $email]);
			$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($stmt->rowCount() == 1) 
			{
				if(password_verify($upass, $userRow['userPass'])) 
				{
					if ($userRow['is_active'] == 1 && $userRow['status'] != 'inactive') 
					{
						$_SESSION['userSession'] = $userRow['id'];
						$_SESSION['id'] = $userRow['id'];
						$_SESSION['user_type'] = $userRow['user_type'];

						// Update last login time
						$updateStmt = $this->conn->prepare("UPDATE users SET last_login = NOW(), is_online = 1 WHERE id = :id");
						$updateStmt->bindParam(':id', $userRow['id'], PDO::PARAM_INT);
						$updateStmt->execute();
						
						return "success";
					} 
					else 
					{
						// Account inactive
                        return "inactive";
					}
				} 
				else 
				{
					// Invalid password
                    return "wrongpass";
				}
			} else {
				// Email not found
                return "notfound";
			}
		} catch (PDOException $ex) {
			return "dberror";
		}
	}

	//Change Password Function
	public function changePassword($userID, $currentPassword, $newPassword, $confirmPassword) {
		try {
			// Validate inputs
			if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
				return false;
			}
			
			if ($newPassword !== $confirmPassword) {
				return false; // Passwords don't match
			}
			
			// Verify current password
			$stmt = $this->conn->prepare("
				SELECT userPass FROM users 
				WHERE id = :id
			");
			$stmt->bindParam(":id", $userID);
			$stmt->execute();
			
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$user || !password_verify($currentPassword, $user['userPass'])) {
				return false; // Current password doesn't match
			}
			
			// Check if new password is different
			if (password_verify($newPassword, $user['userPass'])) {
				return false; // New password same as current
			}
			
			// Optional: Add password strength requirements
			if (strlen($newPassword) < 8) {
				return false;
			}
			
			// Update to new password
			$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
			$updateStmt = $this->conn->prepare("
				UPDATE users 
				SET userPass = :password, password_changed_at = NOW() 
				WHERE id = :id
			");
			$updateStmt->bindParam(":password", $newHash);
			$updateStmt->bindParam(":id", $userID);
			$updateStmt->execute();
			
			return $updateStmt->rowCount() > 0;
		} catch (PDOException $e) {
			error_log("Password change error for user {$userID}: " . $e->getMessage());
			return false;
		}
	}

	// Function to check if a user is logged in
	public function is_logged_in()
	{
		return isset($_SESSION['userSession']);
	}

	// Function to redirect to a specified URL
	public function redirect($url)
	{
		header("Location: $url");
		exit;
	}

	// Function to log out a user
	public function logout()
	{
		if(isset($_SESSION['userSession']))
		{
			$userId = $_SESSION['userSession'];
			$stmt = $this->conn->prepare("UPDATE users SET is_online = 0 WHERE id = :id");
			$stmt->bindParam(":id", $userId, PDO::PARAM_INT);
			$stmt->execute();
		}
		session_destroy();
		$_SESSION['userSession'] = false;
	}

	// Function to send an email
	public function sendMail($email, $message, $subject)
	{
		require_once __DIR__ . '/../mailer/PHPMailer.php';
		require_once __DIR__ . '/../mailer/SMTP.php';

		$mail = new PHPMailer\PHPMailer\PHPMailer();
		$mail->isSMTP();
		$mail->SMTPDebug = 0; // use 2 for debug
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = 'deepseekspider@gmail.com';
		$mail->Password = 'rjva iybi zhra jodd'; // App password
		$mail->SMTPSecure = 'tls';
		$mail->Port = 587;

		$mail->setFrom('deepseekspider@gmail.com', 'SpiDer Monkey');
		$mail->addAddress($email);
		$mail->isHTML(true);
		$mail->CharSet = 'UTF-8';
		$mail->AltBody = strip_tags($message);
		$mail->Subject = $subject;
		$mail->Body = $message;

		if (!$mail->send()) {
			$_SESSION['mailError'] = $mail->ErrorInfo;
			return false;
		} else {
			return true;
		}
	}

	//Categories
	// Function to fetch categories from the database
	public function getCategories()
	{
		try {
			$stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY category_name ASC");
			$stmt->execute();
			$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $categories;
		} catch (PDOException $e) {
			echo "Error fetching categories: " . $e->getMessage();
			return [];
		}
	}
	public function getCategoriesWithSubcategories() {
		$stmt = $this->conn->prepare("
			SELECT c.id, c.category_name, 
				   sc.id as subcat_id, sc.sub_cat_name
			FROM categories c
			LEFT JOIN sub_categories sc ON c.id = sc.category_id
			ORDER BY c.category_name, sc.sub_cat_name
		");
		$stmt->execute();
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// Organize into hierarchical structure
		$categories = [];
		foreach ($results as $row) {
			$catId = $row['id'];
			
			if (!isset($categories[$catId])) {
				$categories[$catId] = [
					'id' => $catId,
					'category_name' => $row['category_name'],
					'sub_categories' => []
				];
			}
			
			if ($row['subcat_id']) {
				$categories[$catId]['sub_categories'][] = [
					'id' => $row['subcat_id'],
					'sub_ca_name' => $row['sub_cat_name']
				];
			}
		}
		return array_values($categories);
	}

	//Function to begin a transaction
	public function beginTransaction() {
		return $this->conn->beginTransaction();
	}

	// Function to commit a transaction
	public function commit() {
		return $this->conn->commit();
	}

	// Function to roll back a transaction
	public function rollBack() {
		return $this->conn->rollBack();
	}

	//Order Count function for admin
	public function countNewOrders() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

	public function getConnection() {
		return $this->conn;
	}
	

	//User Active function for admin
	public function toggleBlockUser($userId)
	{
		try {
			$stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id");
			$stmt->execute([':id'=>$userId]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
		
			if($user) {
				$newStatus = ($user['status'] == 'active') ? 'inactive' : 'active';
				$update = $this->conn->prepare("UPDATE users SET status = :status WHERE id = :id");
				$update->execute([':status'=>$newStatus, ':id'=>$userId]);
		
				// Prepare email content
				$subject = "Your Account Status Has Been Updated";
				
				$emailTemplate = '
				<!DOCTYPE html>
				<html>
				<head>
					<style>
						body { font-family: "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
						.header { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
						.content { padding: 20px 0; }
						.status-update { background-color: ' . ($newStatus == 'inactive' ? '#f8d7da' : '#d4edda') . '; 
										color: ' . ($newStatus == 'inactive' ? '#721c24' : '#155724') . ';
										padding: 15px; border-radius: 5px; margin: 15px 0; }
						.button { display: inline-block; padding: 10px 20px; background-color: #3498db; 
								  color: black; text-decoration: none; border-radius: 5px; margin-top: 15px; }
						.footer { margin-top: 30px; font-size: 12px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 10px; }
					</style>
				</head>
				<body>
					<div class="header">
						<h2>Account Status Notification</h2>
					</div>
					
					<div class="content">
						<p>Hello ' . htmlspecialchars($user['userName']) . ',</p>
						
						<div class="status-update">
							<p>Your account status has been updated to: <strong>' . ucfirst($newStatus) . '</strong></p>
						</div>
						
						<p>' . 
							($newStatus == 'inactive' 
								? "Your account access has been restricted. If you believe this is an error, please contact our support team immediately." 
								: "Your account has been restored and you can now access all features.") . 
						'</p>';
						
				if($newStatus == 'inactive') 
				{
					$emailTemplate .= '<p><a href="contact.php" class="button text-white">Contact Support</a></p>';
				} 
				
				else 
				{
					$emailTemplate .= '<p><a href="index.com" class="button text-white">Login to Your Account</a></p>';
				}
						
				$emailTemplate .= '
						<p>Best regards,<br>The Support Team</p>
					</div>
					
					<div class="footer">
						<p>© ' . date('Y') . ' SpiDer MonKey All rights reserved.</p>
						<p>If you did not request this change, please contact us immediately.</p>
					</div>
				</body>
				</html>';
		
				$this->sendMail($user['userEmail'], $emailTemplate, $subject);
			}
		}
		catch (PDOException $e) 
		{
			echo $e->getMessage();
		}
	}
	
	/**
     * Get new order count for admin dashboard
     */
    public function getNewOrderCount() {
		try {
			$stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM orders WHERE is_seen = 0");
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result['count'] ?? 0;
		} catch (PDOException $e) {
			error_log("Get new order count error: " . $e->getMessage());
			return 0;
		}
	}

	public function markOrdersAsSeen() {
		try {
			$stmt = $this->conn->prepare("UPDATE orders SET is_seen = 1 WHERE is_seen = 0 ");
			$stmt->execute();
			return $stmt->rowCount(); // Return number of rows updated
		} catch (PDOException $e) {
			error_log("Error marking orders as seen: " . $e->getMessage());
			return 0; // Return 0 on error
		}
	}
}
?>
