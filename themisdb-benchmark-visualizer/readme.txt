=== ThemisDB Benchmark Visualizer ===
Contributors: themisdb-team
Tags: benchmarks, charts, performance, visualization, themisdb
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.5.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Interactive charts and accessible tables for measured benchmark data. No fabricated comparison datasets.

== Description ==

Display benchmark measurements using [themisdb_benchmark_visualizer].
The plugin supplies its own PHP template, scoped CSS, JavaScript and locally
bundled Chart.js 4.4.0. It works independently of ThemisDB Pulse.

* Independent shortcode instances with category, metric, engine, task-search and Top-N controls
* Bar, line, area and radar charts with reduced-motion support
* Latency in ms, throughput in ops/s and actual memory occupancy in MiB
* Full operation/parameter identity, missing-value handling and preserved tiny measurements
* Explicit single-engine notices and configurable cross-engine task filtering
* Accessible measurement table, source references, CSV and browser print/PDF
* Responsive light/dark presentation and manual engine emphasis
* WordPress Settings API, nonce-checked AJAX and source-aware caching

Configure a local benchmark_catalog.json or legacy Google Benchmark files.
The GitHub data adapter is not implemented and reports an explicit error.
Missing or invalid source files never produce demonstration measurements.
Database comparisons require actual matching measurements in the supplied data.

== Installation ==

1. Install the themisdb-benchmark-visualizer directory under wp-content/plugins.
2. Activate the plugin.
3. Open Settings > Benchmark Visualizer and configure a local data source.
4. Insert [themisdb_benchmark_visualizer] in a page or template.

== Frequently Asked Questions ==

= Where does the data come from? =

A locally configured catalog or legacy benchmark files. There is no remote
synchronization or automatic polling. See README.md for the catalog schema.

= Why are other databases missing? =

Only engines present in your measurements can be shown. An available ThemisDB
measurement is not turned into a fabricated PostgreSQL or MongoDB comparison.

= Why is memory empty? =

The source must contain actual memory occupancy. Bandwidth is not memory usage.

= Can I use several shortcodes? =

Yes. Their settings, controls, requests and charts are independent.

= Can I export data? =

CSV exports the visible filtered measurements. Print/PDF opens the browser print
dialog. Full task names and missing measurements are available in the table.

= Is the chart library loaded from a CDN? =

No. Chart.js and its MIT license are bundled in assets/vendor.
Assets are enqueued for shortcode content, the front page and explicit shortcode rendering.

== Changelog ==

= 1.5.0 =
* Add linear/logarithmic scales, explicit sorting and selectable reference engines.
* Show eligible per-task performance factors in tables, tooltips and CSV.
* Preserve original zero values outside log plots and explain linear radar fallback.

= 1.4.0 =
* Add explicit horizontal, vertical and isometric 2.5D bar views.
* Separate plain lines, line-dot and stepped lines.
* Share chart choices between admin settings and shortcode controls.

= 1.3.0 =
* Add an opt-in synthetic demo source with three fictional databases.
* Label demo views, tables and CSV exports; never activate demo as an error fallback.

= 1.2.0 =
* Require matching vector End-to-End publication protocols for cross-engine comparisons.
* Isolate historical/external measurements and show missing protocol fields.
* Reject duplicate controlled-run statistics instead of averaging percentiles.

= 1.1.2 =
* Hide chart task labels by default with a per-instance option to show them.
* Preserve full names in tooltips and measurement tables.

= 1.1.1 =
* Add inheritable theme tokens for all component and canvas colors.
* Keep standalone light/dark defaults while allowing explicit theme palettes to override them.
* Integrate Pulse branding without replacing plugin markup, layout or behavior.

= 1.1.0 =
* Fix operation grouping, unit conversion, missing-value handling and small-value precision.
* Replace global state with independent shortcode components and race-safe loading.
* Add truthful source/error states, accessible tables and reliable CSV export.
* Bundle Chart.js locally and remove theme, jQuery and Mermaid dependencies.
* Replace automatic engine rotation with manual emphasis.
* Add parser, WordPress integration and standalone browser regression tests.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Corrects measurement semantics and removes fabricated fallback data. Configure a
valid local source. Legacy decorative overlays and generated architecture diagrams
are no longer rendered; existing shortcode parameters remain accepted.
