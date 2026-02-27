{{-- Estilos estándar para todos los tickets: 80mm, largo = contenido, corte debajo --}}
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-weight: 700; }
html { height: auto; min-height: 0; }
body {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.2;
    width: 80mm;
    max-width: 80mm;
    padding: 3mm 3mm 2mm 3mm;
    margin: 0 auto;
    height: auto;
    min-height: 0;
}
.ticket { width: 80mm; max-width: 80mm; margin: 0; min-height: 0; }
.border-asterisk { text-align: center; font-size: 11px; letter-spacing: 0.5px; margin: 2px 0; }
.header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 3px; margin-bottom: 4px; }
.header h1 { font-size: 18px; font-weight: bold; margin: 2px 0; text-transform: uppercase; }
.header p { font-size: 13px; margin: 1px 0; }
.logo-container { text-align: center; margin-bottom: 2px; }
.logo-container img { max-width: 55mm; max-height: 18mm; object-fit: contain; }
.dashed-line { border-top: 1px dashed #000; margin: 2px 0; }
.order-info { margin-bottom: 4px; font-size: 13px; }
.order-info p { margin: 1px 0; }
.items { margin: 3px 0; }
.item { margin-bottom: 3px; padding-bottom: 2px; }
.item-line { display: flex; justify-content: space-between; font-size: 13px; align-items: flex-start; }
.item-quantity { margin-right: 4px; font-weight: bold; min-width: 22px; flex-shrink: 0; }
.item-name { flex: 1; word-break: break-word; }
.item-price { text-align: right; min-width: 42px; font-weight: bold; flex-shrink: 0; }
.item-details, .item-observations { margin-left: 0; font-size: 12px; margin-top: 1px; color: #333; }
.item-observations { font-style: italic; }
.totals { margin-top: 4px; padding-top: 3px; }
.total-line { display: flex; justify-content: space-between; margin: 2px 0; font-size: 13px; }
.total-line.final { font-weight: bold; font-size: 15px; border-top: 1px dashed #000; padding-top: 3px; margin-top: 3px; }
.payments { margin-top: 4px; padding-top: 3px; border-top: 1px dashed #000; font-size: 13px; }
.payment-line { display: flex; justify-content: space-between; margin: 2px 0; }
.payment-total { border-top: 1px dashed #000; padding-top: 3px; margin-top: 2px; font-weight: bold; }
.change-line { display: flex; justify-content: space-between; margin: 2px 0; font-size: 13px; }
.footer { margin-top: 5px; margin-bottom: 0; padding-bottom: 0; text-align: center; border-top: 1px dashed #000; padding-top: 3px; font-size: 11px; }
.thank-you { text-align: center; font-weight: bold; margin: 2px 0; font-size: 13px; }
.timestamp { font-size: 12px; color: #333; }
table.ticket-table { width: 100%; border-collapse: collapse; font-size: 13px; }
table.ticket-table td, table.ticket-table th { padding: 2px 4px; border-bottom: 1px dashed #ccc; vertical-align: top; }
table.ticket-table th { text-align: left; font-size: 12px; }
td.cant { width: 32px; text-align: right; }
td.prod { word-break: break-word; }

@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }
    html, body {
        margin: 0 !important;
        padding: 2mm 2mm 0 2mm !important;
        width: 80mm !important;
        max-width: 80mm !important;
        min-height: 0 !important;
        height: auto !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ticket {
        padding: 0 !important;
        min-height: 0 !important;
    }
}
</style>
