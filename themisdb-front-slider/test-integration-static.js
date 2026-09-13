#!/usr/bin/env node

/**
 * Static Integration Analysis for ThemisDB Front Slider
 * Überprüft die Korrektheit der Plugin-Integration ohne WordPress-Laufzeit
 */

const fs = require('fs');
const path = require('path');

const pluginDir = __dirname;

console.log('\n' + '='.repeat(70));
console.log('🔍 ThemisDB Front Slider - Static Integration Analysis');
console.log('='.repeat(70));

const checks = {
  passed: 0,
  failed: 0,
  warnings: 0
};

function pass(msg) {
  console.log(`  ✅ ${msg}`);
  checks.passed++;
}

function fail(msg) {
  console.log(`  ❌ ${msg}`);
  checks.failed++;
}

function warn(msg) {
  console.log(`  ⚠️  ${msg}`);
  checks.warnings++;
}

// ========== 1. File Existence ==========
console.log('\n📁 1. File Structure Check');
const requiredFiles = [
  'themisdb-front-slider.php',
  'block.json',
  'assets/js/front-slider.js',
  'assets/js/block.js',
  'assets/css/front-slider.css',
  'assets/css/block-editor.css',
  'templates/slider.php'
];

requiredFiles.forEach(file => {
  const filePath = path.join(pluginDir, file);
  if (fs.existsSync(filePath)) {
    const size = fs.statSync(filePath).size;
    pass(`${file} (${size} bytes)`);
  } else {
    fail(`${file} not found`);
  }
});

// ========== 2. Plugin Header ==========
console.log('\n📋 2. Plugin Header Check');
const mainFile = path.join(pluginDir, 'themisdb-front-slider.php');
const mainContent = fs.readFileSync(mainFile, 'utf8');

const headerFields = [
  { key: 'Plugin Name:', regex: /Plugin Name:\s*(.+)/i },
  { key: 'Version:', regex: /Version:\s*(.+)/i },
  { key: 'Author:', regex: /Author:\s*(.+)/i },
  { key: 'License:', regex: /License:\s*(.+)/i }
];

headerFields.forEach(field => {
  const match = mainContent.match(field.regex);
  if (match) {
    pass(`${field.key} ${match[1].trim()}`);
  } else {
    fail(`${field.key} not found`);
  }
});

// ========== 3. Asset Registration ==========
console.log('\n📦 3. Asset Registration Check');

