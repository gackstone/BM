<?php
/**
 * POST /api/kyc/create-session.php
 * Initializes a Didit KYC verification session for the authenticated user.
 *
 * Headers: Authorization: Bearer <token>
 * Returns: { "ok": true, "session_id": "...", "url": "...", "status": "..." }
 */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../didit-config.php';
require_once __DIR__ . '/../../app/Services/DiditService.php';
require_once __DIR__ . '/../../app/Services/KycAccessService.php';

use App\Services\DiditService;
use App\Services\KycAccessService;

// 1. Authenticate user from server-validated token
$user = get_authenticated_user();
if (!$user || empty($user['id'])) {
    send_kyc_json(['ok' => false, 'error' => 'Authentication required. Please sign in.'], 401);
}

$userId = $user['id'];
$userEmail = $user['email'] ?? '';

$diditService = new DiditService();
$accessService = new KycAccessService();

// Parse any optional request body
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: [];
$forceRetry = !empty($input['retry']) || !empty($input['force_retry']);

// 2. Check if user is already verified
if ($accessService->isKycApproved($userId, $userEmail)) {
    send_kyc_json([
        'ok'         => true,
        'status'     => 'Approved',
        'message'    => 'Your identity has already been verified and approved.',
        'already_verified' => true
    ], 200);
}

// 3. Check for existing active session unless forceRetry is specified
$existing = $diditService->getUserKycStatus($userId);
if (!$forceRetry && $existing && !empty($existing['session_id']) && !empty($existing['verification_url'])) {
    $existingStatus = strtolower(trim($existing['status'] ?? ''));
    $createdTime = strtotime($existing['created_at'] ?? 'now');
    $hoursAgo = (time() - $createdTime) / 3600;

    // Reuse session if created within 24 hours and still in progress
    if ($hoursAgo < 24 && in_array($existingStatus, ['not started', 'in progress', 'pending'])) {
        send_kyc_json([
            'ok'         => true,
            'session_id' => $existing['session_id'],
            'url'        => $existing['verification_url'],
            'status'     => $existing['status'],
            'reused'     => true
        ], 200);
    }
}

// 4. Create new verification session via official Didit API v3
$sessionResult = $diditService->createSession($userId, [
    'callback' => defined('DIDIT_WEBHOOK_URL') ? DIDIT_WEBHOOK_URL : 'https://bmforexhub.exchange/api/webhooks/didit'
]);

if ($sessionResult['success']) {
    send_kyc_json([
        'ok'         => true,
        'session_id' => $sessionResult['session_id'],
        'url'        => $sessionResult['url'],
        'status'     => $sessionResult['status'],
        'reused'     => false
    ], 200);
} else {
    send_kyc_json([
        'ok'    => false,
        'error' => $sessionResult['error'] ?? 'Failed to initialize verification session with Didit.',
        'code'  => $sessionResult['code'] ?? 500
    ], $sessionResult['code'] ?? 500);
}
