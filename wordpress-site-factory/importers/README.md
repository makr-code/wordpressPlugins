# Importers

This folder contains the importer layer for the WordPress site factory.

## Purpose

The importer layer reads the externally linked source directories under the repo-local
`source-links/` folder and prepares them for WordPress import.

## Current scripts

- `source_manifest.py` — scans all configured source roots and produces a manifest
  that can be used to decide which content should be imported and how.
- `docs_manifest.py` — generates a normalized documentation manifest for `source-links/docs`
  and includes SHA-256 hashes per document so the import pipeline can decide what is new,
  updated, or unchanged.
- `docs_sync.py` — compares a current docs manifest with the previous cached state and emits a
  CRUD-style change plan (new / updated / deleted / unchanged).
- The manifest groups files by source root and adds filename, type, and tag hints to
  make later WordPress import tasks easier.

## Usage

```bash
python source_manifest.py --json
```

```bash
python source_manifest.py --write
```

```bash
python docs_manifest.py --write
python docs_sync.py --manifest wordpress-site-factory/output/docs_cache.json
```

These outputs are stored under `wordpress-site-factory/output/` and can be used by later
importers or validation scripts.

## Bootstrap and site assembly

The site factory does more than scan source files. The main build entry point also boots the WordPress instance by:

- validating the site connection and active theme/plugin state
- ensuring the AI guest-author roster is present for ThemisDB co-author metadata
- uploading AI author avatars into the WordPress media library and attaching them to the guest-author profiles
- applying taxonomy and page scaffolding before the docs import pipeline runs

This keeps the WordPress instance aligned with the repository-backed source and ensures imported documents can be assigned to the expected co-author identities.

## Planned additions

- docs importer -> WP posts
- benchmarks importer (implemented as catalog pipeline)
- release importer
- examples importer
- validation pipeline

## Benchmark import pipeline

The benchmark data pipeline normalizes heterogeneous benchmark JSON files from
`source-links/benchmarks/benchmark_results` into a stable catalog file:

- source parser: `benchmark_import.py`
- CLI entrypoint: `build_benchmark_catalog.py`
- output file: `source-links/benchmarks/benchmark_results/benchmark_catalog.json`

Build/update the catalog:

```bash
python wordpress-site-factory/importers/build_benchmark_catalog.py \
  --source-dir C:/Projects/wordpressPlugins/source-links/benchmarks/benchmark_results \
  --output C:/Projects/wordpressPlugins/source-links/benchmarks/benchmark_results/benchmark_catalog.json \
  --summary
```

Behavior:

- parses Google Benchmark JSON (`context` + `benchmarks`) and generic record arrays
- extracts normalized latency/throughput metrics with category inference from file names
- skips malformed files without aborting the full run and reports them in `errors`

### Benchmark docs manifest

Benchmark reports from `source-links/benchmarks/docs` are indexed separately so
the content pipeline can import and classify them consistently:

- source parser: `benchmark_docs_manifest.py`
- output: `wordpress-site-factory/output/benchmark_docs_cache.json`
- captures:
  - title/slug/hash/mtime
  - historical marker (`is_historical`)
  - references to benchmark result files (for example `bench_query.smoke.json`)
  - references to expectation docs (`PERFORMANCE_EXPECTATION*.md`)

Build/update the benchmark docs manifest:

```bash
python wordpress-site-factory/importers/benchmark_docs_manifest.py \
  --docs-root C:/Projects/wordpressPlugins/source-links/benchmarks/docs \
  --repo-root C:/Projects/wordpressPlugins \
  --output C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_cache.json \
  --summary
```

### Update-only helper for restricted production users

If WordPress credentials cannot create posts but may update existing ones, first
filter the benchmark docs manifest to existing posts only:

```bash
python wordpress-site-factory/importers/prepare_benchmark_docs_update_manifest.py \
  --manifest C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_cache.json \
  --output C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_existing_cache.json
```

Then run update-only import:

```bash
python wordpress-site-factory/importers/docs_import.py \
  --manifest C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_existing_cache.json \
  --update-only --limit 20
```

### Validate benchmark docs against data and WordPress content

Use this validation pass to verify:

