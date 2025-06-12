<?php
// Database connection file
function db_connect() {
    $db_host = "localhost";  // Change as needed for your environment
    $db_user = "root";       // Change to your database username
    $db_pass = "";           // Change to your database password
    $db_name = "to_do_app"; // Name of the database

    // Create connection
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Function to get user ID by username (updated to match schema)
function get_user_id($username) {
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE name = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $user ? $user['user_id'] : null;
}

// Function to authenticate user login
function authenticate_user($username, $password) {
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, "SELECT user_id, name, password FROM users WHERE name = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}
?>