<?php
/**
 * Migration: Create KYC Verification tables and update profiles
 * BM FOREX HUB — Didit API v3 KYC Integration
 */
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../engine_config.php';

try {
    $pdo = getMarketPDO();
    echo "Applying KYC schema migration...\n";

    // 1. Create kyc_verifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kyc_verifications (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            session_id TEXT NOT NULL UNIQUE,
            workflow_id TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'Not Started',
            vendor_data TEXT,
            verification_url TEXT,
            decision TEXT,
            decline_reasons TEXT,
            review_notes TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            completed_at TEXT
        );

        CREATE INDEX IF NOT EXISTS idx_kyc_user_id ON kyc_verifications(user_id);
        CREATE INDEX IF NOT EXISTS idx_kyc_status ON kyc_verifications(status);
        CREATE INDEX IF NOT EXISTS idx_kyc_session_id ON kyc_verifications(session_id);
        CREATE INDEX IF NOT EXISTS idx_kyc_created_at ON kyc_verifications(created_at);
    ");

    // 2. Create kyc_webhook_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kyc_webhook_logs (
            id TEXT PRIMARY KEY,
            event_id TEXT UNIQUE,
            session_id TEXT,
            event_type TEXT,
            status TEXT,
            payload_hash TEXT,
            processed_at TEXT,
            is_duplicate INTEGER DEFAULT 0,
            created_at TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_kyc_webhook_event_id ON kyc_webhook_logs(event_id);
        CREATE INDEX IF NOT EXISTS idx_kyc_webhook_session_id ON kyc_webhook_logs(session_id);
    ");

    // 3. Create kyc_audit_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kyc_audit_logs (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            admin_id TEXT,
            action TEXT NOT NULL,
            previous_status TEXT,
            new_status TEXT,
            reason TEXT,
            created_at TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_kyc_audit_user_id ON kyc_audit_logs(user_id);
    ");

    // 4. Safely add KYC columns to profiles if they don't already exist
    $profileCols = $pdo->query("PRAGMA table_info(profiles)")->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = array_map(function($c) { return strtolower($c['name']); }, $profileCols);

    if (!in_array('kyc_status', $existingCols, true)) {
        $pdo->exec("ALTER TABLE profiles ADD COLUMN kyc_status TEXT DEFAULT 'Not Started'");
        echo "Added kyc_status to profiles.\n";
    }
    if (!in_array('kyc_verified_at', $existingCols, true)) {
        $pdo->exec("ALTER TABLE profiles ADD COLUMN kyc_verified_at TEXT");
        echo "Added kyc_verified_at to profiles.\n";
    }
    if (!in_array('kyc_session_id', $existingCols, true)) {
        $pdo->exec("ALTER TABLE profiles ADD COLUMN kyc_session_id TEXT");
        echo "Added kyc_session_id to profiles.\n";
    }

    echo "[SUCCESS] KYC verification tables and schema migration applied successfully.\n";
} catch (Exception $e) {
    echo "[ERROR] KYC migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
