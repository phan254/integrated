<?php
// Set page title
$page_title = "Manage Hostels | IHMS";

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Handle hostel approval
if(isset($_GET['approve_hostel']) && !empty($_GET['approve_hostel'])) {
    $hostel_id = (int)$_GET['approve_hostel'];
    $update_hostel = mysqli_query($conn, "UPDATE hostels SET status = 'active' WHERE hostel_id = $hostel_id");
    
    if($update_hostel) {
        $success_message = "Hostel approved successfully.";
    } else {
        $error_message = "Failed to approve hostel. Please try again.";
    }
}

// Handle hostel rejection/deregistration
if(isset($_GET['deregister_hostel']) && !empty($_GET['deregister_hostel'])) {
    $hostel_id = (int)$_GET['deregister_hostel'];
    $update_hostel = mysqli_query($conn, "UPDATE hostels SET status = 'deregistered' WHERE hostel_id = $hostel_id");
    
    if($update_hostel) {
        $success_message = "Hostel deregistered successfully.";
    } else {
        $error_message = "Failed to deregister hostel. Please try again.";
    }
}

// Get hostels data with pagination
$hostels_per_page = 10;
$page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$offset = ($page_no - 1) * $hostels_per_page;

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT h.*, u.full_name as manager_name, u.email as manager_email 
        FROM hostels h 
        JOIN users u ON h.manager_id = u.user_id";

if(!empty($filter_status)) {
    $sql .= " WHERE h.status = '$filter_status'";
} else {
    $sql .= " WHERE 1=1";
}

if(!empty($search)) {
    $sql .= " AND (h.hostel_name LIKE '%$search%' OR h.address LIKE '%$search%' OR h.city LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

$sql .= " ORDER BY h.created_at DESC";

// Count total hostels for pagination
$count_sql = $sql;
$total_hostels_query = mysqli_query($conn, $count_sql);
$total_hostels = mysqli_num_rows($total_hostels_query);
$total_pages = ceil($total_hostels / $hostels_per_page);

// Add pagination
$sql .= " LIMIT $offset, $hostels_per_page";

// Get hostels
$hostels_query = mysqli_query($conn, $sql);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="list-group mb-4">
                <a href="<?php echo BASE_URL; ?>?page=dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_users" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="list-group-item list-group-item-action active">
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
                    <h4 class="mb-0"><i class="fas fa-hotel me-2"></i> Manage Hostels</h4>
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
                                <input type="hidden" name="page" value="manage_hostels">
                                <input class="form-control me-2" type="search" name="search" placeholder="Search by hostel name, address, city or manager" value="<?php echo $search; ?>">
                                <button class="btn btn-outline-primary" type="submit">Search</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="GET" action="">
                                <input type="hidden" name="page" value="manage_hostels">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="deregistered" <?php echo ($filter_status == 'deregistered') ? 'selected' : ''; ?>>Deregistered</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Hostel Name</th>
                                    <th>Location</th>
                                    <th>Manager</th>
                                    <th>Gender Type</th>
                                    <th>Rooms</th>
                                    <th>Price Range</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($hostels_query) > 0): ?>
                                    <?php while($hostel = mysqli_fetch_assoc($hostels_query)): ?>
                                        <tr>
                                            <td><?php echo $hostel['hostel_name']; ?></td>
                                            <td><?php echo $hostel['address']; ?>, <?php echo $hostel['city']; ?></td>
                                            <td>
                                                <?php echo $hostel['manager_name']; ?>
                                                <div class="small text-muted"><?php echo $hostel['manager_email']; ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    if($hostel['gender_type'] == 'male') echo 'bg-primary';
                                                    elseif($hostel['gender_type'] == 'female') echo 'bg-danger';
                                                    else echo 'bg-success';
                                                ?>">
                                                    <?php echo ucfirst($hostel['gender_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $hostel['available_rooms']; ?>/<?php echo $hostel['total_rooms']; ?>
                                                <div class="small text-muted">Available</div>
                                            </td>
                                            <td>KSh <?php echo $hostel['price_range']; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    if($hostel['status'] == 'active') echo 'bg-success';
                                                    elseif($hostel['status'] == 'pending') echo 'bg-warning text-dark';
                                                    else echo 'bg-danger';
                                                ?>">
                                                    <?php echo ucfirst($hostel['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-info mb-1" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <?php if($hostel['status'] == 'pending'): ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels&approve_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-success mb-1">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels&deregister_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to deregister this hostel?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </a>
                                                <?php elseif($hostel['status'] == 'active'): ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels&deregister_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-warning mb-1" onclick="return confirm('Are you sure you want to deregister this hostel?');">
                                                        <i class="fas fa-ban"></i> Deregister
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=manage_hostels&approve_hostel=<?php echo $hostel['hostel_id']; ?>" class="btn btn-sm btn-success mb-1">
                                                        <i class="fas fa-check"></i> Reactivate
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $hostel['manager_id']; ?>" class="btn btn-sm btn-primary mb-1">
                                                    <i class="fas fa-envelope"></i> Message Manager
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hostels found matching your criteria.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Hostels pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page_no > 1) ? '?page=manage_hostels&page_no='.($page_no-1) : '#'; ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page_no == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=manage_hostels&page_no=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page_no >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page_no < $total_pages) ? '?page=manage_hostels&page_no='.($page_no+1) : '#'; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <!-- Hostel Standards -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Minimum Hostel Standards</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $standards_query = mysqli_query($conn, "SELECT setting_value FROM university_settings WHERE setting_name = 'min_hostel_standards'");
                            $standards = mysqli_fetch_assoc($standards_query)['setting_value'];
                            $standards_list = explode(',', $standards);
                            ?>
                            
                            <p>The following standards must be met for hostels to be approved:</p>
                            <ul>
                                <?php foreach($standards_list as $standard): ?>
                                    <li><?php echo trim($standard); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <p class="mb-0">
                                <a href="<?php echo BASE_URL; ?>?page=settings" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i> Update Standards
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>