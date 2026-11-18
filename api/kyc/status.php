<?php
/**
 * GET /api/kyc/status.php
 * Returns current user's KYC verification status and details.
 *
 * Headers: Authorization: Bearer <token>
 */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../didit-config.php';
require_once __DIR__ . '/../../app/Services/DiditService.php';
require_once __DIR__ . '/../../app/Services/KycAccessService.php';

use App\Services\DiditService;
use App\Services\KycAccessService;

$user = get_authenticated_user();
if (!$user || empty($user['id'])) {
    send_kyc_json(['ok' => false, 'error' => 'Authentication required.'], 401);
}

$userId = $user['id'];
$userEmail = $user['email'] ?? '';

$diditService = new DiditService();
$accessService = new KycAccessService();

$status = $accessService->getKycStatus($userId, $userEmail);
$isApproved = $accessService->isKycApproved($userId, $userEmail);
$record = $diditService->getUserKycStatus($userId);

// Determine user friendly badges and actions
$norm = strtolower(trim($status));
$canRetry = in_array($norm, ['declined', 'failed', 'expired', 'abandoned', 'resubmitted']);
$inReview = in_array($norm, ['in review', 'review', 'pending']);
$inProgress = in_array($norm, ['in progress', 'not started']);

send_kyc_json([
    'ok'               => true,
    'kyc_status'       => $status,
    'is_verified'      => $isApproved,
    'in_review'        => $inReview,
    'in_progress'      => $inProgress,
    'can_retry'        => $canRetry,
    'session_id'       => $record['session_id'] ?? null,
    'verification_url' => $record['verification_url'] ?? null,
    'created_at'       => $record['created_at'] ?? null,
    'completed_at'     => $record['completed_at'] ?? null,
    'review_notes'     => $record['review_notes'] ?? null
], 200);
