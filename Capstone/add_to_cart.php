<?php
// Prevent direct access to this file
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
    die("Direct access is not allowed.");
}

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    echo "User not logged in!";
    exit();
}

// Sanitize and validate input
$productName = isset($_POST['productName']) ? trim($_POST['productName']) : '';
$price       = isset($_POST['price']) ? $_POST['price'] : '';

// Basic checks for product name & price
if ($productName === '' || !is_numeric($price)) {
    echo "Invalid product data.";
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Search for product in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['name'] === $productName) {
        $item['quantity'] += 1;
        $found = true;
        break;
    }
}

// If product not found, add new item
if (!$found) {
    $_SESSION['cart'][] = [
        'name'     => htmlspecialchars($productName), // XSS protection
        'price'    => (float)$price,                  // ensure numeric
        'quantity' => 1
    ];
}

// Indicate success
echo "success";
?>
