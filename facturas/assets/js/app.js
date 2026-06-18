/**
 * Sistema de Facturacion - JavaScript personalizado
 */

document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // Auto-dismiss alerts after 5 seconds
    // =========================================
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // =========================================
    // Delete confirmation modal
    // =========================================
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const deleteInvoiceId = document.getElementById('deleteInvoiceId');
    const deleteInvoiceNumber = document.getElementById('deleteInvoiceNumber');

    if (deleteModal && deleteButtons.length > 0) {
        const modal = new bootstrap.Modal(deleteModal);

        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-id');
                var numero = this.getAttribute('data-numero');

                if (deleteInvoiceId) deleteInvoiceId.value = id;
                if (deleteInvoiceNumber) deleteInvoiceNumber.textContent = numero;

                modal.show();
            });
        });
    }

    // =========================================
    // Form validation (client-side)
    // =========================================
    var forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // =========================================
    // Filter form auto-submit on select change
    // =========================================
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        var selects = filterForm.querySelectorAll('select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                filterForm.submit();
            });
        });
    }

    // =========================================
    // Session timeout warning
    // Show alert 5 minutes before session expiry (30 min session = warn at 25 min)
    // =========================================
    var sessionTimeout = 30 * 60 * 1000; // 30 minutes in ms
    var warningTime = 25 * 60 * 1000; // 25 minutes in ms

    var sessionWarningTimer = setTimeout(function () {
        var warningDiv = document.createElement('div');
        warningDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        warningDiv.style.zIndex = '9999';
        warningDiv.innerHTML = '<strong><i class="bi bi-clock me-2"></i>Aviso:</strong> Su sesion expirara en 5 minutos. Guarde sus cambios.' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
        document.body.appendChild(warningDiv);

        // Auto-dismiss the warning after 30 seconds
        setTimeout(function () {
            if (warningDiv.parentNode) {
                warningDiv.parentNode.removeChild(warningDiv);
            }
        }, 30000);
    }, warningTime);

    // =========================================
    // Currency input formatting
    // =========================================
    var valorInput = document.getElementById('valor');
    if (valorInput) {
        valorInput.addEventListener('blur', function () {
            var value = parseFloat(this.value);
            if (!isNaN(value) && value >= 0) {
                this.value = value.toFixed(2);
            }
        });
    }

    // =========================================
    // Date picker initialization (use native date input)
    // Set min date on date inputs if needed
    // =========================================
    var dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function (input) {
        // Ensure proper format
        if (!input.value && input.id === 'fecha') {
            var today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });

});
