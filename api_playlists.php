<?php
// api_playlists.php
$playlistFile = __DIR__ . '/playlists.json';

header('Content-Type: application/json');

function getPlaylists($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

// GET: Fetch all playlists
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(getPlaylists($playlistFile));
    exit;
}

// POST: Modify Playlists
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $current = getPlaylists($playlistFile);

    // Create or Overwrite Playlist
    if ($action === 'save') {
        $name = $data['name'];
        $songs = $data['songs']; // Array of filenames
        if ($name && !empty($songs)) {
            $current[$name] = $songs;
            file_put_contents($playlistFile, json_encode($current, JSON_PRETTY_PRINT));
            echo json_encode(['ok' => true]);
        }
    } 
    // Add existing tracks to a playlist
    elseif ($action === 'add_tracks') {
        $name = $data['name'] ?? '';
        $songsToAdd = $data['songs'] ?? [];
        
        if (isset($current[$name]) && is_array($songsToAdd)) {
            $existing = $current[$name];
            // Merge and unique
            $merged = array_unique(array_merge($existing, $songsToAdd));
            sort($merged); // Keep tidy
            $current[$name] = array_values($merged);
            
            file_put_contents($playlistFile, json_encode($current, JSON_PRETTY_PRINT));
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Playlist not found']);
        }
    }
    // Remove single track from playlist (Link deletion only)
    elseif ($action === 'remove_track') {
        $name = $data['name'] ?? '';
        $track = $data['track'] ?? '';
        
        if (isset($current[$name])) {
            $current[$name] = array_values(array_filter($current[$name], fn($t) => $t !== $track));
            file_put_contents($playlistFile, json_encode($current, JSON_PRETTY_PRINT));
            echo json_encode(['ok' => true]);
        } else {
             echo json_encode(['ok' => false, 'error' => 'Playlist not found']);
        }
    }
    // Delete entire playlist
    elseif ($action === 'delete') {
        $name = $data['name'];
        if (isset($current[$name])) {
            unset($current[$name]);
            file_put_contents($playlistFile, json_encode($current, JSON_PRETTY_PRINT));
            echo json_encode(['ok' => true]);
        }
    }
    exit;
}
?>