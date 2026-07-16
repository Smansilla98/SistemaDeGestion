const roundMoney = (value) => Math.round((value + Number.EPSILON) * 100) / 100;
const roundPercent = (value) => Math.round((value + Number.EPSILON) * 100) / 100;

const parseNumber = (input) => {
    if (!input) return null;
    const value = Number.parseFloat(input.value);

    return Number.isFinite(value) ? value : null;
};

const formatCurrency = (value) => new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(value);

const initializeProductPricing = (container) => {
    const costInput = container.querySelector('[data-cost-price]');
    const saleInput = container.querySelector('[data-sale-price]');
    const marginInput = container.querySelector('[data-profit-margin]');
    const sourceInput = container.querySelector('[data-pricing-source]');
    const summary = container.querySelector('[data-pricing-summary]');
    const gainCell = container.querySelector('[data-pricing-gain]');

    if (!costInput || !saleInput || !marginInput) {
        return;
    }

    const setSource = (value) => {
        if (sourceInput) {
            sourceInput.value = value;
        }
    };

    const renderSummary = () => {
        const cost = parseNumber(costInput);
        const sale = parseNumber(saleInput);
        const margin = parseNumber(marginInput);

        if (gainCell) {
            if (cost !== null && sale !== null) {
                const gain = sale - cost;
                gainCell.textContent = formatCurrency(gain);
                gainCell.className = gain >= 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            } else {
                gainCell.textContent = '—';
                gainCell.className = 'text-muted';
            }
        }

        if (!summary) {
            return;
        }

        if (cost === null || cost <= 0) {
            summary.textContent = 'Ingresá un costo mayor a cero para calcular la ganancia.';
            summary.className = 'alert alert-info mt-3 mb-0 py-2';
            return;
        }

        if (sale === null) {
            summary.textContent = 'Ingresá el valor de venta o el porcentaje de ganancia.';
            summary.className = 'alert alert-warning mt-3 mb-0 py-2';
            return;
        }

        const gain = sale - cost;
        const tone = gain >= 0 ? 'success' : 'danger';
        summary.className = `alert alert-${tone} mt-3 mb-0 py-2`;
        summary.textContent = `Ganancia por unidad: ${formatCurrency(gain)} (${margin ?? 0}%).`;
    };

    const calculateMargin = () => {
        const cost = parseNumber(costInput);
        const sale = parseNumber(saleInput);
        setSource('sale');

        if (cost === null || cost <= 0 || sale === null) {
            marginInput.value = '';
            renderSummary();
            return;
        }

        marginInput.value = roundPercent(((sale - cost) / cost) * 100);
        renderSummary();
        container.classList.add('is-dirty');
    };

    const calculateSale = () => {
        const cost = parseNumber(costInput);
        const margin = parseNumber(marginInput);
        setSource('margin');

        if (cost === null || cost <= 0 || margin === null) {
            renderSummary();
            return;
        }

        saleInput.value = roundMoney(cost * (1 + (margin / 100))).toFixed(2);
        renderSummary();
        container.classList.add('is-dirty');
    };

    costInput.addEventListener('input', calculateMargin);
    saleInput.addEventListener('input', calculateMargin);
    marginInput.addEventListener('input', calculateSale);

    if (marginInput.value === '') {
        calculateMargin();
    } else {
        renderSummary();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-pricing]').forEach(initializeProductPricing);

    // Aplicar ganancia % a todas las filas visibles de la matriz
    const bulkMargin = document.getElementById('bulkApplyMargin');
    const bulkBtn = document.getElementById('bulkApplyMarginBtn');
    if (bulkMargin && bulkBtn) {
        bulkBtn.addEventListener('click', () => {
            const margin = Number.parseFloat(bulkMargin.value);
            if (!Number.isFinite(margin) || margin < 0) {
                if (typeof window.showToast === 'function') {
                    window.showToast('warning', 'Valor inválido', 'Ingresá un porcentaje de ganancia válido.');
                }
                return;
            }

            document.querySelectorAll('[data-product-pricing]').forEach((row) => {
                const costInput = row.querySelector('[data-cost-price]');
                const marginInput = row.querySelector('[data-profit-margin]');
                const cost = Number.parseFloat(costInput?.value);
                if (!Number.isFinite(cost) || cost <= 0 || !marginInput) {
                    return;
                }
                marginInput.value = margin;
                marginInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }
});
