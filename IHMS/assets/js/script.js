// Enhanced JavaScript for IHMS

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Password Visibility Toggle =====
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    if (togglePasswordButtons.length > 0) {
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordField = document.querySelector(targetId);
                
                if (!passwordField) return; // Exit if target field not found
                
                // Toggle password visibility
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                    // Accessibility improvement
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    passwordField.type = 'password';
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                    // Accessibility improvement
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        });
    }
    
    // ===== Hostel Image Preview =====
    const imageInput = document.getElementById('hostel_images');
    const imagePreview = document.getElementById('image_previews');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            // Clear previous previews
            imagePreview.innerHTML = '';
            
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
                    
                    // Only process image files
                    if (!file.type.match('image.*')) {
                        continue;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 col-6 mb-3';
                        
                        const card = document.createElement('div');
                        card.className = 'card h-100';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'card-img-top';
                        img.style.height = '150px';
                        img.style.objectFit = 'cover';
                        
                        const cardBody = document.createElement('div');
                        cardBody.className = 'card-body p-2';
                        
                        const text = document.createElement('p');
                        text.className = 'card-text small text-center mb-0';
                        text.textContent = 'Image ' + (i + 1);
                        
                        cardBody.appendChild(text);
                        card.appendChild(img);
                        card.appendChild(cardBody);
                        col.appendChild(card);
                        imagePreview.appendChild(col);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                // Update primary image select options
                const primarySelect = document.getElementById('primary_image');
                if (primarySelect) {
                    primarySelect.innerHTML = '';
                    
                    for (let i = 0; i < filesLength; i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = 'Image ' + (i + 1);
                        if (i === 0) option.selected = true;
                        primarySelect.appendChild(option);
                    }
                }
            }
        });
    }
    
    // ===== Single Image Preview =====
    const singleImageInput = document.getElementById('profile_picture');
    const singleImagePreview = document.querySelector('.profile-picture');
    
    if (singleImageInput && singleImagePreview) {
        singleImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    singleImagePreview.src = e.target.result;
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // ===== Room Price Calculation =====
    const roomTypeSelect = document.getElementById('room_type');
    const stayDurationInput = document.getElementById('stay_duration');
    const totalPriceDisplay = document.getElementById('total_price');
    
    if (roomTypeSelect && stayDurationInput && totalPriceDisplay) {
        const calculateTotal = function() {
            const roomPrice = parseFloat(roomTypeSelect.options[roomTypeSelect.selectedIndex].getAttribute('data-price') || 0);
            const duration = parseInt(stayDurationInput.value || 0);
            
            if (roomPrice && duration) {
                const total = roomPrice * duration;
                totalPriceDisplay.textContent = 'KSh ' + total.toFixed(2);
            } else {
                totalPriceDisplay.textContent = 'KSh 0.00';
            }
        };
        
        roomTypeSelect.addEventListener('change', calculateTotal);
        stayDurationInput.addEventListener('input', calculateTotal);
    }
    
    // ===== Filter Form Handling =====
    const filterForm = document.getElementById('filter_form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Don't prevent default submission unless you're doing AJAX
            // e.preventDefault();
            
            // Additional logic for filters if needed
            const minPrice = document.querySelector('input[name="min_price"]');
            const maxPrice = document.querySelector('input[name="max_price"]');
            
            // Validate price range if needed
            if (minPrice && maxPrice && parseInt(minPrice.value) > parseInt(maxPrice.value)) {
                e.preventDefault();
                alert('Minimum price cannot be greater than maximum price.');
            }
        });
    }
    
    // ===== Confirmation Dialogs =====
    // Handle booking cancellation with confirmation
    const cancelBookingButtons = document.querySelectorAll('.cancel-booking');
    if (cancelBookingButtons.length > 0) {
        cancelBookingButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    }
    
    // Handle hostel deletion with confirmation
    const deleteHostelButtons = document.querySelectorAll('.delete-hostel');
    if (deleteHostelButtons.length > 0) {
        deleteHostelButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this hostel? All related data including bookings and images will be permanently removed.')) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    }
    
    // Generic confirmation for dangerous actions
    const confirmActionButtons = document.querySelectorAll('[data-confirm]');
    if (confirmActionButtons.length > 0) {
        confirmActionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
                if (confirm(message)) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    }
    
    // ===== Star Rating Functionality =====
    const ratingInputs = document.querySelectorAll('.rating-input');
    const ratingStars = document.querySelectorAll('.rating-star');
    
    if (ratingStars.length > 0) {
        ratingStars.forEach((star, index) => {
            star.addEventListener('mouseover', function() {
                // Highlight stars on hover
                for (let i = 0; i <= index; i++) {
                    ratingStars[i].classList.add('text-warning');
                }
            });
            
            star.addEventListener('mouseout', function() {
                // Reset stars on mouseout
                ratingStars.forEach(s => {
                    s.classList.remove('text-warning');
                });
                
                // Highlight selected stars
                const selectedRating = document.querySelector('.rating-input:checked');
                if (selectedRating) {
                    const ratingValue = parseInt(selectedRating.value);
                    for (let i = 0; i < ratingValue; i++) {
                        ratingStars[i].classList.add('text-warning');
                    }
                }
            });
            
            star.addEventListener('click', function() {
                // Set rating value on click
                ratingInputs[index].checked = true;
                
                // Highlight selected stars
                ratingStars.forEach((s, i) => {
                    s.classList.toggle('text-warning', i <= index);
                });
            });
        });
    }
    
    // ===== Message Character Counter =====
    const messageTextarea = document.getElementById('message_text');
    const charCounter = document.getElementById('char_counter');
    
    if (messageTextarea && charCounter) {
        messageTextarea.addEventListener('input', function() {
            const maxLength = 1000;
            const remaining = maxLength - this.value.length;
            charCounter.textContent = remaining;
            
            if (remaining < 0) {
                charCounter.classList.add('text-danger');
                messageTextarea.classList.add('is-invalid');
            } else {
                charCounter.classList.remove('text-danger');
                messageTextarea.classList.remove('is-invalid');
            }
        });
    }
    
    // ===== User Type Selection =====
    const userTypeSelect = document.getElementById('user_type');
    const studentIdField = document.getElementById('student_id_field');
    
    if (userTypeSelect && studentIdField) {
        userTypeSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                studentIdField.classList.remove('d-none');
            } else {
                studentIdField.classList.add('d-none');
            }
        });
    }
    
    // ===== Room Type Fields =====
    const addRoomTypeButton = document.querySelector('[onclick="addRoomTypeField()"]');
    if (addRoomTypeButton) {
        addRoomTypeButton.addEventListener('click', addRoomTypeField);
    }
    
    // ===== Mobile Menu Toggle =====
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            // Additional mobile menu logic if needed
        });
    }
    
    // ===== Form Validation =====
    const forms = document.querySelectorAll('.needs-validation');
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
    
    // ===== Initialize any Bootstrap components if needed =====
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    if (popoverTriggerList.length > 0) {
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
});

