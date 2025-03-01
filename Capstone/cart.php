<?php
session_start();

// Mitigate session fixation attacks
session_regenerate_id(true);

// If there's no CSRF token yet, generate one
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'model/db.php';
$conn = getDBConnection();

// Load cart from session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #FAF3EE; 
            font-family: Arial, sans-serif; 
        }
        .cart-container { 
            background-color: #ffffff; 
            margin: 50px auto; 
            padding: 20px; 
            border-radius: 20px; 
            width: 60%; 
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1); 
        }
        .cart-header { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        .cart-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 0; 
            border-bottom: 1px solid #ddd; 
        }
        .cart-item:last-child { 
            border-bottom: none; 
        }
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
        .reserve-btn:hover { 
            background-color: #555; 
        }
        .remove-btn { 
            color: red; 
            cursor: pointer; 
        }
    </style>
</head>
<body>

<div class="cart-container">
    <h2 class="cart-header">Your Cart</h2>

    <?php if (count($cart) > 0): ?>
        <?php foreach ($cart as $index => $item): ?>
            <div class="cart-item" data-index="<?= $index; ?>">
                <!-- 1. Escape the product name -->
                <div><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <!-- 2. Format the price safely -->
                <div>Price: $<?= number_format((float)$item['price'], 2); ?></div>
                <div>
                    Quantity: 
                    <input 
                        type="number" 
                        value="<?= (int)$item['quantity']; ?>" 
                        min="1" 
                        onchange="updateQuantity('<?= addslashes($item['name']); ?>', this.value, <?= (int)$index; ?>)"
                    >
                </div>
                <div class="remove-btn" onclick="removeItem(<?= (int)$index; ?>)">Remove</div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Your cart is empty!</p>
    <?php endif; ?>

    <div class="cart-footer">
        <div id="cartSummary">
            <!-- 3. Summaries also sanitized or cast to numeric -->
            Total Items: <?= array_sum(array_column($cart, 'quantity')); ?>
            Total Price: $
            <?= number_format(array_sum(array_map(function($item) {
                return (float)$item['price'] * (int)$item['quantity'];
            }, $cart)), 2); ?>
        </div>
        <button class="reserve-btn" onclick="reserveItems()">RESERVE</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 4. Retrieve the CSRF token from the session (passed from PHP)
    const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";

    // Called when user changes quantity
    function updateQuantity(productName, quantity, index) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "update_cart_quantity.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // 5. Include the CSRF token
        let data = 
            "csrf_token=" + encodeURIComponent(csrfToken) +
            "&productName=" + encodeURIComponent(productName) +
            "&quantity=" + encodeURIComponent(quantity) +
            "&index=" + encodeURIComponent(index);

        xhr.send(data);

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // The response might be 'not_available|<qty>' or something else
                let response = xhr.responseText.split('|');
                if (response[0] === 'not_available') {
                    let availableQuantity = response[1];
                    alert('Not enough items available. Only ' + availableQuantity + ' available.');
                    document.querySelector(`.cart-item[data-index="${index}"] input[type="number"]`).value = availableQuantity;
                } else {
                    location.reload();
                }
            }
        };
    }

    // Called when user clicks "Remove"
    function removeItem(index) {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "remove_from_cart.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // 6. Include the CSRF token
        xhr.send("csrf_token=" + encodeURIComponent(csrfToken) + "&index=" + encodeURIComponent(index));

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                location.reload();
            }
        };
    }

    // Called when user clicks "RESERVE"
    function reserveItems() {
        // 7. Possibly do a real "reserve" action with a new POST to cart_system or something else
        alert('Reservation confirmed!');
        // If your actual logic is to unset the cart and go home, that's fine
        <?php unset($_SESSION['cart']); ?>
        window.location.href = 'home.php';
    }
</script>
</body>
</html>
