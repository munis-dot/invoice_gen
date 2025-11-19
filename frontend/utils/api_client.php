<?php
require_once __DIR__ . '/session.php';

/**
 * Build a full URL for API endpoints when a relative path is provided.
 * If $path already contains a scheme (http/https) it is returned as-is.
 *
 * @param string $path Relative or absolute URL
 * @return string Full URL
 */
function apiUrl(string $path): string
{
    // If already an absolute URL, return as-is
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Ensure single leading slash
    $path = '/' . ltrim($path, '/');

    return $scheme . '://' . $host . $path;
}

/**
 * Send an API request with JSON payload and Bearer token authentication.
 * 
 * @param string $url API endpoint URL
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param array $data Optional request data
 * @return array|null Decoded JSON response or null on error
 */
function apiRequest(string $url, string $method = 'GET', array $data = []): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $finalUrl = apiUrl($url);
    $method = strtoupper($method);
    //echo $finalUrl;
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . (getJwtToken() ?? ''),
    ];

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'timeout' => 15,
            'ignore_errors' => true, // get response even if 4xx/5xx
        ]
    ];

    if (!empty($data)) {
        $options['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($finalUrl, false, $context);

    // Capture HTTP response code
    $httpCode = 0;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                $httpCode = (int) $matches[1];
                break;
            }
        }
    }
    // echo $response;

    if ($response === false) {
        return ['error' => 'Network error occurred. Please try again.'];
    }
    if ($httpCode >= 400) {
        return ['error' => "API responded with HTTP $httpCode, $response"];
    }
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON response: $response");
        return ['error' => "Invalid JSON response: $response"];
    }

    return $decoded;
}
?>