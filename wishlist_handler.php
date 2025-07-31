<?php
session_start();
header('Content-Type: application/json');
include 'config/classes/user.php';

try {
    $DB_con = new USER();

    // ইউজার লগইন চেক
    if (!isset($_SESSION['userSession'])) {
        throw new Exception('Please login first');
    }

    // Accept only POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    if (!isset($_POST['product_id'], $_POST['action'])) {
        throw new Exception('Missing required data');
    }

    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'];
    $user_id = $_SESSION['userSession'];

    if ($action === 'add') {
        // Check if the product is already in the wishlist
        $check = $DB_con->runQuery("SELECT 1 FROM wishlists WHERE user_id = :uid AND product_id = :pid");
        $check->execute([':uid' => $user_id, ':pid' => $product_id]);

        if ($check->rowCount() === 0) {
            $stmt = $DB_con->runQuery("INSERT INTO wishlists (user_id, product_id) VALUES (:uid, :pid)");
            $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        }

        echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
    } elseif ($action === 'remove') {
        $stmt = $DB_con->runQuery("DELETE FROM wishlists WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);

        echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
