#!/usr/bin/env node

/**
 * ThemisDB Front Slider - Playwright Debug & Layout Test
 * 
 * Überprüft:
 * 1. Asset-Registrierung & -Enqueue
 * 2. DOM-Struktur des Sliders
 * 3. JavaScript-Funktionalität
 * 4. CSS-Rendering
 * 5. Accessibility (ARIA-Attribute)
 * 6. Browser Console-Fehler
 */

const { chromium } = require('playwright');

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const TEST_SLUG = 'test-slider'; // WordPress page slug with shortcode

async function runTests() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    recordVideo: { dir: './test-videos' }
  });
  
  const page = await context.newPage();
  
  // Capture console messages
  const consoleLogs = [];
  page.on('console', msg => {
    consoleLogs.push({
      type: msg.type(),
      text: msg.text(),
      location: msg.location()
    });
  });

  // Capture page errors
  const pageErrors = [];
  page.on('pageerror', err => {
    pageErrors.push({
      message: err.message,
      stack: err.stack
    });
  });

  // Capture network requests/responses
  const networkLog = [];
  page.on('response', response => {
    if (response.url().includes('assets/')) {
      networkLog.push({
        url: response.url(),
        status: response.status(),
        contentType: response.headers()['content-type']
      });
    }
  });

  try {
    console.log(`\n📍 Navigating to: ${BASE_URL}/${TEST_SLUG}`);
    await page.goto(`${BASE_URL}/${TEST_SLUG}`, { waitUntil: 'networkidle' });
    
    // ========== TEST 1: Asset Loading ==========
    console.log('\n✅ TEST 1: Asset Loading');
    const assets = await page.evaluate(() => {
      const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .map(link => ({
          href: link.href,
          loaded: link.sheet ? 'yes' : 'no'
        }));
      
      const scripts = Array.from(document.querySelectorAll('script[src]'))
        .map(script => ({
          src: script.src,
          loaded: 'yes'
        }));
      
      return { styles, scripts };
    });
    
    console.log('Stylesheets loaded:');
    assets.styles
      .filter(s => s.href.includes('front-slider'))
      .forEach(s => console.log(`  - ${s.href}`));
    
    console.log('Scripts loaded:');
    assets.scripts
      .filter(s => s.src.includes('front-slider'))
      .forEach(s => console.log(`  - ${s.src}`));

    // ========== TEST 2: DOM Structure ==========
    console.log('\n✅ TEST 2: DOM Structure');
    const sliderStructure = await page.evaluate(() => {
      const wrapper = document.querySelector('.themisdb-fs-wrapper');
      if (!wrapper) return { error: 'Slider wrapper not found' };
      
      return {
        wrapper: {
          classes: wrapper.className,
          id: wrapper.id,
          hasTrack: !!wrapper.querySelector('.themisdb-fs-track'),
          slideCount: wrapper.querySelectorAll('.themisdb-fs-slide').length,
          hasNav: !!wrapper.querySelector('.themisdb-fs-prev'),
          hasDots: !!wrapper.querySelector('.themisdb-fs-dots'),
          hasTimer: !!wrapper.querySelector('.themisdb-fs-timer-bar')
        },
        dataAttributes: {
          interval: wrapper.getAttribute('data-interval'),
          autoplay: wrapper.getAttribute('data-autoplay'),
          respectReducedMotion: wrapper.getAttribute('data-respect-reduced-motion'),
          preset: wrapper.getAttribute('data-preset')
        }
      };
    });
    
    if (sliderStructure.error) {
      console.log(`  ❌ ${sliderStructure.error}`);
    } else {
      console.log(`  ✓ Wrapper found with class: ${sliderStructure.wrapper.classes}`);
      console.log(`  ✓ Slides: ${sliderStructure.wrapper.slideCount}`);
      console.log(`  ✓ Navigation: ${sliderStructure.wrapper.hasNav ? 'YES' : 'NO'}`);
      console.log(`  ✓ Dots: ${sliderStructure.wrapper.hasDots ? 'YES' : 'NO'}`);
      console.log(`  ✓ Timer Bar: ${sliderStructure.wrapper.hasTimer ? 'YES' : 'NO'}`);
      console.log(`  ✓ Data attributes: ${JSON.stringify(sliderStructure.dataAttributes, null, 2)}`);
    }

    // ========== TEST 3: JavaScript Functionality ==========
    console.log('\n✅ TEST 3: JavaScript Functionality');
    const jsState = await page.evaluate(() => {
      const wrapper = document.querySelector('.themisdb-fs-wrapper');
      if (!wrapper) return { error: 'Slider not found' };
      
      return {
        initialized: wrapper.dataset.tfsInit === '1',
        firstSlideActive: wrapper.querySelector('.themisdb-fs-slide.is-active') !== null,
        trackTransform: wrapper.querySelector('.themisdb-fs-track')?.style.transform || 'none'
      };
    });
    
    if (jsState.error) {
      console.log(`  ❌ ${jsState.error}`);
    } else {
      console.log(`  ✓ Initialized: ${jsState.initialized ? 'YES' : 'NO'}`);
      console.log(`  ✓ First Slide Active: ${jsState.firstSlideActive ? 'YES' : 'NO'}`);
      console.log(`  ✓ Track Transform: ${jsState.trackTransform}`);
    }

    // ========== TEST 4: Accessibility ==========
    console.log('\n✅ TEST 4: Accessibility (ARIA)');
    const a11y = await page.evaluate(() => {
      const wrapper = document.querySelector('.themisdb-fs-wrapper');
      if (!wrapper) return { error: 'Slider not found' };
      
      const slides = wrapper.querySelectorAll('.themisdb-fs-slide');
      const ariaChecks = {
        wrapperRole: wrapper.getAttribute('role'),
        wrapperLabel: wrapper.getAttribute('aria-label'),
        wrapperLive: wrapper.getAttribute('aria-live'),
        firstSlideAriaHidden: slides[0]?.getAttribute('aria-hidden'),
        tabIndexManaged: Array.from(slides[0]?.querySelectorAll('a, button')).some(el => 
          el.getAttribute('tabindex') !== null
        )
      };
      
      return ariaChecks;
    });
    
    if (a11y.error) {
      console.log(`  ❌ ${a11y.error}`);
    } else {
      console.log(`  ✓ Wrapper role: ${a11y.wrapperRole}`);
      console.log(`  ✓ ARIA label: ${a11y.wrapperLabel || 'not set'}`);
      console.log(`  ✓ Aria-live: ${a11y.wrapperLive || 'not set'}`);
      console.log(`  ✓ First slide aria-hidden: ${a11y.firstSlideAriaHidden}`);
      console.log(`  ✓ Tab index managed: ${a11y.tabIndexManaged ? 'YES' : 'NO'}`);
    }

    // ========== TEST 5: CSS Rendering ==========
    console.log('\n✅ TEST 5: CSS Rendering');
    const cssState = await page.evaluate(() => {
      const wrapper = document.querySelector('.themisdb-fs-wrapper');
      if (!wrapper) return { error: 'Slider not found' };
      
      const computed = window.getComputedStyle(wrapper);
      const track = wrapper.querySelector('.themisdb-fs-track');
      
      return {
        displayType: computed.display,
        accentColor: computed.getPropertyValue('--tfs-accent').trim(),
        trackHeight: track?.getBoundingClientRect().height,
        wrapperVisible: wrapper.offsetHeight > 0
      };
    });
    
    if (cssState.error) {
      console.log(`  ❌ ${cssState.error}`);
    } else {
      console.log(`  ✓ Display: ${cssState.displayType}`);
      console.log(`  ✓ Accent color: ${cssState.accentColor}`);
      console.log(`  ✓ Visible: ${cssState.wrapperVisible ? 'YES' : 'NO'} (height: ${cssState.trackHeight}px)`);
    }

    // ========== TEST 6: Interaction ==========
    console.log('\n✅ TEST 6: Interaction Test');
    const nextBtn = await page.$('.themisdb-fs-next');
    if (nextBtn) {
      const trackBefore = await page.evaluate(() => 
        document.querySelector('.themisdb-fs-track')?.style.transform
      );
      
      await nextBtn.click();
      await page.waitForTimeout(1000);
      
      const trackAfter = await page.evaluate(() => 
        document.querySelector('.themisdb-fs-track')?.style.transform
      );
      
      console.log(`  ✓ Track before click: ${trackBefore}`);
      console.log(`  ✓ Track after click: ${trackAfter}`);
      console.log(`  ✓ Changed: ${trackBefore !== trackAfter ? 'YES' : 'NO'}`);
    } else {
      console.log(`  ⚠ Next button not found`);
    }

    // ========== CONSOLE & ERRORS ==========
    console.log('\n🔍 Console Messages:');
    const frontSliderLogs = consoleLogs.filter(log => 
      log.text.includes('slider') || log.text.includes('themisdb')
    );
    
    if (frontSliderLogs.length === 0) {
      console.log('  (no slider-related messages)');
    } else {
      frontSliderLogs.forEach(log => {
        console.log(`  [${log.type.toUpperCase()}] ${log.text}`);
      });
    }

    if (pageErrors.length > 0) {
      console.log('\n❌ Page Errors:');
      pageErrors.forEach(err => {
        console.log(`  - ${err.message}`);
      });
    } else {
      console.log('  ✓ No JavaScript errors');
    }

    // ========== Screenshot ==========
    console.log('\n📸 Taking screenshot...');
    await page.screenshot({ path: './slider-test-screenshot.png', fullPage: true });
    console.log('  ✓ Screenshot saved: slider-test-screenshot.png');

    console.log('\n✅ All tests completed!');

  } catch (error) {
    console.error('❌ Test failed:', error.message);
  } finally {
    await browser.close();
  }
}

runTests().catch(console.error);
