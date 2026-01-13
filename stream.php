<?php
// kyle updated 
// stream.php - streams audio file over HTTP supporting Range Requests (HTTP 206)

$musicDir  = __DIR__ . '/mp3';
$chimesDir = __DIR__ . '/chimes';

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo "Missing file parameter.";
    exit;
}

$file = (string)$_GET['file'];
$isAlarm = isset($_GET['alarm']) && (string)$_GET['alarm'] === '1';

// Basic security: disallow directory traversal
if (strpos($file, '..') !== false) {
    http_response_code(400);
    echo "Invalid filename.";
    exit;
}

$baseDir = $isAlarm ? $chimesDir : $musicDir;

// Allowed file types
$allowedExt = $isAlarm
    ? ['mp3', 'wav', 'ogg', 'm4a']
    : ['mp3'];

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    echo "Invalid file type.";
    exit;
}

$realBase = realpath($baseDir);
if ($realBase === false) {
    http_response_code(500);
    echo "Audio directory missing.";
    exit;
}

$path = realpath($realBase . DIRECTORY_SEPARATOR . $file);

// Ensure file is inside base directory
if ($path === false || strpos($path, $realBase) !== 0 || !file_exists($path)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

// Content-Type mapping
$mimeMap = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

// --- RANGE REQUEST HANDLING START ---

$filesize = filesize($path);
$chunkSize = 1024 * 64; // 64KB
$start = 0;
$length = $filesize;
$httpCode = 200;

// Check for Range header
if (isset($_SERVER['HTTP_RANGE'])) {
    $httpCode = 206; // Partial Content
    $range = $_SERVER['HTTP_RANGE'];
    
    // Parse the range (e.g., "bytes=1000000-")
    if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        $start = (int)$matches[1];
        if (!empty($matches[2])) {
            $end = (int)$matches[2];
            $length = $end - $start + 1;
        } else {
            $length = $filesize - $start;
        }
    }

    // Set headers for partial content
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-" . ($start + $length - 1) . "/$filesize");
}

// Global headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . $length); // IMPORTANT: Length of the chunk being sent, not total file size
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Accept-Ranges: bytes');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');

// --- FILE STREAMING ---

$handle = fopen($path, 'rb');
if ($handle === false) {
    http_response_code(500);
    echo "Unable to open file.";
    exit;
}

// Move pointer to the starting byte
fseek($handle, $start);

// Stream only the requested length
$bytesSent = 0;
while (!feof($handle) && $bytesSent < $length) {
    $readSize = min($chunkSize, $length - $bytesSent);
    echo fread($handle, $readSize);
    $bytesSent += $readSize;
    
    // Flush the output buffer
    if (@ob_get_level() > 0) {
        @ob_flush();
    }
    flush();
}

fclose($handle);
exit;