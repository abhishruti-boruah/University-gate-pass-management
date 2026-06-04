<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if(!isset($_SESSION['security'])){
    header("Location: index.php");
    exit();
}

include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed_statuses = ['Approved', 'Rejected', 'Returned', 'Pending'];

if ($id > 0 && in_array($status, $allowed_statuses)) {
    
    $safe_id = $conn->real_escape_string($id);
    $safe_status = $conn->real_escape_string($status);
    $action_by = $conn->real_escape_string($_SESSION['security']);

    if ($status === 'Approved') {
        $token = uniqid("VISITOR_", true);
        $safe_token = $conn->real_escape_string($token);
        
        $sql = "UPDATE requests 
                SET status='$safe_status', 
                    qr_token='$safe_token', 
                    approved_by='$action_by',
                    Entry_time=NOW() 
                WHERE id='$safe_id'";
    } 
    elseif ($status === 'Rejected') {
        $sql = "UPDATE requests 
                SET status='$safe_status',
                    approved_by='$action_by' 
                WHERE id='$safe_id'";
    }
    elseif ($status === 'Returned') {
        $sql = "UPDATE requests 
                SET status='$safe_status', 
                    returned_by='$action_by',
                    exit_time=NOW() 
                WHERE id='$safe_id'";
    } 
    else {
        $sql = "UPDATE requests 
                SET status='$safe_status' 
                WHERE id='$safe_id'";
    }

    if ($conn->query($sql) === TRUE) {
        // Safe context checking to route admins and guards to their respective panels
        if (isset($_SESSION['admin_logged_in']) || strtolower($_SESSION['security']) === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: guard_dashboard.php");
        }
        exit();
    } else {
        die("Error updating gatepass record: " . htmlspecialchars($conn->error));
    }

} else {
    die("Error: Invalid transaction parameter references.");
}
?>