<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['security'])) {
    $_SESSION['security'] = "Duty_Officer"; 
}

include 'db.php';

// Handle Asynchronous Background AJAX API Scan Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_scan'])) {
    header('Content-Type: application/json');
    $action_by = $conn->real_escape_string($_SESSION['security']);
    $qr_token = $conn->real_escape_string(trim($_POST['qr_token'] ?? ''));

    if (empty($qr_token)) {
        echo json_encode(['success' => false, 'message' => 'Empty or missing token payload data.']);
        exit();
    }

    // Check for an Approved visitor record matching this specific token
    $check_qr = $conn->query("SELECT * FROM `requests` WHERE `qr_token`='$qr_token' AND (`status`='Approved' OR `Status`='Approved') LIMIT 1");
    
    if ($check_qr && $check_qr->num_rows > 0) {
        $row = $check_qr->fetch_assoc();
        $req_id = $row['id'];
        $visitor_name = $row['name'] ?? 'Visitor';
        
        // Update status to Returned
        $update = $conn->query("UPDATE `requests` SET `status`='Returned', `returned_by`='$action_by', `exit_time`=NOW() WHERE `id`='$req_id'");
        
        if ($update) {
            // Recalculate counter metrics to pass back to the UI
            $approved_v = $conn->query("SELECT COUNT(*) as total FROM `requests` WHERE `status`='Approved' OR `Status`='Approved'")->fetch_assoc()['total'] ?? 0;
            $returned_v = $conn->query("SELECT COUNT(*) as total FROM `requests` WHERE `status`='Returned' OR `Status`='Returned'")->fetch_assoc()['total'] ?? 0;

            echo json_encode([
                'success' => true, 
                'message' => 'Success: ' . htmlspecialchars($visitor_name) . ' marked as Returned.',
                'row_id' => $req_id,
                'returned_by' => htmlspecialchars($action_by),
                'approved_count' => $approved_v,
                'returned_count' => $returned_v
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update transaction failed.']);
        }
    } else {
        // Evaluate failure reasons dynamically
        $check_status = $conn->query("SELECT * FROM `requests` WHERE `qr_token`='$qr_token' LIMIT 1");
        if ($check_status && $check_status->num_rows > 0) {
            $status_row = $check_status->fetch_assoc();
            $current_status = $status_row['status'] ?? $status_row['Status'] ?? 'Unknown';
            echo json_encode(['success' => false, 'message' => "Token status is '$current_status'. Only Approved passes can be checked out."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: Invalid or unregistered QR Code Token.']);
        }
    }
    exit();
}

// Handle Traditional Manual Exit Backup Fallback Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_exit_id'])) {
    $action_by = $conn->real_escape_string($_SESSION['security']);
    $req_id = $conn->real_escape_string($_POST['manual_exit_id']);
    $conn->query("UPDATE `requests` SET `status`='Returned', `returned_by`='$action_by', `exit_time`=NOW() WHERE `id`='$req_id'");
    echo "<script>window.location='guard_dashboard.php';</script>";
    exit();
}

// Fetch general system dashboard metrics
$total_v = $conn->query("SELECT COUNT(*) as total FROM `requests`")->fetch_assoc()['total'] ?? 0;
$pending_v = $conn->query("SELECT COUNT(*) as total FROM `requests` WHERE `status`='Pending' OR `Status`='Pending'")->fetch_assoc()['total'] ?? 0;
$approved_v = $conn->query("SELECT COUNT(*) as total FROM `requests` WHERE `status`='Approved' OR `Status`='Approved'")->fetch_assoc()['total'] ?? 0;
$returned_v = $conn->query("SELECT COUNT(*) as total FROM `requests` WHERE `status`='Returned' OR `Status`='Returned'")->fetch_assoc()['total'] ?? 0;

$dataset = $conn->query("SELECT * FROM `requests` ORDER BY `id` DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gate Security Dashboard</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .header-title { font-size: 24px; font-weight: bold; color: #111827; margin: 0; }
        .active-guard { font-size: 14px; color: #6b7280; margin-top: 4px; }
        
        .actions-row { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; justify-content: space-between; }
        .search-container { display: flex; gap: 10px; align-items: center; }
        .search-input { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; width: 280px; font-size: 14px; box-sizing: border-box; }
        .date-input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white; color: #333; }
        .qr-form-container { display: flex; gap: 10px; align-items: center; }
        .btn-action { padding: 10px 20px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; font-size: 14px; color: white; text-decoration: none; }
        .btn-qr { background: #7c3aed; }
        .btn-clear { background: #64748b; padding: 10px 15px; font-size: 13px; }
        .btn-logout { background: #ef4444; }

        /* Integrated Clean Notification Bar UI instead of system alert boxes */
        .toast-banner { display: none; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .toast-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .toast-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }

        .scanner-wrapper { display: none; background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px dashed #7c3aed; }
        #qr-reader { width: 100%; max-width: 480px; margin: 0 auto; background: #fafafa; border-radius: 8px; overflow: hidden; }
        #qr-reader button { background: #7c3aed !important; color: white !important; border: none !important; padding: 8px 16px !important; border-radius: 6px !important; font-weight: bold !important; cursor: pointer !important; margin: 10px 2px !important; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { border-radius: 12px; padding: 20px; color: white; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card-blue { background: #3b82f6; }
        .card-yellow { background: #eab308; }
        .card-green { background: #10b981; }
        .card-purple { background: #8b5cf6; }
        .stat-num { font-size: 32px; font-weight: 800; margin: 5px 0 0 0; }
        .stat-label { font-size: 14px; font-weight: 600; opacity: 0.9; text-transform: uppercase; }

        .table-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th, td { padding: 12px 14px; text-align: left; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        th { background: #3b82f6; color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr { transition: background-color 0.4s ease; }
        tr:hover { background-color: #f8fafc; }

        .badge { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-returned { background: #e0e7ff; color: #3730a3; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }

        .btn-inline { background: none; border: none; color: #3b82f6; font-weight: bold; cursor: pointer; padding: 0; font-size: 13px; text-decoration: underline; }
        .action-link-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; margin-right: 5px; display: inline-block; }
        .lnk-approve { background: #10b981; color: white; }
        .lnk-reject { background: #ef4444; color: white; }
    </style>
</head>
<body>

    <div class="header-bar">
        <div>
            <h1 class="header-title">Gate Security Dashboard</h1>
            <div class="active-guard">Logged Guard Professional: <strong><?php echo htmlspecialchars($_SESSION['security']); ?></strong></div>
        </div>
        <div style="display: flex; gap: 10px;">
            <div class="qr-form-container">
                <button type="button" class="btn-action btn-qr" onclick="toggleScanner()">Scan QR Code</button>
            </div>
            <a href="logout.php" class="btn-action btn-logout">Logout</a>
        </div>
    </div>

    <div id="statusNotification" class="toast-banner"></div>

    <div id="scannerInterface" class="scanner-wrapper">
        <h3 style="margin-top: 0; color: #7c3aed; text-align: center;">Live Gate Pass Scanner Portal</h3>
        <div id="qr-reader"></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card card-blue">
            <div class="stat-label">Total Visitors</div>
            <div class="stat-num" id="stat-total"><?php echo $total_v; ?></div>
        </div>
        <div class="stat-card card-yellow">
            <div class="stat-label">Pending Requests</div>
            <div class="stat-num" id="stat-pending"><?php echo $pending_v; ?></div>
        </div>
        <div class="stat-card card-green">
            <div class="stat-label">Approved Inside</div>
            <div class="stat-num" id="stat-approved"><?php echo $approved_v; ?></div>
        </div>
        <div class="stat-card card-purple">
            <div class="stat-label">Returned Outgoing</div>
            <div class="stat-num" id="stat-returned"><?php echo $returned_v; ?></div>
        </div>
    </div>

    <div class="actions-row">
        <div class="search-container">
            <input type="text" id="tableSearchInput" class="search-input" placeholder="Search by name, phone, vehicle..." onkeyup="filterLogTable()">
            <span style="font-size: 14px; font-weight: 600; margin-left: 10px; color:#475569;">Filter by Date:</span>
            <input type="date" id="tableDateInput" class="date-input" onchange="filterLogTable()">
            <button type="button" class="btn-action btn-clear" onclick="clearFilters()">Reset Filters</button>
        </div>
    </div>

    <div class="table-card">
        <table id="securityLogTable">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Vehicle No</th>
                    <th>Visitor From</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Returned By</th>
                    <th>Request Time</th>
                    <th style="text-align: center; width: 160px;">Action Management Options</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($dataset && $dataset->num_rows > 0) {
                    while ($row = $dataset->fetch_assoc()) {
                        $id = $row['id'];
                        $status = $row['status'] ?? $row['Status'] ?? 'Pending';
                        
                        $badge_class = 'badge-pending';
                        if ($status === 'Approved')  $badge_class = 'badge-approved';
                        if ($status === 'Returned')  $badge_class = 'badge-returned';
                        if ($status === 'Rejected')  $badge_class = 'badge-rejected';
                        
                        $phone_display = $row['phone'] ?? $row['Phone'] ?? '-';
                        $vehicle_display = $row['vehicle_no'] ?? $row['Vehicle_No'] ?? $row['Vehicle_no'] ?? '-';
                        $time_display = $row['request_time'] ?? $row['Request_Time'] ?? $row['created_at'] ?? '-';
                        
                        echo "<tr id='row-id-{$id}'>";
                        echo "<td>" . $id . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['name'] ?? 'Unknown Name') . "</strong></td>";
                        echo "<td>" . htmlspecialchars($phone_display) . "</td>";
                        echo "<td>" . htmlspecialchars($vehicle_display) . "</td>";
                        echo "<td>" . htmlspecialchars($row['visitor_from'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($row['purpose'] ?? '-') . "</td>";
                        echo "<td class='status-cell'><span class='badge {$badge_class}'>" . $status . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['approved_by'] ?? '-') . "</td>";
                        echo "<td class='returned-by-cell'>" . htmlspecialchars($row['returned_by'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($time_display) . "</td>";
                        echo "<td class='action-cell' style='text-align: center;'>";
                        
                        if ($status === 'Pending') {
                            echo "<a href='update.php?id={$id}&status=Approved' class='action-link-btn lnk-approve'>Approve</a>";
                            echo "<a href='update.php?id={$id}&status=Rejected' class='action-link-btn lnk-reject'>Reject</a>";
                        } elseif ($status === 'Approved') {
                            echo "<form method='POST' action='guard_dashboard.php' style='margin:0; display:inline;' onsubmit='return confirm(\"Perform manual override check-out for this visitor record?\");'>";
                            echo "<input type='hidden' name='manual_exit_id' value='{$id}'>";
                            echo "<button type='submit' class='btn-inline' style='color:#ef4444;'>Manual Exit Bypass</button>";
                            echo "</form>";
                        } else {
                            echo "<span style='color: #94a3b8; font-size: 13px; font-style: italic;'>Logged Clear</span>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='11' style='text-align:center; padding: 40px; color: #94a3b8; font-style: italic;'>No records found inside database table log structure.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
    let html5QrcodeScanner = null;

    function showStatusMessage(message, isSuccess) {
        const notifyDiv = document.getElementById('statusNotification');
        notifyDiv.textContent = message;
        notifyDiv.className = isSuccess ? "toast-banner toast-success" : "toast-banner toast-error";
        notifyDiv.style.display = "block";
        
        // Auto-dismiss notification after 4 seconds smoothly
        setTimeout(() => {
            notifyDiv.style.display = "none";
        }, 4000);
    }

    // Standardized instantiation configuration setup for the scanner
    function initScannerInstance() {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", 
            { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
                supportedScanTypes: [
                    Html5QrcodeScanType.SCAN_TYPE_CAMERA,
                    Html5QrcodeScanType.SCAN_TYPE_FILE
                ]
            }
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function toggleScanner() {
        const scannerDiv = document.getElementById('scannerInterface');
        if (scannerDiv.style.display === 'none' || scannerDiv.style.display === '') {
            scannerDiv.style.display = 'block';
            if (!html5QrcodeScanner) {
                initScannerInstance();
            }
        } else {
            scannerDiv.style.display = 'none';
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => { html5QrcodeScanner = null; });
            }
        }
    }

    // Direct background silent sync loop handler
    function onScanSuccess(decodedText, decodedResult) {
        const formData = new FormData();
        formData.append('ajax_scan', '1');
        formData.append('qr_token', decodedText);

        fetch('guard_dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Instantly inject update notice into the top screen banner
                showStatusMessage(data.message, true);
                
                // Track and update the table row elements inside the DOM
                const targetRow = document.getElementById(`row-id-${data.row_id}`);
                if (targetRow) {
                    const statusCell = targetRow.querySelector('.status-cell');
                    statusCell.innerHTML = `<span class="badge badge-returned">Returned</span>`;
                    
                    const returnedByCell = targetRow.querySelector('.returned-by-cell');
                    returnedByCell.textContent = data.returned_by;
                    
                    const actionCell = targetRow.querySelector('.action-cell');
                    actionCell.innerHTML = `<span style="color: #94a3b8; font-size: 13px; font-style: italic;">Logged Clear</span>`;
                    
                    // Smooth green highlighting indicator effect on modified line
                    targetRow.style.backgroundColor = '#d1fae5';
                    setTimeout(() => { targetRow.style.backgroundColor = ''; }, 2500);
                }

                // Sync counter card metrics numbers asynchronously
                document.getElementById('stat-approved').textContent = data.approved_count;
                document.getElementById('stat-returned').textContent = data.returned_count;

                // CRITICAL FIX: Destroys and completely clears the old scanned file image preview
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear().then(() => {
                        // Immediately re-initializes a completely blank, clean scanner area frame
                        initScannerInstance();
                    }).catch(err => {
                        console.error("Failed to clear scanner state:", err);
                    });
                }

            } else {
                showStatusMessage(data.message, false);
                
                // Also reset scanner on failure so a broken code can be swapped instantly
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear().then(() => { initScannerInstance(); });
                }
            }
        })
        .catch(error => {
            console.error('AJAX Sync Failure:', error);
            showStatusMessage('Server connection error. Could not record exit.', false);
        });
    }

    function onScanFailure(error) {}

    function filterLogTable() {
        const textInput = document.getElementById("tableSearchInput");
        const textFilter = textInput.value.toUpperCase();
        const dateInput = document.getElementById("tableDateInput");
        const selectedDate = dateInput.value; 
        const table = document.getElementById("securityLogTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let matchesText = false;
            let matchesDate = true;
            
            const tdElements = tr[i].getElementsByTagName("td");
            if (!tdElements || tdElements.length < 10) continue;

            for (let j = 1; j <= 4; j++) {
                if (tdElements[j]) {
                    const txtValue = tdElements[j].textContent || tdElements[j].innerText;
                    if (txtValue.toUpperCase().indexOf(textFilter) > -1) {
                        matchesText = true;
                        break;
                    }
                }
            }
            if (textFilter === "") { matchesText = true; }

            if (selectedDate !== "") {
                const fullTimestampString = tdElements[9].textContent || tdElements[9].innerText;
                const dynamicRowDate = fullTimestampString.trim().split(" ")[0]; 
                if (dynamicRowDate !== selectedDate) { matchesDate = false; }
            }

            tr[i].style.display = (matchesText && matchesDate) ? "" : "none";
        }
    }

    function clearFilters() {
        document.getElementById("tableSearchInput").value = "";
        document.getElementById("tableDateInput").value = "";
        filterLogTable();
    }
    </script>
</body>
</html>