<?php
// Start the session
session_start();
require_once __DIR__ . '/../config/classes/adminProfile.php';


// Create a new USER object
$user = new AdminProfile();


// Check if user is logged in
if ($user->adminLogout()) {
 // Redirect to login page with success message
    $user->redirect('../admin/login.php?logout=success');
} else {
    // If not logged in, redirect to login page
    $user->redirect('../admin/login.php');
}
?>