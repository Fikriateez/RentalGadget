<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: about.php");
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

// --- LOGIK PEMBATALAN (CANCELLATION) ---
$cancel_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    $u_id = $_SESSION['user_id'];

    // 1. Ambil gadget_id dan nama gadget sebelum kemaskini untuk pulangkan stok dan hantar email
    $get_gadget = $conn->prepare("SELECT b.gadget_id, r.name AS gadget_name FROM bookings b JOIN register r ON b.gadget_id = r.id WHERE b.id = ? AND b.user_id = ? AND b.status = 'Pending'");
    $get_gadget->bind_param("ii", $cancel_id, $u_id);
    $get_gadget->execute();
    $res_gadget = $get_gadget->get_result();

    if ($res_gadget->num_rows > 0) {
        $row_b = $res_gadget->fetch_assoc();
        $g_id = $row_b['gadget_id'];
        $gadget_name = $row_b['gadget_name'];

        // Get user email for notification
        $user_email = '';
        $email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $email_stmt->bind_param("i", $u_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        if ($email_result && $email_result->num_rows > 0) {
            $user_email = $email_result->fetch_assoc()['email'];
        }
        $email_stmt->close();

        // Mulakan transaction
        $conn->begin_transaction();
        try {
            // 2. Kemas kini status booking kepada CANCEL
            $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'CANCEL' WHERE id = ?");
            $stmt_cancel->bind_param("i", $cancel_id);
            $stmt_cancel->execute();

            // 3. Pulangkan stok (+1)
            $conn->query("UPDATE register SET stock = stock + 1 WHERE id = $g_id");

            $conn->commit();

            $email_status = 'none';
            if (!empty($user_email)) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'kl2508019931@student.uptm.edu.my';
                    $mail->Password   = 'bvoqltwiytcjpjvb';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('kl2508019931@student.uptm.edu.my', 'RentalGadget');
                    $mail->addAddress($user_email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Booking Cancelled - ' . $gadget_name;
                    $mail->Body    = "<h3>Your booking has been cancelled</h3>" .
                        "<p>Your booking for <strong>" . htmlspecialchars($gadget_name) . "</strong> has been successfully cancelled.</p>" .
                        "<p>If you have already paid a deposit, please contact support for refund details.</p>";

                    $mail->send();
                    $email_status = 'sent';
                } catch (Exception $e) {
                    $email_status = 'failed';
                }
            }

            header("Location: my_rentals.php?cancelled=success&email=" . $email_status);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: my_rentals.php?cancelled=failed");
            exit;
        }
    } else {
        header("Location: my_rentals.php?cancelled=failed");
        exit;
    }
}

if (isset($_GET['cancelled']) && $_GET['cancelled'] === 'success') {
    if (isset($_GET['email']) && $_GET['email'] === 'sent') {
        $cancel_message = 'Your booking has been cancelled successfully. A notification email has been sent.';
    } elseif (isset($_GET['email']) && $_GET['email'] === 'failed') {
        $cancel_message = 'Your booking has been cancelled successfully, but email notification could not be sent.';
    } else {
        $cancel_message = 'Your booking has been cancelled successfully.';
    }
} elseif (isset($_GET['cancelled']) && $_GET['cancelled'] === 'failed') {
    $cancel_message = 'Unable to cancel the booking. Please try again or contact support.';
}

// --- AMBIL DATA LOKASI DARI SITE_SETTINGS ---
$settings_query = "SELECT location_name, map_link FROM site_settings WHERE id = 1";
$settings_result = $conn->query($settings_query);
$settings = $settings_result ? $settings_result->fetch_assoc() : null;

// Fetch user's active rentals
$user_id = $_SESSION['user_id'];
$query = "SELECT 
    b.id as booking_id,
    b.days,
    b.hours,
    b.total_price,
    b.rental_date,
    b.status,
    b.deposit_paid,
    b.deposit_status,
    b.refund_amount,
    r.id as gadget_id,
    r.name as gadget_name,
    r.specs as gadget_specs,
    r.image as gadget_image,
    r.price_day,
    r.price_hour,
    r.deposit_price as req_deposit
