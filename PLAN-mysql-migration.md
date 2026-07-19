# Airtable → eigenes PHP/MySQL-System migrieren

## Kontext

Die Standort-Daten laufen aktuell über Airtable-Interfaces, eingebettet als
`<iframe>` in `index.html`/`alle-standorte.html`. Der Nutzer ist mit dem
Ergebnis unzufrieden: die iframe-Darstellung (Airtable-Chrome, generisches
Grid) wirkt nicht zeitgemäß, und das Setup (Interfaces statt klassischem
View-Sharing, manuelles Veröffentlichen pro Seite, kein direkter Koordinaten-
Zugriff) ist umständlicher als erhofft.

Der Server (der auch die WordPress-Seite `andere-seite` hostet)
läuft bereits mit **nginx + PHP-FPM + MariaDB** — die nötige Infrastruktur ist
also schon vorhanden. Ziel: Airtable komplett ablösen durch eine eigene
MySQL-Tabelle, eine schlanke Admin-Oberfläche (Formulare, Fotos-Upload,
einfacher Zugangsschutz) und deutlich schöner gestaltete öffentliche Seiten,
die direkt aus der Datenbank rendern statt über iframes. Der Nutzer sieht das
ausdrücklich auch als Lern-Projekt ("Fingerübung") — Umfang darf entsprechend
größer ausfallen als beim bisherigen minimalen Ansatz.

**Wichtige Korrektur zur Anforderung:** Der Server nutzt **nginx**, keinen
Apache — `.htaccess`-Dateien werden von nginx grundsätzlich nicht gelesen.
Das nginx-Äquivalent für einfachen Zugangsschutz ist die native
`auth_basic` + `auth_basic_user_file`-Direktive (gleiches htpasswd-Format,
nur anders eingebunden — direkt im nginx-Server-Block statt in einer
`.htaccess`-Datei). Funktional identisch zu dem, was der Nutzer wollte.

Entschiedener Umfang (mit Nutzer abgestimmt):
- **Volles Feld-Set** inkl. eigener Detailseite pro Standort (nicht nur die
  paar aktuell sichtbaren Felder) — das ist die eigentliche Chance, die
  Darstellung spürbar zu verbessern.
- **Foto-Uploads** (Horizontfoto, Panorama, Horizontgrafik, weitere Fotos)
  direkt in Version 1 mit einbauen.
- **Airtable komplett ablösen**: einmalige Migration der 22 Standorte, danach
  ist die neue Admin-UI die einzige Datenpflege. Airtable-Base bleibt nur als
  Archiv stehen.

## Architektur-Überblick

- Neue MySQL-Datenbank `ago_sofi`, eigener MySQL-User mit Rechten **nur** auf
  diese DB (WordPress-DB bleibt unberührt) — das ist die eigentliche
  Sicherheits-Trennung, nicht ein eigener PHP-FPM-Pool. Der bestehende
  `php8.2-fpm.sock`-Pool wird mitbenutzt (für ein Projekt dieser Größe okay).
- `site/` bleibt weiterhin das einzige Verzeichnis, das per `deploy/deploy.sh`
  (rsync) auf den Server geht — PHP-Dateien werden einfach mitsynchronisiert,
  kein Build-Schritt nötig.
- **Wichtiger Fallstrick:** `deploy.sh` nutzt `rsync --delete`. Hochgeladene
  Fotos entstehen aber direkt auf dem Server (über das Admin-Formular) und
  existieren lokal auf dem Mac gar nicht — der nächste Deploy würde sie sonst
  löschen. Fix: `site/uploads/` wird in `deploy.sh` per `--exclude` von der
  Synchronisation ausgenommen, bleibt aber unterhalb von nginx's `root` und
  ist damit weiterhin öffentlich ausgelieferte statische Datei.
- Datenbank-Zugangsdaten liegen **außerhalb** des Git-Repos und außerhalb von
  `site/`, z. B. unter `/var/www/sofi.agorion.de-secrets/db-config.php` auf
  dem Server — `inc/db.php` bindet das über einen absoluten Pfad ein. Nie im
  Repo, nie Teil des rsync-Payloads.

