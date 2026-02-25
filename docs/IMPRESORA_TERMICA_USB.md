# Impresora térmica USB (ESC/POS) para comandas

El sistema permite imprimir la comanda automáticamente en una **impresora térmica USB** cuando se confirma un pedido, **sin mostrar el diálogo de impresión** del navegador. Se usa el protocolo **ESC/POS** (estándar de impresoras de tickets).

## Requisitos

- **Navegador**: Chrome o Edge (Chromium). WebUSB no está soportado en Firefox/Safari.
- **Sitio en HTTPS** o `localhost` (WebUSB exige contexto seguro).
- **Impresora térmica** compatible con ESC/POS conectada por USB.
- **Windows**: WebUSB no puede acceder a la impresora si el driver la usa en exclusiva. Opciones:
  - Usar una impresora que cree un **puerto serie virtual** (driver “USB to Serial”) y la librería `WebSerialReceiptPrinter` en lugar de WebUSB, o
  - Usar un agente local (JSPrintManager, QZ Tray) para impresión silenciosa.

## Instalación y configuración

### 1. Ubicación en el proyecto (Vite + ESM)

- **Backend**: ruta `GET /api/orders/{order}/print-comanda-data` que devuelve los datos de la comanda en JSON.
- **Frontend** (incluido en el bundle de Vite):
  - **`resources/js/thermalPrinter.js`**: módulo ESM que importa `@point-of-sale/receipt-printer-encoder` y `@point-of-sale/webusb-receipt-printer`, expone `connect`, `reconnect`, `printComanda`, `fetchAndPrintComanda`, `getStoredDevice`, `isAvailable`.
  - **`resources/js/app.js`**: importa `thermalPrinter.js` y asigna `window.ThermalPrinter` para uso desde las vistas Blade (mesas, pedidos rápidos, crear pedido).
  - Las librerías se instalan con npm: `@point-of-sale/receipt-printer-encoder@3.0.3` y `@point-of-sale/webusb-receipt-printer@2.0.0`.

El layout (MOZO/ADMIN) ya no carga scripts UMD desde unpkg ni `public/js/thermal-printer.js`; todo va en el build de Vite (`npm run build` / `npm run dev`).

### 2. Primera conexión (gesto del usuario)

La primera vez hay que **conectar la impresora desde un clic** (política del navegador):

1. Entrar como **Mozo** o **Admin**.
2. Ir a **Mesas** y abrir el modal **Nuevo pedido**.
3. Hacer clic en **"Conectar impresora USB"** (debajo del botón Confirmar pedido).
4. En el cuadro del navegador, elegir la impresora térmica y aceptar.

A partir de ahí el dispositivo se guarda en `localStorage` y en las siguientes cargas se intenta **reconectar solo** al cargar la página.

### 3. Flujo al crear un pedido

- **Mesas** (modal nuevo pedido), **Pedidos rápidos** o **Crear pedido** (formulario):  
  Al confirmar el pedido, si existe `ThermalPrinter` y hay dispositivo guardado (o ya conectado), se llama a:
  - `ThermalPrinter.fetchAndPrintComanda(order_id)`
  - Que hace `GET /api/orders/{id}/print-comanda-data`, genera el ticket ESC/POS y lo envía a la impresora con `receiptPrinter.print(data)`.
- Si no hay impresora USB conectada o falla la impresión, se usa el **fallback**: se abre la ventana de impresión del navegador (ticket HTML + `window.print()`).

## API del servicio (frontend)

Disponible como `window.ThermalPrinter`:

| Método | Descripción |
|--------|-------------|
| `ThermalPrinter.connect()` | Abre el selector USB (debe llamarse desde un clic). Devuelve Promise. |
| `ThermalPrinter.reconnect()` | Reconecta a la última impresora guardada. Se ejecuta al cargar la página si hay dispositivo. |
| `ThermalPrinter.isAvailable()` | Indica si hay dispositivo guardado o conectado. |
| `ThermalPrinter.printComanda(comanda)` | Imprime un objeto comanda (mismo formato que devuelve la API). |
| `ThermalPrinter.fetchAndPrintComanda(orderId [, baseUrl])` | Obtiene los datos con `GET /api/orders/{order}/print-comanda-data` e imprime. |
| `ThermalPrinter.getStoredDevice()` | Devuelve el dispositivo guardado en localStorage (o null). Usado para decidir si ejecutar `reconnect()` al cargar. |

## Estructura de datos de la comanda (API)

`GET /api/orders/{order}/print-comanda-data` devuelve:

```json
{
  "success": true,
  "data": {
    "order_number": "ORD-2026-001",
    "table_number": "5",
    "customer_name": null,
    "waiter_name": "Juan Pérez",
    "created_at": "24/02/2026 14:30",
    "observations": "Sin sal",
    "items": [
      {
        "quantity": 2,
        "name": "Café con leche",
        "observations": "",
        "modifiers": "Doble"
      }
    ]
  }
}
```

## Alternativas para Windows o impresión sin WebUSB

- **WebSerialReceiptPrinter**: si la impresora aparece como puerto serie (COM), se puede usar la librería hermana y cambiar en `resources/js/thermalPrinter.js` la instancia de `WebUSBReceiptPrinter` por `WebSerialReceiptPrinter` (misma API de `print()`).
- **JSPrintManager** (Neodynamic): instalar el cliente en el PC y usar su API JS para enviar trabajos a la impresora por nombre, sin diálogo.
- **QZ Tray**: instalar QZ Tray en el equipo y usar `qz-tray` desde el front para enviar ESC/POS o PDF a la impresora configurada.

## Referencias

- [WebUSBReceiptPrinter](https://www.npmjs.com/package/@point-of-sale/webusb-receipt-printer) – envío a impresora USB desde el navegador.
- [ReceiptPrinterEncoder](https://www.npmjs.com/package/@point-of-sale/receipt-printer-encoder) – generación de comandos ESC/POS.
- [Imprimir ticket en impresora térmica con JavaScript (Parzibyte)](https://parzibyte.me/blog/2017/10/17/imprimir-ticket-en-impresora-termica-usando-javascript/).
