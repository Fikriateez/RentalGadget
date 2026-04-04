<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "admin";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Change the image column to LONGTEXT so it can hold large Base64 strings
$sql1 = "ALTER TABLE register MODIFY COLUMN image LONGTEXT;";
if ($conn->query($sql1) === TRUE) {
    echo "Successfully updated image column to LONGTEXT.\n";
} else {
    echo "Error updating column: " . $conn->error . "\n";
}

// 2. We can also try increasing max_allowed_packet for this session
// (though it often requires a MySQL restart if it's too small globally)
$sql2 = "SET GLOBAL max_allowed_packet=67108864;"; // 64MB
if ($conn->query($sql2) === TRUE) {
    echo "Successfully increased max_allowed_packet.\n";
} else {
    echo "Note: Could not set global max_allowed_packet: " . $conn->error . "\n";
}

$conn->close();
?>
