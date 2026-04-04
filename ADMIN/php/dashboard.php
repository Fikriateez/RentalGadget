<?php
// 1. Database Connection
$host = "localhost";
<<<<<<< HEAD
$user = "root";     
$pass = "";         
$dbname = "admin";  
=======
$user = "root";
$pass = "";
$dbname = "admin";
>>>>>>> 45019c6 (commit)

$conn = new mysqli($host, $user, $pass, $dbname);

session_start();

<<<<<<< HEAD
if (!isset($_SESSION['admin'])) {
    header("Location: login.php"); 
=======
// Increase limits for large image uploads
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
>>>>>>> 45019c6 (commit)
    exit();
}

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// 2. Determine what action to perform
$action = isset($_GET['action']) ? $_GET['action'] : '';

<<<<<<< HEAD
=======
// --- CHECK LOGIN ---
if ($action == 'check') {
    echo json_encode(["logged_in" => isset($_SESSION['admin'])]);
    $conn->close();
    exit;
}

>>>>>>> 45019c6 (commit)
// --- FETCH DATA ---
if ($action == 'fetch') {
    $result = $conn->query("SELECT * FROM register ORDER BY id DESC");
    $gadgets = [];
    while ($row = $result->fetch_assoc()) {
        $gadgets[] = $row;
    }
    echo json_encode($gadgets);
    $conn->close();
    exit;
}

// --- SAVE DATA (INSERT OR UPDATE) ---
elseif ($action == 'add') {
    // Get POST data
    $name = $_POST['name'] ?? '';
    $specs = $_POST['specs'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $priceDay = $_POST['priceDay'] ?? 0;
    $priceHour = $_POST['priceHour'] ?? 0;
    $deposit = $_POST['deposit'] ?? 0; // LOGIK TAMBAHAN: Ambil data deposit
<<<<<<< HEAD
    $image = $_POST['image'] ?? ''; 
=======
    $image_data = $_POST['image'] ?? '';
    $image = '';
    if ($image_data && strpos($image_data, 'data:image') === 0) {
        $parts = explode(',', $image_data);
        if (count($parts) == 2) {
            $data = $parts[1];
            $meta = $parts[0];
            $type = explode(';', $meta)[0];
            $extension = str_replace('data:image/', '', $type);
            $filename = uniqid() . '.' . $extension;
            $image = 'uploads/' . $filename;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            $targetPath = 'uploads/' . $filename;
            file_put_contents($targetPath, base64_decode($data));

            // Resize image for better performance in product cards (max 1200x1200)
            $maxWidth = 1200;
            $maxHeight = 1200;
            $imageInfo = getimagesize($targetPath);
            if ($imageInfo) {
                list($width, $height, $type) = $imageInfo;
                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = intval($width * $ratio);
                    $newHeight = intval($height * $ratio);

                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $srcImg = function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($targetPath) : false;
                            break;
                        case IMAGETYPE_PNG:
                            $srcImg = function_exists('imagecreatefrompng') ? imagecreatefrompng($targetPath) : false;
                            break;
                        case IMAGETYPE_GIF:
                            $srcImg = function_exists('imagecreatefromgif') ? imagecreatefromgif($targetPath) : false;
                            break;
                        default:
                            $srcImg = false;
                    }

                    if (!$srcImg) {
                        // GD is unavailable, or the image format isn't supported; skip resizing
                        error_log('dashboard.php: Image resizing skipped, GD function not available or unsupported format for ' . $targetPath);
                    }

                    if ($srcImg) {
                        $dstImg = imagecreatetruecolor($newWidth, $newHeight);
                        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                            imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
                            imagealphablending($dstImg, false);
                            imagesavealpha($dstImg, true);
                        }
                        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        switch ($type) {
                            case IMAGETYPE_JPEG:
                                imagejpeg($dstImg, $targetPath, 90);
                                break;
                            case IMAGETYPE_PNG:
                                imagepng($dstImg, $targetPath, 6);
                                break;
                            case IMAGETYPE_GIF:
                                imagegif($dstImg, $targetPath);
                                break;
                        }

                        imagedestroy($dstImg);
                        imagedestroy($srcImg);
                    }
                }
            }
        }
    }
