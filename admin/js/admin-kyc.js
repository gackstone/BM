/* ============================================================
   BM Forex Hub — Admin KYC Verifications Management JS
   Powered by Didit Identity Verification Gateway
   ============================================================ */
(function() {
  'use strict';

  var currentPage = 1;
  var currentLimit = 25;
  var totalRecords = 0;
  var currentSearch = '';
  var currentStatus = 'all';

  function esc(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
  }

  function getStatusBadge(status) {
    var s = (status || 'Not Started').toLowerCase();
    if (s === 'approved') {
      return '<span style="background:rgba(34,197,94,0.15);color:#22C55E;border:1px solid rgba(34,197,94,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">APPROVED</span>';
    } else if (s === 'in review' || s === 'pending') {
      return '<span style="background:rgba(240,180,41,0.15);color:#F0B429;border:1px solid rgba(240,180,41,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">IN REVIEW</span>';
    } else if (s === 'in progress') {
      return '<span style="background:rgba(22,119,255,0.15);color:#1677FF;border:1px solid rgba(22,119,255,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">IN PROGRESS</span>';
    } else if (s === 'declined' || s === 'failed') {
      return '<span style="background:rgba(239,68,68,0.15);color:#EF4444;border:1px solid rgba(239,68,68,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">DECLINED</span>';
    } else if (s === 'expired' || s === 'abandoned') {
      return '<span style="background:rgba(249,115,22,0.15);color:#F97316;border:1px solid rgba(249,115,22,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">EXPIRED</span>';
    } else {
      return '<span style="background:rgba(143,163,184,0.15);color:#8fa3b8;border:1px solid rgba(143,163,184,0.3);padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:700;">' + esc(status.toUpperCase()) + '</span>';
    }
  }

  function loadKycVerifications(page) {
    if (page) currentPage = page;
    var tbody = document.getElementById('kycTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="table-empty">Loading KYC verifications...</td></tr>';

    var url = 'api/kyc-management.php?action=list&page=' + currentPage + '&limit=' + currentLimit;
    if (currentStatus && currentStatus !== 'all') url += '&status=' + encodeURIComponent(currentStatus);
    if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res.success) {
          tbody.innerHTML = '<tr><td colspan="6" class="table-empty" style="color:#ef4444">Failed: ' + esc(res.error || 'Server error') + '</td></tr>';
          return;
        }

        // Update stats
        if (res.summary) {
          document.getElementById('kycStatTotal').textContent = res.summary.total || 0;
          document.getElementById('kycStatApproved').textContent = res.summary.approved || 0;
          document.getElementById('kycStatInReview').textContent = res.summary.in_review || 0;
          document.getElementById('kycStatDeclined').textContent = res.summary.declined || 0;
          document.getElementById('kycStatPending').textContent = res.summary.pending || 0;
        }

        var rows = res.data || [];
        totalRecords = (res.pagination && res.pagination.total) || rows.length;

        if (rows.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="table-empty">No KYC verification records found.</td></tr>';
          updatePagination(0, 0);
          return;
        }

        var html = '';
        rows.forEach(function(row) {
          var userDisplay = esc(row.username || row.email || row.user_id);
          var fullName = (row.first_name || row.last_name) ? esc(row.first_name + ' ' + (row.last_name || '')) : '';
          var shortSession = esc(row.session_id ? (row.session_id.substring(0, 8) + '...' + row.session_id.substring(row.session_id.length - 4)) : '--');
          var createdStr = row.created_at ? new Date(row.created_at).toLocaleDateString() : '--';
          var completedStr = row.completed_at ? new Date(row.completed_at).toLocaleDateString() : '--';

          html += '<tr>'
            + '<td>'
              + '<div style="font-weight:600;color:#fff;">' + userDisplay + '</div>'
              + (fullName ? '<div style="font-size:0.75rem;color:#8fa3b8;">' + fullName + '</div>' : '')
              + '<div style="font-size:0.72rem;color:#5b6475;font-family:monospace;">ID: ' + esc(row.user_id) + '</div>'
            + '</td>'
            + '<td>'
              + '<span title="' + esc(row.session_id) + '" style="font-family:monospace;font-size:0.78rem;background:rgba(255,255,255,0.03);padding:3px 6px;border-radius:4px;border:1px solid #1e2d42;">' + shortSession + '</span>'
            + '</td>'
            + '<td>' + getStatusBadge(row.status) + '</td>'
            + '<td style="font-size:0.8rem;color:#8fa3b8;">' + createdStr + '</td>'
            + '<td style="font-size:0.8rem;color:#8fa3b8;">' + completedStr + '</td>'
            + '<td>'
              + '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
                + '<button class="btn-secondary-sm" style="padding:4px 8px;font-size:0.72rem;" onclick="window.inspectKycSession(\'' + esc(row.session_id) + '\')">Inspect</button>'
                + '<button class="btn-secondary-sm" style="padding:4px 8px;font-size:0.72rem;" onclick="window.syncKycSession(\'' + esc(row.session_id) + '\')">Sync Didit</button>'
                + '<button class="btn-primary-sm" style="padding:4px 8px;font-size:0.72rem;background:#F0B429;color:#0B0F14;" onclick="window.openKycOverrideModal(\'' + esc(row.user_id) + '\',\'' + esc(userDisplay) + '\',\'' + esc(row.status) + '\')">Override</button>'
              + '</div>'
            + '</td>'
          + '</tr>';
        });

        tbody.innerHTML = html;
        updatePagination(rows.length, totalRecords);
      })
      .catch(function(err) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-empty" style="color:#ef4444">Network error loading KYC verifications.</td></tr>';
      });
  }

  function updatePagination(count, total) {
    var textEl = document.getElementById('kycPaginationText');
    var prevBtn = document.getElementById('kycPrevPageBtn');
    var nextBtn = document.getElementById('kycNextPageBtn');
    if (!textEl) return;

    var start = total > 0 ? (currentPage - 1) * currentLimit + 1 : 0;
    var end = Math.min(start + count - 1, total);
    textEl.textContent = 'Showing ' + start + ' - ' + end + ' of ' + total + ' records';

    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = end >= total;
  }

  // Window-exposed methods for onclick handlers
  window.inspectKycSession = function(sessionId) {
    var modal = document.getElementById('kycInspectModal');
    var body = document.getElementById('kycInspectModalBody');
    if (!modal || !body) return;

    body.innerHTML = '<div class="table-empty">Loading session details from database...</div>';
    modal.style.display = 'flex';

    fetch('api/kyc-management.php?action=inspect&session_id=' + encodeURIComponent(sessionId))
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res.success || !res.verification) {
          body.innerHTML = '<div class="table-empty" style="color:#ef4444">Failed: ' + esc(res.error || 'Session not found') + '</div>';
          return;
        }

        var v = res.verification;
        var decision = {};
        try {
          if (v.decision) decision = JSON.parse(v.decision);
        } catch(e) {}

        var webhooks = res.webhooks || [];
        var audits = res.audits || [];

        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:rgba(255,255,255,0.02);padding:14px;border-radius:8px;border:1px solid #1e2d42;font-size:0.83rem;">'
          + '<div><strong>User ID:</strong> <span style="font-family:monospace;">' + esc(v.user_id) + '</span></div>'
          + '<div><strong>Status:</strong> ' + getStatusBadge(v.status) + '</div>'
          + '<div><strong>Session ID:</strong> <span style="font-family:monospace;">' + esc(v.session_id) + '</span></div>'
          + '<div><strong>Workflow ID:</strong> <span style="font-family:monospace;">' + esc(v.workflow_id) + '</span></div>'
          + '<div><strong>Created At:</strong> ' + esc(v.created_at || '--') + '</div>'
          + '<div><strong>Completed At:</strong> ' + esc(v.completed_at || '--') + '</div>'
          + (v.review_notes ? '<div style="grid-column:1/-1;"><strong>Review / Override Notes:</strong> ' + esc(v.review_notes) + '</div>' : '')
          + (v.verification_url ? '<div style="grid-column:1/-1;"><strong>Verification Link:</strong> <a href="' + esc(v.verification_url) + '" target="_blank" rel="noopener" style="color:#1677FF;text-decoration:underline;">Open Didit Flow &rarr;</a></div>' : '')
        + '</div>';

        // Decision breakdown if available
        if (Object.keys(decision).length > 0) {
          html += '<div style="border-top:1px solid #1e2d42;padding-top:12px;">'
            + '<h4 style="margin:0 0 8px;color:#fff;font-size:0.9rem;">Authoritative Decision Data</h4>'
            + '<div style="background:#101722;padding:12px;border-radius:8px;font-family:monospace;font-size:0.75rem;color:#8fa3b8;max-height:160px;overflow-y:auto;">'
              + esc(JSON.stringify(decision, null, 2))
            + '</div>'
          + '</div>';
        }

        // Webhook timeline
        html += '<div style="border-top:1px solid #1e2d42;padding-top:12px;">'
          + '<h4 style="margin:0 0 8px;color:#fff;font-size:0.9rem;">Webhook Lifecycle Events (' + webhooks.length + ')</h4>';

        if (webhooks.length === 0) {
          html += '<div style="color:#5b6475;font-size:0.8rem;">No webhook events recorded for this session yet.</div>';
        } else {
          html += '<table class="user-table" style="font-size:0.78rem;">'
            + '<thead><tr><th>Event ID</th><th>Type</th><th>Status</th><th>Processed</th></tr></thead>'
            + '<tbody>';
          webhooks.forEach(function(w) {
            html += '<tr>'
              + '<td style="font-family:monospace;">' + esc(w.event_id) + '</td>'
              + '<td>' + esc(w.event_type) + '</td>'
              + '<td>' + getStatusBadge(w.status) + '</td>'
              + '<td>' + esc(w.processed_at || w.created_at) + '</td>'
            + '</tr>';
          });
          html += '</tbody></table>';
        }
        html += '</div>';

        // Audit Trail
        if (audits.length > 0) {
          html += '<div style="border-top:1px solid #1e2d42;padding-top:12px;">'
            + '<h4 style="margin:0 0 8px;color:#fff;font-size:0.9rem;">Administrative Audit Trail</h4>'
            + '<ul style="padding-left:18px;margin:0;font-size:0.8rem;color:#8fa3b8;">';
          audits.forEach(function(a) {
            html += '<li>'
              + '<strong>' + esc(a.admin_id) + '</strong> changed status from <em>' + esc(a.previous_status) + '</em> to <strong>' + esc(a.new_status) + '</strong> on ' + esc(a.created_at)
              + (a.reason ? '<br><span style="color:#cdd6e3;">Reason: ' + esc(a.reason) + '</span>' : '')
            + '</li>';
          });
          html += '</ul></div>';
        }

        body.innerHTML = html;
      })
      .catch(function(err) {
        body.innerHTML = '<div class="table-empty" style="color:#ef4444">Network error loading session details.</div>';
      });
  };

  window.closeKycInspectModal = function() {
    var modal = document.getElementById('kycInspectModal');
    if (modal) modal.style.display = 'none';
  };

  window.syncKycSession = function(sessionId) {
    if (!confirm('Query Didit API for live decision status on session ' + sessionId + '?')) return;

    fetch('api/kyc-management.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'sync', session_id: sessionId })
    })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          alert('Sync successful: Status is ' + res.status);
          loadKycVerifications();
        } else {
          alert('Sync failed: ' + (res.error || 'Unknown error'));
        }
      })
      .catch(function(err) {
        alert('Network error while syncing session.');
      });
  };

  window.openKycOverrideModal = function(userId, display, currentStatus) {
    var modal = document.getElementById('kycOverrideModal');
    if (!modal) return;
    document.getElementById('overrideTargetUserId').value = userId;
    document.getElementById('overrideTargetUserDisplay').textContent = display + ' (Current: ' + (currentStatus || 'Not Started') + ')';
    document.getElementById('overrideReason').value = '';
    modal.style.display = 'flex';
  };

  window.closeKycOverrideModal = function() {
    var modal = document.getElementById('kycOverrideModal');
    if (modal) modal.style.display = 'none';
  };

  window.closeKycWebhookLogsModal = function() {
    var modal = document.getElementById('kycWebhookLogsModal');
    if (modal) modal.style.display = 'none';
  };

  // Setup DOM Event Listeners
  document.addEventListener('DOMContentLoaded', function() {
    // Search input
    var searchInput = document.getElementById('kycSearchInput');
    if (searchInput) {
      var debounceTimer;
      searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
          currentSearch = searchInput.value.trim();
          loadKycVerifications(1);
        }, 300);
      });
    }

    // Status filter
    var statusFilter = document.getElementById('kycStatusFilter');
    if (statusFilter) {
      statusFilter.addEventListener('change', function() {
        currentStatus = statusFilter.value;
        loadKycVerifications(1);
      });
    }

    // Refresh button
    var refreshBtn = document.getElementById('kycRefreshBtn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function() {
        loadKycVerifications();
      });
    }

    // Webhook activity button
    var webhookBtn = document.getElementById('kycViewWebhookLogsBtn');
    if (webhookBtn) {
      webhookBtn.addEventListener('click', function() {
        var modal = document.getElementById('kycWebhookLogsModal');
        var body = document.getElementById('kycWebhookLogsBody');
        if (!modal || !body) return;
        body.innerHTML = '<tr><td colspan="5" class="table-empty">Loading webhook logs...</td></tr>';
        modal.style.display = 'flex';

        fetch('api/kyc-management.php?action=webhook_logs')
          .then(function(r) { return r.json(); })
          .then(function(res) {
            if (!res.success || !res.logs || res.logs.length === 0) {
              body.innerHTML = '<tr><td colspan="5" class="table-empty">No webhook activity logged yet.</td></tr>';
              return;
            }
            var h = '';
            res.logs.forEach(function(l) {
              h += '<tr>'
                + '<td style="font-family:monospace;font-size:0.75rem;">' + esc(l.event_id) + '</td>'
                + '<td style="font-family:monospace;font-size:0.75rem;">' + esc(l.session_id) + '</td>'
                + '<td>' + esc(l.event_type) + '</td>'
                + '<td>' + getStatusBadge(l.status) + '</td>'
                + '<td style="font-size:0.78rem;color:#8fa3b8;">' + esc(l.processed_at || l.created_at) + '</td>'
              + '</tr>';
            });
            body.innerHTML = h;
          })
          .catch(function(e) {
            body.innerHTML = '<tr><td colspan="5" class="table-empty" style="color:#ef4444">Failed to load webhook logs.</td></tr>';
          });
      });
    }

    // Override Form Submission
    var overrideForm = document.getElementById('kycOverrideForm');
    if (overrideForm) {
      overrideForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var userId = document.getElementById('overrideTargetUserId').value;
        var newStatus = document.getElementById('overrideNewStatus').value;
        var reason = document.getElementById('overrideReason').value.trim();
        var submitBtn = document.getElementById('overrideSubmitBtn');

        if (!reason) {
          alert('A mandatory reason is required for audit logs.');
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Applying...';

        fetch('api/kyc-management.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'override',
            user_id: userId,
            new_status: newStatus,
            reason: reason
          })
        })
          .then(function(r) { return r.json(); })
          .then(function(res) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Override';
            if (res.success) {
              alert('KYC Status successfully overridden to: ' + newStatus);
              window.closeKycOverrideModal();
              loadKycVerifications();
            } else {
              alert('Override failed: ' + (res.error || 'Server rejected request.'));
            }
          })
          .catch(function(err) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Override';
            alert('Network error while overriding KYC status.');
          });
      });
    }

    // Pagination buttons
    var prevBtn = document.getElementById('kycPrevPageBtn');
    if (prevBtn) {
      prevBtn.addEventListener('click', function() {
        if (currentPage > 1) loadKycVerifications(currentPage - 1);
      });
    }
    var nextBtn = document.getElementById('kycNextPageBtn');
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        loadKycVerifications(currentPage + 1);
      });
    }

    // Sidebar navigation handler for #kyc-verifications
    function handleSectionActivation() {
      var hash = window.location.hash.replace('#', '');
      if (hash === 'kyc-verifications') {
        document.querySelectorAll('.section').forEach(function(s) { s.style.display = 'none'; });
        document.querySelectorAll('.sidebar-link').forEach(function(l) { l.classList.remove('active'); });

        var kycSection = document.getElementById('sectionKycVerifications');
        if (kycSection) kycSection.style.display = '';

        var pageTitle = document.getElementById('pageTitle');
        if (pageTitle) pageTitle.textContent = 'KYC Verifications';

        var link = document.querySelector('.sidebar-link[data-section="kyc-verifications"]');
        if (link) link.classList.add('active');

        loadKycVerifications();
      }
    }

    window.addEventListener('hashchange', handleSectionActivation);
    document.querySelectorAll('.sidebar-link[data-section="kyc-verifications"]').forEach(function(l) {
      l.addEventListener('click', function() {
        setTimeout(handleSectionActivation, 20);
      });
    });

    handleSectionActivation();
  });

  // Export to window
  window.loadKycVerifications = loadKycVerifications;
})();
