<?php
namespace App\Services;

use PDO;

/**
 * KYC Access Control Service
 * Centralized, server-side compliance enforcement for BM FOREX HUB
 */
class KycAccessService
{
    private PDO $pdo;
    private DiditService $diditService;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } elseif (function_exists('getMarketPDO')) {
            $this->pdo = getMarketPDO();
        } else {
            $dbPath = defined('DB_PATH') ? DB_PATH : (__DIR__ . '/../../storage/database.sqlite');
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $this->diditService = new DiditService($this->pdo);
    }

    /**
     * Check if a user has an approved KYC status
     */
    public function isKycApproved(string $userId, ?string $userEmail = null): bool
    {
        // 1. Permanent Admin whitelist bypass
        if (!empty($userEmail) && function_exists('bm_has_permanent_access') && bm_has_permanent_access($userEmail)) {
            return true;
        }

        // 2. Query latest KYC record
        $stmt = $this->pdo->prepare("
            SELECT status FROM kyc_verifications
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $status = $stmt->fetchColumn();

        if ($status && strtolower(trim($status)) === 'approved') {
            return true;
        }

        // 3. Fallback check profiles table
        $pStmt = $this->pdo->prepare("
            SELECT kyc_status FROM profiles
            WHERE id = :uid OR LOWER(username) = LOWER(:username)
            LIMIT 1
        ");
        $pStmt->execute([':uid' => $userId, ':username' => $userId]);
        $pStatus = $pStmt->fetchColumn();

        return ($pStatus && strtolower(trim($pStatus)) === 'approved');
    }

    /**
     * Get authoritative KYC status string
     */
    public function getKycStatus(string $userId, ?string $userEmail = null): string
    {
        if (!empty($userEmail) && function_exists('bm_has_permanent_access') && bm_has_permanent_access($userEmail)) {
            return 'Approved';
        }

        $record = $this->diditService->getUserKycStatus($userId);
        if ($record && !empty($record['status'])) {
            return $record['status'];
        }

        return 'Not Started';
    }

    /**
     * Check access permission for a compliance-sensitive operation
     * Operations: 'deposit', 'withdrawal', 'trading_account', 'crypto_trading', 'copy_trading'
     */
    public function checkAccess(string $userId, string $operation, ?string $userEmail = null): array
    {
        if (!empty($userEmail) && function_exists('bm_has_permanent_access') && bm_has_permanent_access($userEmail)) {
            return [
                'allowed'     => true,
                'status'      => 'Approved',
                'reason'      => 'Admin exemption',
                'can_retry'   => false
            ];
        }

        $status = $this->getKycStatus($userId, $userEmail);
        $normStatus = strtolower(trim($status));

        // Approved users have full access
        if ($normStatus === 'approved') {
            return [
                'allowed'     => true,
                'status'      => 'Approved',
                'reason'      => null,
                'can_retry'   => false
            ];
        }

        // Under review or in progress
        if ($normStatus === 'in review' || $normStatus === 'in progress') {
            return [
                'allowed'     => false,
                'status'      => $status,
                'reason'      => 'Your identity verification is currently under review by our compliance team. Access will be unlocked once approved.',
                'can_retry'   => false,
                'kyc_required'=> true
            ];
        }

        // Declined
        if ($normStatus === 'declined' || $normStatus === 'failed') {
            return [
                'allowed'     => false,
                'status'      => 'Declined',
                'reason'      => 'Your identity verification was declined. Please review the requirements and resubmit with valid documents.',
                'can_retry'   => true,
                'kyc_required'=> true
            ];
        }

        // Expired or Abandoned
        if ($normStatus === 'expired' || $normStatus === 'abandoned') {
            return [
                'allowed'     => false,
                'status'      => 'Expired',
                'reason'      => 'Your verification session expired. Please start a new verification session to continue.',
                'can_retry'   => true,
                'kyc_required'=> true
            ];
        }

        // Not Started / Verification Required
        return [
            'allowed'     => false,
            'status'      => 'Verification Required',
            'reason'      => 'Identity verification (KYC) is required to access ' . str_replace('_', ' ', $operation) . ' on BM FOREX HUB.',
            'can_retry'   => true,
            'kyc_required'=> true
        ];
    }
}
