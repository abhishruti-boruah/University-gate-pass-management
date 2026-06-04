<?php

session_start();

/* =========================
   LOGIN PROTECTION (FIXED Key name to match your system)
========================= */
if(!isset($_SESSION['security'])){
    header("Location: index.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
========================= */
$conn = new mysqli("localhost", "root", "", "gatepass");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =========================
   GET REQUEST ID (Cleaned up safely)
========================= */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    /* =========================
       UPDATE STATUS
    ========================= */
    // Using string escaping to protect your SQL statement execution path
    $safe_id = $conn->real_escape_string($id);
    $sql = "UPDATE requests SET status='Rejected' WHERE id='$safe_id'";

    /* =========================
       EXECUTE (FIXED Redirection Destination)
    ========================= */
    if($conn->query($sql) === TRUE){
        // Changed from admin.php to your true live panel view file
        header("Location: guard_dashboard.php");
        exit();
    } else {
        echo "Error rejecting request: " . $conn->error;
    }
} else {
    echo "Error: Invalid ID provided.";
}

?>