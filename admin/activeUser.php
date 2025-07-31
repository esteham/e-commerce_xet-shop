<?php

    require_once __DIR__ . '/../config/classes/adminProfile.php';
    $user = new AdminProfile();

    $stmt = $user->runQuery("SELECT userName, user_type, profile_image FROM users WHERE is_online = 1");
    $stmt -> execute();

    $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json'); 
    echo json_encode($onlineUsers);

?>