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

// Fetch default business hours from site_settings
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

// Handle rental submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data tarikh dan masa
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : $default_start_time;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : $default_end_time;

    // Kira jumlah hari
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $intervalDays = $date1->diff($date2);
    $days = $intervalDays->days;

    // Kira jumlah jam (berdasarkan beza masa)
    $time1 = new DateTime("1970-01-01 " . $start_time);
    $time2 = new DateTime("1970-01-01 " . $end_time);
    $intervalHours = $time1->diff($time2);
    $hours = $intervalHours->h + ($intervalHours->i / 60);

    // Validasi ringkas
    $currentMinute = intval(date('i'));
    $currentHour = intval(date('H')) + ($currentMinute > 0 ? 1 : 0);
    $currentLimit = sprintf('%02d:00', $currentHour);
    if ($currentHour > 23) {
        $currentLimit = '23:00';
    }

    if ($date2 < $date1) {
        $message = "End date cannot be earlier than start date.";
    } elseif ($start_date === date('Y-m-d') && $start_time < $currentLimit) {
        $message = "Pick-up time cannot be earlier than the current time.";
    } elseif ($days == 0 && $time2 <= $time1) {
        $message = "End time must be later than start time for same-day rental.";
    } elseif ($start_time < $default_start_time) {
        $message = "Pick-up time cannot be earlier than " . $default_start_time . " (store opening time).";
    } elseif ($end_time > $default_end_time) {
        $message = "Return time cannot be later than " . $default_end_time . " (store closing time). Please select an earlier return time.";
    } elseif ($gadget['stock'] <= 0) {
        $message = "Sorry, this gadget is out of stock.";
    } else {
        // 1. Calculate total price (Harga sewa sahaja)
        $total_price = ($days * $gadget['price_day']) + ($hours * $gadget['price_hour']);

        // 2. LOGIK DEPOSIT: Set 0.00 & Unpaid (Admin akan sahkan di kaunter)
        $deposit_paid = 0.00;
        $deposit_status = "Unpaid";
        $status = "Pending";

        // 3. LOGIK RENTAL DATETIME: Simpan waktu pickup dan waktu akhir sepatutnya
        $rental_date = $start_date . ' ' . $start_time . ':00';
        $return_deadline = $end_date . ' ' . $end_time . ':00';

        // 4. QUERY INSERT (Pastikan kolum rental_date dan return_deadline adalah DATETIME)
        $user_id = $_SESSION['user_id'];
        $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, gadget_id, days, hours, total_price, rental_date, return_deadline, status, deposit_paid, deposit_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // i = integer, d = double, s = string
        // Urutan: user_id(i), gadget_id(i), days(i), hours(i), total_price(d), rental_date(s), return_deadline(s), status(s), deposit_paid(d), deposit_status(s)
        $booking_stmt->bind_param("iiiidsssds", $user_id, $gadget_id, $days, $hours, $total_price, $rental_date, $return_deadline, $status, $deposit_paid, $deposit_status);

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

            $message = "Rental successful! Booking from $start_date to $end_date recorded. Total: RM " . number_format($total_price, 2) . ". Please pay the deposit at the counter.";
            $gadget['stock'] = $new_stock;

            // Send Email Notification
            if (!empty($user_email)) {
                $mail = new PHPMailer(true);
                try {
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
                    $mail->Subject = 'Rental Confirmation - ' . $gadget['name'];
                    $mail->Body    = "<h3>Thank you for your booking!</h3>" .
                        "<p>You have successfully reserved <strong>" . htmlspecialchars($gadget['name']) . "</strong>.</p>" .
                        "<p><strong>Rental Cost:</strong> RM " . number_format($total_price, 2) . "</p>" .
                        "<p><strong>Required Deposit:</strong> RM " . number_format($gadget['deposit_price'], 2) . "</p>" .
                        "<p>Please proceed to our counter to pay the deposit and collect your device.</p>";

                    $mail->send();
                    $message .= " A confirmation email has been sent.";
                } catch (Exception $e) {
                    $message .= " <br><strong style='color:red;'>Email Error: " . htmlspecialchars($mail->ErrorInfo) . "</strong>";
                }
            }
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
            object-fit: contain;
            object-position: center;
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

        input,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #ffffff;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, var(--text-dim) 50%), linear-gradient(135deg, var(--text-dim) 50%, transparent 50%);
            background-position: calc(100% - 16px) calc(50% - 4px), calc(100% - 10px) calc(50% - 4px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 36px;
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
                <?php
                $imagePath = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjM1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
                if (!empty($gadget['image'])) {
                    $rawImage = $gadget['image'];
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
                <img class="gadget-image" src="<?php echo htmlspecialchars($imagePath); ?>" alt="">
                <h1 style="margin-top:20px;"><?php echo htmlspecialchars($gadget['name']); ?></h1>
                <p style="color:var(--text-dim); margin:10px 0;"><?php echo htmlspecialchars($gadget['specs']); ?></p>
                <div style="margin-top:15px;">
                    <span class="price-item">RM <?php echo $gadget['price_day']; ?>/Day</span>
                    <span class="price-item">RM <?php echo $gadget['price_hour']; ?>/Hour</span>
                </div>
                <div style="margin-top: 15px; color: #b45309; font-weight: 700; font-size: 0.9rem;">
                    ⚠️ Required Security Deposit: RM <?php echo number_format($gadget['deposit_price'], 2); ?>
                </div>
            </div>

            <div>
                <form class="rental-form" method="POST" id="rentalForm">
                    <h2 style="margin-bottom:15px; font-size:1.1rem;">Select Booking Period</h2>

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
                            <input type="time" id="start_time" name="start_time" required step="3600">
                        </div>
                        <div>
                            <label>Return Time</label>
                            <input type="time" id="end_time" name="end_time" required step="3600">
                        </div>
                    </div>

                    <div class="price-calculation">
                        <div style="font-size:0.8rem; color:var(--text-dim);">Estimated Duration</div>
                        <div id="durationText" style="font-weight:600; margin-bottom:5px;">0 Days, 0 Hours</div>
                        <div style="font-size:1.1rem; font-weight:700; color:var(--accent);">Total Rental: RM <span id="totalPrice">0.00</span></div>
                        <small style="color: var(--text-dim);">*Deposit excluded from this total</small>
                    </div>

                    <div style="text-align: center; margin-bottom: 10px;">
                        <small style="color: var(--text-dim); font-size: 0.75rem;">
                            By clicking confirm, you agree to our
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

        const totalPriceSpan = document.getElementById('totalPrice');
        const durationText = document.getElementById('durationText');

        const pricePerDay = <?php echo $gadget['price_day']; ?>;
        const pricePerHour = <?php echo $gadget['price_hour']; ?>;

        function pad(number) {
            return number.toString().padStart(2, '0');
        }

        function formatTime(hour, minute) {
            return `${pad(hour)}:${pad(minute)}`;
        }

        function getCurrentTimeString() {
            const now = new Date();
            const minutes = now.getMinutes();
            let hour = now.getHours();
            if (minutes > 0) {
                hour += 1;
            }
            if (hour > 23) {
                return null;
            }
            return formatTime(hour, 0);
        }

        function updateTimeLimits() {
            const today = new Date().toISOString().split('T')[0];
            startDate.min = today;
            if (!startDate.value) startDate.value = today;
            if (!endDate.value) endDate.value = startDate.value;
            if (endDate.value < startDate.value) {
                endDate.value = startDate.value;
            }
            endDate.min = startDate.value;

            // Restrict pickup time only for today. Return time follows pickup time.
            let minStartTime = '00:00';
            if (startDate.value === today) {
                const currentMin = getCurrentTimeString();
                minStartTime = currentMin || '23:59';
            }
            startTime.min = minStartTime;
            startTime.max = '23:59';
            if (!startTime.value || startTime.value < minStartTime) {
                startTime.value = minStartTime;
            }

            let minEndTime = '00:00';
            if (endDate.value === startDate.value) {
                minEndTime = startTime.value || minStartTime;
            }
            endTime.min = minEndTime;
            endTime.max = '23:59';
            if (!endTime.value || endTime.value < endTime.min) {
                endTime.value = endTime.min;
            }
        }

        function calculateRental() {
            updateTimeLimits();
            if (!startDate.value || !endDate.value || !startTime.value || !endTime.value) return;

            const today = new Date().toISOString().split('T')[0];
            if (startDate.value === today) {
                const nowTime = getCurrentTimeString();
                if (nowTime && startTime.value < nowTime) {
                    durationText.textContent = 'Pick-up time cannot be earlier than current time.';
                    totalPriceSpan.textContent = '0.00';
                    return;
                }
            }

            const start = new Date(`${startDate.value}T${startTime.value}`);
            const end = new Date(`${endDate.value}T${endTime.value}`);

            const diffTime = end - start;
            if (diffTime <= 0) {
                durationText.textContent = 'End date/time must be later than pick-up date/time.';
                totalPriceSpan.textContent = '0.00';
                return;
            }

            const totalHours = Math.round(diffTime / (1000 * 60 * 60));
            const fullDays = Math.floor(totalHours / 24);
            const remainingHours = totalHours % 24;

            const total = (fullDays * pricePerDay) + (remainingHours * pricePerHour);

            durationText.textContent = `${fullDays} Days, ${remainingHours} Hours`;
            totalPriceSpan.textContent = total.toFixed(2);
        }

        [startDate, endDate, startTime, endTime].forEach(el => {
            el.addEventListener('change', calculateRental);
        });

        startDate.value = new Date().toISOString().split('T')[0];
        endDate.value = new Date().toISOString().split('T')[0];
        updateTimeLimits();
        calculateRental();
    </script>
</body>

</html>