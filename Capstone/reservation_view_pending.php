<?php
session_start();
session_regenerate_id(true); // Mitigate session fixation

/************************************************************
 * 1. Admin check
 ************************************************************/
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: restricted.php');
    exit();
}

/************************************************************
 * 2. Generate CSRF token if not set
 ************************************************************/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include your reservations model
include __DIR__ . '/model/model_reservations.php';

// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$error          = "";
$reservationName= '';
$delivery       = '';
$confirmed      = '';
$address        = '';
$date           = '';
$productID      = '';
$status         = '';

// Determine action
$action = filter_input(INPUT_GET, 'action') ?? 'add';
$reservationID = filter_input(INPUT_GET, 'reservationID', FILTER_VALIDATE_INT);

// If editing, load reservation from DB
if ($action === 'edit' && $reservationID) {
    // getReservations() presumably returns an array of arrays. If it returns only one row, 
    // you may need $reservation[0]['ReservationName']. Adjust if needed.
    $reservationArr = getReservations($reservationID);
    // If the function returns multiple rows, we use the first row:
    if (isset($reservationArr[0])) {
        $reservation = $reservationArr[0];
        $reservationName = $reservation['ReservationName'] ?? "";
        $delivery       = $reservation['Delivery'] ?? "";
        $confirmed      = isset($reservation['Confirmed']) ? ($reservation['Confirmed'] ? 'yes' : 'no') : "";
        $address        = $reservation['Address'] ?? "";
        $date           = $reservation['Date'] ?? "";
        $productID      = $reservation['ProductID'] ?? "";
        $status         = $reservation['Status'] ?? "";
    }
}

/************************************************************
 * 3. Handle Form Submissions
 ************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (a) Check CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Error: Invalid CSRF token.");
    }

    // (b) Sanitize user input
    $reservationName = htmlspecialchars(trim($_POST['reservationName'] ?? ''), ENT_QUOTES, 'UTF-8');
    $delivery       = htmlspecialchars(trim($_POST['delivery'] ?? ''), ENT_QUOTES, 'UTF-8');
    $confirmedRaw   = trim($_POST['confirmed'] ?? '');
    $confirmed      = ($confirmedRaw === 'yes' || $confirmedRaw == '1') ? 'yes' : 'no';
    $address        = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $date           = trim($_POST['date'] ?? '');
    $productID      = htmlspecialchars(trim($_POST['productID'] ?? ''), ENT_QUOTES, 'UTF-8');
    $status         = htmlspecialchars(trim($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8');

    // (c) Validate
    if ($reservationName === "") $error .= "<li>Please provide reservation name</li>";
    if ($delivery       === "") $error .= "<li>Please provide delivery info</li>";
    if ($confirmed      === "") $error .= "<li>Please provide confirmation status</li>";
    if ($address        === "") $error .= "<li>Please provide address</li>";
    if ($date          === "")  $error .= "<li>Please provide date</li>";
    if ($productID     === "")  $error .= "<li>Please provide product ID</li>";
    if ($status        === "")  $error .= "<li>Please provide status</li>";

    // (d) If user clicked "Delete Reservation"
    if (isset($_POST['deleteReservation']) && $action === 'edit' && $reservationID) {
        deleteReservation($reservationID);
        header('Location: reservation_view_pending.php');
        exit;
    }

    // (e) If storing/updating and no errors
    if (isset($_POST['storeReservation']) && $error === "") {
        // If adding new
        if ($action === 'add') {
            addReservation($reservationName, $delivery, $confirmed, $address, $date, $productID, $status);
            header('Location: reservation_view_pending.php');
            exit;
        }
        // If editing existing
        elseif ($action === 'edit' && $reservationID) {
            // NOTE: This calls updateReservation($reservationID, ...) 
            // but in your code you pass the userID, not the reservationID. 
            // Possibly a mismatch in your model. Adjust if needed.
            updateReservation($reservationID, $reservationName, $delivery, $confirmed, $address, $date, $productID, $status);
            header('Location: reservation_view_pending.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations (<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>)</title>
    <style>
       .wrapper {
            display: grid;
            grid-template-columns: 180px 400px;
        }
        .label {
            text-align: right;
            padding-right: 10px;
            margin-bottom: 5px;
        }
        label {
           font-weight: bold;
        }
        input[type=text],
        input[type=date] {
            width: 200px;
        }
        .error {
            color: red;
        }
        div {
            margin-top: 5px;
        }
    </style>
</head>
<body>

<?php
    // If user just added a new reservation with no error
    // (But your code block here might be redundant. 
    //  We left it mostly intact in case you rely on it.)
    /*
    if (isset($_POST['storeReservation']) && $error === "" && $action === 'add') {
        $result = addReservation(...);
        echo "<h2>New reservation $reservationName was added</h2>";
        echo '<a href="admin_dashboard.php">View All Reservations</a>';
        exit;
    }
    */
?>

<h2><?= ucfirst($action); ?> Reservation</h2>

<?php if ($error !== ""): ?>
    <div class="error">
        <p>Please fix the following and resubmit:</p>
        <ul><?= $error; ?></ul>
    </div>
<?php endif; ?>

<form name="reservation" method="post">
    <!-- CSRF token for form submission -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="wrapper">
        <div class="label">
            <label>Reservation Name:</label>
        </div>
        <div>
            <input 
                type="text" 
                name="reservationName" 
                value="<?= htmlspecialchars($reservationName, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div class="label">
            <label>Delivery:</label>
        </div>
        <div>
            <input 
                type="text" 
                name="delivery" 
                value="<?= htmlspecialchars($delivery, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div class="label">
            <label>Confirmed:</label>
        </div>
        <div>
            <select name="confirmed">
                <option value="yes" <?= ($confirmed === 'yes') ? 'selected' : ''; ?>>Yes</option>
                <option value="no"  <?= ($confirmed === 'no')  ? 'selected' : ''; ?>>No</option>
            </select>
        </div>

        <div class="label">
            <label>Address:</label>
        </div>
        <div>
            <input 
                type="text" 
                name="address" 
                value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div class="label">
            <label>Date:</label>
        </div>
        <div>
            <input 
                type="date" 
                name="date" 
                value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div class="label">
            <label>Product ID:</label>
        </div>
        <div>
            <input 
                type="text" 
                name="productID" 
                value="<?= htmlspecialchars($productID, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div class="label">
            <label>Status:</label>
        </div>
        <div>
            <input 
                type="text" 
                name="status" 
                value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
            />
        </div>

        <div>&nbsp;</div>
        <div>
            <input 
                class="<?= ($action === 'edit') ? 'btn btn-info' : 'btn btn-success'; ?>" 
                type="submit" 
                name="storeReservation" 
                value="<?= ucfirst($action); ?> Reservation Information"
            />
        </div>

        <div>&nbsp;</div>
        <div>
            <?php if ($action === 'edit'): ?>
                <input 
                    class="btn btn-danger" 
                    type="submit" 
                    name="deleteReservation" 
                    value="DELETE Reservation" 
                    onclick="return confirm('Are you sure you want to delete this reservation?');"
                />
            <?php endif; ?>
        </div>

        <a href="admin_dashboard.php">View All Reservations</a>
    </div>
</form>

</body>
</html>
