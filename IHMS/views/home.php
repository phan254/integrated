<div class="container mt-4">
    <div class="jumbotron bg-light p-5 rounded">
        <h1 class="display-4">Welcome to the Integrated Hostel Management System</h1>
        <p class="lead">Find, book and manage off-campus student accommodation with ease.</p>
        <hr class="my-4">
        <p>IHMS connects universities, students, and private hostel owners, creating a more efficient and transparent accommodation process.</p>
        <p class="lead">
            <a class="btn btn-primary btn-lg" href="<?php echo BASE_URL; ?>?page=hostels" role="button">Find Hostels</a>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a class="btn btn-outline-primary btn-lg ms-2" href="<?php echo BASE_URL; ?>?page=register" role="button">Register Now</a>
            <?php endif; ?>
        </p>
    </div>

    <!-- Feature highlights -->
    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Find Hostels</h3>
                    <p class="card-text">Search and filter hostels based on your preferences, location, amenities, and budget.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-bed fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Book Rooms</h3>
                    <p class="card-text">Reserve your room online and manage your bookings through a simple interface.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Communicate</h3>
                    <p class="card-text">Direct messaging between students, hostel managers, and university administrators.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recently added hostels -->
    <h2 class="mt-5 mb-4">Recently Added Hostels</h2>
    <div class="row">
        <?php
        // Get 3 recently added hostels
        $recent_hostels_query = mysqli_query($conn, "SELECT h.*, u.full_name as manager_name, 
                                           (SELECT image_path FROM hostel_images WHERE hostel_id = h.hostel_id AND is_primary = 1 LIMIT 1) as image 
                                           FROM hostels h 
                                           JOIN users u ON h.manager_id = u.user_id 
                                           WHERE h.status = 'active' 
                                           ORDER BY h.created_at DESC LIMIT 3");
        
        if(mysqli_num_rows($recent_hostels_query) > 0) {
            while($hostel = mysqli_fetch_assoc($recent_hostels_query)) {
                // Get minimum price for this hostel
                $price_query = mysqli_query($conn, "SELECT MIN(price) as min_price FROM room_types WHERE hostel_id = " . $hostel['hostel_id']);
                $price_data = mysqli_fetch_assoc($price_query);
                $min_price = $price_data['min_price'] ? $price_data['min_price'] : 0;
        ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $hostel['image'] ? BASE_URL . 'uploads/' . $hostel['image'] : BASE_URL . 'assets/img/no-image.jpg'; ?>" 
                             class="card-img-top" alt="<?php echo $hostel['hostel_name']; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $hostel['hostel_name']; ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-secondary"></i> <?php echo $hostel['address']; ?><br>
                                <i class="fas fa-user text-secondary"></i> <?php echo $hostel['manager_name']; ?><br>
                                <i class="fas fa-coins text-secondary"></i> From $<?php echo number_format($min_price, 2); ?> per month
                            </p>
                            <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $hostel['hostel_id']; ?>" class="btn btn-outline-primary">View Details</a>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info">No hostels available yet.</div></div>';
        }
        ?>
    </div>
</div>