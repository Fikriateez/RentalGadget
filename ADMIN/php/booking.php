<?php
session_start();

// Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- LOGIK 1: Sahkan Deposit Telah Dibayar ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_deposit'])) {
    $booking_id = intval($_POST['booking_id']);

    // Ambil harga deposit asal dari table register melalui gadget_id
    $query_price = "SELECT r.deposit_price FROM bookings b JOIN register r ON b.gadget_id = r.id WHERE b.id = ?";
    $stmt_price = $conn->prepare($query_price);
    $stmt_price->bind_param("i", $booking_id);
    $stmt_price->execute();
    $deposit_val = $stmt_price->get_result()->fetch_assoc()['deposit_price'];

    $stmt = $conn->prepare("UPDATE bookings SET deposit_paid = ?, deposit_status = 'Paid' WHERE id = ?");
    $stmt->bind_param("di", $deposit_val, $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=paid");
    exit();
}

// --- LOGIK 2: Handle Confirm Pickup ---
function tableHasColumn($conn, $table, $column)
{
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $res && $res->num_rows > 0;
}

$hasActualReturn = tableHasColumn($conn, 'bookings', 'actual_return_time');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_pickup'])) {
    $booking_id = intval($_POST['booking_id']);
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Picked Up' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=pickedup");
    exit();
}

// --- LOGIK 3: Handle Mark as Returned (DENGAN PENGIRAAN DENDA LEWAT) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_returned'])) {
    $booking_id = intval($_POST['booking_id']);

    // Ambil data deadline dan harga per jam untuk peranti ini
    $q = "SELECT b.return_deadline, r.price_hour FROM bookings b 
          JOIN register r ON b.gadget_id = r.id WHERE b.id = $booking_id";
    $data = $conn->query($q)->fetch_assoc();

    // Get business hours for late fee calculation
    $biz_hours = $conn->query("SELECT mon_hours FROM site_settings LIMIT 1");
    $default_return_hour = 21; // 9 PM default
    $default_return_min = 0;
    if ($biz_hours && $biz_hours->num_rows > 0) {
        $biz_row = $biz_hours->fetch_assoc();
        $biz_str = $biz_row['mon_hours'];
        if (strpos($biz_str, ' - ') !== false) {
            list($start, $end) = explode(' - ', $biz_str);
            $end = trim(strtoupper($end));
            $end_obj = DateTime::createFromFormat('g:iA', str_replace(' ', '', $end)) ?: DateTime::createFromFormat('gA', $end);
            if ($end_obj) {
                $default_return_hour = (int)$end_obj->format('H');
                $default_return_min = (int)$end_obj->format('i');
            }
        }
    }

    $late_fee = 0; // Initialize to 0 (no late fee by default)
    if (!empty($data['return_deadline'])) {
        try {
            $deadline = new DateTime($data['return_deadline']);
            $deadlineTime = $deadline->format('H:i');
            
            // If deadline time is 00:00, use business hours default end time
            if ($deadlineTime === '00:00') {
                $deadline->setTime($default_return_hour, $default_return_min, 0);
            }
            
            $now = new DateTime(); // Waktu admin tekan butang
            $seconds_late = $now->getTimestamp() - $deadline->getTimestamp();

            if ($seconds_late > 0) {
                $hours_late = (int) ceil($seconds_late / 3600);
                $late_fee = $hours_late * $data['price_hour'];
            }
        } catch (Exception $e) {
            $late_fee = 0;
        }
    }

    if ($hasActualReturn) {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Returning', late_fee = ?, actual_return_time = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Returning', late_fee = ? WHERE id = ?");
    }
    $stmt->bind_param("di", $late_fee, $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=returning");
    exit();
}

// --- LOGIK 4: Handle Proses Pemulangan & Refund Deposit (Final Step) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_return'])) {
    $booking_id = intval($_POST['complete_booking_id']);
    $refund_amount = floatval($_POST['refund_amount']);

    $conn->begin_transaction();

    try {
        // 1. Dapatkan gadget_id
        $query_get = "SELECT gadget_id FROM bookings WHERE id = ?";
        $stmt_get = $conn->prepare($query_get);
        $stmt_get->bind_param("i", $booking_id);
        $stmt_get->execute();
        $gadget_id = $stmt_get->get_result()->fetch_assoc()['gadget_id'];

        // 2. Kemaskini status & simpan amaun refund
        $stmt_update = $conn->prepare("UPDATE bookings SET status = 'Completed', refund_amount = ? WHERE id = ?");
        $stmt_update->bind_param("di", $refund_amount, $booking_id);
        $stmt_update->execute();

        // 3. Tambah stok peranti semula (+1)
        $conn->query("UPDATE register SET stock = stock + 1 WHERE id = $gadget_id");

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=completed");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Ralat proses pemulangan: " . $e->getMessage());
    }
}

