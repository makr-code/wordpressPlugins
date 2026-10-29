# ThemisDB Graph Navigation V2

A semantic knowledge-graph navigation plugin for WordPress content. It builds relationships between published content, taxonomies, menus, and linked resources, then exposes them through an interactive canvas.

## Architecture

- Published posts, pages, selected public custom post types and taxonomies, menu items, page hierarchies, and HTML anchor references
- Search across graph nodes, select connected neighbors while retaining the wider graph context, and navigate selection history with back/forward controls
- Graph normalization for canonical IDs, deduplication, degree scoring, and cluster assignment
- Bounded node visibility and a relationship-aware, expanded 2D network layout
- Consistent canvas-world coordinates for rendering, pointer hit testing, dragging, panning, and zooming
- Smooth spring-based graph transitions and decluttered labels for dense graphs
- HiDPI-aware Canvas rendering with predictable browser behavior
- Independent shortcode/widget instances; the automatic footer launcher is omitted when a page already contains a shortcode instance

## Usage

Add this shortcode to a page or post:

```text
[themisdb_graph_navigation_v2 title="Graph Navigation"]
```

The graph can be searched by node label or type. Selecting a result or connected node focuses it in the graph and adds it to the selection history. Canvas interactions include hover/select, node dragging, background panning, and wheel zoom.

The plugin's settings are available under **Settings → Graph Navigation V2**. The selected public post types and taxonomies control graph collection; content links are only extracted from anchor `href` attributes. Cache entries are invalidated when graph settings, published content, terms, or navigation menus change.

## Files

- `themisdb-graph-navigation-v2.php` — plugin bootstrap and asset registration
- `includes/class-themisdb-graph-data.php` — heterogeneous WordPress graph builder
- `includes/class-themisdb-graph-normalizer.php` — dedupe, normalize, and cluster
- `includes/class-themisdb-graph-settings.php` — admin configuration for graph data and performance
- `includes/class-themisdb-graph-cache.php` — transient cache wrapper
- `assets/js/state.js` — graph runtime state and viewport settings
- `assets/js/graph-store.js` — graph model + visible-subgraph logic
- `assets/js/render/webgpu-renderer.js` — experimental renderer, not enabled by the production runtime
- `assets/js/render/canvas-renderer.js` — canvas fallback renderer
- `assets/js/render/graph-view.js` — unified render view abstraction
- `assets/js/themisdb-graph-v2.js` — graph layout, animation, bootstrap, and interaction layer
- `assets/css/graph.css` — visual shell styling

## Notes

The plugin keeps WordPress as the content source and owns graph data acquisition, normalization, navigation, layout, and rendering. The theme can configure presentation without being responsible for graph behavior.

## Changes in 2.1.8

- Replaced type-only circular placement with a relationship-aware, expanded multi-ring network layout.
- Added damped spring transitions for graph opening and navigation, including connected placement for newly visible nodes.
- Stopped overlapping animation loops when a new selection interrupts an active transition.
- Reduced label clutter on dense graphs while retaining labels for selected nodes, neighbors, and structural nodes.
- Kept a bounded overview of the wider graph visible while prioritizing the current node and its direct relationships.

## Changes in 2.1.6

- Fixed canvas sizing, world-coordinate layout, scrolling, pointer targeting, and high-DPI canvas rendering.
- Replaced quadratic node insertion with indexed deduplication and corrected public content-type/taxonomy collection.
- Added page-parent, taxonomy-parent, menu, internal-link, and optional external-resource relationships.
- Added graph search, connected-node navigation, and selection back/forward history.
- Prevented the automatic footer launcher from duplicating explicit shortcode/widget instances and stopped removing valid graph shells.
- Added cache invalidation for settings and menu changes.
