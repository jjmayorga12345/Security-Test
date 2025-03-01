<?php
session_start();  // 1. Start session so we can use $_SESSION

// 2. Generate a CSRF token if we don’t already have one
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'model/db.php';
$conn = getDBConnection();

// Sanitize the 'inactive' GET parameter
$isActive = (isset($_GET['inactive']) && $_GET['inactive'] == '1') ? 0 : 1;

// Use a prepared statement to avoid SQL injection
$stmt = $conn->prepare("SELECT * FROM products WHERE isActive = ?");
$stmt->bind_param("i", $isActive);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Furniture Rental Service</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #FAF3EE; }
        .navbar { background-color: #FFF5F0; }
        .product-card {
            border: none;
            background-color: #FDF2ED;
            text-align: center;
            position: relative;
        }
        .product-card img { height: 350px; object-fit: cover; }
        .cart-button {
            position: absolute;
            bottom: 10px;
            right: 10px;
        }
        .floating-cart {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #81A483;
            color: white;
            padding: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px;
            font-size: 12px;
        }
        .add-item-card {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 350px;
            cursor: pointer;
            font-size: 48px;
            background-color: #f9f1ea;
            border: 2px dashed #e38d6d;
            transition: background-color 0.3s;
        }
        .add-item-card:hover {
            background-color: #fce4de;
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
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="registration.php?action=edit&UserID=<?= $_SESSION['user_id']; ?>">My Account</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="user_reservations.php?action=edit&UserID=<?= $_SESSION['user_id']; ?>">My Reservations</a>
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

<div class="container mt-4" style="font-family: Nanum Myeongjo, serif;">
    <div class="row">
        <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <!-- Toggle for active/inactive products -->
                    <input class="form-check-input" type="checkbox" id="toggleActive" 
                           <?= $isActive ? '' : 'checked'; ?> 
                           onchange="toggleProducts()">
                    <label class="form-check-label" for="toggleActive">Show Inactive Products</label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <input type="text" id="searchInput" class="form-control" placeholder="Search for products..." onkeyup="searchProducts()">
        </div>
    </div>

    <div class="row mt-3" id="productGrid">
        <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
            <div class="col-md-3">
                <div class="add-item-card" onclick="window.location.href='upload_product.php'">
                    <p>Add Item</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-3 product-item">
                    <div class="card product-card" style="border: #bcbcbc 1px solid; padding: 25px; margin-bottom: 25px;">
                        <img src="image.php?id=<?= (int)$row['ProductID']; ?>" class="card-img-top" alt="<?= htmlspecialchars($row['Name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['Name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="card-text">Available: <?= (int)$row['Availability']; ?></p>
                            <p class="card-text">Price: $<?= number_format((float)$row['Price'], 2); ?></p>

                            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                <a href="upload_product.php?edit=<?= (int)$row['ProductID']; ?>" 
                                   class="btn btn" 
                                   style="background-color:#81A483; color:white;">Edit</a>
                            <?php endif; ?>

                            <div style="padding: 10px;">
                                <!-- 3. Add to Cart button calls addToCart() with CSRF token -->
                                <button class="btn btn-light cart-button" style="background-color:#81A483; color:white;"
                                        onclick="addToCart(<?= (int)$row['ProductID']; ?>, '<?= addslashes($row['Name']); ?>', <?= (float)$row['Price']; ?>)">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No products found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Floating cart icon -->
<div class="floating-cart" id="floatingCart" onclick="viewCart()">
    <i class="bi bi-cart-fill"></i>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Retrieve the CSRF token from the PHP session
    const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";

    function addToCart(productID, productName, price) {
        // If user is not logged in, redirect to login (optional check, or do it server-side)
        <?php if (!isset($_SESSION['user'])): ?>
            window.location.href = 'login.php';
            return;
        <?php endif; ?>

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "cart_system.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // 2. Include the CSRF token in the POST data
        let data = 
            "csrf_token=" + encodeURIComponent(csrfToken) +
            "&action=add" +
            "&productID=" + encodeURIComponent(productID) +
            "&productName=" + encodeURIComponent(productName) +
            "&productPrice=" + encodeURIComponent(price) +
            "&quantity=1";

        xhr.send(data);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Provide feedback to the user
                alert(xhr.responseText);
                // Optionally, update cart count or do something else
            }
        };
    }

    function updateCartCount() {
        // If you have a get_cart_count.php or similar, you can fetch it here
        // e.g., an AJAX call to update a "cart count" badge
    }

    function viewCart() {
        // Navigate to cart_system.php or your dedicated cart page
        window.location.href = 'cart_system.php';
    }

    function toggleProducts() {
        const checkbox = document.getElementById('toggleActive');
        window.location.href = `Catalog.php?inactive=${checkbox.checked ? '1' : '0'}`;
    }

    function searchProducts() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const products = document.querySelectorAll('.product-item');

        products.forEach(product => {
            const title = product.querySelector('.card-title').textContent.toLowerCase();
            if (title.includes(filter)) {
                product.style.display = '';
            } else {
                product.style.display = 'none';
            }
        });
    }

    // Optionally call updateCartCount() on page load
    // updateCartCount();
</script>

<footer style="background-color: #333; color: #fff; padding: 20px; text-align: center; font-family: Nanum Myeongjo, serif;">
    <p>&copy; 2024 Ileanas Rentals. All Rights Reserved.</p>
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
