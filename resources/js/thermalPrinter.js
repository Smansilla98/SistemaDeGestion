/**
 * Servicio de impresión térmica USB (ESC/POS) para comandas.
 * Usa WebUSBReceiptPrinter + ReceiptPrinterEncoder.
 * Se expone como window.ThermalPrinter desde app.js.
 */
import WebUSBReceiptPrinter from '@point-of-sale/webusb-receipt-printer';
import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder';

const STORAGE_KEY = 'thermal_printer_device';

let receiptPrinter = null;
let encoderOptions = { language: 'esc-pos' };
let lastUsedDevice = null;

function getStoredDevice() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function storeDevice(device) {
    if (!device) return;
    try {
        const toStore = {
            serialNumber: device.serialNumber,
            vendorId: device.vendorId,
            productId: device.productId,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(toStore));
    } catch (e) {}
}

function initPrinter() {
    if (receiptPrinter) return receiptPrinter;
    receiptPrinter = new WebUSBReceiptPrinter();
    receiptPrinter.addEventListener('connected', (device) => {
        lastUsedDevice = device;
        storeDevice(device);
        encoderOptions = {
            language: device.language || 'esc-pos',
            codepageMapping: device.codepageMapping,
        };
    });
    return receiptPrinter;
}

/**
 * Conectar a la impresora USB (debe llamarse desde un clic del usuario).
 */
export function connect() {
    const p = initPrinter();
    return p.connect();
}

/**
 * Reconectar a la última impresora usada (puede llamarse al cargar la página).
 */
export function reconnect() {
    const p = initPrinter();
    const dev = lastUsedDevice || getStoredDevice();
    if (!dev) return Promise.resolve(false);
    p.reconnect(dev);
    return Promise.resolve(true);
}

/**
 * Indica si hay dispositivo guardado o conectado.
 */
export function isAvailable() {
    return !!(getStoredDevice() || lastUsedDevice);
}

/**
 * Genera bytes ESC/POS de la comanda y los envía a la impresora.
 * @param {Object} comanda - { order_number, table_number, customer_name, waiter_name, created_at, observations, items: [{ quantity, name, observations, modifiers }] }
 */
export function printComanda(comanda) {
    const p = initPrinter();
    if (!p) return Promise.reject(new Error('Impresora no inicializada'));

    const encoder = new ReceiptPrinterEncoder(encoderOptions).initialize();

    encoder.line('--------------------------------');
    encoder.line('         COMANDA');
    encoder.line('--------------------------------');
    encoder.line('Pedido #' + (comanda.order_number || ''));
    if (comanda.table_number) encoder.line('Mesa: ' + comanda.table_number);
    if (comanda.customer_name) encoder.line('Cliente: ' + comanda.customer_name);
    encoder.line('Mozo: ' + (comanda.waiter_name || '-'));
    encoder.line('Hora: ' + (comanda.created_at || ''));
    if (comanda.observations) encoder.line('Obs: ' + comanda.observations);
    encoder.line('--------------------------------');

    const items = comanda.items || [];
    for (let i = 0; i < items.length; i++) {
        const it = items[i];
        const qty = it.quantity != null ? it.quantity : 1;
        encoder.line(qty + 'x ' + (it.name || ''));
        if (it.modifiers) encoder.line('  + ' + it.modifiers);
        if (it.observations) encoder.line('  Nota: ' + it.observations);
    }

    encoder.line('--------------------------------');
    encoder.line(comanda.created_at || new Date().toLocaleString('es-AR'));
    encoder.line('--------------------------------');
    if (typeof encoder.cut === 'function') encoder.cut();

    const data = encoder.encode();
    p.print(data);
    return Promise.resolve();
}

/**
 * Obtiene los datos de la comanda desde la API e imprime.
 * @param {number} orderId - ID del pedido
 * @param {string} baseUrl - URL base (opcional)
 */
export function fetchAndPrintComanda(orderId, baseUrl = '') {
    const url = (baseUrl || '') + '/api/orders/' + orderId + '/print-comanda-data';
    const token = document.querySelector('meta[name="csrf-token"]');
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');

    return fetch(url, { headers })
        .then((res) => res.json())
        .then((json) => {
            if (!json.success || !json.data) throw new Error(json.message || 'Error al obtener comanda');
            return printComanda(json.data);
        });
}

export { getStoredDevice };

export default {
    connect,
    reconnect,
    isAvailable,
    printComanda,
    fetchAndPrintComanda,
    getStoredDevice,
};
