<?php
// api_upload.php
header('Content-Type: application/json');

$targetDir = __DIR__ . '/mp3/';
$playlistFile = __DIR__ . '/playlists.json';

// Ensure the mp3 directory exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['ok' => false, 'error' => 'No file provided.']);
    exit;
}

$file = $_FILES['file'];
// Sanitize filename
$fileName = preg_replace('/[^a-zA-Z0-9_\-\.\(\)\s]/', '', basename($file['name']));
$targetPath = $targetDir . $fileName;
$ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

// 1. Basic Validation
if ($ext !== 'mp3') {
    echo json_encode(['ok' => false, 'error' => 'Only .mp3 files are allowed.']);
    exit;
}

// 2. Check for errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

// 3. Move File to MP3 Folder (Storage)
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    
    // 4. If a playlist was targeted, add the link
    $targetPlaylist = $_POST['target_playlist'] ?? '';
    if ($targetPlaylist && $targetPlaylist !== 'all' && file_exists($playlistFile)) {
        $playlists = json_decode(file_get_contents($playlistFile), true) ?? [];
        if (isset($playlists[$targetPlaylist])) {
            // Only add if not already present
            if (!in_array($fileName, $playlists[$targetPlaylist])) {
                $playlists[$targetPlaylist][] = $fileName;
                sort($playlists[$targetPlaylist]);
                file_put_contents($playlistFile, json_encode($playlists, JSON_PRETTY_PRINT));
            }
        }
    }

    echo json_encode(['ok' => true, 'file' => $fileName]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to save file. Check folder permissions.']);
}
?>