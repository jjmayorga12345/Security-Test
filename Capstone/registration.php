<?php
session_start();
// Mitigate session fixation attacks
session_regenerate_id(true);

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'model/model_reservations.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize form fields and error message
$error      = "";
$email      = '';
$password   = '';
$firstName  = '';
$phoneNumber= '';
$address    = '';
$zipCode    = '';
$state      = '';

// Determine if adding or editing
$action = filter_input(INPUT_GET, 'action') ?? 'add';
$id     = filter_input(INPUT_GET, 'UserID', FILTER_VALIDATE_INT);

if ($action === 'edit' && $id) {
    // If editing, load existing user
    $user = getUser($id);
    $firstName   = $user['FirstName']   ?? "";
    $email       = $user['Email']       ?? "";
    $password    = ""; // leave blank; only fill if user sets a new one
    $phoneNumber = $user['PhoneNumber'] ?? "";
    $address     = $user['Address']     ?? "";
    $zipCode     = $user['ZipCode']     ?? "";
    $state       = $user['State']       ?? "";
}

if (isset($_POST["storeUser"])) {
    // 1. Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Invalid CSRF token.");
    }

    // 2. Sanitize user inputs
    $firstName   = htmlspecialchars(trim($_POST['firstName']), ENT_QUOTES, 'UTF-8');
    $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $passwordRaw = trim($_POST['password']);
    $phoneNumber = htmlspecialchars(trim($_POST['phoneNumber']), ENT_QUOTES, 'UTF-8');
    $address     = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
    $zipCode     = htmlspecialchars(trim($_POST['zipCode']), ENT_QUOTES, 'UTF-8');
    $state       = htmlspecialchars(trim($_POST['state']), ENT_QUOTES, 'UTF-8');

    // 3. Validate required fields
    if ($firstName   === "") $error .= "<li>Please provide first name</li>";
    if ($email       === "") $error .= "<li>Please provide email</li>";
    if ($phoneNumber === "") $error .= "<li>Please provide phone number</li>";
    if ($address     === "") $error .= "<li>Please provide address</li>";
    if ($zipCode     === "") $error .= "<li>Please provide zip code</li>";
    if ($state       === "") $error .= "<li>Please provide state</li>";

    // If adding a new user, a password is mandatory
    if ($action === 'add' && $passwordRaw === "") {
        $error .= "<li>Please provide password</li>";
    }

    // 4. If no errors, proceed
    if ($error === "") {
        // 5. Pass the *raw* password to addUser() or updateUser()
        if ($action === 'add') {
            addUser($firstName, $email, $passwordRaw, $phoneNumber, $address, $zipCode, $state);
            header('Location: login.php');
            exit;
        } elseif ($action === 'edit') {
            // If $passwordRaw is empty, updateUser() keeps old password
            updateUser($id, $firstName, $email, $passwordRaw, $phoneNumber, $address, $zipCode, $state);
            header('Location: home.php');
            exit;
        }
    }

    // Optional: If you have a deleteUser action
    if (isset($_POST['deleteUser'])) {
        deleteUser($id);
        header('Location: home.php');
        exit;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FAF3EE;
        }
        .navbar {
            background-color: #FFF5F0;
        }
        .register-form {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            max-width: 600px;
            margin: auto;
            margin-top: 50px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 25px;
            padding: 20px;
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

<div id="nav" class="navbar navbar-expand-lg bg-body-tertiary" style="background-color:#FCE4DE;font-family: 'Kameron', serif; color:black;">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
          <?php if(isset($_SESSION['user'])): ?>
            <li class="nav-item">
              <span class="navbar-text">Welcome <?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8'); ?></span>
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

<div class="register-form" style="font-family: Nanum Myeongjo, serif; margin-bottom:80px;">
    <?php if ($error != ""): ?>
        <div class="alert alert-danger">
            <ul><?= $error ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your email"
                >
            </div>
            <div class="col-md-6 mb-3">
                <label for="address" class="form-label">Address</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="address" 
                    name="address" 
                    value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your address"
                >
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password"
                >
            </div>
            <div class="col-md-6 mb-3">
                <label for="zipcode" class="form-label">Zip Code</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="zipcode" 
                    name="zipCode" 
                    value="<?= htmlspecialchars($zipCode, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your zip code"
                >
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="firstname" class="form-label">First Name</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="firstname" 
                    name="firstName" 
                    value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your first name"
                >
            </div>
            <div class="col-md-6 mb-3">
                <label for="state" class="form-label">State</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="state" 
                    name="state" 
                    value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your state"
                >
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3 ">
                <label for="phone" class="form-label">Phone Number</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="phone" 
                    name="phoneNumber" 
                    value="<?= htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8'); ?>" 
                    placeholder="Enter your phone number"
                >
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-center">
            <input 
                style="background-color:#81A483; color:white;" 
                class="<?= ($action === 'edit') ? 'btn btn-info' : 'btn btn-success'; ?>" 
                type="submit" 
                name="storeUser" 
                value="<?= ($action === 'edit') ? 'Update Information' : 'Register'; ?>"
            >
        </div>
    </form>
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
