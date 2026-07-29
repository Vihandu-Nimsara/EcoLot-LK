<div class="content">
  <div class="header-row">
    <div>
      <p class="breadcrumb">Overview &nbsp;›&nbsp; Personal Dashboard</p>
      <h1 class="title">Welcome Back!</h1>
      <p class="subtitle">Monitor your disposal milestones and upcoming recycling collections.</p>
    </div>
    
    <!-- Upper Control Actions Group -->
    <div class="header-controls">
      <a href="<?= $basePath ?>/user/new-request" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        New Pickup Request
      </a>

      <!-- Notification Trigger Elements Component -->
      <div style="position: relative;">
        <button type="button" class="noti-btn" id="notiBtn" onclick="toggleNotifications(event)">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#5f7268" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="noti-badge"></span>
        </button>
        <div class="noti-dropdown" id="notiDropdown">
          <div class="noti-header">
            <span>Notifications</span>
          </div>
          <div class="noti-item">
            <p>Your request <strong>REQ-2024-00011</strong> has been scheduled.</p>
            <span>2 hours ago</span>
          </div>
          <div class="noti-item">
            <p>Pickup verified for <strong>REQ-2024-00010</strong>. Thank you!</p>
            <span>Yesterday</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Counters & Performance Statistics Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-value">12</span>
        <span class="stat-label">Total Requests</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#e3edfb; color:#2f5fae;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-value">7</span>
        <span class="stat-label">Completed Pickups</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fff1d6; color:#9a6b0f;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-value">2</span>
        <span class="stat-label">Pending Reviews</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fbe4e4; color:#b23b3b;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/><path d="M12 11v4"/><path d="M12 9h.01"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-value">41.5 kg</span>
        <span class="stat-label">Recycled Weight</span>
      </div>
    </div>
  </div>

  <!-- Modified Full Width Request Tracking Matrix -->
  <div class="card">
    <div class="card-header-simple">
      <h2 class="card-title">Latest Pickup Requests</h2>
      <a href="<?= $basePath ?>/user/my-requests" class="view-all">View All</a>
    </div>

    <table style="margin-bottom: 12px;">
      <thead>
        <tr>
          <th>Request ID</th>
          <th>Date</th>
          <th>Category</th>
          <th>Estimated Weight</th>
          <th>Quantity</th>
          <th>Condition</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><a href="<?= $basePath ?>/user/my-requests" class="view-link">REQ-2024-00012</a></td>
          <td>May 20, 2024</td>
          <td>IT equipment</td>
          <td>8.5 kg</td>
          <td>3</td>
          <td>Working</td>
          <td><span class="badge badge-pending"><span class="dot"></span>Pending</span></td>
        </tr>
        <tr>
          <td><a href="<?= $basePath ?>/user/my-requests" class="view-link">REQ-2024-00011</a></td>
          <td>May 18, 2024</td>
          <td>Small appliances</td>
          <td>12 kg</td>
          <td>1</td>
          <td>Working</td>
          <td><span class="badge badge-completed"><span class="dot"></span>Completed</span></td>
        </tr>
      </tbody>
    </table>

    <!-- View More Action Link Interface Part -->
    <div class="table-footer">
      <a href="<?= $basePath ?>/user/my-requests" class="view-more-link">
        View More Requests 
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </a>
    </div>
  </div>

</div>
