<?php
require_once __DIR__ . '/../../config/classes/user.php';

// Disable error display (log them instead)
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

try {
    $DB = new USER();
    $stmt = $DB->runQuery("SELECT zone FROM user_zone_area WHERE zone IS NOT NULL");
    $stmt->execute();
    $zones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'data' => $zones
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("get_zones.php error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load zones'
    ]);
}
?>