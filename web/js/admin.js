const bell = document.getElementById('notification-bell');
const dropdown = document.getElementById('notification-dropdown');

if (bell && dropdown) {
  bell.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('is-open');
  });

  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== bell) {
      dropdown.classList.remove('is-open');
    }
  });
}
