<?php
namespace App\Services;

use PDO;
use Exception;

/**
 * Didit KYC Verification Service v3
 * Official Didit Identity Verification Integration for BM FOREX HUB
 */
class DiditService
{
    private string $apiKey;
    private string $workflowId;
    private string $webhookSecret;
    private string $webhookUrl;
    private string $baseUrl;
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->apiKey = defined('DIDIT_API_KEY') ? DIDIT_API_KEY : (getenv('DIDIT_API_KEY') ?: '');
        $this->workflowId = defined('DIDIT_WORKFLOW_ID') ? DIDIT_WORKFLOW_ID : (getenv('DIDIT_WORKFLOW_ID') ?: '');
        $this->webhookSecret = defined('DIDIT_WEBHOOK_SECRET') ? DIDIT_WEBHOOK_SECRET : (getenv('DIDIT_WEBHOOK_SECRET') ?: $this->apiKey);
        $this->webhookUrl = defined('DIDIT_WEBHOOK_URL') ? DIDIT_WEBHOOK_URL : (getenv('DIDIT_WEBHOOK_URL') ?: 'https://bmforexhub.exchange/api/webhooks/didit');
        $this->baseUrl = defined('DIDIT_BASE_URL') ? DIDIT_BASE_URL : (getenv('DIDIT_BASE_URL') ?: 'https://verification.didit.me');

        if ($pdo !== null) {
            $this->pdo = $pdo;
        } elseif (function_exists('getMarketPDO')) {
            $this->pdo = getMarketPDO();
        } else {
            $dbPath = defined('DB_PATH') ? DB_PATH : (__DIR__ . '/../../storage/database.sqlite');
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * Create a new Didit KYC verification session
     *
     * @param string $userId Internal user ID (never trusted from browser alone)
     * @param array $options Optional metadata or callback overrides
     * @return array
     */
    public function createSession(string $userId, array $options = []): array
    {
        if (empty($userId)) {
            return [
                'success' => false,
                'error' => 'User ID is required to create a verification session.',
                'code' => 400
            ];
        }

        if (empty($this->apiKey) || empty($this->workflowId)) {
            return [
                'success' => false,
                'error' => 'Didit configuration missing: API key or Workflow ID is not set.',
                'code' => 500
            ];
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/v3/session/';
        $callback = $options['callback'] ?? $this->webhookUrl;

        $payload = [
            'workflow_id' => $this->workflowId,
            'vendor_data' => $userId,
            'callback'    => $callback
        ];

        // Include any additional supported session parameters if provided
        if (!empty($options['features'])) {
            $payload['features'] = $options['features'];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: BM-ForexHub-KYC/1.0'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || !empty($curlError)) {
            $this->logError("Network error while connecting to Didit API: " . $curlError);
            return [
                'success' => false,
                'error'   => 'Verification gateway communication error. Please try again shortly.',
                'curl_error' => $curlError,
                'code'    => 504
            ];
        }

        $data = json_decode($responseBody, true);
        if ($httpCode >= 200 && $httpCode < 300 && is_array($data) && !empty($data['session_id'])) {
            $sessionId = $data['session_id'];
            $verificationUrl = $data['url'] ?? '';
            $status = $data['status'] ?? 'Not Started';

            // Persist the session to kyc_verifications
            $this->recordSession($userId, $sessionId, $this->workflowId, $status, $verificationUrl, $data);

            return [
                'success'         => true,
                'session_id'      => $sessionId,
                'url'             => $verificationUrl,
                'status'          => $status,
                'workflow_id'     => $this->workflowId,
                'vendor_data'     => $userId,
                'data'            => $data,
                'code'            => $httpCode
            ];
        }

        // Handle specific failure codes with safe structured messages
        $errorDetail = 'Verification session initialization failed.';
        if ($httpCode === 401) {
            $errorDetail = 'Didit API authentication failed (invalid or unactivated API key).';
        } elseif ($httpCode === 400) {
            $errorDetail = 'Invalid workflow ID or request parameters.';
            if (!empty($data['detail'])) {
                $errorDetail .= ' ' . (is_string($data['detail']) ? $data['detail'] : json_encode($data['detail']));
            }
        } elseif ($httpCode === 403) {
            $errorDetail = 'Permission denied for Didit workflow execution.';
        } elseif (!empty($data['detail']) && is_string($data['detail'])) {
            $errorDetail = $data['detail'];
        }

        $this->logError("Didit session creation failed [HTTP $httpCode]: $errorDetail");

        return [
            'success' => false,
            'error'   => $errorDetail,
            'code'    => $httpCode,
            'data'    => $data
        ];
    }

    /**
     * Retrieve session details from Didit API
     */
    public function getSession(string $sessionId): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . "/v3/session/{$sessionId}/";
        return $this->executeGet($endpoint);
    }

    /**
     * Retrieve authoritative decision for a session
     */
    public function getSessionDecision(string $sessionId): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . "/v3/session/{$sessionId}/decision/";
        return $this->executeGet($endpoint);
    }

