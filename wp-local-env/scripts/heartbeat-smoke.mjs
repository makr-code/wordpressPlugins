import { chromium } from 'playwright';

const baseUrl = 'http://localhost:8888';

async function getCircleState(page, circleSelector) {
    return page.$eval(circleSelector, (circle) => {
        const style = window.getComputedStyle(circle);
        return {
            classList: [...circle.classList],
            hasHeartbeatClass: circle.classList.contains('heartbeat'),
            animationName: style.animationName,
            animationDuration: style.animationDuration,
        };
    });
}

async function run() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1100 } });

    try {
        await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 120000 });
        await page.waitForSelector('#nav-graph-toggle', { timeout: 30000 });
        await page.click('#nav-graph-toggle');

        await page.waitForSelector('#themisdb-nav-overlay.visible', { timeout: 30000 });
        await page.waitForSelector('svg.bloom-graph', { timeout: 30000 });

        const forceCircleSelector = 'svg.bloom-graph .node circle';
        await page.waitForSelector(forceCircleSelector, { timeout: 30000 });

        const forceBefore = await getCircleState(page, forceCircleSelector);
        await page.$eval(forceCircleSelector, (circle) => {
            const node = circle.closest('g.node') || circle.parentElement;
            if (node) {
                node.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const forceAfterHover = await getCircleState(page, forceCircleSelector);

        await page.$eval(forceCircleSelector, (circle) => {
            const node = circle.closest('g.node') || circle.parentElement;
            if (node) {
                node.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const forceAfterMouseOut = await getCircleState(page, forceCircleSelector);

        await page.$eval(forceCircleSelector, (circle) => {
            const node = circle.closest('g.node') || circle.parentElement;
            if (node) {
                if (!node.hasAttribute('tabindex')) {
                    node.setAttribute('tabindex', '0');
                }
                node.focus();
                node.dispatchEvent(new FocusEvent('focus', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const forceAfterFocus = await getCircleState(page, forceCircleSelector);

        await page.$eval(forceCircleSelector, (circle) => {
            const node = circle.closest('g.node') || circle.parentElement;
            if (node) {
                node.dispatchEvent(new FocusEvent('blur', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const forceAfterBlur = await getCircleState(page, forceCircleSelector);

        await page.selectOption('#graph-layout-mode', 'galaxy');
        await page.waitForSelector('svg.bloom-graph.galaxy-layout', { timeout: 30000 });

        const galaxyCircleSelector = 'svg.bloom-graph.galaxy-layout .graph-node circle';
        await page.waitForSelector(galaxyCircleSelector, { timeout: 30000 });

        const galaxyBefore = await getCircleState(page, galaxyCircleSelector);
        await page.$eval(galaxyCircleSelector, (circle) => {
            const node = circle.closest('g.graph-node') || circle.parentElement;
            if (node) {
                node.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const galaxyAfterHover = await getCircleState(page, galaxyCircleSelector);

        await page.$eval(galaxyCircleSelector, (circle) => {
            const node = circle.closest('g.graph-node') || circle.parentElement;
            if (node) {
                node.dispatchEvent(new MouseEvent('mouseout', { bubbles: true, cancelable: true }));
            }
        });
        await page.waitForTimeout(180);
        const galaxyAfterMouseOut = await getCircleState(page, galaxyCircleSelector);

        await page.screenshot({ path: 'heartbeat-smoke.png', fullPage: true });

        console.log(JSON.stringify({
            baseUrl,
            force: {
                before: forceBefore,
                afterHover: forceAfterHover,
                afterMouseOut: forceAfterMouseOut,
                afterFocus: forceAfterFocus,
                afterBlur: forceAfterBlur,
            },
            galaxy: {
                before: galaxyBefore,
                afterHover: galaxyAfterHover,
                afterMouseOut: galaxyAfterMouseOut,
            },
            screenshot: 'c:/VCC/wordpressPlugins/wp-local-env/heartbeat-smoke.png',
        }, null, 2));
    } finally {
        await browser.close();
    }
}

run().catch((error) => {
    console.error(error);
    process.exit(1);
});
