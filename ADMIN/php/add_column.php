<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add price_hour column if it doesn't exist
$sql = "ALTER TABLE register ADD COLUMN price_hour DECIMAL(10,2) DEFAULT 0.00";
if ($conn->query($sql) === TRUE) {
    echo "Column price_hour added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>