<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation attacks

require_once 'model/db.php';
$conn = getDBConnection();

// Ensure only admins can access this page
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: restricted.php');
    exit();
}

// Sanitize and trim search inputs
$searchPending  = isset($_GET['search_pending'])  ? htmlspecialchars(trim($_GET['search_pending']))  : '';
$searchReserved = isset($_GET['search_reserved']) ? htmlspecialchars(trim($_GET['search_reserved'])) : '';

// Prepare statement for pending reservations
$pendingQuery = "SELECT * FROM reservations WHERE Confirmed = FALSE AND (ReservationName LIKE ? OR Date LIKE ?)";
$stmt = $conn->prepare($pendingQuery);
$searchParam = '%' . $searchPending . '%';
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$pendingResults = $stmt->get_result();

// Prepare statement for confirmed reservations
$reservedQuery = "SELECT * FROM reservations WHERE Confirmed = TRUE AND (ReservationName LIKE ? OR Date LIKE ?)";
$stmt = $conn->prepare($reservedQuery);
$searchParam = '%' . $searchReserved . '%';
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$reservedResults = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Capstone</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fce4dea5;
      font-family: Nanum Myeongjo, serif;
    }
    .container {
      margin-top: 30px;
    }
    .panel-title {
      text-align: center;
      background-color: white;
      border-radius: 20px;
      width: 50%;
      margin: 0 auto 20px auto;
      padding: 10px;
      font-weight: bold;
    }
    .table {
      width: 100%;
      background-color: white;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .table th, .table td {
      padding: 15px;
      text-align: left;
    }
    .btn-view {
      background-color: #ccc;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }
    .search-bar {
      margin-bottom: 20px;
    }
    footer {
      background-color: #333;
      color: #fff;
      padding: 20px;
      text-align: center;
      font-family: Nanum Myeongjo, serif;
      width: 100%;
    }
    #nav {
      width: 100%;
      background-color: #FCE4DE;
    }
  </style>
</head>
<body>
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

  <div id="nav" class="navbar navbar-expand-lg bg-body-tertiary" style="background-color:#FCE4DE;font-family: 'Kameron', serif; color:black;">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="Catalog.php">Catalog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="Catalog.php">Reserve</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="contactus.php">Contact Us</a>
          </li>
          <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) : ?>
            <li class="nav-item">
              <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
            </li>
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
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Logout</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="login.php">Login</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="container" style="padding-bottom: 350px;">
    <h2 class="text-center">Admin Panel</h2>
    <h4 class="text-center">Reservations</h4>
    <div class="row">
      <!-- Pending Reservations -->
      <div class="col-6">
        <div class="panel">
          <div class="panel-title">Pending Reservations</div>
          <form class="search-bar" method="GET" action="">
            <input type="text" name="search_pending" value="<?= htmlspecialchars($searchPending); ?>" placeholder="Search pending reservations..." class="form-control">
            <button style="background-color: #81A483; color:white;" type="submit" class="btn btn mt-2">Search</button>
          </form>
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $pendingResults->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['ReservationName']); ?></td>
                  <td><?= htmlspecialchars($row['Address']); ?></td>
                  <td><?= htmlspecialchars($row['Date']); ?></td>
                  <td>
                    <a href="reservation_view.php?action=view&reservationID=<?= (int)$row['ReservationID']; ?>">
                      <button style="background-color: #81A483; color:white; font-family: Nanum Myeongjo, serif;" class="btn-view">
                        view
                      </button>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reserved Reservations -->
      <div class="col-6">
        <div class="panel">
          <div class="panel-title">Reserved</div>
          <form class="search-bar" method="GET" action="">
            <input type="text" name="search_reserved" value="<?= htmlspecialchars($searchReserved); ?>" placeholder="Search reserved items..." class="form-control">
            <button style="background-color: #81A483; color:white;" type="submit" class="btn btn mt-2">Search</button>
          </form>
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $reservedResults->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['ReservationName']); ?></td>
                  <td><?= htmlspecialchars($row['Address']); ?></td>
                  <td><?= htmlspecialchars($row['Date']); ?></td>
                  <td>
                    <a href="reservation_view.php?action=edit&reservationID=<?= (int)$row['ReservationID']; ?>">
                      <button style="background-color: #81A483; color:white; font-family: Nanum Myeongjo, serif;" class="btn-view">
                        edit
                      </button>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
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
    <p>Follow us on:
      <a href="https://www.facebook.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Facebook</a> |
      <a href="https://www.twitter.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Twitter</a> |
      <a href="https://www.instagram.com/yourcompany" target="_blank" style="color: #fff; margin: 0 10px;">Instagram</a>
    </p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
