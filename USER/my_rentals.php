<?php
session_start();

// Check if user is logged in
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

// --- LOGIK PEMBATALAN (CANCELLATION) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    $u_id = $_SESSION['user_id'];

    // 1. Ambil gadget_id sebelum delete untuk pulangkan stok
    $get_gadget = $conn->prepare("SELECT gadget_id FROM bookings WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $get_gadget->bind_param("ii", $cancel_id, $u_id);
    $get_gadget->execute();
    $res_gadget = $get_gadget->get_result();

    if ($res_gadget->num_rows > 0) {
        $row_b = $res_gadget->fetch_assoc();
        $g_id = $row_b['gadget_id'];

        // Mulakan transaction
        $conn->begin_transaction();
        try {
            // 2. Padam tempahan
            $del = $conn->prepare("DELETE FROM bookings WHERE id = ?");
            $del->bind_param("i", $cancel_id);
            $del->execute();

            // 3. Tambah semula stok (+1)
            $conn->query("UPDATE register SET stock = stock + 1 WHERE id = $g_id");

            $conn->commit();
            header("Location: my_rentals.php?cancelled=success");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
}

// --- TAMBAHAN: AMBIL DATA LOKASI DARI SITE_SETTINGS ---
$settings_query = "SELECT location_name, map_link FROM site_settings WHERE id = 1";
$settings_result = $conn->query($settings_query);
$settings = $settings_result->fetch_assoc();

// Fetch user's active rentals
$user_id = $_SESSION['user_id'];
// --- KEMASKINI QUERY: Tambah deposit_status dan req_deposit (harga yang patut dibayar) ---
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
ORDER BY b.rental_date DESC";

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
    <title>My Rentals - RentalGadget</title>
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
            gap: 15px;
        }

        .nav-link {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--accent);
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
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
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .tab-btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text-dim);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .rentals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .rental-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* BAHAGIAN KEMASKINI GAMBAR SUPAYA SERAGAM */
        .rental-image {
            width: 100%;
            height: auto;
            min-height: 250px;
            max-height: 350px;
            object-fit: contain;
            object-position: center;
            background: #f1f5f9;
            display: block;
            border-bottom: 1px solid var(--border);
        }

        .rental-content {
            padding: 20px;
            flex-grow: 1;
        }

        .rental-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .rental-specs {
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .rental-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-item {
            background: #f8fafc;
            padding: 10px;
            border-radius: 8px;
        }

        .detail-label {
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-bottom: 2px;
        }

        .detail-value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .rental-date {
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .status-active {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pickup {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-returned {
            background: #dcfce7;
            color: #166534;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Style untuk Lokasi */
        .location-box {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
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

        .btn-invoice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: transparent;
            color: var(--accent);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-invoice:hover {
            background: var(--accent);
            color: white;
        }

        /* GAYA BUTANG CANCEL */
        .btn-cancel {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 8px;
            border: 1px solid var(--danger);
            background: transparent;
            color: var(--danger);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: white;
        }

        /* Style untuk bahagian deposit */
        .deposit-info {
            background: #fffbeb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #fef3c7;
            font-size: 0.85rem;
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
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container">
        <a href="product.php" class="back-link">← Back to Products</a>

        <div class="page-header">
            <h1>My Rentals</h1>
            <p>View and manage your rental history</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="filterRentals('all')">All</button>
            <button class="tab-btn" onclick="filterRentals('active')">Active</button>
            <button class="tab-btn" onclick="filterRentals('completed')">Returned</button>
        </div>

        <?php if (empty($rentals)): ?>
            <div class="no-rentals" style="text-align: center; color: var(--text-dim); padding: 60px;">
                You haven't rented any gadgets yet. <br>
                <a href="product.php" style="color: var(--accent);">Browse our products</a> to rent one!
            </div>
        <?php else: ?>
            <div class="rentals-grid" id="rentalsGrid">
                <?php foreach ($rentals as $rental): ?>
                    <?php $st = strtolower($rental['status']); ?>
                    <div class="rental-card" data-status="<?php echo $st; ?>">
                        <?php
                        $imageData = trim($rental['gadget_image'] ?? '');
                        if ($imageData === '') {
                            $imageSrc = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjI4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjFmNWY5Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5Y2ExMDYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                        } elseif (strpos($imageData, 'data:image') === 0) {
                            $imageSrc = $imageData;
                        } else {
                            $imageSrc = '../ADMIN/php/' . htmlspecialchars($imageData, ENT_QUOTES);
                        }
                        ?>
                        <img class="rental-image" src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($rental['gadget_name']); ?>">
                        <div class="rental-content">
                            <!-- Status Badge Logic -->
                            <?php if ($st == 'completed'): ?>
                                <span class="status-badge status-returned">Returned</span>
                            <?php elseif ($st == 'picked up'): ?>
                                <span class="status-badge status-pickup">Picked Up / In Use</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Pending Pickup</span>
                            <?php endif; ?>

                            <div class="rental-name"><?php echo htmlspecialchars($rental['gadget_name']); ?></div>
                            <div class="rental-specs"><?php echo htmlspecialchars($rental['gadget_specs'] ?: 'No specifications'); ?></div>

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

                            <!-- BAHAGIAN DEPOSIT (LOGIK UNPAID VS PAID) -->
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

                            <?php if ($settings): ?>
                                <a href="<?php echo htmlspecialchars($settings['map_link']); ?>" target="_blank" class="location-box">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    Pickup: <?php echo htmlspecialchars($settings['location_name']); ?>
                                </a>
                            <?php endif; ?>

                            <a href="generate_invoice.php?booking_id=<?php echo $rental['booking_id']; ?>" class="btn-invoice" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Download Invoice
                            </a>

                            <!-- BUTANG CANCELLATION BARU -->
                            <?php if ($st == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $rental['booking_id']; ?>">
                                    <button type="submit" class="btn-cancel">
                                        Cancel Booking
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterRentals(filterValue) {
            const cards = document.querySelectorAll('.rental-card');
            const buttons = document.querySelectorAll('.tab-btn');

            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            cards.forEach(card => {
                const status = card.dataset.status.toLowerCase();
                if (filterValue === 'all') {
                    card.style.display = 'block';
                } else if (filterValue === 'active') {
                    if (status !== 'completed') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                } else if (filterValue === 'completed') {
                    if (status === 'completed') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>

</html>