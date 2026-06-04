<?php
// Ensure no spaces or HTML exist before the opening PHP tag
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gatepass";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>