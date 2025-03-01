<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation

/************************************************************
 * 1. Admin check
 ************************************************************/
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: restricted.php');
    exit();
}

/************************************************************
 * 2. Generate CSRF token if not set
 ************************************************************/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include DB connection
require_once 'model/db.php';
$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed.");
}

// Read action and reservationID
$action        = isset($_GET['action']) ? $_GET['action'] : '';
$reservationID = isset($_GET['reservationID']) ? intval($_GET['reservationID']) : 0;

// Only proceed if action=edit/view and reservationID>0
if (($action === 'edit' || $action === 'view') && $reservationID > 0) {

    // Fetch the reservation
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
    $stmt->bind_param("i", $reservationID);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // If no reservation found
    if (!$reservation) {
        echo "Reservation not found.";
        exit();
    }

    // Confirmed flag
    $isConfirmed = isset($reservation['Confirmed']) ? $reservation['Confirmed'] : 0;

    // Fetch associated products
    $productStmt = $conn->prepare("
        SELECT p.Name AS ProductName, rp.Quantity, rp.Price 
        FROM reservedproducts rp 
        JOIN products p ON rp.ProductID = p.ProductID 
        WHERE rp.ReservationID = ?
    ");
    $productStmt->bind_param("i", $reservationID);
    $productStmt->execute();
    $reservedProducts = $productStmt->get_result();
    $productStmt->close();

    // Format date for <input type="date">
    $formattedDate = date('Y-m-d', strtotime($reservation['Date']));

    /************************************************************
     * 3. Process POST requests (update/delete/confirm)
     ************************************************************/
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // (a) Check CSRF token
        if (
            !isset($_POST['csrf_token']) ||
            !isset($_SESSION['csrf_token']) ||
            $_POST['csrf_token'] !== $_SESSION['csrf_token']
        ) {
            die("Error: Invalid CSRF token.");
        }

        // (b) If user clicked "update" in edit mode
        if ($action === 'edit' && isset($_POST['update'])) {
            // Sanitize new inputs
            $newName    = htmlspecialchars(trim($_POST['ReservationName'] ?? ''), ENT_QUOTES, 'UTF-8');
            $newAddress = htmlspecialchars(trim($_POST['Address'] ?? ''), ENT_QUOTES, 'UTF-8');
            $newDate    = trim($_POST['Date'] ?? '');

            $updateStmt = $conn->prepare("
                UPDATE reservations
                SET ReservationName = ?, Address = ?, Date = ?
                WHERE ReservationID = ?
            ");
            $updateStmt->bind_param("sssi", $newName, $newAddress, $newDate, $reservationID);

            if ($updateStmt->execute()) {
                echo "Reservation updated successfully!";
                // Optionally redirect back to view mode
                header('Location: reservation_view.php?action=view&reservationID=' . $reservationID);
                exit();
            } else {
                echo "Error updating reservation.";
            }
            $updateStmt->close();
        }

        // (c) If user clicked "delete" in either edit/view mode
        // Notice you have two separate blocks for (view + delete) and (edit + delete).
        // We can unify them or keep them separate to match your existing logic:
        if ((($action === 'view') || ($action === 'edit')) && isset($_POST['delete'])) {
            // Delete from reservedproducts first
            $deleteRP = $conn->prepare("DELETE FROM reservedproducts WHERE ReservationID = ?");
            $deleteRP->bind_param("i", $reservationID);
            $deleteRP->execute();
            $deleteRP->close();

            // Then delete the reservation
            $deleteStmt = $conn->prepare("DELETE FROM reservations WHERE ReservationID = ?");
            $deleteStmt->bind_param("i", $reservationID);

            if ($deleteStmt->execute()) {
                echo "Reservation deleted successfully!";
                header('Location: admin_dashboard.php');
                exit();
            } else {
                echo "Error deleting reservation.";
            }
            $deleteStmt->close();
        }

        // (d) If user clicked "confirm" in view mode
        if ($action === 'view' && isset($_POST['confirm'])) {
            $confirmStmt = $conn->prepare("
                UPDATE reservations
                SET Confirmed = 1
                WHERE ReservationID = ?
            ");
            $confirmStmt->bind_param("i", $reservationID);

            if ($confirmStmt->execute()) {
                echo "Reservation confirmed successfully!";
                header('Location: admin_dashboard.php');
                exit();
            } else {
                echo "Error confirming reservation.";
            }
            $confirmStmt->close();
        }
    }

    // 4. Output the HTML
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= ($action === 'edit') ? 'Edit' : 'View'; ?> Reservation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body style="background-color:#fce4dea5; font-family: Nanum Myeongjo, serif;">
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

    <div id="nav" class="navbar navbar-expand-lg bg-body-tertiary"
         style="background-color:#FCE4DE;font-family: 'Kameron', serif; color:black;">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                  aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <a class="nav-link" href="registration.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>">My Account</a>
                </li>
              <?php endif; ?>
              <?php if (isset($_SESSION['user']) && $_SESSION['user']) : ?>
                <li class="nav-item">
                    <a class="nav-link" href="user_reservations.php?action=edit&UserID=<?= (int)$_SESSION['user_id']; ?>">My Reservations</a>
                </li>
              <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
            <?php if (isset($_SESSION['user'])): ?>
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

    <div class="container">
        <h2><?= ($action === 'edit') ? 'Edit' : 'View'; ?> Reservation</h2>
        
        <form method="POST" action="">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="mb-3">
                <label for="ReservationName" class="form-label">Reservation Name</label>
                <input
                    type="text"
                    class="form-control"
                    id="ReservationName"
                    name="ReservationName"
                    value="<?= htmlspecialchars($reservation['ReservationName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    <?= ($action === 'view') ? 'readonly' : ''; ?>
                    required
                >
            </div>
            <div class="mb-3">
                <label for="Address" class="form-label">Address</label>
                <input
                    type="text"
                    class="form-control"
                    id="Address"
                    name="Address"
                    value="<?= htmlspecialchars($reservation['Address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    <?= ($action === 'view') ? 'readonly' : ''; ?>
                    required
                >
            </div>
            <div class="mb-3">
                <label for="Date" class="form-label">Date</label>
                <input
                    type="date"
                    class="form-control"
                    id="Date"
                    name="Date"
                    value="<?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8'); ?>"
                    <?= ($action === 'view') ? 'readonly' : ''; ?>
                    required
                >
            </div>

            <?php if ($action === 'edit'): ?>
                <!-- "Update" & "Delete" for Edit Mode -->
                <button
                    type="submit"
                    name="update"
                    class="btn"
                    style="background-color: #81A483; color: white;"
                >
                    Update Reservation
                </button>
                <button
                    type="submit"
                    name="delete"
                    class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to delete this reservation?');"
                >
                    Delete Reservation
                </button>
            <?php endif; ?>
            
            <?php if ($action === 'view'): ?>
                <!-- "Confirm" & "Delete" for View Mode -->
                <button
                    type="submit"
                    name="confirm"
                    class="btn"
                    style="background-color: #81A483; color:white;"
                    <?= $isConfirmed ? 'disabled' : ''; ?>
                >
                    Confirm Reservation
                </button>
                <button
                    type="submit"
                    name="delete"
                    class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to delete this reservation?');"
                >
                    Delete Reservation
                </button>
            <?php endif; ?>
        </form>
        <br>

        <!-- Show Reserved Products -->
        <?php if ($action === 'view' || $action === 'edit'): ?>
            <h3>Reserved Products</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $reservedProducts->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= (int)$product['Quantity']; ?></td>
                            <td><?= htmlspecialchars($product['Price'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($reservedProducts->num_rows === 0): ?>
                        <tr>
                            <td colspan="3">No products reserved.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="btn" style="background-color: #81A483; color:white;">Back to Dashboard</a>
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

} else {
    echo "Invalid request or invalid Reservation ID.";
    exit();
}
