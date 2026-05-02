# Delivery System - Überarbeitete Architektur

**Korrektur:** ThemisDB liegt in einem eigenen Repository (`makr-code/ThemisDB`).

---

## Korrigierter Flow

```
┌─────────────────────────────┐   ┌─────────────────────────────────┐
│  makr-code/ThemisDB          │   │  makr-code/wordpressPlugins      │
│  (Core Software)             │   │  (WordPress Plugins)             │
│                              │   │                                  │
│  git tag v1.2.0              │   │  git tag v1.2.0                  │
│       ↓                      │   │       ↓                          │
│  GitHub Actions CI           │   │  GitHub Actions CI               │
│  - Build binary/package      │   │  - Build Plugin ZIPs             │
│  - Run tests                 │   │  - Generate SHA256               │
│       ↓                      │   │  - Publish GitHub Release        │
│  Fetch WP Plugin ZIPs  ◄─────┼───┤  (ZIPs available via Releases)  │
│  (from wordpressPlugins      │   └─────────────────────────────────┘
│   GitHub Releases)           │
│       ↓                      │
│  Bundle Deployment Package   │
│  themisdb-v1.2.0.zip:        │
│  ├── themisdb-core/          │
│  ├── themisdb-order-request/ │
│  ├── themisdb-support-portal/│
│  ├── INSTALL.md              │
│  └── SHA256SUMS              │
│       ↓                      │
│  Upload to themisdb.org      │
│  - SFTP/rsync                │
│  - Update manifest JSON      │
│  - Trigger CDN invalidation  │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       themisdb.org                                   │
│                                                                      │
│  /releases/                                                          │
│  ├── v1.2.0/                                                         │
│  │   ├── themisdb-v1.2.0.zip            (full bundle)               │
│  │   ├── themisdb-order-request-1.2.0.zip  (plugin only)            │
│  │   ├── themisdb-support-portal-1.2.0.zip (plugin only)            │
│  │   └── SHA256SUMS                                                  │
│  ├── latest -> v1.2.0 (symlink)                                      │
│  └── update-manifest.json                                           │
│      {                                                               │
│        "latest_version": "1.2.0",                                   │
│        "download_url": "https://releases.themisdb.org/v1.2.0/...",  │
│        "changelog": "...",                                           │
│        "requires_php": "7.4"                                         │
│      }                                                               │
└──────────┬──────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────┐
│              Kunden WordPress-Installation                            │
│                                                                      │
│  ThemisDB_Plugin_Updater                                             │
│  → checkt https://releases.themisdb.org/update-manifest.json        │
│  → vergleicht Versionen                                              │
│  → bei Update: Download von releases.themisdb.org                   │
│  → WordPress installiert & aktiviert                                 │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Dateien in diesem Repo

Folgende Dateien wurden für den neuen Flow angepasst:

- `includes/class-themisdb-plugin-updater.php` → Update-URL auf themisdb.org
- `*/update-info.json` → Download-URLs auf releases.themisdb.org
- `.github/workflows/build-and-test.yml` → Publiziert ZIPs für ThemisDB-CI
- `scripts/deploy-to-themisdb-org.sh` → Server-Upload-Skript (Template)

## Dateien die in `makr-code/ThemisDB` gehören

- `.github/workflows/release.yml` → Baut Bundle, lädt auf themisdb.org hoch
  → Template liegt in: `docs/ci-templates/themisdb-repo-release.yml`
