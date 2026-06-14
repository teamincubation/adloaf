<?php
require_once __DIR__ . '/lib/helpers.php';

// Check if user is logged in (either as admin or client)
$isAdmin = !empty($_SESSION['admin_id']);
$isUser = !empty($_SESSION['user_id']);

if (!$isAdmin && !$isUser) {
    http_response_code(403);
    die("Access denied. Please log in first.");
}

// Get request ID and file parameter
$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$filePath = isset($_GET['file']) ? $_GET['file'] : '';

if (!$requestId || empty($filePath)) {
    http_response_code(400);
    die("Invalid request parameters.");
}

// Fetch the bake request to verify ownership and find the file
global $pdo;
$stmt = $pdo->prepare("SELECT user_id, uploaded_files FROM bake_requests WHERE id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    die("Bake request not found.");
}

// If the user is a public client, verify they own this bake request
if (!$isAdmin && $isUser && intval($request['user_id']) !== intval($_SESSION['user_id'])) {
    http_response_code(403);
    die("Access denied. You do not own this request.");
}

// Decode uploaded files JSON
$files = json_decode($request['uploaded_files'], true) ?: [];
$foundFile = null;
foreach ($files as $f) {
    // Check if the stored path's basename matches the requested filename's basename to prevent path traversal
    if (basename($f['path']) === basename($filePath)) {
        $foundFile = $f;
        break;
    }
}

if (!$foundFile) {
    http_response_code(404);
    die("File not associated with this bake request.");
}

// Sanitize path to prevent directory traversal
$relativeFilePath = $foundFile['path'];
$relativeFilePath = str_replace(['\\', '..'], ['/', ''], $relativeFilePath);

$fullPath = __DIR__ . '/' . $relativeFilePath;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    die("File not found on the server.");
}

// Send down the file as a secure attachment download
$fileName = $foundFile['name'];

// Detect mime type
$mimeType = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $detectedMime = @mime_content_type($fullPath);
    if ($detectedMime) {
        $mimeType = $detectedMime;
    }
}

// If mime type is potentially executable or inline-renderable script, force octet-stream for download safety
if (preg_match('/(html|javascript|xml|svg|php)/i', $mimeType)) {
    $mimeType = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

// Clean output buffer before reading file
if (ob_get_level()) {
    ob_end_clean();
}
readfile($fullPath);
exit;
