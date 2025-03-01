<?php
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
    die("Direct access is not allowed.");
}

function login($email, $password) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }

    $stmt = $conn->prepare("SELECT UserID, Email, Password, FirstName, PhoneNumber, Address, ZipCode, State, isAdmin
                            FROM users
                            WHERE Email = ?");
    if (!$stmt) {
        die("Statement preparation failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userID, $dbEmail, $dbPassword, $firstName, $phoneNumber, $address, $zipCode, $state, $isAdmin);
        $stmt->fetch();

        // Verify hashed password
        if (password_verify($password, $dbPassword)) {
            $userInfo = [
                'userID'      => $userID,
                'email'       => $dbEmail,
                'firstName'   => $firstName,
                'phoneNumber' => $phoneNumber,
                'address'     => $address,
                'zipCode'     => $zipCode,
                'state'       => $state,
                'isAdmin'     => $isAdmin
            ];
            $stmt->close();
            $conn->close();
            return $userInfo;
        }
    }

    $stmt->close();
    $conn->close();
    return false;
}

function isAdmin($userID) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }
    $stmt = $conn->prepare("SELECT isAdmin FROM users WHERE UserID = ?");
    if (!$stmt) {
        die("Statement preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($isAdmin);
        $stmt->fetch();
        $stmt->close();
        $conn->close();
        return (bool)$isAdmin;
    }
    $stmt->close();
    $conn->close();
    return false;
}

function getReservation($id) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Database connection failed.");
    }
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return [];
}

// EXAMPLE: addReservation, updateReservation, deleteReservation remain as they are (PDO)...

// GET USER
function getUser($userID) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }

    $stmt = $conn->prepare("SELECT UserID, Email, Password, FirstName, PhoneNumber, Address, ZipCode, State, isAdmin
                            FROM users
                            WHERE UserID = ?");
    if (!$stmt) {
        die("Statement preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }
    $stmt->close();
    $conn->close();
    return null;
}

// ADD USER
function addUser($firstName, $email, $password, $phoneNumber, $address, $zipCode, $state) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }

    $isAdmin = 0;

    // Hash the raw password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (FirstName, Email, Password, PhoneNumber, Address, ZipCode, State, isAdmin)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssssssi", $firstName, $email, $hashedPassword, $phoneNumber, $address, $zipCode, $state, $isAdmin);

    if ($stmt->execute()) {
        echo "New user added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}

// DELETE USER
function deleteUser($userID) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE UserID = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userID);

    if ($stmt->execute()) {
        echo "User deleted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}

// UPDATE USER
function updateUser($userID, $firstName, $email, $password, $phoneNumber, $address, $zipCode, $state) {
    require_once 'model/db.php';
    $conn = getDBConnection();
    if (!$conn) {
        die("Connection failed: no valid database connection.");
    }

    // If new password was provided, re-hash
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users
                                SET FirstName = ?, Email = ?, Password = ?, PhoneNumber = ?, Address = ?, ZipCode = ?, State = ?
                                WHERE UserID = ?");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("sssssssi", $firstName, $email, $hashedPassword, $phoneNumber, $address, $zipCode, $state, $userID);
    } else {
        // Keep old password
        $stmt = $conn->prepare("UPDATE users
                                SET FirstName = ?, Email = ?, PhoneNumber = ?, Address = ?, ZipCode = ?, State = ?
                                WHERE UserID = ?");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssssi", $firstName, $email, $phoneNumber, $address, $zipCode, $state, $userID);
    }

    if ($stmt->execute()) {
        echo "User updated successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
