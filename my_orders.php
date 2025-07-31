<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$AUTH_user = new USER();

if (!isset($_SESSION['userSession'])) {
    header("Location: login.php");
    exit();
}

// Handle order deletion
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        // Start transaction
        $AUTH_user->beginTransaction();
        
        // First delete order items
        $stmt = $AUTH_user->runQuery("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Then delete the order
        $stmt = $AUTH_user->runQuery("DELETE FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['userSession']]);
        
        $AUTH_user->commit();
        
        // Refresh the page to show updated list
        header("Location: my_orders.php");
        exit();
    } catch (PDOException $e) {
        $AUTH_user->rollBack();
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Get customer ID from session
$id = $_SESSION['userSession'];

// Fetch customer details
$stmt = $AUTH_user->runQuery("SELECT userName, profile_image FROM users WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch orders for the customer
$stmt = $AUTH_user->runQuery("
    SELECT o.id AS order_id, o.order_date, o.total_amount, o.status, o.tracking_id, 
           o.return_reason, o.return_notes, o.return_date
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.order_date DESC
");

$stmt->execute([$id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/my_order.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <div class="container">
        <!-- Modern Profile Header -->
        <div class="profile-header d-flex justify-content-between">
            <div class="profile-avatar">
                <?php if (!empty($customer['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($customer['profile_image']) ?>" alt="Profile" class="avatar-image">
                <?php else: ?>
                    <div class="avatar-initials"><?php echo strtoupper(substr($customer['userName'], 0, 1)); ?></div>
                <?php endif; ?>
                <div>
                    <h1>My Orders</h1>
                    <p class="welcome">Welcome back, <?php echo htmlspecialchars($customer['userName']); ?></p>
                </div>
            </div>
            <div class="tracking-search-container animate__animated animate__fadeIn">
                <h3><i class="fas fa-search-location me-2"></i>Track Any Order</h3>
                <form id="trackingForm" class="tracking-form">
                    <div class="input-group">
                        <input type="text" id="trackingInput" class="form-control" placeholder="Enter tracking number" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-truck me-1"></i> Track
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($orders) > 0): ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card animate__animated animate__fadeInUp">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-id">Tracking ID : <?php echo $order['tracking_id']; ?></span>
                                <span class="order-date">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                                </span>
                            </div>
                            <div class="order-header-right">
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <span class="status-badge">
                                        <span class="dot"></span>
                                        <?php echo $order['status']; ?>
                                    </span>
                                </span>
                                <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="delete_order" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <?php
                            // Fetch items for this order
                            $items_query = $AUTH_user->runQuery("
                                SELECT p.product_name, p.product_image, oi.quantity, oi.unit_price 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = :id
                            ");
                            $items_query->execute([':id' => $order['order_id']]);
                            $items_result = $items_query->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <div class="products-grid">
                                <?php foreach ($items_result as $item): ?>
                                    <div class="product-card">
                                        <div class="product-image-container">
                                            <?php if ($item['product_image']): ?>
                                                <img src="admin/pages/uploads/<?= htmlspecialchars($item['product_image']) ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image" onerror="this.src='https://via.placeholder.com/100'">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/100" alt="Placeholder" class="product-image">
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <h4 class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                            <div class="product-meta">
                                                <span class="product-price">৳<?php echo number_format($item['unit_price'], 2); ?></span>
                                                <span class="product-quantity">x <?php echo $item['quantity']; ?></span>
                                                <span class="product-total">৳<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                <span>Order Total:</span>
                                <span class="total-amount">৳<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="order-actions">
                                <a href="generate_invoice.php?order_id=<?= $order['order_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-file-invoice me-2"></i>Invoice
                                </a>

                                <a href="#" class="btn btn-secondary" onclick="trackOrder('<?php echo $order['tracking_id']; ?>', '<?php echo $order['status']; ?>')">
                                    <i class="fas fa-external-link-alt me-1"></i> Track Package
                                </a>
                                
                                <button class="btn btn-outline">
                                    <i class="fas fa-headset me-2"></i>Support
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state animate__animated animate__fadeIn">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 class="empty-title">No orders yet</h3>
                <p class="empty-description">You haven't placed any orders with us yet. Start shopping to see your orders here!</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-store me-2"></i> Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>
    
   <!-- Tracking Modal -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Tracking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="trackingResults">
                    <!-- Tracking content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
// Update the form submission handler
document.getElementById('trackingForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    
    const trackingNumber = document.getElementById('trackingInput').value.trim();
    
    if (!trackingNumber) {
        alert('Please enter a tracking number');
        return;
    }
    
    // For the search form, we need to find the order status from existing orders
    const order = findOrderByTrackingNumber(trackingNumber);
    
    if (order) {
        trackOrder(trackingNumber, order.status);
    } else {
        // Show error if tracking number not found
        const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
        const resultsDiv = document.getElementById('trackingResults');
        
        resultsDiv.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5>Order Not Found</h5>
                <p class="text-muted">No order found with tracking number: ${trackingNumber}</p>
                <p>Please check the number and try again.</p>
            </div>
        `;
        
        modal.show();
    }
});

// Helper function to find order by tracking number
function findOrderByTrackingNumber(trackingNumber) {
    // This assumes your orders are rendered in the HTML
    const orderElements = document.querySelectorAll('.order-card');
    
    for (const element of orderElements) {
        const orderTrackingId = element.querySelector('.order-id')?.textContent
            .replace('Tracking ID :', '').trim();
        
        if (orderTrackingId === trackingNumber) {
            const status = element.querySelector('.order-status')?.textContent.trim();
            return { status };
        }
    }
    return null;
}

function trackOrder(trackingNumber, orderStatus) {
    const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
    const resultsDiv = document.getElementById('trackingResults');
    
    // Show loading state
    resultsDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Tracking order #${trackingNumber}</p>
        </div>
    `;
    
    modal.show();
    
    // Simulate API call with delay
    setTimeout(() => {
        // Determine status based on actual order status
        let status;
        switch(orderStatus) {
            case 'Pending':
                status = {name: "Pending", class: "pending", progress: 1};
                break;
            case 'Processing':
                status = {name: "Processing", class: "processing", progress: 2};
                break;
            case 'Shipped':
                status = {name: "Shipped", class: "shipped", progress: 3};
                break;
            case 'Delivered':
                status = {name: "Delivered", class: "delivered", progress: 5};
                break;
            case 'Cancelled':
                status = {name: "Cancelled", class: "cancelled", progress: 0};
                break;
            case 'Returned':
                status = {name: "Returned", class: "returned", progress: 0};
                break;
            default:
                status = {name: "Processing", class: "processing", progress: 1};
        }
        
        // Special handling for cancelled/returned orders
        if (orderStatus === 'Cancelled' || orderStatus === 'Returned') {
            let returnInfo = '';
            
            // Add return details if available and status is Returned
            if (orderStatus === 'Returned') {
                const order = getOrderDetails(trackingNumber);
                if (order) {
                    returnInfo = `
                        <div class="return-info">
                            <h6><i class="fas fa-exchange-alt me-2"></i>Return Details</h6>
                            <p><strong>Reason:</strong> ${order.return_reason || 'Not specified'}</p>
                            <p><strong>Notes:</strong> ${order.return_notes || 'No additional notes'}</p>
                            ${order.return_date ? `<p><strong>Return Date:</strong> ${new Date(order.return_date).toLocaleDateString()}</p>` : ''}
                        </div>
                    `;
                }
            }
            
            resultsDiv.innerHTML = `
                <div class="tracking-simple">
                    <div class="tracking-header">
                        <div class="tracking-id">Order #${trackingNumber}</div>
                        <div class="tracking-status status-${status.class}">${status.name}</div>
                    </div>
                    
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-${orderStatus === 'Cancelled' ? 'danger' : 'warning'} mb-3"></i>
                        <h5>Order ${orderStatus}</h5>
                        <p class="text-muted">
                            ${orderStatus === 'Cancelled' ? 
                              'This order was cancelled and will not be delivered.' : 
                              'This order was returned to the seller.'}
                        </p>
                    </div>
                    ${returnInfo}
                </div>
            `;
            return;
        }
        
        // Normal tracking display
        resultsDiv.innerHTML = `
            <div class="tracking-simple">
                <div class="tracking-header">
                    <div class="tracking-id">Order #${trackingNumber}</div>
                    <div class="tracking-status status-${status.class}">${status.name}</div>
                </div>
                
                <div class="tracking-progress">
                    <div class="progress-steps">
                        <div class="progress-step ${status.progress >= 1 ? 'active' : ''}">
                            <div class="step-icon">1</div>
                            <div class="step-label">Ordered</div>
                        </div>
                        <div class="progress-step ${status.progress >= 2 ? 'active' : ''}">
                            <div class="step-icon">2</div>
                            <div class="step-label">Processing</div>
                        </div>
                        <div class="progress-step ${status.progress >= 3 ? 'active' : ''}">
                            <div class="step-icon">3</div>
                            <div class="step-label">Shipped</div>
                        </div>
                        <div class="progress-step ${status.progress >= 4 ? 'active' : ''}">
                            <div class="step-icon">4</div>
                            <div class="step-label">In Transit</div>
                        </div>
                        <div class="progress-step ${status.progress >= 5 ? 'active' : ''}">
                            <div class="step-icon">5</div>
                            <div class="step-label">Delivered</div>
                        </div>
                    </div>
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${(status.progress-1)*25}%"></div>
                    </div>
                </div>
                
                <div class="tracking-details">
                    <div class="tracking-detail">
                        <div class="detail-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="detail-content">
                            <div class="detail-title">Status</div>
                            <div class="detail-text">${getStatusDescription(status.name)}</div>
                        </div>
                    </div>
                    <div class="tracking-detail">
                        <div class="detail-icon"><i class="fas fa-clock"></i></div>
                        <div class="detail-content">
                            <div class="detail-title">Last Update</div>
                            <div class="detail-text">${getLastUpdate(status.name)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }, 800);
}

// Helper function to get order details (simulated - in a real app this would be from your database)
function getOrderDetails(trackingNumber) {
    // This will make an AJAX call to fetch real return details from the database
    return fetch('get_return_details.php?tracking_id=' + encodeURIComponent(trackingNumber))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                return {
                    return_reason: data.return_reason || 'Not specified',
                    return_notes: data.return_notes || 'No additional notes',
                    return_date: data.return_date || null
                };
            }
            return null;
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            return null;
        });
}

// Helper functions
function getStatusDescription(status) {
    const descriptions = {
        "Pending": "Waiting for payment confirmation",
        "Processing": "Order is being prepared for shipment",
        "Shipped": "Package has left our facility",
        "Delivered": "Package was delivered successfully",
        "Cancelled": "Order was cancelled",
        "Returned": "Order was returned"
    };
    return descriptions[status] || "Order is being processed";
}

function getLastUpdate(status) {
    const updates = {
        "Pending": "Order received - pending payment",
        "Processing": "Preparing your items for shipment",
        "Shipped": "Package dispatched to carrier",
        "Delivered": "Delivered to recipient",
        "Cancelled": "Order cancellation processed",
        "Returned": "Return request completed"
    };
    return updates[status] || "Order update in progress";
}

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>