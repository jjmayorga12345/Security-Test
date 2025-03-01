<?php
session_start();

// Mitigate session fixation attacks
session_regenerate_id(true);

// 1. Generate a CSRF token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'model/db.php'; 
$conn = getDBConnection();

// Initialize the cart array if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 2. Validate CSRF token on every POST action
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "Error: Invalid CSRF token.";
        exit();
    }

    // 3. Sanitize the 'action' input
    $action = isset($_POST['action']) 
        ? htmlspecialchars(trim($_POST['action']), ENT_QUOTES, 'UTF-8') 
        : '';

    if ($action === 'add') {
        // Check required product data
        if (isset($_POST['productID'], $_POST['productName'], $_POST['productPrice'], $_POST['quantity'])) {
            // 4. Sanitize each field
            $productID   = filter_var($_POST['productID'], FILTER_VALIDATE_INT);
            $productName = htmlspecialchars($_POST['productName'], ENT_QUOTES, 'UTF-8');
            $productPrice= filter_var($_POST['productPrice'], FILTER_VALIDATE_FLOAT);
            $quantity    = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

            // Validate numeric inputs
            if ($productID === false || $quantity === false || $productPrice === false) {
                echo "Error: Invalid input values.";
                exit();
            }

            $productExists = false;
            foreach ($_SESSION['cart'] as &$cartItem) {
                if ($cartItem['id'] == $productID) {
                    $cartItem['quantity'] += $quantity;
                    $productExists = true;
                    break;
                }
            }

            if (!$productExists) {
                $_SESSION['cart'][] = [
                    'id'       => $productID,
                    'name'     => $productName,
                    'price'    => $productPrice,
                    'quantity' => $quantity
                ];
            }

            // For debugging, we can keep or remove the console log
            echo "<script>console.log('Product added to cart: " . json_encode($_SESSION['cart']) . "');</script>";
            echo "Product added to cart successfully!";
        } else {
            echo "Error: Missing product data.";
        }

    } elseif ($action === 'update') {
        if (isset($_POST['index'], $_POST['quantity'])) {
            $index    = filter_var($_POST['index'], FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

            if ($index === false || $quantity === false || !isset($_SESSION['cart'][$index])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Error: Invalid index or quantity."
                ]);
                exit();
            }

            $_SESSION['cart'][$index]['quantity'] = $quantity;
            // Return JSON so the JS can parse success
            echo json_encode([
                "success" => true,
                "message" => "Cart updated successfully!"
            ]);
        } else {
            echo "Error: Missing index or quantity.";
        }

    } elseif ($action === 'remove') {
        if (isset($_POST['index'])) {
            $index = filter_var($_POST['index'], FILTER_VALIDATE_INT);

            if ($index === false || !isset($_SESSION['cart'][$index])) {
                http_response_code(400);  // Invalid index
                echo "Error: Invalid cart index.";
                exit();
            }

            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);  // Re-index the cart

            http_response_code(200);  // Set response code to 200 (success)
            exit();  // No further output needed
        } else {
            http_response_code(400);  // Missing index
            echo "Error: Missing index for removal.";
        }

    } elseif ($action === 'reserve' && !empty($_SESSION['cart'])) {
        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo "<script>alert('Please log in first.'); window.location.href = 'login.php';</script>";
            exit();
        }

        $userID = (int)$_SESSION['user_id'];
        // 5. Sanitize date
        $reservationDate = htmlspecialchars($_POST['reservationDate'], ENT_QUOTES, 'UTF-8');

        // Fetch user info
        $sqlUser = "SELECT FirstName, Address FROM users WHERE userID = ?";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param('i', $userID);
        $stmtUser->execute();
        $stmtUser->bind_result($firstName, $address);
        $stmtUser->fetch();
        $stmtUser->close();

        $reservationName = $firstName;
        $delivery        = 1;
        $confirmed       = 0;
        $status          = 0;

        $sql = "INSERT INTO reservations (userID, ReservationName, Delivery, Confirmed, Status, Date, Address)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isiiiss', $userID, $reservationName, $delivery, $confirmed, $status, $reservationDate, $address);

        if ($stmt->execute()) {
            $reservationID = $stmt->insert_id;

            $insertedProducts = true;
            foreach ($_SESSION['cart'] as $cartItem) {
                $productID = (int)$cartItem['id'];
                $quantity  = (int)$cartItem['quantity'];
                $price     = (float)$cartItem['price'];

                $sqlProduct = "INSERT INTO reservedproducts (reservationID, productID, quantity, price)
                               VALUES (?, ?, ?, ?)";
                $stmtProduct = $conn->prepare($sqlProduct);
                $stmtProduct->bind_param('iiid', $reservationID, $productID, $quantity, $price);

                if (!$stmtProduct->execute()) {
                    $insertedProducts = false;
                }
            }

            if ($insertedProducts) {
                echo "<script>alert('Reservation Completed'); window.location.href = 'catalog.php';</script>";
                unset($_SESSION['cart']);
                exit();
            } else {
                echo "Error: Could not add products to reservedproducts.";
            }
        } else {
            echo "Error: Could not create reservation.";
        }
    } else {
        echo "Error: Invalid action or cart is empty.";
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fce4dea5; font-family: Arial, sans-serif; }
        .cart-container {
            background-color: #ffffff; 
            margin: 50px auto; 
            padding: 20px; 
            border-radius: 20px; 
            width: 60%; 
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }
        .cart-header { text-align: center; margin-bottom: 20px; }
        .cart-item {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 0; 
            border-bottom: 1px solid #ddd;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-footer {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-top: 20px;
        }
        .reserve-btn {
            background-color: #333; 
            color: #fff; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 10px;
        }
        .reserve-btn:hover { background-color: #555; }
        .remove-btn { color: red; cursor: pointer; }
        #cartSummary { font-size: 14px; }
    </style>
</head>
<body>
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

<div id="nav" class="navbar navbar-expand-lg bg-body-tertiary" style="background-color:#FCE4DE; font-family: 'Kameron', serif; color:black;">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="Catalog.php">Catalog</a>
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

<!-- Cart Display -->
<div class="cart-container">
    <h2 class="cart-header">Your Cart</h2>

    <?php if (count($_SESSION['cart']) > 0): ?>
        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
            <div class="cart-item" data-index="<?= $index; ?>">
                <div><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div>Price: $<?= number_format($item['price'], 2); ?></div>
                <div>
                    Quantity: 
                    <input 
                        type="number" 
                        value="<?= (int)$item['quantity']; ?>" 
                        min="1" 
                        onchange="updateQuantity(<?= $index; ?>, this.value)"
                    >
                </div>
                <div class="remove-btn" onclick="removeItem(<?= $index; ?>)">Remove</div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Your cart is empty!</p>
    <?php endif; ?>

    <div class="cart-footer">
        <div id="cartSummary">
            Total Items: <?= array_sum(array_column($_SESSION['cart'], 'quantity')); ?>
            Total Price: $
            <?= number_format(array_sum(array_map(function($item) {
                return $item['price'] * $item['quantity'];
            }, $_SESSION['cart'])), 2); ?>
        </div>
        <!-- Reserve Form -->
        <form method="POST">
            <!-- 6. Add hidden CSRF token to form -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="reserve">
            <input type="date" name="reservationDate" id="reservationDate" min="<?= date('Y-m-d'); ?>" required>
            <button type="submit" class="reserve-btn">RESERVE</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function updateQuantity(index, quantity) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // 7. Include CSRF token in the request
        xhr.send(`csrf_token=<?= $_SESSION['csrf_token']; ?>&action=update&index=${index}&quantity=${quantity}`);

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    let response = JSON.parse(xhr.responseText);
                    Swal.fire(response.message);
                    if (response.success) {
                        location.reload();
                    }
                } catch (e) {
                    Swal.fire("Error", xhr.responseText, "error");
                }
            }
        };
    }

    function removeItem(index) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Include CSRF token as well
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                location.reload();  // Refresh page after removing item
            }
        };
        xhr.send(`csrf_token=<?= $_SESSION['csrf_token']; ?>&action=remove&index=${index}`);
    }
</script>

<footer style="background-color: #333; color: #fff; padding: 20px; text-align: center; position: absolute; bottom: 0px; width: 100%; font-family: Nanum Myeongjo, serif;">
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
