<?php

session_start();

if(!isset($_SESSION['security'])){
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Scan Visitor QR Code</title>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        body{
            margin:0;
            padding:0;
            font-family: Arial, sans-serif;
            background:#f4f4f4;
            text-align:center;
        }

        .container{
            margin-top:40px;
        }

        h1{
            font-size:42px;
            color: #002147;
            margin-bottom:30px;
        }

        /* CAMERA READER INTERFACE CONTAINER */
        #reader{
            width:420px;
            margin:auto;
            background:white;
            padding:20px;
            border-radius:18px;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .back-btn{
            display:inline-block;
            margin-top:30px;
            background:#002147;
            color:white;
            padding:14px 30px;
            border-radius:10px;
            text-decoration:none;
            font-size:18px;
            font-weight:bold;
            transition:0.3s;
        }

        .back-btn:hover{
            background:#001124;
        }

        @media(max-width:600px){
            h1{ font-size:32px; }
            #reader{ width:92%; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<div class="container">

    <h1>Scan Visitor QR</h1>

    <div id="reader"></div>

    <a href="guard_dashboard.php" class="back-btn">← Back To Dashboard</a>

</div>

<script>
/* SUCCESS CAPTURE HANDLER OVERVIEW */
function onScanSuccess(decodedText){
    
    /* SHUT DOWN CAMERA CAPTURE ACTIVE STREAMS */
    html5QrcodeScanner.clear();

    /* BEEP AUDIO NOTIFICATION ON TRIGGER SUCCESS */
    let beep = new Audio("https://actions.google.com/sounds/v1/alarms/beep_short.ogg");
    beep.play();

    /* EXECUTE REDIRECTION (FIXED: Redirects token directly into return.php) */
    window.location.href = "return.php?token=" + encodeURIComponent(decodedText);
}

/* START LIVE SCANNER FRAMEWORK MODALS */
let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", 
    { 
        fps: 10, 
        qrbox: 250 
    }
);

html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>