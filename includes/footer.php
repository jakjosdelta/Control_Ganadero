</div><!-- Cierra container -->
    
    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="mb-0 text-muted">
                <i class="fas fa-cow"></i> Control Ganadero &copy; <?php echo date('Y'); ?>
                | Sistema Local XAMPP
            </p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>