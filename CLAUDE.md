# CLAUDE.md

Diese Datei gibt Claude Code Kontext für die Arbeit in diesem Repository.

## Projekt

AG Orion – Mini-Website zur partiellen Sonnenfinsternis am **12. August 2026**.
Lokal entwickelt und veröffentlicht auf **https://sofi.agorion.de** (nginx + PHP-FPM + MariaDB).

Ursprünglich als rein statische Seite mit Airtable-Embeds gebaut; seit Juli 2026 auf ein
eigenes PHP/MySQL-Backend umgestellt (siehe [PLAN-mysql-migration.md](PLAN-mysql-migration.md)
für Hintergrund und Architektur-Entscheidungen). Airtable wird nicht mehr verwendet.

## Tech-Stack

* PHP (Server-Templating, kein Framework, kein Build-Schritt — Dateien werden 1:1 deployt)
* MySQL/MariaDB (eigene Datenbank `ago_sofi` auf dem Server, der auch die WordPress-Seite
  `andere-seite` hostet — eigener DB-User, nur Rechte auf `ago_sofi`)
* CSS (kein Präprozessor)
* Vanilla JavaScript
* **Leaflet** (Kartenbibliothek), per CDN eingebunden (`unpkg.com`, mit
  Subresource-Integrity-Hash), kein Build-Schritt nötig

## Projektstruktur

```text
/
├── CLAUDE.md
├── README.md
├── DEPLOYMENT.md
├── PLAN-mysql-migration.md   ← Architektur-/Migrationsplan, Hintergrund
├── db/
│   ├── schema.sql             (CREATE TABLE — einmalig ausgeführt)
│   └── seed-migration.sql     (einmalige Migration der Airtable-Altdaten)
├── deploy/
│   ├── deploy.sh
│   └── nginx/sofi.agorion.de.conf
└── site/                      ← wird auf den Server deployt (nginx root zeigt hierher)
    ├── index.php
    ├── alle-standorte.php
    ├── standort.php            (Detailseite, erreichbar unter /standort/<slug>)
    ├── beobachten.html
    ├── impressum-datenschutz.php
    ├── verwaltung/                  (Basic-Auth-geschützte Datenpflege)
    │   ├── index.php
    │   ├── edit.php
    │   └── delete.php
    ├── api/
    │   └── standorte.php       (öffentliches JSON für die Karte)
    ├── inc/
    │   ├── db.php               (PDO-Verbindung)
    │   ├── header.php / footer.php
    │   ├── helpers.php
    │   ├── csrf.php
    │   └── upload.php
    ├── css/
    │   └── style.css
    ├── js/
    │   └── map.js
    ├── img/
    └── uploads/                (Standort-Fotos; nicht Teil des Git-Repos/Deploys)
```

Alles, was ausgeliefert werden soll, liegt unterhalb von `site/`. Dateien außerhalb von `site/`
(README, CLAUDE.md, Deployment-Doku, `db/`-Skripte etc.) landen nie auf dem Webserver.

**`site/uploads/` ist von `deploy.sh` per `--exclude` ausgenommen** (siehe DEPLOYMENT.md) —
Fotos entstehen direkt auf dem Server über das Verwaltungsformular und dürfen von einem Deploy vom
Mac aus nicht überschrieben/gelöscht werden.

## Seiten

* **index.php** — Startseite: kurze Einführung, Leaflet-Karte mit Markern nur für geprüfte
  Standorte (Status Geeignet, Eingeschränkt geeignet, Vor Ort geprüft), aufklappbare Liste
  derselben Standorte, Link zu `alle-standorte.php`.
* **alle-standorte.php** — eigene Leaflet-Karte mit Markern für alle gemeldeten Standorte,
  plus aufklappbare Liste aller gemeldeten Standorte inkl. Status-Badge, unabhängig vom
  Veröffentlichungs-Status.
* **standort.php** (`/standort/<slug>`) — Detailseite eines einzelnen Standorts: alle Felder,
  eigene Karte mit einzelnem Marker, Foto-/Grafik-Galerie.
* **beobachten.php** — Informationen zur sicheren Sonnenbeobachtung (keine DB-Abfrage, nutzt
  aber dieselben `inc/header.php`/`inc/footer.php` wie die anderen Seiten).
* **impressum-datenschutz.php** — Impressum und Datenschutzerklärung (im Footer aller Seiten
  verlinkt). Datenschutztext beschreibt den aktuellen Stand (eigene DB, OpenTopoMap-Kacheln,
  keine Airtable-Einbindung mehr).

## Datenbank

Tabellen `standorte` und `standort_fotos` in der Datenbank `ago_sofi`, Schema in
`db/schema.sql`. Feld-/Statuswerte entsprechen 1:1 der ursprünglichen Airtable-Tabelle
(siehe PLAN-mysql-migration.md für die vollständige Zuordnung).

* **Zugangsdaten** liegen auf dem Server unter `/var/www/sofi.agorion.de-secrets/db-config.php`
  — außerhalb des Webroots, außerhalb des Git-Repos, nie Teil des rsync-Deploys.
  `inc/db.php` bindet diese Datei per absolutem Pfad ein (Override über die Umgebungsvariable
  `AGO_SOFI_DB_CONFIG` für lokale Entwicklung möglich).
* **Öffentliche Filterlogik** (muss zwischen `index.php`, `alle-standorte.php` und
  `api/standorte.php` konsistent bleiben):
  * "Geprüft": `veroeffentlicht = 1 AND status IN ('Geeignet', 'Eingeschränkt geeignet', 'Vor Ort geprüft')`
  * "Alle": keine Einschränkung.
* **Verwaltungsoberfläche** (`site/verwaltung/`) für Anlegen/Bearbeiten/Löschen inkl. Foto-Upload,
  geschützt durch nginx `auth_basic` (siehe DEPLOYMENT.md für Zugangsdaten-Verwaltung) plus
  CSRF-Token (`inc/csrf.php`) auf allen Formularen.

## Kartendaten

Leaflet + OpenTopoMap-Kacheln (wegen Geländeschummerung). `site/js/map.js` lädt die Marker
live über `api/standorte.php?filter=geprueft|alle` (kein statischer Snapshot mehr, kein
Client-seitiger Datenbank-/API-Zugriff — die PHP-Datei fragt serverseitig ab und liefert nur
die für die Karte nötigen Felder als JSON).

## Gestaltung

* Schlicht, schnell, responsiv (Desktop und Smartphone)
* Erscheinungsbild möglichst nah an **agorion.de**
* Wenige Farben, gute Lesbarkeit, Fokus auf Inhalt
* Standort-Listen als aufklappbare Zeilen (`<details>`/`<summary>`, kein JS nötig) statt
  Tabellen-Grid

## Konventionen für die Entwicklung

* Sauberes, semantisches HTML5, PDO mit Prepared Statements (nie String-Konkatenation für SQL)
* CSS übersichtlich strukturiert halten
* JavaScript auf das Notwendigste beschränken (kein unnötiges Tooling, keine Abhängigkeiten)
* Lokal im Browser testen, dabei Desktop- und Smartphone-Ansicht prüfen
* Lokale PHP-Entwicklung gegen die echte MariaDB per SSH-Tunnel möglich (siehe
  PLAN-mysql-migration.md) — kein lokales MySQL nötig für ein Projekt dieser Größe
