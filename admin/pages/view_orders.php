<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';

$fetchOrders = new USER();
$fetchOrders->markOrdersAsSeen();

$orders = [];
$noOrdersMessage = '';
$errorMessage = '';
$deliveryZones = [];
$deliveryAreas = [];
$deliveryMen = [];

try {
    $DB_con = new USER();

    // Fetch delivery zones
    try {
        $zoneStmt = $DB_con->runQuery("SELECT id, zone_name FROM zones");
        $zoneStmt->execute();
        $deliveryZones = $zoneStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch delivery zones: " . $e->getMessage());
    }

    // Handle order updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id']) && is_numeric($_POST['order_id'])) {
        $orderId = intval($_POST['order_id']);
        $action = strtolower($_POST['action']);

        if ($action === 'accept') {
            if (empty($_POST['deliveryman_id']) || empty($_POST['zone_id']) || empty($_POST['area_id'])) {
                $errorMessage = "Please select zone, area and deliveryman";
            } else {
                $deliverymanId = intval($_POST['deliveryman_id']);
                $zoneId = intval($_POST['zone_id']);
                $areaId = intval($_POST['area_id']);
                $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

                try {
                    $DB_con->beginTransaction();

                    $stmt = $DB_con->runQuery("
                                      UPDATE orders 
                                      SET status = 'Shipped', 
                                          deliveryman_id = :deliveryman_id, 
                                          zone_id = :zone_id, 
                                          area_id = :area_id,
                                          notes = :notes 
                                      WHERE id = :id
                                  ");
                    $stmt->bindParam(':deliveryman_id', $deliverymanId, PDO::PARAM_INT);
                    $stmt->bindParam(':zone_id', $zoneId, PDO::PARAM_INT);
                    $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
                    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        if ($stmt->rowCount() > 0) {
                            $assignedBy = $_SESSION['userSession'];

                            $assignmentStmt = $DB_con->runQuery("
                                INSERT INTO delivery_assignments 
                                (order_id, deliveryman_id, zone_id, area_id, assigned_by, assigned_at) 
                                VALUES (:order_id, :deliveryman_id, :zone_id, :area_id, :assigned_by, NOW())
                            ");
                            $assignmentStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                            $assignmentStmt->bindParam(':deliveryman_id', $deliverymanId, PDO::PARAM_INT);
                            $assignmentStmt->bindParam(':zone_id', $zoneId, PDO::PARAM_INT);
                            $assignmentStmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
                            $assignmentStmt->bindParam(':assigned_by', $assignedBy, PDO::PARAM_INT);

                            if ($assignmentStmt->execute()) {
                                // Email send to deliveryman
                                $emailStmt = $DB_con->runQuery("SELECT userEmail, first_name FROM users WHERE id = :id");
                                $emailStmt->bindParam(':id', $deliverymanId, PDO::PARAM_INT);
                                $emailStmt->execute();
                                $deliveryman = $emailStmt->fetch(PDO::FETCH_ASSOC);

                                if ($deliveryman && !empty($deliveryman['userEmail'])) {
                                    $subject = "New Delivery Assignment";
                                    $body = '
                                      <!DOCTYPE html>
                                      <html>
                                      <head>
                                          <style>
                                              body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                              .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                              .header { color: #2c3e50; font-size: 24px; margin-bottom: 20px; }
                                              .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                                              .button { 
                                                  display: inline-block; 
                                                  padding: 10px 20px; 
                                                  background-color: #3498db; 
                                                  color: white; 
                                                  text-decoration: none; 
                                                  border-radius: 5px; 
                                                  margin-top: 15px;
                                              }
                                              .footer { margin-top: 20px; font-size: 12px; color: #7f8c8d; }
                                          </style>
                                      </head>
                                      <body>
                                          <div class="container">
                                              <div class="header">New Delivery Assignment</div>
                                              
                                              <div class="content">
                                                  <p>Hello ' . htmlspecialchars($deliveryman['first_name']) . ',</p>
                                                  
                                                  <p>You have been assigned a new delivery order. Here are the details:</p>
                                                  
                                                  <p><strong>Order ID:</strong> #' . $orderId . '</p>
                                                  
                                                  <p>Please review the delivery details in your dashboard and confirm acceptance at your earliest convenience.</p>
                                                  
                                                  <a href="https://eshop.xetroot.com/admin" class="button">View Delivery Details</a>
                                                  
                                                  <p>If you have any questions or encounter issues, please contact our support team.</p>
                                              </div>
                                              
                                              <div class="footer">
                                                  <p>Thank you for your prompt service!</p>
                                                  <p>Spider Monkey</p>
                                              </div>
                                          </div>
                                      </body>
                                      </html>
                                      ';

                                      $emailSent = $DB_con->sendMail($deliveryman['userEmail'], $body, $subject);
                                    if (!$emailSent) {
                                        error_log("Email sending failed: " . ($_SESSION['mailError'] ?? 'Unknown error'));
                                    }
                                }
                                $DB_con->commit();
                                $_SESSION['flash_message'] = [
                                    'type' => 'success',
                                    'message' => 'Order accepted, deliveryman assigned, and email sent!'
                                ];
                            } else {
                                $errorInfo = $assignmentStmt->errorInfo();
                                throw new Exception("Insert failed: " . $errorInfo[2]);
                            }
                        } else {
                            $errorMessage = "No changes made - order may not exist or already has this status";
                        }
                    } else {
                        throw new Exception("Failed to update order status");
                    }
                } catch (Exception $e) {
                    $DB_con->rollBack();
                    error_log("Accept order error: " . $e->getMessage());
                    $errorMessage = "Error updating order status or assigning deliveryman";
                }
            }
        } elseif ($action === 'cancel') {
             try {
                $DB_con->beginTransaction();

                // Cancel the order
                $stmt = $DB_con->runQuery("UPDATE orders SET status = 'Cancelled' WHERE id = :id");
                $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
                $stmt->execute();

                // Get order items
                $itemsStmt = $DB_con->runQuery("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id");
                $itemsStmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $itemsStmt->execute();
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                // Update stock for each item
                foreach ($items as $item) {
                    $updateStmt = $DB_con->runQuery("UPDATE products 
                        SET stock_amount = stock_amount + :qty 
                        WHERE id = :id");
                    $updateStmt->execute([
                        ':qty' => $item['quantity'],
                        ':id' => $item['product_id']
                    ]);
                }

                $DB_con->commit();
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Order cancelled and stock updated successfully!'
                ];
            } catch (Exception $e) {
                $DB_con->rollBack();
                error_log("Cancel order error: " . $e->getMessage());
                $errorMessage = "Error cancelling order";
            }
        }
    }

    // Fetch all orders with their items
    try {
        $stmt = $DB_con->runQuery("
            SELECT o.id, o.user_id, o.order_date, o.total_amount, 
                  o.status, o.customer_name, o.customer_phone, o.customer_address,
                  CONCAT(d.first_name, ' ', d.last_name) as deliveryman_name,
                  GROUP_CONCAT(DISTINCT o.tracking_id ORDER BY o.tracking_id) AS tracking_ids
            FROM orders o
            LEFT JOIN users d ON o.deliveryman_id = d.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            GROUP BY o.id
            ORDER BY o.order_date DESC
        ");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            $noOrdersMessage = "No orders found in the database";
        }
    } catch (PDOException $e) {
        error_log("Database fetch error: " . $e->getMessage());
        $errorMessage = "Failed to load orders from database: " . $e->getMessage();
    }

} catch (Exception $e) {
    error_log("System error: " . $e->getMessage());
    $errorMessage = "System error occurred: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Orders - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    .status-badge {
      min-width: 100px;
      display: inline-block;
      font-size: 0.85rem;
    }
    .action-buttons {
      min-width: 180px;
    }
    .order-row:hover {
      background-color: #f8f9fa;
    }
    .table-responsive {
      overflow-x: auto;
    }
    @media (max-width: 768px) {
      .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-y: hidden;
        -ms-overflow-style: -ms-autohiding-scrollbar;
        border: 1px solid #dee2e6;
      }
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <!-- Flash messages and error handling -->
  <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show">
      <?= $_SESSION['flash_message']['message'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
  <?php endif; ?>

  <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($errorMessage) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h2 class="h5 mb-0"><i class="bi bi-box-seam"></i> Customer Orders</h2>
    </div>
    
    <div class="card-body">
      <?php if (!empty($noOrdersMessage)): ?>
        <div class="alert alert-info text-center py-4">
          <i class="bi bi-info-circle-fill fs-4"></i>
          <h4 class="mt-2"><?= htmlspecialchars($noOrdersMessage) ?></h4>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Address</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Deliveryman</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td>#<?= htmlspecialchars($order['id']) ?></td>
                  <td><?= htmlspecialchars($order['customer_name']) ?></td>
                  <td>
                    <?= htmlspecialchars($order['tracking_ids'] ?? 'No products') ?>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                            data-bs-target="#productsModal" 
                            data-order-id="<?= $order['id'] ?>"
                            onclick="loadProducts(<?= $order['id'] ?>)">
                      <i class="bi bi-list"></i> View Details
                    </button>
                  </td>
                  <td><?= htmlspecialchars($order['customer_address']) ?></td>
                  <td>$<?= number_format($order['total_amount'], 2) ?></td>
                  <td>
                    <span class="badge <?= match($order['status']) {
                      'Pending' => 'bg-warning',
                      'Shipped' => 'bg-success',
                      'Cancelled' => 'bg-danger',
                      default => 'bg-secondary'
                    } ?>">
                      <?= htmlspecialchars($order['status']) ?>
                    </span>
                  </td>
                  <td><?= !empty($order['deliveryman_name']) ? htmlspecialchars($order['deliveryman_name']) : 'Not assigned' ?></td>
                  <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <?php if ($order['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#acceptOrderModal" 
                                data-order-id="<?= $order['id'] ?>" onclick="prepareAcceptModal(this)">
                          <i class="bi bi-check"></i> Accept
                        </button>
                        <button type="button" class="btn btn-danger" 
                                onclick="confirmCancel(<?= $order['id'] ?>)">
                          <i class="bi bi-x"></i> Cancel
                        </button>
                      <?php endif; ?>
                      <a href="index.php?page=view_product&id=<?= $order['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-eye"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Accept Order Modal -->
<div class="modal fade" id="acceptOrderModal" tabindex="-1" aria-labelledby="acceptOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="acceptOrderForm" method="post" action="index.php?page=view_orders">
        <input type="hidden" name="action" value="accept">
        <input type="hidden" name="order_id" id="modalOrderId" value="">
        
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="acceptOrderModalLabel">Accept Order</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="zoneSelect" class="form-label">Delivery Zone</label>
            <select class="form-select" id="zoneSelect" name="zone_id" required onchange="loadAreas(this.value)">
              <option value="">Select Zone</option>
              <?php foreach ($deliveryZones as $zone): ?>
                <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['zone_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="areaSelect" class="form-label">Delivery Area</label>
            <select class="form-select" id="areaSelect" name="area_id" required disabled>
              <option value="">Select Area (choose zone first)</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="deliverymanSelect" class="form-label">Deliveryman</label>
            <select class="form-select" id="deliverymanSelect" name="deliveryman_id" required disabled>
              <option value="">Select Deliveryman (choose area first)</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="deliveryNotes" class="form-label">Notes (Optional)</label>
            <textarea class="form-control" id="deliveryNotes" name="notes" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Confirm Acceptance</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Products Modal -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-labelledby="productsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productsModalLabel">Order #<span id="modalOrderNumber"></span> Products</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Price</th>
            </tr>
          </thead>
          <tbody id="productsList">
            <!-- Products will be loaded here via AJAX -->
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Load products when the modal is opened
function loadProducts(orderId) {
  document.getElementById('modalOrderNumber').textContent = orderId;
  
  fetch(`pages/ajax/get_order_products.php?order_id=${orderId}`)
    .then(response => response.json())
    .then(products => {
      const tbody = document.getElementById('productsList');
      tbody.innerHTML = '';
      
      products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${product.product_name}</td>
          <td>${product.quantity}</td>
          <td>$${product.unit_price}</td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => {
      console.error('Error loading products:', error);
      document.getElementById('productsList').innerHTML = `
        <tr><td colspan="3" class="text-danger">Error loading products</td></tr>
      `;
    });
}

// Prepare the accept modal with order ID
function prepareAcceptModal(button) {
  const orderId = button.getAttribute('data-order-id');
  document.getElementById('modalOrderId').value = orderId;
  
  // Reset form
  document.getElementById('zoneSelect').value = '';
  document.getElementById('areaSelect').value = '';
  document.getElementById('areaSelect').disabled = true;
  document.getElementById('deliverymanSelect').value = '';
  document.getElementById('deliverymanSelect').disabled = true;
}

// Load areas based on selected zone
function loadAreas(zoneId) {
  const areaSelect = document.getElementById('areaSelect');
  const deliverymanSelect = document.getElementById('deliverymanSelect');
  
  if (!zoneId) {
    areaSelect.innerHTML = '<option value="">Select Area (choose zone first)</option>';
    areaSelect.disabled = true;
    deliverymanSelect.innerHTML = '<option value="">Select Deliveryman (choose area first)</option>';
    deliverymanSelect.disabled = true;
    return;
  }
  
  // Fetch areas for the selected zone via AJAX
  fetch(`pages/ajax/get_order_areas.php?zone_id=${zoneId}`)
    .then(response => response.json())
    .then(data => {
      if (data.length > 0) {
        let options = '<option value="">Select Area</option>';
        data.forEach(area => {
          options += `<option value="${area.id}">${area.area_name}</option>`;
        });
        areaSelect.innerHTML = options;
        areaSelect.disabled = false;
      } else {
        areaSelect.innerHTML = '<option value="">No areas found for this zone</option>';
        areaSelect.disabled = true;
      }
      
      // Reset deliveryman select
      deliverymanSelect.innerHTML = '<option value="">Select Deliveryman (choose area first)</option>';
      deliverymanSelect.disabled = true;
    })
    .catch(error => {
      console.error('Error loading areas:', error);
      areaSelect.innerHTML = '<option value="">Error loading areas</option>';
      areaSelect.disabled = true;
    });
}

// Load deliverymen based on selected area
document.getElementById('areaSelect').addEventListener('change', function() {
  const areaId = this.value;
  const deliverymanSelect = document.getElementById('deliverymanSelect');
  
  if (!areaId) {
    deliverymanSelect.innerHTML = '<option value="">Select Deliveryman (choose area first)</option>';
    deliverymanSelect.disabled = true;
    return;
  }
  
  // Fetch deliverymen for the selected area via AJAX
  fetch(`pages/ajax/get_deliverymen.php?area_id=${areaId}`)
    .then(response => response.json())
    .then(data => {
      if (data.length > 0) {
        let options = '<option value="">Select Deliveryman</option>';
        data.forEach(deliveryman => {
          options += `<option value="${deliveryman.id}">${deliveryman.first_name} ${deliveryman.last_name}</option>`;
        });
        deliverymanSelect.innerHTML = options;
        deliverymanSelect.disabled = false;
      } else {
        deliverymanSelect.innerHTML = '<option value="">No deliverymen available for this area</option>';
        deliverymanSelect.disabled = true;
      }
    })
    .catch(error => {
      console.error('Error loading deliverymen:', error);
      deliverymanSelect.innerHTML = '<option value="">Error loading deliverymen</option>';
      deliverymanSelect.disabled = true;
    });
});

// Handle cancel order confirmation
function confirmCancel(orderId) {
  if (confirm(`Are you sure you want to cancel order #${orderId}?`)) {
    // Create a form dynamically
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?page=view_orders';
    
    // Add action input
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'cancel';
    
    // Add order_id input
    const orderIdInput = document.createElement('input');
    orderIdInput.type = 'hidden';
    orderIdInput.name = 'order_id';
    orderIdInput.value = orderId;
    
    // Append inputs to form
    form.appendChild(actionInput);
    form.appendChild(orderIdInput);
    
    // Append form to body and submit
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
</body>
</html>
