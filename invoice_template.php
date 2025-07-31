<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order_id ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <h1>INVOICE</h1>
            <p>Order #<?= $order_id ?> | Date: <?= date('d/m/Y', strtotime($order['order_date'])) ?></p>
        </div>

        <h3>Bill To:</h3>
        <p>
            <?= htmlspecialchars($order['customer_name']) ?><br>
            Phone: <?= htmlspecialchars($order['customer_phone']) ?><br>
            Address: <?= htmlspecialchars($order['customer_address']) ?>
        </p>

        <table>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td>$<?= number_format($item['unit_price'], 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="3">Grand Total</td>
                <td>$<?= number_format($order['total_amount'], 2) ?></td>
            </tr>
        </table>
    </div>
</body>
</html>