## Dateistruktur (innerhalb von `site/`)

```
site/
  index.php                  (ersetzt index.html — geprüfte Standorte)
  alle-standorte.php          (ersetzt alle-standorte.html)
  standort.php                (neu: Detailseite; erreichbar unter /standort/<slug>)
  beobachten.html              (unverändert, keine DB nötig)
  impressum-datenschutz.html   (unverändert)
  css/style.css                (erweitert um Karten/Detail-Layout)
  js/map.js                    (fetch jetzt gegen api/standorte.php)
  api/
    standorte.php               (öffentliches JSON, ?filter=geprueft|alle)
  admin/
    index.php                   (Liste aller Standorte, Filter, Links)
    edit.php                     (Anlegen/Bearbeiten inkl. Foto-Upload)
    delete.php                   (POST-only, CSRF-geschützt)
  inc/
    db.php                       (PDO-Verbindung, bindet externe Config ein)
  uploads/                       (Fotos; von rsync ausgeschlossen, serverseitig)
```

## Datenmodell

**Tabelle `standorte`** (Felder aus der Airtable-Tabelle `Standorte`, ohne
rein interne/redaktionelle Felder wie Interne Notiz, Sortierreihenfolge,
Quellenhinweis, Eingangsdatum, Zuletzt geprueft — `Standort-ID`/`Koordinaten`
waren Airtable-Formelfelder, werden durch `id` bzw. direkte lat/lon ersetzt):

`id, slug, standortname, kurzbeschreibung, status (ENUM: Vorschlag, Zu
prüfen, Vor Ort geprüft, Geeignet, Eingeschränkt geeignet, Ungeeignet, Nicht
mehr verfügbar), veroeffentlicht (TINYINT), breitengrad, laengengrad,
zugaenglichkeit, parkplatz, andrang_erwartet, sicherheitsrisiken, kartenlink,
region, entfernung_bad_homburg_km, fahrzeit_minuten, horizontbewertung,
gesamtbewertung (1–5), kurze_bewertung, created_at, updated_at`

**Tabelle `standort_fotos`**: `id, standort_id (FK, ON DELETE CASCADE),
kategorie (ENUM: horizontfoto, panorama, horizontgrafik, weiteres),
dateiname, sortierung`

## Öffentliche Seiten — Filterlogik (1:1 wie bisher)

- `index.php` / API `?filter=geprueft`: `WHERE veroeffentlicht=1 AND status
  IN ('Geeignet','Eingeschränkt geeignet','Vor Ort geprüft')`
- `alle-standorte.php` / API `?filter=alle`: keine Einschränkung (alle
  Status, unabhängig von `veroeffentlicht`) — entspricht der aktuellen
  "Alle Standorte"-Seite.
- **Listendarstellung (statt iframe-Grid):** jeder Standort erscheint
  zunächst als **eine einzeilige Zusammenfassung** (Name + Status-Badge) und
  lässt sich per Klick **aufklappen** — mehrzeilig, mit Kurzbeschreibung und
  wichtigsten Fakten (Entfernung/Fahrzeit, Zugänglichkeit, Parkplatz).
  Umsetzung mit semantischem HTML (`<details>`/`<summary>` je Zeile) statt
  eigenem JS — funktioniert ohne JavaScript, ist zugänglich, passt zum
  "JavaScript aufs Nötigste"-Grundsatz des Projekts.
- Jede aufgeklappte Zeile verlinkt zusätzlich auf die volle **Detailseite**.
- **Detailseite pro Standort, eigene URL basierend auf `slug`:**
  `/standort/<slug>` (z. B. `/standort/grosser-feldberg`) — per nginx-Rewrite
  intern auf `standort.php?slug=<slug>` gemappt, damit die URL sauber bleibt
  statt eines Query-Strings. Zeigt alle Felder plus Foto-/Grafik-Galerie
  (Horizontfoto, Panorama, Horizontgrafik, weitere Fotos).
- `js/map.js`: `fetch("js/standorte.json")` → `fetch("api/standorte.php?
  filter=...")`, Antwortformat bleibt kompatibel zum bisherigen JSON-Schema;
  Marker-Popups verlinken zusätzlich auf `/standort/<slug>`.

