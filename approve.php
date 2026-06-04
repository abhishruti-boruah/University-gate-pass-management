<?php

session_start();

/* =========================
   LOGIN PROTECTION 
========================= */
if(!isset($_SESSION['security'])){
    header("Location: index.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
========================= */
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "gatepass"
);

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

/* =========================
   GET REQUEST ID & SANITIZE
========================= */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    /* UNIQUE QR TOKEN GENERATION */
    $token = uniqid("VISITOR_", true);

    /* SECURITY LOGGED IN ACCOUNT NAME */
    $approved_by = $_SESSION['security'];

    /* =========================
       UPDATE RECORD STATEMENTS
    ========================= */
    // Updates status to 'Approved', tracks the active guard session, and assigns a unique QR token string
    $safe_id = $conn->real_escape_string($id);
    $safe_token = $conn->real_escape_string($token);
    $safe_approved_by = $conn->real_escape_string($approved_by);

    $sql = "UPDATE requests 
            SET status='Approved', 
                qr_token='$safe_token', 
                approved_by='$safe_approved_by',
                Entry_time=NOW() 
            WHERE id='$safe_id'";

    /* =========================
       EXECUTE & REDIRECT
    ========================= */
    if($conn->query($sql) === TRUE){
        // FIXED: Redirect back to the live checkpoint screen layout
        header("Location: guard_dashboard.php");
        exit();
    } else {
        echo "Database Action Error: " . $conn->error;
    }
} else {
    echo "Error: Invalid request token tracker context.";
}
?>