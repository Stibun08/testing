<?php
// Initialize error variables
$email_err = $password_err = "";
 
// Processing form data when form is submitted
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
    
    // Check input errors before processing login
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
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $db_email;
                            
                            // Handle "remember me" checkbox
                            if(isset($_POST["remember"]) && $_POST["remember"] == "on") {
                                // Set cookies for one month
                                setcookie("user_login", $email, time() + (30 * 24 * 60 * 60), "/");
                                setcookie("user_password", $password, time() + (30 * 24 * 60 * 60), "/");
                            } else {
                                // If remember me is not checked, unset cookies
                                if(isset($_COOKIE["user_login"])) {
                                    setcookie("user_login", "", time() - 3600, "/");
                                }
                                if(isset($_COOKIE["user_password"])) {
                                    setcookie("user_password", "", time() - 3600, "/");
                                }
                            }
                            
                            // Redirect user to homepage
                            header("location: homepage.php");
                        } else {
                            // Display an error message if password is not valid
                            $password_err = "The password you entered is not valid.";
                        }
                    }
                } else {
                    // Display an error message if email doesn't exist
                    $email_err = "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>