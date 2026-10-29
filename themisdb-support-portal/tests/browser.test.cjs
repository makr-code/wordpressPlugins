const { test } = require('node:test');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { chromium } = require('playwright');

const baseURL = process.env.WORDPRESS_URL || 'http://localhost/wordpress/';
const pluginURL = new URL('wp-content/plugins/themisdb-support-portal/', baseURL).href;
const fixture = execFileSync(process.env.PHP_BINARY || 'C:\\xampp\\php\\php.exe', [
    path.join(__dirname, 'integration-test.php'),
    process.env.WORDPRESS_BOOTSTRAP || 'C:\\xampp\\htdocs\\wordpress\\wp-load.php',
    '--fixture',
], { encoding: 'utf8' });

async function openFixture(page) {
    page.setDefaultTimeout(5000);
    const url = new URL('support-readonly-fixture/', baseURL).href;
    await page.route(url, route => route.fulfill({
        contentType: 'text/html; charset=utf-8',
        body: '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' +
            '<link rel="stylesheet" href="' + pluginURL + 'assets/css/support-portal.css">' +
            '<div id="outside" class="tv3-feature-tabs__link">Unrelated theme component</div>' +
            fixture + '<div id="after">Outside hub</div>' +
            '<script>window.themisdbSupport={ajaxUrl:"' + new URL('wp-admin/admin-ajax.php', baseURL).href +
            '",nonce:"fixture",ownsOrderOverlay:true,strings:{error:"Fixture error",auth_with_license:"Login",verifying:"Checking",file_read_error:"File could not be read"}};</script>' +
            '<script src="' + new URL('wp-includes/js/jquery/jquery.min.js', baseURL).href + '"></script>' +
            '<script src="' + pluginURL + 'assets/js/support-portal.js"></script>',
    }));
    await page.goto(url);
}

test('standalone nested hub tabs, keyboard, navigation links and order overlay', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/admin-ajax.php', route => route.abort());
        await openFixture(page);
        assert.equal(await page.locator('#after').evaluate(el => !!el.closest('.themisdb-support-hub-wrap')), false);
        assert.equal(await page.locator('#outside').evaluate(el => getComputedStyle(el).borderTopWidth), '0px');
        const outer = page.locator('.themisdb-support-hub-tabs > .themisdb-support-hub-tabs__tabs > [role=tablist]');
        const inner = page.locator('.themisdb-support-hub-dashboard > [role=tablist]');
        await inner.getByRole('tab', { name: 'Profil', exact: true }).click();
        assert.equal(await page.locator('#themisdb-dashboard-panel-profile').isVisible(), true);
        assert.equal(await page.locator('#themisdb-support-hub-panel-overview').isVisible(), true);
        assert.equal(await outer.getByRole('tab', { name: 'Übersicht', exact: true }).getAttribute('aria-selected'), 'true');
        await inner.getByRole('tab', { name: 'Profil', exact: true }).press('ArrowRight');
        assert.equal(await page.locator('#themisdb-dashboard-panel-activity').isVisible(), true);
        await outer.getByRole('tab', { name: 'Support', exact: true }).click();
        assert.equal(await page.locator('#themisdb-support-hub-panel-support').isVisible(), true);
        assert.equal(await page.locator('#themisdb-support-hub-panel-overview').isVisible(), false);
        await page.locator('.themisdb-support-hub-tabs__toolbar a[href="#themisdb-support-hub-tab-orders"]').click();
        assert.equal(await page.locator('#themisdb-support-hub-panel-orders').isVisible(), true);
        const overlay = page.locator('#themisdb-order-overlay');
        assert.equal(await overlay.isVisible(), false);
        const trigger = page.locator('[data-themisdb-open-order-overlay]');
        await trigger.click();
        assert.equal(await overlay.isVisible(), true);
        assert.equal(await overlay.getAttribute('aria-hidden'), 'false');
        assert.equal(await overlay.locator('[role=dialog]').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(255, 255, 255)');
        await page.keyboard.press('Tab');
        assert.equal(await overlay.locator('button[data-themisdb-close-order-overlay]').evaluate(el => el === document.activeElement), true);
        await page.keyboard.press('Escape');
        assert.equal(await overlay.isVisible(), false);
        assert.equal(await trigger.evaluate(el => el === document.activeElement), true);
        await outer.getByRole('tab', { name: 'Übersicht', exact: true }).click();
        await inner.getByRole('tab', { name: 'Zugriff', exact: true }).click();
        await page.locator('#themisdb-dashboard-panel-access a[href="#themisdb-support-hub-tab-support"]').click();
        assert.equal(await page.locator('#themisdb-support-hub-panel-support').isVisible(), true);
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        }
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});

test('license file read failures show feedback and release the submit button', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        let requests = 0;
        await page.route('**/admin-ajax.php', route => { requests++; return route.abort(); });
        await page.addInitScript(() => {
            window.FileReader = class extends EventTarget {
                readAsText() { if (this.onerror) this.onerror(new Event('error')); }
            };
        });
        await openFixture(page);
        await page.locator('.themisdb-support-hub-dashboard > [role=tablist]').getByRole('tab', { name: 'Zugriff', exact: true }).click();
        await page.locator('#themisdb-support-license-file').setInputFiles({
            name: 'synthetic.json', mimeType: 'application/json', buffer: Buffer.from('{}'),
        });
        await page.locator('#themisdb-support-login-btn').click();
        assert.match(await page.locator('#themisdb-support-login-messages').innerText(), /File could not be read/);
        assert.equal(await page.locator('#themisdb-support-login-btn').isEnabled(), true);
        assert.equal(requests, 0);
    } finally { await browser.close(); }
});

test('real support page renders a single portal with icons and no horizontal overflow', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(new URL('support/', baseURL).href);
        assert.equal(await page.locator('#themisdb-support-license-form').count(), 1);
        assert.ok(await page.locator('link[href*="dashicons"]').count());
        assert.equal(await page.evaluate(() => window.themisdbSupport.ownsOrderOverlay), '1');
        assert.equal(await page.evaluate(() => typeof window.themisdbSupport.strings.file_read_error), 'string');
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
        }
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});
