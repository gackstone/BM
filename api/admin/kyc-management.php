<?php
/**
 * Admin API: KYC Verification Management
 * BM FOREX HUB
 */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function send_admin_json($data, $code = 200) {
    while (ob_get_level()) ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level()) ob_end_clean();
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../engine_config.php';
require_once __DIR__ . '/../../api/didit-config.php';
require_once __DIR__ . '/../../app/Services/DiditService.php';

use App\Services\DiditService;

// Admin Authentication Check
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_user'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches) || $matches[1] !== SUPABASE_SERVICE) {
        send_admin_json(['success' => false, 'error' => 'Admin authentication required.'], 401);
    }
}

$adminUser = $_SESSION['admin_user'] ?? 'Admin';
$adminId   = $_SESSION['admin_id']   ?? 'admin-system';

$pdo = getMarketPDO();
$diditService = new DiditService($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$action = strtolower(trim($_REQUEST['action'] ?? 'list'));

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (is_array($input)) {
        if (!empty($input['action'])) $action = strtolower(trim($input['action']));
    }
} else {
    $input = $_GET;
}

// -------------------------------------------------------------
// Action: list
// -------------------------------------------------------------
if ($action === 'list') {
    $statusFilter = trim($input['status'] ?? '');
    $search = trim($input['search'] ?? '');
    $page = max(1, (int)($input['page'] ?? 1));
    $limit = min(100, max(1, (int)($input['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        if ($statusFilter === 'in_review') {
            $where[] = "(LOWER(k.status) = 'in review' OR LOWER(k.status) = 'pending')";
        } elseif ($statusFilter === 'approved') {
            $where[] = "LOWER(k.status) = 'approved'";
        } elseif ($statusFilter === 'declined') {
            $where[] = "(LOWER(k.status) = 'declined' OR LOWER(k.status) = 'failed')";
        } else {
            $where[] = "LOWER(k.status) = :status_filter";
            $params[':status_filter'] = strtolower($statusFilter);
        }
    }

    if (!empty($search)) {
        $where[] = "(k.user_id LIKE :search OR k.session_id LIKE :search OR p.email LIKE :search OR p.username LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM kyc_verifications k
        LEFT JOIN profiles p ON p.id = k.user_id
        $whereSql
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Summary counts
    $summary = [
        'total'     => (int)$pdo->query("SELECT COUNT(*) FROM kyc_verifications")->fetchColumn(),
        'approved'  => (int)$pdo->query("SELECT COUNT(*) FROM kyc_verifications WHERE LOWER(status) = 'approved'")->fetchColumn(),
        'in_review' => (int)$pdo->query("SELECT COUNT(*) FROM kyc_verifications WHERE LOWER(status) IN ('in review', 'pending')")->fetchColumn(),
        'declined'  => (int)$pdo->query("SELECT COUNT(*) FROM kyc_verifications WHERE LOWER(status) IN ('declined', 'failed')")->fetchColumn(),
        'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM kyc_verifications WHERE LOWER(status) IN ('not started', 'in progress')")->fetchColumn(),
    ];

    // Data query
    $stmt = $pdo->prepare("
        SELECT 
            k.id,
            k.user_id,
            k.session_id,
            k.workflow_id,
            k.status,
            k.vendor_data,
            k.verification_url,
            k.review_notes,
            k.created_at,
            k.updated_at,
            k.completed_at,
            p.email,
            p.username,
            p.first_name,
            p.last_name
        FROM kyc_verifications k
        LEFT JOIN profiles p ON p.id = k.user_id
        $whereSql
        ORDER BY k.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_admin_json([
        'success'    => true,
        'data'       => $rows,
        'summary'    => $summary,
        'pagination' => [
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => ceil($total / $limit)
        ]
    ]);
}

// -------------------------------------------------------------
// Action: inspect
// -------------------------------------------------------------
if ($action === 'inspect') {
    $sessionId = trim($input['session_id'] ?? '');
    if (empty($sessionId)) {
        send_admin_json(['success' => false, 'error' => 'Session ID is required.'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT 
            k.*,
            p.email,
            p.username,
            p.first_name,
            p.last_name,
            p.phone
        FROM kyc_verifications k
        LEFT JOIN profiles p ON p.id = k.user_id
        WHERE k.session_id = :sid OR k.id = :sid
        LIMIT 1
    ");
    $stmt->execute([':sid' => $sessionId]);
    $verif = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verif) {
        send_admin_json(['success' => false, 'error' => 'Verification session not found.'], 404);
    }

    // Webhook logs for this session
    $wStmt = $pdo->prepare("
        SELECT id, event_id, event_type, status, processed_at, created_at
        FROM kyc_webhook_logs
        WHERE session_id = :sid
        ORDER BY created_at DESC
    ");
    $wStmt->execute([':sid' => $verif['session_id']]);
    $webhooks = $wStmt->fetchAll(PDO::FETCH_ASSOC);

    // Audit logs for this user
    $aStmt = $pdo->prepare("
        SELECT id, admin_id, action, previous_status, new_status, reason, created_at
        FROM kyc_audit_logs
        WHERE user_id = :uid
        ORDER BY created_at DESC
    ");
    $aStmt->execute([':uid' => $verif['user_id']]);
    $audits = $aStmt->fetchAll(PDO::FETCH_ASSOC);

    send_admin_json([
        'success'      => true,
        'verification' => $verif,
        'webhooks'     => $webhooks,
        'audits'       => $audits
    ]);
}

// -------------------------------------------------------------
// Action: sync (query Didit API live decision)
// -------------------------------------------------------------
if ($action === 'sync') {
    $sessionId = trim($input['session_id'] ?? '');
    if (empty($sessionId)) {
        send_admin_json(['success' => false, 'error' => 'Session ID is required.'], 400);
    }

    $decisionRes = $diditService->getSessionDecision($sessionId);
    if (!$decisionRes['success']) {
        // Try getting basic session if decision endpoint is 404/not yet decided
        $sessionRes = $diditService->getSession($sessionId);
        if (!$sessionRes['success']) {
            send_admin_json([
                'success' => false,
                'error'   => 'Could not sync from Didit: ' . ($decisionRes['error'] ?: $sessionRes['error'])
            ], 400);
        }
        $data = $sessionRes['data'];
    } else {
        $data = $decisionRes['data'];
    }

    $newStatus = $data['status'] ?? 'In Progress';
    $now = date('c');

    $uStmt = $pdo->prepare("
        UPDATE kyc_verifications SET
            status = :status,
            decision = :decision,
            completed_at = CASE WHEN LOWER(:status) IN ('approved', 'declined', 'expired') THEN COALESCE(completed_at, :now) ELSE completed_at END,
            updated_at = :now
        WHERE session_id = :sid
    ");
    $uStmt->execute([
        ':status'   => $newStatus,
        ':decision' => json_encode($data, JSON_UNESCAPED_SLASHES),
        ':now'      => $now,
        ':sid'      => $sessionId
    ]);

    // Find user_id and update profile
    $fStmt = $pdo->prepare("SELECT user_id FROM kyc_verifications WHERE session_id = :sid LIMIT 1");
    $fStmt->execute([':sid' => $sessionId]);
    $userId = $fStmt->fetchColumn();

    if ($userId) {
        $diditService->updateProfileKyc($userId, $newStatus, $sessionId);
    }

    send_admin_json([
        'success'    => true,
        'session_id' => $sessionId,
        'status'     => $newStatus,
        'message'    => "Successfully synced session status: $newStatus"
    ]);
}

// -------------------------------------------------------------
// Action: override (admin manual status change with audit)
// -------------------------------------------------------------
if ($action === 'override') {
    $targetUserId = trim($input['user_id'] ?? '');
    $newStatus = trim($input['new_status'] ?? '');
    $reason = trim($input['reason'] ?? '');

    if (empty($targetUserId) || empty($newStatus) || empty($reason)) {
        send_admin_json(['success' => false, 'error' => 'user_id, new_status, and reason are required.'], 400);
    }

    $overrideRes = $diditService->adminOverrideStatus($targetUserId, $newStatus, $reason, $adminUser . " ($adminId)");

    send_admin_json($overrideRes, $overrideRes['success'] ? 200 : 400);
}

// -------------------------------------------------------------
// Action: webhook_logs
// -------------------------------------------------------------
if ($action === 'webhook_logs') {
    $stmt = $pdo->query("
        SELECT id, event_id, session_id, event_type, status, processed_at, created_at
        FROM kyc_webhook_logs
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_admin_json([
        'success' => true,
        'logs'    => $logs
    ]);
}

send_admin_json(['success' => false, 'error' => 'Unsupported action.'], 400);
