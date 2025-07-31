<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
// require_once __DIR__ . '/../../config/classes/user.php';
// $DB_con = new USER();

if (isset($_POST['category_id'])) {
    $cat_id = $_POST['category_id'];

    $stmt = $DB_con->runQuery("SELECT id, sub_cat_name FROM sub_categories WHERE category_id = ?");
    $stmt->execute([$cat_id]);

    if ($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<option value="'.$row['id'].'">'.$row['sub_cat_name'].'</option>';
        }
    } else {
        echo '<option value="">No Subcategory Found</option>';
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
