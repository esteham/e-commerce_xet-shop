<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';

$AUTH_user = new USER();
if (!isset($_SESSION['userSession'])) {    
    header('Location: index.php');
    exit;
}

$id = $_SESSION['userSession'];

$error = '';
$success = '';

// Fetch user data
try {  
    // Get main user info
    $stmt = $AUTH_user->runQuery("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "User not found!";
    }
    
    // Get additional info
    $stmt = $AUTH_user->runQuery("SELECT `key`, `value` FROM user_additional_info WHERE id = ?");
    $stmt->execute([$id]);
    $additional_info = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    
    try {
        $stmt = $AUTH_user->runQuery("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, state=?, zip_code=?, country=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $phone, $address, $city, $state, $zip_code, $country, $id]);
        
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $AUTH_user->runQuery("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Handle profile image upload separately
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
    try {
        $target_dir = "uploads/profile_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        } else {
            $new_filename = "user_" . $id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES['profile_image']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Delete old image if not default
                    if ($user['profile_image'] != 'default.jpg' && file_exists($target_dir . $user['profile_image'])) {
                        unlink($target_dir . $user['profile_image']);
                    }
                    
                    // Update database
                    $stmt = $AUTH_user->runQuery("UPDATE users SET profile_image=? WHERE id=?");
                    $stmt->execute([$new_filename, $id]);
                    $user['profile_image'] = $new_filename;
                    
                    $success = "Profile image updated successfully!";
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error = "File is not an image.";
            }
        }
    } catch(PDOException $e) {
        $error = "Error updating profile image: " . $e->getMessage();
    }
}

// Handle additional info update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_additional_info'])) {
    $new_key = trim($_POST['new_key']);
    $new_value = trim($_POST['new_value']);
    
    if (!empty($new_key)) {
        try {
            // Check if key already exists
            $stmt = $AUTH_user->runQuery("SELECT id FROM user_additional_info WHERE id = ? AND `key` = ?");
            $stmt->execute([$id, $new_key]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing
                $stmt = $AUTH_user->runQuery("UPDATE user_additional_info SET `value` = ? WHERE id = ? AND `key` = ?");
                $stmt->execute([$new_value, $id, $new_key]);
            } else {
                // Insert new
                $stmt = $AUTH_user->runQuery("INSERT INTO user_additional_info (id, `key`, `value`) VALUES (?, ?, ?)");
                $stmt->execute([$id, $new_key, $new_value]);
            }
            
            $success = "Additional information updated successfully!";
            
            // Refresh additional info
            $stmt = $AUTH_user->runQuery("SELECT `key`, `value` FROM user_additional_info WHERE id = ?");
            $stmt->execute([$id]);
            $additional_info = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
        } catch(PDOException $e) {
            $error = "Error updating additional information: " . $e->getMessage();
        }
    }
}

// Handle delete additional info
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_info'])) {
    $key_to_delete = $_POST['key_to_delete'];
    
    try {
        $stmt = $AUTH_user->runQuery("DELETE FROM user_additional_info WHERE id = ? AND `key` = ?");
        $stmt->execute([$id, $key_to_delete]);
        
        $success = "Information deleted successfully!";
        
        // Refresh additional info
        $stmt = $AUTH_user->runQuery("SELECT `key`, `value` FROM user_additional_info WHERE id = ?");
        $stmt->execute([$id]);
        $additional_info = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
    } catch(PDOException $e) {
        $error = "Error deleting information: " . $e->getMessage();
    }
}

