<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hardcoded admin login
    if ($username === "admin" && $password === "admin123") {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid username or password!";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MemoryLens Login</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* 1. MINIMALIST BACKGROUND & BODY */
    :root {
        --accent: #0ea5e9; 
        --bg-body: #f8fafc; /* The clean off-white from your body page */
        --bg-card: #ffffff; 
        --border: #e2e8f0; 
        --text-main: #0f172a; 
        --text-dim: #64748b; 
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    }

    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--bg-body); /* Clean background, no images */
        font-family: 'Inter', 'Segoe UI', sans-serif;
        color: var(--text-main);
    }

    /* 2. REFINED MINIMALIST CARD */
    .login-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 24px; 
        width: 100%;
        max-width: 400px;
        padding: 40px;
        text-align: center;
        box-shadow: var(--shadow);
    }

    .logo-container { margin-bottom: 30px; }
    
    .logo-container h1 { 
        margin: 5px 0; 
        font-weight: 700; 
        font-size: 1.8rem;
        letter-spacing: -0.04em;
        color: var(--text-main);
    }
    
    .logo-container img {
        display: block;
        margin: 0 auto 10px;
    }

    h2 { 
        font-weight: 400; 
        font-size: 1rem; 
        margin-bottom: 35px; 
        color: var(--text-dim); 
    }

    /* 3. FORM ELEMENTS */
    .input-group {
        margin-bottom: 15px;
        text-align: left;
    }

    .input-field {
        width: 100%;
        padding: 14px 20px;
        margin: 5px 0;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: #ffffff;
        color: var(--text-main);
        outline: none;
        box-sizing: border-box;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .input-field:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
    }

    /* 4. LOGIN BUTTON (CLEAN & ACCENTED) */
    .btn-login {
        width: 100%;
        padding: 15px;
        margin-top: 15px;
        border-radius: 12px;
        border: none;
        background: var(--accent); 
        color: white;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        transition: all 0.2s ease;
    }

    .btn-login:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
    }

    .btn-login span { 
        grid-column: 2; 
        white-space: nowrap; 
    }

    .btn-login img {
        grid-column: 3;
        justify-self: end;
        margin-right: 10px;
        filter: brightness(0) invert(1); /* Keeps the button logo white */
        opacity: 0.8;
    }

    .footer-links {
        margin-top: 25px;
        font-size: 0.85rem;
        color: var(--text-dim);
    }

    .footer-links a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 500;
    }

    .footer-links a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

    <div class="login-card">
        <div class="logo-container">
            <!-- Ensure path logo3.png is correct relative to your HTML -->
            <img src="../png/logo3.png" width="50" alt="Logo">
            <h1>MemoryLens</h1>
        </div>

        <h2>Ready for your next adventure?</h2>

        <form action="" method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" class="input-field" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" class="input-field" required>
            </div>

            <button type="submit" class="btn-login">
                <span>Log In</span>
            </button>
        </form>

        <div class="footer-links">
            Don't have an account? <a href="#">Sign up</a>
        </div>
    </div>

</body>
</html>
