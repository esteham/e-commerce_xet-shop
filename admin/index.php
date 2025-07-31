<?php
session_start();
require_once __DIR__ . '/../config/classes/user.php';

$user = new USER();

if(!$user->is_logged_in() || !in_array($_SESSION['user_type'], ['admin', 'manager', 'delivaryman'])) 
{
    // If not logged in or not an admin, redirect to login page
    header('location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            transition: all 0.3s;
            position: fixed;
            z-index: 1000;
            width: 250px;
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header with toggle button -->
            <header class="bg-light p-3 d-flex justify-content-between align-items-center">
                <button class="btn btn-primary sidebar-toggle d-md-none">
                    <i class="fas fa-bars"></i>
                </button>
                <?php include 'includes/header.php'; ?>
            </header>
            
            <!-- Page Content -->
            <div class="container-fluid p-4">
                <?php
                if(isset($_GET['page'])) {
                    $page = $_GET['page'];
                    $file = "pages/{$page}.php";

                    if(file_exists($file)) {
                        include $file;
                    } else {
                        echo '<div class="alert alert-danger">Page Not Found!</div>';
                    }
                } else {
                    include 'pages/dashboard.php';
                }
                ?>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            
            // Add active class to parent of active submenu item
            const submenuLinks = document.querySelectorAll('.submenu .nav-link.active');
            submenuLinks.forEach(link => {
                link.closest('.collapse').previousElementSibling.classList.add('active');
            });
            
            // Toggle sidebar on button click
            if(toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if(window.innerWidth <= 768) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggleBtn = toggleBtn.contains(event.target);
                    
                    if(!isClickInsideSidebar && !isClickOnToggleBtn && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                        mainContent.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>