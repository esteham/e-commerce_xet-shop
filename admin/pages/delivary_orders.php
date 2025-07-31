<?php

if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';
$auth_user = new USER();

if (!isset($_SESSION['userSession'])) {
    header("Location: ../login.php");
    exit;
}

$stmt = $auth_user->runQuery("SELECT * FROM users WHERE id=:uid");
$stmt->execute(["uid" => $_SESSION['userSession']]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userRow['user_type'] !== 'delivaryman') {
    echo "Unauthorized access.";
    exit;
}

$deliveryman_id = $userRow['id'];
$stmt = $auth_user->runQuery("
    SELECT 
        o.id, o.user_id, o.order_date, o.total_amount,o.payment_method,
        o.status, o.customer_name, o.customer_phone, o.customer_address,
        GROUP_CONCAT(p.product_name SEPARATOR ', ') AS products
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.status = 'Shipped' AND o.deliveryman_id = :did
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute(["did" => $deliveryman_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipped Orders</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn-send-otp {
            background-color: #4285f4;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-return {
            background-color: #fbbc05;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-left: 5px;
        }
        .btn-send-otp:hover {
            background-color: #3367d6;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        .btn-return:hover {
            background-color: #e6ac00;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        .btn-send-otp:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        /* Modern Modal Styles */
        #otpModal, #returnModal {
            /* display: none; */
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 450px;
            max-width: 90%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.3s;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 22px;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #777;
            transition: color 0.2s;
        }
        .close-modal:hover {
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #555;
        }
        .otp-input-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        .otp-input:focus {
            border-color: #4285f4;
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.5);
            outline: none;
        }
        .submit-btn {
            background-color: #34a853;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        .cancel-btn {
            background-color: #d23f31;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        .submit-btn:hover {
            background-color: #2d8e47;
        }
        .cancel-btn:hover {
            background-color: #b83529;
        }
        .submit-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .timer-container {
            text-align: center;
            margin: 20px 0;
        }
        .timer-text {
            display: inline-block;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-size: 14px;
            color: #555;
        }
        #time {
            font-weight: bold;
            color: #d23f31;
        }
        .resend-section {
            text-align: center;
            margin-top: 20px;
            display: none;
        }
        .resend-btn {
            background: none;
            border: none;
            color: #4285f4;
            cursor: pointer;
            font-size: 15px;
            text-decoration: underline;
            font-weight: 500;
        }
        .resend-btn:hover {
            color: #3367d6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-shipped {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            margin-bottom: 15px;
        }
        .no-orders {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .no-orders p {
            font-size: 18px;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>Shipped Orders Assigned to You</h2>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <p>No shipped orders found.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Products</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <?php
                  // Random unique placeholder data generator
                  $randomLabels = ['Product Info', 'Order Items', 'View List', 'Item Summary', 'Show Goods', 'Click to See', 'Check Products', 'See Details', 'Open List'];
                  $randomIndex = array_rand($randomLabels);
                  $randomText = $randomLabels[$randomIndex];
                ?>
                <tr>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                    <td><?= htmlspecialchars($order['customer_address']) ?></td>
                    <td>
                        <?= htmlspecialchars($randomText ?? 'No products') ?>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                data-bs-target="#productsModal" 
                                data-order-id="<?= $order['id'] ?>"
                                onclick="loadProducts(<?= $order['id'] ?>)">
                        <i class="bi bi-list"></i> View Details
                        </button>
                    </td>
                    <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    <td>
                        <span class="status-badge status-shipped"><?= htmlspecialchars($order['status']) ?></span>
                    </td>
                    <td class="d-flex">
                        <button class="btn-send-otp" onclick="sendOtp(<?= $order['id'] ?>)">Send OTP</button>
                        <button class="btn-return" onclick="showReturnModal(<?= $order['id'] ?>)">Return Order</button>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- OTP Verification Modal -->
    <div id="otpModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Verify Delivery</h3>
                <button class="close-modal" onclick="closeModal('otpModal')">&times;</button>
            </div>
            <form id="otpForm">
                <input type="hidden" name="order_id" id="modal_order_id">
                
                <div class="form-group">
                    <label>Enter the 6-digit OTP sent to customer:</label>
                    <div class="otp-input-container">
                        <input type="text" class="otp-input" maxlength="1" data-index="1" onkeyup="moveToNext(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="2" onkeyup="moveToNext(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="3" onkeyup="moveToNext(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="4" onkeyup="moveToNext(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="5" onkeyup="moveToNext(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="6" onkeyup="moveToNext(this)">
                    </div>
                    <input type="hidden" name="otp" id="fullOtp">
                </div>
                
                <div class="timer-container">
                    <div class="timer-text">
                        OTP will expire in: <span id="time">05:00</span>
                    </div>
                </div>
                
                <div class="resend-section" id="resendSection">
                    <p>OTP has expired</p>
                    <button type="button" class="resend-btn" id="resendBtn">Resend OTP</button>
                </div>
                
                <button type="submit" class="submit-btn">Confirm Delivery</button>
            </form>
        </div>
    </div>

    <!-- Return Order Modal -->
    <div id="returnModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Return Order</h3>
                <button class="close-modal" onclick="closeModal('returnModal')">&times;</button>
            </div>
            <form id="returnForm">
                <input type="hidden" name="order_id" id="return_order_id">
                
                <div class="form-group">
                    <label>Reason for return:</label>
                    <textarea name="return_reason" placeholder="Please specify the reason for returning this order..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Additional notes (optional):</label>
                    <textarea name="return_notes" placeholder="Any additional information..."></textarea>
                </div>
                
                <button type="submit" class="cancel-btn">Confirm Return</button>
            </form>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
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
// Global variables for timer
let countdown;
let timeLeft = 300; // 5 minutes in seconds
let currentOrderId = null;

function sendOtp(orderId) {
    currentOrderId = orderId;
    $.ajax({
        url: 'pages/send_otp.php',
        type: 'POST',
        data: { order_id: orderId },
        success: function(response) {
            if (response.includes("OTP sent")) {
                startTimer();
                $('#otpModal').show();
                $('#modal_order_id').val(orderId);
                
                $(`button[onclick="sendOtp(${orderId})"]`)
                    .prop('disabled', true)
                    .text('OTP Sent');
                    
                $('.otp-input[data-index="1"]').focus();
            }
            alert(response);
        },
        error: function(xhr, status, error) {
            console.error('Send OTP error:', error);
            alert("Failed to send OTP. Please try again.");
        }
    });
}

function showReturnModal(orderId) {
    $('#return_order_id').val(orderId);
    $('#returnModal').show();
}

function startTimer() {
    // Reset timer
    clearInterval(countdown);
    timeLeft = 300;
    $('#resendSection').hide();
    $('.submit-btn').prop('disabled', false);
    
    // Update the timer immediately
    updateTimer();
    
    // Start countdown
    countdown = setInterval(function() {
        timeLeft--;
        updateTimer();
        
        if (timeLeft <= 0) {
            clearInterval(countdown);
            $('#resendSection').show();
            $('.submit-btn').prop('disabled', true);
        }
    }, 1000);
}

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    $('#time').text(
        (minutes < 10 ? '0' : '') + minutes + ':' + 
        (seconds < 10 ? '0' : '') + seconds
    );
}

function closeModal(modalId) {
    $('#' + modalId).hide();
    if (modalId === 'otpModal') {
        clearInterval(countdown);
        $('.otp-input').val(''); // Clear OTP inputs
        $('#fullOtp').val(''); // Clear combined OTP
    }
}

function moveToNext(input) {
    const index = parseInt(input.getAttribute('data-index'));
    const value = input.value;
    
    // Only allow numbers
    input.value = value.replace(/[^0-9]/g, '');
    
    // If a number was entered and there's a next input
    if (input.value && index < 6) {
        document.querySelector(`.otp-input[data-index="${index + 1}"]`).focus();
    }
    
    // If backspace was pressed and there's a previous input
    if (!input.value && index > 1 && event.key === 'Backspace') {
        document.querySelector(`.otp-input[data-index="${index - 1}"]`).focus();
    }
    
    // Update the full OTP value
    updateFullOtp();
}

function updateFullOtp() {
    let fullOtp = '';
    $('.otp-input').each(function() {
        fullOtp += $(this).val();
    });
    $('#fullOtp').val(fullOtp);
}

$('#otpForm').on('submit', function(e) {
    e.preventDefault();
    
    // Combine OTP digits
    updateFullOtp();
    const otp = $('#fullOtp').val();
    
    if (otp.length !== 6) {
        alert("Please enter a complete 6-digit OTP");
        return;
    }
    
    $.ajax({
        url: 'pages/confirm_otp.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            alert(response);
            if (response.includes("confirmed")) {
                closeModal('otpModal');
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error('Confirm OTP error:', error);
            alert("Failed to confirm delivery. Please try again.");
        }
    });
});

$('#returnForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!confirm("Are you sure you want to mark this order as returned?")) {
        return;
    }
    
    $.ajax({
        url: 'pages/process_return.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            alert(response);
            if (response.includes("success")) {
                closeModal('returnModal');
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error('Return order error:', error);
            alert("Failed to process return. Please try again.");
        }
    });
});

$('#resendBtn').click(function() {
    if (currentOrderId) {
        sendOtp(currentOrderId);
    }
});

// Initialize OTP inputs
$('.otp-input').on('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Close modal when clicking outside
$(document).mouseup(function(e) {
    const otpModal = $('#otpModal');
    const returnModal = $('#returnModal');
    
    if (!otpModal.is(e.target) && otpModal.has(e.target).length === 0) {
        closeModal('otpModal');
    }
    
    if (!returnModal.is(e.target) && returnModal.has(e.target).length === 0) {
        closeModal('returnModal');
    }
});
</script>
</html>