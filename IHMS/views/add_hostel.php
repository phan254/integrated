<?php
// Set page title
$page_title = "Add Hostel | IHMS";

// Check if user is logged in as hostel manager
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'hostel_manager') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

$manager_id = $_SESSION['user_id'];

// Check if manager already has a hostel
$check_hostel = mysqli_query($conn, "SELECT * FROM hostels WHERE manager_id = $manager_id");
if(mysqli_num_rows($check_hostel) > 0) {
    // Redirect to manage hostel page if manager already has a hostel
    header("Location: " . BASE_URL . "?page=manage_hostel");
    exit;
}

// Include image handler
require_once 'image_handler.php';

// Initialize variables
$hostel_name = $description = $address = $city = $distance = $total_rooms = $available_rooms = $price_range = $gender_type = "";
$hostel_name_err = $description_err = $address_err = $city_err = $distance_err = $total_rooms_err = $available_rooms_err = $price_range_err = $gender_type_err = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_hostel'])) {
    
    // Validate hostel name
    if(empty(trim($_POST["hostel_name"]))) {
        $hostel_name_err = "Please enter hostel name.";     
    } else {
        $hostel_name = trim($_POST["hostel_name"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))) {
        $description_err = "Please enter hostel description.";     
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))) {
        $address_err = "Please enter hostel address.";     
    } else {
        $address = trim($_POST["address"]);
    }
    
    // Validate city
    if(empty(trim($_POST["city"]))) {
        $city_err = "Please enter city.";     
    } else {
        $city = trim($_POST["city"]);
    }
    
    // Validate distance from university
    if(empty(trim($_POST["distance"]))) {
        $distance_err = "Please enter distance from university.";     
    } else {
        $distance = trim($_POST["distance"]);
        // Check if distance is a valid number
        if(!is_numeric($distance) || $distance < 0) {
            $distance_err = "Please enter a valid distance.";
        }
    }
    
    // Validate total rooms
    if(empty(trim($_POST["total_rooms"]))) {
        $total_rooms_err = "Please enter total rooms.";     
    } else {
        $total_rooms = trim($_POST["total_rooms"]);
        // Check if total rooms is a valid integer
        if(!filter_var($total_rooms, FILTER_VALIDATE_INT) || $total_rooms < 1) {
            $total_rooms_err = "Please enter a valid number of rooms.";
        }
    }
    
    // Validate available rooms
    if(empty(trim($_POST["available_rooms"]))) {
        $available_rooms_err = "Please enter available rooms.";     
    } else {
        $available_rooms = trim($_POST["available_rooms"]);
        // Check if available rooms is a valid integer
        if(!filter_var($available_rooms, FILTER_VALIDATE_INT) || $available_rooms < 0) {
            $available_rooms_err = "Please enter a valid number of available rooms.";
        }
        // Check if available rooms is not greater than total rooms
        if(empty($total_rooms_err) && $available_rooms > $total_rooms) {
            $available_rooms_err = "Available rooms cannot be greater than total rooms.";
        }
    }
    
    // Validate price range
    if(empty(trim($_POST["price_range"]))) {
        $price_range_err = "Please enter price range.";     
    } else {
        $price_range = trim($_POST["price_range"]);
    }
    
    // Validate gender type
    if(empty(trim($_POST["gender_type"]))) {
        $gender_type_err = "Please select gender type.";     
    } else {
        $gender_type = trim($_POST["gender_type"]);
    }
    
    // Check input errors before inserting in database
    if(empty($hostel_name_err) && empty($description_err) && empty($address_err) && empty($city_err) && 
       empty($distance_err) && empty($total_rooms_err) && empty($available_rooms_err) && empty($price_range_err) && 
       empty($gender_type_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO hostels (manager_id, hostel_name, description, address, city, distance_from_university, 
                total_rooms, available_rooms, price_range, gender_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "issssdiisd", $param_manager_id, $param_hostel_name, $param_description, 
                                  $param_address, $param_city, $param_distance, $param_total_rooms, 
                                  $param_available_rooms, $param_price_range, $param_gender_type);
            
            // Set parameters
            $param_manager_id = $manager_id;
            $param_hostel_name = $hostel_name;
            $param_description = $description;
            $param_address = $address;
            $param_city = $city;
            $param_distance = $distance;
            $param_total_rooms = $total_rooms;
            $param_available_rooms = $available_rooms;
            $param_price_range = $price_range;
            $param_gender_type = $gender_type;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $hostel_id = mysqli_insert_id($conn);
                
                // Insert amenities
                $amenities_sql = "INSERT INTO hostel_amenities (hostel_id, wifi, hot_water, security, meals, laundry, study_room, parking, additional_amenities) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                if($amenities_stmt = mysqli_prepare($conn, $amenities_sql)) {
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($amenities_stmt, "iiiiiiiis", $param_hostel_id, $param_wifi, $param_hot_water, 
                    $param_security, $param_meals, $param_laundry, $param_study_room, 
                    $param_parking, $param_additional_amenities);
                    
                    // Set parameters
                    $param_hostel_id = $hostel_id;
                    $param_wifi = isset($_POST["wifi"]) ? 1 : 0;
                    $param_hot_water = isset($_POST["hot_water"]) ? 1 : 0;
                    $param_security = isset($_POST["security"]) ? 1 : 0;
                    $param_meals = isset($_POST["meals"]) ? 1 : 0;
                    $param_laundry = isset($_POST["laundry"]) ? 1 : 0;
                    $param_study_room = isset($_POST["study_room"]) ? 1 : 0;
                    $param_parking = isset($_POST["parking"]) ? 1 : 0;
                    $param_additional_amenities = isset($_POST["additional_amenities"]) ? trim($_POST["additional_amenities"]) : "";
                    
                    // Execute the statement
                    mysqli_stmt_execute($amenities_stmt);
                    mysqli_stmt_close($amenities_stmt);
                }
                
                // Insert room types
                if(isset($_POST["room_type"]) && is_array($_POST["room_type"])) {
                    $room_type_sql = "INSERT INTO room_types (hostel_id, room_type, capacity, price, description, available_count) 
                                     VALUES (?, ?, ?, ?, ?, ?)";
                    
                    if($room_type_stmt = mysqli_prepare($conn, $room_type_sql)) {
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($room_type_stmt, "isissi", $param_hostel_id, $param_room_type, 
                                              $param_capacity, $param_price, $param_room_description, 
                                              $param_available_count);
                        
                        $param_hostel_id = $hostel_id;
                        
                        for($i = 0; $i < count($_POST["room_type"]); $i++) {
                            if(!empty($_POST["room_type"][$i]) && !empty($_POST["capacity"][$i]) && !empty($_POST["price"][$i]) && !empty($_POST["available_count"][$i])) {
                                $param_room_type = $_POST["room_type"][$i];
                                $param_capacity = $_POST["capacity"][$i];
                                $param_price = $_POST["price"][$i];
                                $param_room_description = isset($_POST["room_description"][$i]) ? $_POST["room_description"][$i] : "";
                                $param_available_count = $_POST["available_count"][$i];
                                
                                // Execute the statement
                                mysqli_stmt_execute($room_type_stmt);
                            }
                        }
                        
                        mysqli_stmt_close($room_type_stmt);
                    }
                }
                
                // Upload hostel images
                if(isset($_FILES['hostel_images']) && !empty($_FILES['hostel_images']['name'][0])) {
                    // Get primary image index if set
                    $primary_index = isset($_POST['primary_image']) ? (int)$_POST['primary_image'] : 0;
                    
                    // Upload images
                    upload_hostel_images($hostel_id, $_FILES['hostel_images'], $primary_index);
                }
                
                // Redirect to hostel management page
                header("location: " . BASE_URL . "?page=manage_hostel");
                exit;
            } else {
                $error_message = "Something went wrong. Please try again later.";
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
                <a href="<?php echo BASE_URL; ?>?page=add_hostel" class="list-group-item list-group-item-action active">
                    <i class="fas fa-plus-circle me-2"></i> Add Hostel
                </a>
                <a href="<?php echo BASE_URL; ?>?page=messages" class="list-group-item list-group-item-action">
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
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Add New Hostel</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo BASE_URL; ?>?page=add_hostel" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Your hostel will be reviewed by university administrators before being listed.
                        </div>
                        
                        <!-- Basic Information Section -->
                        <h5 class="mt-4 mb-3">Basic Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hostel_name" class="form-label">Hostel Name <span class="text-danger">*</span></label>
                                <input type="text" name="hostel_name" id="hostel_name" class="form-control <?php echo (!empty($hostel_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $hostel_name; ?>" required>
                                <span class="invalid-feedback"><?php echo $hostel_name_err; ?></span>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="gender_type" class="form-label">Gender Type <span class="text-danger">*</span></label>
                                <select name="gender_type" id="gender_type" class="form-select <?php echo (!empty($gender_type_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">Select Gender Type</option>
                                    <option value="male" <?php echo ($gender_type == "male") ? "selected" : ""; ?>>Male Only</option>
                                    <option value="female" <?php echo ($gender_type == "female") ? "selected" : ""; ?>>Female Only</option>
                                    <option value="mixed" <?php echo ($gender_type == "mixed") ? "selected" : ""; ?>>Mixed</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $gender_type_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4" required><?php echo $description; ?></textarea>
                            <span class="invalid-feedback"><?php echo $description_err; ?></span>
                            <div class="form-text">Provide a detailed description of your hostel, including facilities and benefits.</div>
                        </div>
                        
                        <!-- Location Section -->
                        <h5 class="mt-4 mb-3">Location</h5>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" name="address" id="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $address; ?>" required>
                                <span class="invalid-feedback"><?php echo $address_err; ?></span>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" id="city" class="form-control <?php echo (!empty($city_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $city; ?>" required>
                                <span class="invalid-feedback"><?php echo $city_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="distance" class="form-label">Distance from University (km) <span class="text-danger">*</span></label>
                            <input type="number" name="distance" id="distance" step="0.1" min="0" class="form-control <?php echo (!empty($distance_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $distance; ?>" required>
                            <span class="invalid-feedback"><?php echo $distance_err; ?></span>
                        </div>
                        
                        <!-- Rooms Section -->
                        <h5 class="mt-4 mb-3">Rooms</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="total_rooms" class="form-label">Total Rooms <span class="text-danger">*</span></label>
                                <input type="number" name="total_rooms" id="total_rooms" min="1" class="form-control <?php echo (!empty($total_rooms_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $total_rooms; ?>" required>
                                <span class="invalid-feedback"><?php echo $total_rooms_err; ?></span>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="available_rooms" class="form-label">Available Rooms <span class="text-danger">*</span></label>
                                <input type="number" name="available_rooms" id="available_rooms" min="0" class="form-control <?php echo (!empty($available_rooms_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $available_rooms; ?>" required>
                                <span class="invalid-feedback"><?php echo $available_rooms_err; ?></span>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="price_range" class="form-label">Price Range <span class="text-danger">*</span></label>
                                <input type="text" name="price_range" id="price_range" class="form-control <?php echo (!empty($price_range_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price_range; ?>" placeholder="e.g. KSh 1,000-KSh 3,000" required>
                                <span class="invalid-feedback"><?php echo $price_range_err; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Room Types <span class="text-danger">*</span></label>
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div id="room_types_container">
                                        <div class="room-type-row row mb-3">
                                            <div class="col-md-3">
                                                <input type="text" name="room_type[]" class="form-control" placeholder="Room Type" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="capacity[]" class="form-control" placeholder="Capacity" min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="price[]" class="form-control" placeholder="Price (KSh)" min="0" step="0.01" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="room_description[]" class="form-control" placeholder="Description">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="available_count[]" class="form-control" placeholder="Available" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRoomTypeField()">
                                        <i class="fas fa-plus"></i> Add Another Room Type
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hostel Images Section -->
                        <h5 class="mt-4 mb-3">Hostel Images</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="hostel_images" class="form-label">Upload Images <span class="text-danger">*</span></label>
                                    <input type="file" name="hostel_images[]" id="hostel_images" class="form-control" multiple accept="image/*" required>
                                    <div class="form-text">Upload images of your hostel. You can select multiple images (max 5). First image will be set as primary image.</div>
                                </div>
                                
                                <div class="mb-3" id="image_preview_container">
                                    <!-- Image previews will be displayed here using JavaScript -->
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Primary Image</label>
                                    <select name="primary_image" id="primary_image" class="form-select">
                                        <option value="0">First Image</option>
                                    </select>
                                    <div class="form-text">Select which image should be displayed as the main image in listings.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities Section -->
                        <h5 class="mt-4 mb-3">Amenities</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="wifi" id="wifi" value="1">
                                            <label class="form-check-label" for="wifi">WiFi</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="hot_water" id="hot_water" value="1">
                                            <label class="form-check-label" for="hot_water">Hot Water</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="security" id="security" value="1">
                                            <label class="form-check-label" for="security">Security</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="meals" id="meals" value="1">
                                            <label class="form-check-label" for="meals">Meals</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="laundry" id="laundry" value="1">
                                            <label class="form-check-label" for="laundry">Laundry</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="study_room" id="study_room" value="1">
                                            <label class="form-check-label" for="study_room">Study Room</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="parking" id="parking" value="1">
                                            <label class="form-check-label" for="parking">Parking</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="additional_amenities" class="form-label">Additional Amenities</label>
                                    <textarea name="additional_amenities" id="additional_amenities" class="form-control" rows="3"></textarea>
                                    <div class="form-text">List any other amenities or special features of your hostel.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="add_hostel" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus-circle me-2"></i> Add Hostel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to add another room type field
function addRoomTypeField() {
    var container = document.getElementById('room_types_container');
    var newRow = document.createElement('div');
    newRow.className = 'room-type-row row mb-3';
    newRow.innerHTML = `
        <div class="col-md-3">
            <input type="text" name="room_type[]" class="form-control" placeholder="Room Type" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="capacity[]" class="form-control" placeholder="Capacity" min="1" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="price[]" class="form-control" placeholder="Price (KSh)" min="0" step="0.01" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="room_description[]" class="form-control" placeholder="Description">
        </div>
        <div class="col-md-2">
            <input type="number" name="available_count[]" class="form-control" placeholder="Available" min="0" required>
        </div>
    `;
    container.appendChild(newRow);
}

// Function to handle image previews and primary image selection
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('hostel_images');
    const previewContainer = document.getElementById('image_preview_container');
    const primarySelect = document.getElementById('primary_image');
    
    imageInput.addEventListener('change', function() {
        // Clear previous previews
        previewContainer.innerHTML = '';
        primarySelect.innerHTML = '';
        
        // Check if files are selected
        if (this.files && this.files.length > 0) {
            // Limit to 5 images
            const maxFiles = 5;
            const filesLength = Math.min(this.files.length, maxFiles);
            
            if (this.files.length > maxFiles) {
                alert('You can upload a maximum of 5 images. Only the first 5 will be used.');
            }
            
            // Create preview for each file
            for (let i = 0; i < filesLength; i++) {
                const file = this.files[i];
                
                // Create preview element
                const previewDiv = document.createElement('div');
                previewDiv.className = 'mb-2 d-inline-block me-2';
                
                const img = document.createElement('img');
                img.className = 'img-thumbnail';
                img.style.width = '150px';
                img.style.height = '150px';
                img.style.objectFit = 'cover';
                
                // Create file reader to display preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                previewDiv.appendChild(img);
                previewContainer.appendChild(previewDiv);
                
                // Add option to primary image select
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Image ${i + 1}`;
                if (i === 0) option.selected = true;
                primarySelect.appendChild(option);
            }
        }
    });
});
</script>