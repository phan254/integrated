<?php
// Start session
session_start();

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: index.php?page=login");
    exit;
}

// Include database connection and other required files
require_once 'config/db_config.php';
require_once 'error_handler.php';

// Define base URL

// Set page title
$page_title = "System Logs | IHMS";

// Include header
include 'includes/header.php';
include 'includes/nav.php';

// Get log files from logs directory
$log_dir = 'logs';
$log_files = array();

if(is_dir($log_dir)) {
    $files = scandir($log_dir);
    foreach($files as $file) {
        if($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'log') {
            $log_files[] = $file;
        }
    }
    
    // Sort files by modified time (newest first)
    usort($log_files, function($a, $b) use ($log_dir) {
        return filemtime($log_dir . '/' . $b) - filemtime($log_dir . '/' . $a);
    });
}

// Get selected log file
$selected_log = isset($_GET['file']) ? $_GET['file'] : (count($log_files) > 0 ? $log_files[0] : null);

// Validate selected log file (prevent directory traversal)
if($selected_log && (!in_array($selected_log, $log_files) || strpos($selected_log, '/') !== false || strpos($selected_log, '\\') !== false)) {
    $selected_log = null;
}

// Get log content if file is selected
$log_content = '';
if($selected_log) {
    $log_path = $log_dir . '/' . $selected_log;
    if(file_exists($log_path)) {
        $log_content = file_get_contents($log_path);
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i> System Logs</h4>
                    <a href="<?php echo BASE_URL; ?>?page=settings" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Settings
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h5>Log Files</h5>
                            <div class="list-group">
                                <?php if(count($log_files) > 0): ?>
                                    <?php foreach($log_files as $file): ?>
                                        <a href="<?php echo BASE_URL; ?>view_logs.php?file=<?php echo urlencode($file); ?>" 
                                           class="list-group-item list-group-item-action <?php echo ($file == $selected_log) ? 'active' : ''; ?>">
                                            <?php echo $file; ?>
                                            <small class="d-block text-muted">
                                                <?php echo date('M d, Y h:i A', filemtime($log_dir . '/' . $file)); ?>
                                            </small>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">No log files found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <h5>Log Content</h5>
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                    <?php if($selected_log): ?>
                                        <span><?php echo $selected_log; ?></span>
                                        <a href="<?php echo BASE_URL; ?>download_log.php?file=<?php echo urlencode($selected_log); ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span>No log file selected</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if($selected_log && !empty($log_content)): ?>
                                        <pre class="log-content" style="max-height: 500px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($log_content); ?></pre>
                                    <?php elseif($selected_log): ?>
                                        <div class="alert alert-info">Log file is empty.</div>
                                    <?php else: ?>
                                        <div class="alert alert-info">Select a log file to view its content.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>