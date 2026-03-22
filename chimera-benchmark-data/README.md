# Chimera Benchmark Data

WordPress Plugin zur Verwaltung von Chimera Benchmark-Daten.

## Features

- Eigene Datenbanktabelle fuer Benchmark-Messwerte
- Admin-Oberflaeche fuer vollstaendige CRUD-Operationen (Anlegen, Bearbeiten, Loeschen)
- Bulk-Operationen im Admin (Mehrfach-Loeschen, Feld-Updates fuer mehrere Datensaetze)
- Admin-Filter und Pagination fuer grosse Datenmengen
- Native WP_List_Table Bedienung (Sortierung, Pagination, Bulk-Actions oben/unten)
- Inline-Quick-Edit direkt in der Admin-Tabelle fuer schnelle Feldanpassungen (workload/engine/metric_name/dataset_size/environment)
- CSV-Import mit Header-Mapping
- CSV-Export (optional gefiltert nach workload/engine/metric_name)
- REST-API mit vollstaendigen CRUD-Operationen
- REST-Bulk-Endpoint fuer Mehrfach-Loeschen/Mehrfach-Update
- Frontend-Shortcodes fuer Tabelle und Chart

## Tabelle Shortcode

`[chimera_benchmark_table]`

Optionale Attribute:
- `workload`
- `engine`
- `metric_name`
- `limit` (Standard: 50)

Beispiel:

`[chimera_benchmark_table workload="oltp" engine="chimera" metric_name="latency_ms" limit="25"]`

## Chart Shortcode

`[chimera_benchmark_chart]`

Optionale Attribute:
- `workload`
- `engine`
- `metric_name`
- `limit`
- `chart_type` (`bar`|`line`|`radar`)
- `label_field` (`engine`|`workload`|`benchmark_name`|`dataset_size`|`run_at`)
- `group_by` (`engine`|`workload`|`benchmark_name`|`dataset_size`|`run_at`) optional, aggregiert Werte als Mittelwert je Gruppe
- `order` (`ASC`|`DESC`)

## REST API

Endpoints:

- `GET /wp-json/chimera-benchmark/v1/records`
- `GET /wp-json/chimera-benchmark/v1/records/{id}`
- `POST /wp-json/chimera-benchmark/v1/records`
- `PUT|PATCH /wp-json/chimera-benchmark/v1/records/{id}`
- `DELETE /wp-json/chimera-benchmark/v1/records/{id}`
- `POST /wp-json/chimera-benchmark/v1/records/bulk`

Hinweis Berechtigungen:
- `GET` ist oeffentlich
- `POST/PUT/PATCH/DELETE` erfordern WordPress-Rechte `manage_options`
- `POST /records/bulk` erfordert ebenfalls `manage_options`

Listen-Endpoint liefert zusaetzlich Metadaten:
- `total`
- `page`
- `per_page`
- `total_pages`
- `orderby`
- `order`
- `links.first`
- `links.prev`
- `links.next`
- `links.last`

Optionale Query-Parameter fuer Liste:
- `workload`
- `engine`
- `metric_name`
- `limit` (legacy alias fuer `per_page`)
- `page`
- `per_page`
- `orderby`
- `order`

Bulk-Request Beispiel:

```json
{
  "operation": "update",
  "ids": [1, 2, 3],
  "field": "engine",
  "value": "chimera-v2"
}
```

## CSV Header

Erwartete Header in dieser Form:

- `benchmark_name`
- `workload`
- `dataset_size`
- `engine`
- `environment`
- `metric_name`
- `metric_unit`
- `metric_value`
- `run_at`
- `notes`
- `metadata`
