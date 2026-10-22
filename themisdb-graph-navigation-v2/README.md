# ThemisDB Graph Navigation V2

A fresh graph navigation plugin for heterogeneous WordPress content with animated force-like physics, worker-assisted layout, and a robust canvas renderer (with optional experimental WebGPU path).

## Architecture

- Local WordPress content collection from posts, pages, taxonomies, custom post types, menus, and internal links
- Graph normalization for canonical IDs, deduplication, degree scoring, and cluster assignment
- Visibility/Lod pruning so large graphs render within performance budgets
- Canvas 2D rendering with continuous force-like animation (repulsion, spring links, centering, damping)
- Type-based cluster target layout for clearer structure (home/content/taxonomy groups)
- Worker-based layout bootstrap for initial placement
- Optional experimental WebGPU rendering path behind an explicit runtime flag
- Pointer interaction on the canvas (hover/select, drag nodes, pan background, wheel zoom)
- Shortcode-based registration for immediate use

## Usage

Add this shortcode to a page or post:

```text
[themisdb_graph_navigation_v2 title="Graph Navigation"]
```

## Files

- `themisdb-graph-navigation-v2.php` — plugin bootstrap and asset registration
- `includes/class-themisdb-graph-data.php` — heterogeneous WordPress graph builder
- `includes/class-themisdb-graph-normalizer.php` — dedupe, normalize, and cluster
- `includes/class-themisdb-graph-settings.php` — admin configuration for GPU and performance settings
- `includes/class-themisdb-graph-cache.php` — transient cache wrapper
- `assets/js/state.js` — graph runtime state and viewport settings
- `assets/js/graph-store.js` — graph model + visible-subgraph logic
- `assets/js/render/webgpu-renderer.js` — GPU-first renderer foundation
- `assets/js/render/canvas-renderer.js` — canvas fallback renderer
- `assets/js/render/graph-view.js` — unified render view abstraction
- `assets/js/workers/layout-worker.js` — off-thread layout runner
- `assets/js/themisdb-graph-v2.js` — bootstrap, worker wiring, and interaction layer
- `assets/css/graph.css` — visual shell styling

## Notes

This is a V2 rewrite intentionally structured to separate data acquisition, graph normalization, visibility pruning, layout, and rendering concerns so it can scale across the heterogeneous local WordPress content graph instead of the legacy DOM-heavy SVG approach.
