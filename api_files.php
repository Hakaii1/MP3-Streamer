<?php
// api_files.php
header('Content-Type: application/json');

$targetDir = __DIR__ . '/mp3/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid method']);
    exit;
}

$action = $_POST['action'] ?? '';
$file = $_POST['file'] ?? '';

if ($action === 'delete' && !empty($file)) {
    // SECURITY: strictly limit to MP3 directory and extension
    $filename = basename($file);
    $targetPath = $targetDir . $filename;
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    if ($ext !== 'mp3') {
        echo json_encode(['ok' => false, 'error' => 'Only MP3 files can be deleted']);
        exit;
    }

    if (file_exists($targetPath)) {
        if (unlink($targetPath)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Permission denied']);
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'File not found']);
    }
} else {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
}
?>