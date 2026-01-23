<?php
// api_tts.php
// PHP Text-to-Speech Handler

header('Content-Type: application/json');

// 1. Basic Security Checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// 2. Get the text from the POST request
// Note: We use json_decode because modern frontends usually send JSON, 
// but we check standard $_POST too just in case.
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$text = $input['text'] ?? $_POST['text'] ?? '';

if (empty(trim($text))) {
    http_response_code(400);
    echo json_encode(['error' => 'Text is required']);
    exit();
}

// 3. Setup the storage folder
$uploadDir = 'tts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); // Create folder if it doesn't exist
}

try {
    // 4. Generate a unique filename
    $filename = uniqid('tts_', true) . '.mp3';
    $filePath = $uploadDir . $filename;

    // 5. Call the Google TTS (Unofficial) Endpoint
    // We must URL-encode the text so spaces and symbols don't break the URL
    $encodedText = urlencode($text);
    $lang = 'en'; // You can change this to 'es', 'fr', etc.

    $googleUrl = "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&q={$encodedText}&tl={$lang}";

    // 6. Fetch the audio data
    $audioData = file_get_contents($googleUrl);

    if ($audioData === false) {
        throw new Exception("Failed to fetch audio from Google.");
    }

    // 7. Save to your server
    file_put_contents($filePath, $audioData);

    // 8. Return the URL to the frontend
    // This dynamically finds your current domain/folder to create the full URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['PHP_SELF']);

    // Ensure scriptDir has a trailing slash if it's not root
    $scriptDir = rtrim($scriptDir, '/') . '/';

    $fullUrl = "{$protocol}://{$host}{$scriptDir}{$filePath}";

    echo json_encode([
        'success' => true,
        'message' => 'Audio generated',
        'file_url' => $fullUrl
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
