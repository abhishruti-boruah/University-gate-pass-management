<?php

$conn = new mysqli("localhost", "root", "", "gatepass");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =========================
   GET REQUEST ID & SANITIZE
========================= */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* =========================
   FETCH RECORD ROWS
========================= */
$row = null;
if ($id > 0) {
    $safe_id = $conn->real_escape_string($id);
    $sql = "SELECT * FROM requests WHERE id='$safe_id'";
    $result = $conn->query($sql);
    if($result) {
        $row = $result->fetch_assoc();
    }
}

// Fallback handling if no tracking data entry matches the string index lookup parameters
if (!$row) {
    die("<div style='text-align:center; margin-top:50px; font-family:Arial; color:red;'><h2>Error: Gate Pass Record Not Found.</h2></div>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Request Status - University Gate Pass</title>
    <style>
        body{
            margin:0;
            padding:0;
            font-family: Arial, sans-serif;
            background: url('gate.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .overlay{
            width:100%;
            min-height:100vh;
            background:rgba(0,0,0,0.55);
            display:flex;
            justify-content:center;
            align-items:center;
            padding: 20px;
        }

        .container{
            width:500px;
            background:white;
            padding:35px 30px;
            border-radius:15px;
            text-align:center;
            box-shadow:0px 8px 25px rgba(0,0,0,0.3);
        }

        h2 { margin-top: 0; margin-bottom: 20px; font-size: 26px; }
        h3 { margin: 15px 0; color: #333; }

        .pending{ color:#fd7e14; }
        .approved{ color:#28a745; }
        .rejected{ color:#dc3545; }
        .returned{ color:#007bff; }

        img{
            margin:15px auto;
            display: block;
            border: 4px solid #eee;
            border-radius: 8px;
            padding: 5px;
            background: #fff;
        }

        .note{
            margin-top:25px;
            font-size:15px;
            color:#444;
            line-height:1.6;
            text-align: left;
            background: #fdfdfd;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .loader{
            border:5px solid #f3f3f3;
            border-top:5px solid #007bff;
            border-radius:50%;
            width:50px;
            height:50px;
            animation:spin 1s linear infinite;
            margin:20px auto;
        }

        @keyframes spin{
            0%{ transform:rotate(0deg); }
            100%{ transform:rotate(360deg); }
        }

        .home-btn {
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

<div class="overlay">
<div class="container">

<?php
/* =========================
   CASE 1: PENDING STATUS
========================= */
if($row['status'] == 'Pending'){
?>
    <h2 class="pending">Waiting For Security Approval...</h2>
    <div class="loader"></div>
    <p style="color: #666;">Please wait while security verifies your campus request credentials.</p>
    
    <script>
        // Automatic live data reloading loop script context every 3 seconds
        setTimeout(function(){
            location.reload();
        }, 3000);
    </script>
<?php
}

/* =========================
   CASE 2: APPROVED STATUS
========================= */
elseif($row['status'] == 'Approved'){
    // FIXED: Encode the raw string qr_token into the visual barcode mapping matrices safely 
    // to prevent premature accidental checkouts from generic camera apps.
    $qr_data = $row['qr_token'];
?>
    <h2 class="approved">Request Approved Successfully</h2>
    <h3>Visitor Departure QR Pass</h3>
    
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=<?php echo urlencode($qr_data); ?>" alt="Gate Pass QR Code">

    <div class="note">
        <div style="text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 10px; color: #555;">Instructions / নিৰ্দেশনা / निर्देश</div>
        <b>English:</b><br>
        Save this QR code pass to display to the gate security guard when leaving campus.
        <br><br>
        <b>অসমীয়া:</b><br>
        কেম্পাছ এৰি যোৱাৰ সময়ত গেটৰ নিৰাপত্তাৰক্ষীক দেখুৱাবলৈ এই QR ক’ডটো সংৰক্ষণ কৰক।
        <br><br>
        <b>हिन्दी:</b><br>
        कैंपस से बाहर जाते समय गेट सुरक्षा गार्ड को दिखाने के लिए इस QR कोड को सेव करें।
    </div>
    
    <a href="index.php" class="home-btn">Create New Request</a>
<?php
}

/* =========================
   CASE 3: REJECTED STATUS
========================= */
elseif($row['status'] == 'Rejected'){
?>
    <h2 class="rejected">Request Denied</h2>
    <p style="color: #555; line-height: 1.5;">Security did not clear your gate entry submission request at this time. Please speak with the administration office desk for manual check-in.</p>
    <a href="index.php" class="home-btn">Return to Form</a>
<?php
}

/* =========================
   CASE 4: RETURNED STATUS
========================= */
elseif($row['status'] == 'Returned'){
?>
    <h2 class="returned">Visit Completed</h2>
    <p style="color:#555; line-height: 1.5;">Your campus departure checkout log trace has already been successfully recorded. Thank you!</p>
    <a href="index.php" class="home-btn">Return to Home</a>
<?php
}
?>

</div>
</div>

</body>
</html>