<?php
// 1. Start session & mitigate session fixation
session_start();
session_regenerate_id(true);

require_once 'model/model_reservations.php';

// Initialize variables for the form
$email    = '';
$password = '';
$login_error = '';

// 2. Check if login form was submitted
if (isset($_POST['login'])) {
    // Sanitize user inputs
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']); // We don't HTML-escape the password, as we need the raw value for hashing/verification

    // 3. Attempt login using your existing function
    $user = login($email, $password);

    if ($user) {
        // 4. On successful login, store session data
        // Optionally, regenerate session ID again right after successful login
        session_regenerate_id(true);

        $_SESSION['user']     = $email;
        $_SESSION['user_id']  = $user['userID'];
        $_SESSION['isAdmin']  = $user['isAdmin'];

        // Redirect to home or wherever
        header('Location: home.php');
        exit;
    } else {
        // 5. On invalid login, clear session and set error message
        session_unset();
        $login_error = "Invalid email or password.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Capstone - Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FAF3EE;
        }
        .navbar {
            background-color: #FFF5F0;
        }
        .login-form {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            max-width: 400px;
            margin: auto;
            margin-top: 100px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 25px;
            padding: 20px;
        }
        .btn-login {
            border-radius: 25px;
            padding: 10px 40px;
        }
    </style>
</head>
<body style="background-color:#fce4dea5;">

<div class="container-fluid">
    <div class="row">
        <div class="col-1">
            <a href="home.php"><img src="images/Logo.png" alt="logo" style="width: 100px; height: 100px;"></a>
        </div>
        <div class="col-3" style="margin-top: 25px; font-family: Nanum Myeongjo, serif;">
            <h1>Ileanas Rentals</h1>
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

<div class="login-form" style="font-family: Nanum Myeongjo, serif; margin-bottom:80px;">
    <h2>Login</h2>
    <form action="login.php" method="post">
        <div class="form-group">
            <label for="email">Email:</label>
            <!-- 6. Echo sanitized $email in the form -->
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" 
                required
            >
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <!-- 7. For password, we typically do not show it, but if you do, escape it -->
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                value="<?= htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?>" 
                required
            >
        </div>
        <button 
            type="submit" 
            name="login" 
            class="btn btn-login" 
            style="background-color: #81A483; margin-top: 10px; color:white"
        >
            Login
        </button>

        <?php if (!empty($login_error)): ?>
            <p style="color: red;"><?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </form>
    <p>Don't have an account? <a href="registration.php">Register here</a></p>
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
