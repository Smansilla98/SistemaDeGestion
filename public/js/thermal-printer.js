/**
 * Servicio de impresión térmica USB (ESC/POS) para comandas.
 * Usa WebUSBReceiptPrinter + ReceiptPrinterEncoder.
 * Requiere: ReceiptPrinterEncoder y WebUSBReceiptPrinter cargados (UMD).
 */
(function (global) {
    'use strict';

    var STORAGE_KEY = 'thermal_printer_device';
    var receiptPrinter = null;
    var encoderOptions = { language: 'esc-pos' };
    var lastUsedDevice = null;

    function getStoredDevice() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function storeDevice(device) {
        if (!device) return;
        try {
            var toStore = {
                serialNumber: device.serialNumber,
                vendorId: device.vendorId,
                productId: device.productId
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(toStore));
        } catch (e) {}
    }

    function initPrinter() {
        if (typeof global.WebUSBReceiptPrinter === 'undefined') return null;
        if (receiptPrinter) return receiptPrinter;
        receiptPrinter = new global.WebUSBReceiptPrinter();
        receiptPrinter.addEventListener('connected', function (device) {
            lastUsedDevice = device;
            storeDevice(device);
            encoderOptions.language = device.language || 'esc-pos';
            encoderOptions.codepageMapping = device.codepageMapping || undefined;
        });
        return receiptPrinter;
    }

    function getEncoderClass() {
        return typeof global.ReceiptPrinterEncoder !== 'undefined' ? global.ReceiptPrinterEncoder : null;
    }

    /**
     * Conectar a la impresora USB (debe llamarse desde un gesto del usuario, p. ej. clic).
     * La primera vez el navegador mostrará el selector de dispositivo.
     */
    function connect() {
        var p = initPrinter();
        if (!p) return Promise.reject(new Error('WebUSBReceiptPrinter no cargado'));
        return p.connect();
    }

    /**
     * Reconectar a la última impresora usada (puede llamarse al cargar la página).
     */
    function reconnect() {
        var p = initPrinter();
        if (!p) return Promise.resolve(false);
        var dev = lastUsedDevice || getStoredDevice();
        if (!dev) return Promise.resolve(false);
        p.reconnect(dev);
        return Promise.resolve(true);
    }

    /**
     * Indica si hay una impresora disponible (conectada o con dispositivo guardado).
     */
    function isAvailable() {
        return !!(getStoredDevice() || lastUsedDevice);
    }

    /**
     * Genera los bytes ESC/POS de la comanda y los envía a la impresora.
     * @param {Object} comanda - { order_number, table_number, customer_name, waiter_name, created_at, observations, items: [{ quantity, name, observations, modifiers }] }
     * @returns {Promise<void>}
     */
    function printComanda(comanda) {
        var EncoderClass = getEncoderClass();
        var p = initPrinter();
        if (!EncoderClass || !p) return Promise.reject(new Error('Librerías de impresión no cargadas'));

        var encoder = new EncoderClass(encoderOptions).initialize();

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

        var items = comanda.items || [];
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            var qty = it.quantity != null ? it.quantity : 1;
            encoder.line(qty + 'x ' + (it.name || ''));
            if (it.modifiers) encoder.line('  + ' + it.modifiers);
            if (it.observations) encoder.line('  Nota: ' + it.observations);
        }

        encoder.line('--------------------------------');
        encoder.line(comanda.created_at || new Date().toLocaleString('es-AR'));
        encoder.line('--------------------------------');
        if (typeof encoder.cut === 'function') encoder.cut();

        var data = encoder.encode();
        p.print(data);
        return Promise.resolve();
    }

    /**
     * Obtiene los datos de la comanda desde la API e imprime.
     * @param {number} orderId - ID del pedido
     * @param {string} baseUrl - URL base (opcional)
     */
    function fetchAndPrintComanda(orderId, baseUrl) {
        baseUrl = baseUrl || '';
        var url = baseUrl + '/api/orders/' + orderId + '/print-comanda-data';
        var token = document.querySelector('meta[name="csrf-token"]');
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');

        return fetch(url, { headers: headers })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json.success || !json.data) throw new Error(json.message || 'Error al obtener comanda');
                return printComanda(json.data);
            });
    }

    global.ThermalPrinter = {
        connect: connect,
        reconnect: reconnect,
        isAvailable: isAvailable,
        printComanda: printComanda,
        fetchAndPrintComanda: fetchAndPrintComanda,
        getStoredDevice: getStoredDevice
    };
})(typeof window !== 'undefined' ? window : this);
