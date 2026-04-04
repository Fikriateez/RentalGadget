<?php
session_start();
$host = "localhost"; $user = "root"; $pass = ""; $dbname = "admin";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $loc_name = $_POST['loc_name'];
    $map_link = $_POST['map_link'];
    
    // Waktu Operasi
    $mon = $_POST['mon']; $tue = $_POST['tue']; $wed = $_POST['wed']; 
    $thu = $_POST['thu']; $fri = $_POST['fri']; $sat = $_POST['sat']; $sun = $_POST['sun'];
    
    // Notis Khas
    $notice = $_POST['special_notice'];
    $show_notice = isset($_POST['show_notice']) ? 1 : 0;

    // PEMBETULAN: Format bind_param ditukar kepada ssssssssssssi (12 string, 1 integer)
    $sql = "UPDATE site_settings SET 
            email=?, whatsapp=?, location_name=?, map_link=?, 
            mon_hours=?, tue_hours=?, wed_hours=?, thu_hours=?, fri_hours=?, sat_hours=?, sun_hours=?, 
            special_notice=?, show_notice=? WHERE id=1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssi", 
        $email, $whatsapp, $loc_name, $map_link, 
        $mon, $tue, $wed, $thu, $fri, $sat, $sun, 
        $notice, $show_notice
    );
    
    if ($stmt->execute()) { $message = "Settings updated successfully!"; }
}

$res = $conn->query("SELECT * FROM site_settings WHERE id=1");
$s = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings | MemoryLens</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 20px; color: #0f172a; }
        .card { background: white; padding: 30px; max-width: 800px; margin: auto; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2, h3 { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { font-weight: 600; font-size: 13px; color: #64748b; display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        .notice-box { background: #fff1f2; padding: 20px; border-radius: 12px; border: 1px solid #fecdd3; margin-bottom: 20px; }
        .btn-save { background: #0ea5e9; color: white; border: none; padding: 15px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 700; font-size: 16px; }
        .back-link { text-decoration: none; color: #64748b; font-weight: 600; display: inline-block; margin-bottom: 20px; }
        .hint { font-size: 11px; color: #0ea5e9; margin-top: -10px; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>
    <div class="card">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>System Configuration</h2>

        <?php if(isset($message)) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>

        <form method="POST">
            <div class="notice-box">
                <h3>⚠️ Emergency Notice / Special Announcement</h3>
                <label>Notice Content (e.g. Closed for Raya Holidays)</label>
                <textarea name="special_notice" rows="2" placeholder="Enter reason for closure..."><?php echo $s['special_notice']; ?></textarea>
                <label style="display: flex; align-items: center; gap: 10px; cursor:pointer;">
                    <input type="checkbox" name="show_notice" style="width:auto; margin:0;" <?php if($s['show_notice']) echo 'checked'; ?>>
                    <strong>Display this notice on website</strong>
                </label>
            </div>

            <div class="grid">
                <div>
                    <h3>Contact Info</h3>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $s['email']; ?>">
                    <label>WhatsApp</label>
                    <input type="text" name="whatsapp" value="<?php echo $s['whatsapp']; ?>">
                    <label>Location Name</label>
                    <input type="text" name="loc_name" value="<?php echo $s['location_name']; ?>">
                    <label>Maps Link</label>
                    <textarea name="map_link"><?php echo $s['map_link']; ?></textarea>
                </div>

                <div>
                    <h3>Weekly Operation Hours</h3>
                    <span class="hint">* Taip "1" atau "Closed" untuk paparan CLOSED merah.</span>
                    <label>Monday</label><input type="text" name="mon" value="<?php echo $s['mon_hours']; ?>">
                    <label>Tuesday</label><input type="text" name="tue" value="<?php echo $s['tue_hours']; ?>">
                    <label>Wednesday</label><input type="text" name="wed" value="<?php echo $s['wed_hours']; ?>">
                    <label>Thursday</label><input type="text" name="thu" value="<?php echo $s['thu_hours']; ?>">
                    <label>Friday</label><input type="text" name="fri" value="<?php echo $s['fri_hours']; ?>">
                    <label>Saturday</label><input type="text" name="sat" value="<?php echo $s['sat_hours']; ?>">
                    <label>Sunday</label><input type="text" name="sun" value="<?php echo $s['sun_hours']; ?>">
                </div>
            </div>

            <button type="submit" class="btn-save">Save All Settings</button>
        </form>
    </div>
</body>
</html>