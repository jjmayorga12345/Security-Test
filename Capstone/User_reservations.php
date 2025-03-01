<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation attacks

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Generate CSRF token if not already set (for canceling reservations, etc.)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Include reservation model and DB
require_once __DIR__ . '/model/model_reservations.php';
require_once __DIR__ . '/model/db.php';

// 4. Connect to DB
$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed.");
}

// 5. Sanitize GET inputs for searching reservations
$searchMyReservations = isset($_GET['search_MyReservations'])
    ? htmlspecialchars(trim($_GET['search_MyReservations']), ENT_QUOTES, 'UTF-8')
    : '';

$userid = $_SESSION['user_id'];

// 6. Use prepared statements for search
$MyReservationsQuery = "
    SELECT *
    FROM reservations
    WHERE Confirmed = TRUE
      AND UserID = ?
      AND (ReservationName LIKE ? OR Date LIKE ?)
";
$stmt = $conn->prepare($MyReservationsQuery);
$searchParam = '%' . $searchMyReservations . '%';
$stmt->bind_param("iss", $userid, $searchParam, $searchParam);
$stmt->execute();
$MyReservationsResults = $stmt->get_result();

// 7. Another query for 'pending' or additional data if needed
$searchUserID = isset($_GET['search_user_id']) ? (int)$_GET['search_user_id'] : $userid;
$pendingQuery = "
    SELECT *
    FROM reservations
    WHERE Confirmed = TRUE
      AND UserID = ?
";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("i", $searchUserID);
$stmt->execute();
$pendingResults = $stmt->get_result();

// 8. Handle POST for canceling reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {

    // (a) Check CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        echo "<script>alert('Invalid CSRF token.'); window.location.href = 'User_reservations.php';</script>";
        exit();
    }

    // (b) Validate reservation_id
    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
    if (!$reservation_id) {
        echo "<script>alert('Invalid reservation ID.'); window.location.href = 'User_reservations.php';</script>";
        exit();
    }

    // (c) Attempt deletion
    if (deleteReservation($reservation_id)) {
        echo "<script>alert('Reservation canceled successfully!'); window.location.href = 'User_reservations.php';</script>";
    } else {
        echo "<script>alert('Error canceling reservation.');</script>";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Capstone</title>
  <link rel="stylesheet" href="style.css">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
  >
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    body {
      display: flex;
      flex-direction: column;
    }
    footer {
      background-color: #333;
      color: #fff;
      padding: 20px;
      text-align: center;
      font-family: Nanum Myeongjo, serif;
      width: 100%;
      position: fixed;
      bottom: 0;
    }
  </style>
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

<div
  id="nav"
  class="navbar navbar-expand-lg bg-body-tertiary"
  style="background-color:#FCE4DE; font-family: 'Kameron', serif; color:black;"
>
  <div class="container-fluid">
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarNav"
      aria-controls="navbarNav"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
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
            <a
              class="nav-link"
              href="registration.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>"
            >
              My Account
            </a>
          </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['user']) && $_SESSION['user']) : ?>
          <li class="nav-item">
            <a
              class="nav-link"
              href="user_reservations.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>"
            >
              My Reservations
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if(isset($_SESSION['user'])): ?>
          <li class="nav-item">
            <span class="navbar-text">
              Welcome <?= htmlspecialchars($_SESSION['user']); ?>
            </span>
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

<div class="container my-4" style="font-family: Nanum Myeongjo, serif;">
  <h2>My Reservations</h2>

  <div class="row">
    <?php if ($MyReservationsResults->num_rows > 0) : ?>
      <?php while ($reservation = $MyReservationsResults->fetch_assoc()) : ?>
        <div class="col-12 my-2">
          <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5><?= htmlspecialchars($reservation['ReservationName']); ?></h5>
                <p>Address: <?= htmlspecialchars($reservation['Address']); ?></p>
                <p>Date of reservation: <?= htmlspecialchars($reservation['Date']); ?></p>
              </div>
              <div>
                <a
                  href="reservation_view_user.php?action=view&reservationID=<?= (int)$reservation['ReservationID']; ?>"
                >
                  <button
                    type="button"
                    class="btn"
                    style="background-color:#81A483;color:white;"
                  >
                    View
                  </button>
                </a>
                <!-- Cancel Reservation Form (Optional) -->
                <form action="User_reservations.php" method="post" style="display: inline;">
                  <input type="hidden" name="reservation_id" value="<?= (int)$reservation['ReservationID']; ?>">
                  <!-- Add a hidden CSRF token -->
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                  <!-- If you want to enable the cancel button, uncomment below -->
                  <!--
                  <button
                    type="submit"
                    name="cancel"
                    class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to cancel this reservation?');"
                  >
                    Cancel
                  </button>
                  -->
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else : ?>
      <p>No reservations found.</p>
    <?php endif; ?>
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

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"
></script>
</body>
</html>
