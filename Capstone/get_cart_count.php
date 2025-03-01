<?php
session_start();
// Mitigate session fixation
session_regenerate_id(true);

// If user not logged in, return 0 or redirect
if (!isset($_SESSION['user'])) {
    echo 0;
    exit();
}

// Initialize cart count
$cartCount = 0;

// If cart exists in session, sum up the quantities
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // Cast to int to avoid unexpected input
        $cartCount += (int)$item['quantity'];
    }
}

// Output the final count
echo $cartCount;
?>
