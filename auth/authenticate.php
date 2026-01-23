<?php
// Temporarily redirect to dashboard for testing purposes
// header("Location: ../pages/overview.php");
// exit();

session_start();

/**
 * Function to handle unauthorized access
 * Kills the session and redirects to login with error modal
 */
function die_with_unauthorized_view()
{
    // 1. Clear the session so they don't remain "half-logged-in"
    session_unset();
    session_destroy();

    // 2. Redirect to login page with unauthorized error
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Initialize Variables
$username = $password = "";
$username_error = $password_error = "";
$login_error = "";

// Connect to DB (Seperate from conn.php)
$auth_server_name = "10.2.0.9";
$connectionOptions = [
    "UID" => "sa",
    "PWD" => "S3rverDB02lrn25",
    "Database" => "LRNPH",
    "TrustServerCertificate" => 1
];


// Establish the connection to the SQL Server
$auth_conn = sqlsrv_connect($auth_server_name, $connectionOptions);

// Check if the connection was successful
if (!$auth_conn) {
    // Ideally, make a nice error page for this too, but die is okay for DB connection errors
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Handle POST request for login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    if (empty($_POST["username"])) {
        $username_error = "Username is required";
    } else {
        $username = $_POST["username"];
    }

    if (empty($_POST["password"])) {
        $password_error = "Password is required";
    } else {
        $password = $_POST["password"];
    }

    // If no errors, proceed with the login attempt
    if (empty($username_error) && empty($password_error)) {
        // Prepare the SQL query to fetch the user from the database
        $query = "SELECT 
                lu.username, 
                lu.password, 
                lu.role, 
                lu.empcode, 
                lu.login_token,
                ml.department,
                ml.FirstName + ' ' + ml.LastName as fullname 
                FROM dbo.lrnph_users lu 
                LEFT JOIN LRNPH_E.dbo.lrn_master_list ml 
                    on lu.username = ml.BiometricsID
                WHERE lu.username = ?";
        $params = array($username);

        // Execute the query
        $stmt = sqlsrv_query($auth_conn, $query, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Check if user exists
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Verify the password
            if (password_verify($password, $row['password'])) {
                // Password is correct, start session and store user data
                $_SESSION['username'] = $row['username'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['empcode'] = $row['empcode'];
                $_SESSION['department'] = $row['department'];

                // IT department has full access
                if ($_SESSION['department'] === 'Information Technology Department - LRN') {
                    header("Location: ../host.php");
                    exit();
                } else {
                    // Check if user is in mp3_authorized table
                    $authCheckSql = "SELECT BiometricsID FROM LRNPH_OJT.dbo.mp3_authorized WHERE BiometricsID = ?";
                    $authCheckStmt = sqlsrv_query($auth_conn, $authCheckSql, array($row['username']));

                    if ($authCheckStmt && sqlsrv_fetch_array($authCheckStmt, SQLSRV_FETCH_ASSOC)) {
                        // User is authorized
                        header("Location: ../host.php");
                        exit();
                    } else {
                        // User is not authorized
                        die_with_unauthorized_view();
                    }
                }

            } else {
                header("Location: ../login.php?error=invalid");
                exit();
            }
        } else {
            header("Location: ../login.php?error=invalid");
            exit();
        }

        // Close the statement
        sqlsrv_free_stmt($stmt);
    }
}

// Close the connection
sqlsrv_close($auth_conn);
?>