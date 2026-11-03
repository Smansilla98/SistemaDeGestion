// Patuka: el celular usa la misma web y el calendario crea eventos desde el día.
const { test, expect } = require('@playwright/test');
const { login, expectNoHorizontalScroll } = require('./helpers');

test.describe('celular', () => {
    test.use({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148' });

    test('el inicio es el de la web (no el panel móvil viejo)', async ({ page }) => {
        await login(page);
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.locator('#novaSidebarToggle')).toBeVisible();
        await expectNoHorizontalScroll(page);
    });

    test('las rutas móviles viejas llevan a la web', async ({ page }) => {
        await login(page);
        await page.goto('/m/pedidos');
        await expect(page).toHaveURL(/\/tables/);
    });
});

test('en «Eventos y shows», un clic en el día crea un evento con esa fecha', async ({ page }) => {
    await login(page);
    // Con la Agenda contratada abre la grilla de la Agenda; sin ella, el calendario de eventos. Los dos crean desde el día.
    await page.goto('/events?year=2030&month=3');
    await page.locator('[data-create-url$="date=2030-03-10"]').click({ position: { x: 50, y: 70 } });
    await expect(page).toHaveURL(/\/events\/create\?date=2030-03-10/);
    await expect(page.locator('[name=date]')).toHaveValue('2030-03-10');
});
