<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
$user = new USER();

if(!isset($_SESSION['userSession'])) {
    header('location: login.php');
    exit;
}

// Initialize variables to prevent undefined variable warnings
$error = $success = $selectedZone = $selectedArea = '';

// Handle Form Submission for Add/Edit User
if(isset($_POST['add_user']) || isset($_POST['edit_user'])) {
    $userName = trim($_POST['username']);
    $userEmail = trim($_POST['email']);
    $userType = $_POST['user_type'];
    $contact = $_POST['contact'] ?? '';
    $zone = $_POST['zone'] ?? '';
    $area = $_POST['area'] ?? '';

    $userPass = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    $isEdit = isset($_POST['edit_user']);
    $userId = $isEdit ? intval($_POST['user_id']) : 0;

    try {
        $check = $user->runQuery("SELECT id FROM users WHERE userEmail = ? AND id != ?");
        $check->execute([$userEmail, $userId]);

        if($check->rowCount() > 0) {
            $error = "Email already exists";
        } else {
            if($isEdit) {
                if(!empty($userPass)) {
                    $hashed_password = password_hash($userPass, PASSWORD_DEFAULT);
                    $stmt = $user->runQuery("UPDATE users SET userName=?, userEmail=?, userPass=?, user_type=?, phone=? WHERE id=?");
                    $stmt->execute([$userName, $userEmail, $hashed_password, $userType, $contact, $userId]);
                } else {
                    $stmt = $user->runQuery("UPDATE users SET userName=?, userEmail=?, user_type=?, phone=? WHERE id=?");
                    $stmt->execute([$userName, $userEmail, $userType, $contact, $userId]);
                }

                // zone & area update
                $zstmt = $user->runQuery("SELECT id FROM user_zone_area WHERE user_id = ?");
                $zstmt->execute([$userId]);
                if($zstmt->rowCount() > 0){
                    $stmt2 = $user->runQuery("UPDATE user_zone_area SET zone=?, area=? WHERE user_id=?");
                    $stmt2->execute([$zone, $area, $userId]);
                } else {
                    $stmt2 = $user->runQuery("INSERT INTO user_zone_area (user_id, zone, area, assigned_at) VALUES (?, ?, ?, NOW())");
                    $stmt2->execute([$userId, $zone, $area]);
                }

                $success = "User updated successfully";
            } else {
                $hashed_password = password_hash($userPass, PASSWORD_DEFAULT);
                $stmt = $user->runQuery("INSERT INTO users (userName, userEmail, userPass, phone, user_type, status, is_active, tokenCode) 
                                        VALUES (?, ?, ?, ?, ?, 'active', 1, ?)");
                $token = md5(uniqid(rand(), true));
                $stmt->execute([$userName, $userEmail, $hashed_password, $contact, $userType, $token]);

                $newUserId = $user->lastID();

                // zone & area insert
                if(!empty($zone)) {
                    $stmt2 = $user->runQuery("INSERT INTO user_zone_area (user_id, zone, area) VALUES (?, ?, ?)");
                    $stmt2->execute([$newUserId, $zone, $area]);
                }

                // send email
                $subject = "Your login credentials";
                $message = "
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #4e73df; color: white; padding: 15px; text-align: center; }
                            .content { padding: 20px; background-color: #f8f9fc; }
                            .credentials { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                            .footer { text-align: center; padding: 10px; font-size: 12px; color: #6c757d; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Your Account Has Been Created</h2>
                            </div>
                            <div class='content'>
                                <p>Hello $userName,</p>
                                <p>Your account has been successfully created. Here are your login details:</p>
                                
                                <div class='credentials'>
                                    <p><strong>Email:</strong> $userEmail</p>
                                    <p><strong>Temporary Password:</strong> $userPass</p>
                                </div>
                                
                                <p>Please login and change your password immediately for security reasons.</p>
                                <p>If you didn't request this account, please contact our support team.</p>
                            </div>
                            <div class='footer'>
                                &copy;SpiDer Monkey - All rights reserved
                            </div>
                        </div>
                    </body>";

                $user->sendMail($userEmail, $message, $subject);
                
                $success = "New user added and credentials sent";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: ".$e->getMessage();
    }
}

// Handle Edit User - Fetch user data if ID is provided
$editUser = null;
if(isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $stmt = $user->runQuery("SELECT users.*, user_zone_area.zone, user_zone_area.area 
                            FROM users 
                            LEFT JOIN user_zone_area ON users.id = user_zone_area.user_id 
                            WHERE users.id = ?");
    $stmt->execute([$userId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($editUser) {
        $selectedZone = $editUser['zone'] ?? '';
        $selectedArea = $editUser['area'] ?? '';
    }
}

$currentAdminId = $_SESSION['userSession'];

// Block/unblock action
if(isset($_GET['toggle_block'])) {
    $userId = intval($_GET['toggle_block']);
    if($userId !== $currentAdminId) {
        $user->toggleBlockUser($userId);
        echo "<script>window.location.href='index.php?page=add_users';</script>";
        exit;
    }
}

// Zone insert
if (isset($_POST['add_zone'])) {
    $zone_name = $_POST['zone_name'];
    $stmt = $user->runQuery("INSERT INTO zones (zone_name) VALUES (?)");
    $stmt->execute([$zone_name]);
    $success = "Zone Added Successfully";
}

// Area insert
if (isset($_POST['add_area'])) {
    $area_name = $_POST['area_name'];
    $zone_id = $_POST['zone_id'];
    $stmt = $user->runQuery("INSERT INTO areas (area_name, zone_id) VALUES (?, ?)");
    $stmt->execute([$area_name, $zone_id]);
    $success = "Area Added Successfully";
}

// Fetch all users except current logged in user
$stmt = $user->runQuery("SELECT * FROM users WHERE id != :id ORDER BY id DESC");
$stmt->execute([':id'=>$currentAdminId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .badge-admin {
            background-color: #6f42c1;
        }
        .badge-manager {
            background-color: #fd7e14;
        }
        .badge-deliveryman {
            background-color: #20c997;
        }
        .action-btns .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        @media (max-width: 768px) {
            .form-row .form-group {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Add User Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
            <i class="fas fa-user-plus me-1"></i> Add New User
        </button>

        <!-- Add Zone/Area Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#zoneAreaModal">
            <i class="fas fa-map-marker-alt me-1"></i> Add Zone/Area
        </button>

        <!-- User Modal -->
        <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="userModalLabel"><?= isset($editUser) ? 'Edit User' : 'Add New User' ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="">
                            <?php if(isset($editUser)): ?>
                                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" 
                                        value="<?= isset($editUser) ? htmlspecialchars($editUser['userName']) : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                        value="<?= isset($editUser) ? htmlspecialchars($editUser['userEmail']) : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_type" class="form-label">Role</label>
                                    <select name="user_type" id="user_type" class="form-select" required>
                                        <option value="">Select Role</option>
                                        <option value="admin" <?= (isset($editUser) && $editUser['user_type'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="manager" <?= (isset($editUser) && $editUser['user_type'] == 'manager') ? 'selected' : '' ?>>Manager</option>
                                        <option value="delivaryman" <?= (isset($editUser) && $editUser['user_type'] == 'delivaryman') ? 'selected' : '' ?>>Delivery Manager</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label"><?= isset($editUser) ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                                    <input type="password" name="password" class="form-control" <?= !isset($editUser) ? 'required' : '' ?>>
                                </div>

                                <div class="col-12" style="<?= (isset($editUser) && in_array($editUser['user_type'], ['manager','delivaryman'])) ? 'display: block;' : 'display: none;' ?>" id="extraFields">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="contact" class="form-label">Contact</label>
                                            <input class="form-control" type="text" name="contact" id="contact" 
                                                value="<?= isset($editUser) ? htmlspecialchars($editUser['phone']) : '' ?>" 
                                                placeholder="Contact Number">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="zone" class="form-label">Zone</label>
                                            <select class="form-control" name="zone" id="zone">
                                                <option value="">Select Zone</option>
                                                <?php
                                                $zones = $user->runQuery("SELECT * FROM zones");
                                                $zones->execute();
                                                while($zone = $zones->fetch(PDO::FETCH_ASSOC)) {
                                                    $selected = (isset($editUser) && $editUser['zone'] == $zone['id']) ? 'selected' : '';
                                                    echo "<option value='{$zone['id']}' $selected>{$zone['zone_name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="area" class="form-label">Area</label>
                                            <select class="form-control" name="area" id="area">
                                                <option value="">Select Area</option>
                                                <?php
                                                if(isset($editUser) && !empty($editUser['zone'])) {
                                                    $areas = $user->runQuery("SELECT * FROM areas WHERE zone_id = ?");
                                                    $areas->execute([$editUser['zone']]);
                                                    while($area = $areas->fetch(PDO::FETCH_ASSOC)) {
                                                        $selected = ($editUser['area'] == $area['id']) ? 'selected' : '';
                                                        echo "<option value='{$area['id']}' $selected>{$area['area_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <?php if(isset($editUser)): ?>
                                        <button type="submit" name="edit_user" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Update
                                        </button>
                                        <a href="index.php?page=add_users" class="btn btn-secondary ms-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php else: ?>
                                        <button type="submit" name="add_user" class="btn btn-success">
                                            <i class="fas fa-plus me-1"></i> Add User
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone/Area Modal -->
        <div class="modal fade" id="zoneAreaModal" tabindex="-1" aria-labelledby="zoneAreaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="zoneAreaModalLabel">Manage Zones & Areas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="zoneAreaTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="zone-tab" data-bs-toggle="tab" data-bs-target="#zoneTab" type="button" role="tab">Add Zone</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="area-tab" data-bs-toggle="tab" data-bs-target="#areaTab" type="button" role="tab">Add Area</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="zoneAreaTabContent">
                            <!-- Zone Tab -->
                            <div class="tab-pane fade show active" id="zoneTab" role="tabpanel">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="zone_name" class="form-label">Zone Name</label>
                                        <input type="text" class="form-control" name="zone_name" placeholder="Enter zone name" required>
                                    </div>
                                    <button type="submit" name="add_zone" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add Zone
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Area Tab -->
                            <div class="tab-pane fade" id="areaTab" role="tabpanel">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="zone_id" class="form-label">Select Zone</label>
                                        <select class="form-select" name="zone_id" required>
                                            <option value="">Select Zone</option>
                                            <?php
                                            $zones = $user->runQuery("SELECT * FROM zones");
                                            $zones->execute();
                                            while($zone = $zones->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<option value='{$zone['id']}'>{$zone['zone_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="area_name" class="form-label">Area Name</label>
                                        <input type="text" class="form-control" name="area_name" placeholder="Enter area name" required>
                                    </div>
                                    <button type="submit" name="add_area" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add Area
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">User List</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['userName']); ?></td>
                                    <td><?= htmlspecialchars($u['userEmail']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill 
                                            <?= $u['user_type'] == 'admin' ? 'bg-primary' : 
                                               ($u['user_type'] == 'manager' ? 'bg-warning text-dark' :
                                               ($u['user_type'] == 'delivaryman' ? 'bg-info text-dark' : 'bg-success')) ?>">
                                            <?php if($u['user_type'] == 'admin'): ?>
                                                <i class="fas fa-star text-warning me-1"></i>
                                            <?php endif; ?>
                                            <?= ucfirst($u['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?= $u['status'] == 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $u['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($u['created_at'])); ?></td>

                                    <td class="action-btns">
                                        <a href="index.php?page=add_users&toggle_block=<?= $u['id']; ?>" style="width: 80px;" 
                                           class="btn btn-sm <?= $u['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?>" 
                                           onclick="return confirm('Are you sure to <?= $u['status'] == 'active' ? 'block' : 'unblock'; ?> this user?');"
                                           title="<?= $u['status'] == 'active' ? 'Block' : 'Unblock'; ?>">
                                            <?= $u['status'] == 'active' ? 'Deactive' : 'Active'; ?>
                                        </a>
                                        <?php if(in_array($u['user_type'], ['admin','manager','delivaryman'])): ?>
                                            <a href="index.php?page=add_users&id=<?= $u['id']; ?>" 
                                            class="btn btn-sm btn-primary edit-user-btn" title="Edit">
                                                Edit
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?page=user_profile&id=<?= $u['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (AJAX) CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);

            // Show/hide extra fields based on user type selection
            $('#user_type').change(function() {
                const extraFields = $('#extraFields');
                if(this.value === 'manager' || this.value === 'delivaryman') {
                    extraFields.show();
                } else {
                    extraFields.hide();
                }
            });

            // Load areas when zone is changed
            $('#zone').change(function() {
                var zoneId = $(this).val();
                $('#area').html('<option value="">Select Area</option>');
                
                if(zoneId) {
                    $.ajax({
                        url: 'pages/ajax/get_areas.php',
                        method: 'GET',
                        data: { zone_id: zoneId },
                        dataType: 'json',
                        success: function(response) {
                            if(response.error) {
                                console.error('Error:', response.error);
                                return;
                            }
                            
                            if(response.length > 0) {
                                $.each(response, function(index, area) {
                                    $('#area').append(`<option value="${area.id}">${area.area_name}</option>`);
                                });
                            }
                            
                            // Set selected area if editing
                            <?php if(isset($editUser) && !empty($editUser['area'])): ?>
                                $('#area').val('<?= $editUser['area'] ?>');
                            <?php endif; ?>
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                        }
                    });
                }
            });

            // Automatically show modal if editing user
            <?php if(isset($editUser)): ?>
                $(window).on('load', function() {
                    $('#userModal').modal('show');
                });
            <?php endif; ?>

            // Handle edit button click
            $('.edit-user-btn').click(function(e) {
                e.preventDefault();
                window.location.href = $(this).attr('href');
            });
        });
    </script>
</body>
</html>