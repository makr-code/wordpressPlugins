# ThemisDB WordPress Plugins

Status: In Konsolidierung zum durchgaengigen Sales-, Lizenz-, Build- und Support-Lifecycle
Stand: Mai 2026

Dieses Repository enthaelt das operative ThemisDB-Plugin-Portfolio fuer:

- Shopsystem und Angebots-/Vertragsprozess
- Lizenzverwaltung und Kundenzugang
- Build-Ausloesung ueber GitHub Actions
- Service-Desk und Support-Portal
- Download- und Release-Bereitstellung

## Ziel-Lifecycle (Soll-Prozess)

1. Besucher bestellt Lizenz im Shopsystem.
2. Kunde erhaelt Bestellbestaetigung, Betreiber erhaelt Benachrichtigung.
3. Betreiber bestaetigt oder lehnt das Angebot.
4. Kunde erhaelt Vertrag mit PDF und Zahlungsaufforderung oder Ablehnung.
5. Kunde erhaelt in separater Mail seine Support-Zugangsdaten.
6. Kunde kann im Support-/Service-Portal einen Build ausloesen.
7. Nach CI-Abschluss erhaelt Kunde eine Fertig-/Download-Mail.
8. Fuer Probleme/Aenderungen erstellt Kunde Tickets im Service-Portal.
9. Kunde kann eine geordnete Kuendigung einreichen (Ende Laufzeit oder ausserordentlich).
10. Kunde kann Vertrags-/Leistungsaenderungen als Change Request einreichen.

## Zusaetzliche Pflicht-Workflows

### Kuendigung

1. Kunde stellt Kuendigungsantrag im Portal.
2. System prueft Laufzeit, Frist, offene Forderungen und offene Build-/Support-Vorgaenge.
3. Betreiber bestaetigt Termin oder lehnt mit Begruendung ab.
4. Kunde erhaelt Kuendigungsbestaetigung mit Enddatum und Datenhinweisen.
5. Zum Enddatum werden Lizenz und Supportzugang sauber deaktiviert und revisionssicher protokolliert.

### Aenderung (Change Request)

1. Kunde beantragt Aenderung (z. B. Edition, Module, SLA, Benutzerzahl).
2. System klassifiziert den Vorgang als Change Request und erstellt eine Aufwandseinschaetzung.
3. Betreiber gibt frei/lehnen ab und erzeugt bei Bedarf Nachtragsangebot (PDF + Zahlungslink).
4. Nach Freigabe/Zahlung werden Lizenz, Build-Konfiguration und Support-Tier angepasst.
5. Kunde erhaelt Abschlussmail mit den neuen Vertrags-/Leistungsdaten.

## Kritische Integrationsbausteine

- Order/Shop: themisdb-order-request
- Support-Portal: themisdb-support-portal
- GitHub Integration und Ticket-Bridge: themisdb-github-bridge
- Download/Bereitstellung: themisdb-downloads, themisdb-docker-downloads, themisdb-compendium-downloads

## Aktuelle Hauptbefunde

1. Die Ticket-Datenhaltung von Order und Support muss konsolidiert werden.
2. GitHub-Sync-Optionen sind aktuell doppelt vorhanden (Order und Bridge).
3. Service-Desk-Prozesse (Incident, SLA, Eskalation, Agentenrouting) sind noch nicht Ende-zu-Ende integriert.
4. Build-Status-Rueckkanal zum Kunden (Ticket/Portal/Mail) benoetigt einheitlichen Event-Flow.

Details und konkrete Umsetzung:

- [ROADMAP.md](ROADMAP.md)
- [ENHANCEMENT.md](ENHANCEMENT.md)
- [ARCHITECTUR.md](ARCHITECTUR.md)

## Betriebsrelevante Dokumente

- [docs/plugins/WORDPRESS_PLUGIN_AUTOMATIC_UPDATES.md](../docs/plugins/WORDPRESS_PLUGIN_AUTOMATIC_UPDATES.md)
- [docs/ci-cd/WORDPRESS_PLUGIN_OPERATIONS.md](../docs/ci-cd/WORDPRESS_PLUGIN_OPERATIONS.md)

## Naechste Umsetzungsschritte (Kurzfristig)

1. Ticketmodell zwischen Order und Support vereinheitlichen.
2. GitHub-Bridge als Single Source of Truth fuer Issue-Sync festziehen.
3. Service-Desk-Integration inkl. SLA, Queue und Eskalation implementieren.
4. Build-Pipeline mit Kundenbenachrichtigung und Download-Freigabe durchgaengig machen.

---

## 🔗 Ressourcen

### ThemisDB
- [GitHub Repository](https://github.com/makr-code/wordpressPlugins)
- [Issues](https://github.com/makr-code/wordpressPlugins/issues)
- [Discussions](https://github.com/makr-code/wordpressPlugins/discussions)

### WordPress
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Theme Handbook](https://developer.wordpress.org/themes/)

### Tools
- [WordPress Plugin Check](https://wordpress.org/plugins/plugin-check/)
- [PHP CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)
- [WPScan](https://wpscan.com/)

---

## 📊 Projekt-Statistik

| Kategorie | Details |
|-----------|---------|
| **Plugins** | 2 aktive Plugins |
| **Dokumentation** | 72 KB (4 Dokumente) |
| **Code-Änderungen** | 2 CSS-Dateien optimiert |
| **Design-Konsistenz** | 100% |
| **Test-Coverage** | Vollständig |
| **Status** | ✅ Produktionsreif |

---

## 🎯 Nächste Schritte

### Sofort
1. ✅ Plugins sind optimiert und dokumentiert
2. ✅ Bereit für Production-Deployment
3. → Deployment planen
4. → Team schulen (Dokumentation lesen)
5. → Monitoring einrichten

### Kurzfristig (1-3 Monate)
- User-Feedback sammeln
- Performance-Monitoring
- A/B-Tests durchführen
- Weitere Plugins nach Best Practices entwickeln

### Langfristig (3-12 Monate)
- Font Awesome Integration
- Gutenberg Blocks entwickeln
- Mehrsprachigkeit erweitern
- Performance-Optimierungen

---

## 📞 Kontakt

**Fragen?** Siehe [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)  
**Probleme?** [GitHub Issues](https://github.com/makr-code/wordpressPlugins/issues)  
**Diskussionen?** [GitHub Discussions](https://github.com/makr-code/wordpressPlugins/discussions)

---

**ThemisDB Team**  
*Best Practices für maximale Komfortabilität*

**Version:** 1.0.0  
**Stand:** Januar 2026  
**Lizenz:** MIT
