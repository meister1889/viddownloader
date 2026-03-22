<?php
// api.php
require_once 'config.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => 'Method Not Allowed'], 405);
}

// Receive JSON data
$input_data = json_decode(file_get_contents('php://input'), true);

if (!$input_data) {
    send_json_response(['error' => 'Invalid JSON payload'], 400);
}

// Validate CSRF token
if (empty($input_data['csrf_token']) || !verify_csrf_token($input_data['csrf_token'])) {
    send_json_response(['error' => 'Invalid CSRF token'], 403);
}

// Rate Limiting
if (!check_rate_limit()) {
    send_json_response(['error' => 'Rate limit exceeded. Try again later.'], 429);
}

// Cleanup old temp files
cleanup_temp_files(TMP_DIR, 3600); // 1 hour

// Process request
$action = $input_data['action'] ?? '';
$url = $input_data['url'] ?? '';

if (empty($url)) {
    send_json_response(['error' => 'URL is required'], 400);
}

// Validate URL structure broadly
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    send_json_response(['error' => 'Invalid URL format'], 400);
}

// Platform detection logic
$platform = detect_platform($url);

if ($platform === 'unknown') {
    send_json_response(['error' => 'Unsupported platform. Only TikTok and Instagram are supported.'], 400);
}

if ($action === 'fetch_info') {
    // Fetch info based on platform
    if ($platform === 'tiktok') {
        $info = fetch_tiktok_info($url);
    } elseif ($platform === 'instagram') {
        $info = fetch_instagram_info($url);
    }

    if (isset($info['error'])) {
        send_json_response(['error' => $info['error']], 400);
    }

    send_json_response($info);
}

send_json_response(['error' => 'Invalid action'], 400);


// Functions
function detect_platform($url) {
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['host'])) {
        return 'unknown';
    }

    $host = strtolower($parsed_url['host']);

    if (strpos($host, 'tiktok.com') !== false || strpos($host, 'vt.tiktok.com') !== false) {
        return 'tiktok';
    } elseif (strpos($host, 'instagram.com') !== false) {
        return 'instagram';
    }
    return 'unknown';
}

function fetch_tiktok_info($url) {
    // Use tikwm API as it's free and doesn't require keys for basic usage
    // Endpoint: https://www.tikwm.com/api/
    $api_url = 'https://www.tikwm.com/api/?url=' . urlencode($url) . '&count=12&cursor=0&web=1&hd=1';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        return ['error' => 'Failed to fetch TikTok data. Please check the URL and try again.'];
    }

    $data = json_decode($response, true);

    if (isset($data['code']) && $data['code'] === 0 && isset($data['data'])) {
        $video_data = $data['data'];

        // Ensure URLs start with https://
        $fix_url = function($url) {
            if (!$url) return null;
            if (strpos($url, '//') === 0) return 'https:' . $url;
            return $url;
        };

        $resolutions = [];
        $qualities = [
            ['quality' => 'auto', 'label' => 'Auto (Best Available)', 'url' => $fix_url($video_data['play'] ?? null)],
            ['quality' => 'hd', 'label' => 'HD (High Quality)', 'url' => $fix_url($video_data['hdplay'] ?? null)],
            ['quality' => 'watermark', 'label' => 'With Watermark', 'url' => $fix_url($video_data['wmplay'] ?? null)]
        ];

        foreach ($qualities as $q) {
            if ($q['url']) {
                $q['signature'] = generate_download_signature($q['url']);
                $resolutions[] = $q;
            }
        }

        return [
            'platform' => 'tiktok',
            'title' => $video_data['title'] ?? 'TikTok Video',
            'thumbnail' => $fix_url($video_data['cover'] ?? null),
            'author' => $video_data['author']['nickname'] ?? 'Unknown',
            'resolutions' => $resolutions
        ];
    }

    return ['error' => 'Invalid TikTok URL or video is private.'];
}

function fetch_instagram_info($url) {
    // For Instagram, we'll use a public API or a simple mock since it frequently blocks IPs
    // and requires complex auth for reliable scraping. For this demo, we can use a free generic API
    // or simulate it if the public API fails. A real production app would need robust proxy/auth setup.

    // We'll use snapinsta or similar via rapidapi, but for pure PHP without paid API keys,
    // we have to rely on generic public endpoints or return a placeholder error if none are available.
    // Here we'll implement a basic generic scraper approach that works for some public reels,
    // and a fallback.

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    // Set headers to mimic a real browser to bypass some simple checks
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Instagram is notoriously hard to scrape without auth/proxies.
    // We'll try to extract og:video or similar basic meta tags.
    $video_url = null;
    $thumbnail = null;
    $title = 'Instagram Video';

    if ($http_code === 200 && $response) {
        if (preg_match('/<meta property="og:video" content="([^"]+)"/i', $response, $matches)) {
            $video_url = html_entity_decode($matches[1]);
        }
        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $response, $matches)) {
            $thumbnail = html_entity_decode($matches[1]);
        }
        if (preg_match('/<meta property="og:title" content="([^"]+)"/i', $response, $matches)) {
            $title = html_entity_decode($matches[1]);
        }
    }

    if ($video_url) {
        return [
            'platform' => 'instagram',
            'title' => $title,
            'thumbnail' => $thumbnail,
            'author' => 'Instagram User',
            'resolutions' => [
                [
                    'quality' => 'auto',
                    'label' => 'Auto (Best Available)',
                    'url' => $video_url,
                    'signature' => generate_download_signature($video_url)
                ]
            ]
        ];
    }

    // If simple scraping fails, we return a helpful error indicating the limitation.
    return ['error' => 'Failed to fetch Instagram video. The video might be private, or Instagram is blocking the request.'];
}
?>