# WordPress CI/CD Guidelines (AI-geeignet)

## Ziel
Diese Datei definiert verbindliche Leitlinien für automatisches Deployment von WordPress-Plugins und Themes in diesem Repository und gleicht den aktuellen Stand mit WordPress Best Practices ab.

## Scope
- Plugins und Themes in `makr-code/wordpressPlugins`
- GitHub Actions Workflows in `.github/workflows/*.yml`
- Release-Artefakte (ZIP), Tags, GitHub Releases, Update-Metadaten

## 1) Ist-Stand im Repository (Kurz-Audit)

### Vorhandene Deploy-Workflows
- `wordpress-plugin-release.yml`: manueller Plugin-Release (workflow_dispatch), validiert `update-info.json`, baut ZIP, erstellt Tag + GitHub Release.
- `wordpress-theme-release.yml`: manueller Theme-Release (workflow_dispatch), validiert `style.css` Version, baut ZIP, erstellt Tag + GitHub Release.
- `build-and-test.yml`: Build/Test bei Push/PR; enthält Lint/PHPCS/PHPUnit, Artifact-Build bei Tags.
- `release.yml`: zusätzlicher manueller Release-Workflow mit `actions/create-release@v1`.

### Stärken
- Versionsabgleich vor Release vorhanden (Plugin über `update-info.json`, Theme über `style.css`).
- Plugin-spezifische Tag-Namenskonvention (`plugin-slug/vX.Y.Z`) bereits integriert.
- ZIP-Pakete werden reproduzierbar erzeugt.
- Gemeinsamer Updater (`includes/class-themisdb-plugin-updater.php`) für Auto-Updates vorhanden.

### Abweichungen / Risiken gegenüber Best Practice
- `build-and-test.yml` enthält `|| true` bei PHPCS/PHPUnit → Qualitätsgates können fehlschlagen ohne Pipeline-Abbruch.
- `build-and-test.yml` referenziert `themisdb-license-manager`, das im Repo nicht vorhanden ist.
- Ein Workflow committet/pusht aus CI zurück nach `main` (`git push origin HEAD:main || true`) → riskant für Nachvollziehbarkeit.
- Veraltete Action-Versionen (`actions/checkout@v3`, `softprops/action-gh-release@v1`, `actions/create-release@v1`).
- Uneinheitliche Release-Pfade (mehrere Release-Workflows mit überlappender Verantwortung).

## 2) WordPress Best Practice Abgleich (Plugins/Themes)

### A. Release Governance
1. **Ein Release-Workflow pro Artefakt-Typ** (Plugin/Theme), klarer Trigger, klare Ownership.  
2. **Immutable Releases**: veröffentlichte Tags und ZIPs nicht überschreiben.  
3. **Version Single Source of Truth**:
   - Plugin: Hauptdatei-Version + `update-info.json` müssen identisch sein.
   - Theme: `style.css` `Version` ist führend.

### B. Quality Gates (müssen blockieren)
1. PHP Lint, PHPCS (WordPress Standard), Tests ohne `|| true`.  
2. Build nur wenn Tests grün sind.  
3. Theme-spezifische Gates (wie vorhandene Inline-Style/wp:html Checks) beibehalten und für alle produktiven Themes vereinheitlichen.

### C. Packaging
1. ZIP muss nur deploybare Runtime-Dateien enthalten.  
2. Ausschlüsse (`.git`, `node_modules`, `dist`, Test-/Temp-Dateien) sind Pflicht.  
3. Dateistruktur im ZIP muss WordPress-kompatibel sein (Top-Level-Ordner = slug).

### D. Auto-Update-Fähigkeit
1. Plugin-Header mit `Update URI` muss konsistent und eindeutig sein.  
2. `download_url` in Update-Metadaten muss auf tatsächlich veröffentlichtes ZIP zeigen.  
3. Bei mehreren Plugins im Monorepo: plugin-spezifische Tags und Assets strikt beibehalten.

### E. Security & Supply Chain
1. Workflow-Berechtigungen minimal halten (`permissions` nur was nötig ist).  
2. Actions bevorzugt auf aktuelle Major-Versionen, idealerweise SHA-pinned.  
3. Keine Secrets im Code/Artefakt; nur GitHub Secrets.  
4. Kein automatisches Pushen in protected branches aus Build-Jobs.

## 3) Verbindliche Guidelines für dieses Repository

1. **Release-Schnitt**
   - Plugin-Releases nur über `wordpress-plugin-release.yml`.
   - Theme-Releases nur über `wordpress-theme-release.yml`.
   - `release.yml` nur behalten, wenn es klar abgegrenzte Zusatzaufgaben hat; sonst entfernen oder stilllegen.

2. **Gate-Policy**
   - `|| true` aus PHPCS/PHPUnit entfernen.
   - Fehlende/ungültige Zielpfade (z. B. nicht existierende Plugins) als Fehler behandeln.

3. **No-CI-Writeback-Policy**
   - Keine Versionsdateien in `main` aus CI zurückpushen.
   - Versionsänderungen erfolgen im PR vor dem Release, nicht nachträglich durch Workflow.

4. **Versionierungsregel**
   - Plugin-Version in Hauptdatei und `update-info.json` synchron halten.
   - Theme-Version in `style.css` als Release-Eingabe validieren (bereits vorhanden).

5. **Artifact-Policy**
   - Pro Release genau ein eindeutiges ZIP pro Plugin/Theme-Slug.
   - Dateiname und Tag-Namensschema konsistent halten (`<slug>.zip`, `<slug>/v<version>`).

6. **Dokumentationsregel**
   - Jede Änderung an Release-Workflows muss diese Datei und die operative Release-Dokumentation mit aktualisieren.

## 4) AI-Ausführungsrichtlinien (für künftige Agent-Runs)

Wenn ein AI-Agent CI/CD ändert, muss er:
1. Vorher/Nachher-Audit der betroffenen Workflows dokumentieren.
2. Prüfen, dass alle Gates hart fehlschlagen.
3. Prüfen, dass kein CI-Step nach `main` pusht.
4. Prüfen, dass Version, Tag und Artefaktname übereinstimmen.
5. Prüfen, dass Plugin/Theme-Metadaten WordPress-konform bleiben.
6. Bei Unsicherheit: erst Analyse + Plan, dann minimale Änderung.

## 5) Sofortmaßnahmen (priorisiert)
1. `build-and-test.yml`: `|| true` entfernen und fehlende Plugin-Referenzen bereinigen.
2. `build-and-test.yml`: CI-Writeback (`git push`) entfernen.
3. `release.yml` gegen aktuellen `gh-release`-Ansatz konsolidieren oder deaktivieren.
4. Action-Versionen modernisieren und Berechtigungen pro Job einschränken.

