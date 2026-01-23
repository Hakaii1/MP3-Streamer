<?php

/**
 * API for Managing Authorized Users
 * Uses mp3_authorized table in LRNPH_OJT database
 */

session_start();

// Check if user is logged in and is IT
if (!isset($_SESSION['username']) || $_SESSION['department'] !== 'Information Technology Department - LRN') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

define('API_MODE', true);
require_once __DIR__ . '/conn/conn.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
switch ($action) {
    case 'list':
        // Get all authorized users from mp3_authorized table
        $sql = "SELECT 
                    BiometricsID,
                    fullname
                FROM mp3_authorized
                ORDER BY fullname";

        try {
            $stmt = $conn->query($sql);
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'search':
        // Search for users from lrn_master_list
        $query = $_GET['q'] ?? '';

        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'users' => []]);
            exit();
        }

        $sql = "SELECT TOP 20
                    m.BiometricsID,
                    CONCAT(m.FirstName, ' ', ISNULL(m.MiddleName + ' ', ''), m.LastName) as fullname,
                    CASE WHEN a.BiometricsID IS NOT NULL THEN 1 ELSE 0 END as is_authorized
                FROM LRNPH_E.dbo.lrn_master_list m
                LEFT JOIN mp3_authorized a ON m.BiometricsID = a.BiometricsID
                WHERE m.BiometricsID LIKE ? 
                   OR m.FirstName LIKE ?
                   OR m.LastName LIKE ?
                ORDER BY m.LastName, m.FirstName";

        $searchTerm = "%$query%";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'lookup':
        // Lookup a user by BiometricsID
        $biometricsId = $_GET['id'] ?? '';

        if (empty($biometricsId)) {
            echo json_encode(['error' => 'BiometricsID is required']);
            exit();
        }

        $sql = "SELECT BiometricsID, CONCAT(FirstName, ' ', ISNULL(MiddleName + ' ', ''), LastName) as fullname FROM LRNPH_E.dbo.lrn_master_list WHERE BiometricsID = ?";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$biometricsId]);
            $user = $stmt->fetch();

            if ($user) {
                // Check if already authorized
                $authSql = "SELECT BiometricsID FROM mp3_authorized WHERE BiometricsID = ?";
                $authStmt = $conn->prepare($authSql);
                $authStmt->execute([$biometricsId]);
                $isAuthorized = $authStmt->fetch() ? true : false;

                echo json_encode([
                    'success' => true,
                    'user' => $user,
                    'is_authorized' => $isAuthorized
                ]);
            } else {
                echo json_encode(['error' => 'User not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'add':
        // Add a user to authorized list
        $biometricsId = $_POST['biometrics_id'] ?? '';

        if (empty($biometricsId)) {
            echo json_encode(['error' => 'BiometricsID is required']);
            exit();
        }

        // Get fullname from master list
        $checkSql = "SELECT BiometricsID, CONCAT(FirstName, ' ', ISNULL(MiddleName + ' ', ''), LastName) as fullname FROM LRNPH_E.dbo.lrn_master_list WHERE BiometricsID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$biometricsId]);
        $userRow = $checkStmt->fetch();

        if (!$userRow) {
            echo json_encode(['error' => 'User not found in master list']);
            exit();
        }

        $fullname = $userRow['fullname'];

        // Check if already authorized
        $existsSql = "SELECT BiometricsID FROM mp3_authorized WHERE BiometricsID = ?";
        $existsStmt = $conn->prepare($existsSql);
        $existsStmt->execute([$biometricsId]);

        if ($existsStmt->fetch()) {
            echo json_encode(['error' => 'User is already authorized']);
            exit();
        }

        // Add to authorized list with fullname
        $sql = "INSERT INTO mp3_authorized (BiometricsID, fullname) VALUES (?, ?)";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$biometricsId, $fullname]);
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to add user: ' . $e->getMessage()]);
        }
        break;

    case 'remove':
        // Remove a user from authorized list
        $biometricsId = $_POST['biometrics_id'] ?? '';

        if (empty($biometricsId)) {
            echo json_encode(['error' => 'BiometricsID is required']);
            exit();
        }

        $sql = "DELETE FROM mp3_authorized WHERE BiometricsID = ?";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$biometricsId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'User removed successfully']);
            } else {
                echo json_encode(['error' => 'User not found in authorized list']);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to remove user: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