## Admin-UI

- Zugriff nur auf `/admin/`, geschützt durch nginx `auth_basic` +
  `auth_basic_user_file /etc/nginx/.htpasswd-sofi-admin` (einmalig per
  `htpasswd` angelegt, liegt außerhalb des Webroots, kein Teil des Deploys).
- `admin/index.php`: Tabelle aller Standorte mit Status-Filter, Links zu
  Bearbeiten/Löschen.
- `admin/edit.php`: Formular für alle Felder inkl. Datei-Upload je
  Foto-Kategorie (Validierung von Dateityp/-größe, Speicherung unter
  `uploads/`, Eintrag in `standort_fotos`).
- `admin/delete.php`: POST-only mit Bestätigung und CSRF-Token, entfernt
  Zeile (Fotos kaskadierend) und zugehörige Dateien.
- PDO mit Prepared Statements durchgängig; CSRF-Token über PHP-Session.

## Migration

Einmaliges SQL-Insert-Skript aus den 22 bestehenden Airtable-Datensätzen
(vollständiges Feld-Set wird zur Implementierungszeit erneut über die
Airtable-Verbindung abgerufen, da bisher nur ein Teil der Felder geladen
wurde). Nach erfolgreicher Migration und Prüfung: Airtable-Interface-Embeds
aus den Seiten entfernen, Base bleibt nur als Archiv bestehen.

## Deploy & Doku

- `deploy/deploy.sh`: `--exclude 'uploads/***'` ergänzen.
- `deploy/nginx/sofi.agorion.de.conf`: PHP-Block (`fastcgi_pass unix:/run/
  php/php8.2-fpm.sock`, gemeinsamer Pool), `auth_basic`-Block für
  `/admin/` und Rewrite `location ~ ^/standort/([a-z0-9-]+)/?$ { rewrite ^
  /standort.php?slug=$1 last; }` ergänzen, `index` um `index.php` erweitern.
- `CLAUDE.md`/`DEPLOYMENT.md`: "keine Serverlogik/Datenbank"-Aussage durch
  die neue Architektur ersetzen, Setup-Schritte (DB anlegen, htpasswd,
  Secrets-Datei) dokumentieren — analog zur bisherigen Airtable-Doku.

## Umsetzungsreihenfolge (empfohlen, in dieser Session oder aufgeteilt)

0. Diesen Plan als `PLAN-mysql-migration.md` im Projekt-Root ablegen (neben
   `CLAUDE.md`/`README.md`/`DEPLOYMENT.md` — landet nicht auf dem Server, da
   nur `site/` deployt wird), damit er als Referenz im Repo erhalten bleibt.
1. DB/Tabellen anlegen, Migrations-Insert ausführen und prüfen.
2. `inc/db.php` + `api/standorte.php`, `js/map.js` umstellen, Karten testen.
3. `index.php`/`alle-standorte.php`/`standort.php` (Listen + Detailseite,
   neues Styling) statt iframe.
4. `admin/` (Liste, Formular, Upload, Löschen) + nginx `auth_basic`.
5. `deploy.sh`-Anpassung, Doku aktualisieren, Airtable-Embeds entfernen.

## Verifikation

- Lokale PHP-Entwicklung gegen die echte MariaDB per SSH-Tunnel (kein
  lokales MySQL nötig für dieses kleine Projekt).
- Playwright (bereits im Scratchpad eingerichtet) für Klick-Test: gefilterte
  Listen, Detailseite, Karten-API liefert korrektes JSON, Admin-Login
  (Basic-Auth-Prompt), Anlegen/Bearbeiten/Löschen inkl. Foto-Upload.
- `rsync --dry-run` prüfen, dass `uploads/` nach dem Deploy unangetastet
  bleibt.
- Vor dem Deploy auf den Live-Server: Migration und neue Seiten zunächst
  gegen die Produktions-DB, aber auf einer Testseite/-pfad verifizieren,
  bevor `index.html`/`alle-standorte.html` ersetzt werden.
