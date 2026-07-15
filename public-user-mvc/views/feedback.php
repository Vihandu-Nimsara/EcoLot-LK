<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complaint / Feedback · EcoLotLK</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/feedback.css">
</head>
<body>

<div class="layout">
  <!-- Include Sidebar Navigation -->
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="content">
    <p class="breadcrumb"><a href="index.php?route=dashboard">Dashboard</a> &nbsp;›&nbsp; Complaint / Feedback</p>
    <h1 class="title">Complaint / Feedback</h1>
    <p class="subtitle">We value your feedback and are here to help.</p>

    <div class="layout-2">
      <form class="card" method="POST" action="index.php?route=feedback-submit">
        <div class="field">
          <label>Feedback Type</label>
          <select name="feedback_type" required>
            <option value="">Select type</option>
            <option value="Complaint">Complaint</option>
            <option value="Suggestion">Suggestion</option>
            <option value="Compliment">Compliment</option>
          </select>
        </div>
        
        <div class="field">
          <label>Related Request (optional)</label>
          <select name="request_id">
            <option value="">Select request</option>
            <?php foreach ($requests as $req): ?>
              <option value="<?php echo htmlspecialchars($req['id']); ?>"><?php echo htmlspecialchars($req['id']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field" style="margin-bottom:24px;">
          <label>Message</label>
          <textarea name="message" rows="5" placeholder="Enter your message" required></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;">
          <a href="index.php?route=dashboard" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Cancel</a>
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>

      <div class="card">
        <div class="icon-circle">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1b7a4a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        </div>
        <h3 style="font-size:15px;margin:0 0 8px;">How we help</h3>
        <p style="font-size:13px;color:var(--muted);margin:0;">Your feedback helps us improve our services. We aim to respond to all queries within 48 hours.</p>
      </div>
    </div>
  </div>
</div>

<script src="public/js/feedback.js"></script>
</body>
</html>