// Fetch all bookings
$query = "SELECT
    b.id,
    b.days,
    b.hours,
    b.total_price,
    b.rental_date,
    b.return_deadline,
    b.status, 
    b.deposit_paid,
    b.deposit_status,
    b.late_fee" . ($hasActualReturn ? ",\n    b.actual_return_time" : "") . ",\n    u.name as user_name,\n    u.email as user_email,\n    r.name as gadget_name,\n    r.deposit_price as req_deposit\nFROM bookings b\nJOIN users u ON b.user_id = u.id\nJOIN register r ON b.gadget_id = r.id\nORDER BY b.id ASC";

// Fetch business hours for display fallback
$business_hours = $conn->query("SELECT mon_hours FROM site_settings LIMIT 1");
$default_start_time = "09:00";
$default_end_time = "21:00";
if ($business_hours && $business_hours->num_rows > 0) {
    $hours_row = $business_hours->fetch_assoc();
    $hours_str = $hours_row['mon_hours'];
    if (strpos($hours_str, ' - ') !== false) {
        list($start, $end) = explode(' - ', $hours_str);
        $start = trim(strtoupper($start));
        $end = trim(strtoupper($end));
        
        // Convert AM/PM format to 24-hour format
        $start_obj = DateTime::createFromFormat('g:iA', str_replace(' ', '', $start)) ?: DateTime::createFromFormat('gA', $start);
        $end_obj = DateTime::createFromFormat('g:iA', str_replace(' ', '', $end)) ?: DateTime::createFromFormat('gA', $end);
        
        if ($start_obj) $default_start_time = $start_obj->format('H:i');
        if ($end_obj) $default_end_time = $end_obj->format('H:i');
    }
}

// Helper function to format datetime with business hours fallback
function formatDateTimeWithBusinessHours($dateStr, $isReturnTime = false, $defaultStart = "09:00", $defaultEnd = "18:00") {
    $time = new DateTime($dateStr);
    $timeOnly = $time->format('H:i');
    
    // If time is 00:00 (midnight/empty), use business hours
    if ($timeOnly === '00:00') {
        $displayTime = $isReturnTime ? $defaultEnd : $defaultStart;
    } else {
        $displayTime = $timeOnly;
    }
    
    return date('M d, Y', strtotime($dateStr)) . ' ' . $displayTime;
}

