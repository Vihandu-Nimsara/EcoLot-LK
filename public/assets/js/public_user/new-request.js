document.addEventListener('DOMContentLoaded', function () {
  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      alert('Prototype only — request not actually submitted.');
    });
  }
});

function switchGuideTab(button, categoryId) {
  document.querySelectorAll('.guide-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.guide-list').forEach(list => list.classList.remove('active'));
  button.classList.add('active');
  document.getElementById('guide-' + categoryId).classList.add('active');
}