>>>>>>> 45019c6 (commit)
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? $_POST['id'] : null;

    if ($id) {
        // UPDATE: Tambah deposit_price=? dalam query
        $stmt = $conn->prepare("UPDATE register SET name=?, specs=?, stock=?, price_day=?, price_hour=?, image=?, deposit_price=? WHERE id=?");
        if (!$stmt) {
<<<<<<< HEAD
             echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
             exit;
=======
            echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
            exit;
>>>>>>> 45019c6 (commit)
        }
        // bind_param: s=string, i=int, d=double. Urutan: name(s), specs(s), stock(i), pDay(d), pHour(d), img(s), deposit(d), id(i)
        $stmt->bind_param("ssidssdi", $name, $specs, $stock, $priceDay, $priceHour, $image, $deposit, $id);
    } else {
        // INSERT: Tambah deposit_price dalam query
        $stmt = $conn->prepare("INSERT INTO register (name, specs, stock, price_day, price_hour, image, deposit_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
<<<<<<< HEAD
             echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
             exit;
        }
        $stmt->bind_param("ssiddsd", $name, $specs, $stock, $priceDay, $priceHour, $image, $deposit);
    }
    
=======
            echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssiddsd", $name, $specs, $stock, $priceDay, $priceHour, $image, $deposit);
    }

