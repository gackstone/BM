<?php
/**
 * POST /api/webhooks/didit
 * Official Didit Webhook Endpoint for BM FOREX HUB
 *
 * Receives real-time Didit verification events:
 * - status.updated
 * - data.updated
 *
 * Implements:
 * - Raw request body handling
 * - Didit X-Signature-V2 HMAC-SHA256 verification
 * - X-Timestamp validation (< 300s) & replay attack protection
 * - Event idempotency & duplicate handling
 * - Authoritative decision retrieval
 * - Safe logging without PII
 */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../didit-config.php';
require_once __DIR__ . '/../../app/Services/DiditService.php';

use App\Services\DiditService;

// 1. Capture exact raw body
$rawPayload = file_get_contents('php://input');
if (empty($rawPayload)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Empty webhook payload.']);
    exit;
}

// 2. Collect all incoming headers
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0) {
        $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
        if (!isset($headers[$headerName])) {
            $headers[$headerName] = $v;
        }
    }
}

// 3. Verify signature, timestamp, and payload
$diditService = new DiditService();
$verification = $diditService->verifyWebhookSignature($rawPayload, $headers);

if (!$verification['valid']) {
    $errCode = $verification['code'] ?? 401;
    $errMsg = $verification['error'] ?? 'Unauthorized webhook signature.';
    
    // Log invalid signature attempt safely
    $logDir = __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents(
        $logDir . '/kyc_webhook_security.log',
        "[" . date('Y-m-d H:i:s') . "] Invalid webhook rejected ($errMsg) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n",
        FILE_APPEND | LOCK_EX
    );

    ob_end_clean();
    http_response_code($errCode);
    echo json_encode(['error' => $errMsg]);
    exit;
}

// 4. Process event idempotently
$processResult = $diditService->processWebhookEvent($verification);

ob_end_clean();
http_response_code(200);
echo json_encode([
    'received' => true,
    'status'   => 'processed',
    'session_id' => $verification['session_id'],
    'event_id' => $verification['event_id']
]);
exit;