FROM bookings b
JOIN register r ON b.gadget_id = r.id
WHERE b.user_id = ?
ORDER BY
    CASE
        WHEN b.status = 'Pending' THEN 1
        WHEN b.status = 'Picked Up' THEN 2
        WHEN b.status = 'Returning' THEN 3
        WHEN b.status = 'CANCEL' THEN 4
        WHEN b.status = 'Completed' THEN 5
        ELSE 6
    END,
    b.rental_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rentals = [];
while ($row = $result->fetch_assoc()) {
    $rentals[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals - MemoryLens</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #0ea5e9;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-dim: #64748b;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-brand span {
            color: var(--accent);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-link {
            text-decoration: none;
            color: var(--text-dim);
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: inline-block;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-dim);
            font-size: 1rem;
        }

        .tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab-btn {
            padding: 10px 24px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-dim);
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .rentals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .rental-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .rental-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            object-position: center;
            background: #f1f5f9;
            display: block;
            border-bottom: 1px solid var(--border);
        }

        .rental-content {
            padding: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .status-returned {
            background: #ecfdf5;
            color: #059669;
        }

        .status-cancel {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-pickup {
            background: #eff6ff;
            color: #2563eb;
        }

        .status-active {
            background: #fff7ed;
            color: #ea580c;
        }

        .rental-details {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            flex: 1;
        }

        .detail-label {
            font-size: 0.7rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .location-box {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f9ff;
            border-radius: 8px;
            text-decoration: none;
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #e0f2fe;
            transition: background 0.2s;
        }

        .location-box:hover {
            background: #e0f2fe;
        }

        .deposit-info {
            background: #fffbeb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #fef3c7;
            font-size: 0.85rem;
        }

        .rental-date {
            font-size: 0.8rem;
            color: var(--text-dim);
            margin-bottom: 15px;
        }

        .btn-invoice {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            background: var(--bg-body);
            border: 1px solid var(--border);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .btn-cancel {
            width: 100%;
            padding: 10px;
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fee2e2;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-cancel:hover {
            background: #fee2e2;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="product.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-actions">
            <a href="about.php" class="nav-link">About</a>
            <a href="product.php" class="nav-link">Products</a>
            <a href="my_rentals.php" class="nav-link" style="color: var(--accent);">My Rentals</a>
            <span style="font-size: 0.9rem;">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container">
        <a href="product.php" class="back-link">← Back to Products</a>

        <div class="page-header">
            <h1>My Rentals</h1>
            <p>View and manage your rental history</p>
        </div>

        <?php if ($cancel_message): ?>
            <div style="margin-bottom: 20px; padding: 16px; border-radius: 12px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
                <?php echo htmlspecialchars($cancel_message); ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="filterRentals('all', this)">All</button>
            <button class="tab-btn" onclick="filterRentals('pending', this)">Active</button>
            <button class="tab-btn" onclick="filterRentals('completed', this)">Returned</button>
            <button class="tab-btn" onclick="filterRentals('cancelled', this)">Cancelled</button>
        </div>

        <?php if (empty($rentals)): ?>
            <div style="text-align: center; color: var(--text-dim); padding: 60px;">
                You haven't rented any gadgets yet. <br>
                <a href="product.php" style="color: var(--accent);">Browse our products</a> to rent one!
            </div>
        <?php else: ?>
            <div class="rentals-grid" id="rentalsGrid">
                <?php foreach ($rentals as $rental): ?>
                    <?php $st = strtolower(trim($rental['status'])); ?>
                    <div class="rental-card" data-status="<?php echo htmlspecialchars($st); ?>">
                        <?php
                        $imagePath = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjM1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
                        if (!empty($rental['gadget_image'])) {
                            $rawImage = $rental['gadget_image'];
                            if (strpos($rawImage, 'data:image') === 0 || strpos($rawImage, 'http://') === 0 || strpos($rawImage, 'https://') === 0) {
                                $imagePath = $rawImage;
                            } elseif (strpos($rawImage, 'uploads/') === 0) {
                                $possiblePath = '../ADMIN/php/' . $rawImage;
                                $fsPath = __DIR__ . '/../ADMIN/php/' . $rawImage;
                                if (file_exists($fsPath)) {
                                    $imagePath = $possiblePath;
                                }
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" class="rental-image" alt="Gadget">
                        <div class="rental-content">
                            <!-- Status Badge Logic -->
                            <?php if ($st == 'completed'): ?>
                                <span class="status-badge status-returned">Returned</span>
                            <?php elseif ($st == 'cancel'): ?>
                                <span class="status-badge status-cancel">Cancelled</span>
                            <?php elseif ($st == 'returning'): ?>
                                <span class="status-badge status-pickup">Awaiting Refund</span>
                            <?php elseif ($st == 'picked up'): ?>
                                <span class="status-badge status-pickup">Picked Up / In Use</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Pending Pickup</span>
                            <?php endif; ?>

                            <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($rental['gadget_name']); ?></h3>

                            <div class="rental-details">
                                <div class="detail-item">
                                    <div class="detail-label">Duration</div>
                                    <div class="detail-value">
                                        <?php
                                        $period = [];
                                        if ($rental['days'] > 0) $period[] = $rental['days'] . 'd';
                                        if ($rental['hours'] > 0) $period[] = $rental['hours'] . 'h';
                                        echo implode(' + ', $period);
                                        ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Rental Price</div>
                                    <div class="detail-value" style="color: var(--success);">RM <?php echo number_format($rental['total_price'], 2); ?></div>
                                </div>
                            </div>

                            <!-- BAHAGIAN DEPOSIT -->
                            <div class="deposit-info">
                                <?php if ($rental['deposit_status'] == 'Unpaid'): ?>
                                    <div style="color: #ef4444; font-weight: 700; text-align: center;">
                                        ⚠️ Please pay deposit RM <?php echo number_format($rental['req_deposit'], 2); ?> at counter
                                    </div>
                                <?php else: ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <span style="color: var(--text-dim);">Deposit Paid:</span>
                                        <span style="font-weight: 600; color: #10b981;">RM <?php echo number_format($rental['deposit_paid'], 2); ?></span>
                                    </div>
                                    <?php if ($st == 'completed'): ?>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                            <span style="color: var(--text-dim);">Refunded:</span>
                                            <span style="font-weight: 600; color: var(--accent);">RM <?php echo number_format($rental['refund_amount'], 2); ?></span>
                                        </div>
                                        <?php if ($rental['deposit_paid'] > $rental['refund_amount']): ?>
                                            <div style="display: flex; justify-content: space-between; margin-top: 5px; padding-top: 5px; border-top: 1px solid #fde68a;">
                                                <span style="color: var(--danger); font-weight: 600;">Deduction:</span>
                                                <span style="color: var(--danger); font-weight: 600;">- RM <?php echo number_format($rental['deposit_paid'] - $rental['refund_amount'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="rental-date">
                                Rented on: <?php echo date('M d, Y', strtotime($rental['rental_date'])); ?>
                            </div>

                            <?php if ($settings && $st != 'completed'): ?>
                                <a href="<?php echo htmlspecialchars($settings['map_link']); ?>" target="_blank" class="location-box">
                                    📍 Pickup: <?php echo htmlspecialchars($settings['location_name']); ?>
                                </a>
                            <?php endif; ?>

                            <a href="generate_invoice.php?booking_id=<?php echo $rental['booking_id']; ?>" class="btn-invoice">Download Invoice</a>

                            <!-- BUTANG CANCELLATION -->
                            <?php if ($st == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $rental['booking_id']; ?>">
                                    <button type="submit" class="btn-cancel">Cancel Booking</button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterRentals(filterValue, btn) {
            // Update active button
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('.rental-card');
            cards.forEach(card => {
                const status = card.dataset.status.toLowerCase().trim();
                if (filterValue === 'all') {
                    card.style.display = 'block';
                } else if (filterValue === 'pending') {
                    // Active (Pending, Picked Up, Returning)
                    if (status === 'pending' || status === 'picked up' || status === 'returning') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                } else if (filterValue === 'completed') {
                    // Completed only
                    if (status === 'completed') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                } else if (filterValue === 'cancelled') {
                    if (status === 'cancel') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }

        // Initialize filter on page load - show All by default
        window.addEventListener('DOMContentLoaded', function() {
            filterRentals('all', document.querySelector('.tab-btn.active'));
        });
    </script>
</body>

</html>