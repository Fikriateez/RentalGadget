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

// Fetch all gadgets
$result = $conn->query("SELECT * FROM register ORDER BY id DESC");
$gadgets = [];
while ($row = $result->fetch_assoc()) {
    $gadgets[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Gadget - Products</title>
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

        .logout-link {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 500;
        }

        .logout-link:hover {
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

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: contain;
            object-position: center;
            background: #f8fafc;
        }

        .product-content {
            padding: 20px;

        }

        .product-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .product-specs {
            color: var(--text-dim);
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .product-prices {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .price-item {
            background: #f0f9ff;
            color: var(--accent);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .stock-info {
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-bottom: 15px;
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
            transition: opacity 0.2s ease;
        }

        .rent-btn:hover {
            opacity: 0.9;
        }

        .rent-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .rent-btn-link {
            text-decoration: none;
        }

        .no-products {
            text-align: center;
            color: var(--text-dim);
            padding: 60px 20px;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="product.php" class="nav-brand">Memory<span>Lens</span></a>
        <div class="nav-actions">
            <a href="about.php" class="nav-link">About</a>
            <a href="product.php" class="nav-link">Products</a>
            <a href="my_rentals.php" class="nav-link">My Rentals</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="logout-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Available Gadgets</h1>
            <p>Rent the latest technology for your needs</p>
        </div>

        <div class="products-grid">
            <?php if (empty($gadgets)): ?>
                <div class="no-products">
                    No gadgets available at the moment. Please check back later.
                </div>
            <?php else: ?>
                <?php foreach ($gadgets as $gadget): ?>
                    <div class="product-card">
                        <?php
                        $imageSrc = 'https://via.placeholder.com/420x260?text=No+Image';
                        if (!empty($gadget['image'])) {
                            $rawImage = $gadget['image'];
                            if (strpos($rawImage, 'http://') === 0 || strpos($rawImage, 'https://') === 0 || strpos($rawImage, '/') === 0) {
                                $imageSrc = htmlspecialchars($rawImage);
                            } elseif (strpos($rawImage, 'uploads/') === 0) {
                                $imageSrc = '../ADMIN/php/' . htmlspecialchars($rawImage);
                            } else {
                                $imageSrc = htmlspecialchars($rawImage);
                            }
                        }
                        ?>
                        <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($gadget['name']); ?>" class="product-image">
                        <div class="product-content">
                            <div class="product-name"><?php echo htmlspecialchars($gadget['name']); ?></div>
                            <div class="product-specs"><?php echo htmlspecialchars($gadget['specs'] ?: 'No specifications available'); ?></div>
                            <?php
                            $priceDayValue = $gadget['price_day'] ?? ($gadget['priceDay'] ?? 0);
                            $priceHourValue = $gadget['price_hour'] ?? ($gadget['priceHour'] ?? 0);
                            ?>
                            <div class="product-prices">
                                <span class="price-item">RM <?php echo number_format((float)$priceDayValue, 2); ?>/day</span>
                                <span class="price-item">RM <?php echo number_format((float)$priceHourValue, 2); ?>/hour</span>
                            </div>
                            <div class="stock-info">Available: <?php echo $gadget['stock']; ?> units</div>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="rent.php?id=<?php echo $gadget['id']; ?>" class="rent-btn-link">
                                    <button class="rent-btn" <?php echo ($gadget['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <?php echo ($gadget['stock'] > 0) ? 'Rent Now' : 'Out of Stock'; ?>
                                    </button>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="rent-btn-link">
                                    <button class="rent-btn">
                                        Login to Rent
                                    </button>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>