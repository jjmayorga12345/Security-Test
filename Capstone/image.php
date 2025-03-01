<?php
session_start();
// Mitigate session fixation attacks
session_regenerate_id(true);

require_once 'model/db.php';

// 1. Validate the productID from GET
$productID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productID > 0) {
    $conn = getDBConnection();

    // Use a prepared statement to prevent injection
    $sql = "SELECT Image FROM products WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $stmt->bind_result($imageBlob);

    // 2. Check if we actually got a row
    if ($stmt->fetch() && $imageBlob) {
        // Output the image as PNG (assuming all stored images are PNG)
        header("Content-Type: image/png");
        echo $imageBlob;
    } else {
        // No valid image found, use fallback
        header("Content-Type: image/png");
        readfile("images/no-image.png");
    }

    $stmt->close();
    $conn->close();
} else {
    // If productID is invalid, return fallback
    header("Content-Type: image/png");
    readfile("images/no-image.png");
}
?>
