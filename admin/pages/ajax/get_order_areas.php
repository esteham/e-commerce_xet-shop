<?php
require_once __DIR__ . '/../../../config/classes/user.php';

header('Content-Type: application/json');

try {
    $DB_con = new USER();
    
    if (!isset($_GET['zone_id']) || !is_numeric($_GET['zone_id'])) {
        throw new Exception('Invalid zone ID');
    }
    
    $zoneId = intval($_GET['zone_id']);
    $stmt = $DB_con->runQuery("SELECT id, area_name FROM areas WHERE zone_id = :zone_id");
    $stmt->bindParam(':zone_id', $zoneId, PDO::PARAM_INT);
    $stmt->execute();
    
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($areas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}