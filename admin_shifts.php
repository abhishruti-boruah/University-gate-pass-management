<?php
session_start();

if (!isset($_SESSION['security']) || $_SESSION['security'] !== "Administrator") {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "gatepass");
$result = $conn->query("SELECT * FROM security_shifts ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guard Duty Shift Logs</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .header-container { width: 95%; margin: 10px auto 20px auto; display: flex; justify-content: space-between; align-items: center; }
        h1 { color: #002147; margin: 0; font-size: 30px; }
        .back-btn { background: #475569; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; }
        .table-wrapper { width: 95%; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #eee; font-size: 15px; }
        th { background: #10b981; color: white; }
        .status-badge { font-weight: bold; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
        .active { background: #d1fae5; color: #065f46; }
        .completed { background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>

<div class="header-container">
    <h1>Guard Shift Attendance History</h1>
    <a href="admin.php" class="back-btn">← Back to Admin Hub</a>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Shift ID</th>
                <th>Guard Name</th>
                <th>Guard ID</th>
                <th>Login Time</th>
                <th>Logout Time</th>
                <th>Total Shift Duration</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>#" . $row['id'] . "</td>";
                    echo "<td><strong>" . htmlspecialchars($row['guard_name']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['guard_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
                    
                    if (empty($row['logout_time'])) {
                        echo "<td style='color: #aaa; font-style: italic;'>In Progress</td>";
                        echo "<td style='color: #aaa;'>-</td>";
                        echo "<td><span class='status-badge active'>🟢 On Duty</span></td>";
                    } else {
                        echo "<td>" . htmlspecialchars($row['logout_time']) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['shift_duration']) . "</strong></td>";
                        echo "<td><span class='status-badge completed'>Completed</span></td>";
                    }
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='color:#777; padding:30px; font-style:italic;'>No dynamic shifts logged yet.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>