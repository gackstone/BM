<!-- KYC Verifications Section -->
<section class="section" id="sectionKycVerifications" style="display:none">
  <!-- Summary Stat Cards -->
  <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card" style="padding:16px 14px;">
      <div class="stat-icon" style="background:rgba(22,119,255,0.15);color:#1677FF;">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="kycStatTotal">0</span>
        <span class="stat-lbl">Total Sessions</span>
      </div>
    </div>
    <div class="stat-card" style="padding:16px 14px;">
      <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:#22C55E;">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="kycStatApproved" style="color:#22C55E;">0</span>
        <span class="stat-lbl">Approved</span>
      </div>
    </div>
    <div class="stat-card" style="padding:16px 14px;">
      <div class="stat-icon" style="background:rgba(240,180,41,0.15);color:#F0B429;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="kycStatInReview" style="color:#F0B429;">0</span>
        <span class="stat-lbl">In Review</span>
      </div>
    </div>
    <div class="stat-card" style="padding:16px 14px;">
      <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:#EF4444;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="kycStatDeclined" style="color:#EF4444;">0</span>
        <span class="stat-lbl">Declined</span>
      </div>
    </div>
    <div class="stat-card" style="padding:16px 14px;">
      <div class="stat-icon" style="background:rgba(143,163,184,0.15);color:#8fa3b8;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="kycStatPending" style="color:#8fa3b8;">0</span>
        <span class="stat-lbl">Pending / In Progress</span>
      </div>
    </div>
  </div>

  <!-- Filter & Actions Bar -->
  <div class="users-panel">
    <div class="panel-header" style="flex-wrap:wrap;gap:12px;">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex:1;">
        <h3 class="panel-title" style="margin:0;">Didit KYC Identity Verifications</h3>
        <div style="display:flex;align-items:center;gap:8px;">
          <input type="text" id="kycSearchInput" placeholder="Search user, email, session..." class="search-input" style="background:#101722;border:1px solid #1e2d42;color:#fff;padding:6px 12px;border-radius:6px;font-size:.84rem;min-width:220px;">
          <select id="kycStatusFilter" class="filter-select" style="background:#101722;border:1px solid #1e2d42;color:#fff;padding:6px 10px;border-radius:6px;font-size:.84rem;">
            <option value="all">All Statuses</option>
            <option value="approved">Approved</option>
            <option value="in_review">In Review</option>
            <option value="declined">Declined</option>
            <option value="in progress">In Progress</option>
            <option value="not started">Not Started</option>
          </select>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <button class="btn-secondary-sm" id="kycViewWebhookLogsBtn" type="button">Webhook Activity</button>
        <button class="btn-primary-sm" id="kycRefreshBtn" type="button">Refresh</button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <table class="user-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Session ID</th>
            <th>Status</th>
            <th>Created</th>
            <th>Completed</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="kycTableBody">
          <tr><td colspan="6" class="table-empty">Loading KYC verifications...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div id="kycPagination" style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-top:1px solid #1e2d42;font-size:0.84rem;color:#8fa3b8;">
      <span id="kycPaginationText">Showing 0 of 0</span>
      <div style="display:flex;gap:6px;">
        <button class="btn-secondary-sm" id="kycPrevPageBtn" disabled>&larr; Prev</button>
        <button class="btn-secondary-sm" id="kycNextPageBtn" disabled>Next &rarr;</button>
      </div>
    </div>
  </div>
</section>

<!-- Inspection Modal -->
<div class="modal-overlay" id="kycInspectModal" style="display:none;">
  <div class="modal" style="max-width:680px;max-height:85vh;overflow-y:auto;">
    <div class="modal-header">
      <h3 class="modal-title">Didit Verification Session Details</h3>
      <button class="modal-close" onclick="closeKycInspectModal()">&times;</button>
    </div>
    <div class="modal-body" id="kycInspectModalBody" style="display:grid;gap:16px;">
      <div class="table-empty">Loading session details...</div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
      <button class="btn-secondary-sm" onclick="closeKycInspectModal()">Close</button>
    </div>
  </div>
</div>

<!-- Manual Override Modal -->
<div class="modal-overlay" id="kycOverrideModal" style="display:none;">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3 class="modal-title">Manual KYC Status Override</h3>
      <button class="modal-close" onclick="closeKycOverrideModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="kycOverrideForm" style="display:grid;gap:14px;">
        <input type="hidden" id="overrideTargetUserId">
        <div>
          <label style="display:block;font-size:0.8rem;color:#8fa3b8;margin-bottom:4px;">Target User</label>
          <div id="overrideTargetUserDisplay" style="font-weight:600;color:#fff;">--</div>
        </div>
        <div>
          <label style="display:block;font-size:0.8rem;color:#8fa3b8;margin-bottom:4px;">New KYC Status</label>
          <select id="overrideNewStatus" class="form-input" style="width:100%;padding:8px 10px;background:#101722;border:1px solid #1e2d42;color:#fff;border-radius:6px;" required>
            <option value="Approved">Approved (Verified)</option>
            <option value="In Review">In Review</option>
            <option value="Declined">Declined</option>
            <option value="Resubmitted">Resubmitted (Retry)</option>
            <option value="Not Started">Reset to Not Started</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:0.8rem;color:#8fa3b8;margin-bottom:4px;">Override Reason (Audit Requirement)</label>
          <textarea id="overrideReason" class="form-input" rows="3" placeholder="Provide mandatory compliance reason for manual change..." style="width:100%;padding:8px 10px;background:#101722;border:1px solid #1e2d42;color:#fff;border-radius:6px;" required></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px;">
          <button type="button" class="btn-secondary-sm" onclick="closeKycOverrideModal()">Cancel</button>
          <button type="submit" class="btn-primary-sm" id="overrideSubmitBtn">Confirm Override</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Webhook Logs Modal -->
<div class="modal-overlay" id="kycWebhookLogsModal" style="display:none;">
  <div class="modal" style="max-width:760px;max-height:85vh;overflow-y:auto;">
    <div class="modal-header">
      <h3 class="modal-title">Didit Webhook Event Stream</h3>
      <button class="modal-close" onclick="closeKycWebhookLogsModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="table-wrap">
        <table class="user-table">
          <thead>
            <tr>
              <th>Event ID</th>
              <th>Session ID</th>
              <th>Event Type</th>
              <th>Status</th>
              <th>Processed At</th>
            </tr>
          </thead>
          <tbody id="kycWebhookLogsBody">
            <tr><td colspan="5" class="table-empty">Loading webhook logs...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:flex-end;">
      <button class="btn-secondary-sm" onclick="closeKycWebhookLogsModal()">Close</button>
    </div>
  </div>
</div>