// Function to handle adding more room types dynamically
function addRoomTypeField() {
    const container = document.getElementById('room_types_container');
    if (!container) return;
    
    const roomTypeCount = container.getElementsByClassName('room-type-row').length;
    
    const newRow = document.createElement('div');
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
            <input type="text" name="description[]" class="form-control" placeholder="Description">
        </div>
        <div class="col-md-2">
            <input type="number" name="available_count[]" class="form-control" placeholder="Available" min="0" required>
        </div>
        <div class="col-12 mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger remove-room-type">
                <i class="fas fa-trash me-1"></i> Remove
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Add event listener to remove button
    const removeButton = newRow.querySelector('.remove-room-type');
    if (removeButton) {
        removeButton.addEventListener('click', function() {
            container.removeChild(newRow);
        });
    }
}

// Function to preview multiple images
function previewImages(input) {
    const previewContainer = document.getElementById('image_previews');
    if (!previewContainer || !input.files) return;
    
    previewContainer.innerHTML = '';
    
    const files = Array.from(input.files).slice(0, 5); // Limit to 5 images
    
    files.forEach((file, index) => {
        if (!file.type.match('image.*')) return;
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const imgDiv = document.createElement('div');
            imgDiv.className = 'col-md-4 col-6 mb-3';
            
            const card = document.createElement('div');
            card.className = 'card';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-thumbnail';
            img.style.height = '200px';
            img.style.objectFit = 'cover';
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2';
            
            const text = document.createElement('p');
            text.className = 'card-text small text-center mb-0';
            text.textContent = 'Image ' + (index + 1);
            
            cardBody.appendChild(text);
            card.appendChild(img);
            card.appendChild(cardBody);
            imgDiv.appendChild(card);
            previewContainer.appendChild(imgDiv);
        };
        
        reader.readAsDataURL(file);
    });
}