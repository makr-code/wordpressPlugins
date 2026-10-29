# ThemisDB Benchmark Visualizer

Version 1.5.0. Standalone WordPress shortcode for measured benchmark results.
PHP 7.4+, WordPress 5.0+, and a modern browser with JavaScript are required.

The plugin owns its markup, CSS, controls and JavaScript. It does not depend on
ThemisDB Pulse, jQuery, Mermaid or a chart CDN. Chart.js 4.4.0 is bundled locally
with its [MIT license](assets/vendor/Chart-LICENSE.md).

## Installation and data sources

1. Install the `themisdb-benchmark-visualizer` directory under WordPress plugins
   and activate it. A directory junction to this repository also works.
2. Open **Settings > Benchmark Visualizer**.
3. Select **Benchmark Catalog JSON** and configure an absolute path to the
   catalog, or leave it empty for automatic discovery under a sibling
   `source-links/benchmarks/benchmark_results` directory.
4. Insert `[themisdb_benchmark_visualizer]` in a page or template.

The optional `THEMISDB_BENCHMARK_CATALOG` environment variable is another
catalog discovery candidate. An explicitly configured path takes precedence;
an invalid explicit path reports an error instead of loading a different file.

**Local Files** supports Google Benchmark JSON files named `bench_*.json`.
Discovery also checks the plugin's `data` directory and the
`THEMISDB_BENCHMARK_DIR` environment variable. Legacy files are treated as
ThemisDB results. Use the catalog format for cross-engine comparisons and
explicit environment/dataset context.

**GitHub Repository is not implemented.** Selecting it displays an explicit
configuration error. The plugin does not fetch remote data, silently substitute
local results or generate demonstration values.

### Optional synthetic demo source

Select **Settings > Benchmark Visualizer > Data Source > DEMO** and save to test
the UI without actual benchmark files. This is opt-in: installing/updating the
plugin does not change the selected source. Select **Benchmark Catalog JSON**
again to return to real measurements.

The deterministic generator supplies **VectorFox Demo**, **GraphNest Demo** and
**NimbusMock Demo**, with 60 task configurations per category (`vector_search`,
`graph_traversal`, `transaction`) and latency, throughput and memory values.
Missing engine/task cells, multiple vector sizes and several latency orders of
magnitude exercise sorting, filtering, all/Top-N, tooltips, navigation and tables.
Other categories correctly show an empty state.

These are mathematical test values, not results from an actual database or
VectorDBBench run. A persistent demo notice, table caption, CSV notice and
`DEMO-synthetic-` export filename make that distinction explicit. Engine names and
task names also contain Demo/DEMO. The default real-engine comparison list is
ignored in demo mode; the engine selector still works normally.

Demo task grouping is restricted to the explicit built-in demo adapter. It does
not mark synthetic data as publication-verified or relax the real catalog rules.
Missing/invalid real sources never trigger a demo fallback.

### Catalog format

```json
{
  "records": [
    {
      "benchmark_name": "BM_SIMD_L2/64",
      "engine": "ThemisDB",
      "workload": "vector_search",
      "dataset_size": "100000",
      "environment": "reference-machine",
      "threads": 1,
      "metric_name": "latency",
      "metric_unit": "ns",
      "metric_value": 7.64,
      "source_file": "bench_vector.json"
    }
  ]
}
```

Missing/invalid files are errors. A valid catalog with no measurements matching
the current filters is an empty state, not an error and not a zero-valued result.

### Measurement rules

- Operation names and numeric parameters remain distinct. SIMD L2, scalar L2 and
  inner-product operations with the same vector dimension are not merged.
- Available dataset size, environment and thread count are part of each task's
  identity. Repeated legacy values for the same task, engine and source are
  averaged. Controlled-run statistics are never averaged: duplicate values for
  the same protocol/run/engine are rejected.
- Only nonnegative finite numeric values with known units are accepted.
  Missing values remain `null`; genuine measured zero remains zero.
- Latency is normalized to **ms** from `ns`, `us`, `ms` or `s`. Tiny values are
  preserved instead of rounded to zero.
- Throughput is normalized to **ops/s** from `ops/s`, `ops/sec`, `items/s` or
  `items_per_second`.
- Memory occupancy is normalized to **MiB** from `bytes`, `B`, `KiB`, `MiB`,
  `MB`, `GB` or `GiB`. Bandwidth such as `bytes_per_second` is not memory usage.
- Legacy Google Benchmark error and aggregate rows are excluded. Latency uses
  `real_time` (or `cpu_time`), throughput uses `items_per_second`, and memory
  uses `memory_usage` or `bytes_used` in bytes.
