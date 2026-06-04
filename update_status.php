<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$conn = new mysqli("localhost", "root", "", "gatepass");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =========================
   GET PARAMS & SANITIZE INPUTS
========================= */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Valid status types allowed by your system architecture
$allowed_statuses = ['Approved', 'Rejected', 'Returned', 'Pending'];

if ($id > 0 && in_array($status, $allowed_statuses)) {
    
    $safe_id = $conn->real_escape_string($id);
    $safe_status = $conn->real_escape_string($status);
    $approved_by = $conn->real_escape_string($_SESSION['security']);

    /* =========================
       DYNAMIC CONFIGURATION DEPENDING ON ACTION
    ========================= */
    if ($status === 'Approved') {
        // Generate the vital tracking variables required by status.php and return.php
        $token = uniqid("VISITOR_", true);
        $safe_token = $conn->real_escape_string($token);
        
        $sql = "UPDATE requests 
                SET status='$safe_status', 
                    qr_token='$safe_token', 
                    approved_by='$approved_by',
                    Entry_time=NOW() 
                WHERE id='$safe_id'";
    } 
    elseif ($status === 'Returned') {
        // If updating a checkout through this unified endpoint
        $sql = "UPDATE requests 
                SET status='$safe_status', 
                    returned_by='$approved_by',
                    exit_time=NOW() 
                WHERE id='$safe_id'";
    } 
    else {
        // Standard baseline updates (e.g., Rejections or resetting to Pending)
        $sql = "UPDATE requests 
                SET status='$safe_status' 
                WHERE id='$safe_id'";
    }

    /* =========================
       EXECUTE QUERY & REDIRECT
    ========================= */
    if ($conn->query($sql) === TRUE) {
        header("Location: guard_dashboard.php");
        exit();
    } else {
        echo "Error updating record: " . htmlspecialchars($conn->error);
    }

} else {
    echo "Error: Invalid request parameters or unauthorized status assignment request.";
}

$conn->close();
?>