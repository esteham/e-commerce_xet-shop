<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$user = new USER();

if(!$user->is_logged_in()) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

if(isset($_POST['email'])) {
    $email = $_POST['email'];

    $subject = "🎉 Your Order Confirmation - Spider Monkey";

    $message = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Confirmation</title>
        <style>
            body {
                font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                text-align: center;
                padding: 20px 0;
            }
            .logo {
                max-width: 150px;
            }
            .content {
                background-color: #f9f9f9;
                border-radius: 8px;
                padding: 25px;
                margin: 20px 0;
            }
            .button {
                display: inline-block;
                background-color: #4CAF50;
                color: white !important;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
                margin: 15px 0;
            }
            .footer {
                text-align: center;
                font-size: 14px;
                color: #777;
                margin-top: 30px;
            }
            hr {
                border: 0;
                height: 1px;
                background-color: #e0e0e0;
                margin: 25px 0;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="https://example.com/logo.png" alt="Spider Monkey" class="logo">
            <h2>Thank you for your order!</h2>
        </div>
        
        <div class="content">
            <p>Hello there,</p>
            <p>We\'re excited to let you know that we\'ve received your order and it\'s now being processed.</p>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Our team is preparing your items with care</li>
                <li>You\'ll receive a shipping confirmation soon</li>
                <li>Estimated delivery: 3-5 business days</li>
            </ul>
            
            <p>Need help or have questions? We\'re here for you!</p>
            <a href="https://example.com/contact" class="button">Contact Support</a>
        </div>
        
        <div class="footer">
            <hr>
            <p>© '.date('Y').' Spider Monkey. All rights reserved.</p>
            <p>123 Store Street, City, Country</p>
        </div>
    </body>
    </html>';

    if($user->sendMail($email, $message, $subject)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failed']);
    }
} else {
    echo json_encode(['status' => 'invalid request']);
}
?>