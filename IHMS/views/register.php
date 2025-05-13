<?php
// Initialize variables
$username = $password = $confirm_password = $email = $full_name = $phone = $user_type = $student_id = $gender = "";
$username_err = $password_err = $confirm_password_err = $email_err = $full_name_err = $user_type_err = $student_id_err = $gender_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email already exists
            $sql = "SELECT user_id FROM users WHERE email = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = trim($_POST["email"]);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already registered.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";     
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate user type
    if(empty(trim($_POST["user_type"]))) {
        $user_type_err = "Please select a user type.";     
    } else {
        $user_type = trim($_POST["user_type"]);
        
        // If student, validate student ID and gender
        if($user_type == "student") {
            // Validate student ID
            if(empty(trim($_POST["student_id"]))) {
                $student_id_err = "Please enter your student ID.";
            } else {
                $student_id = trim($_POST["student_id"]);
            }
            
            // Validate gender
            if(empty(trim($_POST["gender"]))) {
                $gender_err = "Please select your gender.";
            } else {
                $gender = trim($_POST["gender"]);
            }
        } else {
            $student_id = NULL;
            $gender = NULL;
        }
    }
    
    // Get phone if provided
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($full_name_err) && empty($user_type_err) && empty($student_id_err) && empty($gender_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email, full_name, phone, user_type, student_id, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssssss", $param_username, $param_password, $param_email, $param_full_name, $param_phone, $param_user_type, $param_student_id, $param_gender);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            $param_full_name = $full_name;
            $param_phone = $phone;
            $param_user_type = $user_type;
            $param_student_id = $student_id;
            $param_gender = $gender;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                header("location: " . BASE_URL . "?page=login");
                exit;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create an Account</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>?page=register" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                            <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone (Optional)</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $phone; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="user_type" class="form-label">Register as</label>
                            <select name="user_type" id="user_type" class="form-select <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>">
                                <option value="">Select user type</option>
                                <option value="student" <?php echo ($user_type == "student") ? 'selected' : ''; ?>>Student</option>
                                <option value="hostel_manager" <?php echo ($user_type == "hostel_manager") ? 'selected' : ''; ?>>Hostel Manager</option>
                            </select>
                            <span class="invalid-feedback"><?php echo $user_type_err; ?></span>
                        </div>
                        
                        <!-- Student ID Field -->
                        <div id="student_id_field" class="mb-3 <?php echo ($user_type != "student") ? 'd-none' : ''; ?>">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" name="student_id" id="student_id" class="form-control <?php echo (!empty($student_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $student_id; ?>">
                            <span class="invalid-feedback"><?php echo $student_id_err; ?></span>
                        </div>
                        
                        <!-- Gender Field -->
                        <div id="gender_field" class="mb-3 <?php echo ($user_type != "student") ? 'd-none' : ''; ?>">
                            <label class="form-label">Gender</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" <?php echo ($gender == "male") ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_male">
                                    Male
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female" <?php echo ($gender == "female") ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_female">
                                    Female
                                </label>
                            </div>
                            <?php if (!empty($gender_err)) : ?>
                                <div class="text-danger mt-1"><?php echo $gender_err; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="<?php echo BASE_URL; ?>?page=login" class="btn btn-outline-secondary">Already have an account? Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('user_type').addEventListener('change', function() {
    var studentIdField = document.getElementById('student_id_field');
    var genderField = document.getElementById('gender_field');
    
    if (this.value === 'student') {
        studentIdField.classList.remove('d-none');
        genderField.classList.remove('d-none');
    } else {
        studentIdField.classList.add('d-none');
        genderField.classList.add('d-none');
    }
});
</script>