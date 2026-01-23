<?php
// Enable error reporting in database connection file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Server connection details
// Added tcp: prefix and port 1433 to prevent Mac Driver Crashes
$serverName = "10.2.0.9";

$dataDbConfig = [
    'database' => 'LRNPH_OJT',
    'username' => 'sa',
    'password' => 'S3rverDB02lrn25',
    "TrustServerCertificate" => 1
];

// Database configurations array
$databases = [
    'data' => $dataDbConfig['database']      // For application data (uniform_headers, etc.)
];

// Create connections for both databases with their respective credentials
$connections = [];
$dbConfigs = [
    'data' => $dataDbConfig
];

foreach ($dbConfigs as $key => $config) {
    try {
        $connections[$key] = new PDO(
            "sqlsrv:Server=tcp:$serverName,1433;Database=" . $config['database'] . ";TrustServerCertificate=1",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        if (defined('API_MODE') && API_MODE) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        } else {
            die(display_unavailable());
        }
    }
}

// Set default connection (application data)
$conn = $connections['data'];

// Get auth connection for login/authentication
function getAuthConnection()
{
    global $connections;
    return $connections['auth'];
}

// Get data connection for application operations
function getDataConnection()
{
    global $connections;
    return $connections['data'];
}

// Centralized database query function with error handling
function safeQuery($sql, $params = [], $useAuthDb = false)
{
    global $authDbConfig, $dataDbConfig;
    $dbConn = $useAuthDb ? getAuthConnection() : getDataConnection();
    $dbName = $useAuthDb ? $authDbConfig['database'] . ' (Auth)' : $dataDbConfig['database'] . ' (Data)';

    try {
        $stmt = $dbConn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("Database Query Error [$dbName]: " . $e->getMessage() . "<br>SQL: " . $sql);
    } catch (Exception $e) {
        die("Database Error [$dbName]: " . $e->getMessage());
    }
}

// Helper function for simple queries that return single values
function safeQuerySingle($sql, $params = [], $useAuthDb = false)
{
    $stmt = safeQuery($sql, $params, $useAuthDb);
    return $stmt->fetchColumn();
}

// Helper function for queries that return multiple rows
function safeQueryAll($sql, $params = [], $useAuthDb = false)
{
    $stmt = safeQuery($sql, $params, $useAuthDb);
    return $stmt->fetchAll();
}

// Helper function for queries that return single row
function safeQueryRow($sql, $params = [], $useAuthDb = false)
{
    $stmt = safeQuery($sql, $params, $useAuthDb);
    return $stmt->fetch();
}
function display_unavailable()
{
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Service Unavailable</title>
        <style>
            body {
                background-color: #fce5f0; /* Matches your app theme */
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .error-card {
                background: white;
                padding: 40px;
                border-radius: 24px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                text-align: center;
                max-width: 400px;
                border: 1px solid rgba(255, 255, 255, 0.8);
            }
            .icon {
                font-size: 60px;
                margin-bottom: 20px;
            }
            h2 {
                color: #333;
                margin: 0 0 10px 0;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .btn {
                background: #ff3385;
                color: white;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 12px;
                font-weight: 600;
                display: inline-block;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #e01b6b;
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="icon">🔌</div>
            <h2>System Unavailable</h2>
            <p>We are currently unable to connect to the database. Please check your internet connection or try again later.</p>
            <a href="javascript:location.reload()" class="btn">Try Again</a>
        </div>
    </body>
    </html>
    ');
}
