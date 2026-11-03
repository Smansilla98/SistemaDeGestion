// Producto base: ingreso, inicio y navegación en computadora y celular.
const { test, expect } = require('@playwright/test');
const { login, expectNoHorizontalScroll } = require('./helpers');

const pantallas = ['/dashboard', '/products', '/orders', '/manual'];

test.describe('computadora', () => {
    test.use({ viewport: { width: 1366, height: 768 } });

    test('el login entra en la pantalla y deja ingresar', async ({ page }) => {
        await page.goto('/login');
        const alto = await page.evaluate(() => document.documentElement.scrollHeight);
        expect(alto, 'el login no debería necesitar scroll en una notebook').toBeLessThanOrEqual(768);
        await login(page);
        await expect(page).not.toHaveURL(/\/login/);
    });

    for (const ruta of pantallas) {
        test(`${ruta} abre sin errores`, async ({ page }) => {
            const errores = [];
            page.on('pageerror', (e) => errores.push(e.message));
            await login(page);
            const r = await page.goto(ruta);
            expect(r.status()).toBeLessThan(400);
            expect(errores, 'errores de JavaScript').toEqual([]);
        });
    }
});

test.describe('celular', () => {
    test.use({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148' });

    for (const ruta of pantallas) {
        test(`${ruta} no se corre de costado`, async ({ page }) => {
            await login(page);
            await page.goto(ruta);
            await expectNoHorizontalScroll(page);
        });
    }

    test('el menú lateral se abre con el botón ☰', async ({ page }) => {
        await login(page);
        // En main el inicio del celular es el panel móvil de restaurante; el menú se prueba en Productos.
        await page.goto('/products');
        await page.click('#novaSidebarToggle');
        await expect(page.locator('#novaSidebar')).toBeInViewport();
    });
});
