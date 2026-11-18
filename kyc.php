<?php
require_once __DIR__ . '/engine_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>try{if(localStorage.getItem('bmTheme')==='light'){document.documentElement.setAttribute('data-theme','light')}}catch(e){}</script>
<link rel="stylesheet" href="css/theme-light.css?v=1">
<script src="js/theme.js" defer></script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/components/pwa_meta.php'; ?>
<title>Identity Verification (KYC) | BM FOREX HUB</title>
<link rel="icon" type="image/png" href="BM-ForexHub-Logo-Circle.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="basics.css?v=<?= filemtime('basics.css') ?>">
<link rel="stylesheet" href="css/motion.css?v=<?= filemtime('css/motion.css') ?>">
<link rel="stylesheet" href="css/mobile.css?v=<?= filemtime('css/mobile.css') ?>">
<style>
.kyc-shell {
  max-width: 820px;
  margin: 40px auto 60px;
  padding: 0 20px;
}
.kyc-card {
  background: linear-gradient(135deg, #151d2a 0%, #101722 100%);
  border: 1px solid #1e2d42;
  border-radius: 16px;
  padding: 32px 36px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.kyc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #1e2d42;
  padding-bottom: 24px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}
.kyc-title-box {
  display: flex;
  align-items: center;
  gap: 14px;
}
.kyc-icon-badge {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(22, 119, 255, 0.15);
  color: #1677FF;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.kyc-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 1.45rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
}
.kyc-subtitle {
  color: #8fa3b8;
  font-size: 0.84rem;
  margin-top: 4px;
}
.kyc-status-pill {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: .02em;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-transform: uppercase;
}
.status-pill--approved {
  background: rgba(34, 197, 94, 0.15);
  color: #22C55E;
  border: 1px solid rgba(34, 197, 94, 0.35);
}
.status-pill--in_review {
  background: rgba(240, 180, 41, 0.15);
  color: #F0B429;
  border: 1px solid rgba(240, 180, 41, 0.35);
}
.status-pill--in_progress {
  background: rgba(22, 119, 255, 0.15);
  color: #1677FF;
  border: 1px solid rgba(22, 119, 255, 0.35);
}
.status-pill--declined {
  background: rgba(239, 68, 68, 0.15);
  color: #EF4444;
  border: 1px solid rgba(239, 68, 68, 0.35);
}
.status-pill--unverified {
  background: rgba(143, 163, 184, 0.15);
  color: #8fa3b8;
  border: 1px solid rgba(143, 163, 184, 0.3);
}

.kyc-steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin: 28px 0;
}
.kyc-step {
  background: rgba(255,255,255,0.02);
  border: 1px solid #1e2d42;
  border-radius: 12px;
  padding: 18px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.kyc-step-num {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: rgba(22, 119, 255, 0.2);
  color: #1677FF;
  font-weight: 700;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.kyc-step-title {
  color: #fff;
  font-size: 0.88rem;
  font-weight: 600;
}
.kyc-step-desc {
  color: #8fa3b8;
  font-size: 0.77rem;
  line-height: 1.45;
}

.kyc-info-box {
  background: rgba(22, 119, 255, 0.05);
  border: 1px solid rgba(22, 119, 255, 0.2);
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 28px;
  font-size: 0.83rem;
  color: #cdd6e3;
  line-height: 1.55;
}
.kyc-info-box strong { color: #fff; }

.kyc-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}
.btn-kyc-primary {
  background: #1677FF;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px 24px;
  font-weight: 600;
  font-size: 0.92rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all .2s;
  text-decoration: none;
}
.btn-kyc-primary:hover {
  background: #0958d9;
  transform: translateY(-1px);
}
.btn-kyc-secondary {
  background: rgba(255,255,255,0.05);
  color: #cdd6e3;
  border: 1px solid #1e2d42;
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 500;
  font-size: 0.88rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all .2s;
  text-decoration: none;
}
.btn-kyc-secondary:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}
.kyc-alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.84rem;
  margin-bottom: 20px;
  display: none;
}
.kyc-alert--error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #ff6b6b;
}
.kyc-alert--success {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #22c55e;
}
@media (max-width: 640px) {
  .kyc-steps { grid-template-columns: 1fr; }
  .kyc-card { padding: 22px 18px; }
  .kyc-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body class="has-sidebar">

<?php $activeNav = 'kyc'; include __DIR__ . '/components/global_sidebar.php'; ?>

<div class="dash-main-wrap">
  <?php include __DIR__ . '/components/global_header.php'; ?>

  <div class="kyc-shell">
    <div class="kyc-card">
      <div class="kyc-header">
        <div class="kyc-title-box">
          <div class="kyc-icon-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div>
            <h1 class="kyc-title">Identity Verification (KYC)</h1>
            <div class="kyc-subtitle">Powered by Didit official identity verification gateway</div>
          </div>
        </div>
        <div id="kycStatusBadgeContainer">
          <span class="kyc-status-pill status-pill--unverified" id="kycStatusPill">Loading...</span>
        </div>
      </div>

      <div id="kycAlert" class="kyc-alert"></div>

      <div id="kycIntroSection">
        <p style="color:#cdd6e3;font-size:0.9rem;line-height:1.6;margin-top:0;">
          In compliance with international financial anti-money laundering (AML) regulations and to ensure maximum protection for all traders, BM FOREX HUB requires identity verification for full access to live trading signals, deposits, copy trading, and capital management.
        </p>

        <div class="kyc-steps">
          <div class="kyc-step">
            <div class="kyc-step-num">1</div>
            <div class="kyc-step-title">Government ID</div>
            <div class="kyc-step-desc">Prepare a clear photo of your National ID, Passport, or Driver&rsquo;s License.</div>
          </div>
          <div class="kyc-step">
            <div class="kyc-step-num">2</div>
            <div class="kyc-step-title">Liveness Check</div>
            <div class="kyc-step-desc">Complete a quick 5-second 3D facial selfie through your camera.</div>
          </div>
          <div class="kyc-step">
            <div class="kyc-step-num">3</div>
            <div class="kyc-step-title">Instant Approval</div>
            <div class="kyc-step-desc">Automated AI compliance screening verifies your identity in under 2 minutes.</div>
          </div>
        </div>

        <div class="kyc-info-box">
          <strong>Privacy &amp; Data Security Notice:</strong><br>
          Your verification is processed directly through Didit&rsquo;s encrypted biometric gateway. BM FOREX HUB does not store your raw biometric facial scans or identity documents.
        </div>
      </div>

      <div class="kyc-actions">
        <button type="button" class="btn-kyc-primary" id="startKycBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          <span id="startKycBtnText">Start Identity Verification</span>
        </button>

        <button type="button" class="btn-kyc-secondary" id="refreshKycBtn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh Status
        </button>

        <a href="index.php" class="btn-kyc-secondary">Back to Dashboard</a>
      </div>

      <div id="kycMetaDetails" style="margin-top:24px;padding-top:18px;border-top:1px solid #1e2d42;color:#8fa3b8;font-size:0.77rem;display:none;">
        <div><strong>Session Reference:</strong> <span id="kycMetaSessionId">--</span></div>
        <div style="margin-top:4px;"><strong>Last Updated:</strong> <span id="kycMetaUpdatedAt">--</span></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>
<script src="js/auth.js?v=<?= filemtime('js/auth.js') ?>"></script>
<script>
(async function() {
  'use strict';
  const { user, session } = await BMAuth.getSession();
  if (!user) {
    window.location.href = 'login.php';
    return;
  }

  const token = session?.access_token || '';
  const statusPill = document.getElementById('kycStatusPill');
  const alertBox = document.getElementById('kycAlert');
  const startBtn = document.getElementById('startKycBtn');
  const startBtnText = document.getElementById('startKycBtnText');
  const refreshBtn = document.getElementById('refreshKycBtn');
  const metaBox = document.getElementById('kycMetaDetails');
  const metaSession = document.getElementById('kycMetaSessionId');
  const metaUpdated = document.getElementById('kycMetaUpdatedAt');

  let activeVerificationUrl = null;
  let currentKycStatus = 'Not Started';

  function showAlert(msg, isError) {
    alertBox.textContent = msg;
    alertBox.className = 'kyc-alert ' + (isError ? 'kyc-alert--error' : 'kyc-alert--success');
    alertBox.style.display = 'block';
  }

  function hideAlert() {
    alertBox.style.display = 'none';
  }

  function renderStatusPill(status) {
    currentKycStatus = status;
    const s = (status || 'Not Started').toLowerCase();
    statusPill.className = 'kyc-status-pill';

    if (s === 'approved') {
      statusPill.className += ' status-pill--approved';
      statusPill.innerHTML = '&#10003; Approved &bull; Verified';
      startBtn.style.display = 'none';
      showAlert('Congratulations! Your identity has been verified and approved.', false);
    } else if (s === 'in review' || s === 'pending') {
      statusPill.className += ' status-pill--in_review';
      statusPill.innerHTML = '&#9203; Under Review';
      startBtnText.textContent = 'Verification Submitted (Under Review)';
      startBtn.disabled = true;
      showAlert('Your identity documents are currently under review by our compliance team.', false);
    } else if (s === 'in progress') {
      statusPill.className += ' status-pill--in_progress';
      statusPill.innerHTML = '&#9881; In Progress';
      startBtnText.textContent = 'Continue Verification';
    } else if (s === 'declined' || s === 'failed') {
      statusPill.className += ' status-pill--declined';
      statusPill.innerHTML = '&#10007; Declined';
      startBtnText.textContent = 'Retry Verification';
      showAlert('Your previous verification was declined. Please click "Retry Verification" to submit valid documents.', true);
    } else if (s === 'expired' || s === 'abandoned') {
      statusPill.className += ' status-pill--declined';
      statusPill.innerHTML = '&#9888; Expired';
      startBtnText.textContent = 'Start New Verification';
    } else {
      statusPill.className += ' status-pill--unverified';
      statusPill.innerHTML = 'Not Verified';
      startBtnText.textContent = 'Start Identity Verification';
    }
  }

  async function fetchStatus() {
    try {
      const resp = await fetch('api/kyc/status.php', {
        headers: { 'Authorization': 'Bearer ' + token }
      });
      if (!resp.ok) {
        statusPill.textContent = 'Unverified';
        return;
      }
      const data = await resp.json();
      renderStatusPill(data.kyc_status);
      activeVerificationUrl = data.verification_url;

      if (data.session_id) {
        metaBox.style.display = 'block';
        metaSession.textContent = data.session_id;
        metaUpdated.textContent = data.completed_at || data.created_at || 'Recently';
      }
    } catch (e) {
      console.error('KYC status load error:', e);
    }
  }

  async function handleStartVerification(forceRetry) {
    hideAlert();
    startBtn.disabled = true;
    startBtnText.textContent = 'Initializing Didit session...';

    try {
      const resp = await fetch('api/kyc/create-session.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({ retry: !!forceRetry })
      });

      const data = await resp.json();
      startBtn.disabled = false;

      if (!resp.ok || !data.ok) {
        startBtnText.textContent = 'Start Identity Verification';
        showAlert(data.error || 'Failed to initialize Didit verification session. Please contact support.', true);
        return;
      }

      if (data.url) {
        startBtnText.textContent = 'Redirecting to Didit...';
        window.location.href = data.url;
      } else if (data.already_verified) {
        renderStatusPill('Approved');
      }
    } catch (err) {
      startBtn.disabled = false;
      startBtnText.textContent = 'Start Identity Verification';
      showAlert('Network error while connecting to verification gateway. Please check your connection.', true);
    }
  }

  startBtn.addEventListener('click', function() {
    const isRetry = ['declined', 'failed', 'expired', 'abandoned'].includes(currentKycStatus.toLowerCase());
    handleStartVerification(isRetry);
  });

  refreshBtn.addEventListener('click', function() {
    refreshBtn.disabled = true;
    fetchStatus().finally(() => {
      setTimeout(() => { refreshBtn.disabled = false; }, 1000);
    });
  });

  await fetchStatus();
})();
</script>
</body>
</html>
