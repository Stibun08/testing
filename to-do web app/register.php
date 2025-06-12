<?php
// Include database connection
require_once "db_connection.php";

// Create connection using the function from db_connection.php
$conn = db_connect();

// Define variables and initialize with empty values
$name = $email = $password = "";
$name_err = $email_err = $password_err = $terms_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register_submit"])) {
 
    // Validate name
    if(empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate terms
    if(!isset($_POST["terms"]) || $_POST["terms"] != "on") {
        $terms_err = "You must agree to terms and conditions.";
    }
    
    // Check input errors before inserting into database
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($terms_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sss", $param_name, $param_email, $param_password);
            
            // Set parameters
            $param_name = $name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Get the user ID of the newly created user
                $user_id = mysqli_insert_id($conn);
                
                // Record the terms agreement
                $terms_sql = "INSERT INTO terms_agreements (user_id, terms_version) VALUES (?, '1.0')";
                if($terms_stmt = mysqli_prepare($conn, $terms_sql)) {
                    mysqli_stmt_bind_param($terms_stmt, "i", $user_id);
                    mysqli_stmt_execute($terms_stmt);
                    mysqli_stmt_close($terms_stmt);
                }
                
                // Redirect to login page with success message
                header("location: login.php?registration=success");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - Register</title>
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
        
        input[type="text"],
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
        
        .login-prompt {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Join TaskMaster Today!</h1>
        
        <div class="form-section">
            <h2>Create Your Account</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="register-name">Name</label>
                    <input type="text" id="register-name" name="name" required>
                    <span class="error"><?php echo $name_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" required>
                    <span class="error"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" required>
                    <span class="error"><?php echo $password_err; ?></span>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the Terms and Conditions</label>
                    <span class="error"><?php echo $terms_err; ?></span>
                </div>
                
                <button type="submit" name="register_submit" style="margin-top: 20px;">Register</button>
            </form>
            
            <div class="login-prompt">
                <p>Already have an account? <a href="login.php" class="link">Login here</a></p>
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