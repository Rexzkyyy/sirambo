<?php
/**
 * Footer Partial - Tema Navy
 */
?>
        <!-- Footer Konten Utama -->
        <footer class="footer mt-auto py-4 bg-white border-top">
            <div class="container-fluid px-4">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <span class="text-muted small">© <?= date('Y') ?> <strong>TIM NERACA</strong>. BPS Provinsi Sulawesi Tenggara.</span>
                    </div>
                    <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                        <div class="d-flex justify-content-center justify-content-md-end gap-3">
                            <a href="#" class="text-muted text-decoration-none small">Syarat & Ketentuan</a>
                            <span class="text-muted opacity-25">|</span>
                            <a href="#" class="text-muted text-decoration-none small">Kebijakan Privasi</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Kontrol UI -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('siramboSidebar');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('active');
                });
            }

            // Menutup sidebar saat klik di luar pada perangkat mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                }
            });

            // Mencegah sidebar tetap aktif saat resize ke desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>