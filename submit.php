<?php

/* =========================
   DATABASE CONNECTION
========================= */
$conn = new mysqli("localhost", "root", "", "gatepass");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =========================
   GET & SANITIZE FORM DATA 
========================= */
// FIXED: Keys updated to match the exact case-sensitive POST parameters sent from index.php
$name         = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
$phone        = isset($_POST['Phone']) ? $conn->real_escape_string(trim($_POST['Phone'])) : '';
$vehicle_no   = isset($_POST['Vehicle_No']) ? $conn->real_escape_string(strtoupper(trim($_POST['Vehicle_No']))) : '';
$visitor_from = isset($_POST['visitor_from']) ? $conn->real_escape_string(trim($_POST['visitor_from'])) : '';
$purpose      = isset($_POST['purpose']) ? $conn->real_escape_string(trim($_POST['purpose'])) : '';

/* =========================
   VALIDATION CHECK
========================= */
if (empty($name) || empty($phone) || empty($purpose)) {
    die("<div style='text-align:center; margin-top:50px; font-family:Arial; color:red;'><h2>Error: All mandatory fields must be filled out.</h2></div>");
}

/* =========================
   INSERT INTO DATABASE (FIXED Column Mappings)
========================= */
// Added Entry_time = NOW() to properly timestamp the pass initialization record.
$sql = "INSERT INTO requests (
            name, 
            Phone, 
            Vehicle_No, 
            visitor_from, 
            purpose, 
            status,
            Entry_time
        ) VALUES (
            '$name', 
            '$phone', 
            '$vehicle_no', 
            '$visitor_from', 
            '$purpose', 
            'Pending',
            NOW()
        )";

/* =========================
   EXECUTE QUERY & REDIRECT
========================= */
if($conn->query($sql) === TRUE){

    /* GET RECENT INSERT ID POINTER */
    $id = $conn->insert_id;

    /* REDIRECT TO LIVE REFRESH STATUS PAGE */
    header("Location: status.php?id=$id");
    exit();

} else {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Submission Error</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f4f4f4;
            margin: 0;
        }

        .container{
            width:500px;
            margin:100px auto;
            background:white;
            padding:40px 30px;
            border-radius:12px;
            text-align:center;
            box-shadow:0px 4px 15px rgba(0,0,0,0.1);
        }

        .error{
            color:#dc3545;
            font-size:26px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .debug-msg {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            font-family: monospace;
            text-align: left;
            margin-top: 20px;
            overflow-x: auto;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #002147;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="error">Failed To Submit Request</div>
    <p style="color: #666;">There was a problem saving your visitor information profile to the server logs.</p>

    <div class="debug-msg">
        <strong>SQL Error Trace:</strong><br>
        <?php echo htmlspecialchars($conn->error); ?>
    </div>

    <a href="index.php" class="back-btn">← Go Back To Form</a>

</div>

</body>
</html>
<?php
}
?>