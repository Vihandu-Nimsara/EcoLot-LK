document.addEventListener('DOMContentLoaded', () => {
  const deleteModal = document.getElementById('deleteModal');
  const viewModal = document.getElementById('viewModal');
  const editModal = document.getElementById('editModal');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

  // Open Delete Modal
  function openDeleteModal(requestId) {
    if (confirmDeleteBtn) {
      confirmDeleteBtn.href = `delete-request.php?id=${requestId}`;
    }
    if (deleteModal) {
      deleteModal.removeAttribute('hidden');
      deleteModal.classList.add('active');
    }
  }

  // Close Delete Modal
  function closeDeleteModal() {
    if (deleteModal) {
      deleteModal.classList.remove('active');
      deleteModal.setAttribute('hidden', '');
    }
  }

  // Open View Details Modal
  function openViewModal(requestId) {
    const viewReqIdElem = document.getElementById('viewRequestId');
    if (viewReqIdElem) {
      viewReqIdElem.innerText = requestId;
    }
    if (viewModal) {
      viewModal.removeAttribute('hidden');
      viewModal.classList.add('active');
    }
  }

  // Close View Details Modal
  function closeViewModal() {
    if (viewModal) {
      viewModal.classList.remove('active');
      viewModal.setAttribute('hidden', '');
    }
  }

  // Open Edit Modal
  function openEditModal(requestId) {
    const editReqIdElem = document.getElementById('editRequestId');
    if (editReqIdElem) {
      editReqIdElem.innerText = requestId;
    }
    if (editModal) {
      editModal.removeAttribute('hidden');
      editModal.classList.add('active');
    }
  }

  // Close Edit Modal
  function closeEditModal() {
    if (editModal) {
      editModal.classList.remove('active');
      editModal.setAttribute('hidden', '');
    }
  }

  // Event Delegation for Table Action Buttons & Request ID Links
  document.addEventListener('click', (event) => {
    // Check for View Actions
    const viewBtn = event.target.closest('[data-view-request]');
    if (viewBtn) {
      const reqId = viewBtn.getAttribute('data-view-request');
      openViewModal(reqId);
      return;
    }

    // Check for Edit Actions
    const editBtn = event.target.closest('[data-edit-request]');
    if (editBtn) {
      const reqId = editBtn.getAttribute('data-edit-request');
      openEditModal(reqId);
      return;
    }

    // Check for Delete Actions
    const deleteBtn = event.target.closest('[data-delete-request]');
    if (deleteBtn) {
      const reqId = deleteBtn.getAttribute('data-delete-request');
      openDeleteModal(reqId);
      return;
    }

    // Modal Close Buttons
    if (event.target.closest('[data-close-delete-modal]')) {
      closeDeleteModal();
    }
    if (event.target.closest('[data-close-view-modal]')) {
      closeViewModal();
    }
    if (event.target.closest('[data-close-edit-modal]')) {
      closeEditModal();
    }

    // Close on Outside Click Overlay
    if (event.target === deleteModal) {
      closeDeleteModal();
    }
    if (event.target === viewModal) {
      closeViewModal();
    }
    if (event.target === editModal) {
      closeEditModal();
    }
  });
});