<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['security']) || $_SESSION['security'] !== "Administrator") {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "gatepass");
if ($conn->connect_error) { die("Database link failure"); }

// Fetch inbound live requests
$result = $conn->query("SELECT * FROM requests WHERE status='Pending' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Central Administrator Console</title>
    <style>
        :root { --primary: #002147; --emerald: #10b981; --royal: #3b82f6; --bg-light: #f4f6f9; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg-light); margin: 0; padding: 25px; }
        
        .admin-header { background: white; padding: 20px 35px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-header h1 { margin: 0; font-size: 26px; color: var(--primary); font-weight: 800; }
        
        .nav-actions { display: flex; gap: 12px; }
        .btn { padding: 11px 22px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; transition: 0.2s; }
        .btn-shift { background: var(--emerald); color: white; }
        .btn-history { background: var(--royal); color: white; }
        .btn-logout { background: #64748b; color: white; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .console-card { background: white; border-radius: 14px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .console-card h2 { margin-top: 0; color: #334155; font-size: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
        th { background: var(--primary); color: white; }
        tr:hover { background: #f8fafc; }
        
        .plate-badge { background: #f1f5f9; padding: 4px 8px; font-family: monospace; font-weight: bold; border-radius: 4px; border: 1px solid #e2e8f0; }
        .act-btn { padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; margin: 0 3px; display: inline-block; }
        .act-approve { background: #d1fae5; color: #065f46; }
        .act-approve:hover { background: #a7f3d0; }
        .act-reject { background: #fee2e2; color: #991b1b; }
        .act-reject:hover { background: #fca5a5; }
    </style>
</head>
<body>

    <div class="admin-header">
        <h1>Admin Control Hub</h1>
        <div class="nav-actions">
            <a href="admin_shifts.php" class="btn btn-shift">📋 View Guard Shifts</a>
            <a href="admin_history.php" class="btn btn-history">🔍 Visitor Logs Archive</a>
            <a href="logout.php" class="btn btn-logout">Sign Out</a>
        </div>
    </div>

    <div class="console-card">
        <h2>Live Entry Authorization Requests (Pending Action)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID Reference</th>
                    <th>Visitor Name</th>
                    <th>Phone Contact</th>
                    <th>Vehicle Plate No</th>
                    <th>Visiting From</th>
                    <th>Purpose Summary</th>
                    <th>System Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>#" . $row['id'] . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['Phone'] ?? '-') . "</td>";
                        echo "<td><span class='plate-badge'>" . htmlspecialchars($row['Vehicle_No']) . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['visitor_from'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($row['purpose'] ?? '-') . "</td>";
                        echo "<td>";
                        echo "<a href='update.php?id=" . $row['id'] . "&status=Approved' class='act-btn act-approve'>✔ Approve</a>";
                        echo "<a href='update.php?id=" . $row['id'] . "&status=Rejected' class='act-btn act-reject'>✖ Reject</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='color: #64748b; padding: 40px; font-style: italic; font-size: 16px;'>🟢 Perfect! There are no pending gate requests requiring processing right now.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>