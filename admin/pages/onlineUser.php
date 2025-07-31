<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://eshop.xetroot.com/admin/assets/plugin/jquery.activeuser.js"></script>

<style>
    .online-users-container {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

    }
    
    .online-users-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        color: #2c3e50;
    }
    
    .online-users-header i {
        margin-right: 10px;
        font-size: 1.2rem;
        color: #27ae60;
    }
    
    .online-users-header h4 {
        margin: 0;
        font-weight: 600;
    }
    
    #userList {
        min-height: 50px;
    }
    
    .user-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .user-item {
        display: flex;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .user-item:last-child {
        border-bottom: none;
    }
    
    .user-status {
        width: 10px;
        height: 10px;
        background-color: #27ae60;
        border-radius: 50%;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    .user-name {
        color: #34495e;
        font-size: 0.9rem;
    }
    
    .loading-text {
        color: #7f8c8d;
        font-style: italic;
    }
</style>

<div class="online-users-container">
    <div class="online-users-header">
        <i class="fa-solid fa-user"></i>
        <h4>Online Users</h4>
    </div>
    <div id="userList" class="loading-text">Loading active users...</div>
</div>

<script type="text/javascript">
    $('#userList').activeUsers({
        url: "activeUser.php",
        interval: 3000,
        render: function(users) {
            if (users.length === 0) {
                return '<div class="loading-text">No active users</div>';
            }
        }
    });
</script>