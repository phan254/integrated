<?php
// Set page title
$page_title = "System Settings | IHMS";

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Get current settings
$settings_query = mysqli_query($conn, "SELECT * FROM university_settings");
$settings = [];
while($setting = mysqli_fetch_assoc($settings_query)) {
    $settings[$setting['setting_name']] = $setting['setting_value'];
}

// Handle form submission to update settings
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $university_name = trim($_POST['university_name']);
    $university_address = trim($_POST['university_address']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $min_hostel_standards = trim($_POST['min_hostel_standards']);
    
    // Update settings in database
    mysqli_query($conn, "UPDATE university_settings SET setting_value = '$university_name' WHERE setting_name = 'university_name'");
    mysqli_query($conn, "UPDATE university_settings SET setting_value = '$university_address' WHERE setting_name = 'university_address'");
    mysqli_query($conn, "UPDATE university_settings SET setting_value = '$contact_email' WHERE setting_name = 'contact_email'");
    mysqli_query($conn, "UPDATE university_settings SET setting_value = '$contact_phone' WHERE setting_name = 'contact_phone'");
    mysqli_query($conn, "UPDATE university_settings SET setting_value = '$min_hostel_standards' WHERE setting_name = 'min_hostel_standards'");
    
    // Show success message
    $success_message = "Settings updated successfully.";
    
    // Refresh settings
    $settings_query = mysqli_query($conn, "SELECT * FROM university_settings");
    $settings = [];
    while($setting = mysqli_fetch_assoc($settings_query)) {
        $settings[$setting['setting_name']] = $setting['setting_value'];
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
                <a href="<?php echo BASE_URL; ?>?page=manage_users" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>?page=manage_hostels" class="list-group-item list-group-item-action">
                    <i class="fas fa-hotel me-2"></i> Manage Hostels
                </a>
                <a href="<?php echo BASE_URL; ?>?page=reports" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <a href="<?php echo BASE_URL; ?>?page=settings" class="list-group-item list-group-item-action active">
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
                    <h4 class="mb-0"><i class="fas fa-cog me-2"></i> System Settings</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="university_name" class="form-label">University Name</label>
                                <input type="text" class="form-control" id="university_name" name="university_name" 
                                       value="<?php echo isset($settings['university_name']) ? $settings['university_name'] : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="university_address" class="form-label">University Address</label>
                                <input type="text" class="form-control" id="university_address" name="university_address" 
                                       value="<?php echo isset($settings['university_address']) ? $settings['university_address'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                       value="<?php echo isset($settings['contact_email']) ? $settings['contact_email'] : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                       value="<?php echo isset($settings['contact_phone']) ? $settings['contact_phone'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="min_hostel_standards" class="form-label">Minimum Hostel Standards</label>
                            <textarea class="form-control" id="min_hostel_standards" name="min_hostel_standards" rows="4" required><?php echo isset($settings['min_hostel_standards']) ? $settings['min_hostel_standards'] : ''; ?></textarea>
                            <div class="form-text">List the minimum standards required for hostels to be approved (comma separated).</div>
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h5>System Maintenance</h5>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Backup Database</h5>
                                    <p class="card-text">Create a backup of the system database. This will download a SQL file with all data.</p>
                                    <a href="<?php echo BASE_URL; ?>backup_database.php" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Backup Database
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">System Logs</h5>
                                    <p class="card-text">View system logs for debugging and monitoring purposes.</p>
                                    <a href="<?php echo BASE_URL; ?>view_logs.php" class="btn btn-outline-primary">
                                        <i class="fas fa-file-alt me-1"></i> View Logs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional System Settings -->
                    <h5 class="mt-4">System Status</h5>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>PHP Version</h6>
                                    <p><?php echo phpversion(); ?></p>
                                    
                                    <h6>MySQL Version</h6>
                                    <p><?php echo mysqli_get_server_info($conn); ?></p>
                                    
                                    <h6>Server Software</h6>
                                    <p><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Disk Usage</h6>
                                    <?php
                                    $total_space = disk_total_space('/');
                                    $free_space = disk_free_space('/');
                                    $used_space = $total_space - $free_space;
                                    $used_percentage = round(($used_space / $total_space) * 100);
                                    ?>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo ($used_percentage > 90) ? 'danger' : (($used_percentage > 70) ? 'warning' : 'success'); ?>" 
                                             role="progressbar" style="width: <?php echo $used_percentage; ?>%;" 
                                             aria-valuenow="<?php echo $used_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $used_percentage; ?>%
                                        </div>
                                    </div>
                                    <p>
                                        <?php echo round($used_space / 1024 / 1024 / 1024, 2); ?> GB used of 
                                        <?php echo round($total_space / 1024 / 1024 / 1024, 2); ?> GB
                                    </p>
                                    
                                    <h6>Database Size</h6>
                                    <?php
                                    $db_size_query = mysqli_query($conn, "SELECT 
                                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size 
                                        FROM information_schema.tables 
                                        WHERE table_schema = '" . DB_NAME . "' 
                                        GROUP BY table_schema");
                                    $db_size = mysqli_fetch_assoc($db_size_query)['size'] ?? 0;
                                    ?>
                                    <p><?php echo $db_size; ?> MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>