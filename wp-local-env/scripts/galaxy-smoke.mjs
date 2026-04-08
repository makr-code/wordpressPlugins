import { chromium } from 'playwright';

const baseUrl = 'http://localhost:8888';

async function run() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1100 } });

    try {
        await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 120000 });

        await page.waitForSelector('#nav-graph-toggle', { timeout: 30000 });
        await page.click('#nav-graph-toggle');

        await page.waitForSelector('#themisdb-nav-overlay.visible', { timeout: 30000 });
        await page.waitForTimeout(1200);

        const hasGraph = await page.locator('svg.bloom-graph').count();
        const hasFallback = await page.locator('.fallback-nav-tree').count();

        if (hasGraph === 0 && hasFallback === 0) {
            throw new Error('Weder D3-Graph noch Fallback-Navigation gefunden.');
        }

        let mode = 'fallback';
        let initialNodeCount = 0;
        let galaxyNodeCount = 0;
        let rotatingNodeCount = 0;
        let fallbackNodeCount = 0;

        if (hasGraph > 0) {
            mode = 'd3';
            await page.waitForSelector('#graph-layout-mode', { timeout: 30000 });
            initialNodeCount = await page.locator('svg.bloom-graph .node, svg.bloom-graph .graph-node').count();

            await page.selectOption('#graph-layout-mode', 'galaxy');
            await page.waitForTimeout(1200);
            await page.waitForSelector('svg.bloom-graph.galaxy-layout', { timeout: 30000 });

            galaxyNodeCount = await page.locator('svg.bloom-graph.galaxy-layout .graph-node.galaxy-mode').count();
            rotatingNodeCount = await page.locator('svg.bloom-graph.galaxy-layout .graph-node.galaxy-mode.rotating').count();
        } else {
            fallbackNodeCount = await page.locator('.fallback-nav-tree .nav-node').count();
        }

        const hasPanel = await page.locator('.graph-side-panel').count();
        const overlayVisible = await page.locator('#themisdb-nav-overlay.visible').count();
        const selectedMode = (await page.locator('#graph-layout-mode').count()) > 0
            ? await page.locator('#graph-layout-mode').inputValue()
            : 'fallback';

        await page.screenshot({ path: 'galaxy-smoke.png', fullPage: true });

        console.log(JSON.stringify({
            baseUrl,
            mode,
            overlayVisible: overlayVisible > 0,
            panelPresent: hasPanel > 0,
            selectedMode,
            initialNodeCount,
            galaxyNodeCount,
            rotatingNodeCount,
            fallbackNodeCount,
            screenshot: 'c:/VCC/wordpressPlugins/wp-local-env/galaxy-smoke.png'
        }, null, 2));
    } finally {
        await browser.close();
    }
}

run().catch((error) => {
    console.error(error);
    process.exit(1);
});