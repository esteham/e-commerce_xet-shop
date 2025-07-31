<?php
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/classes/user.php';

header('Content-Type: application/json');

if(!isset($_SESSION)) {
    session_start();
}

if(!isset($_SESSION['userSession'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if(!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID missing']);
    exit;
}

$product_id = intval($_POST['product_id']);
$encryption = new Encryption();
$encrypted_id = $encryption->encrypt($product_id);

echo json_encode([
    'success' => true,
    'encrypted_id' => $encrypted_id
]);
?>