<?php
// 1. Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);
session_start();

// Increase limits for large image uploads
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- CHECK LOGIN ---
if ($action == 'check') {
    echo json_encode(["logged_in" => isset($_SESSION['admin'])]);
    exit;
}

// --- FETCH DATA ---
if ($action == 'fetch') {
    $result = $conn->query("SELECT * FROM register ORDER BY id DESC");
    $gadgets = [];
    while ($row = $result->fetch_assoc()) {
        $gadgets[] = $row;
    }
    echo json_encode($gadgets);
    exit;
}

// --- SAVE DATA (INSERT OR UPDATE) ---
elseif ($action == 'add') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? $_POST['id'] : null;
    $name = $_POST['name'] ?? '';
    $specs = $_POST['specs'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $priceDay = $_POST['priceDay'] ?? 0;
    $priceHour = $_POST['priceHour'] ?? 0;
    $deposit = $_POST['deposit'] ?? 0;
    $image_data = $_POST['image'] ?? '';

    $image_path = '';

    // Handle Image Upload (Base64)
    if ($image_data && strpos($image_data, 'data:image') === 0) {
        $parts = explode(',', $image_data);
        if (count($parts) == 2) {
            $data = base64_decode($parts[1]);
            $meta = $parts[0];
            $extension = str_replace(['data:image/', ';base64'], '', $meta);
            $filename = uniqid() . '.' . $extension;
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $image_path = 'uploads/' . $filename;
            file_put_contents($image_path, $data);

            // Basic Resize Logic (Optional)
            if (extension_loaded('gd')) {
                $imageInfo = getimagesize($image_path);
                if ($imageInfo) {
                    list($width, $height, $type) = $imageInfo;
                    $maxWidth = 1200;
                    $maxHeight = 1200;
                    if ($width > $maxWidth || $height > $maxHeight) {
                        $ratio = min($maxWidth / $width, $maxHeight / $height);
                        $newW = intval($width * $ratio);
                        $newH = intval($height * $ratio);
                        $src = ($type == IMAGETYPE_JPEG) ? imagecreatefromjpeg($image_path) : (($type == IMAGETYPE_PNG) ? imagecreatefrompng($image_path) : imagecreatefromgif($image_path));
                        if ($src) {
                            $dst = imagecreatetruecolor($newW, $newH);
                            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                                imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                                imagealphablending($dst, false);
                                imagesavealpha($dst, true);
                            }
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
                            ($type == IMAGETYPE_JPEG) ? imagejpeg($dst, $image_path, 90) : (($type == IMAGETYPE_PNG) ? imagepng($dst, $image_path, 6) : imagegif($dst, $image_path));
                        }
                    }
                }
            }
        }
    }

    if ($id) {
        // If image_path is empty, we don't want to overwrite the old image with nothing
        if ($image_path == '') {
            $stmt = $conn->prepare("UPDATE register SET name=?, specs=?, stock=?, price_day=?, price_hour=?, deposit_price=? WHERE id=?");
            $stmt->bind_param("ssidddi", $name, $specs, $stock, $priceDay, $priceHour, $deposit, $id);
        } else {
            $stmt = $conn->prepare("UPDATE register SET name=?, specs=?, stock=?, price_day=?, price_hour=?, image=?, deposit_price=? WHERE id=?");
            $stmt->bind_param("ssidssdi", $name, $specs, $stock, $priceDay, $priceHour, $image_path, $deposit, $id);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO register (name, specs, stock, price_day, price_hour, image, deposit_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiddsd", $name, $specs, $stock, $priceDay, $priceHour, $image_path, $deposit);
    }

    if ($stmt->execute()) echo json_encode(["status" => "success"]);
    else echo json_encode(["status" => "error", "message" => $stmt->error]);
    exit;
}

// --- UPDATE STOCK ---
elseif ($action == 'update') {
    $stmt = $conn->prepare("UPDATE register SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $_POST['stock'], $_POST['id']);
    echo json_encode(["status" => $stmt->execute() ? "success" : "error"]);
    exit;
}

// --- DELETE ---
elseif ($action == 'delete') {
    $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    echo json_encode(["status" => $stmt->execute() ? "success" : "error"]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemoryLens | Dashboard</title>
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
            transition: all 0.2s ease;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .navbar {
            background: var(--bg-card);
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
            text-decoration: none;
            color: var(--text-main);
        }

        .nav-brand span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 25px;
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

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
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

        .registration-panel {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .registration-panel.open {
            max-height: 1000px;
            opacity: 1;
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            font-family: inherit;
            margin-top: 5px;
        }

        .upload-area {
            grid-column: span 3;
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
        }

        #imgPrev {
            max-height: 100px;
            margin: 10px auto;
            border-radius: 8px;
            display: none;
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
        }

        th {
            padding: 16px;
            background: #f9fafb;
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            text-align: left;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .product-img {
            width: 60px;
            height: 60px;
            min-width: 60px;
            min-height: 60px;
            border-radius: 12px;
            object-fit: contain;
            object-position: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .badge-price {
            background: #f0f9ff;
            color: var(--accent);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
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
        }

        .edit-btn {
            color: #2563eb;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            margin-right: 10px;
        }

        .delete-btn {
            color: #ef4444;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="register.html" class="nav-link">Register</a>
            <a href="booking.php" class="nav-link">Bookings</a>
            <a href="customer.php" class="nav-link">Customers</a>
            <a href="admin_settings.php" class="nav-link">Settings</a>
        </div>
        <div class="nav-actions">
            <a href="logout.php" class="btn-outline" style="text-decoration: none;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Dashboard Monitor</h1>
                <p>Manage your rental inventory.</p>
            </div>
            <div class="stats-bar">
                <div class="stat-card"><small>Total Models</small><span id="statItems">0</span></div>
                <div class="stat-card"><small>Total Units</small><span id="statStock">0</span></div>
                <button class="btn-action" onclick="toggleForm()">+ Manage Products</button>
            </div>
        </div>

        <div class="registration-panel" id="regPanel">
            <form id="gadgetForm">
                <input type="hidden" id="gadgetId">
                <div class="form-grid">
                    <div style="grid-column: span 3">
                        <label>Model Name</label>
                        <input type="text" id="gName" required>
                    </div>
                    <div>
                        <label>Opening Stock</label>
                        <input type="number" id="gStock" value="1" required>
                    </div>
                    <div>
                        <label>Daily Rate (RM)</label>
                        <input type="number" id="gPriceDay" step="0.01" required>
                    </div>
                    <div>
                        <label>Hourly Rate (RM)</label>
                        <input type="number" id="gPriceHour" step="0.01" required>
                    </div>
                    <div>
                        <label>Deposit (RM)</label>
                        <input type="number" id="gDeposit" step="0.01" required>
                    </div>
                    <div style="grid-column: span 3">
                        <label>Specifications</label>
                        <textarea id="gSpecs" rows="2"></textarea>
                    </div>
                    <div class="upload-area" onclick="document.getElementById('fileIn').click()">
                        <input type="file" id="fileIn" hidden accept="image/*" onchange="handleImage(this)">
                        <div id="uploadTxt">Click to upload photo</div>
                        <img id="imgPrev">
                    </div>
                    <div style="grid-column: span 3; text-align: right;">
                        <button type="submit" class="btn-action">Save Product</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Details</th>
                        <th>Rates & Deposit</th>
                        <th>Stock</th>
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

        async function loadGadgets() {
            const res = await fetch('dashboard.php?action=fetch');
            gadgets = await res.json();
            renderTable();
        }

        function renderTable() {
            const tbody = document.getElementById('inventoryBody');
            tbody.innerHTML = '';
            gadgets.forEach(g => {
                const img = g.image ? g.image : 'https://via.placeholder.com/60';
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img src="${img}" class="product-img">
                                <strong>${g.name}</strong>
                            </div>
                        </td>
                        <td style="font-size:0.8rem; color:gray;">${g.specs || '--'}</td>
                        <td>
                            <span class="badge-price">RM ${g.price_day}/day</span><br>
                            <span class="badge-price" style="background:#fef3c7; color:#92400e;">RM ${g.price_hour}/hour</span><br>
                            <small>Deposit: RM ${g.deposit_price}</small>
                        </td>
                        <td>
                            <div class="stock-control">
                                <button class="stock-btn" onclick="updateStock(${g.id}, -1)">-</button>
                                <span>${g.stock}</span>
                                <button class="stock-btn" onclick="updateStock(${g.id}, 1)">+</button>
                            </div>
                        </td>
                        <td>
                            <button class="edit-btn" onclick="editGadget(${g.id})">Edit</button>
                            <button class="delete-btn" onclick="deleteGadget(${g.id})">Delete</button>
                        </td>
                    </tr>`;
            });
            document.getElementById('statItems').innerText = gadgets.length;
            document.getElementById('statStock').innerText = gadgets.reduce((s, g) => s + parseInt(g.stock), 0);
        }

        function toggleForm() {
            const p = document.getElementById('regPanel');
            p.classList.toggle('open');
            if (!p.classList.contains('open')) {
                document.getElementById('gadgetForm').reset();
                document.getElementById('gadgetId').value = "";
                currentImage = "";
                document.getElementById('imgPrev').style.display = 'none';
            }
        }

        function handleImage(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    currentImage = e.target.result;
                    document.getElementById('imgPrev').src = currentImage;
                    document.getElementById('imgPrev').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('gadgetForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('id', document.getElementById('gadgetId').value);
            fd.append('name', document.getElementById('gName').value);
            fd.append('specs', document.getElementById('gSpecs').value);
            fd.append('stock', document.getElementById('gStock').value);
            fd.append('priceDay', document.getElementById('gPriceDay').value);
            fd.append('priceHour', document.getElementById('gPriceHour').value);
            fd.append('deposit', document.getElementById('gDeposit').value);
            fd.append('image', currentImage);

            const res = await fetch('dashboard.php?action=add', {
                method: 'POST',
                body: fd
            });
            const result = await res.json();
            if (result.status === 'success') {
                toggleForm();
                loadGadgets();
            } else alert(result.message);
        };

        function editGadget(id) {
            const g = gadgets.find(x => x.id == id);
            document.getElementById('gadgetId').value = g.id;
            document.getElementById('gName').value = g.name;
            document.getElementById('gSpecs').value = g.specs;
            document.getElementById('gStock').value = g.stock;
            document.getElementById('gPriceDay').value = g.price_day;
            document.getElementById('gPriceHour').value = g.price_hour;
            document.getElementById('gDeposit').value = g.deposit_price;
            currentImage = ""; // Reset currentImage so we don't overwrite with old path unless user uploads new
            if (!document.getElementById('regPanel').classList.contains('open')) toggleForm();
        }

        async function updateStock(id, change) {
            const g = gadgets.find(x => x.id == id);
            const ns = parseInt(g.stock) + change;
            if (ns < 0) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('stock', ns);
            await fetch('dashboard.php?action=update', {
                method: 'POST',
                body: fd
            });
            loadGadgets();
        }

        async function deleteGadget(id) {
            if (!confirm('Delete this item?')) return;
            const fd = new FormData();
            fd.append('id', id);
            await fetch('dashboard.php?action=delete', {
                method: 'POST',
                body: fd
            });
            loadGadgets();
        }

        loadGadgets();
    </script>
</body>

</html>