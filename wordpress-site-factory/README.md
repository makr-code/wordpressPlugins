# ThemisDB WordPress Site Factory

This folder is the single orchestration layer for ThemisDB WordPress setup.
It brings together the site bootstrap, taxonomy setup, page generation, content import,
and validation in one place.

## Goal

Create the public ThemisDB site structure in WordPress through the REST API from one
entry point instead of spreading the logic across unrelated scripts.

## Structure

- `build_site.py` — central entry point
- `bootstrap.py` — verifies WP connection, theme, and required plugins
- `taxonomy.py` — creates the reserved public/restricted/descriptive tags
- `pages.py` — creates the public site hierarchy and key pages
- `validation.py` — validates the public content model and homepage behavior
- `config/site.json` — WordPress connection + site config
- `config/taxonomy.json` — semantic tag definitions
- `config/pages.json` — site hierarchy and page metadata

## Quick start

Dry-run:

```bash
python build_site.py --dry-run
```

Apply the full setup:

```bash
python build_site.py --profile local-test
```

## Current scope

This factory is intentionally aligned with the existing ThemisDB repo setup:

- local site: http://localhost/wordpress
- active theme: themisdb-pulse
- public selection tags and restricted tags are managed in one taxonomy layer
- pages are created using the WordPress REST API, not hard-coded HTML only
- canonical content sources are linked into the repo through `../source-links/`

## Source roots

The repo also includes a read-only source bridge at [../source-links](../source-links), which
links the canonical content from `C:/Projects/themisdb` into this repository without copying
it. This is the right place to mount docs, benchmark reports, releases, examples, and other
import source material before building the WordPress importers.

## Planned next step

Once the site is bootstrapped, the next stage is to import the content from the
`page-content-*` sources and connect them to the page and tag structure created here.
