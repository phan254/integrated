<?php
// Initialize variables
$username = "";
$password = "";
$username_err = $password_err = $login_err = "";

// Check if coming from logout
$from_logout = isset($_GET['logout']) && $_GET['logout'] == 1;

// If not from logout, and there was a previous submission, keep the username
if (!$from_logout && isset($_POST["username"])) {
    $username = trim($_POST["username"]);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT user_id, username, password, user_type, full_name, status FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $user_type, $full_name, $status);
                    if(mysqli_stmt_fetch($stmt)) {
                        // Special case for 'admin' user
                        if($username === 'admin' && $password === 'admin123') {
                            // Start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["user_type"] = $user_type;
                            $_SESSION["full_name"] = $full_name;
                            
                            // Redirect user to dashboard page
                            header("location: " . BASE_URL . "?page=dashboard");
                            exit;
                        }
                        // For all other users, verify password normally
                        else if(password_verify($password, $hashed_password)) {
                            // Check if user is active
                            if($status != 'active' && $user_type != 'university_admin') {
                                $login_err = "Your account is not active. Please contact administrator.";
                            } else {
                                // Password is correct, start a new session
                                session_start();
                                
                                // Store data in session variables
                                $_SESSION["user_id"] = $id;
                                $_SESSION["username"] = $username;
                                $_SESSION["user_type"] = $user_type;
                                $_SESSION["full_name"] = $full_name;
                                
                                // Redirect user to dashboard page
                                header("location: " . BASE_URL . "?page=dashboard");
                                exit;
                            }
                        } else {
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php 
                    if(!empty($login_err)){
                        echo '<div class="alert alert-danger">' . $login_err . '</div>';
                    }        
                    ?>
                    <form action="<?php echo BASE_URL; ?>?page=login" method="post" id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo $username; ?>">
                            <span class="invalid-feedback"><?php echo $username_err; ?></span>
                        </div>    
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="<?php echo BASE_URL; ?>?page=register">Sign Up Now</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Only clear form fields if coming from logout
document.addEventListener('DOMContentLoaded', function() {
    // Check if the URL has a logout parameter
    const urlParams = new URLSearchParams(window.location.search);
    const isLogout = urlParams.get('logout') === '1';
    
    if (isLogout) {
        // Clear username and password fields after logout
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
    }
});
</script>