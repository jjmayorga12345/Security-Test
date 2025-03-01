<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation on each request

/************************************************************
 * 1. Check if user is logged in
 ************************************************************/
if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    echo 'error';
    exit();
}

/************************************************************
 * 2. Check CSRF token
 *    Make sure your front-end (JavaScript or form) is 
 *    sending this token along with POST requests.
 ************************************************************/
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    http_response_code(400); // Bad request
    echo 'Invalid CSRF token.';
    exit();
}

/************************************************************
 * 3. Validate "index" input
 ************************************************************/
if (!isset($_POST['index'])) {
    http_response_code(400); // Bad request
    echo 'error';
    exit();
}

$index = (int)$_POST['index'];

/************************************************************
 * 4. Remove item from cart if it exists
 ************************************************************/
if (isset($_SESSION['cart'][$index])) {
    unset($_SESSION['cart'][$index]);
    // Re-index the array so there's no gap
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    echo 'success';
    exit();
}

/************************************************************
 * 5. If we reach here, item wasn't found or invalid
 ************************************************************/
http_response_code(400);
echo 'error';
exit();
