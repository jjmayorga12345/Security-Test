<?php
session_start();
// Mitigate session fixation attacks
session_regenerate_id(true);

// If you don't actually need model_reservations or the DB, you can remove this
// but we'll keep it if you want consistent structure across pages
include __DIR__ . '/model/model_reservations.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .contact-card {
            border: 1px solid #000000;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 10px;
            background-color: #f8f9fa;
            height: 300px;
        }
        .contact-card img {
            height: 100px;
        }
        .delivery-section {
            border: 1px solid #ddd;
            border-radius: 15px;
            padding: 20px;
            background-color: #f8f9fa;
            margin: 10px 0;
        }
        .delivery-section img {
            width: 100%;
            border-radius: 15px;
        }
        .delivery-section h2 {
            text-align: center;
        }
        .cities-list {
            list-style-type: none;
            padding-left: 0;
            columns: 2;
        }
    </style>
</head>
<body style="background-color:#fce4dea5; font-family: Nanum Myeongjo, serif;">

<div class="container-fluid">
    <div class="row">
        <div class="col-1">
            <a href="home.php">
                <img src="images/Logo.png" alt="logo" style="width: 100px; height: 100px;">
            </a>
        </div>
        <div class="col-3" style="margin-top: 25px;">
            <h1 style="font-family: Nanum Myeongjo, serif;">Ileanas Rentals</h1>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg bg-body-tertiary" style="background-color:#FCE4DE; font-family: 'Kameron', serif;">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="Catalog.php">Catalog</a></li>
                <li class="nav-item"><a class="nav-link" href="Catalog.php">Reserve</a></li>
                <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) : ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="registration.php?action=edit&UserID=<?= $_SESSION['user_id']; ?>">My Account</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user_reservations.php?action=edit&UserID=<?= $_SESSION['user_id']; ?>">My Reservations</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text">Welcome <?= htmlspecialchars($_SESSION['user']); ?></span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Contact Cards -->
<div class="container" style="margin-top: 25px;">
    <div class="row justify-content-center">
        <div class="col-md-3">
            <div class="contact-card">
                <img src="images/phoneIcon.png" alt="Phone Icon">
                <h5 style="font-weight: bold;">Phone</h5>
                <p>Ileana Melendez</p>
                <p>(401)-665-5988</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="contact-card">
                <img src="images/emailIcon.png" alt="Email Icon">
                <h5 style="font-weight: bold;">Email</h5>
                <p>Personal: IleanaMelende223@gmail.com</p>
                <p>Business: IleanasRenals@outlook.com</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="contact-card">
                <img src="images/gpsIcon.png" alt="Address Icon">
                <h5 style="font-weight: bold;">Address</h5>
                <p>Main Building</p>
                <p>44 Cranston St, Cranston Rhode Island 02920</p>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Section -->
<div class="delivery-section" style="font-size: 25px; margin-top: 75px;">
    <h2>Delivery Radius</h2>
    <div class="row">
        <div class="col-md-4">
            <img src="images/map.png" alt="Delivery Map">
        </div>
        <div class="col-md-8">
            <strong style="font-weight: bold;">Cities we deliver to:</strong>
            <ul class="cities-list">
                <li>Cranston</li>
                <li>Providence</li>
                <li>Warwick</li>
                <li>Fiskeville</li>
                <li>Johnston</li>
                <li>West Warwick</li>
                <li>North Providence</li>
                <li>Riverside</li>
                <li>East Providence</li>
                <li>Hope,Rumford</li>
                <li>Barrington</li>
                <li>Pawtucket</li>
                <li>East Greenwich</li>
                <li>Greenville</li>
                <li>Seekonk</li>
                <li>Central Falls</li>
            </ul>
            <a href="Catalog.php" class="btn btn" style="float: right; margin-right: 45px; background-color: #81A483; color:white;font-size: 35px;">Reserve</a>
        </div>
    </div>
</div>

<footer style="background-color: #333; color: #fff; padding: 20px; text-align: center; font-family: Nanum Myeongjo, serif;">
    <p>&copy; 2024 Ileans Rentals. All Rights Reserved.</p>
    <nav>
        <a href="contactus.php" style="color: #fff; margin: 0 10px;">Contact Us</a>
        <a href="Catalog.php" style="color: #fff; margin: 0 10px;">Catalog</a>
        <a href="https://doculj1.netlify.app/" style="color: #fff; margin: 0 10px;">Documentation</a>
    </nav>
    <p>
        Follow us on:
        <a href="https://www.facebook.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Facebook</a> |
        <a href="https://www.twitter.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Twitter</a> |
        <a href="https://www.instagram.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Instagram</a>
    </p>
</footer>

</body>
</html>
