<?php
/**
 * HARAMAYA PHARMA - Receipt Printing
 */

$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();
require_login();

$sale_id = (int)($_GET['sale_id'] ?? 0);

if (!$sale_id) {
    die('Invalid sale ID');
}

// Get sale details
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as cashier_name
    FROM sales s
    INNER JOIN users u ON s.cashier_id = u.user_id
    WHERE s.sale_id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$stmt = $pdo->prepare("
    SELECT si.*, p.product_name, p.generic_name, sb.batch_number
    FROM sale_items si
    INNER JOIN products p ON si.product_id = p.product_id
    INNER JOIN stock_batches sb ON si.batch_id = sb.batch_id
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo clean($sale['sale_number']); ?></title>
    <link rel="icon" type="image/jpeg" href="../../assets/images/favicon.jpg">
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .receipt-info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .receipt-info div {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        th {
            border-bottom: 1px solid #000;
            padding: 5px 0;
            text-align: left;
        }
        td {
            padding: 5px 0;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            border-top: 2px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #000;
            font-size: 11px;
        }
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <img src="../../assets/images/image.jpg" alt="Haramaya Pharma" 
             style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px; border-radius: 8px;"
             onerror="this.style.display='none';">
        <h1>HARAMAYA PHARMA</h1>
        <p style="margin: 5px 0;">Professional Pharmacy Services</p>
        <p style="margin: 5px 0;">Harar, Ethiopia</p>
        <p style="margin: 5px 0;">Tel: +251-900-000000</p>
        <p style="margin: 5px 0;">TIN: 123456789</p>
        <p style="margin: 10px 0 5px 0; font-weight: bold;">SALES RECEIPT</p>
    </div>
    
    <div class="receipt-info">
        <div>
            <span>Receipt #:</span>
            <strong><?php echo clean($sale['sale_number']); ?></strong>
        </div>
        <div>
            <span>Date:</span>
            <span><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></span>
        </div>
        <div>
            <span>Cashier:</span>
            <span><?php echo clean($sale['cashier_name']); ?></span>
        </div>
        <?php if ($sale['customer_name']): ?>
        <div>
            <span>Customer:</span>
            <span><?php echo clean($sale['customer_name']); ?></span>
        </div>
        <?php endif; ?>
        <div>
            <span>Payment:</span>
            <span><?php echo strtoupper(clean($sale['payment_method'])); ?></span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php echo clean($item['product_name']); ?><br>
                    <small style="color: #666;">Batch: <?php echo clean($item['batch_number']); ?></small>
                </td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-right"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals">
        <div>
            <span>Subtotal:</span>
            <span>ETB <?php echo number_format($sale['subtotal'], 2); ?></span>
        </div>
        <div>
            <span>Tax (15%):</span>
            <span>ETB <?php echo number_format($sale['tax'], 2); ?></span>
        </div>
        <?php if ($sale['discount'] > 0): ?>
        <div>
            <span>Discount:</span>
            <span>ETB <?php echo number_format($sale['discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="grand-total">
            <span>TOTAL:</span>
            <span>ETB <?php echo number_format($sale['total_amount'], 2); ?></span>
        </div>
        <div style="font-size: 14px; margin: 5px 0;">
            <span>Amount Paid:</span>
            <span>ETB <?php echo number_format($sale['amount_paid'], 2); ?></span>
        </div>
        <div style="font-size: 14px; margin: 5px 0; <?php echo $sale['change_amount'] > 0 ? 'font-weight: bold;' : ''; ?>">
            <span>Change:</span>
            <span>ETB <?php echo number_format($sale['change_amount'], 2); ?></span>
        </div>
    </div>
    
    <div class="receipt-footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>Please keep this receipt for your records</p>
        <p>VAT Included | All sales are final</p>
        <p style="margin-top: 10px;">Powered by Haramaya Pharma System</p>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" style="padding: 12px 24px; font-size: 16px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 5px; margin: 5px;">
            🖨️ Print Receipt
        </button>
        <button onclick="window.location='pos.php'" style="padding: 12px 24px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; margin: 5px;">
            ← New Sale
        </button>
        <button onclick="window.location='history.php'" style="padding: 12px 24px; font-size: 16px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 5px; margin: 5px;">
            📋 Sales History
        </button>
    </div>
    
    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); }
        
        // Print shortcut
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
