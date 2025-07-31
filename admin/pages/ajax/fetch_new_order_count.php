<?php
session_start();
require_once __DIR__ . '/../../../config/classes/user.php';
header('Content-Type: application/json');

$fetchNewOrderCount = new USER();

// Check if user is logged in and is either admin or deliveryman
if(isset($_SESSION['userSession']) && ($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'delivaryman')) {
    $count = $fetchNewOrderCount->getNewOrderCount();
    echo json_encode(['count' => $count]);  
} else {
    echo json_encode(['count' => 0]);
}
?>