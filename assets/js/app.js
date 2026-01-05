// assets/js/app.js
// tempat script ringan (tooltip, helper, dll)
document.addEventListener("DOMContentLoaded", () => {
  // contoh: aktifkan tooltip bootstrap jika ada
  const tips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tips.forEach(el => new bootstrap.Tooltip(el));
});
