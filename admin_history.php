<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* =========================
   LOGIN PROTECTION
========================= */
if (!isset($_SESSION['security'])) {
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
   FILTER & SEARCH ENGINE LOGIC
========================= */
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date   = isset($_GET['date']) ? trim($_GET['date']) : '';

// Base query string
$sql = "SELECT * FROM requests WHERE 1=1";

if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    
    if ($filter == 'name') {
        $sql .= " AND name LIKE '%$safe_search%'";
    } elseif ($filter == 'Phone') {
        $sql .= " AND Phone LIKE '%$safe_search%'";
    } elseif ($filter == 'Vehicle_No') {
        $sql .= " AND Vehicle_No LIKE '%$safe_search%'";
    } else {
        // Global search fallback
        $sql .= " AND (name LIKE '%$safe_search%' OR Phone LIKE '%$safe_search%' OR Vehicle_No LIKE '%$safe_search%')";
    }
}

if (!empty($date)) {
    $safe_date = $conn->real_escape_string($date);
    // Matches the date portion of the Entry_time DATETIME column
    $sql .= " AND DATE(Entry_time) = '$safe_date'";
}

$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gate Pass Logs History</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .header-container {
            width: 95%;
            margin: 10px auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #1f1f1f;
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }

        .back-btn {
            background: #d9534f;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .back-btn:hover {
            background: #c9302c;
            transform: translateY(-1px);
        }

        /* SEARCH BAR INTERFACE */
        .search-container {
            width: 95%;
            margin: 0 auto 20px auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        select, input[type="text"], input[type="date"] {
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        select:focus, input:focus {
            border-color: #007bff;
        }

        input[type="text"] {
            width: 250px;
        }

        .search-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-btn:hover {
            background: #2563eb;
        }

        .clear-btn {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        /* DATA TABLE STYLE */
        .table-wrapper {
            width: 95%;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
        }

        th {
            background: #3b82f6;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background: #f9fafb;
        }

        /* STATUS BADGES */
        .status {
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
        }
        .Pending { background: #ffeeba; color: #856404; }
        .Approved { background: #cce5ff; color: #004085; }
        .Rejected { background: #f8d7da; color: #721c24; }
        .Returned { background: #d4edda; color: #155724; }

        .plate-box {
            background: #f0f0f0;
            padding: 4px 8px;
            font-family: monospace;
            font-weight: bold;
            border-radius: 4px;
            color: #333;
        }
    </style>
</head>
<body>

<div class="header-container">
    <h1>Gate Pass History</h1>
    <a href="guard_dashboard.php" class="back-btn">← Back To Dashboard</a>
</div>

<form method="GET" action="admin_history.php">
    <div class="search-container">
        <select name="filter">
            <option value="">Select Filter</option>
            <option value="name" <?php if($filter == 'name') echo 'selected'; ?>>Name</option>
            <option value="Phone" <?php if($filter == 'Phone') echo 'selected'; ?>>Phone</option>
            <option value="Vehicle_No" <?php if($filter == 'Vehicle_No') echo 'selected'; ?>>Vehicle No</option>
        </select>

        <input type="text" name="search" placeholder="Type search term..." value="<?php echo htmlspecialchars($search); ?>">
        
        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">

        <button type="submit" class="search-btn">Search</button>
        <?php if(!empty($search) || !empty($date)): ?>
            <a href="admin_history.php" class="clear-btn">Clear Filters</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Vehicle No</th>
                <th>Visitor From</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Approved By</th>
                <th>Returned By</th>
                <th>Request Time</th>
                <th>Return Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['Phone'] ?? '-') . "</td>";
                    
                    /* FIXED Case-Sensitivity Column key mapping match string */
                    if (!empty($row['Vehicle_No'])) {
                        echo "<td><span class='plate-box'>" . htmlspecialchars($row['Vehicle_No']) . "</span></td>";
                    } else {
                        echo "<td><span style='color:#aaa;'>-</span></td>";
                    }
                    
                    echo "<td>" . htmlspecialchars($row['visitor_from'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['purpose'] ?? '-') . "</td>";
                    echo "<td><span class='status " . $row['status'] . "'>" . $row['status'] . "</span></td>";
                    echo "<td>" . htmlspecialchars($row['approved_by'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['returned_by'] ?? '-') . "</td>";
                    
                    /* FIXED Timestamps Display Uniformity */
                    echo "<td>" . htmlspecialchars($row['Entry_time'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($row['exit_time'] ?? '-') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='11' style='color: #777; padding: 30px; font-style: italic;'>No history records match your search criteria.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>