<?php
// Prevent direct access to this file
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
    die("Direct access is not allowed.");
}

/**
 * Establishes a secure database connection using credentials from dbconfig.ini
 * 
 * @return mysqli $conn
 */
function getDBConnection() {
    // Path to the INI file
    $configFile = __DIR__ . "/dbconfig.ini";

    // Ensure the config file exists
    if (!file_exists($configFile)) {
        die("Error: dbconfig.ini file is missing.");
    }

    // Parse the INI file
    $config = parse_ini_file($configFile);
    if (!$config) {
        die("Error: Could not parse dbconfig.ini file.");
    }

    // Ensure required keys exist
    $requiredKeys = ['host', 'port', 'dbname', 'username', 'password'];
    foreach ($requiredKeys as $key) {
        if (!isset($config[$key])) {
            die("Error: Missing '{$key}' in dbconfig.ini.");
        }
    }

    // Assign variables from INI
    $host     = $config['host'];
    $port     = $config['port'];
    $dbname   = $config['dbname'];
    $username = $config['username'];
    $password = $config['password'];

    // Enable strict error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Create connection with error handling
    try {
        $conn = new mysqli($host, $username, $password, $dbname, $port);
        // Set charset to prevent encoding issues
        $conn->set_charset("utf8mb4");
    } catch (Exception $e) {
        // Log the error for debugging (not visible to users)
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed.");
    }

    return $conn;
}
?>
