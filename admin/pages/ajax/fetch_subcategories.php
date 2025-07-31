<?php
require_once __DIR__ . '/../../../config/classes/user.php';
$DB_con = new USER();

if (isset($_POST['category_id'])) {
    $cat_id = $_POST['category_id'];

    $stmt = $DB_con->runQuery("SELECT * FROM sub_categories WHERE category_id = :cat_id");
    $stmt->execute([':cat_id' => $cat_id]);
    $sub_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sub_categories as $sub) {
        echo "<option value='{$sub['id']}'>" . htmlspecialchars($sub['sub_cat_name']) . "</option>";
    }
}
?>
