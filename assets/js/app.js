// assets/js/app.js
// tempat script ringan (tooltip, helper, dll)
document.addEventListener("DOMContentLoaded", () => {
  // contoh: aktifkan tooltip bootstrap jika ada
  const tips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tips.forEach(el => new bootstrap.Tooltip(el));

  // toggle sidebar di layar kecil
  const sidebar       = document.getElementById('siramboSidebar');
  const toggleButton  = document.getElementById('sidebarToggle');
  const closeButton   = document.getElementById('sidebarClose');

  const toggleSidebar = () => sidebar?.classList.toggle('is-open');
  const closeSidebar  = () => sidebar?.classList.remove('is-open');

  toggleButton?.addEventListener('click', toggleSidebar);
  closeButton?.addEventListener('click', closeSidebar);

  // tutup ketika klik di luar sidebar pada mobile
  document.addEventListener('click', (e) => {
    if (!sidebar || window.innerWidth >= 992) return;
    if (!sidebar.contains(e.target) && !toggleButton?.contains(e.target)) {
      closeSidebar();
    }
  });
});