//Change Password
if (isset($_POST['change_password'])) {
    if ($AUTH_user->changePassword(
        $id, 
        $_POST['current_password'], 
        $_POST['new_password'],
        $_POST['confirm_password']
    )) {
        $success ='Password changed successfully!';
    } else {
        $error = 'Current password is incorrect or update failed!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/user_profile.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container profile-container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="card-body text-center pt-5">
                        <div class="profile-image-container">
                            <img src="uploads/profile_images/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                 alt="Profile Image" class="profile-image"
                                 onerror="this.src='uploads/profile_images/default.jpg'">
                            <button class="edit-image-btn" onclick="document.getElementById('profile_image').click()">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <h4 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="user-username">@<?php echo htmlspecialchars($user['userName']); ?></p>
                        
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#profile" data-bs-toggle="pill">
                                    <i class="fas fa-user"></i> Profile Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#additional" data-bs-toggle="pill">
                                    <i class="fas fa-info-circle"></i> Additional Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#security" data-bs-toggle="pill">
                                    <i class="fas fa-shield-alt"></i> Security
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#danger" data-bs-toggle="pill">
                                    <i class="fas fa-exclamation-triangle"></i> Danger Zone
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile">
                        <div class="profile-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Profile Information</h5>
                                <button type="button" class="btn btn-primary" id="editProfileBtn">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="profileForm">
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="info-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" 
                                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="info-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" 
                                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="info-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['userEmail']); ?>" disabled>
                                        <small class="text-muted">Contact admin to change email</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="info-label">Phone</label>
                                        <input type="text" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="info-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2" readonly><?php echo htmlspecialchars($user['address']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">City</label>
                                                <input type="text" class="form-control" name="city" 
                                                       value="<?php echo htmlspecialchars($user['city']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">State/Province</label>
                                                <input type="text" class="form-control" name="state" 
                                                       value="<?php echo htmlspecialchars($user['state']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="info-label">Zip/Postal Code</label>
                                                <input type="text" class="form-control" name="zip_code" 
                                                       value="<?php echo htmlspecialchars($user['zip_code']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="info-label">Country</label>
                                        <input type="text" class="form-control" name="country" 
                                               value="<?php echo htmlspecialchars($user['country']); ?>" readonly>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4 edit-submit-btns" style="display: none !important;">
                                        <button type="submit" name="update_profile" class="btn btn-success me-2">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                        <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="additional">
                        <div class="profile-card">
                            <div class="card-header">
                                <h5 class="mb-0">Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($additional_info)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No additional information added yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table additional-info-table">
                                            <thead>
                                                <tr>
                                                    <th>Key</th>
                                                    <th>Value</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($additional_info as $key => $value): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($key); ?></td>
                                                        <td><?php echo htmlspecialchars($value); ?></td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="key_to_delete" value="<?php echo htmlspecialchars($key); ?>">
                                                                <button type="submit" name="delete_info" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3">Add New Information</h5>
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <label class="info-label">Key</label>
                                                <input type="text" class="form-control" name="new_key" required>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <label class="info-label">Value</label>
                                                <input type="text" class="form-control" name="new_value">
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="update_additional_info" class="btn btn-primary w-100">
                                                <i class="fas fa-plus me-1"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="security">
                        <div class="profile-card">
                            <div class="card-header">
                                <h5 class="mb-0">Security Settings</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-4">
                                        <h6 class="mb-3">Change Password</h6>
                                        <div class="mb-3">
                                            <label for="current_password" class="info-label">Current Password</label>
                                            <input name="current_password" type="password" class="form-control" placeholder="Enter current password">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="info-label">New Password</label>
                                            <input name="new_password" type="password" class="form-control" placeholder="Enter new password">
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="info-label">Confirm New Password</label>
                                            <input name="confirm_password" type="password" class="form-control" placeholder="Confirm new password">
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>Update Password
                                        </button>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <h6 class="mb-3">Two-Factor Authentication</h6>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="2faToggle">
                                            <label class="form-check-label" for="2faToggle">Enable 2FA</label>
                                        </div>
                                        <small class="text-muted">Add an extra layer of security to your account</small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="danger">
                        <div class="profile-card danger-zone">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Danger Zone</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6 class="mb-3 text-danger">Delete Account</h6>
                                    <p class="text-muted">Once you delete your account, there is no going back. Please be certain.</p>
                                    
                                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!');">
                                        <div class="mb-3">
                                            <label class="info-label">Type "DELETE" to confirm</label>
                                            <input type="text" class="form-control" name="confirm_delete" required>
                                        </div>
                                        <button type="submit" name="delete_account" class="btn btn-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Delete My Account
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const editBtn = document.getElementById('editProfileBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');
            const submitBtns = document.querySelector('.edit-submit-btns');
            const inputs = form.querySelectorAll('input, textarea, select');
            
            // Initialize form in view mode
            inputs.forEach(input => {
                if (input.name !== 'update_profile' && input.name !== 'profile_image') {
                    input.readOnly = true;
                }
            });
            
            // Edit button click handler
            editBtn.addEventListener('click', function() {
                // Toggle between edit and view mode
                if (editBtn.innerHTML.includes('Edit Profile')) {
                    // Enable all inputs
                    inputs.forEach(input => {
                        if (input.name !== 'update_profile' && input.name !== 'profile_image') {
                            input.readOnly = false;
                        }
                    });
                    
                    // Change button to View Mode
                    editBtn.innerHTML = '<i class="fas fa-eye me-2"></i>View Mode';
                    editBtn.classList.remove('btn-primary');
                    editBtn.classList.add('btn-success');
                    
                    // Show save/cancel buttons
                    submitBtns.style.display = 'flex';
                } else {
                    // Disable all inputs
                    inputs.forEach(input => {
                        if (input.name !== 'update_profile' && input.name !== 'profile_image') {
                            input.readOnly = true;
                        }
                    });
                    
                    // Change button back to Edit Profile
                    editBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Profile';
                    editBtn.classList.remove('btn-success');
                    editBtn.classList.add('btn-primary');
                    
                    // Hide save/cancel buttons
                    submitBtns.style.display = 'none';
                }
            });
            
            // Cancel button click handler
            cancelBtn.addEventListener('click', function() {
                // Disable all inputs
                inputs.forEach(input => {
                    if (input.name !== 'update_profile' && input.name !== 'profile_image') {
                        input.readOnly = true;
                    }
                });
                
                // Change button back to Edit Profile
                editBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Profile';
                editBtn.classList.remove('btn-success');
                editBtn.classList.add('btn-primary');
                
                // Hide save/cancel buttons
                submitBtns.style.display = 'none';
                
                // Reset form to original values
                window.location.reload();
            });
            
            // Profile image upload handling
            document.getElementById('profile_image').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('profile_image', file);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Error uploading image');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error uploading image');
                    });
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>