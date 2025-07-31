<?php
require_once __DIR__ . '/../../../config/classes/user.php';

header('Content-Type: application/json');

try {
    $DB_con = new USER();

    if (!isset($_GET['area_id']) || !is_numeric($_GET['area_id'])) {
        throw new Exception('Invalid area ID');
    }

    $areaId = intval($_GET['area_id']);

    $stmt = $DB_con->runQuery("
        SELECT u.id, u.first_name, u.last_name 
        FROM users u
        JOIN user_zone_area uza ON u.id = uza.user_id
        WHERE u.user_type = 'delivaryman' 
        AND uza.area = :area_id
        AND u.status = 'active'
    ");
    $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
    $stmt->execute();

    $deliverymen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($deliverymen);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}