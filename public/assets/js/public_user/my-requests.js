// Open the delete confirmation modal
function openDeleteModal(requestId) {
  const modal = document.getElementById('deleteModal');
  const confirmBtn = document.getElementById('confirmDeleteBtn');
  
  // Dynamically update the delete link with the selected request ID
  confirmBtn.href = `delete-request.php?id=${requestId}`;
  
  // Display the modal
  modal.classList.add('active');
}

// Close the delete confirmation modal
function closeDeleteModal() {
  const modal = document.getElementById('deleteModal');
  modal.classList.remove('active');
}

// Close the modal if the user clicks anywhere outside the white modal box
window.addEventListener('click', function(event) {
  const modal = document.getElementById('deleteModal');
  if (event.target === modal) {
    closeDeleteModal();
  }
});

function openViewModal(requestId) {
  var modal = document.getElementById('viewModal');
  
  // Update the modal title ID dynamically
  document.getElementById('viewRequestId').innerText = requestId;

  // Note: Once your database is ready, you can fetch real data per request ID here 
  // via AJAX or dataset attributes and populate viewAddress, viewCategories, etc.

  if (modal) {
    modal.classList.add('active');
  }
}

function closeViewModal() {
  var modal = document.getElementById('viewModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

// Ensure clicking outside closes both View & Delete modals
window.addEventListener('click', function(event) {
  var viewModal = document.getElementById('viewModal');
  var deleteModal = document.getElementById('deleteModal');
  
  if (event.target === viewModal) {
    closeViewModal();
  }
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
});