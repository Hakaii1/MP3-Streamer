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
    // Update: Allow TTS files if prefixed with "tts/"

    if (strpos($file, 'tts/') === 0) {
        $cleanName = basename($file); // "file.mp3"
        // Ensure "tts/" is preserved but no traversal
        // The input logic is a bit odd here because "$file" was passed.
        // If passed "tts/foo.mp3", basename is "foo.mp3".

        $targetPath = __DIR__ . '/tts/' . $cleanName;
    } else {
        $filename = basename($file);
        $targetPath = $targetDir . $filename;
    }

    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    if ($ext !== 'mp3') {
        echo json_encode(['ok' => false, 'error' => 'Only MP3 files can be deleted']);
        exit;
    }

    // Verify it's within allowed dirs (Realpath check)
    $realPath = realpath($targetPath);
    $mp3Root = realpath($targetDir);
    $ttsRoot = realpath(__DIR__ . '/tts');

    $valid = false;
    if ($realPath && $mp3Root && strpos($realPath, $mp3Root) === 0) $valid = true;
    if ($realPath && $ttsRoot && strpos($realPath, $ttsRoot) === 0) $valid = true;

    if ($valid && file_exists($realPath)) {
        if (unlink($realPath)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Permission denied']);
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'File not found or invalid path']);
    }
} else {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
}
