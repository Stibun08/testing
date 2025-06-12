<?php
// Start session
session_start();

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: homepage.php");
    exit;
}

// Initialize variables
$email_err = $password_err = "";

// Include database connection
require_once "db_connection.php";

// Create connection using the function from db_connection.php
$conn = db_connect();

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login_submit"])) {
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before querying database
    if(empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT user_id, name, email, password FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $user_id, $name, $db_email, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $user_id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $db_email;
                            
                            // Handle "remember me" functionality
                            if(isset($_POST["remember"]) && $_POST["remember"] == "on") {
                                // Set cookies for one month (you might want to hash the password for security)
                                setcookie("user_login", $email, time() + (30 * 24 * 60 * 60), "/");
                            } else {
                                // Clear cookies if remember me is not checked
                                if(isset($_COOKIE["user_login"])) {
                                    setcookie("user_login", "", time() - 3600, "/");
                                }
                            }
                            
                            // Redirect user to homepage
                            header("location: homepage.php");
                            exit;
                        } else {
                            // Password is not valid
                            $password_err = "The password you entered is not valid.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $email_err = "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Login</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: url('pics/view.webp');
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            width: 90%;
            margin-top: 10px;
            margin-bottom: 10px;
            max-width: 500px;
            padding: 30px;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-section {
            width: 100%;
        }
        
        h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        .link {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .link:hover {
            text-decoration: underline;
        }
        
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        .register-prompt {
            margin-top: 20px;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .footer-link {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .footer-link:hover {
            color: #3498db;
        }
        
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            background-color: #2ecc71;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to TaskMaster!</h1>
        
        <?php
        // Display registration success message
        if(isset($_GET["registration"]) && $_GET["registration"] == "success") {
            echo '<div class="success-message">Registration successful! You can now login with your credentials.</div>';
        }
        ?>
        
        <div class="form-section">
            <h2>Login</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" required>
                    <span class="error"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                    <span class="error"><?php echo $password_err; ?></span>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember-me" name="remember">
                    <label for="remember-me">Remember me</label>
                </div>
                
                <a href="forgot_password.php" class="link">Forgot Password?</a>
                
                <button type="submit" name="login_submit" style="margin-top: 20px;">Login</button>
            </form>
            
            <div class="register-prompt">
                <p>Don't have an account? <a href="register.php" class="link">Register here</a></p>
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-links">
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
                <a href="#" class="footer-link">Contact Us</a>
            </div>
        </div>
    </div>
</body>
</html>