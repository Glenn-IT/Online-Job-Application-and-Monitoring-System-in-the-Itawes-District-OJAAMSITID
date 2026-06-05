    <!-- ── Logout Confirmation Modal ── -->
    <?php include_once(($basePath ?? '') . 'modals/logout-modal.php'); ?>

    <!-- Site footer links -->
    <footer class="border-top mt-5 py-3 bg-light">
        <div class="container text-center small text-muted">
            &copy; <?php echo date('Y'); ?> OJAMS &mdash;
            <a href="<?php echo ($basePath ?? ''); ?>about.php"   class="text-muted text-decoration-none ms-2">About / FAQ</a>
            <a href="<?php echo ($basePath ?? ''); ?>contact.php" class="text-muted text-decoration-none ms-2">Contact</a>
            <a href="<?php echo ($basePath ?? ''); ?>terms.php"   class="text-muted text-decoration-none ms-2">Terms &amp; Privacy</a>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
