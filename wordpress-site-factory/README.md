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
- `.secure/site.json` — WordPress connection + site config (kept outside the tracked repo tree)
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

## Live audit and bootstrap notes

The repository includes a dedicated audit and bootstrap baseline at [AUDIT_REPORT.md](./AUDIT_REPORT.md). It captures the live ThemisDB reference environment, the delta analysis between repo state and production, and the dry-run/live-run setup workflow for a fresh WordPress instance.

Use the audit as the operational baseline for migration planning, but keep the repo as the repeatable implementation source for the factory scripts.

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

## Canonical import contract

The importer should normalize Markdown into a semantic intermediate format before it is rendered to WordPress HTML. The canonical target is a YAML-first block model that represents content as typed blocks instead of raw source text.

See [importers/target-import-format.yaml](./importers/target-import-format.yaml) for the schema.

The target contract standardizes:

- headings (`type: heading`, `level: 1..6`)
- paragraphs (`type: paragraph`)
- lists (`type: list`, ordered/unordered)
- tables (`type: table`, header + row arrays)
- image blocks (`type: image`, with alt/caption)
- Mermaid blocks (`type: mermaid`, with safe code string)
- code blocks (`type: code`, language + code)
- blockquotes (`type: blockquote`)
- callouts / notes (`type: callout`, variant)
- comments are removed from public output
- raw HTML is allowed only in a sanitized subset
- unsupported/odd Markdown is preserved as text instead of crashing the importer

This gives us a stable contract for validation, regardless of whether the source was Markdown, HTML, or an exported doc fragment.

## Source-side metadata standard

Canonical Markdown documents should carry a compact YAML frontmatter block at the top of the file. This keeps the file self-describing, makes validation and change detection faster, and allows the importer to strip the metadata before rendering the public body.

Example:

```yaml
--- title: "Example document"; slug: "example-document"; summary: "Public ready example"; category: "documentation"; status: "publish"; readiness: "ready"; score: 88; author: "ThemisDB"; coauthors: ["Anthropic Claude", "GitHub Copilot"]; tags: ["documentation", "architecture"]; language: "en"; source_origin: "source-links/docs"; source_path: "source-links/docs/website/example-document.md"; hash: "<sha256-content-hash>"; backlog: ["Validate public feed filters", "Check related-doc rewrite"]; ---

The importer can read this block, validate the hash, and later re-write or re-use the metadata without touching the body content. This also makes the source corpus easier to audit and diff cleanly.

## Planned next step

Once the site is bootstrapped, the next stage is to import the content from the
`page-content-*` sources and connect them to the page and tag structure created here.
