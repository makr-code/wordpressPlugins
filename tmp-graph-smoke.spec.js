const { test } = require('playwright/test');

test('graph runtime smoke', async ({ page }) => {
  await page.goto('http://localhost/wordpress/', { waitUntil: 'networkidle' });
  const values = await page.evaluate(() => ({
    hasGraph: !!window.themisdbGraphV2,
    nodeCount: (window.themisdbGraphV2 && window.themisdbGraphV2.graph && window.themisdbGraphV2.graph.nodes) ? window.themisdbGraphV2.graph.nodes.length : 0,
    linkCount: (window.themisdbGraphV2 && window.themisdbGraphV2.graph && window.themisdbGraphV2.graph.links) ? window.themisdbGraphV2.graph.links.length : 0,
    hasGraphStore: !!(window.ThemisDBGraphV2 && window.ThemisDBGraphV2.GraphStore),
    hasCanvasRenderer: !!(window.ThemisDBGraphV2 && window.ThemisDBGraphV2.CanvasRenderer),
    hasCanvas: !!document.querySelector('[data-graph-v2-canvas] canvas'),
    triggerCount: document.querySelectorAll('[data-graph-v2-toggle]').length,
  }));
  console.log(JSON.stringify(values, null, 2));
  const toggle = page.locator('[data-graph-v2-toggle]').first();
  await toggle.click();
  await page.waitForTimeout(500);
  const canvasInfo = await page.evaluate(() => {
    const canvasHost = document.querySelector('[data-graph-v2-canvas]');
    const canvas = canvasHost ? canvasHost.querySelector('canvas') : null;
    return {
      visible: !!canvas,
      width: canvas ? canvas.width : 0,
      height: canvas ? canvas.height : 0,
      styleWidth: canvas ? canvas.style.width : null,
      styleHeight: canvas ? canvas.style.height : null,
      hidden: !!canvasHost && canvasHost.hidden,
      shellDisplay: getComputedStyle(document.querySelector('[data-graph-v2-shell]')).display,
      shellWidth: document.querySelector('[data-graph-v2-shell]') ? document.querySelector('[data-graph-v2-shell]').getBoundingClientRect().width : 0,
      shellHeight: document.querySelector('[data-graph-v2-shell]') ? document.querySelector('[data-graph-v2-shell]').getBoundingClientRect().height : 0,
    };
  });
  console.log(JSON.stringify(canvasInfo, null, 2));
  await page.screenshot({ path: 'C:/Projects/wordpressPlugins/graph-smoke.png', fullPage: true });
});
