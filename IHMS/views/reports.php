<?php
// Set page title
$page_title = "Reports | IHMS";

// Check if user is logged in as university admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'university_admin') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Get basic statistics
$students_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'");
$students_count = mysqli_fetch_assoc($students_query)['count'];

$hostels_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM hostels WHERE status = 'active'");
$hostels_count = mysqli_fetch_assoc($hostels_query)['count'];

$bookings_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings");
$bookings_count = mysqli_fetch_assoc($bookings_query)['count'];

$bookings_confirmed_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
$bookings_confirmed_count = mysqli_fetch_assoc($bookings_confirmed_query)['count'];
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
                <a href="<?php echo BASE_URL; ?>?page=reports" class="list-group-item list-group-item-action active">
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
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i> System Reports</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Summary Statistics</h5>
                    
                    <!-- Summary Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-primary h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Students</h5>
                                    <h2 class="display-4"><?php echo $students_count; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-success h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Active Hostels</h5>
                                    <h2 class="display-4"><?php echo $hostels_count; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-warning h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Bookings</h5>
                                    <h2 class="display-4"><?php echo $bookings_count; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-info h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Confirmed Bookings</h5>
                                    <h2 class="display-4"><?php echo $bookings_confirmed_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Reports -->
                    <div class="row mt-5">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Hostel Occupancy Rates</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Hostel Name</th>
                                                    <th>Total Rooms</th>
                                                    <th>Occupied</th>
                                                    <th>Occupancy Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $hostel_query = mysqli_query($conn, "SELECT h.hostel_id, h.hostel_name, h.total_rooms, h.available_rooms 
                                                                                FROM hostels h 
                                                                                WHERE h.status = 'active' 
                                                                                ORDER BY (h.total_rooms - h.available_rooms) / h.total_rooms DESC");
                                                
                                                while($hostel = mysqli_fetch_assoc($hostel_query)):
                                                    $occupied = $hostel['total_rooms'] - $hostel['available_rooms'];
                                                    $occupancy_rate = ($hostel['total_rooms'] > 0) ? ($occupied / $hostel['total_rooms'] * 100) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $hostel['hostel_name']; ?></td>
                                                        <td><?php echo $hostel['total_rooms']; ?></td>
                                                        <td><?php echo $occupied; ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $occupancy_rate; ?>%;" 
                                                                    aria-valuenow="<?php echo $occupancy_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo round($occupancy_rate); ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Booking Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div id="bookingStats">
                                        <?php
                                        $booking_stats_query = mysqli_query($conn, "SELECT 
                                                               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                                               SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                                                               SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                                                               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                                                               FROM bookings");
                                        $booking_stats = mysqli_fetch_assoc($booking_stats_query);
                                        
                                        $pending = $booking_stats['pending'] ?: 0;
                                        $confirmed = $booking_stats['confirmed'] ?: 0;
                                        $cancelled = $booking_stats['cancelled'] ?: 0;
                                        $completed = $booking_stats['completed'] ?: 0;
                                        $total = $pending + $confirmed + $cancelled + $completed;
                                        
                                        // Calculate percentages
                                        $pending_percent = ($total > 0) ? ($pending / $total * 100) : 0;
                                        $confirmed_percent = ($total > 0) ? ($confirmed / $total * 100) : 0;
                                        $cancelled_percent = ($total > 0) ? ($cancelled / $total * 100) : 0;
                                        $completed_percent = ($total > 0) ? ($completed / $total * 100) : 0;
                                        ?>
                                        
                                        <div class="mb-3">
                                            <h6>Pending Bookings</h6>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-warning text-dark" role="progressbar" style="width: <?php echo $pending_percent; ?>%;" 
                                                    aria-valuenow="<?php echo $pending_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $pending; ?> (<?php echo round($pending_percent); ?>%)
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Confirmed Bookings</h6>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $confirmed_percent; ?>%;" 
                                                    aria-valuenow="<?php echo $confirmed_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $confirmed; ?> (<?php echo round($confirmed_percent); ?>%)
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Cancelled Bookings</h6>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $cancelled_percent; ?>%;" 
                                                    aria-valuenow="<?php echo $cancelled_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $cancelled; ?> (<?php echo round($cancelled_percent); ?>%)
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Completed Bookings</h6>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $completed_percent; ?>%;" 
                                                    aria-valuenow="<?php echo $completed_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $completed; ?> (<?php echo round($completed_percent); ?>%)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Monthly Booking Trends</h5>
                                    <div>
                                        <select id="yearFilter" class="form-select form-select-sm" style="width: 100px;">
                                            <?php
                                            $year_query = mysqli_query($conn, "SELECT DISTINCT YEAR(booking_date) as year FROM bookings ORDER BY year DESC");
                                            while($year = mysqli_fetch_assoc($year_query)):
                                            ?>
                                                <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Total Bookings</th>
                                                    <th>Confirmed</th>
                                                    <th>Cancelled</th>
                                                    <th>Revenue (KSh)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $current_year = date('Y');
                                                $monthly_stats_query = mysqli_query($conn, "SELECT 
                                                                          MONTH(booking_date) as month,
                                                                          COUNT(*) as total,
                                                                          SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                                                                          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                                                                          SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as revenue
                                                                          FROM bookings
                                                                          WHERE YEAR(booking_date) = $current_year
                                                                          GROUP BY MONTH(booking_date)
                                                                          ORDER BY month");
                                                
                                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                                
                                                while($monthly_stats = mysqli_fetch_assoc($monthly_stats_query)):
                                                    $month_index = $monthly_stats['month'] - 1;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $months[$month_index]; ?></td>
                                                        <td><?php echo $monthly_stats['total']; ?></td>
<td><?php echo $monthly_stats['confirmed']; ?></td>
<td><?php echo $monthly_stats['cancelled']; ?></td>
<td>KSh <?php echo number_format($monthly_stats['revenue'], 2); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- Student Distribution Report -->
<div class="row">
   <div class="col-md-12 mb-4">
       <div class="card">
           <div class="card-header">
               <h5 class="mb-0">Student Hostel Distribution</h5>
           </div>
           <div class="card-body">
               <div class="table-responsive">
                   <table class="table table-striped">
                       <thead>
                           <tr>
                               <th>Hostel Name</th>
                               <th>Male Students</th>
                               <th>Female Students</th>
                               <th>Total Students</th>
                               <th>Average Rating</th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php
                           $student_dist_query = mysqli_query($conn, "SELECT 
                                                       h.hostel_id, 
                                                       h.hostel_name,
                                                       SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male_count,
                                                       SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female_count,
                                                       COUNT(b.booking_id) as total_students,
                                                       AVG(r.rating) as avg_rating
                                                       FROM hostels h
                                                       LEFT JOIN room_types rt ON h.hostel_id = rt.hostel_id
                                                       LEFT JOIN bookings b ON rt.room_type_id = b.room_type_id AND b.status = 'confirmed'
                                                       LEFT JOIN users u ON b.student_id = u.user_id
                                                       LEFT JOIN reviews r ON h.hostel_id = r.hostel_id AND r.status = 'approved'
                                                       WHERE h.status = 'active'
                                                       GROUP BY h.hostel_id
                                                       ORDER BY total_students DESC");
                           
                           while($dist = mysqli_fetch_assoc($student_dist_query)):
                               $male_count = $dist['male_count'] ?: 0;
                               $female_count = $dist['female_count'] ?: 0;
                               $total_students = $dist['total_students'] ?: 0;
                               $avg_rating = $dist['avg_rating'] ? round($dist['avg_rating'], 1) : 'N/A';
                           ?>
                               <tr>
                                   <td><?php echo $dist['hostel_name']; ?></td>
                                   <td><?php echo $male_count; ?></td>
                                   <td><?php echo $female_count; ?></td>
                                   <td><?php echo $total_students; ?></td>
                                   <td>
                                       <?php if($avg_rating != 'N/A'): ?>
                                           <div class="d-flex align-items-center">
                                               <?php echo $avg_rating; ?>
                                               <div class="ms-2">
                                                   <?php for($i = 1; $i <= 5; $i++): ?>
                                                       <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                   <?php endfor; ?>
                                               </div>
                                           </div>
                                       <?php else: ?>
                                           <?php echo $avg_rating; ?>
                                       <?php endif; ?>
                                   </td>
                               </tr>
                           <?php endwhile; ?>
                       </tbody>
                   </table>
               </div>
           </div>
       </div>
   </div>
</div>

</div>
</div>
</div>
</div>
</div>