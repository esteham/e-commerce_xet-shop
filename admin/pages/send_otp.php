<?php
session_start();
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';
$auth_user = new USER();

date_default_timezone_set('Asia/Dhaka');

// Get deliveryman info
$stmt = $auth_user->runQuery("SELECT * FROM users WHERE id = :uid");
$stmt->execute(['uid' => $_SESSION['userSession']]);
$deliveryman = $stmt->fetch(PDO::FETCH_ASSOC);
$deliveryman_id = $deliveryman['id'];

if (isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    $auth_user->beginTransaction();
    
    try {
        // Get user info for the order
        $stmt = $auth_user->runQuery("
            SELECT orders.user_id, users.userEmail, users.userName 
            FROM orders 
            JOIN users ON orders.user_id = users.id 
            WHERE orders.id = :oid
            LIMIT 1
        ");
        $stmt->execute(['oid' => $order_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found for this order.");
        }

        // Generate OTP and set expiration (5 minutes)
        $otp = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Invalidate any previous OTPs for this order
        $auth_user->runQuery("
            UPDATE otps 
            SET status = 'expired' 
            WHERE order_id = :oid 
            AND status = 'sent'
        ")->execute(['oid' => $order_id]);

        // Update order with new OTP
        $auth_user->runQuery("
            UPDATE orders 
            SET delivery_otp = :otp 
            WHERE id = :oid
        ")->execute(['otp' => $otp, 'oid' => $order_id]);

        // Store new OTP record (using 'sent' status)
        $auth_user->runQuery("
            INSERT INTO otps (
                order_id, 
                user_id, 
                deliveryman_id, 
                otp, 
                expires_at,
                status
            ) VALUES (
                :oid, 
                :uid, 
                :did, 
                :otp, 
                :expires,
                'sent'
            )
        ")->execute([
            'oid' => $order_id,
            'uid' => $user['user_id'],
            'did' => $deliveryman_id,
            'otp' => $otp,
            'expires' => $expires_at
        ]);

        $auth_user->commit();

        // Send OTP email (your existing email template)
        $msg = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Delivery OTP Confirmation</title>
            <style>
                body {
                    font-family: "Segoe UI", Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    background-color: #f7f7f7;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    padding: 20px 0;
                }
                .logo {
                    max-width: 180px;
                    height: auto;
                }
                .content-card {
                    background-color: #ffffff;
                    border-radius: 8px;
                    padding: 30px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                    margin-bottom: 20px;
                }
                h1 {
                    color: #2c3e50;
                    font-size: 24px;
                    margin-top: 0;
                    margin-bottom: 20px;
                }
                .otp-code {
                    font-size: 32px;
                    letter-spacing: 5px;
                    color: #e74c3c;
                    font-weight: bold;
                    text-align: center;
                    margin: 25px 0;
                    padding: 15px;
                    background-color: #f9f9f9;
                    border-radius: 6px;
                    border: 1px dashed #e0e0e0;
                }
                .instructions {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 6px;
                    font-size: 15px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    color: #7f8c8d;
                    font-size: 14px;
                    padding-top: 20px;
                }
                .button {
                    display: inline-block;
                    background-color: #3498db;
                    color: #ffffff;
                    text-decoration: none;
                    padding: 12px 25px;
                    border-radius: 4px;
                    font-weight: 600;
                    margin: 15px 0;
                }
                @media only screen and (max-width: 600px) {
                    .content-card {
                        padding: 20px;
                    }
                    h1 {
                        font-size: 22px;
                    }
                    .otp-code {
                        font-size: 28px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <!-- Replace with your actual logo -->
                    <p>SpiDer Monkey</p>
                </div>
                
                <div class="content-card">
                    <h1>Hello '.$user['userName'].'!</h1>
                    <p>To complete your order delivery, please provide this OTP to your delivery person:</p>
                    
                    <div class="otp-code">'.$otp.'</div>
                    
                    <div class="instructions">
                        <p><strong>Important:</strong> This OTP is valid for one-time use only. Do not share this code with anyone other than your verified delivery person.</p>
                    </div>
                    
                    <p>Your order details:</p>
                    <ul>
                        <li><strong>Order ID:</strong> #'.$order_id.'</li>
                        <li><strong>Delivery Agent:</strong> '.$deliveryman['userName'].'</li>
                    </ul>
                    
                    <p>If you didn\'t request this OTP, please contact our support team immediately.</p>
                    
                    <a href="https://eshop.xetroot.com/contact.php" class="button">Contact Support</a>
                </div>
                
                <div class="footer">
                    <p>© '.date('Y').' SpiDer Monkey. All rights reserved.</p>
                    <p>
                        <a href="https://eshop.xetroot.com" style="color: #3498db;">Our Website</a> | 
                        <a href="https://eshop.xetroot.com/privacy.php" style="color: #3498db;">Privacy Policy</a>
                    </p>
                    <p>This is an automated message - please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>';
        $subject = "Your Delivery OTP for Order #".$order_id;
        
        if (!$auth_user->sendMail($user['userEmail'], $msg, $subject)) {
            throw new Exception("Failed to send OTP email.");
        }
        
        echo "OTP sent to customer successfully.";
    } catch (Exception $e) {
        $auth_user->rollBack();
        echo "Error: " . $e->getMessage();
    }
}