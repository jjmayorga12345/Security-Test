<?php
session_start();

// 1. Optionally regenerate the session ID before destroying
//    to prevent session fixation after logout
session_regenerate_id(true);

// 2. Clear all session variables
session_unset();

// 3. Destroy the session on the server
session_destroy();

// 4. (Optional) Expire the session cookie on the client
//    This helps ensure the browser discards the old session ID
$params = session_get_cookie_params();
setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"] ?? false,
    $params["httponly"] ?? false
);

// 5. Redirect user to the login or home page
header("Location: login.php");
exit();
