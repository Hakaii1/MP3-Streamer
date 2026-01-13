<?php
// kyle updated 
// api_alarms.php

// --- CRITICAL FIX: Suppress PHP Errors ---
error_reporting(0);
ini_set('display_errors', 0);
// -----------------------------------------

header('Content-Type: application/json');

$alarmsFile = __DIR__ . '/alarms.json';
$chimesDir  = __DIR__ . '/chimes';

// Helper: Open file with Exclusive Lock (prevents overwriting/race conditions)
function withAlarmsLock($file, callable $callback) {
    $fp = fopen($file, 'c+'); // Open for reading and writing
    if (!$fp) {
        // Fallback if file cannot be opened
        return $callback(['slots' => [], 'last_event' => null]);
    }

    $result = null;

    // Try to get lock
    if (flock($fp, LOCK_EX)) {
        $fsize = filesize($file);
        $content = $fsize > 0 ? fread($fp, $fsize) : '';
        $data = json_decode($content, true);

        // Ensure valid structure
        if (!is_array($data)) {
            $data = ['slots' => [], 'last_event' => null];
        }
        if (!isset($data['slots'])) $data['slots'] = [];
        if (!array_key_exists('last_event', $data)) $data['last_event'] = null;

        // Run the logic
        $newData = $callback($data);

        // Save back to file
        if ($newData !== null) {
            ftruncate($fp, 0);      // Clear file
            rewind($fp);            // Go to start
            fwrite($fp, json_encode($newData, JSON_PRETTY_PRINT));
            fflush($fp);
        }
        
        $result = $newData;
        flock($fp, LOCK_UN); // Release lock
    }
    
    fclose($fp);
    return $result;
}

function isAllowedChime($filename) {
    if (!$filename) return false;
    if (strpos($filename, '..') !== false) return false;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp3','wav','ogg','m4a'], true);
}

function chimeExists($chimesDir, $filename) {
    $path = realpath($chimesDir . DIRECTORY_SEPARATOR . $filename);
    $realBase = realpath($chimesDir);
    return ($path && $realBase && strpos($path, $realBase) === 0 && file_exists($path));
}

// --- HANDLE REQUESTS ---

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Read-only access doesn't strictly need a write lock, but consistency helps
    $data = withAlarmsLock($alarmsFile, function($d) { return $d; });
    echo json_encode($data);
    exit;
}

// POST Requests
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];
$action = $body['action'] ?? '';

// WRAP ALL WRITE ACTIONS IN THE LOCK
$finalData = withAlarmsLock($alarmsFile, function($alarms) use ($action, $body, $chimesDir) {
    
    if ($action === 'create') {
        $time  = trim((string)($body['time'] ?? ''));
        $title = trim((string)($body['title'] ?? ''));
        $file  = trim((string)($body['file'] ?? ''));

        if (!preg_match('/^\d{2}:\d{2}$/', $time)) return $alarms; // Invalid time
        if ($title === '' || !$file) return $alarms; // Invalid data
        if (!isAllowedChime($file)) return $alarms;
        if (!chimeExists($chimesDir, $file)) return $alarms;

        // Generate ID
        $newId = uniqid('slot_');

        $alarms['slots'][] = [
            'id' => $newId,
            'time' => $time,
            'title' => $title,
            'file' => $file,
            'enabled' => true,
            'last_fired' => null
        ];
        return $alarms;
    }

    if ($action === 'delete') {
        $id = (string)($body['id'] ?? '');
        $alarms['slots'] = array_values(array_filter($alarms['slots'], fn($s) => ($s['id'] ?? '') !== $id));
        return $alarms;
    }

    if ($action === 'toggle') {
        $id = (string)($body['id'] ?? '');
        foreach ($alarms['slots'] as &$s) {
            if (($s['id'] ?? '') === $id) {
                $s['enabled'] = empty($s['enabled']); // Toggle boolean
                break;
            }
        }
        return $alarms;
    }

    if ($action === 'fire') {
        $id = (string)($body['id'] ?? '');
        $slot = null;
        foreach ($alarms['slots'] as $s) {
            if (($s['id'] ?? '') === $id) { $slot = $s; break; }
        }
        
        if ($slot) {
            $alarms['last_event'] = [
                'id' => uniqid('evt_'),
                'slot_id' => $slot['id'],
                'title' => $slot['title'],
                'file' => $slot['file'],
                'fired_at' => time()
            ];
        }
        return $alarms;
    }

    return $alarms; // No change
});

if ($finalData) {
    echo json_encode(['ok' => true, 'alarms' => $finalData]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Operation failed']);
}
?>