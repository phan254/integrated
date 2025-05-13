<?php
// Set page title
$page_title = "Manage Users | IHMS";

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Handle user activation/suspension
if(isset($_GET['activate_user']) && !empty($_GET['activate_user'])) {
    $user_id = (int)$_GET['activate_user'];
    $update_status = mysqli_query($conn, "UPDATE users SET status = 'active' WHERE user_id = $user_id");
    
    if($update_status) {
        $success_message = "User activated successfully.";
    } else {
        $error_message = "Failed to activate user. Please try again.";
    }
}

if(isset($_GET['suspend_user']) && !empty($_GET['suspend_user'])) {
    $user_id = (int)$_GET['suspend_user'];
    $update_status = mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE user_id = $user_id");
    
    if($update_status) {
        $success_message = "User suspended successfully.";
    } else {
        $error_message = "Failed to suspend user. Please try again.";
    }
}

// Get users data with pagination
$users_per_page = 10;
$page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$offset = ($page_no - 1) * $users_per_page;

$filter_user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT * FROM users WHERE user_id != " . $_SESSION['user_id'];

if(!empty($filter_user_type)) {
    $sql .= " AND user_type = '$filter_user_type'";
}

if(!empty($search)) {
    $sql .= " AND (username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
}

$sql .= " ORDER BY registration_date DESC";

// Count total users for pagination
$count_sql = $sql;
$total_users_query = mysqli_query($conn, $count_sql);
$total_users = mysqli_num_rows($total_users_query);
$total_pages = ceil($total_users / $users_per_page);

// Add pagination
$sql .= " LIMIT $offset, $users_per_page";

// Get users 
$users_query = mysqli_query($conn, $sql);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group mb-4">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_users" class="list-group-item list-group-item-action active">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="list-group-item list-group-item-action">
                    <i class="fas fa-hotel me-2"></i> Manage Hostels
                </a>
                <a href="<?php echo BASE_URL; ?>?page=reports" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <a href="<?php echo BASE_URL; ?>?page=settings" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope me-2"></i> Messages
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
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i> Manage Users</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <!-- Search and Filter -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form class="d-flex" method="GET" action="">
                                <input type="hidden" name="page" value="manage_users">
                                <input class="form-control me-2" type="search" name="search" placeholder="Search by name, username or email" value="<?php echo $search; ?>">
                                <button class="btn btn-outline-primary" type="submit">Search</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="GET" action="">
                                <input type="hidden" name="page" value="manage_users">
                                <select name="user_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">All User Types</option>
                                    <option value="student" <?php echo ($filter_user_type == 'student') ? 'selected' : ''; ?>>Students</option>
                                    <option value="hostel_manager" <?php echo ($filter_user_type == 'hostel_manager') ? 'selected' : ''; ?>>Hostel Managers</option>
                                    <option value="university_admin" <?php echo ($filter_user_type == 'university_admin') ? 'selected' : ''; ?>>Administrators</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($users_query) > 0): ?>
                                    <?php while($user = mysqli_fetch_assoc($users_query)): ?>
                                        <tr>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['full_name']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if($user['user_type'] == 'student') echo 'bg-primary';
                                                    elseif($user['user_type'] == 'hostel_manager') echo 'bg-success';
                                                    else echo 'bg-danger';
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if($user['status'] == 'active') echo 'bg-success';
                                                    elseif($user['status'] == 'inactive') echo 'bg-warning text-dark';
                                                    else echo 'bg-danger';
                                                ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($user['status'] == 'active'): ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_users&suspend_user=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to suspend this user?');">
                                                        <i class="fas fa-ban"></i> Suspend
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_users&activate_user=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Activate
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-envelope"></i> Message
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No users found matching your criteria.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Users pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page_no > 1) ? '?page=manage_users&page_no='.($page_no-1) : '#'; ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page_no == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=manage_users&page_no=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page_no >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page_no < $total_pages) ? '?page=manage_users&page_no='.($page_no+1) : '#'; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>