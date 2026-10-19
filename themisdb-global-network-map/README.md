# ThemisDB Global Network Map

This plugin renders a global network node map for ThemisDB runtime instances. It is intended to consume production homecall telemetry and geodata from WordPress, while still providing a safe demo fallback when production data is unavailable or intentionally disabled.

## Data model

The plugin creates and uses the following WordPress tables when activated:

- `wp_themisdb_gnm_homecalls`
  - Raw homecall telemetry rows captured from active ThemisDB instances.
  - Stores the source IP and runtime metadata needed to build map nodes.
  - Typical columns: `instance_id`, `ip_address`, `network`, `city`, `region`, `country`, `lat`, `lon`, `instances`, `load`, `status`, `is_active`, `source`, `created_at`, `updated_at`.

- `wp_themisdb_gnm_geo_cache`
  - IP-to-geo cache for resolved locations.
  - Avoids repeating expensive GeoIP resolution for the same IP address on every render.
  - Typical columns: `ip_address`, `city`, `region`, `country`, `lat`, `lon`, `geo_status`, `source`, `created_at`, `updated_at`.

The tables are created with `dbDelta()` during plugin activation and include indexes for active rows, country lookups, IP queries, and cache freshness.

## Production flow

The production path is intentionally IP-first:

1. A ThemisDB instance records a homecall row in WordPress.
2. The row includes the runtime IP address and node metadata such as load, instances, and health status.
3. The plugin queries active homecalls from `wp_themisdb_gnm_homecalls`.
4. For each IP, the plugin checks the matching record in `wp_themisdb_gnm_geo_cache`.
5. If the IP is not resolved yet, the plugin uses the row data when it already contains a valid `lat`/`lon` pair.
6. Valid, map-ready nodes are aggregated, normalized, and cached in a transient for a short period.
7. The final node list is passed to the map renderer.

This keeps the plugin focused on consuming telemetry rather than generating it, while still making the map resilient and cache-aware.

## Demo fallback mode

The plugin supports the following modes:

- `auto` — use production data when available; otherwise return demo nodes.
- `production` — prefer real DB data; may soft-fallback to demo only if configured.
- `demo` — always return the built-in demo dataset.

The fallback is safe by design: real nodes with valid coordinates are still preferred, and only unresolved production nodes are skipped or replaced by demo data when the plugin is configured to do so.

## Local GeoJSON and map rendering

The map no longer depends on an external GitHub raw GeoJSON URL. Instead it uses a local plugin asset:

- `assets/data/world.geojson`

The asset URL is injected into the JavaScript via `wp_localize_script()`. If the file is missing, the plugin keeps the existing fallback landmass generation in JavaScript as a graceful last resort.

## Notes

- All DB access uses WordPress-safe patterns and prepared statements where dynamic queries are required.
- User-facing values are sanitized before output.
- Production node coordinates are only rendered when they are valid lat/lon values; the map does not randomly synthesize geographic positions in production mode.
- The shortcode remains compatible with the existing structure while allowing optional parameters such as `mode`, `max_nodes`, `theme`, and `show_controls`.
