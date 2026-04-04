<?php
session_start();

// 1. Sambungan Database
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Ambil data tetapan sistem
$res = $conn->query("SELECT * FROM site_settings WHERE id=1");
$settings = $res->fetch_assoc();

if (!$settings) {
    $settings = [
        'email' => 'info@rentalgadget.com',
        'whatsapp' => '+60 12-345 6789',
        'location_name' => 'Kuala Lumpur, Malaysia',
        'map_link' => '#',
        'mon_hours' => '9AM - 9PM', 'tue_hours' => '9AM - 9PM', 'wed_hours' => '9AM - 9PM',
        'thu_hours' => '9AM - 9PM', 'fri_hours' => '9AM - 9PM', 'sat_hours' => '9AM - 9PM',
        'sun_hours' => '9AM - 9PM', 'special_notice' => '', 'show_notice' => 0
    ];
}

// 3. Fungsi interpretTime
function interpretTime($time) {
    $checkValue = trim(strtolower($time));
    if (empty($checkValue) || $checkValue == 'closed' || $checkValue == '1') {
        return '<span style="color: #ef4444; font-weight: 800; text-transform: uppercase;">Closed</span>';
    }
    return htmlspecialchars($time);
}

// 4. Bersihkan nombor WhatsApp
$wa_link = preg_replace('/[^0-9]/', '', $settings['whatsapp']);

