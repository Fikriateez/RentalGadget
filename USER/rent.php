<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get gadget ID from URL
$gadget_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($gadget_id <= 0) {
    die("Invalid gadget ID");
}

// Fetch gadget details
$stmt = $conn->prepare("SELECT * FROM register WHERE id = ?");
$stmt->bind_param("i", $gadget_id);
$stmt->execute();
$result = $stmt->get_result();
$gadget = $result->fetch_assoc();
$stmt->close();

if (!$gadget) {
    die("Gadget not found");
}

// Handle rental submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data tarikh dan masa
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Kira jumlah hari
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $intervalDays = $date1->diff($date2);
    $days = $intervalDays->days;

    // Kira jumlah jam (berdasarkan beza masa)
    $time1 = new DateTime($start_time);
    $time2 = new DateTime($end_time);
    $intervalHours = $time1->diff($time2);
<<<<<<< HEAD
    $hours = $intervalHours->h + ($intervalHours->i / 60); 
    
=======
    $hours = $intervalHours->h + ($intervalHours->i / 60);

>>>>>>> 45019c6 (commit)
    // Validasi ringkas
    if ($date2 < $date1) {
        $message = "End date cannot be earlier than start date.";
    } elseif ($days == 0 && $time2 <= $time1) {
        $message = "End time must be later than start time for same-day rental.";
    } elseif ($gadget['stock'] <= 0) {
        $message = "Sorry, this gadget is out of stock.";
    } else {
        // 1. Calculate total price (Harga sewa sahaja)
        $total_price = ($days * $gadget['price_day']) + ($hours * $gadget['price_hour']);
<<<<<<< HEAD
        
        // 2. LOGIK DEPOSIT: Set 0.00 & Unpaid (Admin akan sahkan di kaunter)
        $deposit_paid = 0.00; 
        $deposit_status = "Unpaid";
        $status = "Pending"; 
        
        // 3. LOGIK DEADLINE: Gabungkan end_date dan end_time untuk simpan waktu akhir sepatutnya
        $return_deadline = $end_date . ' ' . $end_time . ':00';
        
        // 4. QUERY INSERT (Pastikan kolum return_deadline ada dalam table bookings)
        $user_id = $_SESSION['user_id'];
        $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, gadget_id, days, hours, total_price, rental_date, return_deadline, status, deposit_paid, deposit_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // i = integer, d = double, s = string
        // Urutan: user_id(i), gadget_id(i), days(i), hours(i), total_price(d), start_date(s), return_deadline(s), status(s), deposit_paid(d), deposit_status(s)
        $booking_stmt->bind_param("iiiidsssds", $user_id, $gadget_id, $days, $hours, $total_price, $start_date, $return_deadline, $status, $deposit_paid, $deposit_status);
        
=======

        // 2. LOGIK DEPOSIT: Set 0.00 & Unpaid (Admin akan sahkan di kaunter)
        $deposit_paid = 0.00;
        $deposit_status = "Unpaid";
        $status = "Pending";

        // 3. LOGIK DEADLINE: Gabungkan end_date dan end_time untuk simpan waktu akhir sepatutnya
        $return_deadline = $end_date . ' ' . $end_time . ':00';

        // 4. QUERY INSERT (Pastikan kolum return_deadline ada dalam table bookings)
        $user_id = $_SESSION['user_id'];
        $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, gadget_id, days, hours, total_price, rental_date, return_deadline, status, deposit_paid, deposit_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // i = integer, d = double, s = string
        // Urutan: user_id(i), gadget_id(i), days(i), hours(i), total_price(d), start_date(s), return_deadline(s), status(s), deposit_paid(d), deposit_status(s)
        $booking_stmt->bind_param("iiiidsssds", $user_id, $gadget_id, $days, $hours, $total_price, $start_date, $return_deadline, $status, $deposit_paid, $deposit_status);

>>>>>>> 45019c6 (commit)
        if ($booking_stmt->execute()) {
            // Fetch User Email
            $user_email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $user_email_stmt->bind_param("i", $user_id);
            $user_email_stmt->execute();
            $user_result = $user_email_stmt->get_result();
            if ($user_result && $user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $user_email = $user_data['email'];
            } else {
                $user_email = '';
            }
            $user_email_stmt->close();

            // Reduce stock
            $new_stock = $gadget['stock'] - 1;
            $update_stmt = $conn->prepare("UPDATE register SET stock = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_stock, $gadget_id);
            $update_stmt->execute();
            $update_stmt->close();
<<<<<<< HEAD
            
=======

>>>>>>> 45019c6 (commit)
            $message = "Rental successful! Booking from $start_date to $end_date recorded. Total: RM " . number_format($total_price, 2) . ". Please pay the deposit at the counter.";
            $gadget['stock'] = $new_stock;

            // Send Email Notification
            if (!empty($user_email)) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
<<<<<<< HEAD
                    $mail->Username   = 'kl2508019931@student.uptm.edu.my'; 
                    $mail->Password   = 'bvoqltwiytcjpjvb'; 
=======
                    $mail->Username   = 'kl2508019931@student.uptm.edu.my';
                    $mail->Password   = 'bvoqltwiytcjpjvb';
>>>>>>> 45019c6 (commit)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('kl2508019931@student.uptm.edu.my', 'RentalGadget');
                    $mail->addAddress($user_email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Rental Confirmation - ' . $gadget['name'];
                    $mail->Body    = "<h3>Thank you for your booking!</h3>" .
<<<<<<< HEAD
                                     "<p>You have successfully reserved <strong>" . htmlspecialchars($gadget['name']) . "</strong>.</p>" .
                                     "<p><strong>Rental Cost:</strong> RM " . number_format($total_price, 2) . "</p>" .
                                     "<p><strong>Required Deposit:</strong> RM " . number_format($gadget['deposit_price'], 2) . "</p>" .
                                     "<p>Please proceed to our counter to pay the deposit and collect your device.</p>";
                    
=======
                        "<p>You have successfully reserved <strong>" . htmlspecialchars($gadget['name']) . "</strong>.</p>" .
                        "<p><strong>Rental Cost:</strong> RM " . number_format($total_price, 2) . "</p>" .
                        "<p><strong>Required Deposit:</strong> RM " . number_format($gadget['deposit_price'], 2) . "</p>" .
                        "<p>Please proceed to our counter to pay the deposit and collect your device.</p>";

>>>>>>> 45019c6 (commit)
                    $mail->send();
                    $message .= " A confirmation email has been sent.";
                } catch (Exception $e) {
                    $message .= " <br><strong style='color:red;'>Email Error: " . htmlspecialchars($mail->ErrorInfo) . "</strong>";
                }
            }
<<<<<<< HEAD

=======
>>>>>>> 45019c6 (commit)
        } else {
            $message = "Error processing rental. Please try again.";
        }
        $booking_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<<<<<<< HEAD
=======

>>>>>>> 45019c6 (commit)
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent <?php echo htmlspecialchars($gadget['name']); ?> - RentalGadget</title>
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
        }

