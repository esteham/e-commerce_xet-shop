<?php
session_start();
require_once __DIR__ . '/../../config/classes/user.php';
$auth_user = new USER();

date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['userSession'])) {
    echo "Unauthorized access.";
    exit;
}

if (isset($_POST['order_id'], $_POST['otp'])) {
    $order_id = intval($_POST['order_id']);
    $entered_otp = intval($_POST['otp']);
    $deliveryman_id = $_SESSION['userSession'];

    $auth_user->beginTransaction();

    try {
        // Verify OTP is valid and not expired (checking for 'sent' status)
        $stmt = $auth_user->runQuery("
            SELECT ot.id, o.user_id, u.userEmail, u.userName
            FROM otps ot
            JOIN orders o ON ot.order_id = o.id
            JOIN users u ON o.user_id = u.id
            WHERE ot.order_id = :order_id
              AND ot.otp = :otp
              AND ot.status = 'sent'
              AND ot.expires_at > NOW()
              AND ot.deliveryman_id = :deliveryman_id
            LIMIT 1
        ");
        $stmt->execute([
            'order_id' => $order_id,
            'otp' => $entered_otp,
            'deliveryman_id' => $deliveryman_id
        ]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            throw new Exception("Invalid or expired OTP.");
        }

        // Mark OTP as used
        $auth_user->runQuery("
            UPDATE otps 
            SET status = 'used',
                used_at = NOW() 
            WHERE id = :otp_id
        ")->execute(['otp_id' => $otp_record['id']]);

        // Update order status
        $auth_user->runQuery("
            UPDATE orders 
            SET status = 'Delivered',
                completed_at = NOW(),
                delivery_otp = NULL 
            WHERE id = :order_id
        ")->execute(['order_id' => $order_id]);

        $auth_user->commit();

        //Send delivery confirmation email
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Order Delivered</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" type="text/css">
            <style>
                body {
                    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f5f5f7;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .card {
                    background: #ffffff;
                    border-radius: 12px;
                    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                    margin: 20px 0;
                }
                .header {
                    padding: 30px 30px 20px;
                    text-align: center;
                    border-bottom: 1px solid #f0f0f0;
                }
                .header h1 {
                    color: #111;
                    font-size: 24px;
                    font-weight: 600;
                    margin: 0;
                }
                .content {
                    padding: 30px;
                }
                .content p {
                    margin: 0 0 20px;
                    font-size: 16px;
                    color: #444;
                }
                .highlight {
                    font-weight: 500;
                    color: #111;
                }
                .footer {
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #888;
                    border-top: 1px solid #f0f0f0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <div class="header">
                        <h1>Your Order Has Been Delivered</h1>
                    </div>
                    <div class="content">
                        <p>Hello <span class="highlight">' . htmlspecialchars($otp_record['userName']) . '</span>,</p>
                        <p>We\'re happy to let you know that your order <span class="highlight">#' . htmlspecialchars($order_id) . '</span> has been successfully delivered!</p>
                        <p>Thank you for shopping with us. We hope you\'re enjoying your purchase.</p>
                    </div>
                    <div class="content">
                        <a href="https://eshop.xetroot.com" style="display:inline-block;padding:10px 20px;background:#4CAF50;color:#fff;text-decoration:none;border-radius:6px;font-weight:500;">Continue shopping</a>
                    </div>
                    <div class="footer">
                        <p>Thank you for choosing us!</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        $auth_user->sendMail(
            $otp_record['userEmail'],
            $message,
            "Your Order #".$order_id." Has Been Delivered"
        );
        
        echo "Delivery confirmed successfully.";
    } catch (Exception $e) {
        $auth_user->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Missing required parameters.";
}