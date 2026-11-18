# BM FOREX HUB — Core Platform Documentation

Production Website: [https://bmforexhub.exchange](https://bmforexhub.exchange)  
Repository: [https://github.com/gackstone/BM](https://github.com/gackstone/BM)

---

## 1. Platform Overview

**BM FOREX HUB** is an integrated financial technology, trading education, and algorithmic market intelligence platform. Designed for retail traders, institutional analysts, and managed portfolio clients, the platform bridges the gap between professional-grade institutional trading techniques and retail execution.

### Primary Purpose
BM FOREX HUB addresses several key obstacles faced by modern forex and digital asset market participants:
- **Information Asymmetry:** Delivering institutional-level Smart Money Concepts (SMC) order-flow intelligence, higher-timeframe trend filters, and automated supply/demand zone detection.
- **Fragmented Risk Management:** Providing unified risk calculators, lot-sizing tools, live currency strength meters, and trade journaling.
- **Account Automation & Scaling:** Offering secure MetaTrader 5 (MT5) copy-trading infrastructure and the BM Elites managed investment circle.
- **Regulatory Compliance & Identity Protection:** Operating a native Know Your Customer (KYC) identity verification pipeline powered by the Didit API v3.
- **Regional Payment Friction:** Supporting multi-currency fiat pay-ins (Kenyan Shilling M-Pesa STK push and international card processing via Kora) alongside automated cryptocurrency onramp and offramp settlements (via Kotani Pay).

---

## 2. Products and Services

The platform provides structured packages across trading signals, algorithmic indicators, mentorship, copy-trading, and cryptocurrency exchange:

### A. Trading Signals & Market Intelligence
- **Grid Signals:** Algorithmic grid-based trade recommendations providing explicit entry points, stop-loss levels, and tiered take-profit targets (TP1, TP2, TP3).
  - *Monthly Access:* **$25.00 USD** / 30-day billing cycle.
  - *Lifetime Access:* **$499.00 USD** one-time settlement.
- **Featured Signals & Analyst Commentary:** Live trade alerts covering major forex pairs (EURUSD, GBPUSD, USDJPY), commodities (XAUUSD / Gold), indices (NAS100, US30), and cryptocurrencies (BTCUSD).

### B. Trading Indicators (BM Quantum Edge / Pine Script)
Institutional technical analysis indicators built for the TradingView platform:
- **Silver Plan ($25.00 USD / 3,250 KES per month):** Includes the TrV Advanced Multi-Engine Indicator, higher-timeframe trend filter, market structure detection, automated supply/demand zones, smart entry signals, and trading sessions overlay.
- **Gold Plan ($45.00 USD / 5,850 KES per month):** Everything in Silver plus curated live trade signals, buy/sell alerts, risk-to-reward levels, and priority support.
- **VIP Plan ($75.00 USD / 9,750 KES per month):** Full suite access including the BM Forex AI Assistant, advanced signal dashboard, multi-symbol watchlist, session heatmap, trade journal, VIP WhatsApp community, and a dedicated account manager.
- **BM Quantum Edge One-Time License:** Permanent lifetime access license at **$299.00 USD**.

### C. Forex Academy & Live Masterclasses
Comprehensive educational tracks focusing on institutional order blocks, liquidity sweeps, fair value gaps (FVG), and risk discipline:
- **Online Masterclasses ($399.00 USD):** Live Google Meet interactive cohorts, digital curriculum access, recorded replay archives, and downloadable coursework.
- **Physical Masterclasses ($599.00 USD):** Intensive in-person practical trading bootcamps, physical classroom trading labs, and direct mentor supervision.

### D. Copy Trading Integration
- **Price:** **$249.00 USD** lifetime account integration.
- **Functionality:** Automated trade replication from verified master strategies to user MetaTrader 5 accounts. User MT5 credentials are encrypted client-to-server and secured at rest using AES-256-CBC and HMAC-SHA256 authentication.

### E. BM Elites Trading Circle (Managed Model)
Exclusive managed capital participation tiers with formal electronic enrollment terms:
- **Starter Tier:** $1,000.00 USD
- **Intermediate Tier:** $2,000.00 USD
- **Advanced Tier:** $3,000.00 USD
- **Professional Tier:** $5,000.00 USD
- **Premium Tier:** $6,000.00 USD
- **Elite Tier:** $10,000.00 USD

### F. Cryptocurrency Buy/Sell & Swap Widget
- **Buy (Fiat-to-Crypto Onramp):** Users purchase stablecoins (USDT) or native crypto using M-Pesa or card payments via Kora Pay-ins; Kotani Pay delivers the assets directly to the buyer's external blockchain wallet.
- **Sell (Crypto-to-Fiat Offramp):** Kotani Pay issues a dedicated temporary blockchain deposit address; upon network confirmation, fiat payouts are disbursed directly to the user's mobile money or bank account.

### G. Risk & Calculation Tools
Integrated calculators accessible on [quick_tools.php](quick_tools.php):
- **Pip Calculator:** Real-time pip valuation based on contract sizing, currency pairs, and account base currency.
- **Lot Size & Risk Calculator:** Computes exact position volume given account equity, risk percentage, and stop-loss distance.
- **Margin Calculator:** Evaluates initial margin obligations based on leverage and contract specifications.
- **Position Size & Profit/Loss Calculator:** Pre-execution risk-to-reward ratio modeling.
- **Live Currency Converter:** Real-time multi-currency exchange rates including KES, USD, EUR, GBP, BTC, and Gold (XAU).

---

## 3. Platform Architecture & Tech Stack

BM FOREX HUB operates on a hybrid architecture designed for performance, resilience, and operational security:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Client Layer (Browser / PWA)                    │
│   Vanilla JS, Modern Responsive CSS, WebSocket Feeds, Mobile PWA       │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │ HTTPS / REST JSON
┌──────────────────────────────────▼─────────────────────────────────────┐
│                       Application & API Layer (PHP 8.2)                │
│  ├─ Authentication Middleware (Supabase JWT + Session Handler)         │
│  ├─ Services Layer (Didit, Kora, Kotani, Mail, CopyTrading, AI)        │
│  ├─ Background Workers (Email Queue Worker, Session Sync Engine)       │
│  └─ Admin Portal & Audit Subsystem (/admin)                            │
└──────────────────┬───────────────────────────────┬─────────────────────┘
                   │                               │
┌──────────────────▼──────────────┐ ┌──────────────▼─────────────────────┐
│     Cloud Auth & Storage        │ │    High-Performance Local DB       │
│      Supabase PostgreSQL        │ │          SQLite v3                 │
│  (Auth, User Profiles, RLS)     │ │  (KYC, Audit Logs, Webhook Logs,   │
│                                 │ │   Indicator Access, Email Queue)   │
└─────────────────────────────────┘ └────────────────────────────────────┘
```

### Technology Breakdown
- **Runtime & Language:** PHP 8.2+ with Strict Typing and Object-Oriented Service Architecture.
- **Frontend:** Modern Vanilla JavaScript (ES6+), CSS Custom Properties, Responsive Flex/Grid Layouts, Progressive Web App (PWA) Service Worker (`sw.js`).
- **Database Architecture:**
  - *Cloud Master:* Supabase (PostgreSQL with Row Level Security) for user authentication, global profile records, and subscription state.
  - *Local High-Throughput Engine:* SQLite 3 (`storage/database.sqlite`) for atomic local KYC session tracking, webhook idempotency logs, administrative audit trails, and email queue management.
- **External Integration Gateways:**
  - *Identity Verification (KYC):* Didit API v3 (`https://verification.didit.me`).
  - *Fiat Payments:* Kora API (`https://api.korapay.com`) for M-Pesa STK pushes and debit/credit cards.
  - *Crypto Settlement:* Kotani Pay API v3 (`https://integrator.kotanipay.com`).
  - *Email Infrastructure:* Multi-driver mail service supporting SMTP (Hostinger), Resend, Brevo, SendGrid, Amazon SES, and Mailgun.
  - *Market Data:* Real-time Gold/Forex feeds and TradingView chart widgets.

---

## 4. Directory Structure

```
├── admin/                         # Administrative Portal
│   ├── api/                       # Admin REST endpoints (KYC, Subscriptions, Users, Contacts)
│   ├── css/                       # Admin theme stylesheets (Dark UI, Data Tables, WYSIWYG)
│   ├── js/                        # Admin application controllers
│   ├── views/                     # Admin tab views (KYC verifications, Copy traders, Subscriptions)
│   ├── config.php                 # Admin database and session bootstrap
│   ├── crypto.php                 # Crypto settlement provider dashboard
│   ├── indicator.php              # Indicator subscriber access management
│   └── index.php                  # Central Admin Dashboard
├── api/                           # Public & Protected REST API Endpoints
│   ├── admin/                     # Administrative API bridges
│   ├── kyc/                       # KYC verification endpoints (create-session.php, status.php)
│   ├── webhooks/                  # Upstream webhook receivers (didit.php)
│   ├── crypto-buy.php             # Crypto onramp initiation
│   ├── crypto-sell.php            # Crypto offramp initiation
│   ├── deposit.php                # Kora M-Pesa STK push & card pay-in handler
│   ├── didit-config.php           # Didit credentials validator & constant loader
│   ├── indicator-subscribe.php    # TradingView indicator plan checkout
│   ├── kora-webhook.php           # Kora payment notification listener
│   ├── kotani-webhook.php         # Kotani crypto settlement notification listener
│   └── register-user.php          # User registration endpoint
├── app/                           # Core Backend Application Layer
│   ├── Config/                    # Service configurations (ai.php, crypto.php, mail.php)
│   ├── Controllers/               # Business logic controllers
│   ├── Models/                    # Data models (AIConversation, AIKnowledge, ContactExport)
│   ├── Services/                  # Business service layer
│   │   ├── CopyTradingService.php # MT5 encryption and account allocation
│   │   ├── CryptoSettlementService.php # Crypto buy/sell orchestration
│   │   ├── DiditService.php       # Official Didit API v3 integration client
│   │   ├── EmailQueueService.php  # Asynchronous bulk broadcast dispatcher
│   │   ├── KotaniPayService.php   # Kotani Pay v3 API client
│   │   ├── KycAccessService.php   # Centralized compliance gatekeeper
│   │   ├── MailService.php        # Multi-provider email abstraction
│   │   └── OtpService.php         # Cryptographic OTP generator and rate limiter
│   ├── Templates/                 # Branded HTML email templates (OTP, Broadcasts)
│   └── Workers/                   # Background queue workers (EmailWorker.php)
├── components/                    # Reusable frontend layout components
│   ├── global_header.php          # Standard authenticated top navigation
│   ├── global_sidebar.php         # Collapsible dashboard sidebar
│   └── pwa_meta.php               # PWA icons, manifest, and meta declarations
├── migrations/                    # Database schema migrations (SQLite & SQL)
├── storage/                       # Local file storage (SQLite database, diagnostic logs)
│   ├── database.sqlite            # Primary SQLite database
│   └── logs/                      # System error & diagnostic logs
├── .env                           # Environment variables file (git-ignored)
├── .env.example                   # Template for environment configuration
├── .htaccess                      # Apache rewrite rules, security headers, fastCGI env pass
├── classes.php                    # Forex Academy curriculum and live class interface
├── copy_trading.php               # Copy trading account connection dashboard
├── crypto.php                     # Cryptocurrency buy/sell widget interface
├── engine_config.php              # Global application config, DB PDO factory & session bootstrap
├── index.php                      # User Dashboard (Trading overview, active services)
├── kyc.php                        # KYC verification portal
├── quick_tools.php                # Forex risk and calculation tools
├── subscribe.php                  # Platform subscription catalog and checkout
├── vercel.json                    # Vercel serverless deployment routing config
└── README.md                      # Platform documentation
```

---

## 5. Didit KYC Identity Verification Integration

BM FOREX HUB implements compliance verification using the official **Didit API v3**. The integration ensures users can verify their identity via biometric facial recognition and government-issued ID validation.

```
┌──────────────┐          ┌───────────────────────┐          ┌──────────────┐
│  Client UI   │          │ BM FOREX HUB Backend  │          │  Didit API   │
│  (kyc.php)   │          │ (create-session.php)  │          │   (v3/api)   │
└──────┬───────┘          └───────────┬───────────┘          └──────┬───────┘
       │                              │                             │
       │ 1. Click "Start Verification"│                             │
       ├─────────────────────────────►│                             │
       │                              │ 2. Authenticate User        │
       │                              │ 3. Check for Active Session │
       │                              │ 4. POST /v3/session/        │
       │                              ├────────────────────────────►│
       │                              │    Headers: x-api-key       │
       │                              │    Body: vendor_data=userId │
       │                              │                             │
       │                              │ 5. Return HTTP 201 Created  │
       │                              │◄────────────────────────────┤
       │                              │                             │
       │                              │ 6. Save session in SQLite   │
       │ 7. Return verification_url   │                             │
       │◄─────────────────────────────┤                             │
       │                              │                             │
       │ 8. Redirect browser to Didit │                             │
       ├───────────────────────────────────────────────────────────►│
       │                              │                             │
       │                              │      9. Webhook Event       │
       │                              │         (status.updated)    │
       │                              │◄────────────────────────────┤
       │                              │ 10. Verify HMAC-SHA256 Sig  │
       │                              │ 11. Reconcile Decision      │
       │                              │ 12. Update KYC & Profiles   │
```

### Key Workflow Components
1. **Session Creation (`POST api/kyc/create-session.php`):**
   - Authenticates the current user session via JWT / Bearer token.
   - Evaluates whether an unexpired active verification session exists in `kyc_verifications` (sessions are reused within 24 hours to prevent unnecessary remote charges).
   - If a new session is required, constructs a request to `https://verification.didit.me/v3/session/` using the server-side `x-api-key` header and passes the user ID as `vendor_data`.
   - Records the `session_id`, `verification_url`, and IP address in `kyc_verifications`.
   - Returns the verification URL to the frontend for redirection.
2. **Webhook Verification (`POST api/webhooks/didit.php`):**
   - Receives asynchronous status updates from Didit.
   - **Canonical JSON Normalization:** Keys are sorted alphabetically and compacted without escaping slashes or unicode.
   - **Signature Verification:** Computes HMAC-SHA256 over canonical JSON using `DIDIT_WEBHOOK_SECRET` and matches against `X-Signature-V2`.
   - **Replay Protection:** Rejects events with an `X-Timestamp` older than 300 seconds (5 minutes).
   - **Idempotency:** Checks `kyc_webhook_logs` to ensure duplicated event IDs are acknowledged safely without double processing.
   - **Status Propagation:** Updates `kyc_verifications` and synchronizes the user's `kyc_status` in both local SQLite `profiles` and cloud Supabase tables.
3. **Compliance Access Gating (`app/Services/KycAccessService.php`):**
   - High-risk operations (cryptocurrency purchases, withdrawals, copy-trading allocation, and large deposits) invoke `KycAccessService::checkAccess($userId, $action)`.
   - Operations fail closed (`allowed: false, kyc_required: true`) unless the user's status is officially marked as `Approved`.

---

## 6. Administrative Portal & Features

The Administrative Portal (`/admin`) provides full visibility and control over platform operations:

- **KYC Verification Management (`admin/views/kyc_verifications.php`):**
  - Live table of all user identity verification records.
  - Filter by verification status (`Approved`, `In Review`, `Declined`, `Pending`, `Expired`).
  - View Didit session IDs, verification timestamps, and associated user emails.
  - **Administrative Override:** Compliance officers can manually review documentation and record an override status (`Approved` / `Declined`) with a mandatory audit log entry.
- **User & Contact Management (`admin/api/user-management.php`, `admin/js/admin-contacts.js`):**
  - Unified contact directory displaying registered users, email addresses, phone numbers, and verification statuses.
  - Multi-format data exporter supporting UTF-8 BOM CSV and native Excel (`.xlsx`) spreadsheets with compliance logging.
- **Bulk Email Broadcasts (`admin/index.php?tab=notifications`):**
  - Dark-theme WYSIWYG editor for drafting community announcements.
  - Dynamic recipient filtering: All Users, Verified Users, Unverified Users, Active Users (logged in within 7 days).
  - Variable personalization tags: `{{firstName}}`, `{{email}}`, `{{unsubscribeLink}}`.
  - Non-blocking asynchronous dispatch via SQLite email queue worker.
- **Copy Trading Administration (`admin/views/copy_traders.php`):**
  - Monitor connected client MT5 accounts, master strategy assignments, and active lot multipliers.
  - Audit logs for connection status changes.
- **Indicator Subscriber Management (`admin/indicator.php`):**
  - Track TradingView usernames, subscription expiry dates, and automated invite/revoke status.

---

## 7. Local Development & Installation Setup

Follow these steps to configure a local development environment.

### Prerequisites
- **Operating System:** Windows (XAMPP / native), Linux (Ubuntu 22.04+), or macOS.
- **PHP Version:** PHP 8.2 or higher.
- **Required PHP Extensions:**
  - `php-pdo` and `php-pdo_sqlite`
  - `php-curl`
  - `php-openssl`
  - `php-mbstring`
  - `php-json`
- **Web Server:** Apache 2.4+ (with `mod_rewrite` and `mod_headers` enabled).
- **Composer:** Latest version (for autoloading and vendor dependencies).
- **Git:** Installed and configured.

### Step 1: Clone the Repository
```bash
git clone https://github.com/gackstone/BM.git "C:\xampp\htdocs\BM Forex"
cd "C:\xampp\htdocs\BM Forex"
```

### Step 2: Environment Configuration
Copy the template configuration file to `.env`:
```bash
cp .env.example .env
```
Open `.env` in your editor and configure your local environment parameters (see the Environment Variables section below).

### Step 3: Initialize Database & Run Migrations
Ensure the `storage/` directory and SQLite database file are writable:
```bash
# On Linux / macOS:
chmod -R 775 storage/
# On Windows: Ensure the user/IUSR has write access to storage/
```

Run database migrations to initialize tables and indexes:
```bash
php migrations/20261118_add_kyc_verifications.php
```

### Step 4: Configure Apache / Web Server
Ensure that your Apache configuration permits `.htaccess` overrides:
```apache
<Directory "C:/xampp/htdocs/BM Forex">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```
Verify that the `RewriteEngine` and `Header` modules are loaded in `httpd.conf`:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

### Step 5: Verify the Test Suite
Run the automated test suite to ensure the system and external API connectivity are functioning correctly:
```bash
php tests/test_didit_kyc_suite.php
```
You should see all 20 tests pass with zero failures:
```text
TEST RESULTS: Total = 20 | Passed = 20 | Failed = 0
ALL 20 MASTER ENGINEERING VERIFICATION TESTS PASSED!
```

---

## 8. Environment Variables & Configuration

Configure the application through the `.env` file located in the project root. Never commit secrets to version control.

| Variable Name | Required | Description | Example / Default |
|:---|:---:|:---|:---|
| `APP_ENV` | Yes | Application environment mode (`development`, `production`) | `production` |
| `APP_URL` | Yes | Base public domain URL of the application | `https://bmforexhub.exchange` |
| `ADMIN_EMAIL` | Yes | Primary administrator email for alerts | `info@admin.bmforexhub.exchange` |
| `SUPABASE_URL` | Yes | Supabase project REST URL | `https://your-project.supabase.co` |
| `SUPABASE_ANON` | Yes | Supabase public anonymous API key | `eyJhbGci...` |
| `SUPABASE_SERVICE` | Yes | Supabase private service role key | `eyJhbGci...` |
| `DIDIT_API_KEY` | Yes | Didit API v3 secret key (`sk_live_...`) | `sk_live_example123456789...` |
| `DIDIT_WORKFLOW_ID` | Yes | Didit published verification workflow UUID | `cd012ec6-a97c-492b-b9f8-634bbc9bbf03` |
| `DIDIT_WEBHOOK_SECRET` | Yes | Secret used to verify Didit webhook HMAC signatures | `sk_live_example123456789...` |
| `DIDIT_WEBHOOK_URL` | Yes | Public webhook callback URL for Didit | `https://bmforexhub.exchange/api/webhooks/didit` |
| `DIDIT_BASE_URL` | Yes | Base URL for Didit API requests | `https://verification.didit.me` |
| `KORA_PUBLIC_KEY` | Yes | Kora Pay merchant public API key | `pk_live_...` |
| `KORA_SECRET_KEY` | Yes | Kora Pay merchant secret key | `sk_live_...` |
| `KORA_ENCRYPTION_KEY` | Optional | Kora card encryption key | `kora_enc_...` |
| `KORA_BASE_URL` | Yes | Kora API base URL | `https://api.korapay.com` |
| `KORA_WEBHOOK_URL` | Yes | Kora payment notification webhook URL | `https://bmforexhub.exchange/api/kora-webhook.php` |
| `KOTANI_API_KEY` | Optional | Kotani Pay v3 API client key | `kotani_live_...` |
| `KOTANI_ENV` | Optional | Kotani environment (`sandbox` or `production`) | `production` |
| `KOTANI_WEBHOOK_SECRET`| Optional | Secret used to verify Kotani callback signatures | `kotani_whsec_...` |
| `MAIL_DRIVER` | Yes | Email driver (`smtp`, `resend`, `sendgrid`, `brevo`, `ses`) | `smtp` |
| `MAIL_HOST` | If SMTP | SMTP server host | `smtp.hostinger.com` |
| `MAIL_PORT` | If SMTP | SMTP port (typically 465 for SSL, 587 for TLS) | `465` |
| `MAIL_USERNAME` | If SMTP | SMTP account username | `notifications@bmforexhub.exchange` |
| `MAIL_PASSWORD` | If SMTP | SMTP account password | `your_smtp_password` |
| `MAIL_ENCRYPTION` | If SMTP | Protocol encryption (`ssl` or `tls`) | `ssl` |
| `COPY_TRADING_ENCRYPTION_KEY` | Yes | 32-byte secret for encrypting client MT5 passwords | `random_32_byte_hex_string` |

---

## 9. API Reference

All API routes communicate using JSON payloads and standard HTTP response status codes.

### Identity & KYC Verification
| Method | Route | Description | Auth Required |
|:---|:---|:---|:---:|
| `POST` | `/api/kyc/create-session.php` | Initializes a Didit KYC session and returns the URL | Bearer Token |
| `GET` | `/api/kyc/status.php` | Retrieves current user KYC verification status | Bearer Token |
| `POST` | `/api/webhooks/didit.php` | Ingests Didit verification updates via signed webhook | HMAC Header |

#### Example: Create KYC Session Response
```json
{
  "ok": true,
  "session_id": "803b185c-72a9-4402-99be-9205044a60a0",
  "url": "https://verify.didit.me/session/UXw7jsU1Nhxp",
  "status": "Not Started"
}
```

### Payments & Deposits
| Method | Route | Description | Auth Required |
|:---|:---|:---|:---:|
| `POST` | `/api/deposit.php` | Initiates M-Pesa STK push or Card payment via Kora | Bearer Token |
| `POST` | `/api/kora-webhook.php` | Webhook listener for Kora payment confirmation | Kora Signature |
| `GET` | `/api/subscription-status.php`| Checks user subscription and access permissions | Bearer Token |

### Cryptocurrency Settlement
| Method | Route | Description | Auth Required |
|:---|:---|:---|:---:|
| `GET` | `/api/crypto-prices.php` | Returns current market prices for supported assets | Public |
| `POST` | `/api/crypto-quote.php` | Computes a time-bound buy/sell conversion quote | Bearer Token |
| `POST` | `/api/crypto-buy.php` | Initiates fiat collection for crypto onramp | Bearer + KYC |
| `POST` | `/api/crypto-sell.php` | Generates deposit address for crypto offramp | Bearer + KYC |
| `POST` | `/api/kotani-webhook.php` | Ingests onramp/offramp blockchain settlement updates | Kotani Signature |

### Indicators & Academy
| Method | Route | Description | Auth Required |
|:---|:---|:---|:---:|
| `GET` | `/api/indicator-plans.php` | Lists available TradingView indicator tiers | Public |
| `POST` | `/api/indicator-subscribe.php`| Initiates indicator plan checkout | Bearer Token |
| `GET` | `/api/classes.php` | Fetches live class schedule and session status | Bearer Token |

### Administrative Endpoints
| Method | Route | Description | Auth Required |
|:---|:---|:---|:---:|
| `GET/POST`| `/api/admin/kyc-management.php` | Lists KYC verifications and applies manual overrides | Admin Session |
| `POST` | `/api/admin/notifications/bulk-send.php` | Dispatches bulk email campaign to queue | Admin Session |
| `GET` | `/admin/api/user-management.php`| Queries filtered contact directory for exports | Admin Session |

---

## 10. Security & Compliance Architecture

BM FOREX HUB maintains a security posture designed to safeguard client capital, personal identity data, and trading credentials:

- **Credential & Secret Protection:**
  - Client MT5 passwords stored for copy trading are encrypted at rest with `AES-256-CBC` combined with `HMAC-SHA256` integrity authentication.
  - Secret keys (API keys, webhook secrets, database credentials) are loaded server-side only from `.env` and are never exposed to browser bundles or public endpoints.
- **Webhook Signature Authentication:**
  - Didit webhooks require canonicalized JSON SHA256 HMAC signature verification (`X-Signature-V2`) with strict replay window mitigation (events older than 300 seconds are rejected).
  - Kora and Kotani webhooks validate cryptographic gateway signatures before mutating database records.
- **Fail-Closed KYC Compliance:**
  - Restricted financial functionality (crypto execution, large deposits, copy trading allocations) enforces fail-closed checks via `KycAccessService`. If status is missing or unverified, requests are halted with `HTTP 403 Forbidden`.
- **Brute-Force & Rate Limiting:**
  - One-Time Password (OTP) verification on registration and password recovery is limited to a maximum of 5 requests per hour per IP/email with a mandatory 60-second client-side cooldown.
- **Server Hardening:**
  - `.htaccess` directives explicitly forbid direct browser access to `.env`, `.sql`, `engine_config.php`, and the `storage/` directory.
  - Security headers enforced on every response: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.

---

## 11. Production Deployment

### Production Infrastructure Overview
- **Production Host:** Apache 2.4 / Nginx on dedicated cloud server or virtual private server (e.g., Hostinger / Ubuntu VPS).
- **Static & Edge Proxy:** Vercel edge deployment configured via [vercel.json](vercel.json) to proxy API requests to backend endpoints.

### Deployment Checklist
1. **Repository Synchronization:** Pull latest code to the server directory:
   ```bash
   git pull origin main
   ```
2. **Environment Variable Configuration:** Ensure production environment variables in `.env` are set with live production credentials.
3. **Database Permissions:** Confirm write permissions for the web server user (`www-data` or `nobody`):
   ```bash
   chown -R www-data:www-data storage/
   chmod -R 775 storage/
   ```
4. **Register External Webhooks:**
   - In the **Didit Console**, register: `https://bmforexhub.exchange/api/webhooks/didit`
   - In the **Kora Dashboard**, register: `https://bmforexhub.exchange/api/kora-webhook.php`
   - In the **Kotani Dashboard**, register: `https://bmforexhub.exchange/api/kotani-webhook.php`
5. **Background Workers (Cron Jobs):**
   Configure cron jobs on the production host to process the asynchronous email broadcast queue and perform session reconciliation:
   ```cron
   # Process email queue every minute
   * * * * * /usr/bin/php /var/www/html/app/Workers/EmailWorker.php > /dev/null 2>&1
   ```

---

## 12. Troubleshooting & Operations

### Didit KYC 401 Unauthorized Errors
- **Symptom:** `Didit API authentication failed (invalid or unactivated API key)`.
- **Cause:** Incorrect API key or truncated key string. Didit v3 256-bit live keys are 51 characters in total length (`sk_live_...` followed by 43 Base64URL characters).
- **Resolution:** Verify `.env` contains the full 51-character key. Check `storage/logs/didit_diagnostics.log` for redacted wire-level HTTP response codes.

### Webhook Signature Mismatches (HTTP 403)
- **Symptom:** Incoming Didit webhooks rejected with `Webhook signature verification failed`.
- **Cause:** Payload modified in transit or secret mismatch.
- **Resolution:** Ensure the raw HTTP request body is passed to `canonicalizeJson()` without prior JSON decode/encode mutations. Verify `DIDIT_WEBHOOK_SECRET` matches the secret registered in the Didit portal.

### SQLite Database Locked Errors
- **Symptom:** `SQLSTATE[HY000]: General error: 5 database is locked`.
- **Cause:** Concurrent write locks held open or improper file permissions on `storage/database.sqlite`.
- **Resolution:** Verify WAL (Write-Ahead Logging) mode is active. Run:
  ```sql
  PRAGMA journal_mode = WAL;
  ```

### M-Pesa STK Push Timeout
- **Symptom:** Client initiates deposit but no prompt appears on the user's mobile device.
- **Cause:** Invalid MSISDN phone format or Kora gateway connectivity timeout.
- **Resolution:** Ensure the phone number is formatted with the international prefix without symbols (e.g., `2547XXXXXXXX`). Inspect Kora Pay response payloads in `storage/logs/`.

---

## 13. License & Disclaimer

**Copyright © 2026 BM FOREX HUB. All rights reserved.**

*Financial & Regulatory Disclaimer:*  
Trading foreign exchange (Forex), contracts for difference (CFDs), and digital assets carries a high level of risk and may not be suitable for all investors. The high degree of leverage can work against you as well as for you. Before deciding to trade or participate in any managed investment circle, you should carefully consider your investment objectives, level of experience, and risk appetite. Past performance is not indicative of future results. BM FOREX HUB is not a registered financial advisor or broker-dealer.