>>>>>>> 45019c6 (commit)
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Execution failed: " . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// --- UPDATE STOCK ---
elseif ($action == 'update') {
    $id = $_POST['id'];
    $stock = $_POST['stock'];
    $stmt = $conn->prepare("UPDATE register SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $stock, $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// --- DELETE DATA ---
elseif ($action == 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    $stmt->close();
    $conn->close();
    exit;
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
    <title>MemoryLens | Dashboard & Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
<<<<<<< HEAD
            --accent: #0ea5e9; 
            --bg-body: #f8fafc; 
            --bg-card: #ffffff; 
            --border: #e2e8f0; 
            --text-main: #0f172a; 
            --text-dim: #64748b; 
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        }

        * { box-sizing: border-box; transition: all 0.2s ease; margin: 0; padding: 0; }
        
=======
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
            transition: all 0.2s ease;
            margin: 0;
            padding: 0;
        }

>>>>>>> 45019c6 (commit)
        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- NAVIGATION BAR --- */
        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
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
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-link { text-decoration: none; color: var(--text-dim); font-size: 0.9rem; font-weight: 500; }
        .nav-link:hover, .nav-link.active { color: var(--accent); }
        .nav-actions { display: flex; align-items: center; gap: 15px; }
        .btn-outline { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--text-main); font-size: 0.85rem; font-weight: 600; cursor: pointer; }
        .btn-outline:hover { background: var(--bg-body); }

        /* --- MAIN LAYOUT --- */
        .container { max-width: 1100px; margin: 40px auto; width: 100%; padding: 0 20px; }

        /* --- HEADER --- */
        .page-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 30px; border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
        }
        .page-header h1 { font-weight: 700; font-size: 1.8rem; letter-spacing: -0.02em; margin: 0; }
        .page-header p { color: var(--text-dim); margin-top: 4px; font-size: 0.95rem; }

        .stats-bar { display: flex; gap: 15px; }
        .stat-card {
            background: var(--bg-card); padding: 10px 20px; border-radius: 12px;
            border: 1px solid var(--border); text-align: left;
        }
        .stat-card small { display: block; font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .stat-card span { font-weight: 700; font-size: 1.1rem; color: var(--accent); }

        /* --- REGISTRATION PANEL --- */
        .registration-panel {
            max-height: 0; overflow: hidden; opacity: 0;
            background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border);
            margin-bottom: 0; box-shadow: var(--shadow);
        }
        .registration-panel.open { max-height: 1000px; opacity: 1; padding: 30px; margin-bottom: 30px; }

        /* --- FILTER PANEL --- */
        .filter-panel {
            max-height: 0; overflow: hidden; opacity: 0;
            background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border);
            margin-bottom: 0; box-shadow: var(--shadow);
        }
        .filter-panel.open { max-height: 500px; opacity: 1; padding: 30px; margin-bottom: 30px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        label { font-size: 0.75rem; color: var(--text-dim); font-weight: 700; text-transform: uppercase; margin-bottom: 5px; display: block; }
        input, textarea { width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px; font-family: inherit; }

        .upload-area {
            grid-column: span 3; border: 2px dashed var(--border);
            border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; background: #fdfdfd;
        }
        #imgPrev { max-height: 80px; margin-top: 10px; border-radius: 4px; display: none; margin-left: auto; margin-right: auto; }

        .btn-action {
            background: var(--accent); color: white; border: none; padding: 12px 24px;
            border-radius: 10px; font-weight: 600; cursor: pointer;
        }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }

        /* --- TABLE STYLE --- */
        .table-container {
            background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border);
            box-shadow: var(--shadow); overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background: #f9fafb; border-bottom: 1px solid var(--border); }
        th { padding: 16px; font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .product-cell { display: flex; align-items: center; gap: 12px; }
        .product-img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; background: #f1f5f9; }
        .product-name { font-weight: 600; font-size: 0.9rem; }

        .stock-control { display: flex; align-items: center; gap: 10px; }
        .stock-btn { 
            width: 28px; height: 28px; border: 1px solid var(--border); background: white;
            border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1rem;
        }
        .stock-btn:hover { background: #f1f5f9; border-color: var(--accent); color: var(--accent); }

        .delete-btn { color: #ef4444; border: none; background: none; font-weight: 600; font-size: 0.8rem; cursor: pointer; }
        .delete-btn:hover { text-decoration: underline; }

        .edit-btn { color: #2563eb; border: none; background: none; font-weight: 600; font-size: 0.8rem; cursor: pointer; margin-right: 10px; }
        .edit-btn:hover { text-decoration: underline; }

        .badge-price { background: #f0f9ff; color: var(--accent); padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; display: inline-block; margin-bottom: 4px;}
        .price-sub { font-size: 0.75rem; color: var(--text-dim); margin-left: 5px; }

    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="dashboard.php" class="nav-brand">Memory<span>Lens</span></a>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="register.html" class="nav-link">Register</a>
        <a href="booking.php" class="nav-link">Bookings</a>
        <a href="customer.php" class="nav-link">Customers</a>
        <a href="admin_settings.php" class="nav-link">Settings</a>
    </div>
    <div class="nav-actions"><a href="logout.php" class="btn-outline" style="text-decoration: none;">Logout</a></div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Dashboard Monitor</h1>
            <p>Overview of all active devices in stock.</p>
        </div>
        <div class="stats-bar">
            <div class="stat-card"><small>Total Models</small><span id="statItems">0</span></div>
            <div class="stat-card"><small>Total Units</small><span id="statStock">0</span></div>
            <button class="btn-action" onclick="toggleForm()">Manage Products</button>
        </div>
    </div>

    <!-- UPDATE GADGET FORM -->
    <div class="registration-panel" id="regPanel">
        <form id="gadgetForm">
            <input type="hidden" id="gadgetId" name="id">
            <div class="form-grid">
                <div style="grid-column: span 3">
                    <label>Model Name</label>
                    <input type="text" id="gName" placeholder="e.g. Sony A7IV" required>
                </div>
                <div>
                    <label>Opening Stock</label>
                    <input type="number" id="gStock" value="1" required>
                </div>
                <div>
                    <label>Daily Rate (RM)</label>
                    <input type="number" id="gPriceDay" step="0.01" placeholder="00.00" required>
                </div>
                <div>
                    <label>Hourly Rate (RM)</label>
                    <input type="number" id="gPriceHour" step="0.01" placeholder="00.00" required>
                </div>
                <div>
                    <!-- LOGIK ASAL ANDA -->
                    <label>Deposit Amount (RM)</label>
                    <input type="number" id="gDeposit" name="deposit" step="0.01" placeholder="e.g. 50.00" required>
                </div>
                <div style="grid-column: span 3">
                    <label>Specifications</label>
                    <textarea id="gSpecs" rows="2" placeholder="Key technical features..."></textarea>
                </div>
                <div class="upload-area" onclick="document.getElementById('fileIn').click()">
                    <input type="file" id="fileIn" hidden accept="image/*" onchange="handleImage(this)">
                    <div id="uploadTxt">Click to upload product photo</div>
                    <img id="imgPrev">
                </div>
                <div style="grid-column: span 3; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-action">Update Product</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLE MONITOR -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Technical Details</th>
                    <th>Rental Rates & Deposit</th>
                    <th>Inventory Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="inventoryBody"></tbody>
        </table>
    </div>
</div>

<script>
    let gadgets = [];
    let currentImage = "";
    let filteredGadgets = [];

    async function loadGadgets() {
        try {
            const response = await fetch('dashboard.php?action=fetch');
            gadgets = await response.json();
            filteredGadgets = [...gadgets];
            renderTable();
        } catch (error) {
            console.error('Error loading gadgets:', error);
        }
    }

    function toggleForm() {
        document.getElementById('regPanel').classList.toggle('open');
    }

    function handleImage(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                currentImage = e.target.result;
                document.getElementById('imgPrev').src = currentImage;
                document.getElementById('imgPrev').style.display = 'block';
                document.getElementById('uploadTxt').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    // Saving from Quick Add
    document.getElementById('gadgetForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('id', document.getElementById('gadgetId').value);
        formData.append('name', document.getElementById('gName').value);
        formData.append('specs', document.getElementById('gSpecs').value);
        formData.append('stock', document.getElementById('gStock').value);
        formData.append('priceDay', document.getElementById('gPriceDay').value);
        formData.append('priceHour', document.getElementById('gPriceHour').value);
        // LOGIK TAMBAHAN: Masukkan deposit ke dalam FormData
        formData.append('deposit', document.getElementById('gDeposit').value);
        formData.append('image', currentImage);

        try {
            const response = await fetch('dashboard.php?action=add', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                e.target.reset();
                document.getElementById('imgPrev').style.display = 'none';
                document.getElementById('uploadTxt').style.display = 'block';
                currentImage = "";
                document.getElementById('gadgetId').value = '';
                toggleForm();
                await loadGadgets();
            } else {
                alert('Error updating gadget: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    async function updateStock(id, change) {
        const gadget = gadgets.find(g => g.id == id);
        const newStock = parseInt(gadget.stock) + change;
        if (newStock < 0) return;

        const formData = new FormData();
        formData.append('id', id);
        formData.append('stock', newStock);

        try {
            const response = await fetch('dashboard.php?action=update', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                await loadGadgets();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function deleteGadget(id) {
        if (!confirm('Remove this device from monitor?')) return;
        const formData = new FormData();
        formData.append('id', id);
        try {
            const response = await fetch('dashboard.php?action=delete', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                await loadGadgets();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function editGadget(id) {
        const gadget = gadgets.find(g => g.id == id);
        if (!gadget) return;

        document.getElementById('gadgetId').value = gadget.id;
        document.getElementById('gName').value = gadget.name;
        document.getElementById('gSpecs').value = gadget.specs;
        document.getElementById('gStock').value = gadget.stock;
        document.getElementById('gPriceDay').value = gadget.price_day;
        document.getElementById('gPriceHour').value = gadget.price_hour;
        // LOGIK TAMBAHAN: Masukkan nilai deposit ke form semasa edit
        document.getElementById('gDeposit').value = gadget.deposit_price;
        
        currentImage = gadget.image;
        if (currentImage) {
            document.getElementById('imgPrev').src = currentImage;
            document.getElementById('imgPrev').style.display = 'block';
            document.getElementById('uploadTxt').style.display = 'none';
        } else {
            document.getElementById('imgPrev').style.display = 'none';
            document.getElementById('uploadTxt').style.display = 'block';
        }
        toggleForm();
    }

    function renderTable() {
        const tbody = document.getElementById('inventoryBody');
        tbody.innerHTML = '';

        filteredGadgets.forEach(g => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="product-cell">
                        <img class="product-img" src="${g.image || 'https://via.placeholder.com/50'}" alt="">
=======
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

        .nav-link:hover,
        .nav-link.active {
            color: var(--accent);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .btn-outline:hover {
            background: var(--bg-body);
        }

        /* --- MAIN LAYOUT --- */
        .container {
            max-width: 1100px;
            margin: 40px auto;
            width: 100%;
            padding: 0 20px;
        }

        /* --- HEADER --- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .page-header p {
            color: var(--text-dim);
            margin-top: 4px;
            font-size: 0.95rem;
        }

        .stats-bar {
            display: flex;
            gap: 15px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: left;
        }

        .stat-card small {
            display: block;
            font-size: 0.65rem;
            color: var(--text-dim);
            text-transform: uppercase;
            font-weight: 700;
        }

        .stat-card span {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--accent);
        }

        /* --- REGISTRATION PANEL --- */
        .registration-panel {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border);
            margin-bottom: 0;
            box-shadow: var(--shadow);
        }

        .registration-panel.open {
            max-height: 1000px;
            opacity: 1;
            padding: 30px;
            margin-bottom: 30px;
        }

        /* --- FILTER PANEL --- */
        .filter-panel {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border);
            margin-bottom: 0;
            box-shadow: var(--shadow);
        }

        .filter-panel.open {
            max-height: 500px;
            opacity: 1;
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        label {
            font-size: 0.75rem;
            color: var(--text-dim);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            font-family: inherit;
        }

        .upload-area {
            grid-column: span 3;
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: #fdfdfd;
        }

        #imgPrev {
            max-height: 80px;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-action {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* --- TABLE STYLE --- */
        .table-container {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
        }

        th {
            padding: 16px;
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-img {
            width: 100px !important;
            height: auto;
            min-width: 60px;
            min-height: 60px;
            max-width: 100px !important;
            max-height: 120px;
            border-radius: 8px;
            object-fit: contain;
            object-position: center;
            background: #f1f5f9;
            display: block;
        }

        .product-img.fullsize {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: contain;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .stock-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stock-btn {
            width: 28px;
            height: 28px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }

        .stock-btn:hover {
            background: #f1f5f9;
            border-color: var(--accent);
            color: var(--accent);
        }

        .delete-btn {
            color: #ef4444;
            border: none;
            background: none;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .delete-btn:hover {
            text-decoration: underline;
        }

        .edit-btn {
            color: #2563eb;
            border: none;
            background: none;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            margin-right: 10px;
        }

        .edit-btn:hover {
            text-decoration: underline;
        }

        .badge-price {
            background: #f0f9ff;
            color: var(--accent);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 4px;
        }

        .price-sub {
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-left: 5px;
        }
    </style>
</head>

<body>

    <!-- NAVIGATION BAR -->
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="register.html" class="nav-link">Register</a>
            <a href="booking.php" class="nav-link">Bookings</a>
            <a href="customer.php" class="nav-link">Customers</a>
            <a href="admin_settings.php" class="nav-link">Settings</a>
        </div>
        <div class="nav-actions"><a href="logout.php" class="btn-outline" style="text-decoration: none;">Logout</a></div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Dashboard Monitor</h1>
                <p>Overview of all active devices in stock.</p>
            </div>
            <div class="stats-bar">
                <div class="stat-card"><small>Total Models</small><span id="statItems">0</span></div>
                <div class="stat-card"><small>Total Units</small><span id="statStock">0</span></div>
                <button class="btn-action" onclick="toggleForm()">Manage Products</button>
            </div>
        </div>

        <!-- UPDATE GADGET FORM -->
        <div class="registration-panel" id="regPanel">
            <form id="gadgetForm">
                <input type="hidden" id="gadgetId" name="id">
                <div class="form-grid">
                    <div style="grid-column: span 3">
                        <label>Model Name</label>
                        <input type="text" id="gName" placeholder="e.g. Sony A7IV" required>
                    </div>
                    <div>
                        <label>Opening Stock</label>
                        <input type="number" id="gStock" value="1" required>
                    </div>
                    <div>
                        <label>Daily Rate (RM)</label>
                        <input type="number" id="gPriceDay" step="0.01" placeholder="00.00" required>
                    </div>
                    <div>
                        <label>Hourly Rate (RM)</label>
                        <input type="number" id="gPriceHour" step="0.01" placeholder="00.00" required>
                    </div>
                    <div>
                        <!-- LOGIK ASAL ANDA -->
                        <label>Deposit Amount (RM)</label>
                        <input type="number" id="gDeposit" name="deposit" step="0.01" placeholder="e.g. 50.00" required>
                    </div>
                    <div style="grid-column: span 3">
                        <label>Specifications</label>
                        <textarea id="gSpecs" rows="2" placeholder="Key technical features..."></textarea>
                    </div>
                    <div class="upload-area" onclick="document.getElementById('fileIn').click()">
                        <input type="file" id="fileIn" hidden accept="image/*" onchange="handleImage(this)">
                        <div id="uploadTxt">Click to upload product photo</div>
                        <img id="imgPrev">
                    </div>
                    <div style="grid-column: span 3; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-action">Update Product</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TABLE MONITOR -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Technical Details</th>
                        <th>Rental Rates & Deposit</th>
                        <th>Inventory Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        let gadgets = [];
        let currentImage = ""; // Ini akan menyimpan data Base64
        let filteredGadgets = [];

        async function loadGadgets() {
            try {
                const response = await fetch('dashboard.php?action=fetch');
                gadgets = await response.json();
                filteredGadgets = [...gadgets];
                renderTable();
            } catch (error) {
                console.error('Error loading gadgets:', error);
            }
        }

        function toggleForm() {
            const panel = document.getElementById('regPanel');
            panel.classList.toggle('open');
            // Jika tutup form, reset preview dan id (opsional untuk elak keliru)
            if (!panel.classList.contains('open')) {
                document.getElementById('gadgetForm').reset();
                document.getElementById('gadgetId').value = '';
                document.getElementById('imgPrev').style.display = 'none';
                document.getElementById('uploadTxt').style.display = 'block';
                currentImage = "";
            }
        }

        function handleImage(input) {
            const file = input.files[0];
            if (file) {
                // Check saiz (Opsional: Base64 sangat besar, elakkan file > 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert("File too large! Please upload image below 2MB.");
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    currentImage = e.target.result;
                    document.getElementById('imgPrev').src = currentImage;
                    document.getElementById('imgPrev').style.display = 'block';
                    document.getElementById('uploadTxt').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('gadgetForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();

            formData.append('id', document.getElementById('gadgetId').value);
            formData.append('name', document.getElementById('gName').value);
            formData.append('specs', document.getElementById('gSpecs').value);
            formData.append('stock', document.getElementById('gStock').value);
            formData.append('priceDay', document.getElementById('gPriceDay').value);
            formData.append('priceHour', document.getElementById('gPriceHour').value);
            formData.append('deposit', document.getElementById('gDeposit').value);

            // Penting: Hantar currentImage (Base64 string)
            formData.append('image', currentImage);

            try {
                const response = await fetch('dashboard.php?action=add', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    e.target.reset();
                    toggleForm();
                    await loadGadgets();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert("Failed to save data. Data might be too large for server limits.");
            }
        };

        function editGadget(id) {
            const gadget = gadgets.find(g => g.id == id);
            if (!gadget) return;

            document.getElementById('gadgetId').value = gadget.id;
            document.getElementById('gName').value = gadget.name;
            document.getElementById('gSpecs').value = gadget.specs;
            document.getElementById('gStock').value = gadget.stock;
            document.getElementById('gPriceDay').value = gadget.price_day;
            document.getElementById('gPriceHour').value = gadget.price_hour;
            document.getElementById('gDeposit').value = gadget.deposit_price;

            // KUNCI UTAMA: Simpan imej sedia ada ke dalam currentImage 
            // supaya jika anda tidak upload imej baru, imej lama tetap dihantar ke PHP
            currentImage = gadget.image;

            if (currentImage && currentImage.trim() !== "") {
                document.getElementById('imgPrev').src = currentImage;
                document.getElementById('imgPrev').style.display = 'block';
                document.getElementById('uploadTxt').style.display = 'none';
            } else {
                document.getElementById('imgPrev').style.display = 'none';
                document.getElementById('uploadTxt').style.display = 'block';
            }
            toggleForm();
        }

        function renderTable() {
            const tbody = document.getElementById('inventoryBody');
            tbody.innerHTML = '';

            filteredGadgets.forEach(g => {
                const row = document.createElement('tr');

                // LOGIK PAPARAN: Pastikan src ada data yang sah
                // Jika g.image kosong, guna placeholder
                const finalImg = (g.image && g.image.trim() !== '') ? g.image : 'https://via.placeholder.com/50';

                row.innerHTML = `
                <td>
                    <div class="product-cell">
                        <img class="product-img" style="width:80px;height:80px;min-width:45px;min-height:45px;max-width:80px;max-height:80px;object-fit:cover;object-position:center;" src="${finalImg}" alt="Product" onerror="this.src='https://via.placeholder.com/50'">
>>>>>>> 45019c6 (commit)
                        <div class="product-name">${g.name}</div>
                    </div>
                </td>
                <td style="font-size: 0.85rem; color: #64748b; max-width: 300px;">${g.specs || '--'}</td>
                <td>
                    <span class="badge-price">RM ${g.price_day} / day</span><br>
                    <span class="badge-price" style="background:#fef3c7; color:#92400e;">Deposit: RM ${g.deposit_price || '0.00'}</span>
                </td>
                <td>
                    <div class="stock-control">
                        <button class="stock-btn" onclick="updateStock(${g.id}, -1)">-</button>
                        <span style="font-weight: 700; min-width: 25px; text-align:center;">${g.stock}</span>
                        <button class="stock-btn" onclick="updateStock(${g.id}, 1)">+</button>
                    </div>
                </td>
                <td>
                    <button class="edit-btn" onclick="editGadget(${g.id})">Edit</button>
                    <button class="delete-btn" onclick="deleteGadget(${g.id})">Delete</button>
                </td>
            `;
<<<<<<< HEAD
            tbody.appendChild(row);
        });

        document.getElementById('statItems').innerText = gadgets.length;
        document.getElementById('statStock').innerText = gadgets.reduce((sum, g) => sum + parseInt(g.stock), 0);
    }

    loadGadgets();
</script>
</body>
=======
                tbody.appendChild(row);
            });

            document.getElementById('statItems').innerText = gadgets.length;
            document.getElementById('statStock').innerText = gadgets.reduce((sum, g) => sum + parseInt(g.stock), 0);
        }

        // Fungsi updateStock dan deleteGadget kekal sama seperti kod asal anda
        async function updateStock(id, change) {
            const gadget = gadgets.find(g => g.id == id);
            const newStock = parseInt(gadget.stock) + change;
            if (newStock < 0) return;
            const formData = new FormData();
            formData.append('id', id);
            formData.append('stock', newStock);
            try {
                const response = await fetch('dashboard.php?action=update', {
                    method: 'POST',
                    body: formData
                });
                if ((await response.json()).status === 'success') await loadGadgets();
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function deleteGadget(id) {
            if (!confirm('Remove this device from monitor?')) return;
            const formData = new FormData();
            formData.append('id', id);
            try {
                const response = await fetch('dashboard.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                if ((await response.json()).status === 'success') await loadGadgets();
            } catch (error) {
                console.error('Error:', error);
            }
        }

        loadGadgets();
    </script>
</body>

>>>>>>> 45019c6 (commit)
</html>