    /**
     * Execute authenticated GET request to Didit API
     */
    private function executeGet(string $endpoint): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'Accept: application/json',
                'User-Agent: BM-ForexHub-KYC/1.0'
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || !empty($curlError)) {
            return [
                'success' => false,
                'error'   => 'Didit API connection error: ' . $curlError,
                'code'    => 504
            ];
        }

        $data = json_decode($responseBody, true);
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'code'    => $httpCode,
            'data'    => $data,
            'error'   => ($httpCode >= 200 && $httpCode < 300) ? null : ($data['detail'] ?? "HTTP error $httpCode")
        ];
    }

    /**
     * Verify incoming Didit webhook signature and timestamp
     *
     * @param string $payloadRaw The unmodified raw JSON payload string
     * @param array $headers Request headers
     * @return array Verification outcome with parsed payload
     */
    public function verifyWebhookSignature(string $payloadRaw, array $headers): array
    {
        // 1. Locate headers (case-insensitive)
        $sigHeader = '';
        $timestampHeader = '';

        foreach ($headers as $key => $val) {
            $lk = strtolower(str_replace('_', '-', $key));
            if ($lk === 'x-signature-v2' || $lk === 'http-x-signature-v2') {
                $sigHeader = is_array($val) ? reset($val) : $val;
            } elseif ($lk === 'x-timestamp' || $lk === 'http-x-timestamp') {
                $timestampHeader = is_array($val) ? reset($val) : $val;
            } elseif (empty($sigHeader) && ($lk === 'x-signature' || $lk === 'http-x-signature')) {
                $sigHeader = is_array($val) ? reset($val) : $val;
            }
        }

        if (empty($sigHeader)) {
            return [
                'valid' => false,
                'error' => 'Missing signature header (X-Signature-V2).',
                'code'  => 401
            ];
        }

        if (empty($timestampHeader)) {
            return [
                'valid' => false,
                'error' => 'Missing timestamp header (X-Timestamp).',
                'code'  => 401
            ];
        }

        // 2. Validate Timestamp freshness (within 300 seconds / 5 minutes)
        $now = time();
        $eventTs = (int)$timestampHeader;
        if ($eventTs <= 0 || abs($now - $eventTs) > 300) {
            return [
                'valid' => false,
                'error' => 'Timestamp expired or outside acceptable 5-minute replay window.',
                'code'  => 400
            ];
        }

        // Clean signature value (remove potential v2= or sha256= prefix)
        $receivedSig = trim($sigHeader);
        if (strpos($receivedSig, '=') !== false) {
            $parts = explode('=', $receivedSig, 2);
            $receivedSig = trim($parts[1]);
        }

        // 3. Parse JSON payload
        $parsedBody = json_decode($payloadRaw, true);
        if (!is_array($parsedBody)) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON payload.',
                'code'  => 400
            ];
        }

        // 4. Canonicalize JSON payload according to Didit specification:
        // Recursively sorted keys, compact separators, unescaped unicode/slashes
        $canonicalJson = $this->canonicalizeJson($parsedBody);

        // Secrets to test against (configured secret and API key fallback)
        $secrets = array_filter(array_unique([
            $this->webhookSecret,
            $this->apiKey
        ]));

        $signatureMatches = false;
        foreach ($secrets as $secret) {
            if (empty($secret)) continue;

            // Check signature over canonical JSON
            $expectedCanonical = hash_hmac('sha256', $canonicalJson, $secret);
            if (hash_equals(strtolower($expectedCanonical), strtolower($receivedSig))) {
                $signatureMatches = true;
                break;
            }

            // Also check signature over raw payload string as secondary fallback
            $expectedRaw = hash_hmac('sha256', $payloadRaw, $secret);
            if (hash_equals(strtolower($expectedRaw), strtolower($receivedSig))) {
                $signatureMatches = true;
                break;
            }
        }

        if (!$signatureMatches) {
            return [
                'valid' => false,
                'error' => 'Webhook signature verification failed.',
                'code'  => 403
            ];
        }

        $eventId = $parsedBody['event_id'] ?? ($parsedBody['id'] ?? ('evt_' . md5($payloadRaw)));
        $sessionId = $parsedBody['session_id'] ?? '';
        $eventType = $parsedBody['webhook_type'] ?? ($parsedBody['type'] ?? 'unknown');
        $status = $parsedBody['status'] ?? '';
        $vendorData = $parsedBody['vendor_data'] ?? '';

        return [
            'valid'       => true,
            'event_id'    => $eventId,
            'session_id'  => $sessionId,
            'event_type'  => $eventType,
            'status'      => $status,
            'vendor_data' => $vendorData,
            'data'        => $parsedBody,
            'code'        => 200
        ];
    }

    /**
     * Recursively sort keys alphabetically and produce compact canonical JSON
     */
    public function canonicalizeJson(mixed $data): string
    {
        $sorted = $this->recursiveSortKeys($data);
        return json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function recursiveSortKeys(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // Check if sequential numeric array
        $isList = array_is_list($data);
        if ($isList) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->recursiveSortKeys($item);
            }
            return $result;
        }

        // Associative array: sort keys alphabetically
        ksort($data, SORT_STRING);
        $result = [];
        foreach ($data as $k => $v) {
            $result[$k] = $this->recursiveSortKeys($v);
        }
        return $result;
    }

    /**
     * Persist session creation to database
     */
    public function recordSession(string $userId, string $sessionId, string $workflowId, string $status, string $url, array $raw = []): void
    {
        try {
            $now = date('c');
            $id = 'kyc_' . bin2hex(random_bytes(12));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

            $stmt = $this->pdo->prepare("
                INSERT INTO kyc_verifications (
                    id, user_id, session_id, workflow_id, status, vendor_data, verification_url,
                    ip_address, user_agent, created_at, updated_at
                ) VALUES (
                    :id, :user_id, :session_id, :workflow_id, :status, :vendor_data, :verification_url,
                    :ip_address, :user_agent, :created_at, :updated_at
                )
                ON CONFLICT(session_id) DO UPDATE SET
                    status = excluded.status,
                    verification_url = excluded.verification_url,
                    updated_at = excluded.updated_at
            ");

            $stmt->execute([
                ':id'               => $id,
                ':user_id'          => $userId,
                ':session_id'       => $sessionId,
                ':workflow_id'      => $workflowId,
                ':status'           => $status,
                ':vendor_data'      => $userId,
                ':verification_url' => $url,
                ':ip_address'       => $ip,
                ':user_agent'       => $ua,
                ':created_at'       => $now,
                ':updated_at'       => $now
            ]);

            // Update user's profile with the active session reference
            $this->updateProfileKyc($userId, $status, $sessionId);

        } catch (Exception $e) {
            $this->logError("Failed to record KYC session: " . $e->getMessage());
        }
    }

    /**
     * Process verified webhook event idempotently
     */
    public function processWebhookEvent(array $event): array
    {
        $eventId = $event['event_id'] ?? '';
        $sessionId = $event['session_id'] ?? '';
        $eventType = $event['event_type'] ?? '';
        $status = $event['status'] ?? '';
        $vendorData = $event['vendor_data'] ?? '';
        $now = date('c');

        if (empty($sessionId)) {
            return ['success' => false, 'error' => 'Missing session ID in webhook event.'];
        }

        // 1. Idempotency Check: check if event_id already logged
        $stmt = $this->pdo->prepare("SELECT id FROM kyc_webhook_logs WHERE event_id = :eid LIMIT 1");
        $stmt->execute([':eid' => $eventId]);
        if ($stmt->fetch()) {
            return [
                'success'      => true,
                'idempotent'   => true,
                'message'      => 'Duplicate webhook event already processed.'
            ];
        }

        // 2. Record webhook receipt
        try {
            $logId = 'kwl_' . bin2hex(random_bytes(10));
            $payloadHash = hash('sha256', json_encode($event['data'] ?? []));
            $logStmt = $this->pdo->prepare("
                INSERT INTO kyc_webhook_logs (
                    id, event_id, session_id, event_type, status, payload_hash, processed_at, is_duplicate, created_at
                ) VALUES (
                    :id, :event_id, :session_id, :event_type, :status, :payload_hash, :processed_at, 0, :created_at
                )
            ");
            $logStmt->execute([
                ':id'           => $logId,
                ':event_id'     => $eventId,
                ':session_id'   => $sessionId,
                ':event_type'   => $eventType,
                ':status'       => $status,
                ':payload_hash' => $payloadHash,
                ':processed_at' => $now,
                ':created_at'   => $now
            ]);
        } catch (Exception $e) {
            $this->logError("Failed to log webhook event: " . $e->getMessage());
        }

        // 3. Find the verification session
        $sStmt = $this->pdo->prepare("SELECT * FROM kyc_verifications WHERE session_id = :sid LIMIT 1");
        $sStmt->execute([':sid' => $sessionId]);
        $verification = $sStmt->fetch(PDO::FETCH_ASSOC);

        $userId = $verification['user_id'] ?? $vendorData;

        // 4. If status is decisive, retrieve authoritative decision if possible
        $decisionData = null;
        if (in_array(strtolower($status), ['approved', 'declined', 'in review', 'expired', 'abandoned'])) {
            $decisionRes = $this->getSessionDecision($sessionId);
            if ($decisionRes['success'] && !empty($decisionRes['data'])) {
                $decisionData = $decisionRes['data'];
                if (!empty($decisionData['status'])) {
                    $status = $decisionData['status'];
                }
            }
        }

        // 5. Update kyc_verifications
        $completedAt = in_array(strtolower($status), ['approved', 'declined', 'expired']) ? $now : null;
        $decisionJson = $decisionData ? json_encode($decisionData, JSON_UNESCAPED_SLASHES) : null;

        if ($verification) {
            $uStmt = $this->pdo->prepare("
                UPDATE kyc_verifications SET
                    status = :status,
                    decision = COALESCE(:decision, decision),
                    completed_at = COALESCE(:completed_at, completed_at),
                    updated_at = :updated_at
                WHERE session_id = :sid
            ");
            $uStmt->execute([
                ':status'       => $status,
                ':decision'     => $decisionJson,
                ':completed_at' => $completedAt,
                ':updated_at'   => $now,
                ':sid'          => $sessionId
            ]);
        } else {
            // If session was not yet in our DB, create the record
            $id = 'kyc_' . bin2hex(random_bytes(12));
            $iStmt = $this->pdo->prepare("
                INSERT INTO kyc_verifications (
                    id, user_id, session_id, workflow_id, status, vendor_data, decision, created_at, updated_at, completed_at
                ) VALUES (
                    :id, :user_id, :session_id, :workflow_id, :status, :vendor_data, :decision, :created_at, :updated_at, :completed_at
                )
            ");
            $iStmt->execute([
                ':id'           => $id,
                ':user_id'      => $userId ?: 'unknown_user',
                ':session_id'   => $sessionId,
                ':workflow_id'  => $event['data']['workflow_id'] ?? $this->workflowId,
                ':status'       => $status,
                ':vendor_data'  => $vendorData,
                ':decision'     => $decisionJson,
                ':created_at'   => $now,
                ':updated_at'   => $now,
                ':completed_at' => $completedAt
            ]);
        }

        // 6. Update user's profile kyc_status
        if (!empty($userId)) {
            $this->updateProfileKyc($userId, $status, $sessionId, $completedAt);
        }

        return [
            'success'    => true,
            'session_id' => $sessionId,
            'user_id'    => $userId,
            'status'     => $status
        ];
    }

    /**
     * Authoritative profile KYC update
     */
    public function updateProfileKyc(string $userId, string $status, ?string $sessionId = null, ?string $verifiedAt = null): void
    {
        try {
            $now = date('c');
            $vAt = ($status === 'Approved' || $status === 'approved') ? ($verifiedAt ?: $now) : null;

            // Update local SQLite
            $pStmt = $this->pdo->prepare("
                UPDATE profiles SET
                    kyc_status = :status,
                    kyc_session_id = COALESCE(:session_id, kyc_session_id),
                    kyc_verified_at = COALESCE(:v_at, kyc_verified_at),
                    updated_at = :updated_at
                WHERE id = :id OR LOWER(username) = LOWER(:username)
            ");
            $pStmt->execute([
                ':status'     => $status,
                ':session_id' => $sessionId,
                ':v_at'       => $vAt,
                ':updated_at' => $now,
                ':id'         => $userId,
                ':username'   => $userId
            ]);

            // Sync with Supabase profiles table if available
            if (function_exists('sb_admin_patch')) {
                $patch = ['kyc_status' => $status];
                if ($sessionId) $patch['kyc_session_id'] = $sessionId;
                if ($vAt) $patch['kyc_verified_at'] = $vAt;
                sb_admin_patch("profiles?id=eq.{$userId}", $patch);
            }
        } catch (Exception $e) {
            $this->logError("Failed to update profile KYC status: " . $e->getMessage());
        }
    }

    /**
     * Get active or latest verification record for user
     */
    public function getUserKycStatus(string $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM kyc_verifications
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // Check profiles table fallback
            $pStmt = $this->pdo->prepare("SELECT kyc_status, kyc_verified_at, kyc_session_id FROM profiles WHERE id = :uid OR LOWER(username) = LOWER(:uid) LIMIT 1");
            $pStmt->execute([':uid' => $userId]);
            $p = $pStmt->fetch(PDO::FETCH_ASSOC);
            if ($p && !empty($p['kyc_status'])) {
                return [
                    'status'          => $p['kyc_status'],
                    'session_id'      => $p['kyc_session_id'] ?? null,
                    'created_at'      => null,
                    'completed_at'    => $p['kyc_verified_at'] ?? null,
                    'verification_url'=> null
                ];
            }
            return null;
        }

        return $record;
    }

    /**
     * Admin manual status override
     */
    public function adminOverrideStatus(string $userId, string $newStatus, string $reason, string $adminId = 'admin'): array
    {
        $now = date('c');
        $validStatuses = ['Approved', 'Declined', 'In Review', 'Not Started', 'Expired', 'Resubmitted'];
        if (!in_array($newStatus, $validStatuses, true)) {
            return ['success' => false, 'error' => 'Invalid status value.'];
        }

        // Get previous status
        $prev = $this->getUserKycStatus($userId);
        $prevStatus = $prev['status'] ?? 'Not Started';

        // Update profile
        $this->updateProfileKyc($userId, $newStatus);

        // Update latest verification record
        $stmt = $this->pdo->prepare("
            UPDATE kyc_verifications SET
                status = :new_status,
                review_notes = :reason,
                completed_at = CASE WHEN :new_status = 'Approved' THEN :now ELSE completed_at END,
                updated_at = :now
            WHERE id = (
                SELECT id FROM kyc_verifications
                WHERE user_id = :uid
                ORDER BY created_at DESC LIMIT 1
            )
        ");
        $stmt->execute([
            ':new_status' => $newStatus,
            ':reason'     => "Admin override: $reason",
            ':now'        => $now,
            ':uid'        => $userId
        ]);

        // Record in audit log
        $auditId = 'k_audit_' . bin2hex(random_bytes(10));
        $aStmt = $this->pdo->prepare("
            INSERT INTO kyc_audit_logs (
                id, user_id, admin_id, action, previous_status, new_status, reason, created_at
            ) VALUES (
                :id, :user_id, :admin_id, 'manual_status_override', :prev_status, :new_status, :reason, :created_at
            )
        ");
        $aStmt->execute([
            ':id'          => $auditId,
            ':user_id'     => $userId,
            ':admin_id'    => $adminId,
            ':prev_status' => $prevStatus,
            ':new_status'  => $newStatus,
            ':reason'      => $reason,
            ':created_at'  => $now
        ]);

        return [
            'success'         => true,
            'user_id'         => $userId,
            'previous_status' => $prevStatus,
            'new_status'      => $newStatus,
            'audit_id'        => $auditId
        ];
    }

    /**
     * Safe internal logging without exposing credentials or sensitive biometric data
     */
    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/kyc_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        // Redact any occurrences of API key or secret
        $cleanMessage = str_replace(
            [$this->apiKey, $this->webhookSecret],
            ['[REDACTED_API_KEY]', '[REDACTED_SECRET]'],
            $message
        );
        @file_put_contents($logFile, "[$timestamp] $cleanMessage\n", FILE_APPEND | LOCK_EX);
    }
}
