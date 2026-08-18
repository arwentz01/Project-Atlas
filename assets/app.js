const sidebar = document.getElementById('sidebar');
const menuButton = document.getElementById('menuButton');
const toast = document.getElementById('toast');
const orgSwitcher = document.getElementById('orgSwitcher');
const orgDropdown = document.getElementById('orgDropdown');

const closeNavigation = () => {
  sidebar?.classList.remove('open');
  document.body.classList.remove('nav-open');
};

menuButton?.addEventListener('click', () => {
  const opening = !sidebar?.classList.contains('open');
  sidebar?.classList.toggle('open', opening);
  document.body.classList.toggle('nav-open', opening);
});

sidebar?.querySelectorAll('.nav-link').forEach((link) => link.addEventListener('click', closeNavigation));
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeNavigation(); });
document.addEventListener('click', (event) => {
  if (window.innerWidth > 860 || !document.body.classList.contains('nav-open')) return;
  if (!sidebar?.contains(event.target) && !menuButton?.contains(event.target)) closeNavigation();
});

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

orgSwitcher?.addEventListener('click', () => orgDropdown?.classList.toggle('open'));

document.querySelectorAll('[data-modal]').forEach((button) => {
  button.addEventListener('click', () => document.getElementById(button.dataset.modal)?.showModal());
});

document.querySelectorAll('.modal-close').forEach((button) => {
  button.addEventListener('click', () => button.closest('dialog')?.close());
});

document.querySelectorAll('.atlas-modal').forEach((modal) => {
  modal.addEventListener('click', (event) => { if (event.target === modal) modal.close(); });
});

document.querySelectorAll('[data-structure-tab]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-structure-tab]').forEach((item) => item.classList.remove('active'));
    document.querySelectorAll('[data-structure-panel]').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
    document.querySelector(`[data-structure-panel="${button.dataset.structureTab}"]`)?.classList.add('active');
  });
});

document.querySelector('[data-table-search]')?.addEventListener('input', (event) => {
  const query = event.target.value.toLowerCase();
  document.querySelectorAll('[data-search-row]').forEach((row) => row.hidden = !row.textContent.toLowerCase().includes(query));
});

document.querySelectorAll('[data-copy]').forEach((button) => {
  button.addEventListener('click', async () => {
    const input = document.getElementById(button.dataset.copy);
    if (!input) return;
    await navigator.clipboard.writeText(input.value);
    button.textContent = 'Copied';
  });
});

document.querySelectorAll('.page-flash button').forEach((button) => button.addEventListener('click', () => button.parentElement.remove()));

document.querySelectorAll('.people-table [data-search-row] .row-menu').forEach((button, index) => {
  button.title = 'Edit workforce profile';
  button.addEventListener('click', () => document.getElementById(`staffModal${index}`)?.showModal());
});

if (document.querySelector('[data-structure-panel]') && document.getElementById('catalogModal')) {
  const header = document.querySelector('.page-header');
  const catalogButton = document.createElement('button');
  catalogButton.className = 'button secondary catalog-launcher';
  catalogButton.innerHTML = '+ Operational resource';
  catalogButton.addEventListener('click', () => document.getElementById('catalogModal').showModal());
  header?.appendChild(catalogButton);
}

const eligibilityMode = document.querySelector('[data-eligibility-mode]');
const updateEligibilityFields = () => {
  const mode = eligibilityMode?.value || 'exact';
  document.querySelectorAll('.eligibility-field').forEach((field) => field.hidden = field.dataset.mode !== mode);
};
eligibilityMode?.addEventListener('change', updateEligibilityFields);
updateEligibilityFields();
