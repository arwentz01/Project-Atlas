const sidebar = document.getElementById('sidebar');
const menuButton = document.getElementById('menuButton');
const toast = document.getElementById('toast');

menuButton?.addEventListener('click', () => sidebar?.classList.toggle('open'));

document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-toast]');
  if (!trigger || !toast) return;
  toast.textContent = trigger.dataset.toast;
  toast.classList.add('show');
  window.clearTimeout(window.atlasToastTimer);
  window.atlasToastTimer = window.setTimeout(() => toast.classList.remove('show'), 3200);
});

document.querySelectorAll('.segmented button').forEach((button) => {
  button.addEventListener('click', () => {
    button.parentElement.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
  });
});

