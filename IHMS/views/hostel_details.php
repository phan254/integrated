<?php
// Check if hostel ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to hostels page if no ID provided
    header("Location: " . BASE_URL . "?page=hostels");
    exit;
}

$hostel_id = (int)$_GET['id'];

// Get hostel details
$hostel_query = mysqli_query($conn, "SELECT h.*, u.full_name as manager_name, u.email as manager_email, u.phone as manager_phone 
                                    FROM hostels h 
                                    JOIN users u ON h.manager_id = u.user_id 
                                    WHERE h.hostel_id = $hostel_id AND h.status = 'active'");

// Check if hostel exists
if(mysqli_num_rows($hostel_query) == 0) {
    // Redirect to hostels page if hostel doesn't exist
    header("Location: " . BASE_URL . "?page=hostels");
    exit;
}

$hostel = mysqli_fetch_assoc($hostel_query);

// Get hostel amenities
$amenities_query = mysqli_query($conn, "SELECT * FROM hostel_amenities WHERE hostel_id = $hostel_id");
$amenities = mysqli_fetch_assoc($amenities_query);

// Get room types
$room_types_query = mysqli_query($conn, "SELECT * FROM room_types WHERE hostel_id = $hostel_id ORDER BY price ASC");

// Get hostel images
$images_query = mysqli_query($conn, "SELECT * FROM hostel_images WHERE hostel_id = $hostel_id ORDER BY is_primary DESC");

// Get reviews
$reviews_query = mysqli_query($conn, "SELECT r.*, u.full_name 
                                     FROM reviews r 
                                     JOIN users u ON r.student_id = u.user_id 
                                     WHERE r.hostel_id = $hostel_id AND r.status = 'approved' 
                                     ORDER BY r.review_date DESC");

// Calculate average rating
$avg_rating_query = mysqli_query($conn, "SELECT AVG(rating) as avg_rating FROM reviews WHERE hostel_id = $hostel_id AND status = 'approved'");
$avg_rating_row = mysqli_fetch_assoc($avg_rating_query);
$avg_rating = round($avg_rating_row['avg_rating'], 1);

// Set page title
$page_title = $hostel['hostel_name'] . " | IHMS";

// Handle booking form submission
if(isset($_POST['book_room']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student') {
    $room_type_id = (int)$_POST['room_type_id'];
    $check_in_date = $_POST['check_in_date'];
    $amount = (float)$_POST['amount'];
    $student_id = $_SESSION['user_id'];
    
    // Validate data
    if(!empty($room_type_id) && !empty($check_in_date) && !empty($amount)) {
        // Check if room is available
        $room_check_query = mysqli_query($conn, "SELECT available_count FROM room_types WHERE room_type_id = $room_type_id");
        $room_available = mysqli_fetch_assoc($room_check_query)['available_count'];
        
        if($room_available > 0) {
            // Insert booking
            $insert_booking = mysqli_query($conn, "INSERT INTO bookings (student_id, room_type_id, check_in_date, amount) 
                                                VALUES ($student_id, $room_type_id, '$check_in_date', $amount)");
            
            if($insert_booking) {
                // Update available rooms count
                mysqli_query($conn, "UPDATE room_types SET available_count = available_count - 1 WHERE room_type_id = $room_type_id");
                mysqli_query($conn, "UPDATE hostels SET available_rooms = available_rooms - 1 WHERE hostel_id = $hostel_id");
                
                $success_message = "Booking request submitted successfully. The hostel manager will review your request.";
            } else {
                $error_message = "Failed to submit booking. Please try again.";
            }
        } else {
            $error_message = "Sorry, this room type is no longer available.";
        }
    } else {
        $error_message = "All fields are required.";
    }
}

// Handle review submission
if(isset($_POST['submit_review']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student') {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $student_id = $_SESSION['user_id'];
    
    // Check if user has already submitted a review
    $check_review = mysqli_query($conn, "SELECT * FROM reviews WHERE hostel_id = $hostel_id AND student_id = $student_id");
    
    if(mysqli_num_rows($check_review) > 0) {
        // Update existing review
        $update_review = mysqli_query($conn, "UPDATE reviews SET rating = $rating, review_text = '$review_text', status = 'pending', review_date = NOW() 
                                             WHERE hostel_id = $hostel_id AND student_id = $student_id");
        
        if($update_review) {
            $review_success = "Your review has been updated and is pending approval.";
        } else {
            $review_error = "Failed to update your review. Please try again.";
        }
    } else {
        // Insert new review
        $insert_review = mysqli_query($conn, "INSERT INTO reviews (hostel_id, student_id, rating, review_text) 
                                            VALUES ($hostel_id, $student_id, $rating, '$review_text')");
        
        if($insert_review) {
            $review_success = "Thank you for your review. It is pending approval.";
        } else {
            $review_error = "Failed to submit your review. Please try again.";
        }
    }
}
?>

<div class="container mt-4">
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Hostel Details -->
    <div class="row">
        <div class="col-md-8">
            <h1><?php echo $hostel['hostel_name']; ?></h1>
            <p class="text-muted">
                <i class="fas fa-map-marker-alt"></i> <?php echo $hostel['address']; ?>, <?php echo $hostel['city']; ?>
                <?php if($hostel['distance_from_university']): ?>
                    <span>(<?php echo $hostel['distance_from_university']; ?> km from university)</span>
                <?php endif; ?>
            </p>
            
            <!-- Image Gallery -->
            <div id="hostelCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                <div class="carousel-inner rounded">
                    <?php 
                    $first = true;
                    if(mysqli_num_rows($images_query) > 0):
                        while($image = mysqli_fetch_assoc($images_query)): 
                    ?>
                        <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                            <img src="<?php echo BASE_URL . 'uploads/' . $image['image_path']; ?>" class="d-block w-100" alt="Hostel Image" style="height: 400px; object-fit: cover;">
                        </div>
                    <?php 
                        $first = false;
                        endwhile;
                    else:
                    ?>
                        <div class="carousel-item active">
                            <img src="<?php echo BASE_URL; ?>assets/img/no-image.jpg" class="d-block w-100" alt="No Image Available" style="height: 400px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#hostelCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#hostelCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                    </button>
            </div>
            
            <!-- Hostel Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Description</h4>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br($hostel['description']); ?></p>
                </div>
            </div>
            
            <!-- Amenities -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Amenities</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-wifi <?php echo $amenities['wifi'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">WiFi</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-shower <?php echo $amenities['hot_water'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Hot Water</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-shield-alt <?php echo $amenities['security'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Security</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-utensils <?php echo $amenities['meals'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Meals</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-tshirt <?php echo $amenities['laundry'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Laundry</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-book <?php echo $amenities['study_room'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Study Room</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="fas fa-car <?php echo $amenities['parking'] ? 'text-success' : 'text-muted'; ?>"></i>
                            <span class="ms-2">Parking</span>
                        </div>
                    </div>
                    
                    <?php if(!empty($amenities['additional_amenities'])): ?>
                        <hr>
                        <h5>Additional Amenities:</h5>
                        <p><?php echo nl2br($amenities['additional_amenities']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Room Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Room Types & Pricing</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room Type</th>
                                    <th>Capacity</th>
                                    <th>Description</th>
                                    <th>Price (Monthly)</th>
                                    <th>Available</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($room_types_query) > 0): ?>
                                    <?php while($room = mysqli_fetch_assoc($room_types_query)): ?>
                                        <tr>
                                            <td><?php echo $room['room_type']; ?></td>
                                            <td><?php echo $room['capacity']; ?> person(s)</td>
                                            <td><?php echo $room['description']; ?></td>
                                            <td>KSh <?php echo number_format($room['price'], 2); ?></td>
                                            <td><?php echo $room['available_count']; ?></td>
                                            <td>
                                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                                                    <?php if($room['available_count'] > 0): ?>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bookingModal" 
                                                                data-room-id="<?php echo $room['room_type_id']; ?>"
                                                                data-room-type="<?php echo $room['room_type']; ?>"
                                                                data-room-price="<?php echo $room['price']; ?>">
                                                            Book Now
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Not Available</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a href="<?php echo BASE_URL; ?>?page=login" class="btn btn-sm btn-outline-primary">Login to Book</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No room types available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Reviews -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Reviews</h4>
                    <div>
                        <?php if($avg_rating > 0): ?>
                            <span class="badge bg-success"><?php echo $avg_rating; ?> <i class="fas fa-star"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#reviewModal">Write a Review</button>
                    <?php endif; ?>
                    
                    <?php if(isset($review_success)): ?>
                        <div class="alert alert-success"><?php echo $review_success; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($review_error)): ?>
                        <div class="alert alert-danger"><?php echo $review_error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(mysqli_num_rows($reviews_query) > 0): ?>
                        <div class="list-group">
                            <?php while($review = mysqli_fetch_assoc($reviews_query)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-1"><?php echo $review['full_name']; ?></h5>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['review_date'])); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br($review['review_text']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review this hostel!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Contact Information</h4>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-user me-2"></i> <?php echo $hostel['manager_name']; ?></p>
                    <p><i class="fas fa-envelope me-2"></i> <?php echo $hostel['manager_email']; ?></p>
                    <?php if(!empty($hostel['manager_phone'])): ?>
                        <p><i class="fas fa-phone me-2"></i> <?php echo $hostel['manager_phone']; ?></p>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'student'): ?>
                        <a href="<?php echo BASE_URL; ?>?page=messages&compose=1&to=<?php echo $hostel['manager_id']; ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-envelope me-2"></i> Message Hostel Manager
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Key Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Key Information</h4>
                </div>
                <div class="card-body">
                    <p><strong>Gender Type:</strong> <?php echo ucfirst($hostel['gender_type']); ?></p>
                    <p><strong>Total Rooms:</strong> <?php echo $hostel['total_rooms']; ?></p>
                    <p><strong>Available Rooms:</strong> <?php echo $hostel['available_rooms']; ?></p>
                    <p><strong>Distance from University:</strong> <?php echo $hostel['distance_from_university']; ?> km</p>
                </div>
            </div>
            
            <!-- Google Map -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Location</h4>
                </div>
                <div class="card-body">
                    <div class="ratio ratio-16x9">
                        <iframe 
                            src="https://maps.google.com/maps?q=<?php echo urlencode($hostel['address'] . ', ' . $hostel['city']); ?>&output=embed" 
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalLabel">Book a Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="room_type_id" id="booking_room_id">
                    <input type="hidden" name="amount" id="booking_amount">
                    
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <input type="text" class="form-control" id="booking_room_type" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="check_in_date" class="form-label">Check-in Date</label>
                        <input type="date" class="form-control" id="check_in_date" name="check_in_date" required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Monthly Rent</label>
                        <div class="input-group">
                            <span class="input-group-text">KSh</span>
                            <input type="text" class="form-control" id="booking_price" readonly>
                        </div>
                    </div>
                    
                    <p class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Note: This is a booking request. The hostel manager will review your request and contact you.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="book_room" class="btn btn-primary">Submit Booking Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" class="rating-input" <?php echo ($i == 5) ? 'checked' : ''; ?> required>
                                    <label for="star<?php echo $i; ?>" class="rating-star">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="review_text" class="form-label">Your Review</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="4" required></textarea>
                    </div>
                    
                    <p class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your review will be visible once approved by the administrators.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Set room details in booking modal
    document.addEventListener('DOMContentLoaded', function() {
        var bookingModal = document.getElementById('bookingModal');
        if (bookingModal) {
            bookingModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var roomId = button.getAttribute('data-room-id');
                var roomType = button.getAttribute('data-room-type');
                var roomPrice = button.getAttribute('data-room-price');
                
                document.getElementById('booking_room_id').value = roomId;
                document.getElementById('booking_room_type').value = roomType;
                document.getElementById('booking_price').value = roomPrice;
                document.getElementById('booking_amount').value = roomPrice;
            });
        }
    });
</script>