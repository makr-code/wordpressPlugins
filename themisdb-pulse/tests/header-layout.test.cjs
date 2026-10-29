const assert = require('node:assert/strict');
const { test } = require('node:test');
const { chromium } = require('playwright');

const baseURL = process.env.WORDPRESS_URL || 'http://localhost/wordpress/';

test('header remains one row and native menu/search work at every breakpoint', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage({ reducedMotion: 'reduce' });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));

        for (const width of [1440, 1200, 1199, 860, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            for (const path of ['', '?s=voice']) {
                const response = await page.goto(new URL(path, baseURL).href);
                assert.equal(response.status(), 200);
                const layout = await page.locator('.site-header').evaluate(header => {
                    const row = header.querySelector('.tv3-header-row');
                    const bounds = row.getBoundingClientRect();
                    const children = [...row.children].map(element => element.getBoundingClientRect());
                    const open = header.querySelector('.wp-block-navigation__responsive-container-open');
                    return {
                        height: header.getBoundingClientRect().height,
                        oneRow: children.every(rect => Math.abs(rect.top + rect.height / 2 - bounds.top - bounds.height / 2) < 2),
                        fits: children.every(rect => rect.left >= 0 && rect.right <= innerWidth),
                        overflow: header.scrollWidth > header.clientWidth + 1,
                        menu: getComputedStyle(open).display !== 'none',
                    };
                });
                assert.ok(layout.oneRow && layout.fits && !layout.overflow, JSON.stringify({ width, path, layout }));
                assert.ok(layout.height <= 70, `Header too tall at ${width}px`);
                assert.equal(layout.menu, width < 1200);
            }

            const search = page.locator('.tv3-header-search');
            await search.locator('button').click();
            const input = search.locator('input[type=search]');
            await input.waitFor({ state: 'visible' });
            const rect = await input.boundingBox();
            assert.ok(rect.width >= 150 && rect.x >= 0 && rect.x + rect.width <= width);
            assert.ok(await input.evaluate(element => document.activeElement === element));
            await page.keyboard.press('Escape');
            await page.waitForFunction(() => document.querySelector('.tv3-header-search').classList.contains('wp-block-search__searchfield-hidden'));

            if (width < 1200) {
                const open = page.locator('.tv3-header-split-left .wp-block-navigation__responsive-container-open');
                await open.click();
                const panel = page.locator('.tv3-header-split-left .is-menu-open');
                await panel.waitFor({ state: 'visible' });
                const rect = await panel.boundingBox();
                assert.ok(Math.abs(rect.width - width) < 2 && rect.height >= 898);
                assert.ok(await panel.locator('a:visible').count() >= 1);
                await page.keyboard.press('Escape');
                await panel.waitFor({ state: 'hidden' });
                assert.ok(await open.evaluate(element => document.activeElement === element));
            }
        }
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
