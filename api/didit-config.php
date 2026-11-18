<?php
/**
 * Didit KYC Configuration & Environment Loader
 * BM FOREX HUB
 *
 * Preserves supplied Didit credentials and loads configuration securely.
 * NEVER expose secret keys to the frontend or application logs.
 */

// Load .env variables if not already loaded
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if ($val !== '') {
            putenv("$key=$val");
            $_SERVER[$key] = $val;
            $_ENV[$key] = $val;
        }
    }
}

// Didit Configuration Constants
$didit_api_key = trim($_ENV['DIDIT_API_KEY'] ?? ($_SERVER['DIDIT_API_KEY'] ?? getenv('DIDIT_API_KEY') ?: 'sk_live_HT2OZn3oS-oI-M4AiWfD27JsDBXF-fpG6WWDnsGBBuA'));
$didit_workflow_id = trim($_ENV['DIDIT_WORKFLOW_ID'] ?? ($_SERVER['DIDIT_WORKFLOW_ID'] ?? getenv('DIDIT_WORKFLOW_ID') ?: 'cd012ec6-a97c-492b-b9f8-634bbc9bbf03'));
$didit_webhook_secret = trim($_ENV['DIDIT_WEBHOOK_SECRET'] ?? ($_SERVER['DIDIT_WEBHOOK_SECRET'] ?? getenv('DIDIT_WEBHOOK_SECRET') ?: $didit_api_key));
$didit_webhook_url = trim($_ENV['DIDIT_WEBHOOK_URL'] ?? ($_SERVER['DIDIT_WEBHOOK_URL'] ?? getenv('DIDIT_WEBHOOK_URL') ?: 'https://bmforexhub.exchange/api/webhooks/didit'));
$didit_base_url = trim($_ENV['DIDIT_BASE_URL'] ?? ($_SERVER['DIDIT_BASE_URL'] ?? getenv('DIDIT_BASE_URL') ?: 'https://verification.didit.me'));

if (!defined('DIDIT_API_KEY'))        define('DIDIT_API_KEY', $didit_api_key);
if (!defined('DIDIT_WORKFLOW_ID'))   define('DIDIT_WORKFLOW_ID', $didit_workflow_id);
if (!defined('DIDIT_WEBHOOK_SECRET')) define('DIDIT_WEBHOOK_SECRET', $didit_webhook_secret);
if (!defined('DIDIT_WEBHOOK_URL'))    define('DIDIT_WEBHOOK_URL', $didit_webhook_url);
if (!defined('DIDIT_BASE_URL'))       define('DIDIT_BASE_URL', rtrim($didit_base_url, '/'));

// Include core database and administrative dependencies
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../engine_config.php';

/**
 * Extract authenticated user from Bearer token
 * Checks Authorization header against Supabase auth
 */
if (!function_exists('get_authenticated_user')) {
    function get_authenticated_user(): ?array {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] 
            ?? $_SERVER['Authorization'] 
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
            ?? '';

        if (!preg_match('/Bearer\s+(.+)/i', $auth_header, $m)) {
            $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') { 
                    $auth_header = $v; 
                    break; 
                }
            }
            if (!preg_match('/Bearer\s+(.+)/i', $auth_header, $m)) {
                // If in session, check session
                if (!empty($_SESSION['user_id'])) {
                    return [
                        'id' => $_SESSION['user_id'],
                        'email' => $_SESSION['user_email'] ?? '',
                        'user_metadata' => ['username' => $_SESSION['username'] ?? '']
                    ];
                }
                return null;
            }
        }
        $token = trim($m[1]);
        if (empty($token)) return null;

        $supabase_url  = defined('SUPABASE_URL') ? SUPABASE_URL : '';
        $supabase_anon = defined('SUPABASE_ANON') ? SUPABASE_ANON : '';

        if (!$supabase_url) return null;

        if (function_exists('curl_init')) {
            $ch = curl_init("$supabase_url/auth/v1/user");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $token",
                    "apikey: $supabase_anon"
                ],
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && $resp) {
                $user = json_decode($resp, true);
                return isset($user['id']) ? $user : null;
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer $token\r\napikey: $supabase_anon\r\n",
                'timeout' => 6,
            ],
        ]);

        $resp = @file_get_contents("$supabase_url/auth/v1/user", false, $ctx);
        if (!$resp) return null;
        $user = json_decode($resp, true);
        return isset($user['id']) ? $user : null;
    }
}

/**
 * Send JSON response and exit cleanly
 */
if (!function_exists('send_kyc_json')) {
    function send_kyc_json($data, int $code = 200): void {
        while (ob_get_level()) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
