<?php
session_start();
require_once __DIR__ . '/../../../config/classes/user.php';

// Check authentication (uncomment and adjust as needed)
if (!isset($_SESSION['userSession']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'delivaryman')) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$auth_user = new USER();

// Get and validate input dates
$fromDate = $_POST['fromDate'] ?? '';
$toDate = $_POST['toDate'] ?? '';

// Validate date format
function isValidDate($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (!isValidDate($fromDate) || !isValidDate($toDate)) {
    die("<div class='alert alert-danger'>Invalid date format. Please use YYYY-MM-DD format.</div>");
}

if (strtotime($fromDate) > strtotime($toDate)) {
    die("<div class='alert alert-danger'>From date cannot be after To date.</div>");
}

try {
    // First check database connection
    if (!$auth_user) {
        throw new Exception("Database connection failed");
    }

    // Modified query to correctly join tables
    $query = "
        SELECT
            o.id AS order_id,
            DATE_FORMAT(o.order_date, '%Y-%m-%d %H:%i:%s') AS order_date,
            oi.product_id,
            p.product_name,
            p.price AS unit_price,
            oi.quantity,
            u.userName AS customer_name,
            u.userEmail AS customer_email,
            (oi.quantity * p.price) AS total_price,
            o.payment_method,
            o.status
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'delivered'
        AND o.order_date BETWEEN :fromDate AND :toDate
        ORDER BY o.order_date ASC";
    
    $stmt = $auth_user->runQuery($query);
    
    if (!$stmt) {
        throw new Exception("Query preparation failed");
    }

    // Execute with time boundaries
    $success = $stmt->execute([
        ':fromDate' => $fromDate . ' 00:00:00',
        ':toDate' => $toDate . ' 23:59:59'
    ]);

    if (!$success) {
        throw new Exception("Query execution failed");
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        echo "<div class='alert alert-info'>No delivered orders found between $fromDate and $toDate</div>";
        exit;
    }

    $grandTotal = 0;
    $currency = '৳'; // or '$' as needed

    // Start building the table
    $html = '<div class="table-responsive">
        <table class="table table-striped table-bordered" id="reportTable">
        <thead class="table-dark">
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($data as $row) {
        $grandTotal += $row['total_price'];
        $html .= sprintf('
            <tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s%.2f</td>
                <td>%d</td>
                <td>%s<br><small>%s</small></td>
                <td>%s%.2f</td>
                <td>%s</td>
            </tr>',
            htmlspecialchars($row['order_id']),
            htmlspecialchars($row['order_date']),
            htmlspecialchars($row['product_name']),
            $currency, $row['unit_price'],
            $row['quantity'],
            htmlspecialchars($row['customer_name']),
            htmlspecialchars($row['customer_email']),
            $currency, $row['total_price'],
            htmlspecialchars($row['payment_method'])
        );
    }

    // Add grand total row
    $html .= sprintf('
        <tr class="table-success">
            <td colspan="6" class="text-end fw-bold">Grand Total</td>
            <td class="fw-bold">%s%.2f</td>
            <td></td>
        </tr>',
        $currency, $grandTotal
    );

    $html .= '</tbody></table>
        <div class="text-muted small mt-2">Report generated on ' . date('Y-m-d H:i:s') . '</div>
    </div>';

    echo $html;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Database error occurred: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}