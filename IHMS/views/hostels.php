<?php
// Set page title
$page_title = "Browse Hostels | IHMS";

// Initialize variables for filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;
$amenities = isset($_GET['amenities']) ? $_GET['amenities'] : [];

// Get all distinct cities for filter dropdown
$cities_query = mysqli_query($conn, "SELECT DISTINCT city FROM hostels WHERE status = 'active' ORDER BY city");
$cities = [];
while($city_row = mysqli_fetch_assoc($cities_query)) {
    $cities[] = $city_row['city'];
}

// Build SQL query with filters
$sql = "SELECT h.*, u.full_name as manager_name, 
        (SELECT image_path FROM hostel_images WHERE hostel_id = h.hostel_id AND is_primary = 1 LIMIT 1) as image,
        (SELECT MIN(price) FROM room_types WHERE hostel_id = h.hostel_id) as min_price,
        (SELECT MAX(price) FROM room_types WHERE hostel_id = h.hostel_id) as max_price
        FROM hostels h 
        JOIN users u ON h.manager_id = u.user_id 
        LEFT JOIN hostel_amenities ha ON h.hostel_id = ha.hostel_id
        WHERE h.status = 'active'";

// Add search filter
if(!empty($search)) {
    $sql .= " AND (h.hostel_name LIKE '%".mysqli_real_escape_string($conn, $search)."%' 
              OR h.description LIKE '%".mysqli_real_escape_string($conn, $search)."%'
              OR h.address LIKE '%".mysqli_real_escape_string($conn, $search)."%')";
}

// Add city filter
if(!empty($city)) {
    $sql .= " AND h.city = '".mysqli_real_escape_string($conn, $city)."'";
}

// Add gender filter
if(!empty($gender)) {
    $sql .= " AND h.gender_type = '".mysqli_real_escape_string($conn, $gender)."'";
}

// Add amenities filter if any selected
if(!empty($amenities)) {
    foreach($amenities as $amenity) {
        $sql .= " AND ha.$amenity = 1";
    }
}

$sql .= " GROUP BY h.hostel_id";

// Execute query to get total count before adding price filters and limits
$count_sql = $sql;
$total_results_query = mysqli_query($conn, $count_sql);
$total_results = $total_results_query ? mysqli_num_rows($total_results_query) : 0;

// Add price range filter - we need to do this after the GROUP BY
$sql .= " HAVING min_price >= $min_price";
if ($max_price < 100000) {
    $sql .= " AND max_price <= $max_price";
}

// Add sorting
$sql .= " ORDER BY h.created_at DESC";

// Pagination
$results_per_page = 10;
$page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$offset = ($page_no - 1) * $results_per_page;

// Recount after HAVING filters
$filtered_count_query = mysqli_query($conn, $sql);
if ($filtered_count_query) {
    $total_results = mysqli_num_rows($filtered_count_query);
}
$total_pages = ceil($total_results / $results_per_page);

// Add pagination limit
$sql .= " LIMIT $offset, $results_per_page";

// Execute final query
$hostels_query = mysqli_query($conn, $sql);
if (!$hostels_query) {
    echo "Database Error: " . mysqli_error($conn);
    // For development only - remove in production
    echo "<br>SQL Query: " . $sql;
}
?>

<div class="container mt-4">
    <h1 class="mb-4">Find Your Perfect Hostel</h1>
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filter_form" method="GET" action="">
                <input type="hidden" name="page" value="hostels">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search hostels..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <select name="city" class="form-select">
                            <option value="">All Cities</option>
                            <?php foreach($cities as $city_option): ?>
                                <option value="<?php echo $city_option; ?>" <?php echo ($city == $city_option) ? 'selected' : ''; ?>>
                                    <?php echo $city_option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select name="gender" class="form-select">
                            <option value="">Any Gender</option>
                            <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Male Only</option>
                            <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Female Only</option>
                            <option value="mixed" <?php echo ($gender == 'mixed') ? 'selected' : ''; ?>>Mixed</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Price Range (Monthly)</label>
                        <div class="d-flex">
                            <input type="number" name="min_price" class="form-control me-2" placeholder="Min Price" value="<?php echo $min_price; ?>">
                            <input type="number" name="max_price" class="form-control" placeholder="Max Price" value="<?php echo $max_price; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Amenities</label>
                        <div class="d-flex flex-wrap">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="wifi" <?php echo in_array('wifi', $amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="wifi">WiFi</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="hot_water" id="hot_water" <?php echo in_array('hot_water', $amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="hot_water">Hot Water</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="security" id="security" <?php echo in_array('security', $amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="security">Security</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="meals" id="meals" <?php echo in_array('meals', $amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="meals">Meals</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="study_room" id="study_room" <?php echo in_array('study_room', $amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="study_room">Study Room</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo BASE_URL; ?>?page=hostels" class="btn btn-outline-secondary ms-2">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results count -->
    <p class="mb-4"><?php echo $total_results; ?> hostels found</p>
    
    <!-- Hostel Listings -->
    <?php if($hostels_query && mysqli_num_rows($hostels_query) > 0): ?>
        <?php while($hostel = mysqli_fetch_assoc($hostels_query)): ?>
            <div class="card mb-4 hostel-list-item">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?php echo $hostel['image'] ? BASE_URL . 'uploads/' . $hostel['image'] : BASE_URL . 'assets/img/no-image.jpg'; ?>" 
                             class="card-img-side" alt="<?php echo $hostel['hostel_name']; ?>">
                        
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title"><?php echo $hostel['hostel_name']; ?></h5>
                                <span class="badge <?php echo ($hostel['gender_type'] == 'male') ? 'bg-primary' : (($hostel['gender_type'] == 'female') ? 'bg-danger' : 'bg-success'); ?>">
                                    <?php echo ucfirst($hostel['gender_type']); ?>
                                </span>
                            </div>
                            
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-secondary"></i> <?php echo $hostel['address']; ?>, <?php echo $hostel['city']; ?>
                                <?php if(isset($hostel['distance_from_university'])): ?>
                                    <span class="text-muted">(<?php echo $hostel['distance_from_university']; ?> km from university)</span>
                                <?php endif; ?>
                            </p>
                            
                            <p class="card-text">
                                <i class="fas fa-user text-secondary"></i> Managed by <?php echo $hostel['manager_name']; ?>
                            </p>
                            
                            <p class="card-text">
                                <i class="fas fa-coins text-secondary"></i> 
                                <?php if(isset($hostel['min_price']) && isset($hostel['max_price'])): ?>
                                    KSh <?php echo number_format($hostel['min_price'], 2); ?> - KSh <?php echo number_format($hostel['max_price'], 2); ?> per month
                                <?php else: ?>
                                    Price information not available
                                <?php endif; ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Available Rooms: <?php echo $hostel['available_rooms']; ?></span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>?page=hostel_details&id=<?php echo $hostel['hostel_id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav aria-label="Hostel pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page_no > 1) ? '?page=hostels&page_no='.($page_no-1) : '#'; ?>" tabindex="-1" aria-disabled="<?php echo ($page_no <= 1) ? 'true' : 'false'; ?>">Previous</a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page_no == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=hostels&page_no=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page_no >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($page_no < $total_pages) ? '?page=hostels&page_no='.($page_no+1) : '#'; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">No hostels found matching your criteria. Please try different filters.</div>
    <?php endif; ?>
</div>