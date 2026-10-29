# ThemisDB Global Network Map

Standalone WordPress shortcode for a stylized 3D globe with real local GeoJSON
continents and geolocated ThemisDB homecalls. No theme-specific renderer, external
CDN, global Three.js variable or runtime npm installation is required.

## Display and navigation

- The canvas occupies the entire map container. The globe uses **cover**, not
  contain: it fills both canvas dimensions; wide/tall viewports intentionally crop
  parts of the sphere. The shortcode `height` controls the overall map height.
- Controls, instance list, legend, renderer/source information and errors are
  overlays. The native, keyboard-accessible hamburger disclosure contains settings
  and locations; Escape or clicking outside closes it.
- A bundled, curated city reference layer adds major cities and important regional
  centers worldwide. These neutral labels are geographic orientation, **not**
  instance reports or additional tour hotspots. Globe shows major global centers,
  Region includes regional cities, and Country includes more local centers.
  Labels follow the globe, hide on the far hemisphere, avoid other labels and UI
  overlays, and reduce density on small screens. Toggle **Major cities** in the
  menu; the preference is persisted alongside the other settings. No external
  geocoding service is used.
  Colored instance markers retain a small screen-space radius while zooming, so
  geographic city labels are not swallowed by oversized telemetry dots.
- The default automatic tour visits the available instance hotspots in order:
  zoom out (1.8 s), fly along a spherical great-circle arc (3.8 s), zoom in (2.2 s),
  hold (5 s), then visit the next hotspot. Speed scales these durations. Duplicate
  geographic locations are visited only once per cycle.
- Overflights include a slight camera bank; during automatic focus holds the camera
  drifts gently around the destination. Both stop for reduced motion or a paused
  tour. City labels are thinned during travel.
- A compact focus card shows instance count, load, status and coordinates during
  approach/hold. Demo cards are explicitly labeled. Soft rings highlight instance
  locations; a gentle focus-ring breath is decorative selection feedback, **not**
  a simulated heartbeat or data-transfer event.
- Globe, Region and Country select progressively closer arrival zooms. With no
  geolocated instances, the globe rotates slowly without inventing hotspots.
  One hotspot is focused once and held, rather than repeatedly flying to itself.
- A selected geographic target or manual drag/zoom holds the view. Choose
  **Hotspot tour** or re-enable automatic rotation to resume the tour.
- Pointer drag, arrow keys and +/- operate the camera. Ctrl/Command + wheel zooms;
  ordinary wheel scrolling remains available for the WordPress page.
- `prefers-reduced-motion` disables automatic motion on load and on preference
  changes; explicit target selection is immediate. Users may explicitly enable
  the tour in the menu.
- Settings use the first-party `themisdb_gnm_settings` cookie for 180 days:
  mode, automatic rotation, drag, city labels, speed, selected target and manual zoom.
  It stores no telemetry, IP address or instance identifier. The cookie uses
  `SameSite=Lax`, site-wide path and `Secure` on HTTPS. Invalid/blocked preferences
  produce a visible message. Reset restores and saves defaults.

## Renderer and geography

Three.js `WebGPURenderer` prefers WebGPU and uses WebGL2 if unavailable. The actual
backend is always labeled in the overlay. If neither works, the user sees an
explicit error, not a fake successful map.

The bundled `assets/data/world.geojson` is loaded once per page and rasterized
onto a spherical texture. Polygon/MultiPolygon rings, holes, date-line wrapping
and Antarctica are retained. The renderer does not substitute invented landmasses
if geography fails. The original geography asset is unchanged by the build.

The physical material uses a GeoJSON-derived roughness mask (matte land,
glossier water), directional studio lighting and a view-dependent atmospheric
rim shared by the WebGPU and WebGL2 backends. This is stylized lighting, not a
real-time astronomical day/night calculation. No invented elevation data is used.
Small maps initialize with lower texture resolution and a capped pixel ratio.

Animation pauses when the map is offscreen or the document hidden; paused camera
views render only when needed. Resize observers, pointer capture/cancel and GPU
resource cleanup keep each map instance independent.

## WordPress integration