- Strict cross-engine mode retains protocol-matched tasks measured by at least
  two engines when such tasks exist. Otherwise real single-engine/reference
  measurements remain visible with an explicit notice. Excluded counts are shown.
- Matching task names do not prove equal hardware or equally configured
  databases. Check the original measurements before making performance claims.

The task-source details show original measurement names and source basenames.
They do not expose absolute server paths.

### Vector publication contract (v1)

The canonical machine-readable rules are in
[benchmark-publication-contract.json](includes/benchmark-publication-contract.json).
The Python catalog builder and PHP renderer both validate this same file.
Adding a `publication` object to each catalog record does **not** make a
microbenchmark an End-to-End benchmark: metadata must describe an actual,
reproducible run. Existing records are not automatically upgraded.

The first supported comparison family is `vector_search`, using
[VectorDBBench](https://github.com/zilliztech/VectorDBBench) as the target harness.
This is our publication contract, not an ISO standard, an official VectorDBBench
certification, or proof that the claimed measurements were independently audited.

Required metadata groups:

| Group | Required fields / meaning |
|-------|---------------------------|
| Protocol | `version: 1`, `suite: "vectordbbench"`, `suite_version`, `scope: "end_to_end"`, `origin: "controlled_run"`, `protocol_id`, `experiment_id` |
| Dataset | `dataset_id`, `dataset_sha256`, positive `vectors`, `dimensions`, `distance` (`cosine`, `l2`, `inner_product`), positive `top_k` not exceeding corpus size |
| Resources | `hardware_fingerprint`, `execution_fingerprint`: lowercase SHA-256 digests of the published hardware and execution manifests |
| Load | Positive `concurrency`, `cache_state` (`warm`/`cold`), nonnegative `warmup_queries`, positive `measured_queries`, `repetitions` |
| Quality/statistic | `recall_target` in [0.01, 1], `recall_achieved` at least that target; latency `statistic` is `mean`/`p50`/`p95`/`p99`, throughput is `qps`, memory is `peak` |
| Reproducibility | `engine_version`, `index_config`, `run_id`, timezone-qualified `measured_at`, HTTPS `source_url`, declared `license` |

`protocol_id` identifies a published, immutable methodology. It must define the
query set and ground truth, metric timing boundaries, quality evaluation, filters,
index tuning budget, timeouts/error policy, sampling and repetition aggregation.
The hardware manifest documents CPU/GPU, RAM, storage, OS and resource limits.
The execution manifest documents client placement, transport, durability,
background load and cache preparation. Hash the exact published manifest bytes;
identical hashes with different configurations are not acceptable.

All `comparison_fields` in the contract **and `run_id`** must match across engines,
in addition to task name, metric and existing task context. `run_id` identifies a
shared measurement block within the experiment; individual engine execution IDs
belong in the referenced run report. Engine versions, index configuration and
achieved recall are recorded but may differ, provided the common recall target
is reached. Different percentiles, corpora, hardware or load do not share bars
for the same task. Percentiles must come from the specified query sample or the
explicitly documented aggregation procedure, never an implicit average of p99s.

External reports (`origin: "external_reference"`), unknown protocols, missing
metadata and below-target recall are **reference-only**. They remain visible,
isolated by engine/source/protocol, but do not count as comparable tasks.
The table exposes supplied protocol metadata or validation issues. The UI
explicitly warns about reference/legacy measurements.

Validation checks completeness and matching fields, **not the truth of a report,
the contents of a remote URL, license suitability or statistically adequate sample
sizes**. Choose measurement duration, repetitions and sample counts in the
protocol before running; the schema's positive minima are only structural checks,
not recommended statistical thresholds. Never publish credentials or private paths
in metadata. An independent review of source artifacts remains required.

The [synthetic test fixture](tests/publication-fixture.json) demonstrates the
object shape only. It contains no real performance results and must not be used
as published benchmark data.

## Shortcodes

```text
[themisdb_benchmark_visualizer]
[themisdb_benchmark_visualizer category="vector_search" metric="latency" chart_type="bar"]
[themisdb_benchmark_visualizer category="all" compare="postgresql,mongodb" show_table="true"]
[themisdb_benchmark_visualizer metric="memory" show_controls="false" show_chart="false"]
```

Multiple visualizers on the same page have independent configuration, controls,
requests, charts and exports. Explicit shortcode parameters override admin
defaults even when controls are hidden.

| Parameter | Values / behavior |
|-----------|-------------------|
| `category` | `all` or a category from the admin keyword mapping |
| `metric` | `latency`, `throughput`, `memory` |
| `chart_type` | `bar`, `bar-horizontal`, `bar-vertical`, `bar-isometric`, `line`, `line-dot`, `stepped`, `area`, `radar` |
| `compare` | Comma-separated engine names, case-insensitive; ThemisDB is retained. Empty means all available engines. No datasets are invented. |
| `aspect_ratio` | Positive ratio such as `4:3` or `16:9`; chart height is bounded between 18 and 36 rem |
| `show_header` | Show the plugin title; default `false` |
| `show_controls` | Show the compact native options menu; default `true` |
| `show_chart` | Show the chart; default `true` |
| `show_nav` | Manual previous/next engine emphasis; default `true`, only shown for multiple visible engines |
| `show_stats` | Show statistics of the active engine's visible tasks; default `false` |
| `stats_mode` | `toolbar`, `cards`, `hidden` |
| `show_table` | Open the data table initially; default `false`. The table remains available collapsed, and opens when the chart is disabled or unavailable. |
| `show_overlay` | Compatibility parameter; obsolete decorative overlays are no longer rendered |
| `show_mermaid` | Compatibility parameter; shows an explanation rather than inventing a system topology from timing data |

Category mapping, chart theme, Top-N, comparison engines and the strict comparison
filter are configurable in the existing WordPress settings page. The default
category is `vector_search`. Custom categories can be added through the keyword
mapping, without editing plugin source.

## Interaction, export and caching

### Chart gallery

All modes are available in **Diagramm-Optionen > Diagramm**, as admin defaults,
and through the `chart_type` shortcode attribute, including with controls hidden:

| Mode | Presentation |
|------|--------------|
| `bar` | Automatic orientation: horizontal for dense charts or small screens |
| `bar-horizontal` | Always horizontal grouped bars |
| `bar-vertical` | Always vertical grouped columns, including mobile |
| `bar-isometric` | Vertical columns with shaded 30-degree projected depth faces (2.5D) |
| `line` | Plain lines with no permanent point markers |
| `line-dot` | Lines with visible measurement dots |
| `stepped` | Step connections between discrete task positions |
| `area` | Lines with translucent area fill |
| `radar` | Radial comparison with translucent fills |

```text
[themisdb_benchmark_visualizer chart_type="bar-isometric"]
[themisdb_benchmark_visualizer chart_type="bar-vertical"]
[themisdb_benchmark_visualizer chart_type="line-dot"]
```

Isometric depth is decorative, not a third measured variable or a rotatable 3D
scene. The front top edge stays at the true value. Top/side shading derives from
theme series colors, follows animation geometry and omits absent/zero values.
A visible explanation accompanies this view. For precise comparison prefer the
flat chart, tooltip or table. Use Top-10/20 for wider individual columns; all
modes retain the same filtered rows and export values.

Line/step connections follow the displayed task order (sorted by primary-engine
performance), not an implicit time series. Missing values are not bridged. Point
markers can be hidden without disabling nearest-point tooltips.

### Analysis controls

- **Werteskala:** linear (default) or logarithmic for Cartesian charts. Zero
  measurements are omitted only from logarithmic plots; the original table/CSV
  value is unchanged. No epsilon/pseudovalue is invented. Radar stays linear,
  with an explicit notice, and the log selection resumes on switching back.
- **Sortierung:** best first (default), worst first, task name A-Z, or original
  source order. Sorting precedes Top-N. Missing reference values stay last for
  both performance directions.
- **Referenz-Engine:** automatic chooses visible ThemisDB, otherwise the first
  visible engine. An explicit reference can be selected independently of the
  visible engine filter; it supplies the sorting values and comparison baseline.
  Selection changes do not refetch measurements.
- **Performance factor:** shown per task in table cells/tooltips, with additional
  CSV columns. Throughput factor is `engine / reference`; latency and memory
  factor is `reference / engine`. Thus 1 is the baseline and greater than 1 is
  better for that metric. A memory factor describes occupancy efficiency, not
  query speed. There is no cross-task overall winner.
- Factors require the task's verified publication protocol, or the explicit
  synthetic demo source. Reference-only/legacy tasks, missing values, zeros and
  non-finite ratios produce no factor (blank CSV field). Absolute measurements
  remain unchanged. The explanatory note accompanies these controls.

### Loading and export

- Search tasks, select engines and choose Top-N or **All**. The server no longer
  truncates results to thirty tasks.
- Bars become horizontal for dense charts or small screens. Tooltips include
  the real measurement and unit; the table keeps full task labels.
- Task names on the chart axes (and radar point labels) are hidden by default
  to leave room for the plot. **Testnamen im Diagramm > Einblenden** restores
  them per instance without reloading measurements. Numeric scales, units,
  engine legend, full tooltip names and table labels remain available.
- There is no automatic engine rotation. Engine navigation is manual, and chart
  animation respects reduced-motion preferences.
- Superseded requests are aborted and sequence-checked. Loading, error and empty
  states clear stale charts and exports instead of leaving previous results visible.
- An unavailable/broken chart displays a diagnostic and opens the measurement
  table. CSV remains available when data itself is valid.
- CSV exports the visible filtered rows with units, quoted values, empty missing
  cells and spreadsheet formula-prefix protection. **Drucken / PDF** uses the
  browser's print dialog, not a separate PDF-generation service.
- **Neu laden** reissues the request but respects the server cache; it is not an
  administrator cache-purge action.
- Cache keys include plugin version, category, metric, source configuration,
  category mapping, comparison policy and source-file modification time/size.
  Configured cache duration is bounded between one minute and seven days.
  No live polling or remote GitHub synchronization is performed.

## Theme integration

Embed the shortcode; do not replace the data-loading or navigation implementation.
CSS is scoped to `.themisdb-benchmark-wrapper`. The public `--themisdb-bv-*`
tokens can be set on the component **or any ancestor**, independently of
stylesheet order. Without theme tokens the plugin supplies complete light/dark
defaults selected by the admin chart-theme setting. Explicit theme tokens take
precedence over either default palette.

```css
.themisdb-benchmark-wrapper {
    --themisdb-bv-surface: var(--my-theme-surface);
    --themisdb-bv-text: var(--my-theme-text);
    --themisdb-bv-muted: var(--my-theme-secondary-text);
    --themisdb-bv-border: var(--my-theme-border);
    --themisdb-bv-accent: var(--my-theme-link);
    --themisdb-bv-series-1: var(--my-theme-brand);
}
```

Additional tokens: `subtle` (buttons/stat cards), `hover`, `error`, `grid`,
`series-1` through `series-6`, `radius`, `shadow`, `menu-shadow`, `font` and
`color-scheme` (light/dark native form controls), each with the same
`--themisdb-bv-` prefix. Color tokens accept standard resolved CSS colors
(including hex and RGB), not gradients. Series colors, legend/axis labels,
grid and tooltip colors are read from CSS when a chart is rendered; they are
not hard-coded separately in JavaScript. After a runtime palette change,
reload the view or change a chart option to redraw its canvas.

Pulse maps these tokens to its own `--tv3-*` palette in the theme stylesheet.
It does not disable the plugin stylesheet, replace markup or implement chart logic.
The internal `--bv-*` properties remain available for existing direct overrides.

Existing PHP filters remain available:

- `themisdb_benchmark_visualizer_shortcode_atts`
- `themisdb_benchmark_visualizer_shortcode_payload`
- `themisdb_benchmark_visualizer_shortcode_html`
- `themisdb_benchmark_visualizer_shortcode_html_output`
- `themisdb_benchmark_visualizer_enqueue_frontend_style`

The last filter defaults to `true`. Disable it only when the integrating theme
intentionally supplies the complete component styles.

Scripts initialize each `[data-bv-config]` component and discover dynamically
inserted components. Removed components abort requests and destroy their chart.
There is no shared `window.ThemisDBBenchmarks` singleton.

## Validation

Run from the repository root using its existing Playwright installation:

```powershell
node --test .\themisdb-benchmark-visualizer\tests\data.test.cjs .\themisdb-benchmark-visualizer\tests\browser.test.cjs
& C:\xampp\php\php.exe .\themisdb-benchmark-visualizer\tests\test-wordpress.php C:\xampp\htdocs\wordpress\wp-load.php
```

The browser tests use the local active WordPress homepage and a standalone
same-origin fixture with explicitly mocked measurement responses. Override
`WORDPRESS_URL` if the installation URL differs. They cover independent
instances, filters, chart types, reduced motion, request races, empty/error states,
CSV and layout at 320, 390, 860 and 1440 pixels.

The PHP checks cover unit conversion, identity/grouping, missing values,
comparability, source errors, shortcode flags, unique IDs and local assets.
They require this plugin to be active and a valid local catalog. Option overrides
use temporary filters and do not change saved WordPress settings.