<<<<<<< HEAD
        * { box-sizing: border-box; margin: 0; padding: 0; }
=======
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
>>>>>>> 45019c6 (commit)

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

<<<<<<< HEAD
        .nav-brand span { color: var(--accent); }
=======
        .nav-brand span {
            color: var(--accent);
        }
>>>>>>> 45019c6 (commit)

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .rental-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }

        .gadget-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 12px;
            background: #f1f5f9;
        }

        .price-item {
            background: #f0f9ff;
            color: var(--accent);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
        }

        .rental-form {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-top: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dim);
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .price-calculation {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
            text-align: center;
        }

        .rent-btn {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: var(--accent);
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

<<<<<<< HEAD
        .message.success { background: #d1fae5; color: #065f46; }
        .message.error { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) { .rental-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="product.php"  class="nav-brand">Memory<span>Lens</span></a>
=======
        .message.success {
            background: #d1fae5;
            color: #065f46;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .rental-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="product.php" class="nav-brand">Memory<span>Lens</span></a>
>>>>>>> 45019c6 (commit)
        <div class="nav-actions">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" style="margin-left:15px; color:var(--text-dim); text-decoration:none;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="rental-card">
            <div>
<<<<<<< HEAD
                <img class="gadget-image" src="<?php echo $gadget['image'] ?: 'https://via.placeholder.com/400x350'; ?>" alt="">
=======
                <?php
                $imagePath = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjM1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
                if (!empty($gadget['image'])) {
                    if (strpos($gadget['image'], 'data:image') === 0) {
                        $imagePath = $gadget['image'];
                    } elseif (strpos($gadget['image'], 'uploads/') === 0) {
                        $possiblePath = '../ADMIN/php/' . $gadget['image'];
                        $fsPath = __DIR__ . '/../ADMIN/php/' . $gadget['image'];
                        if (file_exists($fsPath)) {
                            $imagePath = $possiblePath;
                        } else {
                            $imagePath = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjM1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5GaWxlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4=';
                        }
                    }
                }
                ?>
                <img class="gadget-image" src="<?php echo htmlspecialchars($imagePath); ?>" alt="">
>>>>>>> 45019c6 (commit)
                <h1 style="margin-top:20px;"><?php echo htmlspecialchars($gadget['name']); ?></h1>
                <p style="color:var(--text-dim); margin:10px 0;"><?php echo htmlspecialchars($gadget['specs']); ?></p>
                <div style="margin-top:15px;">
                    <span class="price-item">RM <?php echo $gadget['price_day']; ?>/Day</span>
                    <span class="price-item">RM <?php echo $gadget['price_hour']; ?>/Hour</span>
                </div>
                <!-- Paparan Harga Deposit Required -->
                <div style="margin-top: 15px; color: #b45309; font-weight: 700; font-size: 0.9rem;">
                    ⚠️ Required Security Deposit: RM <?php echo number_format($gadget['deposit_price'], 2); ?>
                </div>
            </div>

            <div>
                <form class="rental-form" method="POST" id="rentalForm">
                    <h2 style="margin-bottom:15px; font-size:1.1rem;">Select Booking Period</h2>
<<<<<<< HEAD
                    
=======

>>>>>>> 45019c6 (commit)
                    <div class="form-row">
                        <div>
                            <label>Start Date</label>
                            <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div>
                            <label>End Date</label>
                            <input type="date" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>Pick-up Time</label>
                            <input type="time" id="start_time" name="start_time" required value="09:00">
                        </div>
                        <div>
                            <label>Return Time</label>
                            <input type="time" id="end_time" name="end_time" required value="17:00">
                        </div>
                    </div>

                    <div class="price-calculation">
                        <div style="font-size:0.8rem; color:var(--text-dim);">Estimated Duration</div>
                        <div id="durationText" style="font-weight:600; margin-bottom:5px;">0 Days, 0 Hours</div>
                        <div style="font-size:1.1rem; font-weight:700; color:var(--accent);">Total Rental: RM <span id="totalPrice">0.00</span></div>
                        <small style="color: var(--text-dim);">*Deposit excluded from this total</small>
                    </div>

                    <!-- TAMBAHAN PAUTAN T&C DI SINI -->
                    <div style="text-align: center; margin-bottom: 10px;">
                        <small style="color: var(--text-dim); font-size: 0.75rem;">
<<<<<<< HEAD
                            By clicking confirm, you agree to our 
=======
                            By clicking confirm, you agree to our
>>>>>>> 45019c6 (commit)
                            <a href="tnc.php" target="_blank" style="color: var(--accent); text-decoration: underline;">T&C</a>
                        </small>
                    </div>

                    <button type="submit" class="rent-btn" <?php echo ($gadget['stock'] <= 0) ? 'disabled' : ''; ?>>
                        <?php echo ($gadget['stock'] > 0) ? 'Confirm Booking' : 'Out of Stock'; ?>
                    </button>
                </form>
                <a href="product.php" style="display:block; text-align:center; margin-top:15px; color:var(--text-dim); text-decoration:none; font-size:0.9rem;">← Back to Products</a>
            </div>
        </div>
    </div>

    <script>
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');
<<<<<<< HEAD
        
=======

>>>>>>> 45019c6 (commit)
        const totalPriceSpan = document.getElementById('totalPrice');
        const durationText = document.getElementById('durationText');

        const pricePerDay = <?php echo $gadget['price_day']; ?>;
        const pricePerHour = <?php echo $gadget['price_hour']; ?>;

        function calculateRental() {
            if (!startDate.value || !endDate.value || !startTime.value || !endTime.value) return;

            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
<<<<<<< HEAD
            
=======

>>>>>>> 45019c6 (commit)
            // Kira beza hari
            const diffTime = end - start;
            let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (days < 0) days = 0;

            // Kira beza jam
            const sTime = startTime.value.split(':');
            const eTime = endTime.value.split(':');
<<<<<<< HEAD
            const startH = parseInt(sTime[0]) + parseInt(sTime[1])/60;
            const endH = parseInt(eTime[0]) + parseInt(eTime[1])/60;
            
=======
            const startH = parseInt(sTime[0]) + parseInt(sTime[1]) / 60;
            const endH = parseInt(eTime[0]) + parseInt(eTime[1]) / 60;

>>>>>>> 45019c6 (commit)
            let hours = endH - startH;
            if (hours < 0) hours = 0;

            const total = (days * pricePerDay) + (hours * pricePerHour);
<<<<<<< HEAD
            
=======

>>>>>>> 45019c6 (commit)
            durationText.textContent = `${days} Days, ${hours.toFixed(1)} Hours`;
            totalPriceSpan.textContent = total.toFixed(2);
        }

        [startDate, endDate, startTime, endTime].forEach(el => {
            el.addEventListener('change', calculateRental);
        });

        // Set default end date to today
        startDate.value = new Date().toISOString().split('T')[0];
        endDate.value = new Date().toISOString().split('T')[0];
        calculateRental();
    </script>
</body>
<<<<<<< HEAD
=======

>>>>>>> 45019c6 (commit)
</html>