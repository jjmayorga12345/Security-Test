<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation attacks

/************************************************************
 * (OPTIONAL) CSRF PROTECTION
 * If you want to protect all cart modifications from CSRF,
 * ensure your front-end includes the token in the POST.
 ************************************************************/
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    http_response_code(400);
    echo 'Invalid CSRF token.';
    exit();
}

/************************************************************
 * 1. Check if user is logged in
 ************************************************************/
if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    echo 'error';
    exit();
}

/************************************************************
 * 2. Validate POST inputs
 ************************************************************/
if (
    !isset($_POST['productName']) ||
    !isset($_POST['quantity'])    ||
    !isset($_POST['index'])
) {
    http_response_code(400); // Bad Request
    echo 'error';
    exit();
}

$productName = trim($_POST['productName']);
$quantity    = (int) $_POST['quantity'];
$index       = (int) $_POST['index'];

/************************************************************
 * 3. Check if the cart index is valid
 ************************************************************/
if (!isset($_SESSION['cart'][$index])) {
    http_response_code(400);
    echo 'Invalid cart index.';
    exit();
}

/************************************************************
 * 4. Use a prepared statement to query product availability
 ************************************************************/
require_once 'model/db.php';
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500); // Internal Server Error
    echo 'error';
    exit();
}

$stmt = $conn->prepare("SELECT Availability FROM products WHERE Name = ?");
$stmt->bind_param("s", $productName);
$stmt->execute();
$stmt->bind_result($availableQuantity);
$stmt->fetch();
$stmt->close();
$conn->close();

/************************************************************
 * 5. Compare requested quantity with availability
 ************************************************************/
if ($quantity > $availableQuantity) {
    // Return the partial error string used by your front-end
    echo 'not_available|' . $availableQuantity;
    exit();
}

/************************************************************
 * 6. Update the session cart quantity
 ************************************************************/
$_SESSION['cart'][$index]['quantity'] = $quantity;

/************************************************************
 * 7. Respond with success
 ************************************************************/
echo 'success';
exit();