- whether benchmark docs reference existing and parseable benchmark result files,
- whether numeric/file statements in markdown are still present in the rendered WordPress draft content,
- whether numeric claims can be backed by metric values from related files in `benchmark_catalog.json`,
- and (CT/CB) whether quality is improving/regressing versus the previous validation snapshot.

```bash
python wordpress-site-factory/importers/benchmark_docs_validate.py \
  --docs-root C:/Projects/wordpressPlugins/source-links/benchmarks/docs \
  --catalog C:/Projects/wordpressPlugins/source-links/benchmarks/benchmark_results/benchmark_catalog.json \
  --repo-root C:/Projects/wordpressPlugins \
  --output C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_validation_report.json \
  --previous-report C:/Projects/wordpressPlugins/wordpress-site-factory/output/benchmark_docs_validation_report.prev.json \
  --base-url http://localhost/wordpress \
  --summary
```

Important report keys:

- `docs_missing_related_files`: docs that mention benchmark files not present in catalog inventory
- `docs_only_invalid_related_files`: docs linked only to files that failed parsing
- `docs_with_missing_expectation_refs`: docs that refer to missing `PERFORMANCE_EXPECTATION*.md` files
- `comparability_score_global`: global category comparability score (0-100) derived from cross-engine task coverage
- `numeric_token_match_rate`: how many numeric claims from source markdown are still present in WP-rendered content
- `docs_with_unsupported_data_claims`: docs that still contain numeric claims not supported by related metric values
- `docs_without_supported_data_claims`: docs with valid related files but no numeric claim matched to related metrics
- `ctcb_evolution`: trend snapshot (`improving` / `stable` / `regressing`) with metric deltas against a previous report

## Runtime behavior (docs importer)

- Documents are imported via WordPress REST endpoints (`/wp-json/wp/v2/posts`), not via ZIP handoff to a server-side importer.
- Embedded media assets are uploaded individually to `/wp-json/wp/v2/media`.
- New documents default to `draft` unless the source metadata explicitly provides a valid publish status (`draft`, `pending`, `private`, `publish`).
- Post creation uses the authenticated current WordPress user as author when needed, avoiding cross-author create failures for restricted roles.
- Live runs perform an authentication preflight (`/wp/v2/users/me`) and fail early with a clear error if credentials are invalid.
- Taxonomy terms are prewarmed before live write batches to reduce per-document tag/category creation latency.
- The importer can also read legacy blog sources directly from `page-content-blog/sources` via `--blog-sources-root`, supporting both:
  - newer `<slug>/article.md` + `<slug>/meta.json` folders
  - older dated folders that contain one or more `.md` files
- For restricted production users, `--update-only` enforces update-only behavior and skips creates when no existing post is found.

### Local WordPress credentials (XAMPP)

When `--base-url` points to `localhost` or `127.0.0.1`, the importer can auto-resolve local credentials from:

1. `.secure/.wp_connections.json` (default profile or `WP_CONNECTION_PROFILE`, when profile `wp_base` is local)
2. environment variables `WP_LOCAL_USER` and `WP_LOCAL_APP_PASSWORD`
3. optional file `.secure/site.local.json` (`wordpress.user`, `wordpress.app_password`)

This lets local tests use separate credentials from production `.secure/site.json`.
Application passwords are normalized before auth (whitespace removed), so
WordPress-formatted grouped app passwords remain valid.

### Legacy blog reimport mode

Use this mode to reimport blog posts that originate from the old `page-content-blog` pipeline:

```bash
python wordpress-site-factory/importers/docs_import.py \
  --blog-sources-root C:/Projects/wordpressPlugins/page-content-blog/sources \
  --dry-run --limit 10 --json
```

Then execute live:

```bash
python wordpress-site-factory/importers/docs_import.py \
  --blog-sources-root C:/Projects/wordpressPlugins/page-content-blog/sources \
  --limit 10
```

Update-only live run (recommended for constrained production credentials):

```bash
python wordpress-site-factory/importers/docs_import.py \
  --blog-sources-root C:/Projects/wordpressPlugins/page-content-blog/sources \
  --limit 10 --update-only
```
