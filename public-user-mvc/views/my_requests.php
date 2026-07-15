<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Requests · EcoLotLK</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/my-requests.css">
</head>
<body>
 
<div class="layout">
  <!-- Include Sidebar Navigation -->
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
 
  <div class="content">
    <p class="breadcrumb"><a href="index.php?route=dashboard">Dashboard</a> &nbsp;›&nbsp; My Requests</p>
    <h1 class="title">My Requests</h1>
    <p class="subtitle">View and track all your pickup requests.</p>
 
    <div class="card">
      <div class="tabs">
        <div class="tab active" onclick="filterRequests('all')">All</div>
        <div class="tab" onclick="filterRequests('pending')">Pending</div>
        <div class="tab" onclick="filterRequests('completed')">Completed</div>
        <div class="tab" onclick="filterRequests('cancelled')">Cancelled</div>
      </div>
 
      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Date</th>
            <th>Category</th>
            <th>Estimated Weight</th>
            <th>Quantity</th>
            <th>Condition</th>
            <th>Status</th>
            <th>Edit</th>
          </tr>
        </thead>
        <tbody id="requests-tbody">
          <?php if (empty($requests)): ?>
            <tr class="request-row" data-status="all">
              <td colspan="8" style="text-align: center; color: var(--muted); padding: 20px;">No pickup requests found. Click "New Request" to create one!</td>
            </tr>
          <?php else: ?>
            <?php foreach ($requests as $req): 
                $categoryLabel = 'Other';
                switch ($req['category']) {
                    case 'domestic': $categoryLabel = 'Domestic E-Waste'; break;
                    case 'automobile': $categoryLabel = 'Automobile E-Waste'; break;
                    case 'office': $categoryLabel = 'Office E-Waste'; break;
                    case 'industrial': $categoryLabel = 'Industrial E-Waste'; break;
                    case 'medical': $categoryLabel = 'Medical E-Waste'; break;
                }
                if ($req['category'] === 'Other' && !empty($req['other_category'])) {
                    $categoryLabel = htmlspecialchars($req['other_category']);
                }

                $statusClass = strtolower($req['status']);
                $dateFormatted = date('M d, Y', strtotime($req['collection_date']));
            ?>
              <tr class="request-row" data-status="<?php echo $statusClass; ?>">
                <td><span class="view-link"><?php echo htmlspecialchars($req['id']); ?></span></td>
                <td><?php echo $dateFormatted; ?></td>
                <td><?php echo htmlspecialchars($categoryLabel); ?></td>
                <td><?php echo htmlspecialchars($req['weight']); ?> kg</td>
                <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($req['condition_status'])); ?></td>
                <td><span class="badge badge-<?php echo $statusClass; ?>"><span class="dot"></span><?php echo htmlspecialchars($req['status']); ?></span></td>
                <td>
                  <?php if ($req['status'] === 'Pending'): ?>
                    <a href="index.php?route=new-request&id=<?php echo urlencode($req['id']); ?>" class="edit-btn" title="Edit request">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                    </a>
                  <?php else: ?>
                    <span style="color: var(--muted); font-size: 11px;">Locked</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
 
      <div class="pagination">
        <span>Showing 1 to <?php echo count($requests); ?> of <?php echo count($requests); ?> requests</span>
      </div>
    </div>
  </div>
</div>

<script>
// Filter requests in the DOM based on tabs
function filterRequests(status) {
    // Update active tab styling
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Find the clicked tab
    event.target.classList.add('active');

    const rows = document.querySelectorAll('.request-row');
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
