# ThemisDB Plain URL Query Map

Ziel: Betrieb ohne Apache/Nginx-Rewrite-Regeln (Plesk), mit stabilen Query-URLs.

Wichtig: Diese Datei ist eine Betriebs- und Monitoring-Referenz, keine fest verdrahtete Laufzeit-Mapping-Logik.
Die Themes/Plugins sollen WordPress-nah bleiben und URLs dynamisch ueber Core-APIs aufloesen (z.B. `get_permalink()`, `get_page_by_path()`, `home_url()`).

## Optionaler Plesk-Fallback (standardmaessig aus)

In allen drei Themes ist ein optionaler 404-Resolver vorhanden. Er versucht bei echten Frontend-404ern den angefragten Pfad intern auf eine Seite/einen Beitrag aufgeloest und leitet dann auf `get_permalink()` weiter.

- Theme-Mod Schalter: `themisdb_enable_plesk_permalink_fallback`
- Default: `false`
- Alternativ global per Filter: `themisdb_enable_plesk_permalink_fallback`

Beispiel (mu-plugin oder Child-Theme):

```php
add_filter( 'themisdb_enable_plesk_permalink_fallback', '__return_true' );
```

Damit bleibt die Standardlogik WordPress-nah, und der Fallback wird nur bei Bedarf aktiviert.

## Regeln

- Seiten/Hierarchie-Seiten: `/?pagename=<slug-oder-pfad>`
- Beiträge: `/?p=<post_id>` (ID erforderlich)
- Startseite: `/`

Hinweis: IDs sind offline nicht sicher bestimmbar. Fuer produktive Redirects sollte die Ziel-URL dynamisch aus dem gefundenen Objekt berechnet werden.

## Priorisierte Mappings

- `/blog` -> `/?pagename=blog` (oder `/?page_id=<posts_page_id>` falls als Beitragsseite gesetzt)
- `/downloads` -> `/?pagename=downloads`
- `/docker` -> `/?pagename=docker`
- `/query-playground` -> `/?pagename=query-playground`
- `/benchmarks` -> `/?pagename=benchmarks`
- `/community` -> `/?pagename=community`
- `/contact` -> `/?pagename=contact`
- `/tco-calculator` -> `/?pagename=tco-calculator`
- `/changelog` -> `/?pagename=changelog`

## Docs-Mappings

- `/docs` -> `/?pagename=docs`
- `/docs/getting-started` -> `/?pagename=docs/getting-started`
- `/docs/introduction` -> `/?pagename=docs/introduction`
- `/docs/installation` -> `/?pagename=docs/installation`
- `/docs/quick-start` -> `/?pagename=docs/quick-start`
- `/docs/configuration` -> `/?pagename=docs/configuration`
- `/docs/architecture` -> `/?pagename=docs/architecture`
- `/docs/security` -> `/?pagename=docs/security`
- `/docs/performance` -> `/?pagename=docs/performance`
- `/docs/replication` -> `/?pagename=docs/replication`
- `/docs/vector-search` -> `/?pagename=docs/vector-search`
- `/docs/ai-integration` -> `/?pagename=docs/ai-integration`
- `/docs/changelog` -> `/?pagename=docs/changelog`
- `/docs/migration` -> `/?pagename=docs/migration`

## API-Docs-Mappings

- `/docs/api` -> `/?pagename=docs/api`
- `/docs/api/overview` -> `/?pagename=docs/api/overview`
- `/docs/api/rest` -> `/?pagename=docs/api/rest`
- `/docs/api/sql` -> `/?pagename=docs/api/sql`
- `/docs/api/graphql` -> `/?pagename=docs/api/graphql`
- `/docs/api/websocket` -> `/?pagename=docs/api/websocket`
- `/docs/api/sdks` -> `/?pagename=docs/api/sdks`

## Feature-Mappings

- `/features/multi-model` -> `/?pagename=features/multi-model`
- `/features/ai-integration` -> `/?pagename=features/ai-integration`
- `/features/analytics` -> `/?pagename=features/analytics`
- `/features/query-playground` -> `/?pagename=features/query-playground`
- `/features/feature-matrix` -> `/?pagename=features/feature-matrix`

## Sonderziele

- `/downloads/compendium` -> `/?pagename=downloads/compendium`
- `/newsletter` -> `/?pagename=newsletter`
- `/sitemap.xml` bleibt statisch/SEO-Endpoint und sollte nicht auf Query-URL umgebogen werden.
