<?php
/**
 * Footer - Pie de página HTML compartido
 */
?>
</main>

<?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
<footer class="bg-light text-center text-muted py-3 mt-5 border-top">
    <div class="container">
        <small>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. Todos los derechos reservados.</small>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- Custom JS -->
<script src="<?php echo $baseUrl ?? ''; ?>/assets/js/app.js"></script>
</body>
</html>
