const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const baseURL = process.env.WORDPRESS_URL || 'http://localhost/wordpress/';

test('checkout script saves customer step and keeps submission confirmation without real requests', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        let fixtureLoads = 0;
        const requests = [];
        await page.route(url => url.searchParams.has('synthetic_checkout_fixture'), route => {
            fixtureLoads++;
            return route.fulfill({ contentType: 'text/html', body: `<!doctype html>
                <body class="themisdb-checkout-frame">
                <div class="themisdb-order-flow" data-flow-mode="express">
                  <div class="order-step-content" data-step="4">
                    <input id="customer_name" value="Synthetic">
                    <input id="customer_email" value="synthetic@example.invalid">
                    <input id="billing_name" value="Synthetic">
                    <input id="billing_address_line1" value="Teststrasse 1">
                    <input id="billing_postal_code" value="12345">
                    <input id="billing_city" value="Test">
                    <input id="billing_country" value="DE">
                    <input type="checkbox" name="legal_terms_accepted" checked>
                    <input type="checkbox" name="legal_privacy_accepted" checked>
                    <input type="checkbox" name="legal_withdrawal_acknowledged" checked>
                    <button class="button-next" data-step="4" data-next-step="5">Summary</button>
                  </div>
                  <div class="order-step-content" data-step="5" style="display:none">
                    <button class="button-submit">Submit synthetic fixture</button>
                  </div>
                </div>
                <script>
                  window.themisdbOrder = {ajaxUrl: '${new URL('wp-admin/admin-ajax.php', baseURL).href}', nonce: 'synthetic', strings: {error:'Error', loading:'Loading'}};
                  window.savedSteps = [];
                  document.addEventListener('themisdb-order-step', e => savedSteps.push(e.detail));
                  document.addEventListener('themisdb-order-submitted', () => document.querySelectorAll('.order-step-content').forEach(el => {el.hidden = true;}));
                </script>
                <script src="${new URL('wp-includes/js/jquery/jquery.min.js', baseURL).href}"></script>
                <script src="${new URL('wp-content/plugins/themisdb-order-request/assets/js/order-request.js', baseURL).href}"></script>
                </body>` });
        });
        await page.route('**/wp-admin/admin-ajax.php', route => {
            requests.push(new URLSearchParams(route.request().postData()));
            return route.fulfill({ json: { success: true, data: { message: 'Synthetic confirmation; no actual order' } } });
        });
        await page.goto(new URL('?synthetic_checkout_fixture=1', baseURL).href);
        await page.locator('.button-next').click();
        await page.locator('.order-step-content[data-step="5"]').waitFor({ state: 'visible' });
        assert.deepEqual(await page.evaluate(() => savedSteps), [{ step: 5, persisted: true }]);
        assert.equal(requests[0].get('action'), 'themisdb_save_order_step');
        assert.equal(requests[0].get('data[legal_withdrawal_acknowledged]'), '1');
        assert.equal(requests[0].get('flow_mode'), 'express');
        await page.locator('.button-submit').click();
        await page.getByRole('status').waitFor();
        assert.equal(await page.locator('.order-step-content[data-step="5"]').isVisible(), false);
        assert.equal(requests[1].get('action'), 'themisdb_submit_order');
        await page.waitForTimeout(2200);
        assert.equal(await page.getByRole('status').isVisible(), true);
        assert.equal(fixtureLoads, 1);
        assert.equal(requests.length, 2);
    } finally {
        await browser.close();
    }
});

