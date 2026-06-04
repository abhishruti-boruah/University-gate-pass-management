<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include your database connection
include 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'];

    // Auto-detect whether your user table uses 'guard_name' or 'username' to prevent column errors
    $user_column = '';
    $pass_column = '';
    
    $structure_query = $conn->query("DESCRIBE `security_users`");
    if ($structure_query) {
        while ($column = $structure_query->fetch_assoc()) {
            $field_name = strtolower($column['Field']);
            if ($field_name === 'guard_name' || $field_name === 'username' || $field_name === 'name') {
                $user_column = $column['Field'];
            }
            if ($field_name === 'password' || $field_name === 'pass') {
                $pass_column = $column['Field'];
            }
        }
    }

    if (empty($user_column)) $user_column = 'username';
    if (empty($pass_column)) $pass_column = 'password';

    /* ==========================================
       GUARD REGISTRATION
    ========================================== */
    if ($action_type === "guard_register") {
        $guard_name = $conn->real_escape_string(trim($_POST['guard_name']));
        $password   = trim($_POST['guard_password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check_user = "SELECT * FROM `security_users` WHERE `$user_column`='$guard_name' LIMIT 1";
        $res = $conn->query($check_user);

        if ($res && $res->num_rows > 0) {
            echo "<script>alert('Error: Account already exists.'); window.location='index.php';</script>";
            exit();
        } else {
            $sql = "INSERT INTO `security_users` (`$user_column`, `$pass_column`) VALUES ('$guard_name', '$hashed_password')";
            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Profile Registered!'); window.location='index.php';</script>";
                exit();
            } else {
                echo "Registration failure: " . $conn->error;
                exit();
            }
        }
    }

    /* ==========================================
       GUARD LOGIN (FIXED SHIFT ENTRY)
    ========================================== */
    elseif ($action_type === "guard_login") {
        $guard_name = $conn->real_escape_string(trim($_POST['guard_name']));
        $password   = trim($_POST['guard_password']);

        $search = "SELECT * FROM `security_users` WHERE `$user_column`='$guard_name' LIMIT 1";
        $search_res = $conn->query($search);

        if ($search_res && $search_res->num_rows > 0) {
            $user_row = $search_res->fetch_assoc();
            
            if (password_verify($password, $user_row[$pass_column]) || $password === $user_row[$pass_column]) {
                
                // Set the session variable your dashboard requires
                $_SESSION['security'] = $guard_name; 

                // Look for the guard's ID in the user row, default to '1' if not explicitly found
                $guard_id_val = isset($user_row['id']) ? $user_row['id'] : (isset($user_row['guard_id']) ? $user_row['guard_id'] : '1');

                // FIX: Provide BOTH guard_name AND guard_id so the table does not crash on default values
                $shift_sql = "INSERT INTO `security_shifts` (guard_name, guard_id, login_time) VALUES ('$guard_name', '$guard_id_val', NOW())";
                $conn->query($shift_sql);

                // Redirect directly to the dashboard
                header("Location: guard_dashboard.php");
                exit();
            } else {
                echo "<script>alert('Access Denied: Incorrect Password.'); window.location='index.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Profile Not Found: Guard user does not exist.'); window.location='index.php';</script>";
            exit();
        }
    }
}
?>