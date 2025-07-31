<?php
require_once __DIR__ . '/../dbconfig.php';

class AdminProfile {
    private $conn;

	// Constructor
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}
    
    //Prepare query
    public function runQuery($sql)
	{
		$stmt = $this->conn->prepare($sql);
		return $stmt;
	}

    //Admin Login
	public function adminlogin($email, $password)
	{
		try 
		{
			$stmt = $this->conn->prepare("SELECT * FROM users WHERE userEmail = :email OR userName = :email LIMIT 1");
			$stmt->execute([':email'=>$email]);
			$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($stmt->rowcount() == 1) 
			{
				if(password_verify($password, $userRow['userPass'])) 
				{
					if ($userRow['is_active'] == 1 && $userRow['status'] != 'inactive') 
					{
						$_SESSION['userSession'] = $userRow['id'];
						$_SESSION['user_type'] = $userRow['user_type'];

                        //update online status
                        if(in_array($userRow['user_type'],['manager','delivaryman']))
                        {
                            $updateOnline = $this->conn->prepare("UPDATE users SET last_login = NOW(), is_online = 1 WHERE id = :id");
                            $updateOnline -> bindParam(":id", $userRow['id'],PDO::PARAM_INT);
                            $updateOnline -> execute();
                        }
						return true;
					} 
					else 
					{
						echo "Your account is inactive!";
						return false;
					}
				} 
				else 
				{
					echo "Invalid password!";
					return false;
				}
			}

			else
			{
				echo "User not found!";
				return false;
			}
		} 
		catch (PDOException $e) 
		{
			echo "DB Error:".$e->getMessage();
			return false;
		}
	}


	//Admin Logout function
	public function adminLogout()
	{
        if(isset($_SESSION['userSession']))
        {
            $userID = $_SESSION['userSession'];
            $stmt = $this->conn->prepare("UPDATE users SET is_online = 0 WHERE id = :id");
            $stmt ->bindParam(":id", $userID, PDO::PARAM_INT);
            $stmt -> execute();
        }

		session_destroy();
		unset($_SESSION['userSession']);
		unset($_SESSION['user_type']);
		unset($_SESSION['username']);
		return true;
	}

    // Function to redirect to a specified URL
    public function redirect($url)
	{
		header("Location: $url");
		exit;
	}

    // Function to check if a user is logged in
    public function is_logged_in()
	{
		return isset($_SESSION['userSession']);
	}

    /**
     * Get admin profile data
     */
    public function getProfile($adminId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, userName, userEmail, first_name, last_name, phone, 
                       address, city, state, zip_code, country, profile_image, 
                       created_at, updated_at, status, last_login, user_type
                FROM users 
                WHERE id = :id AND user_type = 'admin'
            ");
            $stmt->bindParam(":id", $adminId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : []; // Return empty array if false
        } catch (PDOException $e) {
            error_log("Admin profile error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    /**
     * Update admin profile
     */
    public function updateProfile($adminId, $data) {
        try {
            // Prepare update query
            $query = "UPDATE users SET ";
            $params = [];
            
            // Build dynamic update query based on provided data
            foreach ($data as $field => $value) {
                // Only allow updating specific fields for security
                $allowedFields = [
                    'first_name', 'last_name', 'phone', 'address', 
                    'city', 'state', 'zip_code', 'country', 'profile_image'
                ];
                
                if (in_array($field, $allowedFields)) {
                    $query .= "$field = :$field, ";
                    $params[":$field"] = $value;
                }
            }
            
            // Remove trailing comma and add where clause
            $query = rtrim($query, ', ') . " WHERE id = :id AND user_type = 'admin'";
            $params[':id'] = $adminId;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Admin profile update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change admin password
     */
    public function changePassword($adminId, $currentPassword, $newPassword) {
        try {
            // First verify current password
            $stmt = $this->conn->prepare("
                SELECT userPass FROM users 
                WHERE id = :id AND user_type = 'admin'
            ");
            $stmt->bindParam(":id", $adminId);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin || !password_verify($currentPassword, $admin['userPass'])) {
                return false; // Current password doesn't match
            }
            
            // Update to new password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->conn->prepare("
                UPDATE users 
                SET userPass = :password, password_changed_at = NOW() 
                WHERE id = :id
            ");
            $updateStmt->bindParam(":password", $newHash);
            $updateStmt->bindParam(":id", $adminId);
            $updateStmt->execute();
            
            return $updateStmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Admin password change error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all admins (for admin management)
     */
    public function getAllAdmins() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, userName, userEmail, first_name, last_name, 
                       created_at, status, last_login 
                FROM users 
                WHERE user_type = 'admin'
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all admins error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update admin status (active/inactive)
     */
    public function updateAdminStatus($adminId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET status = :status, is_active = :is_active 
                WHERE id = :id AND user_type = 'admin'
            ");
            
            $isActive = ($status === 'active') ? 1 : 0;
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":is_active", $isActive);
            $stmt->bindParam(":id", $adminId);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Admin status update error: " . $e->getMessage());
            return false;
        }
    }


     // Upload profile image
  
    public function uploadProfileImage($adminId, $file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads/profile_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'admin_' . $adminId . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Update database record
            $relativePath = 'uploads/profile_images/' . $filename;
            return $this->updateProfile($adminId, ['profile_image' => $relativePath]);
        }
        
        return false;
    }
}

// Usage example:
// $adminProfile = new AdminProfile($AUTH_admin);
// $profileData = $adminProfile->getProfile($adminId);
?>