const { expect } = require('@playwright/test');

/** Inicia sesión con el usuario sembrado por DatabaseSeeder (o el de E2E_USER / E2E_PASSWORD). */
async function login(page, user = process.env.E2E_USER || 'admin', password = process.env.E2E_PASSWORD || 'admin123') {
    await page.goto('/login');
    await page.fill('#username', user);
    await page.fill('#password', password);
    await Promise.all([page.waitForURL((url) => !url.pathname.startsWith('/login')), page.click('button[type=submit]')]);
}

/** La página no se corre de costado (el error más común en celular). */
async function expectNoHorizontalScroll(page) {
    const [scroll, width] = await page.evaluate(() => [document.documentElement.scrollWidth, window.innerWidth]);
    expect(scroll, 'la página tiene scroll horizontal').toBeLessThanOrEqual(width + 1);
}

module.exports = { login, expectNoHorizontalScroll };
