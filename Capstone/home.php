<?php
session_start();
// Mitigate session fixation attacks
session_regenerate_id(true);

// If you don't need model_reservations, you can remove it
include __DIR__ . '/model/model_reservations.php';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Capstone</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color:#fce4dea5;">
  <div class="container-fluid">
      <div class="row">
          <div class="col-1">
              <a href="home.php">
                  <img src="images/Logo.png" alt="logo" style="width: 100px; height: 100px;">
              </a>
          </div>
          <div class="col-3" style="margin-top: 25px; font-family: Nanum Myeongjo, serif;">
              <h1>Ileanas Rentals</h1>
          </div>
      </div>
  </div>

  <nav class="navbar navbar-expand-lg bg-body-tertiary" style="background-color:#FCE4DE; font-family: 'Kameron', serif; color:black;">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="Catalog.php">Catalog</a></li>
            <li class="nav-item"><a class="nav-link" href="Catalog.php">Reserve</a></li>
            <li class="nav-item"><a class="nav-link" href="contactus.php">Contact Us</a></li>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
              <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a></li>
            <?php endif; ?>
            <?php if (isset($_SESSION['user']) && $_SESSION['user']): ?>
              <li class="nav-item">
                <a class="nav-link" href="registration.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>">My Account</a>
              </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['user']) && $_SESSION['user']): ?>
              <li class="nav-item">
                <a class="nav-link" href="user_reservations.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>">My Reservations</a>
              </li>
            <?php endif; ?>
          </ul>

          <ul class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user'])): ?>
              <li class="nav-item">
                <span class="navbar-text">Welcome <?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
  </nav>

  <!-- Carousel Section -->
  <div id="carouselExample" class="carousel slide">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="images/weddingchairs.avif" class="d-block w-100" alt="...">
        </div>
        <div class="carousel-item">
          <img src="images/table.webp" class="d-block w-100" alt="...">
        </div>
        <div class="carousel-item">
          <img src="images/wedding.jpg" class="d-block w-100" alt="...">
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
  </div>

  <!-- About Rentals -->
  <div id="aboutUS" class="container-fluid" style="height: 190px; margin-top: 35px;">
      <div class="row">
          <h2 class="text-center">About Rentals</h2>
          <p class="text-center">
            We offer a wide selection of high-quality rentals for all your needs. Whether you're planning a 
            family vacation, a weekend getaway, or a special event, we have the perfect rental for you. 
            Our rentals are well-maintained and fully equipped to ensure your comfort and satisfaction. 
            Browse our catalog and reserve your rental today!
          </p>
      </div>
  </div>

  <!-- Featured Items -->
  <div id="featuredItems" class="container-fluid" style="height: 250px; margin-top: 35px; margin-bottom: 65px; padding:5px;">
    <div class="row align-items-center"> 
      <div class="col-7">
        <h2>Featured Items</h2>
        <p>
          Add a touch of sophistication to your next event with our elegant Chiavari chairs. 
          Perfect for weddings, banquets, and upscale gatherings, these chairs offer both style and comfort. 
          Their sturdy construction ensures they can withstand the demands of any event, while their timeless design 
          complements any decor. Rent our Chiavari chairs to elevate the ambiance of your special occasion.
        </p>
        <a href="Catalog.php" class="btn btn-light" style="float: right; margin-right: 25px; background-color:#81A483; color:white;">
          Reserve
        </a>
      </div>
      <div class="col-5 d-flex justify-content-center">
        <img src="images/Chair-featured.jpg" alt="..." style="max-width: 100%; height: auto; padding-bottom:30px; margin-top: 8px;">
      </div>
    </div>
  </div>

  <footer style="background-color: #333; color: #fff; padding: 20px; text-align: center; font-family: Nanum Myeongjo, serif;">
    <p>&copy; 2024 Ileanas Rentals. All Rights Reserved.</p>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
