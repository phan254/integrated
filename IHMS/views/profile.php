<?php
// Set page title
$page_title = "My Profile | IHMS";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Include image handler
require_once 'image_handler.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Initialize variables
$full_name = $email = $phone = $student_id = $current_password = $new_password = $confirm_password = "";
$full_name_err = $email_err = $phone_err = $student_id_err = $current_password_err = $new_password_err = $confirm_password_err = "";

// Handle profile picture upload
if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $upload_result = upload_profile_picture($user_id, $_FILES['profile_picture']);
    
    if($upload_result) {
        $success_message = "Profile picture updated successfully.";
        
        // Refresh user data to get the new profile picture
        $user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
        $user = mysqli_fetch_assoc($user_query);
    } else {
        $error_message = "Failed to upload profile picture. Please try again.";
    }
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    // Validate full name
    if(empty(trim($_POST['full_name']))) {
        $full_name_err = "Please enter your full name.";
    } else {
        $full_name = trim($_POST['full_name']);
    }
    
    // Validate email
    if(empty(trim($_POST['email']))) {
        $email_err = "Please enter your email.";
    } else {
        // Check if email is valid
        if(!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email already exists (excluding current user)
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $param_email, $user_id);
                $param_email = trim($_POST['email']);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already registered.";
                    } else {
                        $email = trim($_POST['email']);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Get phone if provided
    $phone = trim($_POST['phone']);
    
    // Validate student ID if user is a student
    if($user_type == 'student') {
        if(empty(trim($_POST['student_id']))) {
            $student_id_err = "Please enter your student ID.";
        } else {
            $student_id = trim($_POST['student_id']);
        }
    }
    
    // Check input errors before updating
    if(empty($full_name_err) && empty($email_err) && empty($student_id_err)) {
        // Prepare an update statement
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?";
        $params = "sss";
        $param_values = array($full_name, $email, $phone);
        
        // Add student_id parameter if user is a student
        if($user_type == 'student') {
            $sql .= ", student_id = ?";
            $params .= "s";
            $param_values[] = $student_id;
        }
        
        $sql .= " WHERE user_id = ?";
        $params .= "i";
        $param_values[] = $user_id;
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, $params, ...$param_values);
            
            // Attempt to execute
            if(mysqli_stmt_execute($stmt)) {
                // Update session data
                $_SESSION['full_name'] = $full_name;
                
                $success_message = "Profile updated successfully.";
                
                // Refresh user data
                $user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
                $user = mysqli_fetch_assoc($user_query);
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    // Validate current password
    if(empty(trim($_POST['current_password']))) {
        $current_password_err = "Please enter your current password.";
    } else {
        $current_password = trim($_POST['current_password']);
        
        // Verify current password
        if(!password_verify($current_password, $user['password'])) {
            $current_password_err = "Current password is incorrect.";
        }
    }
    
    // Validate new password
    if(empty(trim($_POST['new_password']))) {
        $new_password_err = "Please enter a new password.";
    } elseif(strlen(trim($_POST['new_password'])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST['new_password']);
    }
    
    // Validate confirm password
    if(empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if(empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check input errors before updating
    if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $user_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Attempt to execute
            if(mysqli_stmt_execute($stmt)) {
                $password_success = "Password changed successfully.";
            } else {
                $password_error = "Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group mb-4">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <?php if($user_type == 'student'): ?>
                    <a href="<?php echo BASE_URL; ?>?page=hostels" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i> Find Hostels
                    </a>
                <?php elseif($user_type == 'hostel_manager'): ?>
                    <a href="<?php echo BASE_URL; ?>?page=manage_hostel" class="list-group-item list-group-item-action">
                        <i class="fas fa-hotel me-2"></i> Manage Hostel
                    </a>
                <?php elseif($user_type == 'university_admin'): ?>
                    <a href="<?php echo BASE_URL; ?>?page=manage_users" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Manage Users
                    </a>
                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="list-group-item list-group-item-action">
                        <i class="fas fa-hotel me-2"></i> Manage Hostels
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
                </a>
                <a href="<?php echo BASE_URL; ?>?page=profile" class="list-group-item list-group-item-action active">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
                <a href="<?php echo BASE_URL; ?>?page=logout" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i> My Profile</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <!-- Profile Picture with Upload Form -->
                            <form method="post" action="" enctype="multipart/form-data" id="profile_picture_form">
                                <?php if(!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_picture']; ?>" 
                                         alt="Profile Picture" class="img-thumbnail rounded-circle profile-picture mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="<?php echo BASE_URL; ?>assets/img/user-avatar.jpg" 
                                         alt="Profile Picture" class="profile-picture mb-3"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="profile_picture" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-camera"></i> Change Picture
                                        <input type="file" name="profile_picture" id="profile_picture" 
                                               style="display: none;" accept="image/*" onchange="this.form.submit()">
                                    </label>
                                </div>
                            </form>
                            
                            <h5><?php echo $user['full_name']; ?></h5>
                            <p class="badge <?php 
                                if($user_type == 'student') echo 'bg-primary';
                                elseif($user_type == 'hostel_manager') echo 'bg-success';
                                else echo 'bg-danger';
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user_type)); ?>
                            </p>
                            <p class="text-muted">
                                <small>Member since <?php echo date('M d, Y', strtotime($user['registration_date'])); ?></small>
                            </p>
                        </div>
                        
                        <div class="col-md-9">
                            <!-- Success/Error Messages -->
                            <?php if(isset($success_message)): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            
                            <?php if(isset($error_message)): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            
                            <!-- Tabs navigation -->
                            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" 
                                            type="button" role="tab" aria-controls="profile" aria-selected="true">
                                        Profile Information
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" 
                                            type="button" role="tab" aria-controls="security" aria-selected="false">
                                        Security
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Tabs content -->
                            <div class="tab-content" id="profileTabsContent">
                                <!-- Profile Information Tab -->
                                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" value="<?php echo $user['username']; ?>" readonly>
                                            <div class="form-text">Username cannot be changed.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" name="full_name" id="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" 
                                                   value="<?php echo !empty($full_name) ? $full_name : $user['full_name']; ?>" required>
                                            <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                                   value="<?php echo !empty($email) ? $email : $user['email']; ?>" required>
                                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                                            <input type="text" name="phone" id="phone" class="form-control" 
                                                   value="<?php echo !empty($phone) ? $phone : $user['phone']; ?>">
                                        </div>
                                        
                                        <?php if($user_type == 'student'): ?>
                                            <div class="mb-3">
                                                <label for="student_id" class="form-label">Student ID</label>
                                                <input type="text" name="student_id" id="student_id" 
                                                       class="form-control <?php echo (!empty($student_id_err)) ? 'is-invalid' : ''; ?>" 
                                                       value="<?php echo !empty($student_id) ? $student_id : $user['student_id']; ?>" required>
                                                <span class="invalid-feedback"><?php echo $student_id_err; ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                                
                                <!-- Security Tab -->
                                <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                    <?php if(isset($password_success)): ?>
                                        <div class="alert alert-success"><?php echo $password_success; ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($password_error)): ?>
                                        <div class="alert alert-danger"><?php echo $password_error; ?></div>
                                    <?php endif; ?>
                                    
                                    <h5>Change Password</h5>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <div class="input-group">
                                                <input type="password" name="current_password" id="current_password" 
                                                       class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#current_password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"><?php echo $current_password_err; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <div class="input-group">
                                                <input type="password" name="new_password" id="new_password" 
                                                       class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                                            <div class="form-text">Password must be at least 6 characters.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <div class="input-group">
                                                <input type="password" name="confirm_password" id="confirm_password" 
                                                       class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                                        </div>
                                        
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Direct inline script for password visibility toggle -->
<script>
// Immediately execute this script when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Get all toggle password buttons
    var toggleButtons = document.querySelectorAll('.toggle-password');
    
    // Add click event to each button
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Get the target password field ID (remove the # from the selector)
            var targetId = this.getAttribute('data-target');
            
            // Get the password input element
            var passwordField = document.querySelector(targetId);
            
            // Check if password field exists
            if (passwordField) {
                // Toggle password visibility
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            }
        });
    });
});
</script>