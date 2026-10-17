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

## Planned additions

- docs importer -> WP posts
- benchmarks importer
- release importer
- examples importer
- validation pipeline
