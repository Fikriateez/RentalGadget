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

// Filter/search logic
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '') {
    $like = "%" . $conn->real_escape_string($search) . "%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
}

$stmt->execute();
$result = $stmt->get_result();
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Customers</title>
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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

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

        .nav-link:hover, .nav-link.active {
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
            font-size: 2.5rem;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-dim);
            font-size: 1rem;
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .search-bar button {
            padding: 12px 18px;
            border-radius: 10px;
            border: none;
            background: var(--accent);
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-dim);
            font-weight: 500;
        }

        .customers-table {
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

        th, td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        th {
            background: var(--bg-body);
            font-weight: 600;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            color: var(--text-dim);
        }

        .no-results {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-dim);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="php/dashboard.php" class="nav-brand">
            Memory<span>Lens</span>
        </a>

        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="register.html" class="nav-link">Register</a>
            <a href="booking.php" class="nav-link">Bookings</a>
            <a href="customer.php" class="nav-link active">Customers</a>
            <a href="admin_settings.php" class="nav-link">Settings</a>
        </div>

        <div class="nav-actions">
            <a href="logout.php" class="btn-outline" style="text-decoration: none;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Customer List</h1>
            <p>View and filter all registered customers.</p>
        </div>

        <form class="search-bar" method="get" action="customer.php">
            <input type="text" name="q" placeholder="Search by name, email, or phone" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a href="customer.php" style="align-self: center; font-weight: 600; color: var(--accent); text-decoration: none;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($customers); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars($search !== '' ? "Filter: $search" : "All users"); ?></div>
                <div class="stat-label">Current View</div>
            </div>
        </div>

        <div class="customers-table">
            <?php if (empty($customers)): ?>
                <div class="no-results">
                    No customers found. Try a different search term or make sure there are users registered.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>#<?php echo $customer['id']; ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($customer['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
