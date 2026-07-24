function toggleNotifications(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('notiDropdown');
  dropdown.classList.toggle('show');
}

// Global click event logic to safely dismiss the menu interface element
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('notiDropdown');
  const btn = document.getElementById('notiBtn');
  if (dropdown && !dropdown.contains(event.target) && !btn.contains(event.target)) {
    dropdown.classList.remove('show');
  }
});