test('products header cart opens one accessible overlay, without separate checkout pages', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        assert.equal((await page.goto(new URL('products/', baseURL).href)).status(), 200);
        assert.equal(await page.locator('.themisdb-product-detail').count(), 1);
        assert.ok(await page.locator('.tpd-edition-card').count() > 0);
        assert.equal(await page.locator('.tpd-training-check').count(), 0);
        const trigger = page.locator('.site-header [data-themisdb-open-cart]');
        assert.equal(await trigger.count(), 1);
        const location = page.url();
        await trigger.click();
        const dialog = page.locator('#themisdb-checkout-dialog');
        assert.equal(await dialog.isVisible(), true);
        assert.equal(await page.locator('dialog[open]').count(), 1);
        const frame = dialog.locator('iframe').contentFrame();
        await frame.locator('.themisdb-shopping-cart').waitFor();
        const productsLink = frame.getByRole('link', { name: 'Lizenzprodukte auswählen' });
        assert.equal(await productsLink.getAttribute('target'), '_parent');
        assert.equal(new URL(await productsLink.getAttribute('href')).pathname, '/wordpress/products/');
        assert.equal(page.url(), location);
        const realFrame = page.frames().find(candidate => candidate.url().includes('themisdb_checkout_frame'));
        const originalFrameURL = realFrame.url();
        await realFrame.evaluate(() => {
            document.dispatchEvent(new CustomEvent('themisdb-order-step', { detail: { step: 5, persisted: false } }));
        });
        assert.equal(realFrame.url(), originalFrameURL);
        await Promise.all([
            page.waitForEvent('framenavigated', candidate => candidate.parentFrame() && new URL(candidate.url()).searchParams.get('view') === 'checkout'),
            realFrame.evaluate(() => {
                const url = new URL(location.href);
                // Change only history, not the server request: no real product selection.
                url.searchParams.set('edition', 'synthetic-only');
                url.searchParams.set('modules', 'synthetic-module');
                history.replaceState(null, '', url.href);
                document.dispatchEvent(new CustomEvent('themisdb-order-step', { detail: { step: 5, persisted: true } }));
            }),
        ]);
        await frame.locator('.themisdb-shopping-cart').waitFor();
        const cleanURL = new URL(realFrame.url());
        assert.equal(cleanURL.searchParams.get('view'), 'checkout');
        assert.equal(cleanURL.searchParams.has('edition'), false);
        assert.equal(cleanURL.searchParams.has('modules'), false);
        await realFrame.evaluate(() => {
            const fixture = document.createElement('section');
            fixture.className = 'themisdb-order-flow';
            fixture.innerHTML = '<div class="order-steps">Steps</div><div class="order-step-content">Submit</div><div role="status">Synthetic submission confirmation</div>';
            document.body.append(fixture);
            document.dispatchEvent(new CustomEvent('themisdb-order-submitted'));
        });
        assert.equal(await frame.locator('.order-step-content').isVisible(), false);
        assert.equal(await frame.getByRole('status').isVisible(), true);
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            const bounds = await dialog.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
        }
        await dialog.getByRole('button', { name: 'Schließen' }).click();
        assert.equal(await dialog.isVisible(), false);
        assert.equal(await trigger.evaluate(el => el === document.activeElement), true);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        await trigger.click();
        await frame.getByRole('link', { name: 'Lizenzprodukte auswählen' }).focus();
        await page.keyboard.press('Escape');
        await dialog.waitFor({ state: 'hidden' });
        assert.equal(await trigger.evaluate(el => el === document.activeElement), true);

        // Intercept explicit selection: browser regression must not create real draft orders.
        let selection;
        await page.route(url => url.searchParams.has('themisdb_checkout_frame'), route => {
            selection = new URL(route.request().url());
            return route.fulfill({ contentType: 'text/html', body: '<p id="synthetic-cart">Synthetic license cart; no order created</p>' });
        });
        const edition = await page.locator('.tpd-edition-card').last().getAttribute('data-edition');
        await page.locator('.tpd-edition-card').last().click();
        const module = page.locator('.tpd-module-check:not(:disabled)').first();
        let code;
        if (await module.count()) {
            code = await module.getAttribute('value');
            await module.check();
        }
        await page.locator('.tpd-order-btn').click();
        await dialog.locator('iframe').contentFrame().locator('#synthetic-cart').waitFor();
        assert.equal(selection.searchParams.get('edition'), edition);
        if (code) assert.ok(selection.searchParams.get('modules').split(',').includes(code));
        assert.ok(selection.searchParams.get('_wpnonce'));
        assert.equal(page.url(), location);
        await page.keyboard.press('Escape');
        assert.equal(await dialog.isVisible(), false);
        await page.goto(baseURL);
        assert.equal(await page.locator('.site-header [data-themisdb-open-cart]').count(), 1);
        for (const width of [1440, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            const headerBounds = await page.locator('.site-header').boundingBox();
            const cartBounds = await page.locator('.site-header [data-themisdb-open-cart]').boundingBox();
            assert.ok(headerBounds.x >= 0 && headerBounds.x + headerBounds.width <= width + 1);
            assert.ok(cartBounds.x >= 0 && cartBounds.x + cartBounds.width <= width + 1);
        }
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