// 5. Link Direct Gmail Compose (untuk buka tab baru terus ke Gmail)
$gmail_link = "https://mail.google.com/mail/?view=cm&fs=1&to=" . $settings['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About MemoryLens | Capture Your Story</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #0ea5e9;
            --accent-dark: #0284c7;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-dim: #64748b;
            --shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
        }

        .navbar {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand { font-size: 1.5rem; font-weight: 800; color: var(--text-main); text-decoration: none; letter-spacing: -1px; }
        .nav-brand span { color: var(--accent); }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-link { color: var(--text-dim); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: color 0.2s; }
        .nav-link:hover, .nav-link.active { color: var(--accent); }

        .emergency-notice { background: #ef4444; color: white; padding: 15px; text-align: center; font-weight: 700; font-size: 0.95rem; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }

        .hero {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), 
                        url('https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&q=80&w=1600');
            background-size: cover; background-position: center; height: 40vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; padding: 20px;
        }

        .hero h1 { font-size: 3rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -2px; }
        .hero p { font-size: 1.1rem; max-width: 600px; opacity: 0.9; font-weight: 300; }

        .container { max-width: 1200px; margin: -60px auto 60px; padding: 0 20px; }

        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 24px; padding: 50px; border: 1px solid var(--border); box-shadow: var(--shadow); margin-bottom: 40px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }

        h2 { font-size: 2rem; font-weight: 800; margin-bottom: 20px; letter-spacing: -1px; color: var(--text-main); }
        .accent-bar { width: 60px; height: 5px; background: var(--accent); margin-bottom: 24px; border-radius: 10px; }

        .about-text { color: var(--text-dim); line-height: 1.8; margin-bottom: 20px; }

        .cta-btn {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 14px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
        }
        .cta-btn:hover { background: var(--accent-dark); transform: translateY(-2px); }

        .experience-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 40px; }
        .exp-item { background: var(--bg-body); padding: 30px; border-radius: 20px; border: 1px solid var(--border); transition: transform 0.3s ease; }
        .exp-item h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: var(--accent); }

        /* Gaya Contact Info */
        .contact-info { background: #0f172a; color: white; border-radius: 24px; padding: 60px; margin-top: 40px; }
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 40px; }
        .contact-grid h4 { 
            font-size: 1rem; 
            color: var(--accent); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 15px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .op-hours-list { list-style: none; padding: 0; }
        .op-hours-list li { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
        .day-name { font-weight: 600; color: #cbd5e1; }

        .map-link { 
            color: white; 
            text-decoration: none; 
            border-bottom: 1px dashed var(--accent); 
            padding-bottom: 2px; 
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .map-link:hover { color: var(--accent); }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } .hero h1 { font-size: 2.5rem; } .navbar { padding: 0 20px; } }
    </style>
</head>
<body>

    <?php if($settings['show_notice']): ?>
        <div class="emergency-notice">
            ⚠️ NOTICE: <?php echo htmlspecialchars($settings['special_notice']); ?>
        </div>
    <?php endif; ?>

    <nav class="navbar">
        <a href="product.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-links">
            <a href="about.php" class="nav-link active">About Us</a>
            <a href="product.php" class="nav-link">Products</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <h1>Capturing life, one frame at a time.</h1>
        <p>Your premium gateway to professional-grade storytelling equipment.</p>
    </div>

    <div class="container">
        
        <div class="glass-card">
            <div class="grid-2">
                <div>
                    <div class="accent-bar"></div>
                    <h2>Our Story</h2>
                    <p class="about-text">Memory Lens was created with a passion for preserving memories. We believe everyone deserves the opportunity to capture life's most precious moments, whether it's recording your favorite artist performing live at a concert, documenting your daily adventures in a vlog, or simply capturing family gatherings with professional-quality equipment.</p>
                    <p class="about-text">Founded in 2023, we've built a platform that makes high-end recording devices accessible to everyone. No longer do you need to invest thousands in expensive cameras or audio equipment just to create lasting memories.</p>
                    
                    <a href="product.php" class="cta-btn">Browse the Gear</a>
                </div>
                <div style="text-align: center;">
                    <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&q=80&w=600" alt="Gear" style="width: 100%; border-radius: 20px; box-shadow: var(--shadow);">
                </div>
            </div>
        </div>

        <div class="glass-card">
            <h2>Why Choose Us?</h2>
            <div class="accent-bar"></div>
            <p class="about-text">Whether you're a concert-goer wanting to record your favorite artist's performance, a vlogger documenting your daily life adventures, or someone wanting to capture special family moments, our rental system provides access to professional-grade equipment without the commitment of ownership.</p>
            
            <div class="experience-grid">
                <div class="exp-item">
                    <h3>Quality Assurance</h3>
                    <p style="font-size: 0.9rem; color: var(--text-dim);">Every device undergoes rigorous testing before being rented out.</p>
                </div>
                <div class="exp-item">
                    <h3>Flexible Rentals</h3>
                    <p style="font-size: 0.9rem; color: var(--text-dim);">Hourly, daily, or extended rental periods to fit your schedule.</p>
                </div>
                <div class="exp-item">
                    <h3>Memory Preservation</h3>
                    <p style="font-size: 0.9rem; color: var(--text-dim);">Preserve your cherished moments with professional equipment.</p>
                </div>
            </div>
        </div>

        <div class="contact-info">
            <div class="contact-grid">
                <!-- Location -->
                <div class="info-item">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Location
                    </h4>
                    <p>
                        <a href="<?php echo $settings['map_link']; ?>" target="_blank" class="map-link">
                            <?php echo htmlspecialchars($settings['location_name']); ?>
                        </a>
                    </p>
                </div>

                <!-- Contact -->
                <div class="info-item">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Get In Touch
                    </h4>
                    
                    <!-- Email dengan Ikon & Buka Gmail Web Compose di Tab Baru -->
                    <p style="display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <a href="<?php echo $gmail_link; ?>" target="_blank" class="map-link">
                            <?php echo htmlspecialchars($settings['email']); ?>
                        </a>
                    </p>
                    
                    <!-- WhatsApp dengan Ikon & Tab Baru -->
                    <p style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <a href="https://wa.me/<?php echo $wa_link; ?>" target="_blank" class="map-link">
                            WhatsApp Us
                        </a>
                    </p>
                </div>

                <!-- Operation Hours -->
                <div class="info-item">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Operation Hours
                    </h4>
                    <ul class="op-hours-list">
                        <li><span class="day-name">Mon:</span> <span><?php echo interpretTime($settings['mon_hours']); ?></span></li>
                        <li><span class="day-name">Tue:</span> <span><?php echo interpretTime($settings['tue_hours']); ?></span></li>
                        <li><span class="day-name">Wed:</span> <span><?php echo interpretTime($settings['wed_hours']); ?></span></li>
                        <li><span class="day-name">Thu:</span> <span><?php echo interpretTime($settings['thu_hours']); ?></span></li>
                        <li><span class="day-name">Fri:</span> <span><?php echo interpretTime($settings['fri_hours']); ?></span></li>
                        <li><span class="day-name">Sat:</span> <span><?php echo interpretTime($settings['sat_hours']); ?></span></li>
                        <li><span class="day-name">Sun:</span> <span><?php echo interpretTime($settings['sun_hours']); ?></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>