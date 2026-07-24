<h1 class="title">Customer Profile</h1>
<p class="subtitle">Update your profile here!</p>

<div class="card">
  <h3 style="margin:0 0 18px;font-size:16px;">Customer Information</h3>
  <div class="avatar-row">
    <div class="avatar">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1b7a4a" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
    </div>
  </div>
  <div class="grid-2">
    <div class="field"><label>First Name</label><input type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly></div>
    <div class="field"><label>Last Name</label><input type="text" value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly></div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3>Contact Information</h3>
  </div>
  <div class="field"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly></div>
  <div class="field"><label>Phone</label><input type="tel" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly></div>
  <div class="field"><label>Address</label><textarea rows="3" readonly><?php echo htmlspecialchars($user['address']); ?></textarea></div>
  <p class="locked-note">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    These details were provided at registration and can't be edited here. Contact support if something needs to change.
  </p>
</div>

<div class="card" style="margin-bottom:0;">
  <h3 style="margin:0 0 16px;font-size:16px;">Security &amp; Privacy</h3>
  <div style="display:flex;gap:12px;">
    <button class="btn">Change Password</button>
    <button class="btn btn-danger">Delete Account</button>
  </div>
</div>
