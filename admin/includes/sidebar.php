<?php
$userType = $_SESSION['user_type'] ?? null;
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="p-3">
        <h4 class="text-white mb-4 text-center">
            <i class="fas fa-cog"></i> Admin Panel
        </h4>
    
        <!-- Admin, Manager, Delivery Manager -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : '' ?>" 
                   href="index.php?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if($userType == 'delivaryman'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'delivery_orders') ? 'active' : '' ?>" 
                       href="index.php?page=delivary_orders">
                        <i class="fas fa-truck"></i> View Orders
                    </a>
                </li>
            <?php endif; ?>

            <!-- Admin and Manager -->
            <?php if($userType == 'admin' || $userType == 'manager'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'view_orders') ? 'active' : '' ?>" 
                       href="index.php?page=view_orders">
                        <i class="fas fa-clipboard-list"></i> View Orders
                        <span class="badge bg-danger" id="newOrderCount" style="display: none;"></span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && strpos($_GET['page'], 'sales/') !== false) ? 'active' : '' ?>" 
                       data-bs-toggle="collapse" 
                       href="#salesMenu" 
                       role="button" 
                       aria-expanded="<?= (isset($_GET['page']) && strpos($_GET['page'], 'sales/') !== false) ? 'true' : 'false' ?>" 
                       aria-controls="salesMenu">
                        <i class="fas fa-shopping-cart"></i> Sales
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= (isset($_GET['page']) && strpos($_GET['page'], 'sales/') !== false) ? 'show' : '' ?>" 
                         id="salesMenu" 
                         data-bs-parent=".sidebar">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'todays_sales') ? 'active' : '' ?>" 
                                   href="index.php?page=todays_sales">Today's Sales</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'total_sales') ? 'active' : '' ?>" 
                                   href="index.php?page=total_sales">Total Sales</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'pro_wise_sale') ? 'active' : '' ?>" 
                                   href="index.php?page=pro_wise_sale">Product Wise Sales</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'categories') ? 'active' : '' ?>" 
                       href="index.php?page=categories">
                        <i class="fas fa-list"></i> Categories
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'products') ? 'active' : '' ?>" 
                       href="index.php?page=products">
                        <i class="fas fa-boxes"></i> Manage Products
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'stock') ? 'active' : '' ?>" 
                       href="index.php?page=stock">
                        <i class="fas fa-warehouse"></i> Manage Stock
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'customers') ? 'active' : '' ?>" 
                       href="index.php?page=customers">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'messages') ? 'active' : '' ?>" 
                       href="index.php?page=messages">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                </li>
            <?php endif; ?>
        
            <!-- Admin only -->
            <?php if($userType == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && strpos($_GET['page'], 'users/') !== false) ? 'active' : '' ?>" 
                       data-bs-toggle="collapse" 
                       href="#userMenu" 
                       role="button" 
                       aria-expanded="<?= (isset($_GET['page']) && strpos($_GET['page'], 'users/') !== false) ? 'true' : 'false' ?>" 
                       aria-controls="userMenu">
                        <i class="fas fa-user-cog"></i> Users
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= (isset($_GET['page']) && strpos($_GET['page'], 'users/') !== false) ? 'show' : '' ?>" 
                         id="userMenu" 
                         data-bs-parent=".sidebar">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'users/add_users') ? 'active' : '' ?>" 
                                   href="index.php?page=users/add_users">Add User</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'users/onlineUser') ? 'active' : '' ?>" 
                                   href="index.php?page=users/onlineUser">Online Users</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['page']) && strpos($_GET['page'], 'reports/') !== false) ? 'active' : '' ?>" 
                       data-bs-toggle="collapse" 
                       href="#reportMenu" 
                       role="button" 
                       aria-expanded="<?= (isset($_GET['page']) && strpos($_GET['page'], 'reports/') !== false) ? 'true' : 'false' ?>" 
                       aria-controls="reportMenu">
                        <i class="fas fa-chart-bar"></i> Reports
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?= (isset($_GET['page']) && strpos($_GET['page'], 'reports/') !== false) ? 'show' : '' ?>" 
                         id="reportMenu" 
                         data-bs-parent=".sidebar">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'reports/sales') ? 'active' : '' ?>" 
                                   href="index.php?page=datewise">Sales Report</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'reports/inventory') ? 'active' : '' ?>" 
                                   href="index.php?page=reports/inventory">Inventory Report</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_GET['page']) && $_GET['page'] == 'reports/profits') ? 'active' : '' ?>" 
                                   href="index.php?page=reports/profits">Profit Reports</a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Required JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Only Bootstrap 5 Bundle required (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Fetch new order count
    function fetchNewOrderCount() {
        $.ajax({
            url: 'pages/ajax/fetch_new_order_count.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.count > 0) {
                    $('#newOrderCount').text(data.count).show();
                } else {
                    $('#newOrderCount').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching new order count:', error);
                $('#newOrderCount').hide();
            }
        });
    }

    fetchNewOrderCount();
    setInterval(fetchNewOrderCount, 60000);
});
</script>