```text
[themisdb_global_network_map mode="production" height="540" theme="ocean" show_controls="true" show_legend="true" max_nodes="250"]
```

Optional `title` displays an overlay title. Themes: `ocean`, `aurora`, `night`.
`show_controls="false"` hides settings but retains the accessible location list
in the hamburger menu. Multiple shortcodes have separate configuration and no
duplicate IDs. No changes to Gutenberg or the theme are necessary.

The browser reads `GET /wp-json/themisdb-gnm/v1/nodes?mode=production&max_nodes=250`.
The response contains `nodes`, `source`, `generatedAt`, and `maxNodes`.
Refresh follows `themisdb_gnm_cache_ttl` (default 120 seconds, minimum 30).
This is a **read-only public display endpoint**, not a homecall receiver.

## Existing homecall data

The plugin creates/updates these prefixed tables with `dbDelta()` on activation
and version upgrades:

- `themisdb_gnm_homecalls`: instance ID, source IP, optional network and geography,
  instance count, load, status, active flag, creation/update timestamps.
- `themisdb_gnm_geo_cache`: IP-to-coordinate cache with resolution status.

An existing external homecall service must populate these tables. This plugin
does not add an ingestion endpoint or call a third-party GeoIP service.

Only the newest row for each `instance_id` is considered; legacy rows without an
ID use IP identity. A newer inactive row suppresses older active reports. Distinct
instance IDs sharing a NAT address remain distinct before location aggregation.
Valid coordinates in the homecall override cached coordinates. Otherwise a
resolved cache entry is used. Zero latitude/longitude is valid; missing, nonnumeric
and out-of-range values are excluded. Locations with the same coordinates and
network are aggregated, with instance counts summed and maximum load displayed.
No arbitrary heartbeat expiry is assumed: the upstream service manages
`is_active`. `max_nodes` limits newest eligible instance records before location
aggregation (1–1000); it is not an unlimited global instance total.

The public response exposes display geography and an optional supplied network,
not the raw source-IP column. Database/schema failures return HTTP 503 and are
shown in the overlay. Deactivation retains tables and settings.

### Data modes

- `production`: real data only, including a genuine empty state; never demo fallback.
- `demo`: built-in example locations only.
- `auto`: production when available; otherwise demo if
  `themisdb_gnm_allow_demo_fallback` is enabled (default).

Demo data is visibly labeled **DEMO / not live homecalls** and marked `is_demo`.
An actual DB error is not disguised by switching to demo data.

## Build and tests

The production bundle is tree-shaken and minified, including shortened internal
identifiers. No source maps, dynamic code evaluation or obfuscation runtime are
generated. The readable development sources remain in Git. Browser-side code
cannot be made secret: minification discourages casual copying but is not access
control, encryption or protection for credentials. Keep privileged logic and
secrets on the server. The build also writes `network-map.js.gz`. A scoped
`assets/dist/.htaccess` negotiates this precompressed copy when Apache rewrite,
headers and MIME modules and directory overrides are available. It honors
`gzip;q=0` and sets `Vary: Accept-Encoding`; clients without gzip receive the
ordinary bundle. No global Apache/WordPress settings are changed. Other servers
require their own gzip/Brotli configuration. The plugin does not claim that
minification provides copy protection.
Distribution tests enforce size/compression budgets and absence of source maps.

From the plugin directory:

```powershell
npm ci
npm test
npm run build
php tests\test-wordpress.php C:\xampp\htdocs\wordpress\wp-load.php
```

Ship `assets/dist/` including its hidden `.htaccess`, both JS variants, the
Three.js license, CSS, geography,
PHP and template files. `node_modules` is local-only and ignored by Git; the pinned
manifest/lockfile and built distribution are versioned. The build copies the
Three.js MIT license alongside its bundle.

Node tests cover geographic projection, date-line/hole handling, real GeoJSON,
telemetry validation, city data/label collisions, cookie validation, flight continuity/phases and cover
framing. WordPress integration tests require InnoDB, use transaction-isolated
fixtures, and roll them back in `finally`. They check latest reports, NAT counts,
inactive rows, coordinate validation, cache precedence and shortcode isolation.
