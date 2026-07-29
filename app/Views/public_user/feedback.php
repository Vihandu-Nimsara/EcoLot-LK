<div class="content">
  <p class="breadcrumb"><a href="<?= $basePath ?>/user/dashboard">Dashboard</a> &nbsp;›&nbsp; Complaint / Feedback</p>
  <h1 class="title">Complaint / Feedback</h1>
  <p class="subtitle">We value your feedback and are here to help.</p>

  <div class="layout-2">
    <form class="card">
      <div class="field"><label>Feedback Type</label><select><option>Select type</option><option>Complaint</option><option>Suggestion</option><option>Compliment</option></select></div>
      <div class="field"><label>Related Request (optional)</label><select><option>Select request</option><option>REQ-2024-00012</option><option>REQ-2024-00011</option></select></div>
      <div class="field" style="margin-bottom:24px;"><label>Message</label><textarea rows="5" placeholder="Enter your message"></textarea></div>
      <div style="display:flex;justify-content:flex-end;gap:12px;">
        <a href="<?= $basePath ?>/user/dashboard"><button type="button" class="btn">Cancel</button></a>
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form>
  </div>
</div>
