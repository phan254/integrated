<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">IHMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'home') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'hostels') ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>?page=hostels">Hostels</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] == 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($page == 'bookings') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=dashboard">My Bookings</a>
                        </li>
                    <?php elseif($_SESSION['user_type'] == 'hostel_manager'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($page == 'manage_hostel') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=dashboard">Manage Hostel</a>
                        </li>
                    <?php elseif($_SESSION['user_type'] == 'university_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($page == 'admin') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=dashboard">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'messages') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>?page=messages">
                            Messages
                            <?php
                            // Check for unread messages
                            $user_id = $_SESSION['user_id'];
                            $unread_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND read_status = 0");
                            $unread_count = mysqli_fetch_assoc($unread_query)['count'];
                            if($unread_count > 0):
                            ?>
                                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=profile">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=logout">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'login') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>?page=login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'register') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>?page=register">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>