$result = $conn->query($query);
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bookings Management</title>
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

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-dim);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .nav-link.active {
            color: var(--accent);
        }

        .btn-outline {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .bookings-table {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--bg-body);
            font-size: 0.875rem;
            text-transform: uppercase;
            color: var(--text-dim);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pickup {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-returning {
            background: #f1f5f9;
            color: #475569;
        }

        .status-cancel {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-complete {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-complete:hover {
            background-color: #0284c7;
        }

        .returned-text {
            color: var(--text-dim);
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="register.html" class="nav-link">Register</a>
            <a href="booking.php" class="nav-link active">Bookings</a>
            <a href="customer.php" class="nav-link">Customers</a>
            <a href="admin_settings.php" class="nav-link">Settings</a>
        </div>
        <div class="nav-actions">
            <a href="logout.php" class="btn-outline" style="text-decoration: none;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Bookings Management</h1>
            <p>Admin Control Panel</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">RM <?php
                                            $total_revenue = 0;
                                            foreach ($bookings as $booking) {
                                                $total_revenue += $booking['total_price'];
                                            }
                                            echo number_format($total_revenue, 2);
                                            ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="bookings-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Gadget</th>
                        <th>Duration</th>
                        <th>Total Price</th>
                        <th>Pickup</th>
                        <th>Expected Return</th>
                        <?php if ($hasActualReturn): ?>
                            <th>Actual Return</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($booking['user_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($booking['gadget_name']); ?></td>
                            <td>
                                <?php 
                                $duration = "";
                                if ($booking['days'] > 0) {
                                    $duration .= $booking['days'] . " day" . ($booking['days'] != 1 ? "s" : "");
                                }
                                if ($booking['hours'] > 0) {
                                    if ($duration) $duration .= " ";
                                    $duration .= $booking['hours'] . " hour" . ($booking['hours'] != 1 ? "s" : "");
                                }
                                echo $duration ? $duration : "No duration";
                                ?>
                            </td>
                            <td style="color: var(--success); font-weight: 600;">
                                RM <?php echo number_format($booking['total_price'], 2); ?>
                            </td>
                            <td><?php echo formatDateTimeWithBusinessHours($booking['rental_date'], false, $default_start_time, $default_end_time); ?></td>
                            <td><?php echo formatDateTimeWithBusinessHours($booking['return_deadline'], true, $default_start_time, $default_end_time); ?></td>
                            <?php if ($hasActualReturn): ?>
                                <td><?php echo $booking['actual_return_time'] ? formatDateTimeWithBusinessHours($booking['actual_return_time'], true, $default_start_time, $default_end_time) : '-'; ?></td>
                            <?php endif; ?>
                            <td>
                                <?php
                                $current_status = strtolower(trim($booking['status']));
                                if ($current_status == 'completed') {
                                    echo '<span class="status-badge status-completed">Returned</span>';
                                } elseif ($current_status == 'cancel' || $current_status == 'cancelled') {
                                    echo '<span class="status-badge status-cancel">Cancelled</span>';
                                } elseif ($current_status == 'returning') {
                                    echo '<span class="status-badge status-returning">Awaiting Refund</span>';
                                } elseif ($current_status == 'picked up') {
                                    echo '<span class="status-badge status-pickup">Picked Up</span>';
                                } else {
                                    echo '<span class="status-badge status-pending">Pending</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($current_status == 'cancel' || $current_status == 'cancelled'): ?>
                                    <span class="returned-text">Booking was cancelled by user</span>
                                <?php elseif ($booking['deposit_status'] == 'Unpaid'): ?>
                                    <!-- LANGKAH 1: SAHKAN BAYARAN DEPOSIT -->
                                    <form method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="confirm_deposit" class="btn-complete" style="background-color: #8b5cf6;">
                                            Confirm Deposit
                                        </button>
                                    </form>

                                <?php elseif ($current_status == 'pending' || $current_status == ''): ?>
                                    <!-- LANGKAH 2: SAHKAN PICKUP -->
                                    <form method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="confirm_pickup" class="btn-complete" style="background-color: var(--warning);">
                                            Confirm Pickup
                                        </button>
                                    </form>

                                <?php elseif ($current_status == 'picked up'): ?>
                                    <!-- LANGKAH 3: ADMIN TERIMA BARANG -->
                                    <form method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="mark_returned" class="btn-complete" style="background-color: #6366f1;">
                                            Mark as Returned
                                        </button>
                                    </form>

                                <?php elseif ($current_status == 'returning'): ?>
                                    <!-- LANGKAH TERAKHIR: DAMAGE CHECK + LATE FEE CHECK -->
                                    <form method="POST" onsubmit="return confirm('Sahkan amaun refund?');">
                                        <input type="hidden" name="complete_booking_id" value="<?php echo $booking['id']; ?>">

                                        <div style="margin-bottom: 5px;">
                                            <small>Paid Deposit: <strong>RM <?php echo number_format($booking['deposit_paid'], 2); ?></strong></small><br>
                                            <?php if ($booking['late_fee'] > 0): ?>
                                                <small style="color:red;">Late Fee: <strong>RM <?php echo number_format($booking['late_fee'], 2); ?></strong></small>
                                            <?php else: ?>
                                                <small style="color:green;">✓ Returned on time (No late fees)</small>
                                            <?php endif; ?>
                                        </div>

                                        <?php
                                        // Cadangkan refund = Deposit - Late Fee
                                        $suggested = $booking['deposit_paid'] - $booking['late_fee'];
                                        if ($suggested < 0) $suggested = 0;
                                        ?>

                                        <label style="font-size: 10px; display: block;">Refund Amount (RM):</label>
                                        <input type="number" name="refund_amount"
                                            value="<?php echo $suggested; ?>"
                                            max="<?php echo $booking['deposit_paid']; ?>"
                                            step="0.01"
                                            required
                                            style="width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 5px;">
                                        <small style="color: var(--text-dim); display: block; margin-bottom: 5px;">
                                            <?php
                                            if ($booking['late_fee'] > 0) {
                                                echo "Deduction for late return: RM " . number_format($booking['late_fee'], 2);
                                            } else {
                                                echo "Full deposit to be refunded (early or on-time return)";
                                            }
                                            ?>
                                        </small>

                                        <button type="submit" name="process_return" class="btn-complete" style="width: 100%; background-color: var(--success);">
                                            Finalize Refund
                                        </button>
                                    </form>

                                <?php elseif ($current_status == 'completed'): ?>
                                    <span class="returned-text">Transaction Finished</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>