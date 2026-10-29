const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const baseURL = process.env.WORDPRESS_URL || 'http://localhost/wordpress/';
const asset = path => new URL('wp-content/' + path, baseURL).href;

test('plugin base styles are standalone, scoped and accept theme palette tokens', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        const url = new URL('plugin-presentation-fixture/', baseURL).href;
        const css = ['order-request', 'product-detail', 'shopping-cart']
            .map(name => asset('plugins/themisdb-order-request/assets/css/' + name + '.css'));
        css.push(asset('plugins/themisdb-support-portal/assets/css/support-portal.css'));
        await page.route(url, route => route.fulfill({
            contentType: 'text/html',
            body: '<!doctype html><meta name="viewport" content="width=device-width, initial-scale=1">' +
                css.map(href => '<link rel="stylesheet" href="' + href + '">').join('') +
                '<div id="outside" class="form-group loading"><label>Outside plugins</label></div>' +
                '<div class="themisdb-order-flow"><div class="order-content"><div class="form-group"><label>Inside</label>' +
                '<input type="text"></div><div class="order-navigation"><button class="button-next">Next</button></div></div></div>' +
                '<div class="themisdb-product-detail"><div class="tpd-edition-card tpd-edition-card--selected">Edition</div></div>' +
                '<div class="themisdb-shopping-cart"><a class="tsc-btn tsc-btn--primary">Checkout</a></div>' +
                '<div class="themisdb-support-login-wrap"><button class="themisdb-support-btn themisdb-support-btn-primary">Login</button></div>',
        }));
        await page.goto(url);
        const style = (selector, property) => page.locator(selector).evaluate((el, key) => getComputedStyle(el)[key], property);
        assert.equal(await style('.order-content', 'backgroundColor'), 'rgb(255, 255, 255)');
        assert.equal(await style('.button-next', 'backgroundColor'), 'rgb(0, 115, 170)');
        assert.equal(await style('.tsc-btn', 'backgroundColor'), 'rgb(37, 99, 235)');
        assert.equal(await style('.tsc-btn', 'whiteSpace'), 'nowrap');
        assert.equal(await style('#outside', 'opacity'), '1');
        assert.equal(await style('#outside', 'marginBottom'), '0px');
        assert.equal(await style('#outside label', 'display'), 'inline');
        assert.equal(await style('.themisdb-order-flow label', 'display'), 'block');
        await page.addStyleTag({ url: asset('themes/themisdb-pulse/style.css') });
        await page.addStyleTag({ url: asset('themes/themisdb-pulse/assets/css/color-schemes.css') });
        for (const [scheme, color] of [
            ['tv3-scheme-modern-slate', 'rgb(46, 111, 168)'],
            ['tv3-scheme-themis-original', 'rgb(31, 127, 97)'],
        ]) {
            await page.evaluate(value => { document.body.className = value; }, scheme);
            for (const selector of ['.button-next', '.tsc-btn', '.themisdb-support-btn-primary']) {
                await page.waitForFunction(({ selector, color }) =>
                    getComputedStyle(document.querySelector(selector)).backgroundColor === color,
                { selector, color });
                assert.equal(await style(selector, 'backgroundColor'), color, scheme + ' styles ' + selector);
            }
            await page.waitForFunction(color =>
                getComputedStyle(document.querySelector('.tpd-edition-card--selected')).borderTopColor === color, color);
            assert.equal(await style('.tpd-edition-card--selected', 'borderTopColor'), color);
        }
    } finally {
        await browser.close();
    }
});

test('published shop and support use Pulse palette and retain mobile layout', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();
        for (const slug of ['shop/', 'support/']) {
            const response = await page.goto(new URL(slug, baseURL).href);
            assert.equal(response.status(), 200);
            assert.equal(await page.locator('link[href*="assets/css/order-request.css"]').count(), 1);
            const selector = slug === 'shop/' ? '.themisdb-shop-page' : '.themisdb-support-login-wrap';
            await page.locator(selector).first().waitFor({ state: 'attached' });
            const colors = await page.locator(selector).first().evaluate(el => ({
                primary: getComputedStyle(el).getPropertyValue('--themisdb-ui-primary').trim(),
                theme: getComputedStyle(document.body).getPropertyValue('--tv3-accent-primary').trim(),
            }));
            assert.equal(colors.primary, colors.theme);
            assert.ok(colors.primary);
            for (const width of [1440, 390, 320]) {
                await page.setViewportSize({ width, height: 900 });
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, slug + ' overflows at ' + width);
            }
        }
    } finally {
        await browser.close();
    }
});