const registrations = [
  { name: 'themisdb-front-slider-css', pattern: /wp_register_style\(\s*'themisdb-front-slider-css'/i },
  { name: 'themisdb-front-slider-editor-css', pattern: /wp_register_style\(\s*'themisdb-front-slider-editor-css'/i },
  { name: 'themisdb-front-slider-js', pattern: /wp_register_script\(\s*'themisdb-front-slider-js'/i },
  { name: 'themisdb-front-slider-block-js', pattern: /wp_register_script\(\s*'themisdb-front-slider-block-js'/i }
];

registrations.forEach(reg => {
  if (mainContent.match(reg.pattern)) {
    pass(`${reg.name} registered`);
  } else {
    fail(`${reg.name} not registered`);
  }
});

// ========== 4. Enqueue Logic ==========
console.log('\n⚙️  4. Asset Enqueue Logic Check');

const enqueueChecks = [
  { name: 'Frontend style enqueue', pattern: /wp_enqueue_style\(\s*'themisdb-front-slider-css'\s*\)/ },
  { name: 'Frontend script enqueue', pattern: /wp_enqueue_script\(\s*'themisdb-front-slider-js'\s*\)/ },
  { name: 'Editor style enqueue', pattern: /'themisdb-front-slider-editor-css'/ },
  { name: 'Filter hook for style', pattern: /apply_filters\(\s*'themisdb_front_slider_enqueue_frontend_style'/ },
  { name: 'Filter hook for script', pattern: /apply_filters\(\s*'themisdb_front_slider_enqueue_frontend_script'/ }
];

enqueueChecks.forEach(check => {
  if (mainContent.match(check.pattern)) {
    pass(check.name);
  } else {
    fail(check.name);
  }
});

// ========== 5. Block Registration ==========
console.log('\n🔷 5. Block Registration Check');

const blockChecks = [
  { name: 'register_block_type function', pattern: /register_block_type\s*\(/ },
  { name: 'register_block_type_from_metadata', pattern: /register_block_type_from_metadata/ },
  { name: 'Block render callback', pattern: /render_callback.*themisdb_fs_render_block/ },
  { name: 'Block editor script handle', pattern: /'editor_script'\s*=>\s*'themisdb-front-slider-block-js'/ }
];

blockChecks.forEach(check => {
  if (mainContent.match(check.pattern)) {
    pass(check.name);
  } else {
    fail(check.name);
  }
});

// ========== 6. Shortcode Registration ==========
console.log('\n🏷️  6. Shortcode Check');

if (mainContent.match(/add_shortcode\s*\(\s*'themisdb_front_slider'/)) {
  pass('Shortcode [themisdb_front_slider] registered');
  
  const shortcodeFunc = mainContent.match(/function themisdb_fs_shortcode\s*\(/);
  if (shortcodeFunc) {
    pass('Shortcode handler function defined');
  } else {
    fail('Shortcode handler function not defined');
  }
} else {
  fail('Shortcode not registered');
}

// ========== 7. JavaScript Files Check ==========
console.log('\n🔨 7. JavaScript Files Check');

const frontSliderJs = path.join(pluginDir, 'assets/js/front-slider.js');
const frontSliderContent = fs.readFileSync(frontSliderJs, 'utf8');

const jsChecks = [
  { name: 'initSlider function', pattern: /function initSlider\s*\(/ },
  { name: 'boot function', pattern: /function boot\s*\(/ },
  { name: 'goTo navigation', pattern: /function goTo\s*\(/ },
  { name: 'autoplay timer', pattern: /startTimer|stopTimer/ },
  { name: 'keyboard events', pattern: /ArrowLeft|ArrowRight/ },
  { name: 'touch/swipe events', pattern: /pointerdown|pointermove|pointerup/ },
  { name: 'ARIA management', pattern: /aria-hidden|aria-selected|tabindex/ }
];

jsChecks.forEach(check => {
  if (frontSliderContent.match(check.pattern)) {
    pass(`${check.name} implemented`);
  } else {
    fail(`${check.name} missing`);
  }
});

// ========== 8. Template Check ==========
console.log('\n🎨 8. Template Structure Check');

const sliderTemplate = path.join(pluginDir, 'templates/slider.php');
const templateContent = fs.readFileSync(sliderTemplate, 'utf8');

const templateChecks = [
  { name: '.themisdb-fs-wrapper', pattern: /class="themisdb-fs-wrapper/ },
  { name: '.themisdb-fs-track', pattern: /class="themisdb-fs-track/ },
  { name: '.themisdb-fs-slide', pattern: /class="themisdb-fs-slide/ },
  { name: 'Navigation buttons', pattern: /themisdb-fs-prev|themisdb-fs-next/ },
  { name: 'Dot pagination', pattern: /themisdb-fs-dots/ },
  { name: 'Timer bar', pattern: /themisdb-fs-timer-bar/ },
  { name: 'ARIA attributes', pattern: /role="|aria-/ }
];

templateChecks.forEach(check => {
  if (templateContent.match(check.pattern)) {
    pass(check.name);
  } else {
    fail(check.name);
  }
});

// ========== 9. CSS Files Check ==========
console.log('\n🎨 9. CSS Files Check');

const frontSliderCss = path.join(pluginDir, 'assets/css/front-slider.css');
const cssContent = fs.readFileSync(frontSliderCss, 'utf8');

const cssChecks = [
  { name: '.themisdb-fs-wrapper', pattern: /\.themisdb-fs-wrapper/ },
  { name: 'CSS custom properties', pattern: /--tfs-/ },
  { name: 'Transform animations', pattern: /transform|translateX/ },
  { name: 'Responsive design', pattern: /@media/ }
];

cssChecks.forEach(check => {
  if (cssContent.match(check.pattern)) {
    pass(check.name);
  } else {
    fail(check.name);
  }
});

// ========== 10. Block.json Consistency ==========
console.log('\n📄 10. Block Configuration Check');

const blockJsonPath = path.join(pluginDir, 'block.json');
const blockJson = JSON.parse(fs.readFileSync(blockJsonPath, 'utf8'));

const blockConfigChecks = [
  { key: 'name', expected: 'themisdb/front-slider' },
  { key: 'category', expected: 'widgets' },
  { key: 'editorScript', expected: 'themisdb-front-slider-block-js' }
];

blockConfigChecks.forEach(check => {
  const actual = blockJson[check.key];
  if (actual === check.expected) {
    pass(`block.json ${check.key}: ${actual}`);
  } else {
    fail(`block.json ${check.key}: expected "${check.expected}", got "${actual}"`);
  }
});

// Attributes check
if (blockJson.attributes && typeof blockJson.attributes === 'object') {
  const attributeCount = Object.keys(blockJson.attributes).length;
  pass(`${attributeCount} block attributes defined`);
} else {
  fail('Block attributes not properly defined');
}

// ========== Summary ==========
console.log('\n' + '='.repeat(70));
console.log('📊 Integration Analysis Summary');
console.log('='.repeat(70));
console.log(`  ✅ Passed: ${checks.passed}`);
console.log(`  ❌ Failed: ${checks.failed}`);
console.log(`  ⚠️  Warnings: ${checks.warnings}`);

if (checks.failed === 0) {
  console.log('\n🎉 All integration checks passed!');
  process.exit(0);
} else {
  console.log('\n⚠️  Some checks failed. Review the issues above.');
  process.exit(1);
}
