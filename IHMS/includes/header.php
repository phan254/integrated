<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta http-equiv="Permissions-Policy" content="geolocation=(), interest-cohort=()">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Google Maps API - Replace YOUR_API_KEY with your actual API key -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>
<!-- Mobile Optimization Meta Tags -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">

<!-- Custom Mobile Styles -->
<style>
    /* Mobile specific adjustments */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .card {
            margin-bottom: 15px;
        }
        
        .table-responsive {
            margin-bottom: 0;
        }
        
        .navbar-brand {
            font-size: 1.2rem;
        }
        
        /* Mobile friendly buttons */
        .btn {
            padding: 0.375rem 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Card and form adjustments */
        .card-body {
            padding: 1rem;
        }
        
        .form-control, .form-select {
            font-size: 0.9rem;
        }
        
        /* Dashboard cards adjustments */
        .display-4 {
            font-size: 2rem;
        }
        
        /* Sidebar collapse on mobile */
        .col-md-3.col-lg-2 {
            margin-bottom: 15px;
        }
    }
</style>
</head>
<body>
    <div class="container-fluid">