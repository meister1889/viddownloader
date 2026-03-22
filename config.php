<?php
// config.php
session_start();

// Directory for temporary files and rate limit files
define('TMP_DIR', __DIR__ . '/tmp/');

// Secret key for HMAC signing URLs
define('SECRET_KEY', 'your_super_secret_key_change_this_in_production_12345');

// Create tmp directory if it doesn't exist
if (!is_dir(TMP_DIR)) {
    mkdir(TMP_DIR, 0777, true);
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting settings
define('RATE_LIMIT_REQUESTS', 10); // requests per minute
define('RATE_LIMIT_TIME', 60); // 1 minute

function check_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $time = time();
    $safe_ip = preg_replace('/[^a-zA-Z0-9\.:]/', '', $ip);
    $limit_file = TMP_DIR . 'rate_limit_' . md5($safe_ip) . '.json';

    // Acquire a lock to prevent race conditions
    $fp = fopen($limit_file, 'c+');
    if (!$fp) {
        return false; // Fail safe
    }

    if (flock($fp, LOCK_EX)) {
        $filesize = filesize($limit_file);
        $content = $filesize > 0 ? fread($fp, $filesize) : '';
        $data = $content ? json_decode($content, true) : null;

        if (!$data || ($time - $data['start_time']) > RATE_LIMIT_TIME) {
            // Reset or initialize
            $data = [
                'count' => 1,
                'start_time' => $time
            ];
        } else {
            // Increment
            $data['count']++;
        }

        // Write back
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($data['count'] > RATE_LIMIT_REQUESTS) {
            return false;
        }
        return true;
    }

    fclose($fp);
    return false; // Could not get lock
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function cleanup_temp_files($dir, $max_age = 3600) {
    if (!is_dir($dir)) return;

    $files = glob($dir . '*');
    $now = time();

    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= $max_age) {
                unlink($file);
            }
        }
    }
}

function generate_download_signature($url) {
    return hash_hmac('sha256', $url, SECRET_KEY);
}

function verify_download_signature($url, $signature) {
    $expected_signature = generate_download_signature($url);
    return hash_equals($expected_signature, $signature);
}
?>