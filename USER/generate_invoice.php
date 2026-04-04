<?php
session_start();

// Semak jika user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil booking_id dari URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$user_id = $_SESSION['user_id'];

// Query untuk ambil data lengkap (Booking + User + Gadget)
// Kita pastikan user_id sepadan supaya user tidak boleh tengok invois orang lain
$query = "SELECT 
    b.*, 
    u.name as user_name, u.email as user_email, u.phone as user_phone,
    r.name as gadget_name, r.specs as gadget_specs, r.price_day, r.price_hour
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN register r ON b.gadget_id = r.id
WHERE b.id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Invoice not found or you do not have permission to view it.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $data['id']; ?> - MemoryLens</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text-main: #0f172a;
            --text-dim: #64748b;
            --accent: #0ea5e9;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background-color: #f1f5f9;
            padding: 40px 20px;
            line-height: 1.5;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .brand h1 { color: var(--accent); font-size: 24px; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { font-size: 20px; text-transform: uppercase; margin-bottom: 5px; }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-title {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            color: var(--text-dim);
            margin-bottom: 10px;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--border);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .total-row {
            text-align: right;
        }

        .total-amount {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: var(--text-dim);
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .no-print-zone {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }

        .btn-print { background: var(--accent); color: white; }
        .btn-back { background: var(--text-dim); color: white; }

        @media print {
            body { background: white; padding: 0; }
            .invoice-box { box-shadow: none; border: none; max-width: 100%; }
            .no-print-zone { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print-zone">
        <a href="my_rentals.php" class="btn btn-back">← Back to Rentals</a>
        <button onclick="window.print()" class="btn btn-print">Print / Save as PDF</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="brand">
                <h1>Memory<span>Lens</span></h1>
                <p>Premium Gadget Rental Service</p>
            </div>
            <div class="invoice-details">
                <h2>Invoice</h2>
                <p>#INV-<?php echo str_pad($data['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p>Date: <?php echo date('d M Y', strtotime($data['rental_date'])); ?></p>
            </div>
        </div>

        <div class="info-section">
            <div>
                <span class="info-title">Billed To:</span>
                <strong><?php echo htmlspecialchars($data['user_name']); ?></strong><br>
                Email: <?php echo htmlspecialchars($data['user_email']); ?><br>
                Phone: <?php echo htmlspecialchars($data['user_phone']); ?>
            </div>
            <div style="text-align: right;">
                <span class="info-title">Status:</span>
                <strong style="color: <?php echo ($data['status'] == 'Completed') ? '#10b981' : '#f59e0b'; ?>">
                    <?php echo ucfirst($data['status']); ?>
                </strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Rate</th>
                    <th>Duration</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($data['gadget_name']); ?></strong><br>
                        <small style="color: var(--text-dim);"><?php echo htmlspecialchars($data['gadget_specs']); ?></small>
                    </td>
                    <td>
                        RM <?php echo number_format($data['price_day'], 2); ?>/d<br>
                        RM <?php echo number_format($data['price_hour'], 2); ?>/h
                    </td>
                    <td>
                        <?php 
                            if($data['days'] > 0) echo $data['days'] . " Day(s) ";
                            if($data['hours'] > 0) echo $data['hours'] . " Hour(s)";
                        ?>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        RM <?php echo number_format($data['total_price'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-row">
            <p style="font-size: 14px; color: var(--text-dim);">Rental Fee: RM <?php echo number_format($data['total_price'], 2); ?></p>
            
            <!-- TAMBAHAN CODING DEPOSIT -->
            <p style="font-size: 14px; color: var(--text-dim);">Security Deposit Paid: RM <?php echo number_format($data['deposit_paid'], 2); ?></p>

            <!-- LOGIK REFUND & DEDUCTION -->
            <?php if (strtolower($data['status']) == 'completed'): ?>
                <p style="font-size: 14px; color: #10b981;">Amount Refunded: RM <?php echo number_format($data['refund_amount'], 2); ?></p>
                <?php 
                $deduction = $data['deposit_paid'] - $data['refund_amount'];
                if ($deduction > 0): ?>
                    <p style="font-size: 14px; color: #ef4444;">Deduction (Damage/Late): - RM <?php echo number_format($deduction, 2); ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <div style="border-top: 1px solid var(--border); margin: 10px 0; width: 250px; margin-left: auto;"></div>
            
            <p style="font-size: 14px; color: var(--text-dim);">Total Amount Due</p>
            <p class="total-amount">RM <?php echo number_format($data['total_price'], 2); ?></p>
        </div>

        <div class="footer">
            <p>Thank you for choosing MemoryLens!</p>
            <p>Please bring this invoice along with your IC during device pickup/return.</p>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>