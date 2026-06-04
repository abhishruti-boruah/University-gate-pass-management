<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* =========================
   SECURITY ACCESS CHECK
========================= */
if(!isset($_SESSION['security'])){
    die("Unauthorized Access Panel Context.");
}

/* =========================
   DATABASE CONNECTION
========================= */
$conn = new mysqli("localhost", "root", "", "gatepass");

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

/* =========================
   TOKEN PARSING & PROCESSING
========================= */
if(isset($_GET['token'])){

    // Sanitize inbound scanned token data metrics safely
    $token = $conn->real_escape_string(trim($_GET['token']));

    /* FIND VALID ACTIVE COMPLIANT QR */
    // Checks that token is real and request has been 'Approved' but not yet 'Returned'
    $check = "SELECT * FROM requests WHERE qr_token='$token' AND status='Approved'";
    $result = $conn->query($check);

    /* CASE 1: MATCH FOUND SUCCESS */
    if($result && $result->num_rows > 0){
        
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $returned_by = $conn->real_escape_string($_SESSION['security']);

        /* EXECUTE STATUS RECORDS WRITES */
        // FIXED: Swapped 'return_time' out for 'exit_time' column mapping matching your schema
        // FIXED: Swapped 'CURRENT_TIME' to 'NOW()' to log the full current date and time context
        $update = "UPDATE requests 
                   SET status='Returned', 
                       exit_time = NOW(), 
                       returned_by='$returned_by' 
                   WHERE id='$id'";

        if($conn->query($update)){
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Scan Success</title>
                <style>
                    body{
                        margin:0;
                        padding:0;
                        background:#f4f4f4;
                        font-family: Arial, sans-serif;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                        height:100vh;
                    }
                    .success-box{
                        background:white;
                        padding:50px;
                        border-radius:20px;
                        box-shadow:0 8px 20px rgba(0,0,0,0.1);
                        text-align:center;
                        width: 450px;
                    }
                    h1{
                        color:#28a745;
                        margin-bottom:15px;
                        font-size:38px;
                        margin-top: 0;
                    }
                    p{
                        font-size:20px;
                        color:#555;
                        line-height: 1.5;
                    }
                </style>
            </head>
            <body>

            <div class="success-box">
                <h1>✔ QR Verified</h1>
                <p>Visitor Exit Recorded Successfully.<br>Have a safe journey!</p>
            </div>

            <script>
                /* AUDIO TRIGGER INTERACTION NOTIFICATION */
                let beep = new Audio("https://actions.google.com/sounds/v1/alarms/beep_short.ogg");
                beep.play();

                /* AUTOMATED REDIRECT LOOP (FIXED: Route back to your active live guard dashboard view) */
                setTimeout(function(){
                    window.location = "guard_dashboard.php";
                }, 2000);
            </script>
            </body>
            </html>
            <?php
            exit();
        }
        else{
            echo "
            <script>
            alert('Database Update Error: " . addslashes($conn->error) . "');
            window.location='scanner.php';
            </script>
            ";
            exit();
        }
    }
    /* CASE 2: INVALID OR TWICE USED QR PASS */
    else{
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invalid QR Pass</title>
            <style>
                body{
                    margin:0;
                    padding:0;
                    background:#f4f4f4;
                    font-family: Arial, sans-serif;
                    display:flex;
                    justify-content:center;
                    align-items:center;
                    height:100vh;
                }
                .error-box{
                    background:white;
                    padding:45px;
                    border-radius:18px;
                    box-shadow:0 8px 20px rgba(0,0,0,0.1);
                    text-align:center;
                    width: 450px;
                }
                h1{
                    color:#dc3545;
                    font-size:36px;
                    margin-top: 0;
                    margin-bottom: 15px;
                }
                p {
                    font-size: 18px;
                    color: #666;
                }
            </style>
        </head>
        <body>

        <div class="error-box">
            <h1>✖ Access Denied</h1>
            <p>This QR Code is either invalid, unapproved, or has already been used for departure checkout.</p>
        </div>

        <script>
            /* AUTOMATED BOUNCE TO SCANNER DESK AFTER TIMEOUT */
            setTimeout(function(){
                window.location='scanner.php';
            }, 2500);
        </script>
        </body>
        </html>
        <?php
        exit();
    }
}
/* NO TOKEN PROVIDED IN URL STRINGS */
else{
    header("Location: scanner.php");
    exit();
}
?>