<div class="content">
  <p class="breadcrumb"><a href="<?= $basePath ?>/user/dashboard">Dashboard</a> &nbsp;›&nbsp; My Requests</p>
  <h1 class="title">My Requests</h1>
  <p class="subtitle">View and track all your pickup requests.</p>

  <div class="card">
    <div class="tabs">
      <div class="tab active">All</div>
      <div class="tab">Pending</div>
      <div class="tab">Completed</div>
      <div class="tab">Cancelled</div>
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
          <th style="text-align: center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- Sample Row 1 -->
        <tr>
          <td><a href="javascript:void(0)" class="view-link" onclick="openViewModal('REQ-2024-00012')">REQ-2024-00012</a></td>
          <td>May 20, 2024</td>
          <td>IT equipment</td>
          <td>8.5 kg</td>
          <td>3</td>
          <td>Working</td>
          <td><span class="badge badge-pending"><span class="dot"></span>Pending</span></td>
          <td>
            <div class="action-buttons">
              <!-- View Icon -->
              <button type="button" class="action-btn view-btn" title="View Request" onclick="openViewModal('REQ-2024-00012')">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
              <!-- Edit Icon -->
              <a href="javascript:void(0)" class="action-btn edit-btn" title="Edit Request" onclick="openViewModal('REQ-2024-00012')">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </a>
              <!-- Actions Column Delete Icon -->
              <a href="javascript:void(0)" class="action-btn delete-btn" title="Delete Request" onclick="openDeleteModal('REQ-2024-00012')">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pagination">
      <span>Showing 1 to 5 of 12 requests</span>
      <div class="pagenums">
        <button>‹</button>
        <button class="active">1</button>
        <button>2</button>
        <button>3</button>
        <button>›</button>
      </div>
    </div>
  </div>
</div>

<!-- Visually Appealing Center Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay">
  <div class="modal-box">
    <div class="modal-icon">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
    <h3 class="modal-title">Delete Request?</h3>
    <p class="modal-desc">Are you sure you want to delete this record? This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">Cancel</button>
      <a id="confirmDeleteBtn" href="#" class="btn-modal btn-confirm-delete">Delete</a>
    </div>
  </div>
</div>

<!-- Non-Editable View Details Modal -->
<div id="viewModal" class="modal-overlay">
  <div class="modal-box view-modal-box">
    
    <div class="view-modal-header">
      <div>
        <h3 class="modal-title" style="text-align: left; margin: 0;">Request Details</h3>
        <span id="viewRequestId" class="view-req-badge">REQ-2024-00012</span>
      </div>
      <button type="button" class="close-modal-btn" onclick="closeViewModal()">&times;</button>
    </div>

    <div class="view-details-grid">
      <div class="detail-group">
        <label>Status</label>
        <div><span id="viewStatus" class="badge badge-pending"><span class="dot"></span>Pending</span></div>
      </div>

      <div class="detail-group">
        <label>Submitted Date</label>
        <p id="viewCreatedDate">May 20, 2024</p>
      </div>

      <div class="detail-group">
        <label>Selected Collection Date</label>
        <p id="viewCollectionDate">May 25, 2024</p>
      </div>

      <div class="detail-group">
        <label>Postal Code Area</label>
        <p id="viewPostalCode">10230 (Maharagama)</p>
      </div>

      <div class="detail-group full-width">
        <label>Pickup Address</label>
        <p id="viewAddress">No. 45, High Level Road, Maharagama</p>
      </div>

      <div class="detail-group">
        <label>Selected E-Waste Categories</label>
        <p id="viewCategories">IT & Telecommunication Equipment</p>
      </div>

      <div class="detail-group">
        <label>Estimated Weight</label>
        <p id="viewWeight">8.5 kg</p>
      </div>

      <div class="detail-group">
        <label>Quantity</label>
        <p id="viewQuantity">3 items</p>
      </div>

      <div class="detail-group">
        <label>Item Condition</label>
        <p id="viewCondition">Working / Repairable</p>
      </div>

      <div class="detail-group full-width">
        <label>Other E-Waste Items / Description</label>
        <p id="viewOtherEwaste">2x Old Dell Laptops, 1x HP Monitor with power cables.</p>
      </div>
    </div>

    <div class="modal-actions" style="margin-top: 24px;">
      <button type="button" class="btn-modal btn-cancel" onclick="closeViewModal()">Close</button>
    </div>

  </div>
</div>