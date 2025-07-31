<?php
require_once __DIR__ . '/../../../config/classes/user.php';
$user = new USER();

header('Content-Type: application/json');

if(isset($_GET['zone_id'])) {
    $zoneId = intval($_GET['zone_id']);
    
    try {
        $stmt = $user->runQuery("SELECT * FROM areas WHERE zone_id = ?");
        $stmt->execute([$zoneId]);
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($areas);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode([]);
exit;