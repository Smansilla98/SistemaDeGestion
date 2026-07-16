import './bootstrap';
import * as ThermalPrinter from './thermalPrinter.js';
import './drag-drop.js';
import './notifications.js';
import './product-pricing.js';

// Impresora térmica USB (comandas): disponible como window.ThermalPrinter
window.ThermalPrinter = ThermalPrinter;

// Funciones JavaScript globales
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirmar eliminaciones
    const deleteForms = document.querySelectorAll('form[data-confirm-delete]');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });
});

// Función para actualizar totales en tiempo real
window.updateTotal = function() {
    // Implementación para cálculos dinámicos
};

// Función para formatear moneda
window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(amount);
};

// Función para formatear fecha
window.formatDate = function(date) {
    return new Date(date).toLocaleDateString('es-AR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

/**
 * Feedback de guardado para redes inestables (mobile salón).
 * Uso: window.cxSaving.show('Guardando…') / .hide() / .fail('No se pudo guardar')
 */
window.cxSaving = {
    _el: null,
    _ensure() {
        if (this._el) return this._el;
        let el = document.getElementById('cxSavingToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'cxSavingToast';
            el.className = 'm-saving';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.innerHTML = '<span class="cx-spinner cx-spinner--sm"><span class="cx-spinner__dot" aria-hidden="true"></span></span><span class="cx-saving-text">Guardando…</span>';
            document.body.appendChild(el);
        }
        this._el = el;
        return el;
    },
    show(message = 'Guardando…') {
        const el = this._ensure();
        const text = el.querySelector('.cx-saving-text');
        if (text) text.textContent = message;
        el.classList.add('is-visible');
    },
    hide() {
        this._ensure().classList.remove('is-visible');
    },
    fail(message = 'No se pudo guardar. Reintentá.') {
        this.hide();
        if (typeof window.showToast === 'function') {
            window.showToast('error', 'Error de guardado', message);
        } else if (window.Swal) {
            window.Swal.fire({ icon: 'error', title: 'Error de guardado', text: message });
        } else {
            alert(message);
        }
    },
};
