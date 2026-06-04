<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$visitor_record = null;
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phone = $conn->real_escape_string(trim($_POST['phone_number']));
    
    // Server-side validation to ensure it's exactly 10 digits
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = "Please enter a valid 10-digit phone number.";
    } else {
        // Find the most recent gate pass request matching this mobile number
        $query = $conn->query("SELECT * FROM `requests` WHERE `phone`='$phone' OR `Phone`='$phone' ORDER BY `id` DESC LIMIT 1");
        
        if ($query && $query->num_rows > 0) {
            $visitor_record = $query->fetch_assoc();
        } else {
            $error_message = "No active gate pass request found for this phone number.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retrieve Gate Pass QR Code</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary-color: #002147;
            --accent-color: #3b82f6;
            --text-dark: #222222;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: url('gate.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
        }

        .blur-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.55); z-index: 1;
        }
        
        /* Matches your single card overlay style perfectly */
        .portal-card {
            position: relative; z-index: 5; width: 480px;
            background: rgba(255, 255, 255, 0.95); padding: 40px 35px;
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            box-sizing: border-box; text-align: center;
        }
        
        .portal-title {
            color: var(--primary-color); font-size: 26px; font-weight: 800;
            margin-top: 0; margin-bottom: 12px;
        }
        
        .portal-subtitle {
            color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.5;
        }
        
        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        
        .input-field {
            width: 100%; padding: 12px 15px; border: 1px solid #ccc;
            border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none;
        }
        .input-field:focus { border-color: var(--primary-color); }
        
        .btn-submit {
            width: 100%; background: var(--primary-color); color: white; border: none;
            padding: 14px; font-size: 16px; font-weight: bold; border-radius: 8px;
            cursor: pointer; transition: background 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { background: #001127; }
        
        .error-banner {
            background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px;
            margin-bottom: 20px; font-size: 14px; border: 1px solid #fca5a5;
        }
        
        .result-container {
            margin-top: 30px; padding-top: 25px; border-top: 2px dashed #cbd5e1;
        }
        
        .status-badge {
            display: inline-block; padding: 6px 16px; border-radius: 20px;
            font-size: 13px; font-weight: bold; margin-bottom: 15px;
        }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-returned { background: #e0e7ff; color: #3730a3; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        #qrcode {
            display: inline-block; padding: 15px; background: white;
            border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            margin: 15px 0; border: 1px solid #e2e8f0;
        }
        
        .visitor-name { font-size: 20px; font-weight: bold; color: var(--primary-color); margin: 5px 0; }
        
        .nav-back {
            display: inline-block; margin-top: 25px; color: var(--primary-color);
            text-decoration: none; font-size: 14px; font-weight: 600;
        }
        .nav-back:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="blur-overlay"></div>

    <div class="portal-card">
        <h2 class="portal-title">Get Visitor QR Pass</h2>
        <p class="portal-subtitle">Lost your pass window? Enter your registered mobile contact number below to fetch your current active QR code pass instantly.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="get_qr.php">
            <div class="form-group">
                <label for="phone_number">Phone Number *</label>
                <input 
                    type="tel" 
                    id="phone_number" 
                    name="phone_number" 
                    class="input-field" 
                    placeholder="Enter 10-Digit Contact Number" 
                    pattern="[0-9]{10}" 
                    maxlength="10" 
                    required 
                    autocomplete="off"
                    title="Phone number must be exactly 10 digits without spaces or special characters."
                    value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                >
            </div>
            <button type="submit" class="btn-submit">Find My Gate Pass</button>
        </form>

        <?php if ($visitor_record): 
            $status = $visitor_record['status'] ?? $visitor_record['Status'] ?? 'Pending';
            $qr_token = $visitor_record['qr_token'] ?? '';
            
            $badge_style = "status-pending";
            if ($status === 'Approved') $badge_style = "status-approved";
            if ($status === 'Returned') $badge_style = "status-returned";
            if ($status === 'Rejected') $badge_style = "status-rejected";
        ?>
            <div class="result-container">
                <div class="visitor-name"><?php echo htmlspecialchars($visitor_record['name'] ?? 'Visitor'); ?></div>
                <div style="font-size: 14px; color: #64748b; margin-bottom: 12px;">Purpose: <?php echo htmlspecialchars($visitor_record['purpose'] ?? 'Campus Visit'); ?></div>
                
                <div>
                    <span class="status-badge <?php echo $badge_style; ?>">
                        Pass Status: <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>

                <?php if (!empty($qr_token)): ?>
                    <div id="qrcode"></div>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">Present this QR pass image to the security guard stationed at the entrance checkpoint.</p>
                    
                    <script type="text/javascript">
                        document.getElementById("qrcode").innerHTML = "";
                        new QRCode(document.getElementById("qrcode"), {
                            text: "<?php echo $qr_token; ?>",
                            width: 180,
                            height: 180,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    </script>
                <?php else: ?>
                    <p style="color: #ef4444; font-size: 14px; font-weight: bold; margin-top: 15px;">No active data token associated with this entry.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="nav-back">← Back to Main Request Form</a>
    </div>

</body>
</html>