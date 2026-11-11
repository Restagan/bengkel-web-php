</main>

<?php if (!($tanpa_navbar ?? false)): ?>
    <footer class="gr-footer">
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>Sistem Manajemen Bengkel</span>
            <span>Proyek Tugas Kelompok, <?= date('Y'); ?></span>
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
