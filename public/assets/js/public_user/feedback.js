document.addEventListener('DOMContentLoaded', function () {
  var form = document.querySelector('.card form, form.card');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    alert('Prototype only — feedback not actually submitted.');
  });
});
