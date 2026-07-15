<?php
// views/partials/sidebar.php
$activePage = isset($activePage) ? $activePage : '';
?>
<div class="sidebar">
  <div>
    <div class="logo-container">
      <img src="public/images/logo-header.png" alt="EcoLot LK Workspace" class="logo-header-img">
    </div>
    <a href="index.php?route=profile" class="nav-item <?php echo ($activePage === 'profile') ? 'active' : ''; ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <a href="index.php?route=dashboard" class="nav-item <?php echo ($activePage === 'dashboard') ? 'active' : ''; ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
      Dashboard
    </a>
    <a href="index.php?route=new-request" class="nav-item <?php echo ($activePage === 'new-request') ? 'active' : ''; ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M12 14v4M10 16h4"/></svg>
      New Request
    </a>
    <a href="index.php?route=my-requests" class="nav-item <?php echo ($activePage === 'my-requests') ? 'active' : ''; ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
      My Requests
    </a>
    <a href="index.php?route=feedback" class="nav-item <?php echo ($activePage === 'feedback') ? 'active' : ''; ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
      Feedback
    </a>
  </div>
  <a href="homepage.html" class="logout-item">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
    Logout
  </a>
</div>
