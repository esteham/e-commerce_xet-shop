<?php

if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/adminProfile.php';
require_once __DIR__ . '/../../config/classes/user.php';
$user = new USER();
$admin = new AdminProfile();

// Check session and get user data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminId = $_SESSION['userSession'];
if($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'manager' || $_SESSION['user_type'] == 'delivaryman') 
// Get profile data
$profileData = $admin->getProfile($adminId);


if (isset($_SESSION['userSession'])) {
    $stmt = $user->runQuery("SELECT * FROM users WHERE id=:uid");
    $stmt->execute(array(":uid" => $_SESSION['userSession']));
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $user->redirect('login.php');
    exit;
}

// Handle form submission for profile update
if (isset($_POST['btn-update'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zipCode = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    
    try {
        $stmt = $user->runQuery("UPDATE users SET 
            first_name=:first_name, 
            last_name=:last_name, 
            phone=:phone, 
            address=:address, 
            city=:city, 
            state=:state, 
            zip_code=:zip_code, 
            country=:country 
            WHERE id=:uid");
            
        $stmt->execute(array(
            ":first_name" => $firstName,
            ":last_name" => $lastName,
            ":phone" => $phone,
            ":address" => $address,
            ":city" => $city,
            ":state" => $state,
            ":zip_code" => $zipCode,
            ":country" => $country,
            ":uid" => $_SESSION['userSession']
        ));
        
        $_SESSION['success_message'] = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $user->runQuery("SELECT * FROM users WHERE id=:uid");
        $stmt->execute(array(":uid" => $_SESSION['userSession']));
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<script>window.location.href='index.php?page=admin_profile';</script>";
        exit;
    } catch (PDOException $ex) {
        $_SESSION['error_message'] = 'Error updating profile: '.$ex->getMessage();
        echo "<script>window.location.href='index.php?page=admin_profile';</script>";
        exit;
    }
}

// Handle profile picture upload
if (isset($_POST['upload_image'])) {
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = 'Invalid file type. Only JPG, JPEG, PNG & GIF are allowed.';
        echo "<script>window.location.href='index.php?page=admin_profile';</script>";
        exit;
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '.' . $ext;
    $uploadPath = __DIR__ . '/../../uploads//profile_images/' . $newFilename;
    
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
        // Delete old profile image if it's not the default
        if ($userData['profile_image'] != 'default.jpg') {
            $oldImagePath = __DIR__ . '/../../uploads//profile_images/' . $userData['profile_image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        
        // Update database
        $stmt = $user->runQuery("UPDATE users SET profile_image=:profile_image WHERE id=:uid");
        $stmt->execute(array(
            ":profile_image" => $newFilename,
            ":uid" => $_SESSION['userSession']
        ));
        
        $_SESSION['success_message'] = 'Profile picture updated successfully!';
        
        // Refresh user data
        $userData['profile_image'] = $newFilename;
        
        echo "<script>window.location.href='index.php?page=admin_profile';</script>";
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to upload file.';
        echo "<script>window.location.href='index.php?page=admin_profile';</script>";
        exit;
    }
}

if (isset($_POST['change_password'])) {
    // Change password
    if ($admin->changePassword(
        $adminId, 
        $_POST['current_password'], 
        $_POST['new_password']
    )) {
        $_SESSION['success_message'] = 'Password changed successfully!';
    } else {
        $_SESSION['error_message'] = 'Current password is incorrect or update failed!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .profile-container {
            max-width: 880px;
            margin: 10px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #f8f9fa;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-img-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .edit-section {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="container profile-container">
    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-primary" onclick="toggleEditMode(true)">Edit Profile</button>
    </div>
        <div class="profile-header d-flex justify-content-around align-items-center">
            <!-- Profile Image -->
            <div class="profile-img-container" style="float: left; margin-right: 20px;">
                <img src="../uploads/profile_images/<?php echo $userData['profile_image'] ?>"  
                     alt="Profile Image" class="profile-img" id="profileImage">
            </div>
            <div>
                <h1 class="mt-5 p-3"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h1>
                <p class="text-muted">@<?php echo htmlspecialchars($userData['userName']); ?></p>
            </div>
        </div>
        <!-- Profile Image Upload Form -->
        <div class="col-md-2" style="margin-left: 100px; ">
            <form method="post" enctype="multipart/form-data">
                <div class="input-group mb-3">
                    <input type="file" class="form-control" name="profile_image" accept="image/*" >
                    <button class="btn btn-primary" type="submit" name="upload_image">Upload</button>
                </div>
            </form>
        </div>
        
        <!-- View Mode -->
        <div id="viewMode">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['first_name']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['last_name']); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['userEmail']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['phone']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea class="form-control" rows="2" readonly><?php 
                    echo htmlspecialchars($userData['address']); 
                ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['city']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['state']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['zip_code']); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Country</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['country']); ?>" readonly>
            </div>
            
            <!-- Button trigger modal -->
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                 Change Password
                </button>        
            </div>
        </div>
        
        <!-- Edit Mode -->
        <div id="editMode" class="edit-section">
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($userData['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" required><?php 
                        echo htmlspecialchars($userData['address']); 
                    ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($userData['city']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($userData['state']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="zip_code">Zip Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($userData['zip_code']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" class="form-control" id="country" name="country" 
                           value="<?php echo htmlspecialchars($userData['country']); ?>" required>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleEditMode(false)">Cancel</button>
                    <button type="submit" class="btn btn-success" name="btn-update">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between view and edit modes
        function toggleEditMode(showEdit) {
            document.getElementById('viewMode').style.display = showEdit ? 'none' : 'block';
            document.getElementById('editMode').style.display = showEdit ? 'block' : 'none';
        }
        
        // Auto-close alerts after 5 seconds
        window.onload = function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        };
    </script>
</body>
</html>