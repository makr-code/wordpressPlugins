# ThemisDB Live-Instance Audit and Bootstrap Validation

## Scope

This document captures the live-reference audit for ThemisDB's WordPress environment and the dry-run/live-run bootstrap flow for a fresh ThemisDB instance. The goal is not a 1:1 migration of the current `themisdb.org` deployment, but a safe delta analysis and a repeatable production setup for the new environment.

## 1. Live-instance audit baseline

### Overview

- Reference site: https://themisdb.org
- Primary objective: inventory live content, structure, plugin/theme setup, and migration-relevant gaps.
- Output: operational baseline for migration or rebuild planning.

### Audit inventory checklist

- Pages, posts, CPTs, and templates
  - Inventory all public pages and post types exposed via the WordPress REST API.
  - Record page hierarchy, slug structure, parent/child relationships, and template assignments.
  - Document custom post types and their metadata fields.
- Menus and navigation
  - Capture all registered menus and menu locations.
  - Record top-level navigation, footer navigation, and mobile navigation.
  - Validate theme support for the active menu locations.
- Theme and frontend configuration
  - Record active theme and any child theme or custom front-page setup.
  - Check header/footer template dependencies, widget zones, and sidebars.
- Plugins and variants
  - Record the active plugin set and any plugin variants installed for staging, local, or legacy reasons.
  - Document deactivated or duplicate plugin instances.
  - Capture plugin versions and whether they are core or custom ThemisDB additions.
- Media library and URL structure
  - Inventory uploaded media, attachment slugs, and image sizes.
  - Compare image URLs, CDN references, and absolute/relative paths.
  - Capture permalink structure and redirect behavior.
- Redirects and SEO metadata
  - Document redirect rules, SEO plugins, canonical tags, and sitemap generation.
  - Record meta titles, descriptions, OG/Twitter metadata, and structured data output.
- Authentication and user model
  - Record default roles and custom capabilities.
  - Note admin/user-setup patterns and app-password usage.
  - Validate who can publish, update, and manage theme/plugin settings.
- Repository delta and drift analysis
  - Compare repo content (`wordpress-site-factory`, theme sources, plugin code) with the live production site.
  - Identify drift between source-controlled configuration and live state.
  - Document intentionally custom live content not represented in the repo.

### Live delta notes

The live instance should be treated as a source of truth for operational content and structure, while the repository remains the source for repeatable implementation logic. The delta list should therefore record:

- content that exists live but is not yet represented in the repo
- repo-defined pages/taxonomies that are absent live
- plugin/theme differences between repo and production
- URL and redirect differences that require migration logic
- authentication and data-access differences that affect a new instance

### Open issues / special cases

- Legacy WordPress content may remain in custom tables or older media paths.
- Some URLs or page slugs may have been intentionally deprecated and still receive redirects.
- Custom plugin functionality may exist live without a repo equivalent and must be re-implemented explicitly.
- Media-hosting/CDN differences need to be verified before migration.

## 2. WordPress bootstrap process for a new ThemisDB instance

### Supported setup path

The repo implements a central factory in `wordpress-site-factory/`:

- `build_site.py` — entry point for dry-run and live bootstrap
- `bootstrap.py` — WordPress connectivity, theme, and plugin readiness validation
- `taxonomy.py` — reserved taxonomy creation and tag setup
- `pages.py` — page hierarchy generation and front-page assignment
- `validation.py` — smoke tests for homepage and content readiness

### Required runtime checks

A fresh WordPress instance must validate the following before production setup:

- REST API connectivity: `GET /wp-json/wp/v2/settings`
- Theme availability: active or installed theme must match the configured stylesheet
- Plugin availability: all required plugins must be discoverable in the WP plugin registry
- Auth credentials: application password must be valid for the configured user
- Taxonomy availability: reserved selection/restricted/descriptive tags must be accessible
- Page creation: required pages must be created idempotently

### Dry-run / live-run procedure

1. Configure the WordPress target in `wordpress-site-factory/config/site.json`.
2. Ensure a valid application password is set for the chosen WordPress user.
3. Run a dry-run:

```bash
cd /home/runner/work/wordpressPlugins/wordpressPlugins/wordpress-site-factory
python build_site.py --dry-run
```

4. Review the output for: connection check, theme/plugin readiness, taxonomy creation, page generation, and validation.
5. Execute the live setup:

```bash
cd /home/runner/work/wordpressPlugins/wordpressPlugins/wordpress-site-factory
python build_site.py --profile local-test
```

### Production validation checklist

The site-factory run should confirm:

- WordPress REST API is reachable and authenticated
- the configured theme is detected
- required plugins are detected/activated when present
- reserved taxonomy terms are created idempotently
- the public page tree is created without duplicates
- homepage is assigned correctly
- smoke test confirms the homepage and query endpoints are available

### Error handling and failure modes

The bootstrap scripts should fail clearly when:

- the WordPress REST endpoint is not reachable
- the configured theme is not installed
- a plugin is missing or the activation call fails
- app-password or user credentials are invalid
- taxonomy or page creation fails because the site is not ready

All such failures must remain traceable and log the actual HTTP or subprocess failure path.

## 3. Repo-to-live delta summary

The repository currently contains the orchestration layer for the new setup in `wordpress-site-factory/`, but the live production instance itself is not yet audited in the repo as a formal artifact. The audit report closes that gap by documenting the target system and the delta model the team should use for a clean rebuild.

The current repo structure already covers the core implementation contracts:

- factory bootstrap logic
- reserved taxonomy definitions
- page scaffold generation
- validation checks for public site readiness
- importer contract for normalized Markdown/HTML to target blocks

The remaining operational work is a real live instance inventory and delta capture against that structure.

## 4. Acceptance criteria alignment

This repository now contains:

- a formal audit document covering the live site and migration baseline (`wordpress-site-factory/AUDIT_REPORT.md`)
- the documented dry-run and live-run setup workflow for a ThemisDB instance
- the local configuration entry point with a safe blank password placeholder for environments that should not keep a static secret in-repo
- a repeatable validation path through the factory scripts

## 5. Notes

This document is intentionally focused on safe bootstrap and delta capture. It does not replace the need for a full live audit on the actual production site, but it gives the repo a clear operational baseline and a transparent migration strategy.
