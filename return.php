<?php

session_start();

/* =========================
   LOGIN PROTECTION (FIXED: Session Token Key)
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
   GET PARAMS (FIXED: Supports both ID clicks and QR Tokens)
========================= */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

/* =========================
   CHECK ACTIVE RECORD
========================= */
if ($id > 0) {
    // If arriving from the Dashboard checkout button link click
    $safe_id = $conn->real_escape_string($id);
    $check_sql = "SELECT * FROM requests WHERE id='$safe_id'";
} elseif (!empty($token)) {
    // If arriving from a live QR scanner camera string redirect
    $safe_token = $conn->real_escape_string($token);
    $check_sql = "SELECT * FROM requests WHERE qr_token='$safe_token'";
} else {
    // Neither parameter is provided
    $check_sql = "SELECT * FROM requests WHERE 1=0";
}

$result = $conn->query($check_sql);

/* =========================
   INVALID LOG RECORD
========================= */
if(!$result || $result->num_rows == 0){
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invalid Request</title>
<style>
body{ font-family:Arial, sans-serif; background:#f4f4f4; margin:0; }
.container{ width:500px; margin:100px auto; background:white; padding:40px 30px; border-radius:12px; text-align:center; box-shadow:0px 4px 15px rgba(0,0,0,0.1); }
.error{ color:#d9534f; font-size:28px; font-weight:bold; margin-bottom: 20px; }
.back-btn { display: inline-block; padding: 10px 20px; background: #002147; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <div class="error">Invalid QR Code / Request ID</div>
    <a href="guard_dashboard.php" class="back-btn">Return to Dashboard</a>
</div>
</body>
</html>
<?php
exit();
}

$row = $result->fetch_assoc();
$target_id = $row['id']; // Lock down the accurate row index item reference

/* =========================
   ALREADY CHECKED OUT
========================= */
if($row['status'] == 'Returned'){
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Already Checked Out</title>
<style>
body{ font-family:Arial, sans-serif; background:#f4f4f4; margin:0; }
.container{ width:500px; margin:100px auto; background:white; padding:40px 30px; border-radius:12px; text-align:center; box-shadow:0px 4px 15px rgba(0,0,0,0.1); }
.warning{ color:#f0ad4e; font-size:28px; font-weight:bold; margin-bottom: 20px; }
.back-btn { display: inline-block; padding: 10px 20px; background: #002147; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <div class="warning">QR Code / Pass Already Used</div>
    <a href="guard_dashboard.php" class="back-btn">Return to Dashboard</a>
</div>
</body>
</html>
<?php
exit();
}

/* =========================
   UPDATE RETURN DATA VALUES
========================= */
// Pulls the active guard name from session cache storage 
$returned_by = $_SESSION['security']; 
$safe_target_id = $conn->real_escape_string($target_id);
$safe_returned_by = $conn->real_escape_string($returned_by);

// FIXED: Maps your case-sensitive database log row tracking name 'exit_time'
$sql = "UPDATE requests 
        SET exit_time = NOW(), 
            status='Returned',
            returned_by='$safe_returned_by'
        WHERE id='$safe_target_id'";

/* =========================
   SUCCESS MESSAGE OUTPUT
========================= */
if($conn->query($sql) === TRUE){
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Departure Recorded</title>
<style>
body{ font-family:Arial, sans-serif; background:#f4f4f4; margin:0; }
.container{ width:550px; margin:60px auto; background:white; padding:40px 30px; border-radius:12px; text-align:center; box-shadow:0px 4px 15px rgba(0,0,0,0.1); }
.success{ color:#28a745; font-size:30px; font-weight:bold; }
.message{ margin-top:20px; font-size:16px; color:#444; line-height:1.8; text-align: left; background: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
.back-btn { display: inline-block; margin-top: 25px; padding: 12px 25px; background: #002147; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <div class="success">Departure Recorded Successfully</div>

    <div class="message">
        <div style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #555;">Thank You / ধন্যবাদ / धन्यवाद</div>
        
        <b>English:</b><br>
        Your exit checkout timestamp has been logged successfully. Have a safe journey!
        <br><br>
        <b>অসমীয়া:</b><br>
        আপোনাৰ প্ৰস্থানৰ সময় সফলভাৱে সংৰক্ষণ কৰা হৈছে। আপোনাৰ যাত্ৰা শুভ হওঁক!
        <br><br>
        <b>हिन्दी:</b><br>
        आपकी प्रस्थान का समय सफलतापूर्वक दर्ज कर लिया गया है। आपकी यात्रा मंगलमय हो!
    </div>

    <br>
    <a href="guard_dashboard.php" class="back-btn">Back To Dashboard Console</a>
</div>
</body>
</html>
<?php
} else {
    echo "Database Update Error: " . $conn->error;
}
?>