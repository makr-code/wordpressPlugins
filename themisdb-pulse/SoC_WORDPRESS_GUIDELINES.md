# WordPress Block Separation of Concerns (SoC) Guidelines
**themisdb-pulse**

## Principle
Maximize WordPress-native blocks (paragraph, heading, image, columns, buttons, etc.). Use HTML blocks (`<!-- wp:html -->`) only when technically necessary.

---

## ✅ **WordPress-Native First** (Preferred)

### When to Use Native Blocks:
- **Text content**: Use `wp:paragraph`, `wp:heading`, `wp:list`
- **Spacing**: Use `wp:spacer` with height/width attributes
- **Containers**: Use `wp:group`, `wp:columns` for layout
- **Images**: Use `wp:image` for responsive rendering
- **CTAs**: Use `wp:button`, `wp:buttons` for links and calls-to-action
- **Forms**: Use `wp:search`, `wp:post-content`
- **Navigation**: Use `wp:navigation`

### Benefits:
- Full theme editor support
- Easy content updates for editors
- Responsive by default
- Accessibility improvements
- Future-proof (compatible with WordPress releases)

---

## ⚠️ **HTML Blocks (wp:html) – Only When Required**

### Legitimate Use Cases:

#### 1. **JavaScript Data Attributes**
- Counter animations (`data-target`, `data-suffix`, `data-prefix`)
- Interactive components requiring JS initialization
- **Example**: `stats-bar.php` (counter animations)

#### 2. **Complex Multi-Row Grids with JavaScript**
- Tabbed interfaces (`data-tabindex`, event handlers)
- Dynamic code blocks with copy buttons
- **Example**: `query-showcase.php`, `tabs-section.php`, `cta-download.php`

#### 3. **Specialized Visual Components**
- Custom SVG rendering
- Chart/graph containers
- **Example**: None currently, but allowed if architecturally justified

#### 4. **Fallback for Unsupported Layouts**
- When WordPress blocks cannot achieve the required responsive behavior
- **Rationale**: WordPress improves; re-evaluate with each release

---

## 📊 **Current Status**

| Category | Count | Status |
|----------|-------|--------|
| **Inline Styles** (deprecated CSS) | 0 | ✅ COMPLETE |
| **wp:html Blocks** (SoC target) | 0 | ✅ COMPLETE |
| **Target wp:html Count** | 0 | ✅ REACHED |

---

## 🔄 **Migration Roadmap**

### Phase 1 ✅ (Complete)
- [x] Inline styles → CSS classes
- [x] Simple badges → Native paragraph + spans
- [x] Page headers (h2, p) → wp:heading, wp:paragraph
- [x] Spacer divs → wp:spacer
- [x] 404 display code → wp:heading

### Phase 2 ✅ (Complete)
- [x] cta-download.php badge wrapper → wp:paragraph
- [x] docs-grid.php badge wrapper → wp:paragraph

### Phase 3 🟡 (In Progress)
- [x] Testimonial section migrated to native blocks
- [x] Hero badge wrapper → wp:paragraph
- [x] Hero command wrapper migrated to native blocks
- [x] CTA grid structure migrated to native blocks
- [x] Docs grid migrated to native blocks
- [x] Feature cards grid migrated to native blocks
- [x] Pricing grid migrated to native blocks
- [x] Query showcase wrapper migrated to native blocks
- [x] Tabs section wrapper migrated to native blocks

### Phase 4 ✅ (Complete)
- [x] Replaced `stats-bar.php` HTML counters with custom block `themisdb/stats-counter`

### Phase 5 ✅ (Complete)
- [x] Archive/search/listing templates migrated to native `core/query` loops
- [x] Inline style attributes removed from migrated listing templates
- [x] Pattern categories registered (`themisdb-v3`, `themisdb-v3-landing`)
- [x] Theme style variations added (`styles/azure-night.json`, `styles/clean-slate.json`)
- [x] Pattern metadata normalized (keywords + consistent category mapping)
- [x] Reusable query pattern added (`themisdb-v3/query-loop-cards`)

### Phase 6 ✅ (Complete)
- [x] Front page shortcode islands replaced with native blocks/patterns
- [x] Hero migrated from shortcode to pattern (`themisdb-v3/hero-home`)
- [x] Pricing section migrated to pattern (`themisdb-v3/pricing-section`)
- [x] Blog cards migrated to native `core/query` stack
- [x] Front-page inline style attributes removed in favor of class-based cascade

---

## 📋 **Legitimate wp:html Blocks (Current State)**

No `wp:html` blocks are currently present in `themisdb-pulse`.

The former stats-bar exception was replaced by the custom block `themisdb/stats-counter`, which preserves counter data attributes while keeping pattern markup WordPress-native.

**Total Remaining:** 0 blocks

---

## 🛠️ **CI/CD Gates**

### Automated Checks:
```powershell
# Inline style gate (hard 0)
./scripts/check-theme-inline-styles.ps1 -ThemePath themisdb-theme-v3 -MaxAllowed 0

# wp:html block gate (hard 0)
./scripts/check-theme-wp-html-blocks.ps1 -ThemePath themisdb-theme-v3 -MaxAllowed 0
```

### Gate Progression:
- **Current**: MaxAllowed = 0
- **Milestone 1**: reached
- **Final**: MaxAllowed = 0 (WordPress-native only)

---

## 🎯 **Best Practices**

### For Theme Developers:
1. **Default to WordPress blocks** unless strong technical justification
2. **Document why** if using `wp:html` (see "Legitimate Use Cases")
3. **Prefer CSS** over HTML wrappers (use classes + style.css)
4. **Use data attributes** sparingly (prefer JavaScript event listeners on semantic elements)
5. **Test with Block Editor** to ensure editor UX is smooth
6. **Prefer core query stack** (`core/query`, `core/post-template`, pagination, no-results) over bespoke listing shortcodes
7. **Expose pattern categories** via `register_block_pattern_category(...)` for reliable Inserter discoverability
8. **Ship at least one style variation** in `styles/*.json` to support designer workflows in Site Editor
9. **Maintain pattern metadata hygiene**: every pattern should define `Title`, `Slug`, `Categories`, `Keywords`, and `Description`
10. **Prefer reusable query patterns** for card listings before introducing bespoke dynamic blocks

### For Content Editors:
- Edit text, images, and CTAs directly in the block editor
- Don't manually edit HTML blocks unless instructed
- Report issues if content appears broken or misaligned

### For DevOps/CI:
- Run gates on every PR and release
- Lower baseline incrementally (e.g., every 2 releases)
- Document decisions if baseline is raised (with approval from tech lead)

---

## 🚧 **CI Gate Progression**

### Gate Progression:
- **Current**: MaxAllowed = 0
- **Milestone 1**: reached
- **Final**: MaxAllowed = 0 (WordPress-native only)

### Guardrail:
- `wp:html` is disallowed (`MaxAllowed = 0`).
Integrated in:
- `.github/workflows/build-and-test.yml` (all pushes/PRs)
- `.github/workflows/wordpress-theme-release.yml` (manual releases)

---

## 📚 **References**

- [WordPress Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [WordPress Block Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-core-overview/)
- [Separation of Concerns in Web Development](https://en.wikipedia.org/wiki/Separation_of_concerns)

---

**Last Updated**: 2026-08-21 (Phase 6 complete, front page migrated to native blocks/patterns)
