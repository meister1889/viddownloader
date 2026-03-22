<?php
// download.php
require_once 'config.php';

// Allow GET requests for download
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method Not Allowed');
}

$url = $_GET['url'] ?? '';
$token = $_GET['token'] ?? '';
$signature = $_GET['sig'] ?? '';
$filename = $_GET['filename'] ?? 'video_' . time() . '.mp4';

// Validate CSRF token (passed in GET for file downloads)
if (empty($token) || !verify_csrf_token($token)) {
    http_response_code(403);
    die('Invalid CSRF token or session expired.');
}

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('Invalid URL.');
}

// Additional protection: Only allow HTTP(S) schemas to block file:// protocols
$parsed_url = parse_url($url);
$scheme = isset($parsed_url['scheme']) ? strtolower($parsed_url['scheme']) : '';
if ($scheme !== 'http' && $scheme !== 'https') {
    http_response_code(400);
    die('Invalid URL scheme.');
}

// Validate the signature to prevent SSRF
if (empty($signature) || !verify_download_signature($url, $signature)) {
    http_response_code(403);
    die('Invalid or missing signature. Direct downloads of arbitrary URLs are not allowed.');
}

// Ensure the filename is safe
$filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
if (empty($filename)) {
    $filename = 'download.mp4';
}
if (!str_ends_with(strtolower($filename), '.mp4')) {
    $filename .= '.mp4';
}

// Clean up temp files before processing
cleanup_temp_files(TMP_DIR, 3600);

// Set headers to force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream'); // Changed to octet-stream to force download
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Stream the file using cURL directly to output buffer
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Stream directly to browser
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// We intercept the headers to get the file size if possible
$filesize = 0;
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$filesize) {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2) {
        return $len;
    }

    $name = strtolower(trim($header[0]));
    if ($name === 'content-length') {
        $filesize = trim($header[1]);
        header('Content-Length: ' . $filesize);
    }

    return $len;
});

// Clear output buffer before streaming
if (ob_get_level()) {
    ob_end_clean();
}

// Execute cURL request which outputs directly
$success = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$success || $http_code >= 400) {
    // If the download fails, we try to show a message,
    // but headers are already sent, so this is just a fallback.
    echo "Error downloading file. HTTP Code: $http_code";
}

exit;
?>