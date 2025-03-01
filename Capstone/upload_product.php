<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation attacks

/************************************************************
 * 1. Admin check: Only admins can upload/edit products
 ************************************************************/
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: restricted.php');
    exit();
}

/************************************************************
 * 2. Generate CSRF token if not already set
 ************************************************************/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'model/db.php';

$productID   = 0;
$name        = '';
$availability= '';
$price       = '';
$isActive    = 1; 

/************************************************************
 * If editing an existing product (GET param: ?edit=ID)
 ************************************************************/
if (isset($_GET['edit'])) {
    $productID = intval($_GET['edit']);
    $conn      = getDBConnection();
    if (!$conn) {
        die("Database connection failed.");
    }

    // Use a prepared statement to get the product
    $sql  = "SELECT * FROM products WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product      = $result->fetch_assoc();
        $name         = $product['Name'];
        $availability = $product['Availability'];
        $price        = $product['Price'];
        $isActive     = $product['isActive'];
    }

    $stmt->close();
    $conn->close();
}

/************************************************************
 * 3. Handle POST request: Insert or Update product
 ************************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // (a) CSRF token check
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Error: Invalid CSRF token.");
    }

    // (b) Sanitize user inputs
    $name         = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $availability = (int)($_POST['availability'] ?? 0);
    $price        = (float)($_POST['price'] ?? 0);
    $isActive     = isset($_POST['isActive']) ? 1 : 0;

    // (c) (Optional) Basic file checks for the uploaded image
    $imgData = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // For security, you might limit file size, check MIME type, etc.
        // e.g., if ($_FILES['image']['size'] > 2_000_000) { ... error ... }

        $imgData = file_get_contents($_FILES['image']['tmp_name']);
    }

    // (d) Insert or Update using prepared statements
    $conn = getDBConnection();
    if (!$conn) {
        die("Database connection failed.");
    }

    if ($productID > 0) {
        // Update existing product
        if ($imgData) {
            $sql  = "UPDATE products
                     SET Name = ?, Availability = ?, Price = ?, Image = ?, isActive = ?
                     WHERE ProductID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sidsii", $name, $availability, $price, $imgData, $isActive, $productID);
        } else {
            $sql  = "UPDATE products
                     SET Name = ?, Availability = ?, Price = ?, isActive = ?
                     WHERE ProductID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sidii", $name, $availability, $price, $isActive, $productID);
        }
    } else {
        // Insert new product
        if ($imgData) {
            $sql  = "INSERT INTO products (Name, Availability, Price, Image, isActive)
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sidsi", $name, $availability, $price, $imgData, $isActive);
        } else {
            $sql  = "INSERT INTO products (Name, Availability, Price, isActive)
                     VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sidi", $name, $availability, $price, $isActive);
        }
    }

    // (e) Execute and report results
    if ($stmt->execute()) {
        echo "Product successfully " . ($productID ? 'updated' : 'uploaded') . "!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $productID ? 'Edit' : 'Upload'; ?> Product</title>
    <link rel="stylesheet" href="style.css">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <style>
        body {
            font-family: Nanum Myeongjo, serif;
            background-color: #f9f1ea;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #e38d6d;
        }
        label {
            font-size: 18px;
            display: block;
            margin: 15px 0 5px;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="checkbox"] {
            transform: scale(1.5);
            margin: 10px 0;
        }
        input[type="submit"] {
            background-color: #e38d6d;
            border: none;
            color: white;
            padding: 15px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 18px;
            margin-top: 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        input[type="submit"]:hover {
            background-color: #d36a4c;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #e38d6d;
            text-decoration: none;
            font-size: 18px;
        }
        .back-link a:hover {
            text-decoration: underline;
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
    style="background-color:#FCE4DE;font-family: 'Kameron', serif; color:black;"
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

<div class="container">
    <h2 style="color: #81A483;">
        <?= $productID ? 'Edit' : 'Upload'; ?> Product
    </h2>
    <!-- CSRF token in the form -->
    <form
        action="upload_product.php<?= $productID ? '?edit=' . (int)$productID : ''; ?>"
        method="post"
        enctype="multipart/form-data"
    >
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <label for="name">Product Name:</label>
        <input
            type="text"
            id="name"
            name="name"
            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >

        <label for="availability">Availability:</label>
        <input
            type="number"
            id="availability"
            name="availability"
            value="<?= htmlspecialchars($availability, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >

        <label for="price">Price:</label>
        <input
            type="number"
            step="0.01"
            id="price"
            name="price"
            value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >

        <label for="image">Upload New Image (Optional):</label>
        <input
            type="file"
            id="image"
            name="image"
            accept="image/*"
        >
        
        <div>
            <label for="isActive">Active Product:</label>
            <input
                type="checkbox"
                id="isActive"
                name="isActive"
                <?= ($isActive == 1) ? 'checked' : ''; ?>
            >
        </div>

        <input
            style="background-color: #81A483;"
            type="submit"
            value="<?= $productID ? 'Update' : 'Upload'; ?> Product"
        >
    </form>
    <div class="back-link">
        <a style="color: #81A483;" href="Catalog.php">Back to Catalog</a>
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
</body>
</html>
