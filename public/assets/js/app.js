// Helper interaksi UI
(function() {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  if (!sidebar || !toggle) return;

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!sidebar.classList.contains('show')) return;
    const isInside = sidebar.contains(e.target) || toggle.contains(e.target);
    if (!isInside) {
      sidebar.classList.remove('show');
    }
  });
})();
