<?php
// Set page title
$page_title = "Messages | IHMS";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Initialize variables
$message_text = $subject = $receiver_id = "";
$message_text_err = $receiver_id_err = "";

// Handle message composition
if(isset($_POST['send_message'])) {
    // Validate receiver
    if(empty(trim($_POST['receiver_id']))) {
        $receiver_id_err = "Please select a receiver.";
    } else {
        $receiver_id = trim($_POST['receiver_id']);
    }
    
    // Validate subject
    $subject = trim($_POST['subject']);
    
    // Validate message text
    if(empty(trim($_POST['message_text']))) {
        $message_text_err = "Please enter a message.";
    } else {
        $message_text = trim($_POST['message_text']);
    }
    
    // Check input errors before sending
    if(empty($receiver_id_err) && empty($message_text_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message_text) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "iiss", $user_id, $receiver_id, $subject, $message_text);
            
            // Attempt to execute
            if(mysqli_stmt_execute($stmt)) {
                $success_message = "Message sent successfully.";
                $message_text = $subject = "";
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle message reply
if(isset($_POST['reply_message']) && isset($_POST['original_message_id'])) {
    $original_message_id = (int)$_POST['original_message_id'];
    
    // Get original message details
    $original_message_query = mysqli_query($conn, "SELECT * FROM messages WHERE message_id = $original_message_id AND receiver_id = $user_id");
    
    if(mysqli_num_rows($original_message_query) > 0) {
        $original_message = mysqli_fetch_assoc($original_message_query);
        $receiver_id = $original_message['sender_id'];
        $subject = !empty($_POST['subject']) ? $_POST['subject'] : "Re: " . $original_message['subject'];
        
        // Validate message text
        if(empty(trim($_POST['message_text']))) {
            $message_text_err = "Please enter a message.";
        } else {
            $message_text = trim($_POST['message_text']);
        }
        
        // Check input errors before sending
        if(empty($message_text_err)) {
            // Prepare an insert statement
            $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message_text) VALUES (?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "iiss", $user_id, $receiver_id, $subject, $message_text);
                
                // Attempt to execute
                if(mysqli_stmt_execute($stmt)) {
                    $success_message = "Reply sent successfully.";
                    $message_text = "";
                } else {
                    $error_message = "Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get users for message composition
if($user_type == 'student') {
    // Students can message hostel managers and university admins
    $users_query = mysqli_query($conn, "SELECT user_id, full_name, user_type FROM users 
                                    WHERE (user_type = 'hostel_manager' OR user_type = 'university_admin') 
                                    AND status = 'active' ORDER BY user_type, full_name");
} elseif($user_type == 'hostel_manager') {
    // Hostel managers can message students who have booked their hostel and university admins
    $users_query = mysqli_query($conn, "SELECT DISTINCT u.user_id, u.full_name, u.user_type 
                                    FROM users u 
                                    LEFT JOIN bookings b ON u.user_id = b.student_id 
                                    LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id 
                                    LEFT JOIN hostels h ON rt.hostel_id = h.hostel_id 
                                    WHERE ((u.user_type = 'student' AND h.manager_id = $user_id) OR u.user_type = 'university_admin') 
                                    AND u.status = 'active' 
                                    ORDER BY u.user_type, u.full_name");
} elseif($user_type == 'university_admin') {
    // University admins can message all users
    $users_query = mysqli_query($conn, "SELECT user_id, full_name, user_type FROM users 
                                    WHERE user_id != $user_id AND status = 'active' 
                                    ORDER BY user_type, full_name");
}

// Get received messages
$inbox_query = mysqli_query($conn, "SELECT m.*, u.full_name as sender_name, u.user_type as sender_type 
                                FROM messages m 
                                JOIN users u ON m.sender_id = u.user_id 
                                WHERE m.receiver_id = $user_id 
                                ORDER BY m.sent_at DESC");

// Get sent messages
$sent_query = mysqli_query($conn, "SELECT m.*, u.full_name as receiver_name, u.user_type as receiver_type 
                              FROM messages m 
                              JOIN users u ON m.receiver_id = u.user_id 
                              WHERE m.sender_id = $user_id 
                              ORDER BY m.sent_at DESC");

// Handle marking message as read
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $message_id = (int)$_GET['view'];
    
    // Check if message belongs to user
    $message_check = mysqli_query($conn, "SELECT * FROM messages WHERE message_id = $message_id AND receiver_id = $user_id");
    
    if(mysqli_num_rows($message_check) > 0) {
        // Mark as read
        mysqli_query($conn, "UPDATE messages SET read_status = 1 WHERE message_id = $message_id");
        
        // Get message details
        $message = mysqli_fetch_assoc($message_check);
        $sender_query = mysqli_query($conn, "SELECT full_name, user_type FROM users WHERE user_id = " . $message['sender_id']);
        $sender = mysqli_fetch_assoc($sender_query);
    } else {
        header("Location: " . BASE_URL . "?page=messages");
        exit;
    }
}

// Handle message deletion
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    // Check if message belongs to user
    $message_check = mysqli_query($conn, "SELECT * FROM messages WHERE message_id = $message_id AND (sender_id = $user_id OR receiver_id = $user_id)");
    
    if(mysqli_num_rows($message_check) > 0) {
        // Delete message
        if(mysqli_query($conn, "DELETE FROM messages WHERE message_id = $message_id")) {
            $success_message = "Message deleted successfully.";
        } else {
            $error_message = "Failed to delete message. Please try again.";
        }
    }
}

// Check for compose parameter
$compose = isset($_GET['compose']) ? true : false;

// Get pre-selected receiver from URL if available
if(isset($_GET['to']) && !empty($_GET['to'])) {
    $preselected_receiver = (int)$_GET['to'];
} else {
    $preselected_receiver = null;
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
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action active">
                    <i class="fas fa-envelope me-2"></i> Messages
                </a>
                <a href="<?php echo BASE_URL; ?>?page=profile" class="list-group-item list-group-item-action">
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
                    <h4 class="mb-0"><i class="fas fa-envelope me-2"></i> Messages</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <!-- Message Navigation -->
                            <div class="list-group mb-3">
                                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action <?php echo (!isset($_GET['view']) && !$compose && !isset($_GET['sent'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-inbox me-2"></i> Inbox
                                    <?php
                                    $unread_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND read_status = 0");
                                    $unread_count = mysqli_fetch_assoc($unread_count_query)['count'];
                                    if($unread_count > 0):
                                    ?>
                                        <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="<?php echo BASE_URL; ?>?page=messages&sent=1" class="list-group-item list-group-item-action <?php echo (isset($_GET['sent'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-paper-plane me-2"></i> Sent
                                </a>
                                <a href="<?php echo BASE_URL; ?>?page=messages&compose=1" class="list-group-item list-group-item-action <?php echo ($compose) ? 'active' : ''; ?>">
                                    <i class="fas fa-pen me-2"></i> Compose
                                </a>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="d-grid">
                                <a href="<?php echo BASE_URL; ?>?page=messages&compose=1" class="btn btn-primary mb-3">
                                    <i class="fas fa-plus-circle me-2"></i> New Message
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <?php if(isset($_GET['view'])): ?>
                                <!-- View Message -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo $message['subject']; ?></h5>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&reply=<?php echo $message_id; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-reply"></i> Reply
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>?page=messages&delete=<?php echo $message_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this message?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>From:</strong> <?php echo $sender['full_name']; ?> 
                                            <span class="badge <?php 
                                                if($sender['user_type'] == 'student') echo 'bg-primary';
                                                elseif($sender['user_type'] == 'hostel_manager') echo 'bg-success';
                                                else echo 'bg-danger';
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $sender['user_type'])); ?>
                                            </span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($message['sent_at'])); ?>
                                        </div>
                                        <hr>
                                        <div class="message-content">
                                            <?php echo nl2br($message['message_text']); ?>
                                        </div>
                                        
                                        <!-- Reply Form -->
                                        <div class="mt-4">
                                            <h5>Quick Reply</h5>
                                            <form method="post">
                                                <input type="hidden" name="original_message_id" value="<?php echo $message_id; ?>">
                                                <div class="mb-3">
                                                    <label for="subject" class="form-label">Subject</label>
                                                    <input type="text" name="subject" id="subject" class="form-control" value="Re: <?php echo $message['subject']; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="message_text" class="form-label">Message</label>
                                                    <textarea name="message_text" id="message_text" class="form-control <?php echo (!empty($message_text_err)) ? 'is-invalid' : ''; ?>" 
                                                              rows="5" required></textarea>
                                                    <span class="invalid-feedback"><?php echo $message_text_err; ?></span>
                                                    <div class="form-text">
                                                        <span id="char_counter">1000</span> characters remaining
                                                    </div>
                                                </div>
                                                <button type="submit" name="reply_message" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane me-1"></i> Send Reply
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif($compose): ?>
                                <!-- Compose Message -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">New Message</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="mb-3">
                                                <label for="receiver_id" class="form-label">To</label>
                                                <select name="receiver_id" id="receiver_id" class="form-select <?php echo (!empty($receiver_id_err)) ? 'is-invalid' : ''; ?>" required>
                                                    <option value="">Select Recipient</option>
                                                    
                                                    <?php if($user_type == 'university_admin'): ?>
                                                        <optgroup label="Administrators">
                                                            <?php
                                                            mysqli_data_seek($users_query, 0);
                                                            while($user = mysqli_fetch_assoc($users_query)) {
                                                                if($user['user_type'] == 'university_admin') {
                                                                    echo '<option value="'.$user['user_id'].'" '.($preselected_receiver == $user['user_id'] ? 'selected' : '').'>'.$user['full_name'].'</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </optgroup>
                                                        <optgroup label="Hostel Managers">
                                                            <?php
                                                            mysqli_data_seek($users_query, 0);
                                                            while($user = mysqli_fetch_assoc($users_query)) {
                                                                if($user['user_type'] == 'hostel_manager') {
                                                                    echo '<option value="'.$user['user_id'].'" '.($preselected_receiver == $user['user_id'] ? 'selected' : '').'>'.$user['full_name'].'</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </optgroup>
                                                        <optgroup label="Students">
                                                            <?php
                                                            mysqli_data_seek($users_query, 0);
                                                            while($user = mysqli_fetch_assoc($users_query)) {
                                                                if($user['user_type'] == 'student') {
                                                                    echo '<option value="'.$user['user_id'].'" '.($preselected_receiver == $user['user_id'] ? 'selected' : '').'>'.$user['full_name'].'</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </optgroup>
                                                    <?php else: ?>
                                                        <?php while($user = mysqli_fetch_assoc($users_query)): ?>
                                                            <option value="<?php echo $user['user_id']; ?>" <?php echo ($preselected_receiver == $user['user_id'] ? 'selected' : ''); ?>>
                                                                <?php echo $user['full_name']; ?> 
                                                                (<?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?>)
                                                            </option>
                                                        <?php endwhile; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <span class="invalid-feedback"><?php echo $receiver_id_err; ?></span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="subject" class="form-label">Subject</label>
                                                <input type="text" name="subject" id="subject" class="form-control" value="<?php echo $subject; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="message_text" class="form-label">Message</label>
                                                <textarea name="message_text" id="message_text" class="form-control <?php echo (!empty($message_text_err)) ? 'is-invalid' : ''; ?>" 
                                                          rows="10" required><?php echo $message_text; ?></textarea>
                                                <span class="invalid-feedback"><?php echo $message_text_err; ?></span>
                                                <div class="form-text">
                                                    <span id="char_counter">1000</span> characters remaining
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex">
                                                <button type="submit" name="send_message" class="btn btn-primary me-2">
                                                    <i class="fas fa-paper-plane me-1"></i> Send Message
                                                </button>
                                                <a href="<?php echo BASE_URL; ?>?page=messages" class="btn btn-outline-secondary">Cancel</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php elseif(isset($_GET['sent'])): ?>
                                <!-- Sent Messages -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Sent Messages</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if(mysqli_num_rows($sent_query) > 0): ?>
                                            <div class="list-group message-list">
                                                <?php while($message = mysqli_fetch_assoc($sent_query)): ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=messages&view=<?php echo $message['message_id']; ?>" class="list-group-item list-group-item-action">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1">
                                                                To: <?php echo $message['receiver_name']; ?>
                                                                <span class="badge <?php 
                                                                    if($message['receiver_type'] == 'student') echo 'bg-primary';
                                                                    elseif($message['receiver_type'] == 'hostel_manager') echo 'bg-success';
                                                                    else echo 'bg-danger';
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $message['receiver_type'])); ?>
                                                                </span>
                                                            </h6>
                                                            <small class="message-date"><?php echo date('M d, Y h:i A', strtotime($message['sent_at'])); ?></small>
                                                        </div>
                                                        <p class="mb-1"><strong><?php echo $message['subject']; ?></strong></p>
                                                        <p class="mb-1"><?php echo substr($message['message_text'], 0, 100) . (strlen($message['message_text']) > 100 ? '...' : ''); ?></p>
                                                    </a>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> You haven't sent any messages yet.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Inbox -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Inbox</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if(mysqli_num_rows($inbox_query) > 0): ?>
                                            <div class="list-group message-list">
                                                <?php while($message = mysqli_fetch_assoc($inbox_query)): ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=messages&view=<?php echo $message['message_id']; ?>" 
                                                       class="list-group-item list-group-item-action <?php echo (!$message['read_status']) ? 'unread' : ''; ?>">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1">
                                                                <?php echo $message['sender_name']; ?>
                                                                <span class="badge <?php 
                                                                    if($message['sender_type'] == 'student') echo 'bg-primary';
                                                                    elseif($message['sender_type'] == 'hostel_manager') echo 'bg-success';
                                                                    else echo 'bg-danger';
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $message['sender_type'])); ?>
                                                                </span>
                                                                <?php if(!$message['read_status']): ?>
                                                                    <span class="badge bg-warning text-dark">New</span>
                                                                <?php endif; ?>
                                                            </h6>
                                                            <small class="message-date"><?php echo date('M d, Y h:i A', strtotime($message['sent_at'])); ?></small>
                                                        </div>
                                                        <p class="mb-1"><strong><?php echo $message['subject']; ?></strong></p>
                                                        <p class="mb-1"><?php echo substr($message['message_text'], 0, 100) . (strlen($message['message_text']) > 100 ? '...' : ''); ?></p>
                                                    </a>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> Your inbox is empty.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Message character counter
    document.addEventListener('DOMContentLoaded', function() {
        const messageTextarea = document.getElementById('message_text');
        const charCounter = document.getElementById('char_counter');
        
        if(messageTextarea && charCounter) {
            messageTextarea.addEventListener('input', function() {
                const maxLength = 1000;
                const remaining = maxLength - this.value.length;
                charCounter.textContent = remaining;
                
                if(remaining < 0) {
                    charCounter.classList.add('text-danger');
                    messageTextarea.classList.add('is-invalid');
                } else {
                    charCounter.classList.remove('text-danger');
                    messageTextarea.classList.remove('is-invalid');
                }
            });
        }
    });
</script>